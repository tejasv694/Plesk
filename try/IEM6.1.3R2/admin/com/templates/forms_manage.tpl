<script>
	Application.Page.FormsManage = {
		eventDOMReady: function(event) {
			Application.Ui.CheckboxSelection(	'table#FormsManageList',
												'input.UICheckboxToggleSelector',
												'input.UICheckboxToggleRows');
		}
	};

	Application.init.push(Application.Page.FormsManage.eventDOMReady);
</script>
<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">%%LNG_FormsManage%%</td>
	</tr>
	<tr>
		<td class="body pageinfo">
			<p>%%LNG_Help_FormsManage%%</p>
		</td>
	</tr>
	<tr>
		<td>%%GLOBAL_Message%%</td>
	</tr>
	<tr>
		<td class="body">
			<form name="formsform" method="post" action="index.php?Page=Forms&Action=Delete" onsubmit="return DeleteSelectedForms(this);">
			<table width="100%" border="0">
				<tr>
					<td valign="top">
						%%GLOBAL_Forms_AddButton%%
						%%GLOBAL_Forms_DeleteButton%%
					</td>
					<td align="right">
						%%TPL_Paging%%
					</td>
				</tr>
			</table>
			<table border="0" cellspacing="0" cellpadding="0" width=100% class="Text" id="FormsManageList">
				<tr class="Heading3">
					<td width="5" nowrap align="center">
						<input type="checkbox" name="toggle" class="UICheckboxToggleSelector">
					</td>
					<td width="5">&nbsp;</td>
					<td width="40%">
						%%LNG_FormName%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Name&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Name&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border="0" /></a>
					</td>
					<td width="20%">
						%%LNG_Created%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Date&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Date&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border="0" /></a>
					</td>
					<td width="20%">
						%%LNG_FormType%%
					</td>
					<td width="20%">
						%%LNG_Owner%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Owner&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Owner&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border="0" /></a>
					</td>
					<td width="180" nowrap>
						%%LNG_Action%%
					</td>
				</tr>
			%%TPL_Forms_Manage_Row%%
			</table>
			%%TPL_Paging_Bottom%%
		</td>
	</tr>
</table>

<script>
	function DeleteSelectedForms(formObj) {
		forms_found = 0;
		for (var i=0; i<formObj.length; i++)
		{
			fldObj = formObj.elements[i];
			if (fldObj.type == 'checkbox')
			{
				if (fldObj.checked) {
					forms_found++;
					break;
				}
			}
		}

		if (forms_found <= 0) {
			alert("%%LNG_ChooseFormsToDelete%%");
			return false;
		}

		if (confirm("%%LNG_ConfirmRemoveForms%%")) {
			return true;
		}
		return false;
	}

	function ConfirmDelete(FormID) {
		if (!FormID) {
			return false;
		}
		if (confirm("%%LNG_DeleteFormPrompt%%")) {
			document.location='index.php?Page=%%PAGE%%&Action=Delete&id=' + FormID;
			return true;
		}
	}
</script>
