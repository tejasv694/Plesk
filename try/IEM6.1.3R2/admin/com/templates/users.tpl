<style type="text/css">
	tr.Heading3 td { white-space:nowrap }
	div.UserInfo img { margin-top:-3px }
	
	div.NoRecords { padding-top: 50px; text-align: center }

	table#ActionContainerTop { padding-top:5px; border:0; width:100% }
	table#ActionContainerTop input#createAccountButton { width:150px }
	table#ActionContainerTop button#deleteAccountButton { white-space:nowrap }
	table#ActionContainerTop div#deleteAccount { display:none; width:130px }
	table#ActionContainerTop div#deleteAccount img { border:0; padding:0 5px 0 0; margin:0; vertical-align:middle }
</style>
<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr><td class="Heading1">{$lang.Users}{if $groupInformation.groupid} : {$groupInformation.groupname}{/if}</td></tr>
	<tr><td class="body pageinfo"><p>{$lang.Help_Users}</p></td></tr>
	{if trim($PAGE.messages) != ''}<tr><td>{$PAGE.messages}</td></tr>{/if}
	{if $PAGE.userreport}
	<tr>
		<td>
			<div class="UserInfo">
				<img src="images/user.gif" alt="user_icon" align ="left">{$PAGE.userreport}
			</div>
		</td>
	</tr>
	{/if}
	<tr>
		<td class="body">
			<form name="userform" method="post" action="index.php?Page=Users">
				<table id="ActionContainerTop">
					<tr>
						<td colspan="2" align="right" style="padding-bottom: 10px;">
					 		<input type="text" class="Field250" size="20" value="{$quicksearchstring}" name="QuickSearchString" title="Search for emails in this mailing list." />
							<input type="image" border="0" src="images/searchicon.gif" id="SearchButton" style="padding-left: 10px; vertical-align: top;" name="SearchButton" alt="Search" />
							<input type="submit" value="search" style="display:none;" />
						</td>
					</tr>
					<tr>
						<td>
							<input id="createAccountButton" type="button" class="SmallButton" value="{$lang.UserAdd}" disabled="disabled" />
							{if count($records) != 0}
								<button id="deleteAccountButton" class="SmallButton">{$lang.UserDeletePopDown}</button>
								<div id="deleteAccount" class="DropDownMenu DropShadow">
									<ul>
										<li>
											<a href="#N" title="{$lang.UserDeleteNoData_Summary}">
												<img src="images/lists_view.gif" alt="icon" /> {$lang.UserDeleteNoData}
											</a>
										</li>
										<li>
											<a href="#Y" title="{$lang.UserDeleteWithData_Summary}">
												<img src="images/lists_view.gif" alt="icon" /> {$lang.UserDeleteWithData}
											</a>
										</li>
									</ul>
								</div>
							{/if}
						</td>
						{if count($records) != 0}<td align="right" valign="bottom">{template="Paging"}</td>{/if}
					</tr>
				</table>
				{if count($records) == 0}
					<div class="NoRecords">{$lang.SearchRecordNotFound}</div>
				{else}
					<table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text" id="UserListTable">
						<tr class="Heading3">
							<td width="5" nowrap align="center">
								<input type="checkbox" name="toggle" class="UICheckboxToggleSelector" />
							</td>
							<td width="5">&nbsp;</td>
							<td width="24%">
								{$lang.UserName}
								<a href="index.php?Page=Users&SortBy=username&Direction=Up"><img src="images/sortup.gif" border="0" /></a>
								<a href="index.php?Page=Users&SortBy=username&Direction=Down"><img src="images/sortdown.gif" border="0" /></a>
							</td>
							<td width="24%">
								{$lang.FullName}
								<a href="index.php?Page=Users&SortBy=fullname&Direction=Up"><img src="images/sortup.gif" border="0" /></a>
								<a href="index.php?Page=Users&SortBy=fullname&Direction=Down"><img src="images/sortdown.gif" border="0" /></a>
							</td>
							<td width="12%">
								{$lang.UserType}
								<a href="index.php?Page=Users&SortBy=admintype&Direction=Up"><img src="images/sortup.gif" border="0" /></a>
								<a href="index.php?Page=Users&SortBy=admintype&Direction=Down"><img src="images/sortdown.gif" border="0" /></a>
							</td>
							<td width="15%">
								{$lang.UsersGroups}
								<a href="index.php?Page=Users&SortBy=usergroup&Direction=Up"><img src="images/sortup.gif" border="0" /></a>
								<a href="index.php?Page=Users&SortBy=usergroup&Direction=Down"><img src="images/sortdown.gif" border="0" /></a>
							</td>
							<td width="12%">
								{$lang.UserCreatedOn}
								<a href="index.php?Page=Users&SortBy=createdate&Direction=Up"><img src="images/sortup.gif" border="0" /></a>
								<a href="index.php?Page=Users&SortBy=createdate&Direction=Down"><img src="images/sortdown.gif" border="0" /></a>
							</td>
							<td width="12%">
								{$lang.LastLoggedIn}
								<a href="index.php?Page=Users&SortBy=lastloggedin&Direction=Up"><img src="images/sortup.gif" border="0" /></a>
								<a href="index.php?Page=Users&SortBy=lastloggedin&Direction=Down"><img src="images/sortdown.gif" border="0" /></a>
							</td>
							<td width="15">
								{$lang.UserStatusColumn}
								<a href="index.php?Page=Users&SortBy=status&Direction=Up"><img src="images/sortup.gif" border="0" /></a>
								<a href="index.php?Page=Users&SortBy=status&Direction=Down"><img src="images/sortdown.gif" border="0" /></a>
							</td>
							<td width="70">
								{$lang.Action}
							</td>
						</tr>
						{foreach from=$records item=each}
							<tr class="GridRow UserRecordRow">
								<td valign="top" align="center"><input type="checkbox" name="users[]" value="{$each.userid}" class="UICheckboxToggleRows" /></td>
								<td><img src="images/user.gif"></td>
								<td>{$each.username}</td>
								<td>{if trim($each.fullname) == ''}{$lang.N/A}{else}{$each.fullname}{/if}</td>
								<td style="white-space: nowrap;">
									{if $each.admintype == 'a'}
										{$lang.AdministratorType_SystemAdministrator}
									{elseif $each.admintype == 'l'}
										{$lang.AdministratorType_ListAdministrator}
									{elseif $each.admintype == 'n'}
										{$lang.AdministratorType_NewsletterAdministrator}
									{elseif $each.admintype == 't'}
										{$lang.AdministratorType_TemplateAdministrator}
									{elseif $each.admintype == 'u'}
										{$lang.AdministratorType_UserAdministrator}
									{elseif !$each.trialuser}
										{$lang.AdministratorType_RegularUser}
									{else}
										{$lang.AdministratorType_TrialUser}
									{/if}
								</td>
								<td style="white-space: nowrap;">{$each.groupname}</td>
								<td>{$each.processed_CreateDate}</td>
								<td style="white-space:nowrap;">{$each.processed_LastLoggedIn}</td>
								<td align="center">
									{if $each.status == '0'}
										<img alt="{$lang.Inactive}" src="images/cross.gif" border="0" title="{$lang.Inactive}" />
									{elseif $each.status == '1'}
										<img alt="{$lang.Active}" src="images/tick.gif" border="0" title="{$lang.Active}" />
									{else}
										-
									{/if}
								</td>
								<td style="white-space:nowrap;">
									<a href="index.php?Page=Users&Action=Edit&UserID={$each.userid}">{$lang.Edit}</a>
									{if $each.userid != $PAGE.currentuserid}
										&nbsp;<a href="index.php?page=AdminTools&action=disguise&newID={$each.userid}" class="ActionLink ActionType_disguise">{$lang.LoginAsUser}</a>
									{/if}
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
$(function() {
	Application.Ui.CheckboxSelection(	'table#UserListTable',
										'input.UICheckboxToggleSelector',
										'input.UICheckboxToggleRows');

	$(document.userform.QuickSearchString).focus(function() {
		if (this.readonly) {
			this.value = '';
		}

		this.readonly = false;
		$(this).css('color', '');
		this.select();
	});
	
	$(document.userform.QuickSearchString).blur(function() { EvaluateQuickSearch(); });
	EvaluateQuickSearch();

	{if count($records) == 0}
		$(document.userform.QuickSearchString).focus();
	{else}
		$('table#UserListTable').click(function(e) {
			if (!$(e.target).is('a.ActionType_disguise')) return;
			var matches = $(e.target).attr('href').match(/(.*?)&newID=(\d+)$/);
	
			if (matches.length == 3) {
				Application.Util.submitPost(matches[1], {newUserID:matches[2]});
			}
	
			e.stopPropagation();
			e.preventDefault();
			return false;
		});
	
		$('div#deleteAccount a').click(function(e) {
			e.preventDefault();
	
			var deleteData = ($(this).attr('href').match(/^#Y/) == '#Y');
			var selectedIDs = [];
			var selectedRows = $('table tr.UserRecordRow input.UICheckboxToggleRows[type=checkbox]:checked');
			for(var i = 0, j = selectedRows.size(); i < j; ++i) selectedIDs.push(selectedRows.get(i).value);
	
			if (selectedRows.length == 0) {
				alert("{$lang.ChooseUsersToDelete}");
				return true;
			}
	
			if ($.inArray('{$PAGE.currentuserid}', selectedIDs) != -1) {
				alert("{$lang.User_CantDeleteOwn}");
				return true;
			}
	
			if (!confirm(deleteData ? "{$lang.ConfirmRemoveUsersWithData}" : "{$lang.ConfirmRemoveUsers}")) {
				return true;
			}
	
			$('button#deleteAccountButton', document.userform).attr('disabled', true);
			Application.Util.submitPost('index.php?Page=Users&Action=Delete', {users:selectedIDs, deleteData:(deleteData ? '1' : '0')});
			return true;
		});
	{/if}

	$('input#createAccountButton', document.userform).click(function() {
		document.location="index.php?Page=Users&Action=Add";
	});

	Application.Ui.Menu.PopDown('button#deleteAccountButton', {topMarginPixel: -3});
});

function DeleteSelectedUsers(formObj) {
	users_found = 0;
	for (var i=0;i < formObj.length;i++)
	{
		fldObj = formObj.elements[i];
		if (fldObj.type == 'checkbox')
		{
			if (fldObj.checked) {
				users_found++;
				break;
			}
		}
	}

	if (users_found <= 0) {
		alert("%%LNG_ChooseUsersToDelete%%");
		return false;
	}

	if (confirm("%%LNG_ConfirmRemoveUsers%%")) {
		return true;
	}
	return false;
}

function EvaluateQuickSearch() {
	var elm = document.userform.QuickSearchString;
	if(elm.value.trim() == '') {
		$(elm).css('color', '#999999');
		elm.value = '{$lang.QuickUserSearchIntro}';
		elm.readonly = true;
	}
}
</script>