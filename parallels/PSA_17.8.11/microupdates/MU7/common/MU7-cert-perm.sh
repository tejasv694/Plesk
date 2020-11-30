#!/bin/bash
### Copyright 2018. Plesk International GmbH. All rights reserved.

if [ -n "$PLESK_INSTALLER_DEBUG" -o -n "$PLESK_INSTALLER_VERBOSE" ]; then
	set -x;
fi

prog="`basename $0`"
tmp_d="/usr/local/psa/var"
mu_flag="$tmp_d/${prog}.flag"

[ ! -f "$mu_flag" ] || exit 0

#####

CERTIFICATES_D="/usr/local/psa/var/certificates"
[ -n "`ls $CERTIFICATES_D/ 2>/dev/null`" ] && chmod 0400 "$CERTIFICATES_D"/*

#####

touch "$mu_flag"
