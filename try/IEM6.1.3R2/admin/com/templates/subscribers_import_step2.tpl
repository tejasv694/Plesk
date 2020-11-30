<form method="post" action="index.php?Page=Subscribers&Action=Import&SubAction=Step3" onsubmit="return CheckForm();" enctype="multipart/form-data">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Subscribers_Import_Step2%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
			<p>
				%%LNG_Subscribers_Import_Step2_Intro%%&nbsp;<a href="#" onClick="LaunchHelp('%%WHITELABEL_INFOTIPS%%','817'); return false;">%%LNG_ImportTutorialLink%%</a>
			</p>
			</td>
		</tr>
		<tr>
			<td>
				%%GLOBAL_Message%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_NextButton%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Import_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Import" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_ImportDetails%%
						</td>
					</tr>
					<tr style="display:none">
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_ImportStatus%%:
						</td>
						<td>
							<select name="status">
								<option value="active" SELECTED>%%LNG_Active%%
							</select>&nbsp;%%LNG_HLP_ImportStatus%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_ImportConfirmedStatus%%:
						</td>
						<td>
							<select name="confirmed">
								<option value="confirmed" SELECTED>%%LNG_Confirmed%%
								<option value="unconfirmed">%%LNG_Unconfirmed%%
							</select>&nbsp;%%LNG_HLP_ImportConfirmedStatus%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_ImportFormat%%:
						</td>
						<td>
							<select name="format" onchange="ChangeOptions();">
								%%GLOBAL_ListFormats%%
							</select>&nbsp;%%LNG_HLP_ImportFormat%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_OverwriteExistingSubscriber%%:
						</td>
						<td>
							<label for="overwrite"><input type="checkbox" name="overwrite" id="overwrite" value="1">&nbsp;%%LNG_YesOverwriteExistingSubscriber%%</label> %%LNG_HLP_OverwriteExistingSubscriber%%
						</td>
					</tr>
					<tr style="display:%%GLOBAL_ShowAutoresponderImport%%">
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_IncludeAutoresponder%%:
						</td>
						<td>
							<label for="autoresponder"><input type="checkbox" name="autoresponder" id="autoresponder" value="1">&nbsp;%%LNG_YesIncludeAutoresponder%%</label> %%LNG_HLP_IncludeAutoresponder%%
						</td>
					</tr>
					<tr>
						<td colspan="2" class="EmptyRow">
							&nbsp;
						</td>
					</tr>
					<tr style="display:none">
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_ImportType%%
						</td>
					</tr>
					<tr style="display:none">
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_ImportType%%:&nbsp;
						</td>
						<td>
							<select name="importtype">
								%%GLOBAL_ImportTypes%%
							</select>&nbsp;%%LNG_HLP_ImportType%%
						</td>
					</tr>
					<tr style="display:none">
						<td colspan="2" class="EmptyRow">
							&nbsp;
						</td>
					</tr>
					<!-- here we go for importing of files. //-->
						<div id="importinfo_file">
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;%%LNG_ImportFileDetails%%
							</td>
						</tr>
						<tr>
							<td width="200" class="FieldLabel">
								{template="Not_Required"}
								%%LNG_ContainsHeaders%%:
							</td>
							<td>
								<label for="headers"><input type="checkbox" name="headers" id="headers" value="1">&nbsp;%%LNG_YesContainsHeaders%%</label> %%LNG_HLP_ContainsHeaders%%
							</td>
						</tr>
						<tr>
							<td width="200" class="FieldLabel">
								{template="Required"}
								%%LNG_FieldSeparator%%:
							</td>
							<td>
								<input type="text" name="fieldseparator" class="Field250" value="%%GLOBAL_fieldseparator%%">&nbsp;%%LNG_HLP_FieldSeparator%%
							</td>
						</tr>
						<tr id="fieldenclosed_info" style="display: %%GLOBAL_ShowFieldEnclosed%%">
							<td width="200" class="FieldLabel">
								{template="Not_Required"}
								%%LNG_FieldEnclosed%%:
							</td>
							<td>
								<input type="text" name="fieldenclosed" class="Field250" value="%%GLOBAL_fieldenclosed%%">&nbsp;%%LNG_HLP_FieldEnclosed%%
							</td>
						</tr>
						<tr>
							<td width="200" class="FieldLabel">
								{template="Required"}
								%%LNG_ImportFile%%:
							</td>
							<td>
								<div>
									<input id="SubscriberImportUseUpload" type="radio" name="useserver" value="0" checked="checked" onClick="FileSourceFromServer(false);" />
									<label for="SubscriberImportUseUpload">%%LNG_ImportFile_SourceUpload%%</label>
									&nbsp;%%LNG_HLP_ImportFile%%
								</div>
								<div id="SubscriberImportUploadField" style="margin-left: 25px;">
									<input type="file" name="importfile" class="Field250" />
								</div>
							</td>
						</tr>
						<tr>
							<td width="200" class="FieldLabel">&nbsp;</td>
							<td>
								<div>
									<input id="SubscriberImportUseServer" type="radio" name="useserver" value="1" onClick="FileSourceFromServer(true);" />
									<label for="SubscriberImportUseServer">%%LNG_ImportFile_SourceServer%%</label>
									&nbsp;%%LNG_HLP_ImportFileFromServer%%
								</div>
								<div id="SubscriberImportServerField" style="margin-left: 25px; display:none;">
									<select name="serverfile" class="Field250" disabled="disabled">
										%%GLOBAL_fieldServerFiles%%
									</select>
								</div>
								<div id="SubscriberImportServerNoList" style="margin: 5px 0 0 25px; display:none; width:300px; font-style: italic;">
									%%LNG_ImportFile_ServerFileEmptyList%%
								</div>
							</td>
						</tr>
					</div>
					<!-- end of importing files //-->
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel">&nbsp;</td>
						<td valign="top" height="30">
							<input class="FormButton" type="submit" value="%%LNG_NextButton%%">
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Import_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Import" }'>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<script>
	function CheckForm() {
		var f = document.forms[0];
		var import_index = f.importtype.selectedIndex;
		var importtype = f.importtype.options[import_index].value;

		if (importtype == 'file') {
			if (f.fieldseparator.value == '') {
				alert('%%LNG_ImportFile_FieldSeparatorEmpty%%');
				f.fieldseparator.focus();
				return false;
			}
			if(f.useserver[1].checked) {
				if (f.localfile.value == '') {
					alert('%%LNG_ImportFile_ServerFileEmpty%%');
					f.serverfile.focus();
					return false;
				}
			} else {
				if (f.importfile.value == '') {
					alert('%%LNG_ImportFile_FileEmpty%%');
					f.importfile.focus();
					return false;
				}
			}
			return true;
		}
	}

	function ChangeOptions() {
		var Options = Array('file');
		var f = document.forms[0];
		var import_index = f.importtype.selectedIndex;
		var importtype = f.importtype.options[import_index].value;
		for (var option in Options) {
			if (option == importtype) {
				document.getElementById('importinfo_' + option).display.style = '';
			} else {
				document.getElementById('importinfo_' + option).display.style = 'none';
			}
		}
	}

	function FileSourceFromServer(value) {
		var frm = document.forms[0];
		frm.importfile.disabled = value;
		document.getElementById('SubscriberImportUploadField').style.display = value? 'none' : '';
		frm.serverfile.disabled = !value;
		document.getElementById(frm.serverfile.options.length == 0? 'SubscriberImportServerNoList' : 'SubscriberImportServerField').style.display = value? '' : 'none';
	}

	function ImportTutorial()
	{
		window.open('index.php?Page=Subscribers&Action=Import&SubAction=ViewTutorial', "importWin", "left=" + (((screen.availWidth) / 2) - 225) + ", top="+ (((screen.availHeight) / 2) - 300) +", width=450, height=600, toolbar=0, statusbar=0, scrollbars=1");
	}
</script>
