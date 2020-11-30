<form method="post" action="index.php?Page=Forms&Action=%%GLOBAL_Action%%" onsubmit="return CheckForm();">
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
			</td>
		</tr>
		<tr>
			<td>
				%%GLOBAL_Message%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_Next%%">&nbsp;
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Forms" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					%%GLOBAL_FormConfirmPage%%
					%%GLOBAL_FormConfirmEmail%%

					%%GLOBAL_FormThanksPageHTML%%
					%%GLOBAL_FormThanksPage%%
					%%GLOBAL_FormThanksEmail%%

					%%GLOBAL_FormSendFriendPage%%
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td valign="top" height="30">
							<input type="submit" value="%%LNG_Next%%" class="FormButton" />
							<input type="button" value="%%LNG_Cancel%%" class="FormButton CancelButton" onClick='if (confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Forms" }' />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<script>

	var f = document.forms[0];

	function CheckForm() {
		// this page doesn't know what options will be available.
		// if the object is null, then it's not loaded (ie not an option for this form type)
		// so we can quickly check what's set and what isn't to make sure we're not going to create
		// unnecessary alerts.
		if (f.sendfromname != null) {
			if (f.sendfromname.value == "") {
				alert("%%LNG_EnterSendFromName%%");
				f.sendfromname.focus();
				return false;
			}

			if (f.sendfromemail.value == "") {
				alert("%%LNG_EnterSendFromEmail%%");
				f.sendfromemail.focus();
				return false;
			}

			if (f.replytoemail.value == "") {
				alert("%%LNG_EnterReplyToEmail%%");
				f.replytoemail.focus();
				return false;
			}

			if (f.bounceemail.value == "") {
				alert("%%LNG_EnterBouceEmail%%");
				f.bounceemail.focus();
				return false;
			}
		}

		if (f.confirmsubject != null) {
			if (f.confirmsubject.value == "") {
				alert("%%LNG_EnterConfirmSubject%%");
				f.confirmsubject.focus();
				return false;
			}
		}

		if (f.thankssubject != null) {
			if (f.thankssubject.value == "") {
				alert("%%LNG_EnterThanksSubject%%");
				f.thankssubject.focus();
				return false;
			}
		}

		return true;
	}
</script>

