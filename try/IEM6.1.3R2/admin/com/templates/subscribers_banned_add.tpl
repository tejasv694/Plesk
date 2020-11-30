<form method="post" action="index.php?Page=Subscribers&Action=Banned&SubAction=Ban" enctype="multipart/form-data" onsubmit="return CheckForm();">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Subscribers_Banned_Add%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Subscribers_Banned_Add_Intro%%
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
				<input class="FormButton" type="submit" value="%%LNG_Save%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Banned_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Banned" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_BannedEmailDetails%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_BannedEmailsChooseList%%:&nbsp;
						</td>
						<td>
							<select name="list" style="width:350px">
								%%GLOBAL_SelectList%%
							</select>
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_BannedEmails%%:&nbsp;
						</td>
						<td style="padding:0px; margin:0px; height:15px">
							<input id="listRadio" name="bannedType" type="radio"> <label for="listRadio">%%LNG_Banned_AddEmailsUsingForm%%</label><br />
						</td>
					</tr>
					<tr style="display:none" id="trList">
						<td>&nbsp;</td>
						<td style="padding-left:20px" valign="top">
							<img src="images/nodejoin.gif" style="float:left" />&nbsp; <textarea name="BannedEmailList" style="width:250px" rows="5"></textarea>
							%%LNG_HLP_BannedEmails%%
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td style="padding:0px; margin:0px; height:15px">	
							<input id="fileRadio" name="bannedType" type="radio"> <label for="fileRadio">%%LNG_Add_Banned_From_File%%</label>
						</td>
					</tr>
					<tr style="display:none" id="trFile">
						<td>&nbsp;</td>
						<td style="padding-left:20px" valign="top">
							<img src="images/nodejoin.gif" style="float:left" />&nbsp; <input type="file" style="width:200px" name="BannedFile" class="Field">&nbsp;%%LNG_HLP_BannedFile%%
						</td>
					</tr>
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td>
							<input class="FormButton" type="submit" value="%%LNG_Save%%" />
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if (confirm("%%LNG_Subscribers_Banned_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Banned" }' />
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

		if(!f.listRadio.checked && !f.fileRadio.checked) {
			alert('%%LNG_Banned_Choose_Action%%');
			return false;
		}

		if (f.BannedEmailList.value == "" && f.listRadio.checked) {
			alert("%%LNG_Banned_Add_EmptyList%%");
			f.BannedEmailList.focus();
			return false;
		}

		if (f.BannedFile.value == "" && f.fileRadio.checked) {
			alert("%%LNG_Banned_Add_EmptyFile%%");
			f.BannedFile.focus();
			return false;
		}

		if (f.list.selectedIndex == -1) {
			alert("%%LNG_Banned_Add_ChooseList%%");
			f.list.focus();
			return false;
		}
		return true;
	}

	$('#listRadio').click(function() {
		$('#trList').show();
		$('#trFile').hide();

	});

	$('#fileRadio').click(function() {
		$('#trList').hide();
		$('#trFile').show();

	});

</script>