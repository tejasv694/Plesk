<script>
	Application.Page.AutoresponderManage = {
		eventDOMReady: function(event) {
			Application.Ui.CheckboxSelection(	'table#AutoresponderManageList',
												'input.UICheckboxToggleSelector',
												'input.UICheckboxToggleRows');

			$(document.ActionAutorespondersForm.cmdChangeType).click(Application.Page.AutoresponderManage.eventChangeType);
		},

		eventChangeType: function(event) {
			if($(document.ActionAutorespondersForm.ChangeType).val() == '') {
				alert('{$lang.PleaseChooseAction}');
				event.stopPropagation();
				event.preventDefault();
				return;
			}

			if($('input.UICheckboxToggleRows:checked').size() == 0) {
				alert('{$lang.ChooseAutoresponders}');
				event.stopPropagation();
				event.preventDefault();
				return;
			}
		}
	}

	Application.init.push(Application.Page.AutoresponderManage.eventDOMReady);
</script>
<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">%%LNG_AutorespondersManage%%</td>
	</tr>
	<tr>
		<td class="body pageinfo"><p>%%LNG_Help_AutorespondersManage%%</p></td>
	</tr>
	<tr>
		<td>%%GLOBAL_Message%%</td>
	</tr>
	<tr>
		<td>%%GLOBAL_CronWarning%%</td>
	</tr>
	<tr>
		<td class="body">
			<table width="100%" border="0">
				<tr>
					<td valign="bottom">
						<div style="padding-top:10px; padding-bottom:10px">
							%%GLOBAL_Autoresponders_AddButton%%
						</div>
						<form name="ActionAutorespondersForm" method="post" action="index.php?Page=Autoresponders&Action=Change&list=%%GLOBAL_List%%" onsubmit="return ConfirmChanges();" style="margin: 0px; padding: 0px;">
							<select name="ChangeType">
								<option value="" selected="selected">%%LNG_ChooseAction%%</option>
								%%GLOBAL_Option_DeleteAutoresponder%%
								%%GLOBAL_Option_ActivateAutoresponder%%
							</select>
							<input type="submit" name="cmdChangeType" value="%%LNG_Go%%" class="Text" />
					</td>
					<td align="right" valign="bottom">
						%%TPL_Paging%%
					</td>
				</tr>
			</table>
			<table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text" id="AutoresponderManageList">
				<tr class="Heading3">
					<td width="5" nowrap>
						<input type="checkbox" name="toggle" class="UICheckboxToggleSelector">
					</td>
					<td width="5">&nbsp;</td>
					<td width="35%">
						%%LNG_Name%%&nbsp;<a href='index.php?Page=%%GLOBAL_PAGE%%&SortBy=Name&Direction=Up'><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href='index.php?Page=%%GLOBAL_PAGE%%&SortBy=Name&Direction=Down'><img src="images/sortdown.gif" border="0" /></a>
					</td>
					<td width="18%">
						%%LNG_Created%%&nbsp;<a href='index.php?Page=%%GLOBAL_PAGE%%&SortBy=Date&Direction=Up'><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href='index.php?Page=%%GLOBAL_PAGE%%&SortBy=Date&Direction=Down'><img src="images/sortdown.gif" border="0" /></a>
					</td>
					<td width="27%" valign="top">
						%%LNG_SentWhen%%&nbsp;<a href='index.php?Page=%%GLOBAL_PAGE%%&SortBy=Hours&Direction=Up'><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href='index.php?Page=%%GLOBAL_PAGE%%&SortBy=Hours&Direction=Down'><img src="images/sortdown.gif" border="0" /></a>
					</td>
					<td width="15%">
						%%LNG_Owner%%&nbsp;<a href='index.php?Page=%%GLOBAL_PAGE%%&SortBy=Owner&Direction=Up'><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href='index.php?Page=%%GLOBAL_PAGE%%&SortBy=Owner&Direction=Down'><img src="images/sortdown.gif" border="0" /></a>
					</td>
					<td width="5%">
						%%LNG_Active%%
					</td>
					<td width="120">
						%%LNG_Action%%
					</td>
				</tr>
				%%TPL_Autoresponders_Manage_Row%%
			</table>
			%%TPL_Paging_Bottom%%
		</td>
	</tr>
</table>

<script>

	function ConfirmChanges() {
		formObj = document.ActionAutorespondersForm;

		if (formObj.ChangeType.selectedIndex == 0) {
			alert("%%LNG_PleaseChooseAction%%");
			formObj.ChangeType.focus();
			return false;
		}

		selectedValue = formObj.ChangeType[formObj.ChangeType.selectedIndex].value;

		autos_found = 0;
		for (var i=0;i < formObj.length;i++)
		{
			fldObj = formObj.elements[i];
			if (fldObj.type == 'checkbox')
			{
				if (fldObj.checked) {
					autos_found++;
					break;
				}
			}
		}

		if (autos_found <= 0) {
			alert("%%LNG_ChooseAutoresponders%%");
			return false;
		}

		if (confirm("%%LNG_ConfirmChanges%%")) {
			return true;
		}
		return false;
	}

	function ConfirmDelete(AutoresponderID) {
		if (!AutoresponderID) {
			return false;
		}
		if (confirm("%%LNG_DeleteAutoresponderPrompt%%")) {
			document.location='index.php?Page=%%PAGE%%&Action=Delete&list=%%GLOBAL_list%%&id=' + AutoresponderID;
		}
	}
</script>
