<form method="post" action="index.php?Page=CustomFields&Action=Associate" onsubmit="return CheckForm();">
<input type="hidden" name="fieldid" value="%%GLOBAL_fieldid%%">
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
				<input class="FormButton" type="submit" value="%%LNG_Save%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=CustomFields" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_AssociateCustomField%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}%%LNG_MailingLists%%:&nbsp;
						</td>
						<td>
							<select name="listid[]" multiple="multiple" class="ISSelectReplacement ISSelectSearch">
								%%GLOBAL_ListAssociations%%
							</select>
						</td>
					</tr>
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td>
							<input class="FormButton" type="submit" value="%%LNG_Save%%" />
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if (confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=CustomFields" }' />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<script>
	function CheckForm() {
		if (!CountLists()) {
			alert("%%LNG_ChooseCustomFieldLists%%");
			return false;
		}
		return true;
	}

	function CountLists() {
		for (i = 0; i < $('select')[0].options.length; i++) {
			if ($('select')[0].options[i].selected) { return true; }
		}
		
		return false;
	}

</script>