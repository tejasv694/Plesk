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
				<input class="FormButton" type="submit" value="%%LNG_Save%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Forms" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_FormErrorPageOptions%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel" width="200">
							{template="Not_Required"}
							%%LNG_ErrorPageHTML%%:&nbsp;
						</td>
						<td align="left">
							<table width="100%" border="0">
									<tr>
										<td width="20" valign="top">
											<input onClick="document.getElementById('errorurlField').style.display = 'none'; document.getElementById('errorhtmlField').style.display = '';" id="errorhtmlRadio" name="userrorhtmlurl" value="0" type="radio"%%GLOBAL_ErrorPageHTMLField%%>
										</td>
										<td>
											<label for="errorhtmlRadio">%%LNG_ShowContentBelow%%</label><br>
											<table border="0" cellspacing="0" cellpadding="0" width="100%" style="xdisplay:none; padding-top:10px" id="errorhtmlField">
												<tr>
													<td valign="top">
														<img src="images/nodejoin.gif" />
													</td>
													<td>
														%%GLOBAL_ErrorHTML%%
													</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr>
										<td width="20">
											<input onClick="document.getElementById('errorurlField').style.display = ''; document.getElementById('errorhtmlField').style.display = 'none'; $('#errorpageurl').focus();" id="errorurlRadio" name="userrorhtmlurl" value="1" type="radio"%%GLOBAL_ErrorPageUrlField%%>
										</td>
										<td>
											<label for="errorurlRadio">%%LNG_TakeSubscriberToAURL%%</label><br>
										</td>
									</tr>

									<tr id="errorurlField" style="display: %%GLOBAL_ErrorPageUrlStyle%%">
										<td>&nbsp;</td>
										<td>
											<img src="images/nodejoin.gif" />&nbsp;<input type="text" name="errorpageurl" id="errorpageurl" value="%%GLOBAL_ErrorPageURL%%" class="Field250">
										</td>
									</tr>
							</table>
						</td>
					</tr>
					%%GLOBAL_EditFormHTML%%
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td>
							<input class="FormButton" type="submit" value="%%LNG_Save%%" />
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if (confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Forms" }' />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<script>

	function CheckForm() {
		return true;
	}
</script>

