<form method="post" action="index.php?Page=Subscribers&Action=Search&SubAction=step2">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Subscribers_AdvancedSearch%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Subscribers_Search_Intro%%
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_Step2%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Search_CancelPrompt%%")) { document.location="index.php?Page=Subscribers" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_FilterSearch%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_MailingList%%:&nbsp;
						</td>
						<td>
							<select name="list[]" multiple="multiple" class="ISSelectReplacement ISSelectSearch" onDblClick="this.form.submit()">
								%%GLOBAL_SelectList%%
							</select>&nbsp;%%LNG_HLP_Subscribers_Search_ListsMultiple%%
						</td>
					</tr>
					<tr>
						<td colspan="2" class="EmptyRow">
							&nbsp;
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_Email%%:&nbsp;
						</td>
						<td>
							<input type="text" name="emailaddress" value="" class="Field250">
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_Format%%:&nbsp;
						</td>
						<td>
							<select name="format" class="Field250">
								<option value="-1" selected>%%LNG_Either_Format%%</option>
								<option value="h">%%LNG_Format_HTML%%</option>
								<option value="t">%%LNG_Format_Text%%</option>
							</select>
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
							</select>
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
							</select>
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							%%LNG_FilterByDate%%
						</td>
						<td>
							<input type="checkbox" name="datesearch[filter]" id="datesearch[filter]" value="1"%%GLOBAL_FilterChecked%% onClick="javascript: enableDate_SubscribeDate(this, 'datesearch')">&nbsp;%%LNG_YesFilterByDate%%<br/>
							<div id="datesearch" style="display: none">
								<table border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td valign="middle">
											<img src="images/nodejoin.gif" width="20" height="20" border="0">
										</td>
										<td>
											<select class="datefield" name="datesearch[type]" onClick="javascript: ChangeFilterOptionsSubscribeDate(this, 'datesearch');">
												<option value="after">%%LNG_After%%</option>
												<option value="before">%%LNG_Before%%</option>
												<option value="exactly">%%LNG_Exactly%%</option>
												<option value="between">%%LNG_Between%%</option>
											</select>
										</td>
										<td valign="top">
											%%GLOBAL_Display_date1_Field1%%
											%%GLOBAL_Display_date1_Field2%%
											%%GLOBAL_Display_date1_Field3%%
										</td>
									</tr>
									<tr style="display: none" id="datesearchdate2">
										<td colspan="2" align="right" valign="middle">
											<img src="images/nodejoin.gif" width="20" height="20" border="0">&nbsp;%%LNG_AND%%&nbsp;
										</td>
										<td valign="top">
											%%GLOBAL_Display_date2_Field1%%
											%%GLOBAL_Display_date2_Field2%%
											%%GLOBAL_Display_date2_Field3%%
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel">&nbsp;</td>
						<td valign="top" height="30">
							<input class="FormButton" type="submit" value="%%LNG_Step2%%">
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Search_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Search&SubAction=step2" }'>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

