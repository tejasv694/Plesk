<form method="post" action="index.php?Page=Autoresponders&Action=%%GLOBAL_Action%%" onsubmit="return CheckForm()">
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
				%%GLOBAL_CronWarning%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_Next%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Autoresponders&Action=Step2&list=%%GLOBAL_List%%" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr %%GLOBAL_FilterOptions%%>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_AutoresponderDetails%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_AutoresponderName%%:&nbsp;
						</td>
						<td>
							<input type="text" name="name" class="Field250" value="%%GLOBAL_Name%%">&nbsp;%%LNG_HLP_AutoresponderName%%
							<div class="aside">%%LNG_Autoresponder_Name_Reference%%</div>
						</td>
					</tr>
				</table>
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel" %%GLOBAL_FilterOptions%%>
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_FilterOptions_Autoresponders%%
						</td>
					</tr>
					<tr %%GLOBAL_FilterOptions%%>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_ShowFilteringOptions_Autoresponders%%:&nbsp;
						</td>
						<td>
							<table width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td width="260px;">
										<label for="DoNotShowFilteringOptions"><input type="radio" name="ShowFilteringOptions" id="DoNotShowFilteringOptions" value="2" %%GLOBAL_DoNotShowFilteringOptions%% onclick="document.getElementById('FilteringOptions').style.display = 'none';">%%LNG_AutorespondersDoNotShowFilteringOptionsOneListExplain%%</label>
								</tr>
								<tr>
									<td>
										<label for="ShowFilteringOptions"><input type="radio" name="ShowFilteringOptions" id="ShowFilteringOptions" value="1" %%GLOBAL_ShowFilteringOptions%% onclick="document.getElementById('FilteringOptions').style.display = '';">%%LNG_AutorespondersShowFilteringOptionsOneListExplain%%</label>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>

				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel" id="FilteringOptions" %%GLOBAL_FilteringOptions_Display%%>
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_AutoresponderFilterDetails%%
						</td>
					</tr>
					<Tr>
						<td colspan="2">
							<br />
							<div style='background-color:#FFF1A8; padding:5px 5px 8px 10px; margin-bottom:10px'>
								%%LNG_Autoresponder_Filter_Help%%
							</div>
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_MatchEmail%%:&nbsp;
						</td>
						<td>
							<input type="text" name="emailaddress" value="%%GLOBAL_emailaddress%%" class="Field250">&nbsp;%%LNG_HLP_MatchEmail%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_MatchFormat%%:&nbsp;
						</td>
						<td>
							<select name="format" class="Field250">
								%%GLOBAL_FormatList%%&nbsp;
							</select>&nbsp;%%LNG_HLP_MatchFormat%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_MatchConfirmedStatus%%:&nbsp;
						</td>
						<td>
							<select name="confirmed" class="Field250">
								%%GLOBAL_ConfirmList%%
							</select>&nbsp;%%LNG_HLP_MatchConfirmedStatus%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_Autoresponder_ClickedOnLink%%:
						</td>
						<td>
							<label for="clickedlink"><input type="checkbox" name="clickedlink" id="clickedlink" value="1" %%GLOBAL_clickedlink%% onClick="javascript: enable_ClickedLink(this, 'clicklink', 'linkid', '%%LNG_LoadingMessage%%')">&nbsp;%%LNG_Autoresponder_YesFilterByLink%%</label>
							&nbsp;%%LNG_HLP_Autoresponder_ClickedOnLink%%
							<br/>
							<div id="clicklink" style="display: %%GLOBAL_clickedlinkdisplay%%">
								<table border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td valign="middle">
											<img src="images/nodejoin.gif" width="20" height="20" border="0">
										</td>
										<td colspan="2">
											<select name="linktype" style="width: 120px;">
												<option value="clicked"%%GLOBAL_LinkType_Clicked%%>%%LNG_Search_HaveClicked%%</option>
												<option value="not_clicked"%%GLOBAL_LinkType_NotClicked%%>%%LNG_Search_HaveNotClicked%%</option>
											</select>
										</td>
									</tr>
									<tr>
										<td valign="middle">
											&nbsp;
										</td>
										<td valign="middle">
											<img src="images/nodejoin.gif" width="20" height="20" border="0">
										</td>
										<td>
											<select name="linkid" id="linkid"%%GLOBAL_LinkChange%%>
												%%GLOBAL_ClickedLinkOptions%%
											</select>
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_Autoresponder_OpenedNewsletter%%:
						</td>
						<td>
							<label for="openednewsletter"><input type="checkbox" name="openednewsletter" id="openednewsletter" value="1" %%GLOBAL_openednewsletter%% onClick="javascript: enable_OpenedNewsletter(this, 'opennews', 'newsletterid', '%%LNG_LoadingMessage%%')">&nbsp;%%LNG_Autoresponder_YesFilterByOpenedNewsletter%%</label>
							&nbsp;%%LNG_HLP_Autoresponder_OpenedNewsletter%%
							<br/>
							<div id="opennews" style="display: %%GLOBAL_openednewsletterdisplay%%">
								<table border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td valign="middle">
											<img src="images/nodejoin.gif" width="20" height="20" border="0">
										</td>
										<td colspan="2">
											<select name="opentype" style="width: 120px;">
												<option value="opened"%%GLOBAL_NewsletterType_Opened%%>%%LNG_Search_HaveOpened%%</option>
												<option value="not_opened"%%GLOBAL_NewsletterType_NotOpened%%>%%LNG_Search_HaveNotOpened%%</option>
											</select>
										</td>
									</tr>
									<tr>
										<td valign="middle">
											&nbsp;
										</td>
										<td valign="middle">
											<img src="images/nodejoin.gif" width="20" height="20" border="0">
										</td>
										<td>
											<select name="newsletterid" id="newsletterid"%%GLOBAL_NewsletterChange%%>
												%%GLOBAL_OpenedNewsletterOptions%%
											</select>
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
					%%GLOBAL_CustomFieldInfo%%
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td>
							<input class="FormButton" type="submit" value="%%LNG_Next%%" />
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if (confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Autoresponders&Action=Step2&list=%%GLOBAL_List%%" }' />
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
		if (f.name.value == '') {
			alert("%%LNG_EnterAutoresponderName%%");
			f.name.focus();
			return false;
		}
	}
</script>
