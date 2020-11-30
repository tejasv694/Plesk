<script>
	Application.Page.SubscribersBannedManage = {
		eventDOMReady: function(event) {
			Application.Ui.CheckboxSelection(	'table#SubscribersBannedManageList',
												'input.UICheckboxToggleSelector',
												'input.UICheckboxToggleRows');
		}
	}

	Application.init.push(Application.Page.SubscribersBannedManage.eventDOMReady);
</script>
<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">%%GLOBAL_SubscribersBannedManage%%</td>
	</tr>
	<tr>
		<td class="body pageinfo"><p>%%LNG_Manage_Banned_Intro%%</p></td>
	</tr>
	<tr>
		<td>
			%%GLOBAL_Message%%
		</td>
	</tr>
	<tr><td class=body height="10">%%GLOBAL_Subscribers_AddButton%%</td></tr>
	<tr>
		<td class=body>
			<form name="bannedform" method="post" action="index.php?Page=Subscribers&Action=Banned&SubAction=Delete&list=%%GLOBAL_List%%" onsubmit="return DeleteSelectedBans(this);">
			<table width="100%" border="0" cellspacing="0">
				<tr>
					<td valign="top">
						<div style="padding-bottom:10px">
							<input type="button" name="AddBannedButton" value="%%LNG_BannedAddButton%%" class="SmallButton" style="width:210px" onclick="javascript: document.location='index.php?Page=Subscribers&Action=Banned&SubAction=add';" />
							<input type="submit" name="DeleteBannedButton" value="%%LNG_Delete_Banned_Selected%%" class="SmallButton" />
						</div>
					</td>
					<td align="right">
						%%TPL_Paging%%
					</td>
				</tr>
			</table>
			<table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text" id="SubscribersBannedManageList">
				<tr class="Heading3">
					<td width="5" nowrap align="center">
						<input type="checkbox" name="toggle" class="UICheckboxToggleSelector">
					</td>
					<td width="5">&nbsp;</td>
					<td width="80%">
						%%LNG_BannedSubscriberEmail%%&nbsp;<a href='index.php?Page=Subscribers&Action=Banned&SubAction=Step2&SortBy=EmailAddress&Direction=Up&list=%%GLOBAL_List%%'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=Subscribers&Action=Banned&SubAction=Step2&SortBy=EmailAddress&Direction=Down&list=%%GLOBAL_List%%'><img src="images/sortdown.gif" border=0></a>
					</td>
					<td width="20%">
						%%LNG_BannedDate%%&nbsp;<a href='index.php?Page=Subscribers&Action=Banned&SubAction=Step2&SortBy=BanDate&Direction=Up&list=%%GLOBAL_List%%'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=Subscribers&Action=Banned&SubAction=Step2&SortBy=BanDate&Direction=Down&list=%%GLOBAL_List%%'><img src="images/sortdown.gif" border=0></a>
					</td>
					<td width="70" nowrap>
						%%LNG_Action%%
					</td>
				</tr>
				%%TPL_Subscribers_Banned_Manage_Row%%
			</table>
			%%TPL_Paging_Bottom%%
			</form>
		</td>
	</tr>
</table>


<script>
	function DeleteSelectedBans(formObj) {

		bans_found = 0;
		for (var i=0;i < formObj.length;i++)
		{
			fldObj = formObj.elements[i];
			if (fldObj.type == 'checkbox')
			{
				if (fldObj.checked) {
					bans_found++;
					break;
				}
			}
		}

		if (bans_found <= 0) {
			alert("%%LNG_ChooseBannedSubscribers%%");
			return false;
		}

		if (confirm("%%LNG_ConfirmRemoveBannedSubscribers%%")) {
			return true;
		}
		return false;
	}

	function ConfirmDelete(EmailID) {
		var List = '%%GLOBAL_List%%';
		if (!EmailID) {
			return false;
		}
		if (confirm("%%LNG_DeleteBannedSubscriberPrompt%%")) {
			document.location='index.php?Page=Subscribers&Action=Banned&SubAction=Delete&list=' + List + '&id=' + EmailID;
			return true;
		}
	}
</script>
