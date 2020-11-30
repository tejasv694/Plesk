<form method="post" action="index.php?Page=Autoresponders&Action=%%GLOBAL_Action%%" onsubmit="return CheckForm()" autocomplete="off">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%GLOBAL_Heading%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%GLOBAL_Intro%%
				</p>
				%%GLOBAL_Message%%
			</td>
		</tr>
		<tr>
			<td>
				%%GLOBAL_CronWarning%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_Next%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Autoresponders&Action=Step2&list=%%GLOBAL_List%%" }'>
				<input type="hidden" name="charset" value="%%GLOBAL_Charset%%">
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel" id="AutoresponderDetails">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_AutoresponderDetails%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_SendFromName%%&nbsp;
						</td>
						<td>
							<input type="text" name="sendfromname" class="Field250" value="%%GLOBAL_SendFromName%%">&nbsp;%%LNG_HLP_SendFromName%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_SendFromEmail%%:&nbsp;
						</td>
						<td>
							<input type="text" name="sendfromemail" class="Field250" value="%%GLOBAL_SendFromEmail%%">&nbsp;%%LNG_HLP_SendFromEmail%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_ReplyToEmail%%:&nbsp;
						</td>
						<td>
							<input type="text" name="replytoemail" class="Field250" value="%%GLOBAL_ReplyToEmail%%">&nbsp;%%LNG_HLP_ReplyToEmail%%
						</td>
					</tr>
					<tr style="display: %%GLOBAL_ShowBounceInfo%%">
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_BounceEmail%%:&nbsp;
						</td>
						<td>
							<input type="text" name="bounceemail" class="Field250" value="%%GLOBAL_BounceEmail%%">&nbsp;%%LNG_HLP_BounceEmail%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_EmailFormat%%:&nbsp;
						</td>
						<td>
							<select name="format" onChange="adjustHtmlEmailPreferences();">
								%%GLOBAL_FormatList%%
							</select>
							&nbsp;
							%%LNG_HLP_EmailFormat%%
						</td>
					</tr>
					<tr style="display: %%GLOBAL_DisplayNameOptions%%">
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SendTo_FirstName%%:
						</td>
						<td>
							<select name="to_firstname">
								%%GLOBAL_FirstNameOptions%%
							</select>&nbsp;&nbsp;%%LNG_HLP_SendTo_FirstName%%
						</td>
					</tr>
					<tr style="display: %%GLOBAL_DisplayNameOptions%%">
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SendTo_LastName%%:
						</td>
						<td>
							<select name="to_lastname">
								%%GLOBAL_LastNameOptions%%
							</select>&nbsp;&nbsp;%%LNG_HLP_SendTo_LastName%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_AutoresponderIncludeExisting%%:&nbsp;
						</td>
						<td>
							<label for="includeexisting"><input type="checkbox" name="includeexisting" id="includeexisting" value="1" onClick="javascript: checkExisting(this, '%%GLOBAL_AutoresponderID%%');">%%LNG_AutoresponderIncludeExistingExplain%%</label>&nbsp;%%LNG_HLP_AutoresponderIncludeExisting%%
						</td>
					</tr>
				</table>
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel" id="FormatDetails_Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_SchedulingDetails%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_HoursDelayed%%:&nbsp;
						</td>
						<td>
							<input name="sendWhen" type="radio" id="timeASAP" /> <label for="timeASAP">%%LNG_Autoresponder_Send_ASAP%%</label> %%LNG_HLP_HoursDelayed%%
							<br />
							<div style="padding-bottom:3px">
								<input name="sendWhen" type="radio" id="timeCustom" /> <label for="timeCustom">%%LNG_Autoresponder_Send_Custom%%:</label>
							</div>
							<img src="images/nodejoin.gif" style="padding-left:20px; padding-top:2px" align="left" />&nbsp;
							<input id="hoursafter" type="text" value="%%GLOBAL_HoursAfterSubscription%%" class="Field50" style="width:30px" onclick="document.getElementById('timeCustom').checked=true;" />
							<select id="hoursInPeriod" style="width:80px" onchange="document.getElementById('timeCustom').checked=true;" onclick="document.getElementById('timeCustom').checked=true;">
								<option value="1" SELECTED="SELECTED">%%LNG_Autoresponder_Period_Hours%%</option>
								<option value="24">%%LNG_Autoresponder_Period_Days%%</option>
								<option value="168">%%LNG_Autoresponder_Period_Weeks%%</option>
								<option value="672">%%LNG_Autoresponder_Period_Months%%</option>
								<option value="8064">%%LNG_Autoresponder_Period_Years%%</option>
							</select>
							<input type="hidden" id="hoursaftersubscription" name="hoursaftersubscription" class="Field" style="width: 40px;" value="%%GLOBAL_HoursAfterSubscription%%">
						</td>
					</tr>
				</table>
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel" id="FormatDetails_Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_FormatDetails%%
						</td>
					</tr>
					<tr id="HTMLFormatDetails1">
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SendMultipart%%&nbsp;
						</td>
						<td>
							<label for="multipart"><input type="checkbox" name="multipart" id="multipart" value="1" %%GLOBAL_multipart%%>&nbsp;%%LNG_SendMultipartExplain%%</label>&nbsp;%%LNG_HLP_SendMultipart%%
						</td>
					</tr>
					<tr id="HTMLFormatDetails2">
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_TrackOpens%%&nbsp;
						</td>
						<td>
							<label for="trackopens"><input type="checkbox" name="trackopens" id="trackopens" value="1" %%GLOBAL_trackopens%%>&nbsp;%%LNG_TrackOpensExplain%%</label>&nbsp;%%LNG_HLP_TrackOpens%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_TrackLinks%%:&nbsp;
						</td>
						<td>
							<label for="tracklinks"><input type="checkbox" name="tracklinks" id="tracklinks" value="1" %%GLOBAL_tracklinks%%>&nbsp;%%LNG_TrackLinksExplain%%</label>&nbsp;%%LNG_HLP_TrackLinks%%
						</td>
					</tr>
					<tr id="HTMLFormatDetails3" style="display: none;">
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_EmbedImages%%:&nbsp;
						</td>
						<td>
							<label for="embedimages"><input type="checkbox" name="embedimages" id="embedimages" value="1" %%GLOBAL_embedimages%%>&nbsp;%%LNG_EmbedImagesExplain%%</label>&nbsp;%%LNG_HLP_EmbedImages%%
						</td>
					</tr>
				</table>
				<div style="display: %%GLOBAL_HideCompleteTemplateList%%">
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel" id="TemplateDetails" style="margin-bottom: 0;">
						<tr>
							<td colspan="3" class="Heading2">
								&nbsp;&nbsp;%%LNG_TemplateDetails%%
							</td>
						</tr>
						<tr style="display: %%GLOBAL_DisplayTemplateList%%">
							<td width="200" class="FieldLabel" valign="top">
								{template="Not_Required"}
								%%LNG_Autoresponder_I_Want_To%%:
							</td>
							<td valign="top">
								<input type="radio" checked="checked" onclick="$('#chooseATemplate').hide()" id="noTpl" name="AutoresponderContent" /> <label for="noTpl">%%LNG_Autoresponder_From_Scratch%%</label><br />
								<div style="padding-bottom:5px"><input type="radio" onclick="$('#chooseATemplate').show()" id="useTpl" name="AutoresponderContent" /> <label for="useTpl">%%LNG_Autoresponder_From_Tpl%%</label> %%LNG_HLP_ChooseTemplate%%</div>
								<table border="0" id="chooseATemplate" style="display:none">
									<tr>
										<td valign="top" style="padding-left:20px">
											<img src="images/nodejoin.gif" />
										</td>
										<td valign="top">
											%%GLOBAL_TemplateList%%
										</td>
										<td valign="top">
											<div style="display: block; width: 255px; text-align: center; display: %%GLOBAL_DisplayTemplateList%%">
												<div><a href="javascript:void(0);" onclick="javascript:ShowPreview();"><img id="imgPreview" src="images/nopreview.gif" width="247" height="200" style="border: 1px solid black" onerror="this.src='images/nopreview.gif';"></a></div>
												<div style="padding-top: 10px;"><a href="#" onclick="javascript:ShowPreview(); return false;"><img src="images/magnify.gif" border="0" style="padding-right:5px">%%LNG_Preview_Template%%</a></div>
											</div>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<br />
				</div>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td>
							<input class="FormButton" type="submit" value="%%LNG_Next%%" />
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if (confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Autoresponders&Action=Step2&list=%%GLOBAL_List%%" }' />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
<script>
	var ShowEmbed = %%GLOBAL_ShowEmbed%%;

	function CheckForm() {
		// Set the value of the hoursaftersubscription field because we've changed the way we show
		// when the autoresponder will be sent (added by Mitch during alpha testing for IEM5)
		var num_hours = 0;
		var base_date = $('#hoursInPeriod').val();
		var hoursafter = document.getElementById('hoursafter');

		// Is the number (days, hours, etc) valid?
		if(document.getElementById('timeCustom').checked) {
			if((isNaN(hoursafter.value) || hoursafter.value == '' || parseInt(hoursafter.value) < 0)) {
				// Invalid time period
				alert('%%LNG_Autoresponder_Valid_Time%%');
				hoursafter.focus();
				hoursafter.select();
				return false;
			}
			else {
				// Valid details, convert to hours
				num_hours = parseInt(hoursafter.value) * parseInt(base_date);
			}
		}
		else {
			// Sending immediately, so its zero - do nothing
		}

		document.getElementById('hoursaftersubscription').value = num_hours;
		return true;
	}

	function checkExisting(frm, id)
	{
		// we unchecked the box? no problem.
		if (!frm.checked) {
			return;
		}

		// if the id is 0, then we're creating an autoresponder and don't care about this being checked.
		var int_id = parseInt(id);
		if (int_id == 0 || isNaN(int_id)) {
			return;
		}

		$.ajax({
			type: 'post',
			url: 'remote.php',
			data: 'what=check_existing&auto=' + id,
			success: function (msg) {
				if (msg.length > 0) {
					alert(msg);
				}
			}
		});
	}

	function changeHours(frm) {
		var selected = frm.selectedIndex;
		var hrs = frm[selected].value;
		if (hrs > -1) {
			document.getElementById('hoursaftersubscription').value = hrs;
		}
	}
	function adjustHtmlEmailPreferences() {
		var selected = document.forms[0].format;
		if (selected.options[selected.selectedIndex].value=='t') {
			document.getElementById('HTMLFormatDetails1').style.display='none';
			document.getElementById('HTMLFormatDetails2').style.display='none';
			if (ShowEmbed) {
				document.getElementById('HTMLFormatDetails3').style.display='none';
			}
			document.getElementById('FormatDetails_Panel').style.marginBottom='0px';
			document.getElementById('TemplateDetails').style.display='none';
		} else {
			document.getElementById('FormatDetails_Panel').style.marginBottom='20px';
			document.getElementById('HTMLFormatDetails1').style.display='';
			document.getElementById('HTMLFormatDetails2').style.display='';

			if (ShowEmbed) {
				document.getElementById('HTMLFormatDetails3').style.display='';
			}

			document.getElementById('TemplateDetails').style.display='';
		}
	}

	$(document).ready(function() {
		adjustHtmlEmailPreferences();

		if($('#hoursaftersubscription').val() == 0) {
			$('#timeASAP').click();
		}
		else {
			$('#timeCustom').click();
		}
	});

</script>
