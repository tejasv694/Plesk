<script>
	$(function() {
		if($('#GlobalMessage').html() == '')
			$('#GlobalMessageContainer').hide();
	});
</script>
<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">%%LNG_TemplatesManage%%</td>
	</tr>
	<tr>
		<td class="body pageinfo">%%LNG_Help_TemplatesManage%%%%GLOBAL_CreateTemplatePreview%%</td>
	</tr>
	<tr id="GlobalMessageContainer">
		<td id="GlobalMessage">%%GLOBAL_Message%%</td>
	</tr>
	<tr>
		<td class=body>
			<table width="100%" border="0">
				<tr>
					<td valign="bottom">
						<div style="padding-top:10px; padding-bottom:10px">
							%%GLOBAL_Templates_AddButton%%
						</div>
						<form name="ActionTemplatesForm" method="post" action="index.php?Page=Templates&Action=Change" onsubmit="return ConfirmChanges();" style="margin: 0px; padding: 0px;">
							<select name="ChangeType">
								<option value="" SELECTED>%%LNG_ChooseAction%%</option>
								%%GLOBAL_Option_DeleteTemplate%%
								%%GLOBAL_Option_ActivateTemplate%%
								%%GLOBAL_Option_GlobalTemplate%%
							</select>
							<input type="submit" value="%%LNG_Go%%" class="Text">
					</td>
					<td align="right" valign="bottom">
						%%TPL_Paging%%
					</td>
				</tr>
			</table>
			<table border=0 cellspacing="0" cellpadding="0" width=100% class="Text">
				<tr class="Heading3">
					<td width="5" nowrap align="center">
						<input type="checkbox" name="toggle" onClick="javascript: toggleAllCheckboxes(this);">
					</td>
					<td width="5">&nbsp;</td>
					<td width="55%">
						%%LNG_TemplateName%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Name&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Name&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border=0></a>
					</td>
					<td width="20%">
						%%LNG_Created%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Date&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Date&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border=0></a>
					</td>
					<td width="15%">
						%%LNG_Format%%
					</td>
					<td width="5%" align="center">%%LNG_Active%%</td>
					<td width="5%" align="center">%%LNG_Global%%</td>
					<td width="180">
						%%LNG_Action%%
					</td>
				</tr>
			%%TPL_Templates_Manage_Row%%
			</table>
			%%TPL_Paging_Bottom%%
		</td>
	</tr>
</table>

<script>
	function ConfirmChanges() {
		formObj = document.ActionTemplatesForm;

		if (formObj.ChangeType.selectedIndex == 0) {
			alert("%%LNG_PleaseChooseAction%%");
			formObj.ChangeType.focus();
			return false;
		}

		selectedValue = formObj.ChangeType[formObj.ChangeType.selectedIndex].value;

		templates_found = false;
		for (var i=0;i < formObj.length;i++)
		{
			fldObj = formObj.elements[i];
			if (fldObj.type == 'checkbox')
			{
				if (fldObj.checked) {
					templates_found = true;
					break;
				}
			}
		}

		if (!templates_found) {
			alert("%%LNG_ChooseTemplates%%");
			return false;
		}

		if (confirm("%%LNG_ConfirmChanges%%")) {
			return true;
		}

		return false;
	}

	function ConfirmDelete(TemplateID) {
		if (!TemplateID) {
			return false;
		}
		if (confirm("%%LNG_DeleteTemplatePrompt%%")) {
			document.location='index.php?Page=%%PAGE%%&Action=Delete&id=' + TemplateID;
		}
	}
</script>
