<form method="post" action="index.php?Page=Subscribers&Action=Banned&SubAction=Update&id=%%GLOBAL_BanID%%">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Subscribers_Banned_Edit%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Subscribers_Banned_Edit_Intro%%
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
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Banned_Edit_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Banned" }'>
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
							%%LNG_BanSingleEmail%%:&nbsp;
						</td>
						<td>
							<input type="text" name="BannedEmail" value="%%GLOBAL_BannedAddress%%" class="Field250">&nbsp;%%LNG_HLP_BanSingleEmail%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_BannedEmailsChooseList_Edit%%:&nbsp;
						</td>
						<td>
							<select name="list" size="7" class="Field250">
								%%GLOBAL_SelectList%%
							</select>&nbsp;%%LNG_HLP_BannedEmailsChooseList_Edit%%
						</td>
					</tr>
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td>
							<input class="FormButton" type="submit" value="%%LNG_Save%%" />
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if (confirm("%%LNG_Subscribers_Banned_Edit_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Banned" }' />
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
		if (f.BannedEmail.value == "") {
			alert("%%LNG_Banned_Edit_Empty%%");
			f.BannedEmail.focus();
			return false;
		}
		if (f.list.selectedIndex == -1) {
			alert("%%LNG_Banned_Edit_ChooseList%%");
			f.list.focus();
			return false;
		}
		return true;
	}
</script>