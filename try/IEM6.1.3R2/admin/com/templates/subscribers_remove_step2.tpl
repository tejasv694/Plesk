<form method="post" action="index.php?Page=Subscribers&Action=Remove&SubAction=Step3&list=%%GLOBAL_list%%" enctype="multipart/form-data" onsubmit="return CheckForm();">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Subscribers_Remove_Step2%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Subscribers_Remove_Step2_Intro%%
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
				<input class="FormButton" type="submit" value="%%LNG_Next%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Remove_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Remove" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							%%LNG_Subscribers_Remove_Heading%%
						</td>
					</tr>
					<tr style="height:15px">
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_RemoveEmails%%:&nbsp;
						</td>
						<td>
							<input type="radio" name="howRemove" id="radioTextbox" /> <label for="radioTextbox">%%LNG_RemoveViaTextbox%%</label>
						</td>
					</tr>
					<tr style="display:none" id="trTextbox">
						<td class="FieldLabel">
							&nbsp;
						</td>
						<td valign="top" style="padding-left:20px">
							<img src="images/nodejoin.gif" style="float:left" />&nbsp; <textarea name="RemoveEmailList" id="RemoveEmailList" cols="30" rows="5" style="width: 250px;"></textarea>&nbsp;&nbsp;&nbsp;%%LNG_HLP_RemoveEmails%%
						</td>
					</tr>
					<tr style="height:15px">
						<td class="FieldLabel">
							&nbsp;
						</td>
						<td>
							<input type="radio" name="howRemove" id="radioFile" /> <label for="radioFile">%%LNG_RemoveViaFile%%</label>
						</td>
					</tr>
					<tr style="display:none" id="trFile">
						<td class="FieldLabel">
							&nbsp;
						</td>
						<td valign="top" style="padding-left:20px">
							<img src="images/nodejoin.gif" style="float:left" />&nbsp; <input type="file" name="RemoveFile" id="RemoveFile" class="Field250">
							%%LNG_HLP_RemoveFile%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_RemoveOptions%%:&nbsp;
						</td>
						<td>
							<select name="RemoveOption" id="RemoveOption">
								<option value="Unsubscribe">%%LNG_RemoveUnsubscribe%%</option>
								<option value="Delete">%%LNG_RemoveDelete%%</option>
							</select>
							&nbsp;%%LNG_HLP_RemoveOptions%%
						</td>
					</tr>
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel">&nbsp;</td>
						<td valign="top" height="30">
							<input class="FormButton" type="submit" value="%%LNG_Next%%">
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Remove_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Remove" }'>
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
		if (f.RemoveEmailList.value == "" && f.RemoveFile.value =="") {
			alert("%%LNG_EnterEmailAddressesToRemove%%");
			f.RemoveEmailList.focus();
			return false;
		}

		// Double check they really want to do this if they selected the delete option (Added by Mitch)
		if(document.getElementById('RemoveOption').selectedIndex == 1) {
			if(!confirm('%%LNG_RemoveConfirmDelete%%')) {
				return false;
			}
		}

		return true;
	}

	// Added by Mitch when redesigning the removal process for IEM 5
	$('#radioTextbox').click(function() {
		$('#trTextbox').show();
		$('#trFile').hide();
		$('#RemoveEmailList').focus();
	});

	$('#radioFile').click(function() {
		$('#trTextbox').hide();
		$('#trFile').show();
	});

</script>
