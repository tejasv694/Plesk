<style type="text/css">
	tr.Heading3 td { white-space:nowrap }

	table#ActionContainerTop { padding-top:5px; border:0; width:100% }
	table#ActionContainerTop input { width:150px }
</style>
<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr><td class="Heading1">{$lang.UsersGroups_ManageGroups}</td></tr>
	<tr><td class="body pageinfo"><p>{$lang.UsersGroups_ManageGroups_Intro}</p></td></tr>
	{if trim($PAGE.messages) != ''}<tr><td>{$PAGE.messages}</td></tr>{/if}
	<tr>
		<td class="body">
			<form name="frmUsersGroupsManage" method="post" action="index.php?Page=UsersGroups">
				<table id="ActionContainerTop">
					<tr>
						<td>
							<input id="createGroupButton" type="button" class="SmallButton" value="{$lang.UsersGroups_ManageGroups_CreateGroupButton}" />
							{if count($records) != 0}<input id="deleteGroupsButton" type="button" class="SmallButton" value="{$lang.UsersGroups_ManageGroups_DeleteGroupsButton}" />{/if}
						</td>
						{if count($records) != 0}<td align="right" valign="bottom">{template="Paging"}</td>{/if}
					</tr>
				</table>
				{if count($records) != 0}
					<table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text" id="GroupsListTable">
						<tr class="Heading3">
							<td width="5" nowrap align="center">
								<input type="checkbox" name="toggle" class="UICheckboxToggleSelector" />
							</td>
							<td width="5">&nbsp;</td>
							<td width="70%">
								{$lang.UsersGroups_Field_GroupName}
								<a href="index.php?Page=UsersGroups&action=manageGroups&SortBy=groupname&Direction=Up"><img src="images/sortup.gif" border="0" /></a>
								<a href="index.php?Page=UsersGroups&action=manageGroups&SortBy=groupname&Direction=Down"><img src="images/sortdown.gif" border="0" /></a>
							</td>
							<td width="15%">
								{$lang.UsersGroups_Field_UsersInGroup}
								<a href="index.php?Page=UsersGroups&action=manageGroups&SortBy=usercount&Direction=Up"><img src="images/sortup.gif" border="0" /></a>
								<a href="index.php?Page=UsersGroups&action=manageGroups&SortBy=usercount&Direction=Down"><img src="images/sortdown.gif" border="0" /></a>
							</td>
							<td width="15%">
								{$lang.UsersGroups_Field_DateCreated}
								<a href="index.php?Page=UsersGroups&action=manageGroups&SortBy=createdate&Direction=Up"><img src="images/sortup.gif" border="0" /></a>
								<a href="index.php?Page=UsersGroups&action=manageGroups&SortBy=createdate&Direction=Down"><img src="images/sortdown.gif" border="0" /></a>
							</td>
							<td width="70">{$lang.Action}</td>
						</tr>
						{foreach from=$records item=each}
							<tr class="GridRow GroupRecordRow">
								<td valign="top" align="center"><input type="checkbox" name="groups[]" value="{$each.groupid}" class="UICheckboxToggleRows {if $each.usercount > 0}GroupContainsUser{/if}" /></td>
								<td><img src="images/group.gif"></td>
								<td>{$each.groupname}</td>
								<td>{$each.usercount}</td>
								<td style="white-space:nowrap;">&nbsp;{$each.processed_CreateDate}</td>
								<td style="white-space:nowrap;">
									{if $each.usercount != 0}<a href="index.php?Page=Users&GroupID={$each.groupid}" title="{$lang.UsersGroups_ManageGroups_Action_ViewUsers}">{/if}
										{$lang.UsersGroups_ManageGroups_Action_ViewUsers}
									{if $each.usercount != 0}</a>{/if}
									&nbsp;<a href="index.php?Page=UsersGroups&Action=editGroup&GroupID={$each.groupid}" title="{$lang.Edit}">{$lang.Edit}</a>
									&nbsp;
								</td>
							</tr>
						{/foreach}
					</table>
				{/if}
			</form>
			{* Bottom pagination -- Only print if records are available *}
			{if count($records) != 0}{template="Paging_Bottom"}{/if}
		</td>
	</tr>
</table>
<script type="text/javascript">
Application.Page.UsersGroups_ManageGroups = {
	eventReady: function() {
		if ($('table#GroupsListTable')) {
			Application.Ui.CheckboxSelection('table#GroupsListTable', 'input.UICheckboxToggleSelector', 'input.UICheckboxToggleRows');
			$('input#deleteGroupsButton').click(Application.Page.UsersGroups_ManageGroups.eventDeleteGroups);
			$('input#createGroupButton').click(Application.Page.UsersGroups_ManageGroups.eventCreateGroup);
		}

		$(document.frmUsersGroupsManage).submit(Application.Page.UsersGroups_ManageGroups.eventFormStopEvent);
	},

	eventFormStopEvent: function(e) {
		e.stopPropagation();
		e.preventDefault();
		return false;
	},

	eventDeleteGroups: function(e) {
		var selectedRows = $('table tr.GroupRecordRow input.UICheckboxToggleRows[type=checkbox]:checked');

		if (selectedRows.size() == 0) {
			alert('{$lang.UsersGroups_ManageGroups_JS_ChooseAtLeastOne}');
			return true;
		}

		if (selectedRows.filter('.GroupContainsUser').size() != 0) {
			alert('{$lang.UsersGroups_ManageGroups_JS_GroupContainsUser}');
			return true;
		}

		if (!confirm('{$lang.UsersGroups_ManageGroups_JS_DeletePrompt}')) {
			return true;
		}

		var selectedIDs = [];
		for(var i = 0, j = selectedRows.size(); i < j; ++i) selectedIDs.push(selectedRows.get(i).value);

		Application.Util.submitPost('index.php?Page=UsersGroups&Action=deleteGroups', {groups:selectedIDs});
	},

	eventCreateGroup: function(e) {
		Application.Util.submitGet('index.php', {Page:'UsersGroups', Action:'createGroup'});
	}
};

Application.init.push(Application.Page.UsersGroups_ManageGroups.eventReady);
</script>
