<fieldset>
	<legend>{DNS_SOA_LEGEND}</legend><table width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td>

<table class="formFields" cellspacing="0" width="100%" id="type_select">

<!-- BEGIN DYNAMIC BLOCK: edit -->
<tr {TTL_ERROR}>
	<td class="name"><label for="fid-ttl">{TTL_TEXT}</label>&nbsp;{REQ}</td>
	<td><input type="text" name="ttl" id="fid-ttl" value="{TTL_VALUE}" size="20" maxlength="10">&nbsp;{TTL_UNIT_LIST}</td>
</tr>

<tr {REFRESH_ERROR}>
	<td class="name"><label for="fid-refresh">{REFRESH_TEXT}</label>&nbsp;{REQ}</td>
	<td><input type="text" name="refresh" id="fid-refresh" value="{REFRESH_VALUE}" size="20" maxlength="10">&nbsp;{REFRESH_UNIT_LIST}</td>
</tr>

<tr {RETRY_ERROR}>
	<td class="name"><label for="fid-retry">{RETRY_TEXT}</label>&nbsp;{REQ}</td>
	<td><input type="text" name="retry" id="fid-retry" value="{RETRY_VALUE}" size="20" maxlength="10">&nbsp;{RETRY_UNIT_LIST}</td>
</tr>

<tr {EXPIRE_ERROR}>
	<td class="name"><label for="fid-expire">{EXPIRE_TEXT}</label>&nbsp;{REQ}</td>
	<td><input type="text" name="expire" id="fid-expire" value="{EXPIRE_VALUE}" size="20" maxlength="10">&nbsp;{EXPIRE_UNIT_LIST}</td>
</tr>

<tr {MINIMUM_ERROR}>
	<td class="name"><label for="fid-minimum">{MINIMUM_TEXT}</label>&nbsp;{REQ}</td>
	<td><input type="text" name="minimum" id="fid-minimum" value="{MINIMUM_VALUE}" size="20" maxlength="10">&nbsp;{MINIMUM_UNIT_LIST}</td>
</tr>

<tr {RNAME_ERROR}>
	<td class="name"><label for="fid-rname">{RNAME_LABEL}</label>&nbsp;{REQ}</td>
	<td>
		<div class="hint">{RNAME_HINT}</div>
		<div class="text-value">
			<div class="indent-box">
				<input type="radio" class="radiobox" value="owner" id="soaRecord-rname_type_owner" name="rname_type" {RNAME_TYPE_OWNER_CHECKED}>
				<div class="indent-box-content">
					<label for="soaRecord-rname_type_owner">{RNAME_TYPE_OWNER_TEXT}</label>
				</div>
			</div>
			<div class="indent-box">
				<input type="radio" class="radiobox" value="domain" id="soaRecord-rname_type_domain" name="rname_type" {RNAME_TYPE_DOMAIN_CHECKED}>
				<div class="indent-box-content">
					<label for="soaRecord-rname_type_domain">{RNAME_TYPE_DOMAIN_TEXT}</label>
					<div class="b-subitem {RNAME_DOMAIN_HIDDEN}">
						<input type="text" name="rname_domain" id="soaRecord-rname_domain" value="{RNAME_DOMAIN}" maxlength="100" size="17"> @&lt;domain&gt;
					</div>
				</div>
			</div>
			<div class="indent-box">
				<input type="radio" class="radiobox" value="external" id="soaRecord-rname_type_external" name="rname_type" {RNAME_TYPE_EXTERNAL_CHECKED}>
				<div class="indent-box-content">
					<label for="soaRecord-rname_type_external">{RNAME_TYPE_EXTERNAL_TEXT}</label>
					<div class="b-subitem {RNAME_EXTERNAL_HIDDEN}">
						<input type="text" name="rname_external" id="soaRecord-rname_external" value="{RNAME_EXTERNAL}" maxlength="100" size="17">
					</div>
				</div>
			</div>
			<div class="indent-box">
				<input type="checkbox" class="checkbox" name="rname_enforce" id="soaRecord-rname_enforce" value="true" {RNAME_ENFORCE_EMAIL_CHECKED}>
				<div class="indent-box-content">
					<label for="soaRecord-rname_enforce">{RNAME_ENFORCE_EMAIL_TEXT}</label>
				</div>
			</div>
		</div>
	</td>
</tr>

<!-- END DYNAMIC BLOCK: edit -->

<!-- BEGIN DYNAMIC BLOCK: ro -->
<tr {TTL_ERROR}>
	<td class="name"><label for="fid-ttl">{TTL_TEXT}</label></td>
	<td>{TTL_VALUE}&nbsp;{TTL_UNIT_VALUE}</td>
</tr>

<tr {REFRESH_ERROR}>
	<td class="name"><label for="fid-refresh">{REFRESH_TEXT}</label></td>
	<td>{REFRESH_VALUE}&nbsp;{REFRESH_UNIT_VALUE}</td>
</tr>

<tr {RETRY_ERROR}>
	<td class="name"><label for="fid-retry">{RETRY_TEXT}</label></td>
	<td>{RETRY_VALUE}&nbsp;{RETRY_UNIT_VALUE}</td>
</tr>

<tr {EXPIRE_ERROR}>
	<td class="name"><label for="fid-expire">{EXPIRE_TEXT}</label></td>
	<td>{EXPIRE_VALUE}&nbsp;{EXPIRE_UNIT_VALUE}</td>
</tr>

<tr {MINIMUM_ERROR}>
	<td class="name"><label for="fid-minimum">{MINIMUM_TEXT}</label></td>
	<td>{MINIMUM_VALUE}&nbsp;{MINIMUM_UNIT_VALUE}</td>
</tr>
<!-- END DYNAMIC BLOCK: ro -->

</table>

</td></tr></table></fieldset>
<fieldset>
<legend>{DNS_SERIAL_FORMAT_LEGEND}</legend><table width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td>

<table class="formFields" cellspacing="0" width="100%">

<!-- BEGIN DYNAMIC BLOCK: edit_serial_format -->
<tr>
	<td><label>{SERIAL_FORMAT_HINT}</label></td>
</tr>
</table>
<table class="formFields" cellspacing="0" width="100%">
<tr>
	<td class="name"><label for="fid-serial_format">{SERIAL_FORMAT_TEXT}</label>&nbsp;{REQ}</td>
	<td><input type="checkbox" class="checkbox" name="serial_format" id="fid-serial_format" value="YYYYMMDDNN" {SERIAL_FORMAT_CHECKED} onClick="serial_format_oC(document.forms[0]);"></td>
</tr>
<!-- END DYNAMIC BLOCK: edit_serial_format -->

<!-- BEGIN DYNAMIC BLOCK: ro_serial_format -->
<tr>
	<td class="name"><label for="serial_format">{SERIAL_FORMAT_TEXT}</label></td>
	<td>{SERIAL_FORMAT_VALUE}</td>
</tr>
<!-- END DYNAMIC BLOCK: ro_serial_format -->

</table>


</td></tr></table></fieldset>
<?= $this->requireJs('app/dns-zone/soa-record-rname') ?>
