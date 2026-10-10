#!/usr/bin/env bash

set -Eeuo pipefail
umask 0027

readonly APP_ROOT='/var/www/bozzbill'
readonly RELEASES_DIRECTORY="$APP_ROOT/releases"
readonly SHARED_DIRECTORY="$APP_ROOT/shared"
readonly CURRENT_LINK="$APP_ROOT/current"
readonly HEALTH_URL='http://127.0.0.1:8080/up'
readonly RELEASES_TO_KEEP=4

fail() {
    printf 'Deployment failed: %s\n' "$*" >&2
    exit 1
}

[[ $# -eq 2 ]] || fail 'Usage: deploy.sh /var/www/bozzbill/incoming/<sha>-<run>-<attempt>.tar.gz <sha>'

archive_path="$1"
commit_sha="$2"
[[ "$commit_sha" =~ ^[a-f0-9]{40}$ ]] || fail 'Invalid commit SHA.'
[[ "$archive_path" =~ ^/var/www/bozzbill/incoming/([a-f0-9]{40}-[0-9]+-[0-9]+)\.tar\.gz$ ]] || fail 'Invalid archive path.'
release_id="${BASH_REMATCH[1]}"
[[ "${release_id%%-*}" == "$commit_sha" ]] || fail 'Archive and commit SHA do not match.'
[[ -f "$archive_path" && ! -L "$archive_path" ]] || fail 'The release archive is missing or is a symbolic link.'
[[ "$(realpath -e -- "$archive_path")" == "$archive_path" ]] || fail 'The archive must be inside the deployment incoming directory.'

for directory in "$APP_ROOT" "$RELEASES_DIRECTORY" "$SHARED_DIRECTORY" "$SHARED_DIRECTORY/storage"; do
    [[ -d "$directory" && ! -L "$directory" ]] || fail "Server setup is incomplete: $directory"
done
[[ -f "$SHARED_DIRECTORY/.env" ]] || fail 'The shared production .env file is missing.'
id -nG | tr ' ' '\n' | grep -Fxq www-data || fail 'The deployment user must belong to www-data; rerun setup-vps.sh and reconnect.'

exec 9> "$APP_ROOT/.deploy.lock"
flock -n 9 || fail 'Another deployment is already running.'

release_directory="$RELEASES_DIRECTORY/$release_id"
temporary_link="$APP_ROOT/.current-$release_id"
previous_release=''
switched=false
committed=false

if [[ -L "$CURRENT_LINK" ]]; then
    previous_release="$(readlink -f -- "$CURRENT_LINK")"
    [[ "$previous_release" =~ ^/var/www/bozzbill/releases/[a-f0-9]{40}-[0-9]+-[0-9]+$ && -d "$previous_release" ]] || fail 'The current release points outside the releases directory.'
elif [[ -e "$CURRENT_LINK" ]]; then
    fail 'The current application path must be a symbolic link.'
fi

cleanup() {
    local exit_status=$?
    trap - EXIT HUP INT TERM

    if (( exit_status != 0 )) && [[ "$switched" == true && "$committed" == false ]]; then
        if [[ -n "$previous_release" ]]; then
            if ln -sfn -- "$previous_release" "$temporary_link" && mv -Tf -- "$temporary_link" "$CURRENT_LINK"; then
                printf 'The previous application release has been restored. Database migrations were not reversed.\n' >&2
            else
                printf 'The previous application release could not be restored; check the current symlink manually.\n' >&2
            fi
        else
            rm -f -- "$CURRENT_LINK"
            printf 'The first release failed its health check; no current release was retained.\n' >&2
        fi
    fi

    rm -f -- "$temporary_link" "$archive_path" || true
    exit "$exit_status"
}

trap cleanup EXIT
trap 'exit 129' HUP
trap 'exit 130' INT
trap 'exit 143' TERM

[[ ! -e "$release_directory" && ! -L "$release_directory" ]] || fail 'This release directory already exists; rerun the workflow to create a new release.'

archive_entries="$(tar -tzf "$archive_path")"
if ! printf '%s\n' "$archive_entries" | awk '
    /^\// { exit 1 }
    /(^|\/)\.\.($|\/)/ { exit 1 }
    END { if (NR == 0) exit 1 }
'; then
    fail 'The archive contains unsafe paths.'
fi

mkdir -- "$release_directory"
tar -xzf "$archive_path" --no-same-owner --no-same-permissions -C "$release_directory"
[[ -f "$release_directory/.release-sha" ]] || fail 'The release has no commit identifier.'
[[ "$(tr -d '\r\n' < "$release_directory/.release-sha")" == "$commit_sha" ]] || fail 'The release commit identifier does not match.'

for file in artisan vendor/autoload.php public/index.php public/build/manifest.json; do
    [[ -f "$release_directory/$file" ]] || fail "The release is missing $file."
done
[[ ! -e "$release_directory/.env" && ! -L "$release_directory/.env" ]] || fail 'The archive must not contain an environment file.'
[[ ! -e "$release_directory/storage" && ! -L "$release_directory/storage" ]] || fail 'The archive must not contain runtime storage.'
[[ ! -e "$release_directory/public/storage" && ! -L "$release_directory/public/storage" ]] || fail 'The archive must not contain the public storage link.'
[[ ! -e "$release_directory/public/hot" ]] || fail 'The archive contains a development Vite server marker.'

ln -s -- "$SHARED_DIRECTORY/.env" "$release_directory/.env"
ln -s -- "$SHARED_DIRECTORY/storage" "$release_directory/storage"
chmod -R g+rX -- "$release_directory"
chmod g+w -- "$release_directory/bootstrap/cache"

cd -- "$release_directory"
printf 'Preparing release %s\n' "$release_id"
umask 0007
php artisan package:discover --no-interaction
php artisan migrate --force --no-interaction
php artisan db:seed --class=ProductionSeeder --force --no-interaction
php artisan storage:link --no-interaction
php artisan optimize --no-interaction
umask 0027

ln -s -- "$release_directory" "$temporary_link"
mv -Tf -- "$temporary_link" "$CURRENT_LINK"
switched=true

healthy=false
for attempt in 1 2 3 4 5 6; do
    if curl --fail --silent --show-error --max-time 5 "$HEALTH_URL" > /dev/null; then
        healthy=true
        break
    fi
    sleep 2
done
[[ "$healthy" == true ]] || fail 'The new application did not pass the HTTP health check.'
committed=true

retained_count=0
while IFS= read -r candidate; do
    [[ "$candidate" =~ ^[a-f0-9]{40}-[0-9]+-[0-9]+$ ]] || continue
    retained_count=$((retained_count + 1))
    candidate_directory="$RELEASES_DIRECTORY/$candidate"
    if (( retained_count <= RELEASES_TO_KEEP )) || [[ "$candidate_directory" == "$release_directory" || "$candidate_directory" == "$previous_release" ]]; then
        continue
    fi
    if [[ -d "$candidate_directory" && ! -L "$candidate_directory" && "$(realpath -e -- "$candidate_directory")" == "$candidate_directory" ]]; then
        rm -rf --one-file-system -- "$candidate_directory" || printf 'Warning: could not remove old release %s\n' "$candidate" >&2
    fi
done < <(find -P "$RELEASES_DIRECTORY" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %f\n' | sort -nr | awk '{ print $2 }')

printf 'Deployment completed: %s\n' "$release_id"
