<script>
	var SendPage = {	varPrevNewsletterIdx:	0,
						varCurNewsletterIdx:	0,
						_CheckFormObservers:	[],
						toggleTrackerOptions: 	function() { $('#tracklinks_module_list')[this.getFormObject().tracklinks.checked && $('.Module_Tracker_Options').length > 0? 'show' : 'hide'](); },
						getFormObject: 			function() { return document.frmSendStep3; },
						getCampaignName:		function() {
													var o = this.getFormObject().newsletter;
													if(o.selectedIndex != 0) return '';
													else return o[o.selectedIndex].text;
												},
						addCheckFormObserver:	function($fn) { if($fn) this._CheckFormObservers.push($fn); },
						checkForm:				function() {
													for(var i = 0, j = this._CheckFormObservers.length; i < j; ++i) {
														if(this._CheckFormObservers[i]) {
															try {
																if(!this._CheckFormObservers[i]())
																	return false;
															} catch(e) { }
														}
													}

													return true;
												}};

	SendPage.addCheckFormObserver(function() {
		var form = SendPage.getFormObject();

		if (form.newsletter.selectedIndex < 1) {
			alert("%%LNG_SelectNewsletterPrompt%%");
			form.newsletter.focus();
			return false;
		}

		if (form.sendfromname.value == '') {
			alert("%%LNG_EnterSendFromName%%");
			form.sendfromname.focus();
			return false;
		}

		if (form.sendfromemail.value == '') {
			alert("%%LNG_EnterSendFromEmail%%");
			form.sendfromemail.focus();
			return false;
		}

		if (form.replytoemail.value == '') {
			alert("%%LNG_EnterReplyToEmail%%");
			form.replytoemail.focus();
			return false;
		}

		if (form.bounceemail.value == '') {
			alert("%%LNG_EnterBounceEmail%%");
			form.bounceemail.focus();
			return false;
		}

		return true;
	});


	$(function() {
		$(document.frmSendStep3).submit(function() { return SendPage.checkForm(); });

		$('input.CancelButton', document.frmSendStep3).click(function() {
			if(confirm("%%LNG_Send_CancelPrompt%%")) document.location="index.php?Page=Newsletters";
		});

		$('#hrefPreview').click(function() {
			var baseurl = "index.php?Page=Newsletters&Action=Preview&id=";
			if (document.frmSendStep3.newsletter.selectedIndex < 0) {
				alert("%%LNG_SelectNewsletterPrompt%%");
				document.frmSendStep3.newsletter.focus();
				return false;
			}
			if (document.frmSendStep3.newsletter.length > 1) {
				if (document.frmSendStep3.newsletter.selectedIndex == 0) {
					alert("%%LNG_SelectNewsletterPreviewPrompt%%");
					document.frmSendStep3.newsletter.focus();
					return false;
				}
			}
			var realId = document.frmSendStep3.newsletter[document.frmSendStep3.newsletter.selectedIndex].value;
			window.open(baseurl + realId , "pp");
			return false;
		});

		$(document.frmSendStep3.newsletter).change(function() {
			if(this.selectedIndex == 0) return;
			SendPage.varPrevNewsletterIdx = SendPage.varCurNewsletterIdx;
			SendPage.varCurNewsletterIdx = this.selectedIndex;
		});

		$(document.frmSendStep3.tracklinks).click(function() { SendPage.toggleTrackerOptions(); });



		SendPage.toggleTrackerOptions();
	});

	function ShowSendTime(chkbox) {
		if (chkbox.checked) {
			document.getElementById('show_senddate').style.display='none';
		} else {
			document.getElementById('show_senddate').style.display='';
		}
	}
