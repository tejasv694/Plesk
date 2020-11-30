<form method="post" action="index.php?Page=CustomFields&Action=%%GLOBAL_Action%%" onsubmit="return CheckForm()">
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
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_CreateCustomField_CancelPrompt%%")) { document.location="index.php?Page=CustomFields" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_CustomFieldDetails%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_CustomFieldType%%:&nbsp;
						</td>
						<td>
							%%GLOBAL_FieldTypeList%%
							<!-- Custom Help so we can make it show higher -->
							<div id="cfHelp" style="display:none">
								<img onMouseOut="HideHelp('sscVAaNTpt');" onMouseOver="ShowHelp('sscVAaNTpt', '%%LNG_CustomFieldType%%', '%%LNG_CustomFieldTypeHelp%%');" src="images/help.gif" width="24" height="16" border="0"><div style="display:none; top: 180px;" id="sscVAaNTpt"></div>
							</div>
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_CustomFieldName%%:&nbsp;
						</td>
						<td>
							<input type="text" name="FieldName" id="FieldName" class="Field250" value="%%GLOBAL_FieldName%%">&nbsp;%%LNG_HLP_CustomFieldName%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_CustomFieldRequired%%&nbsp;
						</td>
						<td>
							<label for="FieldRequired"><input type="checkbox" id="FieldRequired" name="FieldRequired"%%GLOBAL_FieldRequired%%>%%LNG_CustomFieldRequiredExplain%%</label>

							%%LNG_HLP_CustomFieldRequired%%
						</td>
					</tr>
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

	// Check the radio button custom fields (Added by Mitch during IEM5 alpha testing)
	var cf_selected = false;

	function CheckForm() {
		if ($('#FieldName').val() == '') {
			alert("%%LNG_EnterCustomFieldName%%");
			$('#FieldName').focus();
			return false;
		}

		if (!cf_selected) {
			alert("%%LNG_SelectCustomFieldType%%");
			$('#FieldName').focus();
			return false;
		}
	}

	$(document).ready(function() {
		document.getElementById('cfCustomHelp').innerHTML = document.getElementById('cfHelp').innerHTML;
		document.getElementById('cfHelp').innerHTML = '';
	});

</script>
