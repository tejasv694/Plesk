<script>
	var SendPage = {	varPrevNewsletterIdx:	0,
						varCurNewsletterIdx:	0,
						_CheckFormObservers:	[],
						getFormObject: 			function() { return document.frmSend_Step2; },
						toggleTrackerOptions: 	function() { $('#tracklinks_module_list')[this.getFormObject().tracklinks.checked && $('.Module_Tracker_Options').length > 0? 'show' : 'hide'](); },
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

		if (form.sendfromname.value == '') {
			alert("{$lang.Addon_splittest_Send_Step2_EnterSendFromName}");
			form.sendfromname.focus();
			return false;
		}

		if (form.sendfromemail.value == '') {
			alert("{$lang.Addon_splittest_Send_Step2_EnterSendFromEmail}");
			form.sendfromemail.focus();
			return false;
		}

		if (form.replytoemail.value == '') {
			alert("{$lang.Addon_splittest_Send_Step2_EnterReplyToEmail}");
			form.replytoemail.focus();
			return false;
		}

		if (form.bounceemail.value == '') {
			alert("{$lang.Addon_splittest_Send_Step2_EnterBounceEmail}");
			form.bounceemail.focus();
			return false;
		}

		return true;
	});


	$(function() {
		$(document.frmSend_Step2).submit(function() { return SendPage.checkForm(); });

		$('input.CancelButton', document.frmSend_Step2).click(function() {
			if(confirm("{$lang.Addon_splittest_Send_CancelPrompt}")) {
				document.location = '{$AdminUrl}';
			}
		});

		$(document.frmSend_Step2.tracklinks).click(function() {
			var opt = document.frmSend_Step2.tracklinks;
			if (!opt.checked) {
				alert('{$lang.Addon_splittest_Send_Step2_MustTrackLinks}');
				opt.checked = true;
			}
			SendPage.toggleTrackerOptions();
		});

		$(document.frmSend_Step2.trackopens).click(function() {
			var opt = document.frmSend_Step2.trackopens;
			if (!opt.checked) {
				alert('{$lang.Addon_splittest_Send_Step2_MustTrackOpens}');
				opt.checked = true;
			}
		});

		SendPage.toggleTrackerOptions();
	});

	{if $CronEnabled}
	function ShowSendTime(chkbox) {
		if (chkbox.checked) {
			document.getElementById('show_senddate').style.display='none';
		} else {
			document.getElementById('show_senddate').style.display='';
		}
	}
	{/if}
