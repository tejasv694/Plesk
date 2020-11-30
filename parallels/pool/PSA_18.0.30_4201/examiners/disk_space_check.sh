#!/bin/sh
### Copyright 1999-2020. Plesk International GmbH. All rights reserved.

# If env variable PLESK_INSTALLER_ERROR_REPORT=path_to_file is specified then in case of error
# disk_space_check.sh writes single line json report into it with the following fields:
# - "stage": "diskspacecheck"
# - "level": "error"
# - "errtype": "notenoughdiskspace"
# - "volume": volume with not enough diskspace (e.g. "/")
# - "required": required diskspace on the volume, human readable (e.g. "600 MB")
# - "available": available diskspace on the volume, human readable (e.g. "255 MB")
# - "needtofree": amount of diskspace which should be freed on the volume, human readable (e.g. "345 MB")
# - "date": time of error occurance ("2020-03-24T06:59:43,127545441+0000")
# - "error": human readable error message ("There is not enough disk space available in the / directory.")

# Required values below for Full installation are in MB. See 'du -cs -BM /*' and 'df -Pm'.

required_disk_space_cloudlinux6()
{
	case "$1" in
	/opt)	echo 450	;;
	/usr)	echo 2550	;;
	/var)	echo 600	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_cloudlinux7()
{
	case "$1" in
	/opt)	echo 300	;;
	/usr)	echo 2400	;;
	/var)	echo 600	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_centos6()
{
	case "$1" in
	/opt)	echo 250	;;
	/usr)	echo 2200	;;
	/var)	echo 600	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_centos7()
{
	case "$1" in
	/opt)	echo 450	;;
	/usr)	echo 2250	;;
	/var)	echo 600	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_centos8()
{
	case "$1" in
	/opt)	echo 480	;;
	/usr)	echo 2500	;;
	/var)	echo 600	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_vzlinux7()
{
	required_disk_space_centos7 "$1"
}

required_disk_space_redhat6()
{
	required_disk_space_centos6 "$1"
}

required_disk_space_redhat7()
{
	required_disk_space_centos7 "$1"
}

required_disk_space_redhat8()
{
	required_disk_space_centos8 "$1"
}

required_disk_space_debian8()
{
	case "$1" in
	/opt)	echo 1500	;;
	/usr)	echo 2800	;;
	/var)	echo 600	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_debian9()
{
	case "$1" in
	/opt)	echo 1500	;;
	/usr)	echo 2800	;;
	/var)	echo 600	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_debian10()
{
	case "$1" in
	/opt)	echo 1800	;;
	/usr)	echo 2300	;;
	/var)	echo 1700	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_ubuntu16()
{
	case "$1" in
	/opt)	echo 900	;;
	/usr)	echo 1800	;;
	/var)	echo 600	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_ubuntu18()
{
	case "$1" in
	/opt)	echo 900	;;
	/usr)	echo 1800	;;
	/var)	echo 600	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_ubuntu20()
{
	case "$1" in
	/opt)	echo 1800	;;
	/usr)	echo 2900	;;
	/var)	echo 1600	;;
	/tmp)	echo 100	;;
	esac
}

required_update_upgrade_disk_space()
{
	case "$1" in
	/opt)	echo 100	;;
	/usr)	echo 300	;;
	/var)	echo 600	;;
	/tmp)	echo 100	;;
	esac
}

[ -z "$PLESK_INSTALLER_DEBUG" ] || set -x
[ -z "$PLESK_INSTALLER_STRICT_MODE" ] || set -e

