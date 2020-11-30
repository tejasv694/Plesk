#!/bin/sh
### Copyright 1999-2017. Plesk International GmbH. All rights reserved.

if [ -n "$PLESK_INSTALLER_DEBUG" -o -n "$PLESK_INSTALLER_VERBOSE" ]; then
	set -x;
fi

prog="`basename $0`"
mu="`echo $prog | awk -F '-' '{print $1}'`"
mu_flag="/usr/local/psa/var/${mu}-postconf_installed.flag"

[ -f "$mu_flag" ] && exit 0

postconf_cmd="`which postconf 2>/dev/null`"

touch "$mu_flag"
[ -n "$postconf_cmd" ] || exit 0

$postconf_cmd -e authorized_mailq_users= authorized_flush_users=

exit 0
