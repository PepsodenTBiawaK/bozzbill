#!/usr/bin/env bash

set -Eeuo pipefail
umask 0027

readonly APP_ROOT='/var/www/bozzbill'
readonly DOMAIN='bozzbill.my.id'
readonly SERVER_IP='103.247.10.110'
readonly NGINX_SITE='/etc/nginx/sites-available/bozzbill'
readonly NGINX_SNIPPET='/etc/nginx/snippets/bozzbill-app.conf'
readonly ENV_FILE="$APP_ROOT/shared/.env"
readonly ACME_ROOT="$APP_ROOT/acme"
readonly CERTIFICATE='/etc/letsencrypt/live/bozzbill.my.id'
readonly RENEWAL_HOOK='/etc/letsencrypt/renewal-hooks/deploy/bozzbill-reload-nginx.sh'

fail() {
    printf 'Konfigurasi domain berhenti: %s\n' "$*" >&2
    exit 1
}

[[ $EUID -eq 0 ]] || fail 'Jalankan dengan sudo bash /home/fadil/bozzbill-deployment/configure-domain.sh'
for executable in nginx certbot php8.4 python3 curl dig flock runuser openssl; do
    command -v "$executable" > /dev/null || fail "Program belum tersedia: $executable"
done
[[ -f "$APP_ROOT/.bozzbill-managed" && -L "$APP_ROOT/current" ]] || fail 'Instalasi BozzBill belum siap.'
[[ -S /run/php/php8.4-fpm-bozzbill.sock ]] || fail 'PHP-FPM BozzBill belum aktif.'
[[ -f "$ENV_FILE" && ! -L "$ENV_FILE" ]] || fail 'File environment BozzBill tidak valid.'
[[ -f "$NGINX_SITE" && ! -L "$NGINX_SITE" ]] || fail 'File Nginx BozzBill tidak valid.'
[[ ! -L "$NGINX_SNIPPET" ]] || fail 'Snippet Nginx tidak boleh berupa symlink.'
[[ $(readlink -f /etc/nginx/sites-enabled/bozzbill) == "$NGINX_SITE" ]] || fail 'Site Nginx BozzBill belum diaktifkan.'

exec 9>> "$APP_ROOT/.deploy.lock"
flock -n 9 || fail 'Deployment sedang berjalan. Coba lagi setelah selesai.'

printf 'Memeriksa DNS authoritative Rumahweb...\n'
for hostname in "$DOMAIN" "www.$DOMAIN"; do
    resolved_count=0
    for resolver in nsid1.rumahweb.com nsid2.rumahweb.net nsid3.rumahweb.biz nsid4.rumahweb.org; do
        answers=$(dig +time=3 +tries=1 +short @"$resolver" "$hostname" A 2>/dev/null | awk '/^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$/' || true)
        if [[ "$answers" == "$SERVER_IP" ]]; then
            resolved_count=$((resolved_count + 1))
        elif [[ -n "$answers" ]]; then
            fail "DNS $hostname pada $resolver menunjuk ke IP lain. Periksa A record."
        fi
        ipv6=$(dig +time=3 +tries=1 +short @"$resolver" "$hostname" AAAA 2>/dev/null | awk '!/^;;/ && NF' || true)
        [[ -z "$ipv6" ]] || fail "Ada record AAAA pada $hostname. Periksa alamat IPv6 sebelum memasang SSL."
    done
    (( resolved_count >= 2 )) || fail "DNS $hostname belum menunjuk ke $SERVER_IP pada nameserver Rumahweb. Tunggu propagasi, lalu jalankan perintah yang sama."
done

nginx -t
readonly BACKUP_DIRECTORY="$APP_ROOT/backups/domain-$(date -u +%Y%m%d-%H%M%S)"
install -d -m 0700 "$BACKUP_DIRECTORY"
cp -a -- "$NGINX_SITE" "$BACKUP_DIRECTORY/nginx-site"
cp -a -- "$ENV_FILE" "$BACKUP_DIRECTORY/environment"
if [[ -f "$NGINX_SNIPPET" ]]; then
    cp -a -- "$NGINX_SNIPPET" "$BACKUP_DIRECTORY/nginx-snippet"
