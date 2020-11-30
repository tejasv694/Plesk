<script>
	Application.Page.CustomfieldManage = {
		eventDOMReady: function(event) {
			Application.Ui.CheckboxSelection(	'table#CustomfieldManageList',
												'input.UICheckboxToggleSelector',
												'input.UICheckboxToggleRows');
		}
	};

	Application.init.push(Application.Page.CustomfieldManage.eventDOMReady);
</script>
<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">%%LNG_CustomFieldsManage%%</td>
	</tr>
	<tr>
		<td class="body pageinfo">
			<p>%%LNG_Help_CustomFieldsManage%% <a href="Javascript:LaunchHelp('%%WHITELABEL_INFOTIPS%%','810')">%%LNG_WhatAreCustomFields%%</a></p>
		</td>
	</tr>
	<tr>
		<td>
			%%GLOBAL_Message%%
		</td>
	</tr>
	<tr>
		<td class="body">
			<form name="formsform" method="post" action="index.php?Page=CustomFields&Action=Delete" onsubmit="return DeleteSelectedCustomFields(this);">
			<table width="100%" border="0">
				<tr>
					<td valign="top" valign="bottom">
						%%GLOBAL_CustomFields_AddButton%%
						%%GLOBAL_CustomFields_DeleteButton%%
					</td>
					<td align="right" valign="bottom">
						%%TPL_Paging%%
					</td>
				</tr>
			</table>
			<table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text" id="CustomfieldManageList">
				<tr class="Heading3">
					<td width="5" nowrap align="center">
						<input type="checkbox" name="toggle" class="UICheckboxToggleSelector" />
					</td>
					<td width="5">&nbsp;</td>
					<td width="55%">
						%%LNG_CustomFieldsName%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Name&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Name&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border=0></a>
					</td>
					<td width="15%">
						%%LNG_Created%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Date&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Date&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border=0></a>
					</td>
					<td width="15%">
						%%LNG_CustomFieldsType%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Type&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Type&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border=0></a>
					</td>
					<td width="20%">
						%%LNG_CustomFieldRequired1%%
					</td>
					<td width="100">
						%%LNG_Action%%
					</td>
				</tr>
				%%TPL_CustomFields_Manage_Row%%
			</table>
			%%TPL_Paging_Bottom%%
		</td>
	</tr>
</table>

<script>
	function DeleteSelectedCustomFields(formObj) {
		fields_found = 0;
		for (var i=0;i < formObj.length;i++)
		{
			fldObj = formObj.elements[i];
			if (fldObj.type == 'checkbox')
			{
				if (fldObj.checked) {
					fields_found++;
					break;
				}
			}
		}

		if (fields_found < 1) {
			alert("%%LNG_ChooseFieldsToDelete%%");
			return false;
		}

		if (confirm("%%LNG_ConfirmChanges%%")) {
			return true;
		}
		return false;
	}

function ConfirmDelete(FieldID) {
	if (!FieldID) {
		return false;
	}
	if (confirm("%%LNG_DeleteCustomFieldPrompt%%")) {
		document.location='index.php?Page=%%PAGE%%&Action=Delete&id=' + FieldID;
		return true;
	}
}

</script>
