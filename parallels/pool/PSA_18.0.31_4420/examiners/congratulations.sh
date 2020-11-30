#!/bin/bash
### Copyright 1999-2020. Plesk International GmbH. All rights reserved.

out()
{
	echo -e "\t$*" >&2
}

print_urls()
{
	plesk login 2>/dev/null | sed -e $'s|^|\t  * |' >&2
}

print_congratulations()
{
	local mode="$1"		# 'install' or 'upgrade'
	local process=
	[ "$mode" = "install" ] && process="installation" || process="upgrade"

	out
	out "                           Congratulations!"
	out
	out "The $process has been finished. Plesk is now running on your server."
	out
	if [ "$mode" = "install" ]; then
		out "To complete the configuration process, browse either of URLs:"
		print_urls
		out
	fi
	out "Use the username 'admin' to log in. To log in as 'admin', use the 'plesk login' command."
	out "You can also log in as 'root' using your 'root' password."
	out
	out "Use the 'plesk' command to manage the server. Run 'plesk help' for more info."
	out
	out "Use the following commands to start and stop the Plesk web interface:"
	out "'service psa start' and 'service psa stop' respectively."
	out
	if [ "$mode" = "install" ]; then
		out "If you would like to migrate your subscriptions from other hosting panel"
		out "or older Plesk version to this server, please check out our assistance"
		out "options: https://www.plesk.com/professional-services/"
		out
	fi
}

unset GREP_OPTIONS

print_congratulations "$1"
# Force showing text when used as AI post-examiner
exit 1
