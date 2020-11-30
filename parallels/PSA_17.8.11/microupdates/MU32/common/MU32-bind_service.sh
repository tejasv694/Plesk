#!/bin/sh
### Copyright 1999-2017. Plesk International GmbH. All rights reserved.

if [ -n "$PLESK_INSTALLER_DEBUG" -o -n "$PLESK_INSTALLER_VERBOSE" ]; then
	set -x;
fi

prog="`basename $0`"
mu="`echo $prog | awk -F '-' '{print $1}'`"
mu_flag="/usr/local/psa/var/${mu}-bind_service_override_installed.flag"

[ -f "$mu_flag" ] && exit 0

bind_conf_d="/usr/lib/systemd/system/named.service.d"
bind_conf="$bind_conf_d/disable.conf"

[ ! -f "/etc/os-release" ] && touch "$mu_flag" && exit 0

. /etc/os-release

[ "$ID" != "centos" -a "$ID" != "rhel" -a "$ID" != "cloudlinux" ] && touch "$mu_flag" && exit 0

if [ "${VERSION_ID%%.*}" = "7" ]; then
	[ -d "$bind_conf_d" ] || mkdir -p $bind_conf_d

	cat > "$bind_conf" <<- EOF
		[Service]
		ExecStartPre=
		ExecStartPre=/bin/sh -c "echo \"Service 'named' was not restarted because Plesk uses 'named' in chroot environment.\nIf you want to restart the 'named' service, please use 'service named-chroot restart'.\"; exit 1;"
	EOF
fi

touch "$mu_flag"
exit 0
