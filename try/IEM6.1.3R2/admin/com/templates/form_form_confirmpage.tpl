<tr>
	<td colspan="2" class="Heading2">
		&nbsp;&nbsp;%%LNG_FormDisplayPageOptions%%
	</td>
</tr>
<tr>
	<td class="FieldLabel" width="200">
		{template="Required"}
		%%LNG_ConfirmPageHTML%%:&nbsp;
	</td>
	<td align="left">
		<script>

			$(document).ready(function() {
				// Select the WYSIWYG editor
				if('%%GLOBAL_ConfirmHTMLField%%' != '' || '%%GLOBAL_ConfirmUrlField%%' == '') {
					document.getElementById('htmlRadio').checked = true;
					$('#htmlEditor').show();
					$('#urlField').hide();
				}
				else {
					document.getElementById('urlRadio').checked = true;
					$('#urlField').show();
					$('#htmlEditor').hide();
					$('#confirmpageurl').focus();
				}

				$('#htmlRadio').click(function() {
					document.getElementById('htmlRadio').checked = true;
					$('#htmlEditor').show();
					$('#urlField').hide();
				});

				$('#urlRadio').click(function()  {
					document.getElementById('urlRadio').checked = true;
					$('#urlField').show();
					$('#htmlEditor').hide();
					$('#confirmpageurl').focus();
				});
			});

		</script>
		<table width="100%" border="0">
				<tr>
					<td width="20" valign="top">
						<input id="htmlRadio" name="useconfirmpageurl" value="0" type="radio" />
					</td>
					<td>
						<label for="htmlRadio">%%LNG_ShowContentBelow%%</label><br>
						<table border="0" cellspacing="0" cellpadding="0" width="100%" style="display:none; padding-top:10px" id="htmlEditor">
							<tr>
								<td valign="top">
									<img src="images/nodejoin.gif" />
								</td>
								<td>
									%%GLOBAL_ConfirmHTML%%
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td width="20">
						<input id="urlRadio" name="useconfirmpageurl" value="1" type="radio"%%GLOBAL_ConfirmUrlField%%>
					</td>
					<td>
						<label for="urlRadio">%%LNG_TakeSubscriberToAURL%%</label><br>
					</td>
				</tr>

				<tr id="urlField" style="display: %%GLOBAL_ConfirmUrlStyle%%">
					<td>&nbsp;</td>
					<td>
						<img src="images/nodejoin.gif" align="left" />&nbsp; <input type="text" name="confirmpageurl" id="confirmpageurl" value="%%GLOBAL_ConfirmPageURL%%" class="Field250">
					</td>
				</tr>
		</table>
	</td>
</tr>

<tr>
	<td colspan="2" class="EmptyRow">
		&nbsp;
	</td>
</tr>
<tr>
	<td colspan="2" class="Heading2">
		&nbsp;&nbsp;%%LNG_FormConfirmEmailOptions%%
	</td>
</tr>
{template="Form_EmailOptions"}
<tr>
	<td class="FieldLabel" width="200">
		{template="Required"}
		%%LNG_ConfirmSubject%%:&nbsp;
	</td>
	<td>
		<input type="text" class="Field250" name="confirmsubject" value="%%GLOBAL_ConfirmSubject%%">&nbsp;%%LNG_HLP_ConfirmSubject%%
	</td>
</tr>
<tr>
	<td class="FieldLabel" width="200">
		{template="Not_Required"}
		%%LNG_ConfirmHTMLVersion%%:&nbsp;
	</td>
	<td>
		%%GLOBAL_EditorHTML%%
	</td>
</tr>
<tr id="trGrab">
	<td>&nbsp;</td><td id="tdGrab"><input class="FormButton" type="button" name="" value="%%LNG_GetTextContent%%" style="width:200px" onClick="grabTextContent('TextContent','%%GLOBAL_HTMLEditorName%%');"></td>
</tr>
<tr>
	<td class="FieldLabel" width="200">
		{template="Not_Required"}
		%%LNG_ConfirmTextVersion%%:&nbsp;
	</td>
	<td align="left">
		%%GLOBAL_EditorText%%
	</td>
</tr>
