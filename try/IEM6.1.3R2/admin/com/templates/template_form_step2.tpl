<script src="includes/js/jquery/thickbox.js"></script>
<link rel="stylesheet" href="includes/styles/thickbox.css" type="text/css">
<link rel="stylesheet" type="text/css" href="includes/styles/tabs.css" />
<form method="post" action="index.php?Page=Templates&Action=%%GLOBAL_Action%%" onsubmit="return CheckForm()" enctype="multipart/form-data">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%GLOBAL_Heading%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				%%GLOBAL_Intro%%
				<br /><br />%%GLOBAL_Message%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="button" value="%%LNG_Save%%" onclick='Save();'>
				<input class="FormButton_wide" type="submit" value="%%LNG_SaveAndExit%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Templates" }'>
				<br />
				<br />
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_EditTemplateHeading%%
						</td>
					</tr>
					%%GLOBAL_Editor%%
					<tr>
						<td colspan="2" class="EmptyRow"></td>
					</tr>
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_EmailValidation%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel" width="150">
							{template="Not_Required"}
							%%LNG_EmailClientCompatibility%%:
						</td>
						<td>
							<input type="button" class="Field250" value="%%LNG_EmailClientCompatibility_Button%%" onclick="javascript: ViewCompatibility();">
						</td>
					</tr>
					<tr>
						<td colspan="2" class="EmptyRow"></td>
					</tr>
					<tr style="display: %%GLOBAL_ShowMiscOptions%%">
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_MiscellaneousOptions%%
						</td>
					</tr>
					<tr style="display: %%GLOBAL_ShowActive%%">
						<td class="FieldLabel" width="150">
							{template="Not_Required"}
							%%LNG_TemplateIsActive%%:
						</td>
						<td>
							<label for="active">
							<input type="checkbox" name="active" id="active" value="1"%%GLOBAL_IsActive%%>
							%%LNG_TemplateIsActiveExplain%%
							</label>
							%%LNG_HLP_TemplateIsActive%%
						</td>
					</tr>
					<tr style="display: %%GLOBAL_ShowGlobal%%">
						<td class="FieldLabel" width="150">
							{template="Not_Required"}
							%%LNG_TemplateIsGlobal%%:
						</td>
						<td>
							<label for="isglobal">
							<input type="checkbox" name="isglobal" id="isglobal" value="1" />
							%%LNG_TemplateIsGlobalExplain%%
							</label>
							%%LNG_HLP_TemplateIsGlobal%%
						</td>
					</tr>
					<tr>
						<td width="10%">
							<img src="images/blank.gif" width="220" height="1" />
						</td>
						<td height="35" width="90%">
							<input class="FormButton" type="button" value="%%LNG_Save%%" onclick='Save();'>
							<input class="FormButton_wide" type="submit" value="%%LNG_SaveAndExit%%">
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Templates" }'>
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
		return true;
	}

	function Save() {
		if (CheckForm()) {
			f.action = 'index.php?Page=Templates&Action=%%GLOBAL_SaveAction%%';
			f.submit();
		}
	}

	function ViewCompatibility() {
		f.target = "_blank";

		prevAction = f.action;
		f.action = "index.php?Page=Templates&Action=ViewCompatibility&ShowBroken=1";
		f.submit();

		f.target = "";
		f.action = prevAction;
	}

</script>
