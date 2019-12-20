#!/bin/sh
set -e

CA_CERT_PATH=${CA_CERT_PATH:-"/usr/local/share/ca-certificates"}

# If there are files within the CA Certificates path, run update-ca-certificates
if [  -e "${CA_CERT_PATH}" ] && [ "$(ls -A "${CA_CERT_PATH}")" ]; then
    update-ca-certificates 1> /tmp/update-ca-certificates.log
fi

/opt/php-version-audit/php-version-audit "$@"
