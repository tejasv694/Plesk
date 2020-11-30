<form method="post" action="index.php?Page=Subscribers&Action=%%GLOBAL_FormAction%%&SubAction=step3&Lists[]=%%GLOBAL_List%%">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%GLOBAL_Heading%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Subscribers_Export_Step2_Intro%%
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
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Manage_CancelPrompt%%")) { document.location="index.php?Page=Subscribers" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel" %%GLOBAL_FilterOptions%%>
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_FilterOptions_Subscribers%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_ShowFilteringOptionsLabel_Export%%&nbsp;
						</td>
						<td>
							<table width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td colspan="2">
										<label for="DoNotShowFilteringOptions"><input type="radio" name="ShowFilteringOptions" id="DoNotShowFilteringOptions" value="2" %%GLOBAL_DoNotShowFilteringOptions%% onclick="document.getElementById('FilteringOptions').style.display = 'none'; document.getElementById('filter_next').style.display = '';">%%GLOBAL_DoNotShowFilteringOptionLabel%%</label>
										%%LNG_HLP_ShowFilteringOptions%%
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<label for="ShowFilteringOptions"><input type="radio" name="ShowFilteringOptions" id="ShowFilteringOptions" value="1" %%GLOBAL_ShowFilteringOptions%% onclick="document.getElementById('FilteringOptions').style.display = ''; document.getElementById('filter_next').style.display = 'none';">%%GLOBAL_ShowFilteringOptionLabel%%</label>
									</td>
								</tr>
							</table>
						</td>

					</tr>
					<tr id="filter_next" style="%%GLOBAL_FilterNext_Display%%">
						<td>
							&nbsp;
						</td>
						<td height="35">
							<input class="FormButton" type="submit" value="%%LNG_Next%%">
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Manage_CancelPrompt%%")) { document.location="index.php?Page=Subscribers" }'>
						</td>
					</tr>
				</table>
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel" id="FilteringOptions" %%GLOBAL_FilteringOptions_Display%%>
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_FilterSearch%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_Email%%:&nbsp;
						</td>
						<td>
							<input type="text" name="emailaddress" value="" class="Field250">&nbsp;%%LNG_HLP_FilterEmailAddress%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_Format%%:&nbsp;
						</td>
						<td>
							<select name="format" class="Field250">
								%%GLOBAL_FormatList%%
							</select>&nbsp;%%LNG_HLP_FilterFormat%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_ConfirmedStatus%%:&nbsp;
						</td>
						<td>
							<select name="confirmed" class="Field250">
								<option value="-1" SELECTED>%%LNG_Either_Confirmed%%</option>
								<option value="1">%%LNG_Confirmed%%</option>
								<option value="0">%%LNG_Unconfirmed%%</option>
							</select>&nbsp;%%LNG_HLP_FilterConfirmedStatus%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_Status%%:&nbsp;
						</td>
						<td>
							<select name="status" class="Field250">
								<option value="-1">%%LNG_AllStatus%%</option>
								<option value="a" SELECTED>%%LNG_Active%%</option>
								<option value="b">%%LNG_Bounced%%</option>
								<option value="u">%%LNG_Unsubscribed%%</option>
							</select>&nbsp;%%LNG_HLP_FilterStatus%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_FilterByDate%%:
						</td>
						<td>
							<label for="datesearch[filter]"><input type="checkbox" name="datesearch[filter]" id="datesearch[filter]" value="1"%%GLOBAL_FilterChecked%% onClick="javascript: enableDate_SubscribeDate(this, 'datesearch')">&nbsp;%%LNG_YesFilterByDate%%</label>
							&nbsp;%%LNG_HLP_FilterByDate%%
							<br/>
							<div id="datesearch" style="display: none">
								<table border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td valign="middle">
											<img src="images/nodejoin.gif" width="20" height="20" border="0">
										</td>
										<td>
											<select class="datefield" name="datesearch[type]" onChange="javascript: ChangeFilterOptionsSubscribeDate(this, 'datesearch');">
												<option value="after">%%LNG_After%%</option>
												<option value="before">%%LNG_Before%%</option>
												<option value="exactly">%%LNG_Exactly%%</option>
												<option value="between">%%LNG_Between%%</option>
											</select>
										</td>
										<td valign="top">
											%%GLOBAL_Display_subdate_date1_Field1%%
											%%GLOBAL_Display_subdate_date1_Field2%%
											%%GLOBAL_Display_subdate_date1_Field3%%
										</td>
									</tr>
									<tr style="display: none" id="datesearchdate2">
										<td colspan="2" align="right" valign="middle">
											<img src="images/nodejoin.gif" width="20" height="20" border="0">&nbsp;%%LNG_AND%%&nbsp;
										</td>
										<td valign="top">
											%%GLOBAL_Display_subdate_date2_Field1%%
											%%GLOBAL_Display_subdate_date2_Field2%%
											%%GLOBAL_Display_subdate_date2_Field3%%
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_ClickedOnLink%%:
						</td>
						<td>
							<label for="clickedlink"><input type="checkbox" name="clickedlink" id="clickedlink" value="1" onClick="javascript: enable_ClickedLink(this, 'clicklink', 'linkid', '%%LNG_LoadingMessage%%')">&nbsp;%%LNG_YesFilterByLink%%</label>
							&nbsp;%%LNG_HLP_ClickedOnLink%%
							<br/>
							%%GLOBAL_ClickedLinkOptions%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_OpenedNewsletter%%:
						</td>
						<td>
							<label for="openednewsletter"><input type="checkbox" name="openednewsletter" id="openednewsletter" value="1" onClick="javascript: enable_OpenedNewsletter(this, 'opennews', 'newsletterid', '%%LNG_LoadingMessage%%')">&nbsp;%%LNG_YesFilterByOpenedNewsletter%%</label>
							&nbsp;%%LNG_HLP_OpenedNewsletter%%
							<br/>
							%%GLOBAL_OpenedNewsletterOptions%%
						</td>
					</tr>
					%%GLOBAL_CustomFieldInfo%%
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel">&nbsp;</td>
						<td valign="top" height="30">
							<input class="FormButton" type="submit" value="%%LNG_Next%%">
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Manage_CancelPrompt%%")) { document.location="index.php?Page=Subscribers" }'>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

