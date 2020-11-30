<form method="post" action="index.php?Page=Newsletters&Action=%%GLOBAL_Action%%" onsubmit="return CheckForm()">
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
				%%GLOBAL_Message%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_Next%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Newsletters" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="3" class="Heading2">
							&nbsp;&nbsp;%%GLOBAL_NewsletterDetails%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_NewsletterName%%:
						</td>
						<td width="300">
							<input type="text" name="Name" class="Field250" value="%%GLOBAL_Name%%">&nbsp;%%LNG_HLP_NewsletterName%%
						</td>
						<td rowspan="3" valign="top">
							<div class="DisplayTemplateList" style="display: block; width: 255px; text-align: center; display: %%GLOBAL_DisplayTemplateList%%">
								<div><a href="javascript:void(0);" onclick="javascript:ShowPreview();"><img id="imgPreview" src="images/nopreview.gif" width="247" height="200" style="border: 1px solid black" onerror="this.src=images/nopreview.gif"></a></div>
								<div style="padding-top: 10px;"><a href="#" onclick="javascript: ShowPreview(); return false;"><img src="images/magnify.gif" border="0" style="padding-right:5px">%%LNG_Preview_Template%%</a></div>
							</div>
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_NewsletterFormat%%:
						</td>
						<td>
							<select name="Format" id="Format">
								%%GLOBAL_FormatList%%
							</select>
							&nbsp;
							%%LNG_HLP_NewsletterFormat%%
						</td>
					</tr>
					<tr class="DisplayTemplateList" style="display: %%GLOBAL_DisplayTemplateList%%">
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
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td>
							<input class="FormButton" type="submit" value="%%LNG_Next%%" />
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if (confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Newsletters" }' />
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
			alert("%%LNG_EnterNewsletterName%%");
			f.Name.focus();
			return false;
		}
	}

	$('#Format').change(function() {
		// Hide the template preview if a text campaign was selected
		if($(this).val() == 't') {
			$('.DisplayTemplateList').hide();
		}
		else {
			$('.DisplayTemplateList').show();
		}
	});

</script>