</script>
<form name="frmSendStep3" method="post" action="index.php?Page=Send&Action=Step4">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Send_Step3%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Send_Step3_Intro%%
				</p>
			</td>
		</tr>
		<tr>
			<td>
				%%GLOBAL_Message%%
				%%GLOBAL_NoCronMessage%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_Next%%" />
				<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%" />
				<input type="hidden" name="sendcharset" value="%%GLOBAL_SendCharset%%" />
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_WhichCampaignToSend%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}%%LNG_WhichEmailToSend%%&nbsp;
						</td>
						<td>
							<select name="newsletter" style="margin-top:4px">
								%%GLOBAL_NewsletterList%%
							</select>&nbsp;
							%%LNG_HLP_SendNewsletter%%<a id="hrefPreview" href="#"><img src="images/magnify.gif" border="0">&nbsp;&nbsp;%%LNG_Preview%%</a>
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_SendFromName%%&nbsp;
						</td>
						<td>
							<input type="text" name="sendfromname" value="%%GLOBAL_SendFromName%%" class="Field250">%%LNG_HLP_SendFromName%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_SendFromEmail%%:&nbsp;
						</td>
						<td>
							<input type="text" name="sendfromemail" value="%%GLOBAL_SendFromEmail%%" class="Field250">%%LNG_HLP_SendFromEmail%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_ReplyToEmail%%:&nbsp;
						</td>
						<td>
							<input type="text" name="replytoemail" value="%%GLOBAL_ReplyToEmail%%" class="Field250">%%LNG_HLP_ReplyToEmail%%
						</td>
					</tr>
					<tr style="display: %%GLOBAL_ShowBounceInfo%%">
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_BounceEmail%%:&nbsp;
						</td>
						<td>
							<input type="text" name="bounceemail" value="%%GLOBAL_BounceEmail%%" class="Field250">%%LNG_HLP_BounceEmail%%
						</td>
					</tr>
					<tr>
						<td class="EmptyRow" colspan="2"></td>
					</tr>
					%%GLOBAL_CronOptions%%
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_AdvancedSendingOptions%%
						</td>
					</tr>
					<tr style="display: %%GLOBAL_DisplayNameOptions%%">
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SendTo_FirstName%%:
						</td>
						<td>
							<select name="to_firstname">
								<option value="0">%%LNG_SelectFirstNameOption%%</option>
								%%GLOBAL_NameOptions%%
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
								<option value="0">%%LNG_SelectLastNameOption%%</option>
								%%GLOBAL_NameOptions%%
							</select>&nbsp;&nbsp;%%LNG_HLP_SendTo_LastName%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SendMultipart%%&nbsp;
						</td>
						<td>
							<label for="sendmultipart"><input type="checkbox" name="sendmultipart" id="sendmultipart" value="1" CHECKED>&nbsp;%%LNG_SendMultipartExplain%%</label>&nbsp;%%LNG_HLP_SendMultipart%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_TrackOpens%%&nbsp;
						</td>
						<td>
							<div>
								<label for="trackopens"><input type="checkbox" name="trackopens" id="trackopens" value="1" CHECKED>
									%%LNG_TrackOpensExplain%%
								</label>&nbsp;%%LNG_HLP_TrackOpens%%
							</div>
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_TrackLinks%%&nbsp;
						</td>
						<td>
							<div>
								<label for="tracklinks">
									<input type="checkbox" name="tracklinks" id="tracklinks" value="1" CHECKED>&nbsp;%%LNG_TrackLinksExplain%%
								</label>&nbsp;%%LNG_HLP_TrackLinks%%
							</div>
							<div id="tracklinks_module_list">
								<div style="float: left;"><img width="20" height="20" src="images/nodejoin.gif"/></div>
								<div style="float: left;">%%GLOBAL_TrackerOptions%%</div>
							</div>
						</td>
					</tr>
					<tr style="display: %%GLOBAL_DisplayEmbedImages%%">
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_EmbedImages%%&nbsp;
						</td>
						<td>
							<div>
								<label for="embedimages">
									<input type="checkbox" name="embedimages" id="embedimages" value="1"%%GLOBAL_EmbedImages%%>&nbsp;%%LNG_EmbedImagesExplain%%
								</label>&nbsp;%%LNG_HLP_EmbedImages%%
							</div>
						</td>
					</tr>
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td>
							<input class="FormButton" type="submit" value="%%LNG_Next%%" />
							<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