platform()
{
	local distrib="unknown"
	local version=""

	if [ -e /etc/debian_version ]
	then
		if [ -e /etc/lsb-release ]
		then
			. /etc/lsb-release

			distrib="$DISTRIB_ID"
			version="$DISTRIB_RELEASE"
		else
			distrib="debian"
			version="$(head -n 1 /etc/debian_version)"
		fi
	elif [ -e /etc/redhat-release ]
	then
		local header="$(head -n 1 /etc/redhat-release)"

		case "$header" in
		Red\ Hat*)      distrib="redhat" ;;
		CentOS*)        distrib="centos" ;;
		CloudLinux*)    distrib="cloudlinux" ;;
		Virtuozzo*)     distrib="vzlinux" ;;
		*)
			distrib="$(echo $header | awk '{ print $1 }')"
			;;
		esac

		version="$(echo $header | sed -e 's/[^0-9]*\([0-9.]*\)/\1/g')"
	fi

	echo "${distrib}${version%%.*}" | tr "[:upper:]" "[:lower:]"
}

# @param $1 target directory
mount_point()
{
	df -Pm $1 | awk 'NR==2 { print $6 }'
}

# @param $1 target directory
available_disk_space()
{
	df -Pm $1 | awk 'NR==2 { print $4 }'
}

# @param $1 target directory
# @param $2 mode (install/upgrade/update)
required_disk_space()
{
	if [ "$2" != "install" ]; then
		required_update_upgrade_disk_space "$1"
		return
	fi

	local p="$(platform)"
	local f="required_disk_space_$p"

	case "$(type $f 2>/dev/null)" in
	*function*)
		$f "$1"
		;;
	*)
		echo "There are no requirements defined for $p." >&2
		echo "Disk space check cannot be performed." >&2
		exit 1
		;;
	esac
}

human_readable_size()
{
	echo "$1" | awk '
		function human(x) {
			s = "MGTEPYZ";
			while (x >= 1000 && length(s) > 1) {
				x /= 1024; s = substr(s, 2);
			}
			# 0.05 below will make sure the value is rounded up
			return sprintf("%.1f %sB", x + 0.05, substr(s, 1, 1));
		}
		{ print human($1); }'
}

# @param $1 target directory
# @param $2 required disk space
check_available_disk_space()
{
	local available=$(available_disk_space "$1")
	if [ "$available" -lt "$2" ]
	then
		local needtofree
		needtofree=`human_readable_size $(($2 - $available))`
		make_error_report 'stage=diskspacecheck' 'level=error' 'errtype=notenoughdiskspace' \
			"volume=$1" "required=$2 MB" "available=$available MB" "needtofree=$needtofree" <<-EOL
				There is not enough disk space available in the $1 directory.
				You need to free up $needtofree.
		EOL
		exit 2
	fi
}

# @params are tags in format "key=value"
# Report body (human readable information) is read from stdin
# and copied to stderr.
make_error_report()
{
	local report_file="${PLESK_INSTALLER_ERROR_REPORT:-}"

	local python_bin=
	for bin in "/opt/psa/bin/python" "/usr/local/psa/bin/python" "/usr/bin/python2" "/usr/libexec/platform-python" "/usr/bin/python3"; do
		if [ -x "$bin" ]; then
			python_bin="$bin"
			break
		fi
	done

	if [ -n "$report_file" -a -x "$python_bin" ]; then
		"$python_bin" -c 'import sys, json
report_file = sys.argv[1]
error = sys.stdin.read()

sys.stderr.write(error)

data = {
    "error": error,
}

for tag in sys.argv[2:]:
    k, v = tag.split("=", 1)
    data[k] = v

with open(report_file, "a") as f:
    json.dump(data, f)
    f.write("\n")
' "$report_file" "date=$(date --utc --iso-8601=ns)" "$@"
	else
		cat - >&2
	fi
}

# @param $1 mode (install/upgrade/update)
check_disk_space()
{
	local mode="$1"
	local shared=0

	for target_directory in /opt /usr /var /tmp
	do
		local required=$(required_disk_space "$target_directory" "$mode")
		[ -n "$required" ] || exit 1

		if [ "$(mount_point $target_directory)" = "/" ]
		then
			shared=$(($shared + $required))
		else
			check_available_disk_space $target_directory $required || exit 2
		fi
	done

	check_available_disk_space "/" $shared
}

check_disk_space "$1"
