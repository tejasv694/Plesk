<form method="get" action="index.php" onsubmit="return CheckForm();">
	<input type="hidden" name="Page" value="Autoresponders" />
	<input type="hidden" name="Action" value="Step2" />
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Autoresponder_Step1%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Autoresponder_Step1_Intro%%
				</p>
			</td>
		</tr>
		<tr>
			<td>
				%%GLOBAL_CronWarning%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_Next%%" />
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if (confirm("%%LNG_Autoresponder_Step1_CancelPrompt%%")) { window.location.href="index.php?Page=Autoresponders" }' />
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_MailingListDetails%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_MailingList%%:&nbsp;
						</td>
						<td>
							<select name="list" style="width: 450px;" size="15" onDblClick="CheckForm() && this.form.submit()">
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
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if (confirm("%%GLOBAL_CancelButton%%")) { window.location.href="index.php?Page=Autoresponders" }' />
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
			alert("%%LNG_SelectOneList%%");
			f.list.focus();
			return false;
		}
		return true;
	}
</script>
