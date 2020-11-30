<form method="post" action="index.php?Page=Subscribers&Action=Banned&SubAction=Step2" onsubmit="return CheckForm();">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Subscribers_Banned%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
				%%LNG_Subscribers_Banned_Intro%%
				</p>
				%%GLOBAL_Message%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_Next%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_BannedManage_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Banned" }'>
				<br />&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_MailingListDetails%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_ShowSupressionsFor%%:&nbsp;
						</td>
						<td>
							<select name="list" style="width: 350px" size="9" onDblClick="this.form.submit()">
								%%GLOBAL_SelectList%%
							</select>&nbsp;%%LNG_HLP_MailingList%%
						</td>
					</tr>
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td>
							<input class="FormButton" type="submit" value="%%LNG_Next%%" />
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if (confirm("%%LNG_Subscribers_BannedManage_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Banned" }' />
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
		if (f.list.selectedIndex < 0) {
			alert("%%LNG_SelectList%%");
			f.list.focus();
			return false;
		}
		return true;
	}
</script>
