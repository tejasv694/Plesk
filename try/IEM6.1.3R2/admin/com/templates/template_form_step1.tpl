<form method="post" action="index.php?Page=Templates&Action=%%GLOBAL_Action%%" onsubmit="return CheckForm()">
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
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Templates" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="3" class="Heading2">
							&nbsp;&nbsp;%%GLOBAL_TemplateDetails%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_TemplateName%%:&nbsp;
						</td>
						<td width="300">
							<input type="text" name="Name" class="Field250" value="%%GLOBAL_Name%%">&nbsp;%%LNG_HLP_TemplateName%%
						</td>
						<td rowspan="3" valign="top">
							<div style="display: block; width: 255px; text-align: center; display: %%GLOBAL_DisplayTemplateList%%">
								<div><a href="javascript:void(0);" onclick="javascript:ShowPreview();"><img id="imgPreview" src="images/nopreview.gif" width="247" height="200" style="border: 1px solid black" onerror="this.src=images/nopreview.gif"></a></div>
								<div style="padding-top: 10px;"><a href="#" onclick="javascript: ShowPreview(); return false;"><img src="images/magnify.gif" border="0" style="padding-right:5px">%%LNG_Preview_Template%%</a></div>
							</div>
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_TemplateFormat%%:&nbsp;
						</td>
						<td>
							<select name="Format">
								%%GLOBAL_FormatList%%
							</select>
							&nbsp;
							%%LNG_HLP_TemplateFormat%%
						</td>
					</tr>
					<tr style="display: %%GLOBAL_DisplayTemplateList%%">
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_ChooseTemplate%%:
						</td>
						<td>
							%%GLOBAL_TemplateList%%
							&nbsp;
							%%LNG_HLP_ChooseTemplate%%
						</td>
					</tr>
					<tr>
						<td>
							&nbsp;
						</td>
						<td>
							<input class="FormButton" type="submit" value="%%LNG_Next%%">
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Templates" }'>
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
		if (f.Name.value == '') {
			alert("%%LNG_EnterTemplateName%%");
			f.Name.focus();
			return false;
		}
	}
</script>
