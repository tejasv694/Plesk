#!/bin/sh
### Copyright 1999-2017. Plesk International GmbH. All rights reserved.

required_disk_space_cloudlinux6()
{
	case "$1" in
	/opt)	echo 450	;;
	/usr)	echo 2550	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_cloudlinux7()
{
	case "$1" in
	/opt)	echo 300	;;
	/usr)	echo 2400	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_centos6()
{
	case "$1" in
	/opt)	echo 250	;;
	/usr)	echo 2200	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_centos7()
{
	case "$1" in
	/opt)	echo 450	;;
	/usr)	echo 2250	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_vzlinux7()
{
	required_disk_space_centos7 "$1"
}

required_disk_space_redhat6()
{
	case "$1" in
	/opt)	echo 250	;;
	/usr)	echo 2200	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_redhat7()
{
	case "$1" in
	/opt)	echo 450	;;
	/usr)	echo 2400	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_debian7()
{
	case "$1" in
	/opt)	echo 750	;;
	/usr)	echo 2500	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_debian8()
{
	case "$1" in
	/opt)	echo 1500	;;
	/usr)	echo 2800	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_debian9()
{
	case "$1" in
	/opt)	echo 1500	;;
	/usr)	echo 2800	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_ubuntu12()
{
	case "$1" in
	/opt)	echo 750	;;
	/usr)	echo 2550	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_ubuntu14()
{
	case "$1" in
	/opt)	echo 900	;;
	/usr)	echo 1800	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_ubuntu16()
{
	case "$1" in
	/opt)	echo 900	;;
	/usr)	echo 1800	;;
	/tmp)	echo 100	;;
	esac
}

required_disk_space_ubuntu18()
{
	case "$1" in
	/opt)	echo 900	;;
	/usr)	echo 1800	;;
	/tmp)	echo 100	;;
	esac
}

required_update_upgrade_disk_space()
{
	case "$1" in
	/opt)	echo 100	;;
	/usr)	echo 300	;;
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
		echo "There is not enough disk space available in the $1 directory." >&2
		echo "You need to free up `human_readable_size $(($2 - $available))`." >&2
		exit 2
	fi
}

# @param $1 mode (install/upgrade/update)
check_disk_space()
{
	local mode="$1"
	local shared=0

	for target_directory in /opt /usr /tmp
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
