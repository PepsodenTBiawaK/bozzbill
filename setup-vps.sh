#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

application_root=/var/www/bozzbill
deployment_user=fadil
web_group=www-data
database_name=bozzbill
database_user=bozzbill_app
application_url=http://103.247.10.110:8080
script_directory=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)
environment_file="$application_root/shared/.env"
managed_marker="$application_root/.bozzbill-managed"
nginx_configuration=/etc/nginx/sites-available/bozzbill
nginx_enabled=/etc/nginx/sites-enabled/bozzbill
fpm_configuration=/etc/php/8.4/fpm/pool.d/bozzbill.conf

fail() {
    printf '%s\n' "$1" >&2
    exit 1
}

[[ $EUID -eq 0 ]] || fail 'Jalankan script ini dengan sudo.'
exec 9>/run/lock/bozzbill-setup.lock
flock -n 9 || fail 'Setup BozzBill sedang dijalankan oleh proses lain.'
id "$deployment_user" >/dev/null
deployment_group=$(id -gn "$deployment_user")
getent group "$web_group" >/dev/null
for executable in mysql nginx php8.4 php-fpm8.4 openssl install flock usermod; do
    command -v "$executable" >/dev/null || fail "Program belum tersedia: $executable"
done
[[ -f "$script_directory/deploy.sh" ]] || fail 'deploy.sh harus berada di folder yang sama.'
[[ -f "$script_directory/ssh-deploy-guard.sh" ]] || fail 'ssh-deploy-guard.sh harus berada di folder yang sama.'
mysql --batch --skip-column-names --execute='SELECT 1' >/dev/null

for directory in "$application_root" "$application_root/shared" "$application_root/shared/storage" "$application_root/releases" "$application_root/incoming"; do
    [[ ! -L "$directory" ]] || fail "Folder setup tidak boleh berupa symlink: $directory"
    [[ ! -e "$directory" || -d "$directory" ]] || fail "Path setup bukan folder: $directory"
done
for configuration in "$nginx_configuration" "$fpm_configuration" "$environment_file" "$managed_marker"; do
    [[ ! -L "$configuration" ]] || fail "File setup tidak boleh berupa symlink: $configuration"
    [[ ! -e "$configuration" || -f "$configuration" ]] || fail "Path setup bukan file biasa: $configuration"
done

if [[ -e "$application_root" && ! -f "$managed_marker" ]]; then
    fail 'Folder /var/www/bozzbill sudah ada dan bukan milik setup ini. Periksa sebelum melanjutkan.'
fi
if [[ ! -f "$managed_marker" ]]; then
    for configuration in "$nginx_configuration" "$nginx_enabled" "$fpm_configuration"; do
        [[ ! -e "$configuration" && ! -L "$configuration" ]] || fail "Konfigurasi sudah ada: $configuration"
    done
    existing_database=$(mysql --batch --skip-column-names --execute="SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '$database_name';")
    existing_user=$(mysql --batch --skip-column-names --execute="SELECT COUNT(*) FROM mysql.user WHERE User = '$database_user';")
    [[ "$existing_database" == 0 && "$existing_user" == 0 ]] || fail 'Database atau user bozzbill sudah ada. Setup berhenti untuk menjaga data yang sudah ada.'
    if ss -ltnH 'sport = :8080' | read -r _; then
        fail 'Port 8080 sudah digunakan oleh layanan lain.'
    fi
fi

if ! id -nG "$deployment_user" | tr ' ' '\n' | grep -Fxq "$web_group"; then
    usermod -a -G "$web_group" "$deployment_user"
fi

install -d -m 2750 -o "$deployment_user" -g "$web_group" \
    "$application_root" "$application_root/releases" "$application_root/incoming" "$application_root/shared"
if [[ ! -f "$managed_marker" ]]; then
    printf 'bozzbill-v1\n' > "$managed_marker"
    chown root:root "$managed_marker"
    chmod 644 "$managed_marker"
fi
[[ $(cat "$managed_marker") == bozzbill-v1 ]] || fail 'Marker setup tidak sesuai.'

install -d -m 2770 -o "$deployment_user" -g "$web_group" \
    "$application_root/shared/storage" \
    "$application_root/shared/storage/app" \
    "$application_root/shared/storage/app/private" \
    "$application_root/shared/storage/app/public" \
    "$application_root/shared/storage/framework" \
    "$application_root/shared/storage/framework/cache" \
    "$application_root/shared/storage/framework/cache/data" \
    "$application_root/shared/storage/framework/sessions" \
    "$application_root/shared/storage/framework/views" \
    "$application_root/shared/storage/logs"

if [[ ! -e "$environment_file" ]]; then
    database_password=$(openssl rand -hex 24)
    application_key=$(openssl rand -base64 32)
    environment_temporary=$(mktemp "$application_root/shared/.env.XXXXXX")
    cat > "$environment_temporary" <<EOF
APP_NAME=BozzBill
APP_ENV=production
APP_KEY=base64:$application_key
APP_DEBUG=false
APP_URL=$application_url
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12
LOG_CHANNEL=single
LOG_LEVEL=warning
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$database_name
DB_USERNAME=$database_user
DB_PASSWORD=$database_password
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_COOKIE=bozzbill_session
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
CACHE_STORE=database
CACHE_PREFIX=bozzbill_cache_
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
BROADCAST_CONNECTION=log
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@example.invalid
MAIL_FROM_NAME=BozzBill
EOF
    chown "$deployment_user:$web_group" "$environment_temporary"
    chmod 640 "$environment_temporary"
    mv -T -- "$environment_temporary" "$environment_file"
