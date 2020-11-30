#!/bin/sh
### Copyright 1999-2020. Plesk International GmbH. All rights reserved.

die()
{
	echo "$*"
	exit 1
}

[ -f "$1" ] || die "Usage: $0 PEX [args...]"

[ "X${PLESK_INSTALLER_DEBUG}" = "X" ] || set -x
[ "X${PLESK_INSTALLER_STRICT_MODE}" = "X" ] || set -e

python_bin=

for bin in "/opt/psa/bin/python" "/usr/local/psa/bin/python" "/usr/bin/python2" "/usr/libexec/platform-python" "/usr/bin/python3"; do
	if [ -x "$bin" ]; then
		python_bin="$bin"
		break
	fi
done

[ -x "$python_bin" ] ||
	die "Unable to locate Python interpreter to execute the script."

exec "$python_bin" "$@"