</script>
<form name="frmSend_Step2" method="post" action="{$AdminUrl}&Action=Send&Step=3">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				{$lang.Addon_splittest_Send_Step2_Heading}
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					{$lang.Addon_splittest_Send_Step2_Intro}
				</p>
			</td>
		</tr>
		<tr>
			<td>
				{$FlashMessages}
				%%GLOBAL_NoCronMessage%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_Next%%" />
				<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%" />
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;{$lang.Addon_splittest_Send_Step2_Settings}
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="required"}
							{$lang.Addon_splittest_Send_Step2_FromName}&nbsp;
						</td>
						<td>
							<input type="text" name="sendfromname" value="{$fromname}" class="Field250">{$lnghlp.Addon_splittest_Send_Step2_FromName}
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="required"}
							{$lang.Addon_splittest_Send_Step2_FromEmail}&nbsp;
						</td>
						<td>
							<input type="text" name="sendfromemail" value="{$fromemail}" class="Field250">{$lnghlp.Addon_splittest_Send_Step2_FromEmail}
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="required"}
							{$lang.Addon_splittest_Send_Step2_ReplyToEmail}&nbsp;
						</td>
						<td>
							<input type="text" name="replytoemail" value="{$replytoemail}" class="Field250">{$lnghlp.Addon_splittest_Send_Step2_ReplyToEmail}
						</td>
					</tr>
					{if $ShowBounceInfo}
					<tr>
						<td width="200" class="FieldLabel">
							{template="required"}
							{$lang.Addon_splittest_Send_Step2_BounceEmail}&nbsp;
						</td>
						<td>
							<input type="text" name="bounceemail" value="{$bounceemail}" class="Field250">{$lnghlp.Addon_splittest_Send_Step2_BounceEmail}
						</td>
					</tr>
					{/if}
					<tr>
						<td class="EmptyRow" colspan="2"></td>
					</tr>
					{if $CronEnabled}
						{template="send_step2_cron_options"}
					{/if}
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;{$lang.Addon_splittest_Send_Step2_AdvancedOptions}
						</td>
					</tr>
					{if $ShowCustomFields}
					<tr>
						<td class="FieldLabel">
							{template="not_required"}
							{$lang.Addon_splittest_Send_Step2_FirstName}
						</td>
						<td>
							<select name="to_firstname">
								<option value="0">{$lang.Addon_splittest_Send_Step2_FirstName_Choose}</option>
								{foreach from=$CustomFields key=fieldid item=fieldname}
									<option value="{$fieldid}">{$fieldname}</option>
								{/foreach}
							</select>&nbsp;&nbsp;{$lnghlp.Addon_splittest_Send_Step2_FirstName}
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="not_required"}
							{$lang.Addon_splittest_Send_Step2_LastName}
						</td>
						<td>
							<select name="to_lastname">
								<option value="0">{$lang.Addon_splittest_Send_Step2_LastName_Choose}</option>
								{foreach from=$CustomFields key=fieldid item=fieldname}
									<option value="{$fieldid}">{$fieldname}</option>
								{/foreach}
							</select>&nbsp;&nbsp;{$lnghlp.Addon_splittest_Send_Step2_LastName}
						</td>
					</tr>
					{/if}
					<tr>
						<td width="200" class="FieldLabel">
							{template="not_required"}
							{$lang.Addon_splittest_Send_Step2_Multipart}
							&nbsp;
						</td>
						<td>
							<label for="sendmultipart"><input type="checkbox" name="sendmultipart" id="sendmultipart" value="1"
							CHECKED>&nbsp;{$lang.Addon_splittest_Send_Step2_Multipart_Explain}</label>&nbsp;{$lnghlp.Addon_splittest_Send_Step2_Multipart}
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="required"}
							{$lang.Addon_splittest_Send_Step2_TrackOpens}
							&nbsp;
						</td>
						<td>
							<div>
								<label for="trackopens"><input type="checkbox" name="trackopens" id="trackopens" value="1" CHECKED>
									{$lang.Addon_splittest_Send_Step2_TrackOpens_Explain}
								</label>&nbsp;{$lnghlp.Addon_splittest_Send_Step2_TrackOpens}
							</div>
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="required"}
							{$lang.Addon_splittest_Send_Step2_TrackLinks}
							&nbsp;
						</td>
						<td>
							<div>
								<label for="tracklinks">
									<input type="checkbox" name="tracklinks" id="tracklinks" value="1" CHECKED>
									{$lang.Addon_splittest_Send_Step2_TrackLinks_Explain}
								</label>&nbsp;{$lnghlp.Addon_splittest_Send_Step2_TrackLinks}
							</div>
							<div id="tracklinks_module_list">
								<div style="float: left;"><img width="20" height="20" src="images/nodejoin.gif"/></div>
								<div style="float: left;">%%GLOBAL_TrackerOptions%%</div>
							</div>
						</td>
					</tr>
					{if $DisplayEmbedImages}
					<tr>
						<td width="200" class="FieldLabel">
							{template="not_required"}
							{$lang.Addon_splittest_Send_Step2_EmbedImages}
						</td>
						<td>
							<div>
								<label for="embedimages">
									<input type="checkbox" name="embedimages" id="embedimages" value="1"{if $EmbedImagesByDefault} CHECKED{/if}>
									{$lang.Addon_splittest_Send_Step2_EmbedImages_Explain}
								</label>&nbsp;{$lnghlp.Addon_splittest_Send_Step2_EmbedImages}
							</div>
						</td>
					</tr>
					{/if}
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
