<script src="includes/js/jquery/form.js"></script>
<script src="includes/js/jquery/thickbox.js"></script>
<link rel="stylesheet" type="text/css" href="includes/styles/thickbox.css" />
<script>
	$(function() {
		$(document.settings).submit(function() {
			if (this.ss_p.value != "") {
				if (this.ss_p_confirm.value == "") {
					alert("%%LNG_PasswordConfirmAlert%%");
					this.ss_p_confirm.focus();
					return false;
				}
				if (this.ss_p.value != this.ss_p_confirm.value) {
					alert("%%LNG_PasswordsDontMatch%%");
					this.ss_p_confirm.select();
					this.ss_p_confirm.focus();
					return false;
				}
			}
			return true;
		});

		$('.CancelButton', document.settings).click(function() { if(confirm('Are you sure you want to cancel?')) document.location.href='index.php?Page=ManageAccount'; });

		$('#usewysiwyg').click(function() { $('#sectionUseXHTML').toggle(); });

		$(document.settings.smtptype).click(function() {
			$('.SMTPDetails')[document.settings.smtptype[1].checked? 'show' : 'hide']();
			if(document.settings.smtptype[2]) $('.sectionSignuptoSMTP')[document.settings.smtptype[2].checked? 'show' : 'hide']();
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

			tb_show('%%LNG_SendPreview%%', 'index.php?Page=ManageAccount&Action=SendPreviewDisplay&keepThis=true&TB_iframe=tue&height=250&width=420', '');
			return true;
		});

		document.settings.smtptype[0].checked = !(document.settings.smtptype[1].checked = (document.settings.smtp_server.value != ''));

		if('%%GLOBAL_ShowSMTPInfo%%' != 'none') {
			$('.SMTPDetails')[document.settings.smtptype[1].checked? 'show' : 'hide']();
			if(document.settings.smtptype[2]) $('.sectionSignuptoSMTP')[document.settings.smtptype[2].checked? 'show' : 'hide']();
		}
	});

	function getSMTPPreviewParameters() {
		var values = {};
		$($('.smtpSettings', document.settings).fieldSerialize().split('&')).each(function(i,n) {
			var temp = n.split('=');
			if(temp.length == 2) values[temp[0]] = temp[1];
		});
		return values;
	}

	function closePopup() {
		tb_remove();
	}


	$(document).ready(function(){
		$('#cmdTestGoogleCalendar').click(function() {
			if ($('#googlecalendarusername').val() == '') {
				alert('%%LNG_EnterGoogleCalendarUsername%%');
				$('#googlecalendarusername').focus();
				return false;
			} else if ($('#googlecalendarpassword').val() == '') {
				alert('%%LNG_EnterGoogleCalendarPassword%%');
				$('#googlecalendarpassword').focus();
				return false;
			}
			params = '&gcusername=' + escape($('#googlecalendarusername').val()) + '&gcpassword=' + escape($('#googlecalendarpassword').val());

			$('#spanTestGoogleCalendar').show();
			$(this).attr('disabled', true);

			$.ajax({	type:		'GET',
						url:		'index.php',
						data:		{	Page: 		'ManageAccount',
										Action:		'TestGoogleCalendar',
										gcusername:	escape($('#googlecalendarusername').val()),
										gcpassword:	escape($('#googlecalendarpassword').val())},
						timeout:	10000,
						success:	function(data) {
										try {
											var d = eval('(' + data + ')');
											alert(d.message);
										} catch(e) { alert('{$lang.GooglecalendarTestError}'); }
									},
						error:		function() { alert('{$lang.GooglecalendarTestError}'); },
						complete:	function() {
										$('#spanTestGoogleCalendar').hide();
										$('#cmdTestGoogleCalendar').attr('disabled', false);
									}});

			return false;
		});
	});