fi
readonly O5_CHECKSUM=$(sha256sum /etc/nginx/sites-available/o5hub | awk '{print $1}')
configuration_changed=false
environment_changed=false
completed=false

rebuild_cache() {
    runuser -u fadil -- bash -c 'umask 0007; cd /var/www/bozzbill/current; php8.4 artisan optimize --no-interaction'
}

cleanup() {
    local exit_status=$?
    trap - EXIT
    if (( exit_status != 0 )) && [[ "$completed" == false ]]; then
        printf 'Mengembalikan konfigurasi sebelumnya...\n' >&2
        if [[ "$configuration_changed" == true ]]; then
            cp -a -- "$BACKUP_DIRECTORY/nginx-site" "$NGINX_SITE"
            if [[ -f "$BACKUP_DIRECTORY/nginx-snippet" ]]; then
                cp -a -- "$BACKUP_DIRECTORY/nginx-snippet" "$NGINX_SNIPPET"
            else
                rm -f -- "$NGINX_SNIPPET"
            fi
        fi
        if [[ "$environment_changed" == true ]]; then
            cp -a -- "$BACKUP_DIRECTORY/environment" "$ENV_FILE"
            rebuild_cache || true
        fi
        nginx -t && systemctl reload nginx || true
        printf 'Backup konfigurasi: %s\n' "$BACKUP_DIRECTORY" >&2
    fi
    exit "$exit_status"
}
trap cleanup EXIT

install -d -m 0755 "$ACME_ROOT/.well-known/acme-challenge"
configuration_changed=true
cat > "$NGINX_SNIPPET" <<'NGINX'
root /var/www/bozzbill/current/public;
index index.php;
charset utf-8;
client_max_body_size 3m;
access_log /var/log/nginx/bozzbill-access.log;
error_log /var/log/nginx/bozzbill-error.log;
add_header X-Frame-Options SAMEORIGIN always;
add_header X-Content-Type-Options nosniff always;

location / { try_files $uri $uri/ /index.php?$query_string; }
location = /favicon.ico { access_log off; log_not_found off; }
location = /robots.txt { access_log off; log_not_found off; }
location = /index.php {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $realpath_root/index.php;
    fastcgi_param DOCUMENT_ROOT $realpath_root;
    fastcgi_pass unix:/run/php/php8.4-fpm-bozzbill.sock;
    fastcgi_hide_header X-Powered-By;
}
location ~ \.php$ { return 404; }
location ~ /\.(?!well-known).* { deny all; }
NGINX
chmod 0644 "$NGINX_SNIPPET"

if [[ ! -f "$CERTIFICATE/fullchain.pem" ]]; then
    cat > "$NGINX_SITE" <<'NGINX'
server {
    listen 8080;
    listen [::]:8080;
    server_name 103.247.10.110;
    include /etc/nginx/snippets/bozzbill-app.conf;
}
server {
    listen 80;
    listen [::]:80;
    server_name bozzbill.my.id www.bozzbill.my.id;
    location ^~ /.well-known/acme-challenge/ {
        root /var/www/bozzbill/acme;
        default_type text/plain;
        try_files $uri =404;
    }
    location / { return 503; }
}
NGINX
    nginx -t
    systemctl reload nginx
    certbot certonly --webroot --webroot-path "$ACME_ROOT" \
        --cert-name "$DOMAIN" -d "$DOMAIN" -d "www.$DOMAIN" \
        --non-interactive --agree-tos --register-unsafely-without-email
fi

