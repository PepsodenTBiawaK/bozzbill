#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

original_command=${SSH_ORIGINAL_COMMAND:-}
incoming_pattern='/var/www/bozzbill/incoming/[a-f0-9]{40}-[0-9]+-[0-9]+[.]tar[.]gz'

if [[ "$original_command" =~ ^scp\ -t\ ($incoming_pattern)$ ]]; then
    exec /usr/bin/scp -t -- "${BASH_REMATCH[1]}"
fi

if [[ "$original_command" =~ ^bash\ /var/www/bozzbill/deploy[.]sh\ ($incoming_pattern)\ ([a-f0-9]{40})$ ]]; then
    exec /bin/bash /var/www/bozzbill/deploy.sh "${BASH_REMATCH[1]}" "${BASH_REMATCH[2]}"
fi

printf '%s\n' 'Key deployment ini hanya dapat mengirim artifact dan menjalankan deploy BozzBill.' >&2
exit 126
