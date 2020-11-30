#!/bin/sh
### Copyright 1999-2018. Plesk International GmbH. All rights reserved.

if [ -n "$PLESK_INSTALLER_DEBUG" -o -n "$PLESK_INSTALLER_VERBOSE" ]; then
	set -x;
fi

prog="`basename $0`"
tmp_d="/usr/local/psa/var"
mu="`echo $prog | awk -F '-' '{print $1}'`"
mu_flag="$tmp_d/${mu}-fs_passwd.flag"
fs_passwd_d="/var/www/vhosts/fs-passwd"

[ -d "$fs_passwd_d" ] || touch $mu_flag
[ -f "$mu_flag" ] && exit 0

[ -f "/etc/debian_version" ] && apache_group="www-data" || apache_group="apache" 

chown psaadm:$apache_group $fs_passwd_d
chmod 0750 $fs_passwd_d

if [ -f "$fs_passwd_d/dav.passwd" ]; then
		chown psaadm:$apache_group $fs_passwd_d/dav.passwd
		chmod 0640 $fs_passwd_d/dav.passwd
fi

touch $mu_flag
exit 0