openssl x509 -in "$CERTIFICATE/fullchain.pem" -noout -checkhost "$DOMAIN" | grep -q 'does match' || fail 'Sertifikat tidak mencakup domain utama.'
openssl x509 -in "$CERTIFICATE/fullchain.pem" -noout -checkhost "www.$DOMAIN" | grep -q 'does match' || fail 'Sertifikat tidak mencakup www.'
openssl x509 -in "$CERTIFICATE/fullchain.pem" -noout -checkend 86400 > /dev/null || fail 'Sertifikat kedaluwarsa atau segera berakhir.'

cat > "$NGINX_SITE" <<'NGINX'
server {
    listen 127.0.0.1:8080;
    server_name localhost;
    include /etc/nginx/snippets/bozzbill-app.conf;
}
server {
    listen 80;
    listen [::]:80;
    server_name bozzbill.my.id www.bozzbill.my.id;
    location ^~ /.well-known/acme-challenge/ {
        root /var/www/bozzbill/acme;
        default_type text/plain;
        try_files $uri =404;
    }
    location / { return 301 https://bozzbill.my.id$request_uri; }
}
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.bozzbill.my.id;
    ssl_certificate /etc/letsencrypt/live/bozzbill.my.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/bozzbill.my.id/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    return 301 https://bozzbill.my.id$request_uri;
}
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name bozzbill.my.id;
    ssl_certificate /etc/letsencrypt/live/bozzbill.my.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/bozzbill.my.id/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_session_cache shared:BozzBillSSL:10m;
    ssl_session_timeout 1d;
    ssl_session_tickets off;
    include /etc/nginx/snippets/bozzbill-app.conf;
}
NGINX
chmod 0644 "$NGINX_SITE"
nginx -t

environment_changed=true
python3 <<'PYTHON'
from pathlib import Path
import os
import tempfile

path = Path('/var/www/bozzbill/shared/.env')
settings = {'APP_URL': 'https://bozzbill.my.id', 'SESSION_SECURE_COOKIE': 'true', 'SESSION_DOMAIN': 'null'}
lines = []
for line in path.read_text().splitlines():
    key = line.split('=', 1)[0]
    if key in settings:
        continue
    lines.append(line)
lines.extend(f'{key}={value}' for key, value in settings.items())
metadata = path.stat()
descriptor, temporary_path = tempfile.mkstemp(prefix='.domain-env-', dir=path.parent)
try:
    os.fchmod(descriptor, metadata.st_mode & 0o777)
    os.fchown(descriptor, metadata.st_uid, metadata.st_gid)
    with os.fdopen(descriptor, 'w') as output:
        output.write('\n'.join(lines) + '\n')
    os.replace(temporary_path, path)
finally:
    if os.path.exists(temporary_path):
        os.unlink(temporary_path)
PYTHON
rebuild_cache
systemctl reload nginx

install -d -m 0755 /etc/letsencrypt/renewal-hooks/deploy
cat > "$RENEWAL_HOOK" <<'BASH'
#!/usr/bin/env bash
set -euo pipefail
if [[ "${RENEWED_LINEAGE:-}" == '/etc/letsencrypt/live/bozzbill.my.id' ]]; then
    /usr/sbin/nginx -t
    /usr/bin/systemctl reload nginx
fi
BASH
chmod 0750 "$RENEWAL_HOOK"
systemctl enable --now certbot.timer

curl --fail --silent --show-error --max-time 15 --resolve "$DOMAIN:443:127.0.0.1" "https://$DOMAIN/up" > /dev/null
curl --fail --silent --show-error --max-time 15 --resolve "$DOMAIN:443:127.0.0.1" "https://$DOMAIN/login" > /dev/null
curl --fail --silent --show-error --max-time 15 http://127.0.0.1:8080/up > /dev/null
[[ $(sha256sum /etc/nginx/sites-available/o5hub | awk '{print $1}') == "$O5_CHECKSUM" ]] || fail 'Konfigurasi O5 berubah; periksa server.'
completed=true
printf '\nKonfigurasi domain selesai.\nAlamat: https://bozzbill.my.id\nWWW otomatis menuju domain utama.\nBackup konfigurasi: %s\n' "$BACKUP_DIRECTORY"
