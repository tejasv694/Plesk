#!/bin/bash
### Copyright 1999-2017. Plesk International GmbH. All rights reserved.

if [ -n "$PLESK_INSTALLER_DEBUG" -o -n "$PLESK_INSTALLER_VERBOSE" ]; then
	set -x;
fi

prog="`basename $0`"
tmp_d="/usr/local/psa/var"
mu_flag="$tmp_d/${prog%.sh}.flag"

[ ! -f "$mu_flag" ] || exit 0

#####
PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/sbin:/usr/bin:/bin:/usr/local/psa/bin:/opt/psa/bin"
umask 022
log="/var/log/plesk/enable-ip-anonymization.log"

plesk_db() {
    MYSQL_PWD=$(cat /etc/psa/.psa.shadow) mysql -u admin -D psa -N -Br -e "$1"
}

do_work() {
	local enabled=$(plesk bin settings -g logrotate_anonymize_ips 2>/dev/null)

	if [ "$enabled" != "true" ]; then
		echo "IP anonymization not enabled"
		touch "$mu_flag"
		return 0
	fi

	rc="0"
	if grep -q "anonymize_ip" "/usr/local/psa/etc/logrotate.conf"; then
		echo "IP anonymization already successfully enabled on server-wide level"
	else
		echo "Fix IP anonymization on server-wide level"
		plesk sbin logrot_mng --system-logs --anonymize-ip=true || rc=1
	fi

	echo "Fix sites"
	plesk_db "select domains.name from domains left join dom_param on domains.id=dom_param.dom_id left join log_rotation on dom_param.val=log_rotation.id where domains.htype='vrt_hst' and dom_param.param='logrotation_id' and log_rotation.turned_on='true'" | 
	{
		while read s; do
			if [ -f "/usr/local/psa/etc/logrotate.d/$s" ] && grep -q "anonymize_ip" "/usr/local/psa/etc/logrotate.d/$s" 2>/dev/null; then
				continue
			fi

			echo "$s"
			plesk bin domain --update "$s" -log-rotate true || rc=1
		done
		return $rc
	}

	if [ "$?" -ne 0 ]; then
		rc=1
	fi

	if [ "$rc" = "1" ]; then
		echo "There were errors during re-enabling of IP address anonymization" >&2
		echo "There were errors during re-enabling of IP address anonymization"
		exit 1
	else
		echo "Successfully completed"
		touch "$mu_flag"
	fi
}

do_work | tee -a "$log"

#####

