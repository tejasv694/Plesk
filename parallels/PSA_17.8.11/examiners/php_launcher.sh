#!/bin/sh
### Copyright 1999-2017. Plesk International GmbH. All rights reserved.

die()
{
	echo $*
	exit 1
}

[ -n "$1" ] || die "Usage: $0 php_script [args...]"

[ "X${PLESK_INSTALLER_DEBUG}" = "X" ] || set -x
[ "X${PLESK_INSTALLER_STRICT_MODE}" = "X" ] || set -e

php_bin=

lookup()
{
	[ -z "$php_bin" ] || return

	local paths="$1"
	local name="$2"

	for path in $paths; do
		if [ -x "$path/$name" ]; then
			php_bin="$path/$name"
			break
		fi
	done
}

lookup "/usr/local/psa/admin/bin /opt/psa/admin/bin" "php"
lookup "/usr/local/psa/bin /opt/psa/bin" "sw-engine-pleskrun"

[ -n "$php_bin" ] || \
	die "Unable to locate the sw-engine PHP interpreter to execute the script. Make sure that Parallels Plesk Panel is installed on this server."

exec "${php_bin}" "$@"
