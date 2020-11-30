#!/bin/bash

[ ! -f '/usr/local/psa/var/MU68-modsecurity-update.flag' ] || exit 0

export LANG=C LANGUAGE=C LC_ALL=C

[ -z "$PLESK_INSTALLER_DEBUG" ] || set -x

[ -x /usr/bin/dpkg ] || exit 0

# new libapache2-modsecurity-plesk already installed
! dpkg -s libapache2-modsecurity-plesk > /dev/null 2>&1 || exit 0

# check if security2 module is loaded to reenable it in modsecurity_check_post.sh
if /usr/sbin/apache2ctl -M | grep -q security2; then
	touch /var/run/plesk_enable_modsecurity.flag
fi

dpkg -l libapache2-mod-security2 2>/dev/null | grep -q '^in' || exit 0

DEBIAN_FRONTEND=noninteractive apt-get remove -y libapache2-mod-security2 modsecurity-crs aum
