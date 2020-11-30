<script src="includes/js/jquery/form.js"></script>
<script src="includes/js/jquery/thickbox.js"></script>
<link rel="stylesheet" type="text/css" href="includes/styles/thickbox.css" />
<script>
	$(function() {
		$(document.settings).submit(function(event) {
			if ($(document.settings.email_address).val().trim() == '') {
				alert('%%LNG_ErrorAlertMessage_BlankContactEmail%%');
				document.settings.email_address.focus();
				event.preventDefault();
				event.stopPropagation();
				return false;
			}

			if ($(document.settings.licensekey).val().trim() == '') {
				alert('%%LNG_ErrorAlertMessage_BlankLicenseKey%%');
				document.settings.licensekey.focus();
				event.preventDefault();
				event.stopPropagation();
				return false;
			}

			if ($(document.settings.lng_applicationtitle).val().trim() == '') {
				alert('%%LNG_ErrorAlertMessage_BlankApplicationName%%');
				document.settings.lng_applicationtitle.focus();
				event.preventDefault();
				event.stopPropagation();
				return false;
			}
			{if trim($AgencyEdition.agencyid) != ''}
				if ($(document.settings.lng_accountupgrademessage).val().trim() == '') {
					alert('%%LNG_ErrorAlertMessage_BlankAccountUpgradeMessage%%');
					document.settings.lng_accountupgrademessage.focus();
					event.preventDefault();
					event.stopPropagation();
					return false;
				}
				if ($(document.settings.lng_freetrial_expiry_login).val().trim() == '') {
					alert('%%LNG_ErrorAlertMessage_BlankExpiredLogin%%');
					document.settings.lng_freetrial_expiry_login.focus();
					event.preventDefault();
					event.stopPropagation();
					return false;
				}
			{/if}

			var temp = $('input.percentage_credit_warning', document.settings);
			var selected = {};
			for (var i = 0, j = temp.size(); i < j; ++i) {
				var select_element = temp.get(i);
				var index = select_element.id.match(/_(\d+)$/)[1];
				var elementEmailContents =  $('div#credit_percentage_warnings_text_' + index + ' textarea', document.settings).get(0);
				var elementSubject = $('div#credit_percentage_warnings_text_' + index + ' input', document.settings).get(0);

				if (elementSubject.value.trim() == '') {
					ShowTab(3);
					elementSubject.focus();
					alert('{$lang.CreditSettings_Warnings_Alert_EnterEmailSubject|addslashes}');
					event.preventDefault();
					event.stopPropagation();
					return false;
				}

				if (elementEmailContents.value.trim() == '') {
					ShowTab(3);
					elementEmailContents.focus();
					alert('{$lang.CreditSettings_Warnings_Alert_EnterEmailContents|addslashes}');
					event.preventDefault();
					event.stopPropagation();
					return false;
				}
			};

			if (!$('#usesmtp').attr('checked')) {
				$('form#frmSettings .smtpSettings').val('');
			}

			$('select.percentage_credit_warning_level', document.settings).attr('disabled', false);

			return true;
		});

		$('input.CancelButton', document.settings).click(function() {
			if (confirm("%%LNG_ConfirmCancel%%")) {
				document.location.href='index.php';
			} else {
				return false;
			}
		});

		$(document.settings.cmdPreviewEmail).click(function() {
			var f = document.forms[0];
			if (f.PreviewEmail.value == "") {
				alert("%%LNG_EnterPreviewEmail%%");
				f.PreviewEmail.focus();
				return false;
			}

			tb_show('', 'index.php?Page=Settings&Action=SendPreviewDisplay&keepThis=true&TB_iframe=true&height=250&width=420&modal=true', '');
			return true;
		});

		$(document.settings.allow_attachments).click(function() { $('#ShowAttachmentSize').toggle(); });

		$(document.settings.allow_embedimages).click(function() { $('#ShowDefaultEmbeddedImages')[this.checked? 'show' : 'hide' ](); });

		$(document.settings.usesmtp).click(function() {
			$('.SMTPOptions')[$('#usesmtp').attr('checked') ? 'show' : 'hide']();
			$('.sectionSignuptoSMTP')[$('#signtosmtp').attr('checked') ? 'show' : 'hide']();
			$('#sectionSMTPComOption').html($('#signtosmtp').attr('checked') ? '%%LNG_SMTPCOM_UseSMTPOption%% %%LNG_SMTPCOM_UseSMTPOptionSeeBelow%%' : '%%LNG_SMTPCOM_UseSMTPOption%%');
		});

		$(document.settings.cmdTestSMTP).click(function() {
			var f = document.forms[0];
			if (f.smtp_server.value == '') {
				alert("%%LNG_EnterSMTPServer%%");
				f.smtp_server.focus();
				return false;
			}

			if (f.smtp_test.value == '') {
				alert("%%LNG_EnterTestEmail%%");
				f.smtp_test.focus();
				return false;
			}

			tb_show('', 'index.php?Page=Settings&Action=SendSMTPPreviewDisplay&keepThis=true&TB_iframe=tue&height=250&width=420&modal=true', '');
			return true;
		});

		$(document.settings.cron_enabled).click(function() { $('.CronInfo', document.settings)[this.checked? 'show' : 'hide'](); });

		$(document.settings.security_wrong_login_wait_enable).click(function() { $('tr.security_wrong_login_wait_options').toggle(); });
		$(document.settings.security_wrong_login_threshold_enable).click(function() { $('tr.security_wrong_login_threshold_options').toggle(); });

		$(document.settings.credit_warnings).click(function() { $('div#credit_percentage_warnings_options', document.settings)[this.checked? 'show' : 'hide'](); });
		$('input.percentage_credit_warning', document.settings).click(function() {
			var index = this.id.match(/_(\d+)$/)[1];
			$('select#credit_percentage_warnings_level_' + index, document.settings).attr('disabled', !this.checked);
			$('div#credit_percentage_warnings_text_' + index, document.settings)[this.checked? 'show' : 'hide']();
		});

		$('input.OnFocusSelect, textarea.OnFocusSelect', document.settings).focus(function() { this.select(); });
	});

	function getPreviewParameters() {
		var values = getSMTPPreviewParameters();
		$($('form#frmSettings .emailPreviewSettings').fieldSerialize().split('&')).each(function(i,n) {
			var temp = n.split('=');
			if (temp.length == 2) values[temp[0]] = temp[1];
		});
		return values;
	}

	function getSMTPPreviewParameters() {
		var values = {};
		$($('form#frmSettings .smtpSettings').fieldSerialize().split('&')).each(function(i,n) {
			var temp = n.split('=');
			if (temp.length == 2) values[temp[0]] = temp[1];
		});
		return values;
	}

	function closePopup()
	{
		tb_remove();
	}
