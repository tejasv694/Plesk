<form method="post" action="index.php?Page=Subscribers&Action=Export&SubAction=Step2" onsubmit="return CheckForm();">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Subscribers_Export%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Subscribers_Export_Intro%%
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_Next%%" />
				<input class="FormButton cancel" type="button" value="%%LNG_Cancel%%" />
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel" %%GLOBAL_FilterOptions%%>
					<tr %%GLOBAL_FilterOptions%%>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_FilterOptions_Subscribers%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_ShowFilteringOptionsLabel%%
						</td>
						<td>
							<table width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td>
										<input type="checkbox" name="ShowFilteringOptions" id="ShowFilteringOptions" value="1" />
										<label for="ShowFilteringOptions">%%LNG_ShowFilteringOptionsExplanation%%</label>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel" id="FilteringOptions" %%GLOBAL_FilteringOptions_Display%%>
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_MailingListDetails%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_MailingList%%:&nbsp;
						</td>
						<td>
							<select id="lists" name="lists[]" multiple="multiple" class="ISSelectReplacement ISSelectSearch" onDblClick="this.form.submit()">
								%%GLOBAL_SelectList%%
							</select>
							%%LNG_HLP_MailingList%%
						</td>
					</tr>
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel">&nbsp;</td>
						<td valign="top" height="30">
							<input class="FormButton" type="submit" value="%%LNG_Next%%" />
							<input class="FormButton cancel" type="button" value="%%LNG_Cancel%%" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
<script type="text/javascript">

	function CheckForm() {
		var f = document.forms[0];
		var listbox = document.getElementById('lists');
		if (listbox.selectedIndex < 0) {
			alert("%%LNG_SelectList%%");
			listbox.focus();
			return false;
		}
		return true;
	}

	/*
	 * This code is duplicated on all steps. I know, bad practice, but it's
	 * at least better than what was there before.
	 */
	jQuery(function($) {

		$('.cancel').bind('click', function() {
			if (confirm('%%LNG_Subscribers_Export_CancelPrompt%%')) {
				document.location = 'index.php?Page=Subscribers';
			}
		});
		
	});
	
</script>