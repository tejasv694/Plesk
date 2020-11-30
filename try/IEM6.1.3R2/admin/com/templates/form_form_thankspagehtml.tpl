<tr>
	<td colspan="2" class="Heading2">
		&nbsp;&nbsp;%%LNG_FormThanksPageOptions%%
	</td>
</tr>
<tr>
	<td class="FieldLabel" width="200">
		{template="Not_Required"}
		%%LNG_ThanksPageHTML%%:&nbsp;
	</td>
	<td align="left">

		<script>

			$(document).ready(function() {
				// Select the WYSIWYG editor
				if('%%GLOBAL_ThanksPageHTMLField%%' != '' || '%%GLOBAL_ThanksPageURLField%%' == '') {
					document.getElementById('thankshtmlRadio').checked = true;
					$('#htmlEditor').show();
					$('#thanksurlField').hide();
				}
				else {
					document.getElementById('thanksurlRadio').checked = true;
					$('#thanksurlField').show();
					$('#htmlEditor').hide();
					$('#thankspageurl').focus();
				}

				$('#thankshtmlRadio').click(function() {
					document.getElementById('thankshtmlRadio').checked = true;
					$('#htmlEditor').show();
					$('#thanksurlField').hide();
				});

				$('#thanksurlRadio').click(function()  {
					document.getElementById('thanksurlRadio').checked = true;
					$('#thanksurlField').show();
					$('#htmlEditor').hide();
					$('#thankspageurl').focus();
				});
			});

		</script>

		<table width="100%" border="0">
				<tr>
					<td width="20" valign="top">
						<input id="thankshtmlRadio" name="usethankspageurl" value="0" type="radio">
					</td>
					<td>
						<label for="thankshtmlRadio">%%LNG_ShowContentBelow%%</label><br>
						<table border="0" cellspacing="0" cellpadding="0" width="100%" style="xdisplay:none; padding-top:10px" id="htmlEditor">
							<tr>
								<td valign="top">
									<img src="images/nodejoin.gif" />
								</td>
								<td>
									%%GLOBAL_ThanksPage_HTML%%
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td width="20">
						<input id="thanksurlRadio" name="usethankspageurl" value="1" type="radio">
					</td>
					<td>
						<label for="thanksurlRadio">%%LNG_TakeSubscriberToAURL%%</label><br>
					</td>
				</tr>

				<tr id="thanksurlField" style="display: %%GLOBAL_ThanksPageUrlStyle%%">
					<td>&nbsp;</td>
					<td>
						<img src="images/nodejoin.gif" align="left" />&nbsp; <input type="text" name="thankspageurl" id="thankspageurl" value="%%GLOBAL_ThanksPageURL%%" class="Field250">
					</td>
				</tr>
		</table>
	</td>
</tr>