</script>

<form ENCTYPE="multipart/form-data" name="settings" id="frmSettings" method="post" action="index.php?Page=%%PAGE%%&%%GLOBAL_FormAction%%">
<input type="hidden" value="{$ShowTab}" name="tab_num" id="id_tab_num" />
<table cellspacing="0" cellpadding="0" width="100%" align="center" style="margin-left: 4px;">
	<tr>
		<td class="Heading1">%%LNG_Settings%%</td>
	</tr>
	<tr>
		<td class="body pageinfo"><p>%%LNG_Help_Settings%%</p></td>
	</tr>
	<tr>
		<td>
			%%GLOBAL_Message%%
			<span style="display: %%GLOBAL_DisplayDbUpgrade%%">
				%%GLOBAL_DbUpgradeMessage%%
			</span>
			<span style="display: %%GLOBAL_DisplayAttachmentsMessage%%">
				%%GLOBAL_Attachments_Message%%
			</span>
			%%GLOBAL_Send_TestMode_Report%%
		</td>
	</tr>
	<tr>
		<td>
			%%GLOBAL_CronWarning%%
		</td>
	</tr>
	<tr>
		<td class="body">
			<input name="setting_submit" class="FormButton SubmitButton" type="submit" value="%%LNG_Save%%" />
			<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%" />
		</td>
	</tr>
	<tr>
		<td>
			<div>
				<br/>
				<ul id="tabnav">
					<li><a href="index.php?Page=Settings&Tab=1" {if $ShowTab == 1}class="active"{/if} id="tab1" onclick="ShowTab(1); $('#id_tab_num').val(1); return false;"><span>{$lang.ApplicationSettings_Heading}</span></a></li>
					<li><a href="index.php?Page=Settings&Tab=2" {if $ShowTab == 2}class="active"{/if} id="tab2" onclick="ShowTab(2); $('#id_tab_num').val(2); return false;"><span>{$lang.EmailSettings_Heading}</span></a></li>
					<li><a href="index.php?Page=Settings&Tab=7" {if $ShowTab == 7}class="active"{/if} id="tab7" onclick="ShowTab(7); $('#id_tab_num').val(7); return false;"><span>{$lang.BounceSettings_Heading}</span></a></li>
					<li><a href="index.php?Page=Settings&Tab=3" {if $ShowTab == 3}class="active"{/if} id="tab3" onclick="ShowTab(3); $('#id_tab_num').val(3); return false;"><span>{$lang.CreditSettings_Heading}</span></a></li>
					<li><a href="index.php?Page=Settings&Tab=4" {if $ShowTab == 4}class="active"{/if} id="tab4" onclick="ShowTab(4); $('#id_tab_num').val(4); return false;"><span>{$lang.CronSettings_Heading}</span></a></li>
					{if $DisplayPrivateLabel}<li><a href="index.php?Page=Settings&Tab=8" {if $ShowTab == 8}class="active"{/if} id="tab8" onclick="ShowTab(8); $('#id_tab_num').val(8); return false;"><span>{$lang.PrivateLabelSettings_Heading}</span></a></li>{/if}
					<li><a href="index.php?Page=Settings&Tab=5" {if $ShowTab == 5}class="active"{/if} id="tab5" onclick="ShowTab(5); $('#id_tab_num').val(5); return false;"><span>{$lang.SecuritySettings_Heading}</span></a></li>
					<li><a href="index.php?Page=Settings&Tab=6" {if $ShowTab == 6}class="active"{/if} id="tab6" onclick="ShowTab(6); $('#id_tab_num').val(6); return false;"><span>{$lang.AddonsSettings_Heading}</span></a></li>
				</ul>
				<div id="div1" style="padding-top: 10px; display: {if $ShowTab == 1}block{else}none{/if};">
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;%%LNG_Miscellaneous%%
							</td>
						</tr>
						<tr>
							<td width="200" class="FieldLabel">
								{template="Required"}
								%%LNG_ApplicationURL%%:
							</td>
							<td>
								<input type="hidden" name="application_url" value="%%GLOBAL_ApplicationURL%%" />
								<input type="text" value="%%GLOBAL_ApplicationURL%%" class="Field250" readonly="readonly" disabled="disabled"> %%LNG_HLP_ApplicationURL%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Required"}
								%%LNG_ApplicationEmail%%:
							</td>
							<td>
								<input type="text" name="email_address" value="%%GLOBAL_EmailAddress%%" class="Field250"> %%LNG_HLP_ApplicationEmail%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_IpTracking%%:
							</td>
							<td>
								<label for="iptracking"><input type="checkbox" name="iptracking" id="iptracking" value="1"%%GLOBAL_IpTracking%%>%%LNG_IpTrackingExplain%%</label> %%LNG_HLP_IpTracking%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								{$lang.MultipleUnsubscribe}:
							</td>
							<td>
								<label for="usemultipleunsubscribe"><input type="checkbox" name="usemultipleunsubscribe" id="usemultipleunsubscribe" value="1" %%GLOBAL_UseMultipleUnsubscribe%% /> {$lang.MultipleUnsubscribe_Explain}</label> {$lnghlp.MultipleUnsubscribe}
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								{$lang.ContactCanModifyEmail}:
							</td>
							<td>
								<label for="contactcanmodifyemail"><input type="checkbox" name="contactcanmodifyemail" id="contactcanmodifyemail" value="1" %%GLOBAL_ContactCanModifyEmail%% /> {$lang.ContactCanModifyEmail_Explain}</label> {$lnghlp.ContactCanModifyEmail}
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_SystemMessage%%:
							</td>
							<td>
								<textarea name="system_message" rows="3" cols="28" wrap="virtual" style="width: 250px;">%%GLOBAL_System_Message%%</textarea>&nbsp;&nbsp;&nbsp;%%LNG_HLP_SystemMessage%%
							</td>
						</tr>
						<tr>
							<td colspan="2" class="EmptyRow">
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;%%LNG_DatabaseIntro%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_DatabaseType%%:
							</td>
							<td class=body>
								[%%GLOBAL_DatabaseType%%]
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Required"}
								%%LNG_DatabaseUser%%:
							</td>
							<td>
								<input type="hidden" name="database_u" value="%%GLOBAL_DatabaseUser%%" />
								<input type="text" value="%%GLOBAL_DatabaseUser%%" class="Field250 OnFocusSelect" readonly="readonly" disabled="disabled"> %%LNG_HLP_DatabaseUser%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_DatabasePassword%%:
							</td>
							<td>
								<input type="hidden" name="database_p" value="%%GLOBAL_DatabasePass%%" />
								<input type="password" value="%%GLOBAL_DatabasePass%%" class="Field250 OnFocusSelect" readonly="readonly" disabled="disabled" autocomplete="off" /> %%LNG_HLP_DatabasePassword%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Required"}
								%%LNG_DatabaseHost%%:
							</td>
							<td>
								<input type="hidden" name="database_host" value="%%GLOBAL_DatabaseHost%%" />
								<input type="text" name="database_host" value="%%GLOBAL_DatabaseHost%%" class="Field250 OnFocusSelect" readonly="readonly" disabled="disabled"> %%LNG_HLP_DatabaseHost%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Required"}
								%%LNG_DatabaseName%%:
							</td>
							<td>
								<input type="hidden" name="database_name" value="%%GLOBAL_DatabaseName%%" />
								<input type="text" value="%%GLOBAL_DatabaseName%%" class="Field250 OnFocusSelect" readonly="readonly" disabled="disabled"> %%LNG_HLP_DatabaseName%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_DatabaseTablePrefix%%:
							</td>
							<td>
								<input type="hidden" name="tableprefix" value="%%GLOBAL_DatabaseTablePrefix%%" />
								<input type="text" value="%%GLOBAL_DatabaseTablePrefix%%" class="Field250 OnFocusSelect" readonly="readonly" disabled="disabled"> %%LNG_HLP_DatabaseTablePrefix%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_DatabaseVersion%%:
							</td>
							<td>
								%%GLOBAL_DatabaseVersion%%
							</td>
						</tr>
						<tr>
							<td colspan="2" class="EmptyRow">
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;%%LNG_LicenseKeyIntro%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Required"}
								%%LNG_LicenseKey%%:
							</td>
							<td>
								<input type="text" name="licensekey" id="licensekey" value="%%GLOBAL_LicenseKey%%" class="Field250"> %%LNG_HLP_LicenseKey%%
							</td>
						</tr>
						<tr>
							<td colspan="2" class="EmptyRow">
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;%%LNG_SendTestIntro%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_TestEmailAddress%%:
							</td>
							<td>
								<input type="text" name="PreviewEmail" value="" class="Field250 emailPreviewSettings" />
								<input type="button" name="cmdPreviewEmail" value="%%LNG_Send%%" class="Field" />
								%%LNG_HLP_TestEmailAddress%%
							</td>
						</tr>
					</table>
				</div>

				<div id="div2" style="padding-top: 10px; display: {if $ShowTab == 2}block{else}none{/if};">
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;%%LNG_EmailSettings%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel" width="10%">
								<img src="images/blank.gif" width="200" height="1" /><br />
								{template="Not_Required"}
								%%LNG_EmailSize_Warning%%:
							</td>
							<td width="90%">
								<input type="text" name="emailsize_warning" value="%%GLOBAL_EmailSize_Warning%%" class="Field250" style="width: 50px;">%%LNG_EmailSize_Warning_KB%% %%LNG_HLP_EmailSize_Warning%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_EmailSize_Maximum%%:
							</td>
							<td>
								<input type="text" name="emailsize_maximum" value="%%GLOBAL_EmailSize_Maximum%%" class="Field250" style="width: 50px;">%%LNG_EmailSize_Maximum_KB%% %%LNG_HLP_EmailSize_Maximum%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_MaxHourlyRate%%:
							</td>
							<td>
								<input type="text" name="maxhourlyrate" value="%%GLOBAL_MaxHourlyRate%%" class="Field250" style="width: 50px;"> %%LNG_HLP_MaxHourlyRate%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_MaxOverSize%%:
							</td>
							<td>
								<input type="text" name="maxoversize" value="%%GLOBAL_MaxOverSize%%" class="Field250" style="width: 50px;"> %%LNG_HLP_MaxOverSize%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_Resend_Maximum%%:
							</td>
							<td>
								<input type="text" name="resend_maximum" value="%%GLOBAL_Resend_Maximum%%" class="Field250" style="width: 50px;"> %%LNG_HLP_Resend_Maximum%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_MaxImageWidth%%:
							</td>
							<td>
								<input type="text" name="max_imagewidth" value="%%GLOBAL_MaxImageWidth%%" class="Field250" style="width: 50px;"> %%LNG_HLP_MaxImageWidth%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_MaxImageHeight%%:
							</td>
							<td>
								<input type="text" name="max_imageheight" value="%%GLOBAL_MaxImageHeight%%" class="Field250" style="width: 50px;"> %%LNG_HLP_MaxImageHeight%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_GlobalHTMLFooter%%:
							</td>
							<td>
								<textarea name="htmlfooter" rows="3" cols="28" wrap="virtual" style="width: 250px;">%%GLOBAL_HTMLFooter%%</textarea>&nbsp;&nbsp;&nbsp;%%LNG_HLP_GlobalHTMLFooter%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_GlobalTextFooter%%:
							</td>
							<td>
								<textarea name="textfooter" rows="3" cols="28" wrap="virtual" style="width: 250px;">%%GLOBAL_TextFooter%%</textarea>&nbsp;&nbsp;&nbsp;%%LNG_HLP_GlobalTextFooter%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_ForceUnsubLink%%:
							</td>
							<td>
								<label for="force_unsublink"><input type="checkbox" name="force_unsublink" id="force_unsublink" value="1"%%GLOBAL_ForceUnsubLink%%>%%LNG_ForceUnsubLinkExplain%%</label> %%LNG_HLP_ForceUnsubLink%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_AllowAttachments%%:
							</td>
							<td>
								<div>
									<label for="allow_attachments">
										<input type="checkbox" name="allow_attachments" id="allow_attachments" value="1"%%GLOBAL_AllowAttachments%%>%%LNG_AllowAttachmentsExplain%%
									</label>
									%%LNG_HLP_AllowAttachments%%
								</div>
								<div id="ShowAttachmentSize" style="display: %%GLOBAL_ShowAttachmentSize%%">
									<div>
										<img width="20" height="20" src="images/nodejoin.gif"/>
										%%LNG_MaxAttachmentSize%%
										<input type="text" name="attachment_size" value="%%GLOBAL_AttachmentSize%%" class="Field250" style="width: 50px;">%%LNG_MaxAttachmentSizeKB%%
										%%LNG_HLP_MaxAttachmentSize%%
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_AllowEmbeddedImages%%:
							</td>
							<td>
								<div>
									<label for="allow_embedimages">
										<input type="checkbox" name="allow_embedimages" id="allow_embedimages" value="1"%%GLOBAL_AllowEmbedImages%%>%%LNG_AllowEmbeddedImagesExplain%%
									</label>
									%%LNG_HLP_AllowEmbeddedImages%%
								</div>
								<div id="ShowDefaultEmbeddedImages" style="display: %%GLOBAL_ShowDefaultEmbeddedImages%%">
									<div>
										<img width="20" height="20" src="images/nodejoin.gif"/>
										<label for="default_embedimages">
											<input type="checkbox" name="default_embedimages" id="default_embedimages" value="1"%%GLOBAL_DefaultEmbedImages%%>%%LNG_DefaultEmbeddedImagesExplain%%
										</label>
										%%LNG_HLP_DefaultEmbeddedImages%%
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_Send_TestMode%%:
							</td>
							<td>
								<label for="send_test_mode">
									<input type="checkbox" name="send_test_mode" id="send_test_mode" value="1"%%GLOBAL_SendTestMode%%>%%LNG_Send_TestModeExplain%%
								</label>
								%%LNG_HLP_Send_TestMode%%
							</td>
						</tr>
						<tr>
							<td colspan="2" class="EmptyRow">
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;%%LNG_SmtpServerIntro%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_UseSMTP%%:
							</td>
							<td>
								<label for="usephpmail">
									<input type="radio" name="usesmtp" id="usephpmail" value="0"%%GLOBAL_UseDefaultMail%%/>
									%%LNG_SmtpDefaultSettings%%
								</label>
								%%LNG_HLP_UseDefaultMail%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">&nbsp;</td>
							<td>
								<label for="usesmtp">
									<input type="radio" name="usesmtp" id="usesmtp" value="1"%%GLOBAL_UseSMTP%%/>
									%%LNG_SmtpCustom%%
								</label>
								%%LNG_HLP_UseSMTP%%
							</td>
						</tr>
						<tr class="SMTPOptions" style="display: %%GLOBAL_DisplaySMTP%%">
							<td class="FieldLabel">
								{template="Required"}
								%%LNG_SmtpServerName%%:
							</td>
							<td>
								<img width="20" height="20" src="images/nodejoin.gif"/>
								<input type="text" name="smtp_server" id="smtp_server" value="%%GLOBAL_Smtp_Server%%" class="Field250 smtpSettings"> %%LNG_HLP_SmtpServerName%%
							</td>
						</tr>
						<tr class="SMTPOptions" style="display: %%GLOBAL_DisplaySMTP%%">
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_SmtpServerUsername%%:
							</td>
							<td>
								<img width="20" height="20" src="images/blank.gif"/>
								<input type="text" name="smtp_u" id="smtp_u" value="%%GLOBAL_Smtp_Username%%" class="Field250 smtpSettings"> %%LNG_HLP_SmtpServerUsername%%
							</td>
						</tr>
						<tr class="SMTPOptions" style="display: %%GLOBAL_DisplaySMTP%%">
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_SmtpServerPassword%%:
							</td>
							<td>
								<img width="20" height="20" src="images/blank.gif"/>
								<input type="password" name="smtp_p" id="smtp_p" value="%%GLOBAL_Smtp_Password%%" class="Field250 smtpSettings" autocomplete="off" /> %%LNG_HLP_SmtpServerPassword%%
							</td>
						</tr>
						<tr class="SMTPOptions" style="display: %%GLOBAL_DisplaySMTP%%">
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_SmtpServerPort%%:
							</td>
							<td>
								<img width="20" height="20" src="images/blank.gif"/>
								<input type="text" name="smtp_port" id="smtp_port" value="%%GLOBAL_Smtp_Port%%" class="Field250 smtpSettings"> %%LNG_HLP_SmtpServerPort%%
							</td>
						</tr>
						<tr class="SMTPOptions" style="display: %%GLOBAL_DisplaySMTP%%">
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_TestSendTo%%:
							</td>
							<td>
								<img width="20" height="20" src="images/blank.gif"/>
								<input type="text" name="smtp_test" id="smtp_test" value="" class="Field250 smtpSettings"> %%LNG_HLP_TestSMTPSettings%%
							</td>
						</tr>
						<tr class="SMTPOptions" style="display: %%GLOBAL_DisplaySMTP%%">
							<td class="FieldLabel">
								&nbsp;
							</td>
							<td>
								<img width="20" height="20" src="images/blank.gif"/>
								<input type="button" name="cmdTestSMTP" value="%%LNG_TestSMTPSettings%%" class="FormButton" style="width: 120px;" />
							</td>
						</tr>
						<tr style="display:%%GLOBAL_ShowSmtpComOptionShow%%;">
							<td class="FieldLabel">&nbsp;</td>
							<td>
								<label for="signtosmtp">
									<input type="radio" name="usesmtp" id="signtosmtp" value="2" />
									<span id="sectionSMTPComOption">%%LNG_SMTPCOM_UseSMTPOption%%</span>
								</label>
								%%LNG_HLP_UseSMTPCOM%%
							</td>
						</tr>
						<tr class="sectionSignuptoSMTP" style="display: none;">
							<td colspan="2" class="EmptyRow">
								&nbsp;
							</td>
						</tr>
						<tr class="sectionSignuptoSMTP" style="display: none;">
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;%%LNG_SMTPCOM_Header%%
							</td>
						</tr>
						<tr class="sectionSignuptoSMTP" style="display: none;">
							<td colspan="2" style="padding-left: 20px;">%%LNG_SMTPCOM_Explain%%</td>
						</tr>
					</table>
				</div>

				<div id="div7" style="padding-top: 10px; display: {if $ShowTab == 7}block{else}none{/if};">
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
						{template="bounce_details"}
					</table>
				</div>

				<div id="div3" style="padding-top: 10px; display: {if $ShowTab == 3}block{else}none{/if};">
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
						<tr><td colspan="2" class="Heading2">&nbsp;&nbsp;{$lang.CreditSettings_UserCredit_Section}</td></tr>
						<tr>
							<td width="200" class="FieldLabel">
								{template="Not_Required"}
								{$lang.CreditSettings_UserCredit_AutorespondersTakeCredit}
							</td>
							<td>
								<label for="credit_include_autoresponders">
									<input type="checkbox" name="credit_include_autoresponders" id="credit_include_autoresponders" value="1" {if $credit_settings.autoresponders_take_credit}checked="checked"{/if} />
									{$lang.CreditSettings_UserCredit_AutorespondersTakeCredit_Description}
								</label>
								{$lnghlp.CreditSettings_UserCredit_AutorespondersTakeCredit}
							</td>
						</tr>
						<tr>
							<td width="200" class="FieldLabel">
								{template="Not_Required"}
								{$lang.CreditSettings_UserCredit_TriggersTakeCredit}
							</td>
							<td>
								<label for="credit_include_triggers">
									<input type="checkbox" name="credit_include_triggers" id="credit_include_triggers" value="1" {if $credit_settings.triggers_take_credit}checked="checked"{/if} />
									{$lang.CreditSettings_UserCredit_TriggersTakeCredit_Description}
								</label>
								{$lnghlp.CreditSettings_UserCredit_TriggersTakeCredit}
							</td>
						</tr>
						<tr><td colspan="2" class="EmptyRow">&nbsp;</td></tr>
						<tr><td colspan="2" class="Heading2">&nbsp;&nbsp;{$lang.CreditSettings_Warnings_Section}</td></tr>
						<tr>
							<td width="200" class="FieldLabel">
								{template="Not_Required"}
								{$lang.CreditSettings_Warnings_Enabled}
							</td>
							<td>
								<label for="credit_warnings">
									<input type="checkbox" name="credit_warnings" id="credit_warnings" value="1" {if $credit_settings.enable_credit_level_warnings}checked="checked"{/if} />
									{$lang.CreditSettings_Warnings_Enabled_Description}
								</label>
								{$lnghlp.CreditSettings_Warnings_Enabled}
								<div id="credit_percentage_warnings_options" {if !$credit_settings.enable_credit_level_warnings}style="display:none;"{/if}>
									{foreach from=$credit_settings.warnings_percentage_level item=warning_level key=index}
										<div>
											{if $index == 0}
												<img height="20" width="20" src="images/nodejoin.gif" />
											{else}
												<img height="20" width="20" src="images/blank.gif" />
											{/if}
											<label for="credit_percentage_warnings_enable_{$index}">
												<input type="checkbox" name="credit_percentage_warnings_enable[]" id="credit_percentage_warnings_enable_{$index}" class="percentage_credit_warning" value="{$index}" {if $warning_level.enabled}checked="checked"{/if} />
												{$lang.CreditSettings_Warnings_LevelPrompt_PRE}
											</label>
											<select name="credit_percentage_warnings_level[]" id="credit_percentage_warnings_level_{$index}" class="percentage_credit_warning_level" style="width:auto;" {if !$warning_level.enabled}disabled="disabled"{/if}>
												{foreach from=$credit_settings.warnings_percentage_level_choices item=each}
													<option value="{$each}" {if $each == $warning_level.creditlevel}selected="selected"{/if}>{$each}%</option>
												{/foreach}
											</select>
											<label for="credit_percentage_warnings_enable_{$index}">
												{$lang.CreditSettings_Warnings_LevelPrompt_POST}
											</label>
											<div id="credit_percentage_warnings_text_{$index}" {if !$warning_level.enabled}style="display:none;"{/if}>
												<span style="float:left;">
													<img height="20" width="20" src="images/blank.gif" />
													<img height="20" width="20" src="images/nodejoin.gif" />
												</span>
												<input type="text" name="credit_percentage_warnings_subject[]" maxlength="250" value="{$warning_level.emailsubject}" class="Field250" style="width:578px;" /><br />
												<textarea name="credit_percentage_warnings_text[]" rows="10" cols="70" class="OnFocusSelect">{$warning_level.emailcontents}</textarea>
											</div>
										</div>
									{/foreach}
								</div>
							</td>
						</tr>
					</table>
				</div>

				<div id="div4" style="padding-top: 10px; display: {if $ShowTab == 4}block{else}none{/if};">
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;%%LNG_CronSettings%%
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								%%LNG_CronEnabled%%
							</td>
							<td>
								<label for="cron_enabled"><input type="checkbox" name="cron_enabled" id="cron_enabled" value="1"%%GLOBAL_CronEnabled%%>%%LNG_CronEnabledExplain%%</label> %%LNG_HLP_CronEnabled%%
							</td>
						</tr>
						<tr id="show_cron_path" class="CronInfo" style="display: %%GLOBAL_Cron_ShowInfo%%">
							<td width="200" class="FieldLabel">
								{template="Not_Required"}
								%%LNG_CronPath%%:
							</td>
							<td>
								<textarea name="cronpath" class="Field250 OnFocusSelect" style="width:400px" rows="4" onclick="this.select()" readonly>%%GLOBAL_CronPath%%</textarea> %%LNG_HLP_CronPath%%
							</td>
						</tr>
						<tr id="show_cron_runtime" class="CronInfo" style="display: %%GLOBAL_Cron_ShowInfo%%">
							<td width="200" class="FieldLabel">
								{template="Not_Required"}
								%%LNG_CronRunTime%%:
							</td>
							<td align="left">
								%%GLOBAL_CronRunTime%%
							</td>
						</tr>
					</table>
					<table width="100%" cellspacing="0" cellpadding="0" border="0" class="Text CronInfo" style="margin-top:-20px; margin-bottom:10px; display: %%GLOBAL_Cron_ShowInfo%%">
						<tr>
							<td colspan="4">
								<table width="100%" cellspacing="0" cellpadding="0" align="center" class="message_box">
									<tbody><tr>
										<td class="Message">
											<img width="20" height="16" align="left" src="images/infoballon.gif"/>
											%%GLOBAL_CronRunTime_Explain%%
										</td>
									</tr>
								</tbody></table>
							</td>
						</tr>
						<tr class="Heading3">
							<td style="width:200px; padding-left:10px">Job Type</td>
							<td>Last Run</td>
							<td>Next Run</td>
							<td>Run Every</td>
						</tr>
						%%GLOBAL_Settings_CronOptionsList%%
					</table>
				</div>
				<div id="div5" style="padding-top: 10px; display: {if $ShowTab == 5}block{else}none{/if};">
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
						<tr><td colspan="2" class="Heading2">{template="Not_Required"}{$lang.SecuritySettings_LoginSecurity_EnableLoginWait_Title}</td></tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								{$lang.SecuritySettings_LoginSecurity_EnableLoginWait}
							</td>
							<td>
								<label for="security_wrong_login_wait_enable">
									<input type="checkbox" name="security_wrong_login_wait_enable" id="security_wrong_login_wait_enable" value="1" {if $security_settings.login_wait != 0}checked="checked"{/if} />
									{$lang.SecuritySettings_LoginSecurity_YesEnableLoginWait}
								</label>
								{$lnghlp.SecuritySettings_LoginSecurity_EnableLoginWait}
							</td>
						</tr>
						<tr class="security_wrong_login_wait_options" {if $security_settings.login_wait == 0}style="display:none;"{/if}>
							<td class="FieldLabel">
								{template="Required"}
								{$lang.SecuritySettings_LoginSecurity_EnableLoginWait_DelayFor}
							</td>
							<td>
								<img width="20" height="20" src="images/nodejoin.gif"/>
								<label for="security_wrong_login_wait">
									<select name="security_wrong_login_wait" id="security_wrong_login_wait" style="width: 50px;">
										{foreach from=$security_settings_options.login_wait item=item}
											<option value="{$item}" {if $security_settings.login_wait == $item}selected="selected"{/if}>{$item}</option>
										{/foreach}
									</select>
									{$lang.Second(s)}
								</label>
							</td>
						</tr>
						<tr><td colspan="2" class="EmptyRow">&nbsp;</td></tr>
						<tr><td colspan="2" class="Heading2">{template="Not_Required"}{$lang.SecuritySettings_LoginSecurity_EnableLoginFailureThreshold_Title}</td></tr>
						<tr>
							<td class="FieldLabel">
								{template="Not_Required"}
								{$lang.SecuritySettings_LoginSecurity_EnableLoginFailureThreshold}
							</td>
							<td>
								<label for="security_wrong_login_threshold_enable">
									<input type="checkbox" name="security_wrong_login_threshold_enable" id="security_wrong_login_threshold_enable" value="1" {if $security_settings.threshold_login_count != 0}checked="checked"{/if} />
									{$lang.SecuritySettings_LoginSecurity_YesEnableLoginFailureThreshold}
								</label>
								{$lnghlp.SecuritySettings_LoginSecurity_EnableLoginFailureThreshold}
							</td>
						</tr>
						<tr class="security_wrong_login_threshold_options" {if $security_settings.threshold_login_count == 0}style="display:none;"{/if}>
							<td class="FieldLabel">
								{template="Required"}
								{$lang.SecuritySettings_LoginSecurity_EnableLoginFailureThreshold_Threshold}
							</td>
							<td>
								<img width="20" height="20" src="images/nodejoin.gif"/>
								<label for="security_wrong_login_threshold_count">
									<select name="security_wrong_login_threshold_count" id="security_wrong_login_threshold_count" style="width: 50px;">
										{foreach from=$security_settings_options.threshold_login_count item=item}
											<option value="{$item}" {if $security_settings.threshold_login_count == $item}selected="selected"{/if}>{$item}</option>
										{/foreach}
									</select>
								</label>
								{$lang.SecuritySettings_LoginSecurity_EnableLoginFailureThreshold_FailedAttemptsIn}
								<label for="security_wrong_login_threshold_duration">
									<select name="security_wrong_login_threshold_duration" id="security_wrong_login_threshold_duration" style="width: 50px;">
										{foreach from=$security_settings_options.threshold_login_duration item=item}
											<option value="{$item}" {if $security_settings.threshold_login_duration == $item}selected="selected"{/if}>{$item}</option>
										{/foreach}
									</select>
								</label>
								{$lang.Minute(s)}
							</td>
						</tr>
						<tr class="security_wrong_login_threshold_options" {if $security_settings.threshold_login_count == 0}style="display:none;"{/if}>
							<td class="FieldLabel">
								{template="Required"}
								{$lang.SecuritySettings_LoginSecurity_EnableLoginFailureThreshold_BanIPFor}
							</td>
							<td>
								<img src="images/blank.gif" height="20" width="20" />
								<label for="security_ban_duration">
									<select name="security_ban_duration" id="security_ban_duration" style="width: 50px;">
										{foreach from=$security_settings_options.ip_login_ban_duration item=item}
											<option value="{$item}" {if $security_settings.ip_login_ban_duration == $item}selected="selected"{/if}>{$item}</option>
										{/foreach}
									</select>
								</label>
								{$lang.Minute(s)}
							</td>
						</tr>
					</table>
				</div>
				{if !$DisplayPrivateLabel}<div style="display:none;">{/if}
				<div id="div8" style="padding-top: 10px; display: {if $ShowTab == 8}block{else}none{/if};">
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;{$lang.PrivateLabelSettings_Heading}
							</td>
						</tr>
						<tr>
							<td class="FieldLabel" >
								{template="Required"}
								{$lang.PrivateLabelSettings_ApplicationName}:
							</td>
							<td>
								<input type="text" name="lng_applicationtitle" value="{$lang.ApplicationTitle|addslashes}" class="Field300" />
								<div class="HelpToolTipPos">
									{$lnghlp.PrivateLabelSettings_ApplicationName}
								</div>
							</td>
						</tr>
						<tr style="height:100px;">
							<td class="FieldLabel" >
								{template="Not_Required"}
								{$lang.PrivateLabelSettings_ApplicationFooter}:
							</td>
							<td >
								<textarea name="lng_copyright" rows="3" cols="28" wrap="virtual" class="Field300">%%GLOBAL_Copyright%%</textarea>
								<div class="HelpToolTipPos">
									{$lnghlp.PrivateLabelSettings_ApplicationFooter}
								</div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel" >
								{template="Not_Required"}
								{$lang.PrivateLabelSettings_DefaultHtmlEmailFooter}:
							</td>
							<td >
								<textarea name="lng_default_global_html_footer" rows="12" cols="28" wrap="virtual" class="Field300">{$lang.Default_Global_HTML_Footer}</textarea>
								<div class="HelpToolTipPos">
									{$lnghlp.PrivateLabelSettings_DefaultHtmlEmailFooter}
								</div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel" >
								{template="Not_Required"}
								{$lang.PrivateLabelSettings_DefaultTextEmailFooter}:
							</td>
							<td >
								<textarea name="lng_default_global_text_footer" rows="3" cols="28" wrap="virtual" class="Field300">{$lang.Default_Global_Text_Footer}</textarea>
								<div class="HelpToolTipPos">
									{$lnghlp.PrivateLabelSettings_DefaultTextEmailFooter}
								</div>
							</td>
						</tr>
						{if trim($AgencyEdition.agencyid) != ''}
							<tr>
								<td class="FieldLabel" >
									{template="Required"}
									{$lang.PrivateLabelSettings_ExpiredTrial_LoginMessage}:
								</td>
								<td >
									<textarea name="lng_freetrial_expiry_login" rows="3" cols="28" wrap="virtual" class="Field300">{$lang.FreeTrial_Expiry_Login}</textarea>
									<div class="HelpToolTipPos">
										{$lnghlp.PrivateLabelSettings_ExpiredTrial_LoginMessage}
									</div>
									<p />
								</td>
							</tr>

							<tr>
								<td class="FieldLabel" >
									{template="Required"}
									{$lang.PrivateLabelSettings_UpgradeMessage}:
								</td>
								<td >
									<textarea name="lng_accountupgrademessage" rows="3" cols="28" wrap="virtual" class="Field300">{$lang.AccountUpgradeMessage}</textarea>
									<div class="HelpToolTipPos">
										{$lnghlp.PrivateLabelSettings_UpgradeMessage}
									</div>
									<p />
								</td>
							</tr>
						{/if}
						<tr>
							<td class="FieldLabel" >
								{template="Not_Required"}
								{$lang.PrivateLabelSettings_ApplicationLogoImage}:
							</td>
							<td>
								<table border="0" cellspacing="0" cellpadding="0" width="100%">
									<tr valign="top">
										<td>
											<img src="%%GLOBAL_Existing_App_Logo_Image%%" alt="logo" border="0" />
											<input type="hidden" name="existing_app_logo_image" value="%%GLOBAL_Existing_App_Logo_Image%%" />
										</td>
									</tr>
									<tr>
										<td>
											<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
											<input type="file" name="Application_Logo_Image" class="Field300" />
											{$lnghlp.PrivateLabelSettings_ApplicationLogoImage}
										</td>
									</tr>
								</table>

							</td>
						</tr>
						<tr>
							<td class="FieldLabel" >
								{template="Not_Required"}
								{$lang.PrivateLabelSettings_ApplicationFavicon}:
							</td>
							<td>
								<table border="0" cellspacing="0" cellpadding="0" width="100%">
									<tr valign="top">
										<td>
											<img src="%%GLOBAL_Existing_App_Favicon%%" alt="logo" border="0" />
											<input type="hidden" name="existing_app_favicon" value="%%GLOBAL_Existing_App_Favicon%%" />
										</td>
									</tr>
									<tr>
										<td>
											<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
											<input type="file" name="Application_Favicon" class="Field300" />
											{$lnghlp.PrivateLabelSettings_ApplicationFavicon}
										</td>
									</tr>
								</table>

							</td>
						</tr>
						<tr>
							<td class="FieldLabel" >
								{template="Not_Required"}
								{$lang.PrivateLabelSettings_UpdatesCheck}
							</td>
							<td >
								<input id="Id_Update_Check_Enabled" type="checkbox" name="update_check_enabled" value="1" %%GLOBAL_EnableUpdatesCheck%% />
								<label for="Id_Update_Check_Enabled" >
									{$lang.PrivateLabelSettings_YesUpdatesCheck}
								</label>
								{$lnghlp.PrivateLabelSettings_UpdatesCheck}
							</td>
						</tr>
						<tr>
							<td class="FieldLabel" >
								{template="Not_Required"}
								{$lang.PrivateLabelSettings_SmtpSending}
							</td>
							<td >
								<input id="Id_Show_Smtp_Com_Option" type="checkbox" name="show_smtp_com_option" value="1" %%GLOBAL_ShowSmtpComOption%% />
								<label for="Id_Show_Smtp_Com_Option" >
									{$lang.PrivateLabelSettings_YesSmtpSending}
								</label>
								{$lnghlp.PrivateLabelSettings_SmtpSending}
							</td>
						</tr>
						<tr>
							<td class="FieldLabel" >
								{template="Not_Required"}
								{$lang.PrivateLabelSettings_GettingStartedVideo}
							</td>
							<td>
								<input id="Id_Show_Intro_Video" type="checkbox" name="show_intro_video" value="1" checked="checked" %%GLOBAL_ShowIntroVideo%% />
								<label for="Id_Show_Intro_Video" >
									{$lang.PrivateLabelSettings_YesGettingStartedVideo}
								</label>
								{$lnghlp.PrivateLabelSettings_GettingStartedVideo}
							</td>
						</tr>
					</table>
				</div>
				{if !$DisplayPrivateLabel}</div>{/if}
				<div id="div6" style="padding-top: 10px; display: {if $ShowTab == 6}block{else}none{/if};">
					%%GLOBAL_Settings_AddonsDisplay%%
				</div>
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<input type="hidden" name="database_type" value="%%GLOBAL_DatabaseType%%" />
			<input name="setting_submit" class="FormButton SubmitButton" type="submit" value="%%LNG_Save%%" />
			<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%" />
		</td>
	</tr>
</table>
</form>

	<script>
	function ShowReport(reporttype)
	{
		var link = 'index.php?Page=Settings&Action=ViewDisabled&Report=' + reporttype;

		var top = screen.height / 2 - (230);
		var left = screen.width / 2 - (250);

		window.open(link,"reportWin","left=" + left + ",top="+top+",toolbar=false,status=no,directories=false,menubar=false,scrollbars=false,resizable=false,copyhistory=false,width=500,height=460");
	}

	function LoadAddonSettings(addon_name, addon_title)
	{
		tb_show(addon_title, 'index.php?Page=Settings&Action=Addons&SubAction=configure&Addon=' + escape(addon_name) + '&keepThis=true&TB_iframe=true&height=320&width=450&', '');
	}
	</script>
%%GLOBAL_ExtraScript%%
