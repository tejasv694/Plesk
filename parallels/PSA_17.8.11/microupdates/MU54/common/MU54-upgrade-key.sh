#!/bin/sh
### Copyright 1999-2019. Plesk International GmbH. All rights reserved.

if [ -n "$PLESK_INSTALLER_DEBUG" -o -n "$PLESK_INSTALLER_VERBOSE" ]; then
	set -x;
fi

prog="`basename $0`"
tmp_d="/usr/local/psa/var"
mu_flag="$tmp_d/${prog%.sh}.flag"

[ ! -f "$mu_flag" ] || exit 0

#####

PATH="/usr/lib64/plesk-9.0:/usr/lib/plesk-9.0:$PATH"
key-upgrade

#####

touch "$mu_flag"
