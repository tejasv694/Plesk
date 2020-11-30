<script src="includes/js/jquery/interface.js"></script>
<script src="includes/js/jquery/inestedsortable.js"></script>
<script>
Application.Page.ListsManage = {

	updatingSortables: false,
	updateTimeout: null,

	eventDOMReady: function(event) {
		var mode = '%%GLOBAL_Mode%%';
		if (mode == 'Folder') {
			Application.Ui.Folders.CreateSortableList('l');
		}
		Application.Ui.CheckboxSelection(	'table#SubscriberListManageList',
											'input.UICheckboxToggleSelector',
											'input.UICheckboxToggleRows');
	}
}

Application.init.push(Application.Page.ListsManage.eventDOMReady);

</script>
<table cellspacing="0" cellpadding="0" align="center" width="100%">
	<tr>
		<td class="Heading1">%%LNG_ListsManage%%</td>
	</tr>
	<tr>
		<td class="Intro pageinfo"><p>%%LNG_Help_ListsManage%%</p></td>
	</tr>
	<tr>
		<td>
			%%GLOBAL_Message%%
		</td>
	</tr>
	<tr>
		<td class="body">
			<span class="body">%%GLOBAL_ListsReport%%</span>
			<form name="ActionListsForm" method="post" action="index.php?Page=Lists&Action=Change" onsubmit="return ConfirmChanges();" style="margin: 0px;padding: 0px;">
				<table width="100%" border="0">
					<tr>
						<td valign="bottom">
							%%GLOBAL_Lists_AddButton%%
							<br />
							<select name="ChangeType">
								<option value="" SELECTED>%%LNG_ChooseAction%%</option>
								%%GLOBAL_Option_DeleteList%%
								%%GLOBAL_Option_DeleteSubscribers%%
								<option value="ChangeFormat_Text">%%LNG_ChangeFormat_Text%%</option>
								<option value="ChangeFormat_HTML">%%LNG_ChangeFormat_HTML%%</option>
								<option value="ChangeStatus_Confirm">%%LNG_ChangeStatus_Confirm%%</option>
								<option value="ChangeStatus_Unconfirm">%%LNG_ChangeStatus_Unconfirm%%</option>
								<option value="MergeLists">%%LNG_MergeLists%%</option>
							</select>
							<input type="submit" name="cmdChangeType" value="%%LNG_Go%%" class="Text">
							{template="folder_viewpicker"}
						</td>
						<td align="right" valign="bottom">
							%%TPL_Paging%%
						</td>
					</tr>
				</table>

				<table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text" id="SubscriberListManageList">
					<tr class="Heading3">
						<td width="28" nowrap align="center">
							<input type="checkbox" name="toggle" class="UICheckboxToggleSelector">
						</td>
						<td width="44" nowrap="nowrap"><img src="images/blank.gif" width="44" height="1" /></td>
						<td width="*" nowrap="nowrap">
							%%LNG_ListName%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Name&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Name&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border=0></a>
						</td>
						<td width="120" nowrap="nowrap">
							%%LNG_Created%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Date&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Date&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border=0></a>
						</td>
						<td width="120" nowrap="nowrap">
							%%LNG_Subscribers%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Subscribers&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Subscribers&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border=0></a>
						</td>
						<td width="120" nowrap="nowrap">
							%%LNG_ListOwner%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=fullname&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=fullname&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border=0></a>
						</td>
						<td width="40" nowrap="nowrap" align="center">
							%%LNG_ArchiveLists%%
						</td>
						<td width="240" nowrap="nowrap">
							%%LNG_Action%%
						</td>
					</tr>
				</table>
				<div id="PlaceholderParent" style="margin:0; padding:0;">
					<ul id="PlaceholderSortable" class="SortableList Folder">
						%%TPL_Lists_Manage_Row%%
					</ul>
				</div>
			</form>

			%%TPL_Paging_Bottom%%

		</td>
	</tr>
</table>

<script>

	function closePopup() {
		tb_remove();
	}

	function ConfirmDelete(ListID) {
		if (!ListID) {
			return false;
		}
		if (confirm("%%LNG_DeleteListPrompt%%")) {
			document.location='index.php?Page=%%PAGE%%&Action=Delete&id=' + ListID;
			return true;
		}
	}

	function ConfirmDeleteAllSubscribers(ListID) {
		if (!ListID) {
			return false;
		}
		if (confirm("%%LNG_DeleteAllSubscribersPrompt%%")) {
			document.location='index.php?Page=%%PAGE%%&Action=DeleteAllSubscribers&id=' + ListID;
			return true;
		}
	}
	
	function ConfirmChanges() {
		formObj = document.ActionListsForm;

		if (formObj.ChangeType.selectedIndex == 0) {
			alert("%%LNG_PleaseChooseAction%%");
			formObj.ChangeType.focus();
			return false;
		}

		selectedValue = formObj.ChangeType[formObj.ChangeType.selectedIndex].value;

		lists_found = 0;
		for (var i=0;i < formObj.length;i++)
		{
			fldObj = formObj.elements[i];
			if (fldObj.type == 'checkbox')
			{
				if (fldObj.checked) {
					lists_found++;
					// check for more than 2 lists found already
					// as merging lists together requires more than one being selected.
					if (lists_found > 2) {
						break;
					}
				}
			}
		}

		if (lists_found <= 0) {
			alert("%%LNG_ChooseList%%");
			return false;
		}

		if (selectedValue == 'MergeLists') {
			if (lists_found < 2) {
				alert("%%LNG_ChooseMultipleLists%%");
				return false;
			}
		}

		if (confirm("%%LNG_ConfirmChanges%%")) {
			return true;
		}

		return false;
	}

</script>