</script>
<form name="settings" method="post" action="index.php?Page=%%PAGE%%&%%GLOBAL_FormAction%%">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">%%LNG_MyAccount%%</td>
		</tr>
		<tr>
			<td class="body pageinfo"><p>%%LNG_Help_MyAccount%%</p></td>
		</tr>
		<tr>
			<td>
				%%GLOBAL_Message%%
			</td>
		</tr>
		<tr>
			<td class=body>
				<input class="FormButton" type="submit" value="%%LNG_Save%%">
				<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%">
			</td>
		</tr>
		<tr>
			<td><br>
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr><td class=Heading2 colspan=2>%%LNG_UserDetails%%</td></tr>
					<tr>
						<td class="FieldLabel" width="10%">
							<img src="images/blank.gif" width="200" height="1" /><br />
							{template="Required"}
							%%LNG_UserName%%:
						</td>
						<td width="90%">
							%%GLOBAL_UserName%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_Password%%:
						</td>
						<td>
							<input type="password" name="ss_p" value="" class="Field250" autocomplete="off" />
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_PasswordConfirm%%:
						</td>
						<td>
							<input type="password" name="ss_p_confirm" value="" class="Field250" autocomplete="off" />
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_FullName%%:
						</td>
						<td>
							<input type="text" name="fullname" value="%%GLOBAL_FullName%%" class="Field250">
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_EmailAddress%%:
						</td>
						<td>
							<input type="text" name="emailaddress" value="%%GLOBAL_EmailAddress%%" class="Field250">&nbsp;%%LNG_HLP_EmailAddress%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_TimeZone%%:
						</td>
						<td>
							<select name="usertimezone">
								%%GLOBAL_TimeZoneList%%
							</select>&nbsp;&nbsp;&nbsp;%%LNG_HLP_TimeZone%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_ShowInfoTips%%:
						</td>
						<td>
							<label for="infotips"><input type="checkbox" id="infotips" name="infotips" value="1"%%GLOBAL_InfoTipsChecked%%> %%LNG_YesShowInfoTips%%</label> %%LNG_HLP_ShowInfoTips%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_UseWysiwygEditor%%:
						</td>
						<td>
							<div>
								<label for="usewysiwyg">
									<input type="checkbox" name="usewysiwyg" id="usewysiwyg" value="1"%%GLOBAL_UseWysiwyg%% />
									%%LNG_YesUseWysiwygEditor%%
								</label>
								%%LNG_HLP_UseWysiwygEditor%%
							</div>
							<div id="sectionUseXHTML"%%GLOBAL_UseXHTMLDisplay%%>
								<img width="20" height="20" src="images/nodejoin.gif"/>
								<label for="usexhtml">
									<input type="checkbox" name="usexhtml" id="usexhtml" value="1"%%GLOBAL_UseXHTMLCheckbox%% />
									%%LNG_YesUseXHTML%%
								</label>
								%%LNG_HLP_UseWysiwygXHTML%%
							</div>
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							{$lang.EnableActivityLog}:
						</td>
						<td>
							<label for="enableactivitylog"><input type="checkbox" name="enableactivitylog" id="enableactivitylog" value="1" %%GLOBAL_EnableActivityLog%%> {$lang.YesEnableActivityLog}</label> {$lnghlp.EnableActivityLog}
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_HTMLFooter%%:
						</td>
						<td>
							<textarea name="htmlfooter" rows="10" cols="50" wrap="virtual">%%GLOBAL_HTMLFooter%%</textarea>&nbsp;&nbsp;&nbsp;%%LNG_HLP_HTMLFooter%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_TextFooter%%:
						</td>
						<td>
							<textarea name="textfooter" rows="3" cols="50" wrap="virtual">%%GLOBAL_TextFooter%%</textarea>&nbsp;&nbsp;&nbsp;%%LNG_HLP_TextFooter%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							{$lang.EventTypeList}:
						</td>
						<td>
							<textarea name="eventactivitytype" rows="10" cols="50" wrap="virtual">%%GLOBAL_EventActivityType%%</textarea>&nbsp;&nbsp;&nbsp;{$lnghlp.EventTypeList}
						</td>
					</tr>
					<tr style="display: %%GLOBAL_ShowSMTPInfo%%">
						<td colspan="2" class="EmptyRow">
							&nbsp;
						</td>
					</tr>
					<tr style="display: %%GLOBAL_ShowSMTPInfo%%">
						<td colspan="2" class="Heading2">
							%%LNG_SmtpServerIntro%%
						</td>
					</tr>
					<tr style="display: %%GLOBAL_ShowSMTPInfo%%">
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SmtpServer%%:
						</td>
						<td>
							<label for="usedefaultsmtp">
								<input type="radio" name="smtptype" id="usedefaultsmtp" value="0"/>
								%%LNG_SmtpDefault%%
							</label>
							%%LNG_HLP_UseDefaultMail%%
						</td>
					</tr>
					<tr style="display: %%GLOBAL_ShowSMTPInfo%%">
						<td class="FieldLabel">&nbsp;</td>
						<td>
							<label for="usecustomsmtp">
								<input type="radio" name="smtptype" id="usecustomsmtp" value="1"/>
								%%LNG_SmtpCustom%%
							</label>
							%%LNG_HLP_UseSMTP_User%%
						</td>
					<tr style="display:none" class="SMTPDetails">
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_SmtpServerName%%:
						</td>
						<td>
							<img src="images/nodejoin.gif" width="20" height="20">
							<input type="text" name="smtp_server" value="%%GLOBAL_SmtpServer%%" class="Field250 smtpSettings"> %%LNG_HLP_SmtpServerName%%
						</td>
					</tr>
					<tr style="display:none" class="SMTPDetails">
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SmtpServerUsername%%:
						</td>
						<td>
							<img src="images/blank.gif" width="20" height="20">
							<input type="text" name="smtp_u" value="%%GLOBAL_SmtpUsername%%" class="Field250 smtpSettings"> %%LNG_HLP_SmtpServerUsername%%
						</td>
					</tr>
					<tr style="display:none" class="SMTPDetails">
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SmtpServerPassword%%:
						</td>
						<td>
							<img src="images/blank.gif" width="20" height="20">
							<input type="password" name="smtp_p" value="%%GLOBAL_SmtpPassword%%" class="Field250 smtpSettings" autocomplete="off" /> %%LNG_HLP_SmtpServerPassword%%
						</td>
					</tr>
					<tr style="display:none" class="SMTPDetails">
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SmtpServerPort%%:
						</td>
						<td>
							<img src="images/blank.gif" width="20" height="20">
							<input type="text" name="smtp_port" value="%%GLOBAL_SmtpPort%%" class="field50 smtpSettings"> %%LNG_HLP_SmtpServerPort%%
						</td>
					</tr>
					<tr style="display:none" class="SMTPDetails">
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_TestSMTPSettings%%:
						</td>
						<td>
							<img src="images/blank.gif" width="20" height="20">
							<input type="text" name="smtp_test" id="smtp_test" value="" class="Field250 smtpSettings"> %%LNG_HLP_TestSMTPSettings%%
						</td>
					</tr>
					<tr style="display:none" class="SMTPDetails">
						<td class="FieldLabel">
							&nbsp;
						</td>
						<td>
							<input type="button" name="cmdTestSMTP" value="%%LNG_TestSMTPSettings%%" class="FormButton" style="width: 120px;">
						</td>
					</tr>
					<tr style="display:%%GLOBAL_ShowSMTPCOMOption%%">
						<td class="FieldLabel">&nbsp;</td>
						<td>
							<label for="signtosmtp">
								<input type="radio" name="smtptype" id="signtosmtp" value="2"/>
								%%LNG_SMTPCOM_UseSMTPOption%%
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

					<tr>
						<td colspan="2" class="EmptyRow">
							&nbsp;
						</td>
					</tr>

					<tr><td class=Heading2 colspan=2>%%LNG_GoogleCalendarIntro%%</td></tr>
					<tr>

						<td class="FieldLabel" width="10%">
							<img src="images/blank.gif" width="200" height="1" /><br />
								{template="Not_Required"}
								%%LNG_GoogleCalendarUsername%%:
						</td>
						<td width="90%">
							<label for="googlecalendarusername">
								<input type="text" class="Field250 googlecalendar" name="googlecalendarusername" id="googlecalendarusername" value="%%GLOBAL_googlecalendarusername%%" autocomplete="off" />
							</label>
							%%LNG_HLP_GoogleCalendarUsername%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_GoogleCalendarPassword%%:
						</td>
						<td>
							<label for="googlecalendarpassword">
								<input type="password" class="Field250 googlecalendar" name="googlecalendarpassword" id="googlecalendarpassword" value="%%GLOBAL_googlecalendarpassword%%" autocomplete="off" />
							</label>
							%%LNG_HLP_GoogleCalendarPassword%%
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>
							<input type="button" id="cmdTestGoogleCalendar" value="%%LNG_TestLogin%%" class="FormButton" />
							<span id="spanTestGoogleCalendar" style="display:none;">&nbsp;&nbsp;<img src="images/searching.gif" alt="wait" /></span>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="EmptyRow">
							&nbsp;
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>
							<input class="FormButton" type="submit" value="%%LNG_Save%%">
							<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%">
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
