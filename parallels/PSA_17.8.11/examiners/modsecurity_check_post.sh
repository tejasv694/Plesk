#!/bin/bash

[ ! -f '/usr/local/psa/var/MU68-modsecurity-update.flag' ] || exit 0

export LANG=C LANGUAGE=C LC_ALL=C

[ -z "$PLESK_INSTALLER_DEBUG" ] || set -x

[ -f /var/run/plesk_enable_modsecurity.flag ] || exit 0

/usr/local/psa/admin/sbin/httpd_modules_ctl -e security2
rm -f /var/run/plesk_enable_modsecurity.flag

touch '/usr/local/psa/var/MU68-modsecurity-update.flag'