fi

[[ -f "$environment_file" && ! -L "$environment_file" ]] || fail 'shared/.env harus berupa file biasa.'
chown "$deployment_user:$web_group" "$environment_file"
chmod 640 "$environment_file"
database_password=$(sed -n 's/^DB_PASSWORD=//p' "$environment_file")
[[ "$database_password" =~ ^[a-f0-9]{48}$ ]] || fail 'Format password database tidak sesuai setup. File .env tidak diubah.'
[[ $(sed -n 's/^DB_DATABASE=//p' "$environment_file") == "$database_name" ]] || fail 'Nama database pada .env berbeda.'
[[ $(sed -n 's/^DB_USERNAME=//p' "$environment_file") == "$database_user" ]] || fail 'User database pada .env berbeda.'

mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`$database_name\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$database_user'@'127.0.0.1' IDENTIFIED BY '$database_password';
ALTER USER '$database_user'@'127.0.0.1' IDENTIFIED BY '$database_password';
GRANT ALL PRIVILEGES ON \`$database_name\`.* TO '$database_user'@'127.0.0.1';
SQL
unset database_password application_key

install -m 750 -o "$deployment_user" -g "$web_group" "$script_directory/deploy.sh" "$application_root/deploy.sh"
install -m 750 -o root -g "$deployment_group" "$script_directory/ssh-deploy-guard.sh" "$application_root/ssh-deploy-guard.sh"

configuration_backup=$(mktemp -d /run/bozzbill-setup.XXXXXX)
nginx_had_configuration=false
fpm_had_configuration=false
nginx_had_link=false
[[ ! -f "$nginx_configuration" ]] || { cp -p -- "$nginx_configuration" "$configuration_backup/nginx"; nginx_had_configuration=true; }
[[ ! -f "$fpm_configuration" ]] || { cp -p -- "$fpm_configuration" "$configuration_backup/fpm"; fpm_had_configuration=true; }
if [[ -L "$nginx_enabled" ]]; then
    [[ $(readlink -f -- "$nginx_enabled") == "$nginx_configuration" ]] || fail 'Symlink Nginx bozzbill menunjuk konfigurasi lain.'
    nginx_had_link=true
elif [[ -e "$nginx_enabled" ]]; then
    fail 'sites-enabled/bozzbill harus berupa symlink.'
fi

restore_configurations() {
    if [[ "$nginx_had_configuration" == true ]]; then
        cp -p -- "$configuration_backup/nginx" "$nginx_configuration"
    else
        rm -f -- "$nginx_configuration"
    fi
    if [[ "$fpm_had_configuration" == true ]]; then
        cp -p -- "$configuration_backup/fpm" "$fpm_configuration"
    else
        rm -f -- "$fpm_configuration"
    fi
    [[ "$nginx_had_link" == true ]] || rm -f -- "$nginx_enabled"
}

configuration_changes_started=false
configuration_changes_committed=false
cleanup() {
    setup_exit_code=$?
    if [[ "$configuration_changes_started" == true && "$configuration_changes_committed" != true ]]; then
        restore_configurations
        systemctl reload php8.4-fpm || true
        systemctl reload nginx || true
    fi
    rm -f -- "$configuration_backup/nginx" "$configuration_backup/fpm"
    rmdir -- "$configuration_backup"
    return "$setup_exit_code"
}
trap cleanup EXIT

configuration_changes_started=true
cat > "$fpm_configuration" <<'FPM'
[bozzbill]
user = www-data
group = www-data
listen = /run/php/php8.4-fpm-bozzbill.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = ondemand
pm.max_children = 2
pm.process_idle_timeout = 10s
pm.max_requests = 300
php_admin_value[memory_limit] = 128M
php_admin_value[upload_max_filesize] = 2M
php_admin_value[post_max_size] = 3M
php_admin_flag[opcache.validate_timestamps] = on
FPM

cat > "$nginx_configuration" <<'NGINX'
server {
    listen 8080;
    listen [::]:8080;
    server_name 103.247.10.110;
    root /var/www/bozzbill/current/public;
    index index.php;
    charset utf-8;
    client_max_body_size 3m;
    access_log /var/log/nginx/bozzbill-access.log;
    error_log /var/log/nginx/bozzbill-error.log;
    add_header X-Frame-Options SAMEORIGIN always;
    add_header X-Content-Type-Options nosniff always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt { access_log off; log_not_found off; }

    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root/index.php;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_pass unix:/run/php/php8.4-fpm-bozzbill.sock;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ \.php$ {
        return 404;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX
chmod 644 "$nginx_configuration" "$fpm_configuration"
ln -sfn -- "$nginx_configuration" "$nginx_enabled"

if ! php-fpm8.4 --test || ! nginx -t; then
    fail 'Konfigurasi baru gagal validasi. Konfigurasi sebelumnya dipulihkan.'
fi

if ! systemctl reload php8.4-fpm || ! systemctl reload nginx; then
    fail 'Reload layanan gagal. Konfigurasi sebelumnya dipulihkan.'
fi
configuration_changes_committed=true

if command -v ufw >/dev/null && ufw status | head -n 1 | grep -q '^Status: active'; then
    ufw allow 8080/tcp comment 'BozzBill temporary access'
fi

printf '%s\n' 'Setup BozzBill selesai.' 'Folder: /var/www/bozzbill' "Alamat sementara: $application_url" 'Database dan APP_KEY dibuat tanpa menampilkan kredensial.' 'Selanjutnya upload release aplikasi dan buat admin melalui Artisan.'
