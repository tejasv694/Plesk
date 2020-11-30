<script>
	Application.Page.NewslettersManage = {
		eventDOMReady: function(event) {
			Application.Ui.CheckboxSelection(	'table#NewsletterManageList',
												'input.UICheckboxToggleSelector',
												'input.UICheckboxToggleRows');
		}
	}

	Application.init.push(Application.Page.NewslettersManage.eventDOMReady);
</script>
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">%%LNG_NewslettersManage%%</td>
	</tr>
	<tr>
		<td class="body pageinfo"><p>%%LNG_Help_NewslettersManage%%%%GLOBAL_Newsletters_HasAccess%%</p></td>
	</tr>
	<tr>
		<td>
			%%GLOBAL_Message%%
		</td>
	</tr>
	<tr>
		<td class="body">
			<table width="100%" border="0">
				<tr>
					<td>
						<div style="padding-top:10px; padding-bottom:10px">
							%%GLOBAL_Newsletters_AddButton%%
							%%GLOBAL_Newsletters_ExtraButtons%%
						</div>
						<form name="ActionNewslettersForm" method="post" action="index.php?Page=Newsletters&Action=Change" onsubmit="return ConfirmChanges();" style="margin: 0px; padding: 0px;">
							<select name="ChangeType">
								<option value="" selected="selected">%%LNG_ChooseAction%%</option>
								%%GLOBAL_Option_DeleteNewsletter%%
								%%GLOBAL_Option_ArchiveNewsletter%%
								%%GLOBAL_Option_ActivateNewsletter%%
							</select>
							<input type="submit" name="cmdChangeType" value="%%LNG_Go%%" class="Text">
					</td>
					<td align="right" valign="bottom">
						%%TPL_Paging%%
					</td>
				</tr>
			</table>
			<table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text" id="NewsletterManageList">
				<tr class="Heading3">
					<td width="5" nowrap align="center">
						<input type="checkbox" name="toggle" class="UICheckboxToggleSelector" />
					</td>
					<td width="5">&nbsp;</td>
					<td width="25%" nowrap="nowrap">
						%%LNG_Name%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Name&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Name&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border="0" /></a>
					</td>
					<td width="30%" nowrap="nowrap">
						%%LNG_Subject%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Subject&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Subject&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border="0" /></a>
					</td>
					<td width="14%" nowrap="nowrap">
						%%LNG_Created%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Date&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Date&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border="0" /></a>
					</td>
					<td width="10%" nowrap="nowrap">
						%%LNG_LastSent%%
					</td>
					<td width="11%" nowrap="nowrap">
						%%LNG_Owner%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Owner&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Owner&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border="0" /></a>
					</td>
					<td width="5%">
						<span class="HelpText" onMouseOut="HideHelp('active');" onMouseOver="ShowQuickHelp('active', '%%LNG_Active%%', '%%LNG_ActiveEmailCampaignHelp%%');">%%LNG_Active%%</span><br /><div style="font-weight: normal" id="active" style="display: none;"></div>
					</td>
					<td width="5%">
						<span class="HelpText" onMouseOut="HideHelp('archive');" onMouseOver="ShowQuickHelp('archive', '%%LNG_Archive%%', '%%LNG_ArchiveHelp%%');">%%LNG_Archive%%</span><br /><div style="font-weight: normal" id="archive" style="display: none;"></div>
					</td>
					<td width="150">
						%%LNG_Action%%
					</td>
				</tr>
				%%TPL_Newsletters_Manage_Row%%
			</table>
			%%TPL_Paging_Bottom%%
		</td>
	</tr>
</table>

<script>
	function ConfirmChanges() {
		formObj = document.ActionNewslettersForm;

		if (formObj.ChangeType.selectedIndex == 0) {
			alert("%%LNG_PleaseChooseAction%%");
			formObj.ChangeType.focus();
			return false;
		}

		selectedValue = formObj.ChangeType[formObj.ChangeType.selectedIndex].value;

		newsletters_found = 0;
		for (var i=0;i < formObj.length;i++)
		{
			fldObj = formObj.elements[i];
			if (fldObj.type == 'checkbox')
			{
				if (fldObj.checked) {
					newsletters_found++;
					break;
				}
			}
		}

		if (newsletters_found <= 0) {
			alert("%%LNG_ChooseNewsletters%%");
			return false;
		}

		if (confirm("%%LNG_ConfirmChanges%%")) {
			return true;
		}

		return false;
	}

	function ConfirmDelete(NewsletterID) {
		if (!NewsletterID) {
			return false;
		}
		if (confirm("%%LNG_DeleteNewsletterPrompt%%")) {
			document.location='index.php?Page=%%PAGE%%&Action=Delete&id=' + NewsletterID;
			return true;
		}
	}
</script>
