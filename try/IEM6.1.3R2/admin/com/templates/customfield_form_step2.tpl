<form method="post" id="cfForm" action="index.php?Page=CustomFields&Action=%%GLOBAL_Action%%" onsubmit="return CheckForm()">
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
				<input class="FormButton" type="submit" value="%%LNG_Next%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=CustomFields" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel" id="customFieldsTable">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_CustomFieldDetails%%
						</td>
					</tr>
					%%GLOBAL_SubForm%%
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td>
							<input class="FormButton" type="submit" value="%%LNG_Next%%" />
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
		// This function is called more than once so it's in javascript.js
		return ValidateCustomFieldForm('%%LNG_CustomField_NoFieldName%%', '%%LNG_CustomField_NoDefaultValue%%', '%%LNG_CustomFields_NoMultiValues%%');
	}

</script>
