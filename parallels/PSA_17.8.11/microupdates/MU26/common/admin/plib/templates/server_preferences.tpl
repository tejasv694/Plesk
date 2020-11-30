
<script>
<!--
	function force_logrotate_oC() {
    	document.getElementById('fid-logrotate_period').disabled =
        	!document.getElementById('fid-logrotate_force').checked;
	}

	function check_num(element) {
    	if (chk_uint(element.value) && (element.value != 0)) {
        	return true;
    	}

    	alert('{INVALID_DIGIT}');
    	element.select();
    	element.focus();
    	return false;
	}

	function update_oC(f) {
        if (!check_num(f.stat_ttl)) {
            return false;
        }

        if (f.logrotate_force.checked && (!check_num(f.logrotate_period))) {
            return false;
        }

		if (
			(f.include_logs.checked != f.include_logs.defaultChecked) ||
			(f.include_databases.checked != f.include_databases.defaultChecked) ||
            (f.include_remote_databases.checked != f.include_remote_databases.defaultChecked) ||
			(f.include_mailboxes && f.include_mailboxes.checked != f.include_mailboxes.defaultChecked) ||
			(f.include_maillists && f.include_maillists.checked != f.include_maillists.defaultChecked)
		) {
			alert("{STAT_WARNING}");
		}

		lsubmit(f);
		return false;
	}
	Jsw.onReady(function () {
    	Jsw.Tooltip.init(document.getElementById('logrotate_anonymize_ips_label'), {text: '{ANONYMIZE_IPS_DESCRIPTION}'});
    	force_logrotate_oC();
	});
//-->
</script>

<fieldset>
	<legend>{SYSTEM_PREFERENCES_LEGEND}</legend><table width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td>

<table class="formFields" cellspacing="0" width="100%">

<tr {FULL_HOSTNAME_ERROR}>
	<td class="name"><label for="fid-full_hostname">{FULL_HOSTNAME_TEXT}</label>&nbsp;{REQ}</td>
	<td><input type="text" name="full_hostname" id="fid-full_hostname" value="{FULL_HOSTNAME_VALUE}" maxlenght="254" size="22"></td>
</tr>

<tr>
	<td class="name"><label for="fid-stat_ttl">{KEEP_STATS_TEXT}&nbsp;{REQ}</td>
	<td><input type="text" name="stat_ttl" id="fid-stat_ttl" value="{STAT_TTL}" size=9 maxlength=6>&nbsp;{MONTHS}</td>
</tr>

<tr>
	<td class="name">{COUNT_DISK_SPACE_TEXT}</td>
	<td>
		<div class="option"><input type="checkbox" class="checkbox" name="include_logs" id="fid-include_logs" value="true" {INCLUDE_LOGS}>&nbsp;<label for="fid-include_logs">{INCLUDE_LOGS_TEXT}</label></div>
		<div class="option"><input type="checkbox" class="checkbox" name="include_databases" id="fid-include_databases" value="true" {INCLUDE_DATABASES}>&nbsp;<label for="fid-include_databases">{INCLUDE_DATABASES_TEXT}</label></div>
        <div class="option"><input type="checkbox" class="checkbox" name="include_remote_databases" id="fid-include_remote_databases" value="true" {INCLUDE_REMOTE_DATABASES}>&nbsp;<label for="fid-include_remote_databases">{INCLUDE_REMOTE_DATABASES_TEXT}</label></div>
<!-- BEGIN DYNAMIC BLOCK: mailboxes -->
        <div class="option"><input type="checkbox" class="checkbox" name="include_mailboxes" id="fid-include_mailboxes" value="true" {INCLUDE_MAILBOXES}>&nbsp;<label for="fid-include_mailboxes">{INCLUDE_MAILBOXES_TEXT}</label></div>
<!-- END DYNAMIC BLOCK: mailboxes -->
<!-- BEGIN DYNAMIC BLOCK: maillists -->
        <div class="option"><input type="checkbox" class="checkbox" name="include_maillists" id="fid-include_maillists" value="true" {INCLUDE_MAILLISTS}>&nbsp;<label for="fid-include_maillists">{INCLUDE_MAILLISTS_TEXT}</label></div>
