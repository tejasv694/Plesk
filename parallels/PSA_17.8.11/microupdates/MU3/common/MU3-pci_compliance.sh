#!/bin/sh
### Copyright 1999-2017. Parallels IP Holdings GmbH. All Rights Reserved.

if [ -n "$PLESK_INSTALLER_DEBUG" -o -n "$PLESK_INSTALLER_VERBOSE" ]; then
	set -x;
fi

prog="`basename $0`"
mu="`echo $prog | awk -F '-' '{print $1}'`"
mu_flag="/usr/local/psa/var/${mu}-pci_compliance.flag"

[ -f "$mu_flag" ] && exit 0

oldcfg="/etc/sw-cp-server/conf.d/pci-compliance.conf"

[ -f "$oldcfg" ] && rm -f $oldcfg

service sw-cp-server restart

touch "$mu_flag"
exit 0
