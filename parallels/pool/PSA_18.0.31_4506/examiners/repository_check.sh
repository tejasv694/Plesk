#!/bin/sh
### Copyright 1999-2020. Plesk International GmbH. All rights reserved.

# If env variable PLESK_INSTALLER_ERROR_REPORT=path_to_file is specified then in case of error
# repository_check.sh writes single line json report into it with the following fields:
# - "stage": "repositorycheck"
# - "level": "error"
# - "errtype" is one of the following:
#   * "reponotenabled" - required repository is not enabled.
#   * "configmanagernotinstalled" - dnf config-manager is disabled.
# - "repo": required repository name.
# - "date": time of error occurance ("2020-03-24T06:59:43,127545441+0000")
# - "error": human readable error message.

[ -z "$PLESK_INSTALLER_DEBUG" ] || set -x
[ -z "$PLESK_INSTALLER_STRICT_MODE" ] || set -e

export LC_ALL=C
unset GREP_OPTIONS

SKIP_FLAG="/tmp/plesk-installer-skip-repository-check.flag"
RET_FATAL=2

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

report_no_repo()
{
	local repo="$1"

	make_error_report 'stage=repositorycheck' 'level=error' 'errtype=reponotenabled' "repo=$repo" <<-EOL
		Plesk installation requires '$repo' OS repository to be enabled.
		Make sure it is available and enabled, then try again.
	EOL
}

report_dnf_no_config_manager()
{
	make_error_report 'stage=repositorycheck' 'level=error' 'errtype=configmanagernotinstalled' <<-EOL
		Failed to install config-manager dnf plugin.
		Make sure repositories configuration of dnf package manager is correct
		(use 'dnf repolist --verbose' to get its actual state), then try again.
	EOL
}

has_dnf_enabled_repo()
{
	local repo="$1"

	# note: --noplugins may cause failure and empty output on RedHat
	dnf repoinfo --enabled --cacheonly -q | egrep -q "^Repo-id\s*: $repo\s*$"
}

has_dnf_config_manager()
{
	dnf config-manager --help >/dev/null 2>&1
}

install_dnf_config_manager()
{
	dnf install --disablerepo 'PLESK_*' -q -y 'dnf-command(config-manager)'
}

check_repos_centos8()
{
	[ "$1" = "install" ] || return 0

	if ! has_dnf_config_manager && ! install_dnf_config_manager; then
		report_dnf_no_config_manager
		return $RET_FATAL
	fi

	local rc=0
	for repo in "PowerTools"; do
		if ! dnf config-manager --set-enabled "$repo"; then
			! has_dnf_enabled_repo "$repo" || continue
			report_no_repo "$repo"
			rc=$RET_FATAL
		fi
	done
	return $rc
}

check_repos_cloudlinux8()
{
	[ "$1" = "install" ] || return 0

	if ! has_dnf_config_manager && ! install_dnf_config_manager; then
		report_dnf_no_config_manager
		return $RET_FATAL
	fi

	local rc=0
	for repo in "cloudlinux-PowerTools"; do
		if ! dnf config-manager --set-enabled "$repo"; then
			! has_dnf_enabled_repo "$repo" || continue
			report_no_repo "$repo"
			rc=$RET_FATAL
		fi
	done
	return $rc
}

check_repos_redhat8()
{
	[ "$1" = "install" ] || return 0

	local arch="`/usr/bin/arch`"

	local rc=0
	for repo in "codeready-builder-for-rhel-8-$arch-rpms"; do
		if ! subscription-manager repos --enable "$repo"; then
			! has_dnf_enabled_repo "$repo" || continue
			report_no_repo "$repo"
			rc=$RET_FATAL
		fi
	done
	return $rc
}

check_apt_repos()
{
	local rc=0
	local orig=
	local dist_tag=
	case "$os_name" in
		ubuntu) orig="Ubuntu"; dist_tag="a" ;;
		debian) orig="Debian"; dist_tag="n" ;;
	esac

	# get available releases by origin
	local policy="`apt-cache policy | grep -E "^\s+release\s+([^ ]+=[^ ]+,)*o=$orig(,[^ ]+=[^ ]+)*$" | grep "b=amd64" | awk '{print $2}'`"

	for repo in "$@"; do
		# try to find release by distribution and component
		local l="`echo "$repo" | awk -F'/' '{print $1}'`"
		local d="`echo "$repo" | awk -F'/' '{print $2}'`"
		local c="`echo "$repo" | awk -F'/' '{print $3}'`"
		! echo "$policy" | \
			grep -E  "^([^ ]+=[^ ]+,)*$dist_tag=$d(,[^ ]+=[^ ]+)*$" | \
			grep -E  "^([^ ]+=[^ ]+,)*c=$c(,[^ ]+=[^ ]+)*$" | \
			grep -qE "^([^ ]+=[^ ]+,)*l=$l(,[^ ]+=[^ ]+)*$" || continue
		report_no_repo "$repo"
		rc=$RET_FATAL
	done
	return $rc
}

check_repos_ubuntu()
{
	[ -z "$os_codename" ] || check_apt_repos "Ubuntu/$os_codename/main" "Ubuntu/$os_codename/universe" "Ubuntu/$os_codename-updates/main" "Ubuntu/$os_codename-updates/universe"
}

check_repos_ubuntu20()
{
	[ -z "$os_codename" ] || check_apt_repos "Ubuntu/$os_codename/main" "Ubuntu/$os_codename/universe"
}

check_repos_debian()
{
	[ -z "$os_codename" ] || check_apt_repos "Debian/$os_codename/main"
}

detect_platform()
{
	os_name=
	os_codename=
	os_version=

	# CentOS6 doesn't contain an unified os-release file
	# but it can be ommited because no checking is required there
	if [ -e /etc/os-release ]; then
		. /etc/os-release
		os_name="$ID"
		os_version="${VERSION_ID%%.*}"
		if [ -e /etc/debian_version ]; then
			if [ -n "$VERSION_CODENAME" ]; then
				os_codename="$VERSION_CODENAME"
			else
				case "$os_name$os_version" in
					debian9)  os_codename="stretch" ;;
					debian10) os_codename="buster"  ;;
					ubuntu16) os_codename="xenial"  ;;
					ubuntu18) os_codename="bionic"  ;;
					ubuntu20) os_codename="focal"   ;;
				esac
			fi
		fi
	fi

	# Treat Virtuozzo Linux as CentOS for now
	! [ "$os_name" = "virtuozzo" ] || os_name="centos"
}

check_repos()
{
	detect_platform

	# try to execute checker only if all attributes are detected
	[ -n "$os_name" -a -n "$os_version" ] || return 0

	local mode="$1"
	local prefix="check_repos"
	for checker in "${prefix}_${os_name}${os_version}" "${prefix}_${os_name}"; do
		case "`type "$checker" 2>/dev/null`" in
			*function*)
				"$checker" "$mode"
				return $?
			;;
		esac
	done
	return 0
}

# ---

if [ -f "$SKIP_FLAG" ]; then
	echo "Repository check was skipped due to flag file." >&2
	exit 0
fi

check_repos "$1"