<!-- END DYNAMIC BLOCK: maillists -->
        <div class="option"><input type="checkbox" class="checkbox" name="include_domaindumps" id="fid-include_domaindumps" value="true" {INCLUDE_DOMAINDUMPS}>&nbsp;<label for="fid-include_domaindumps">{INCLUDE_DOMAINDUMPS_TEXT}</label></div>
        <div class="option"><input type="checkbox" class="checkbox" name="include_admindumps" id="fid-include_admindumps" value="true" {INCLUDE_ADMINDUMPS}>&nbsp;<label for="fid-include_admindumps">{INCLUDE_ADMINDUMPS_TEXT}</label></div>
	</td>
</tr>

<tr>
	<td class="name">{FILE_COUNT_TYPE_TEXT}</td>
	<td>
		<div class="option"><input type="radio" class="radiobox" name="size_count_type" id="fid-byte_count_type" value="byte" {BYTE_COUNT}>&nbsp;<label for="fid-byte_count_type">{BYTE_COUNT_TEXT}</label></div>
		<div class="option"><input type="radio" class="radiobox" name="size_count_type" id="fid-block_count_type" value="block" {BLOCK_COUNT}>&nbsp;<label for="fid-block_count_type">{BLOCK_COUNT_TEXT}</label></div>
	</td>
</tr>


<tr>
	<td class="name">{COUNT_TRAFFIC_TEXT}</td>
	<td>
		<div class="option"><input type="radio" class="radiobox" name="traffic_accounting" id="fid-traffic_accounting3" value="3" {INCLUDE_IN_OUT}>&nbsp;<label for="fid-traffic_accounting3">{INCLUDE_IN_OUT_TEXT}</label></div>
		<div class="option"><input type="radio" class="radiobox" name="traffic_accounting" id="fid-traffic_accounting1" value="1" {INCLUDE_IN}>&nbsp;<label for="fid-traffic_accounting1">{INCLUDE_IN_TEXT}</label></div>
		<div class="option"><input type="radio" class="radiobox" name="traffic_accounting" id="fid-traffic_accounting2" value="2" {INCLUDE_OUT}>&nbsp;<label for="fid-traffic_accounting2">{INCLUDE_OUT_TEXT}</label></div>
	</td>
</tr>

<!-- BEGIN DYNAMIC BLOCK: dns -->
<tr>
	<td class="name"><label for="fid-forbid_create_dns_subzone">{FORBID_CREATE_DNS_SUBZONE_TEXT}</label></td>
	<td><input type="checkbox" class="checkbox" name="forbid_create_dns_subzone" id="fid-forbid_create_dns_subzone" value="true" {FORBID_CREATE_DNS_SUBZONE_CHECKED}></td>
</tr>
<!-- END DYNAMIC BLOCK: dns -->

<!-- BEGIN DYNAMIC BLOCK: webdeploy -->
<tr>
	<td class="name"><label for="fid-webdeploy_include_password">{WEBDEPLOY_INCLUDE_PASSWORD_TEXT}</label></td>
	<td><input type="checkbox" class="checkbox" name="webdeploy_include_password" id="fid-webdeploy_include_password" value="true" {WEBDEPLOY_INCLUDE_PASSWORD_CHECKED}></td>
</tr>
<!-- END DYNAMIC BLOCK: webdeploy -->

<tr>
    <td class="name">{ALLOW_SYS_USER_RENAME_TEXT}</td>
    <td>
        <div class="option"><input type="radio" class="radiobox" name="forbid_ftp_user_rename" id="fid-forbid_ftp_user_rename_false" value="false" {ALLOW_SYS_USER_RENAME_FALSE_CHECKED}>&nbsp;<label for="fid-forbid_ftp_user_rename_false">{ALLOW_SYS_USER_RENAME_FALSE}</label></div>
        <div class="option"><input type="radio" class="radiobox" name="forbid_ftp_user_rename" id="fid-forbid_ftp_user_rename_true" value="true" {ALLOW_SYS_USER_RENAME_TRUE_CHECKED}>&nbsp;<label for="fid-forbid_ftp_user_rename_true">{ALLOW_SYS_USER_RENAME_TRUE}</label></div>
        <div class="option"><input type="radio" class="radiobox" name="forbid_ftp_user_rename" id="fid-forbid_ftp_user_rename_forced" value="forced" {ALLOW_SYS_USER_RENAME_FORCED_CHECKED}>&nbsp;<label for="fid-forbid_ftp_user_rename_forced">{ALLOW_SYS_USER_RENAME_FORCED}</label></div>
    </td>
</tr>

<tr>
    <td class="name"><label for="fid-forbid_subscription_rename">{FORBID_SUBSCRIPTION_RENAME_TEXT}</label></td>
    <td><input type="checkbox" class="checkbox" name="forbid_subscription_rename" id="fid-forbid_subscription_rename" value="true" {FORBID_SUBSCRIPTION_RENAME_CHECKED}></td>
</tr>

<tr>
    <td class="name">{PREFERRED_DOMAIN}</td>
    <td>
        <div class="option"><input type="radio" class="radiobox" name="preferred_domain" id="fid-preferred_domain_none" value="{PREFERRED_DOMAIN_NONE_VALUE}" {PREFERRED_DOMAIN_NONE_CHECKED}>&nbsp;<label for="fid-preferred_domain_none">{PREFERRED_DOMAIN_NONE_TEXT}</label></div>
        <div class="option"><input type="radio" class="radiobox" name="preferred_domain" id="fid-preferred_domain_to_landing" value="{PREFERRED_DOMAIN_TO_LANDING_VALUE}" {PREFERRED_DOMAIN_TO_LANDING_CHECKED}>&nbsp;<label for="fid-preferred_domain_to_landing">{PREFERRED_DOMAIN_TO_LANDING_TEXT}</label></div>
        <div class="option"><input type="radio" class="radiobox" name="preferred_domain" id="fid-preferred_domain_to_www" value="{PREFERRED_DOMAIN_TO_WWW_VALUE}" {PREFERRED_DOMAIN_TO_WWW_CHECKED}>&nbsp;<label for="fid-preferred_domain_to_www">{PREFERRED_DOMAIN_TO_WWW_TEXT}</label></div>
    </td>
</tr>

<tr>
	<td class="name">{ANONYMIZE_IPS}</td>
	<td><label id="logrotate_anonymize_ips_label"><input type="checkbox" class="checkbox" name="logrotate_anonymize_ips" id="logrotate_anonymize_ips" value="true" {ANONYMIZE_IPS_CHECKED}/> {ANONYMIZE_IPS_TEXT}</label></td>
</tr>

<tr>
	<td class="name">{LOGROTATE}</td>
	<td>
		<div class="option">
			<label for="fid-logrotate_force"><input type="checkbox" class="checkbox" name="logrotate_force" id="fid-logrotate_force" value="true" {LOGROTATE_FORCE_CHECKED} onclick="force_logrotate_oC()"/> {LOGROTATE_FORCE_TEXT}</label>
		</div>
		<div class="suboption">
			<label for="fid-logrotate_period">{LOGROTATE_PERIOD_TEXT}</label>
			&nbsp;<input type="text" name="logrotate_period" id="fid-logrotate_period" value="{LOGROTATE_PERIOD}" size="6" maxlength="5">
			&nbsp;<label for="fid-logrotate_period">{LOGROTATE_PERIOD_DAYS_TEXT}</label>
		</div>
	</td>
</tr>

<!-- BEGIN DYNAMIC BLOCK: auto_install_updates -->
<tr>
	<td colspan="2">{AUTO_INSTALL_UPDATES_LINK}</td>
</tr>
<!-- END DYNAMIC BLOCK: auto_install_updates -->

</table>

</td></tr></table></fieldset>
