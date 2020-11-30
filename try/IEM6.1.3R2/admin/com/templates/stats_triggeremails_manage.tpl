<script>
	Application.Page.StatsTriggerEmailsManage = {
		eventDOMReady: function(event) {
			if($('#triggeremails_statistics_list').size() != 0) {
				Application.Ui.CheckboxSelection(	'table#triggeremails_statistics_list',
													'input.UICheckboxToggleSelector',
													'input.UICheckboxToggleRows');

				$(document.frmPageAction).submit(Application.Page.StatsTriggerEmailsManage.eventSubmitBulkAction);

				$('#triggeremails_statistics_list .StatsTriggerEmails_Row_Action_Delete').click(Application.Page.StatsTriggerEmailsManage.eventActionRow);
			}

			if(document.frmPageAction.cmdAddTrigger) {
				$(document.frmPageAction.cmdAddTrigger).click(Application.Page.StatsTriggerEmailsManage.eventAddTrigger);
			}
		},

		eventActionRow: function(event) {
			event.stopPropagation();
			event.preventDefault();

			var id = this.href.match(/id=(\d+)$/)[1];
			var url = this.href.replace(/&{0,1}id=(\d+)$/, '');
			var action = this.href.match(/SubAction=(\w+)/)[1];

			if (action == 'Delete') {
				if (!confirm('{$lang.TriggerEmails_Stats_PromptDelete}')) return;
			}

			Application.Util.submitPost(url, {'id':id});
		},

		eventSubmitBulkAction: function(event) {
			event.stopPropagation();
			event.preventDefault();

			if (this.ChangeType.selectedIndex == 0) {
				alert("{$lang.PleaseChooseAction}");
				return false;
			}

			var selectedIDs = [];
			var selectedStatsIDs = [];
			var selectedRows = $('#triggeremails_statistics_list input.UICheckboxToggleRows').filter(':checked');

			if(selectedRows.size() == 0) {
				alert('{$lang.TriggerEmails_Stats_PromptChoose}');
				return false;
			}

			var action = $(this.ChangeType).val();
			for(var i = 0, j = selectedRows.size(); i < j; ++i) {
				var temp = selectedRows.get(i).value.split(':');
				selectedIDs.push(temp[0]);
				selectedStatsIDs.push(temp[1]);
			}

			switch (action) {
				case 'print':
					var idstring = 'triggerid[]=' + selectedIDs.join('&triggerid[]=');
					var statidstring = 'stats[]=' + selectedStatsIDs.join('&stats[]=');

					tb_show('{$lang.TriggerEmails_Stats_Print}',
							'remote_stats.php?height=420&width=420&overflow=none&statstype=t&Action=print&' + idstring + '&' + statidstring);
				break;

				case 'delete':
					if(!confirm('{$lang.TriggerEmails_Stats_BulkAction_Delete}')) break;
					Application.Util.submitPost('index.php?Page=Stats&Action=TriggerEmails&SubAction=bulkAction&bulkAction=delete',
												{'id':selectedStatsIDs});
				break;

				default:
					alert("{$lang.PleaseChooseAction}");
				break;
			}
		},

		eventAddTrigger: function(event) {
			event.stopPropagation();
			event.preventDefault();

			document.location.href='index.php?Page=TriggerEmails&Action=Create';
		}
	};

	Application.init.push(Application.Page.StatsTriggerEmailsManage.eventDOMReady);
</script>
<div class="PageBodyContainer">
	<div class="Page_Header">
		<div class="Heading1">{$lang.TriggerEmails_Stats_Title}</div>
		<div class="Intro">{$lang.TriggerEmails_Stats_Intro}</div>
		{if trim($PAGE.messages) != ''}
			<div style="margin-top: 5px;">{$PAGE.messages}</div>
		{/if}

		<div class="Page_Action">
			<div style="{if trim($PAGE.messages) == ''}padding-top: 10px;{/if} padding-bottom: 10px;">
				{* Contact Lists and Campaigns are available, print the "Add Trigger" button *}
				{if $listCount != 0 && $newsletterCount != 0}
					{if $permissions.create}
						<form name="frmCreateTrigger" action="index.php" method="GET">
							<input type="hidden" name="Page" value="TriggerEmails" />
							<input type="hidden" name="Action" value="Create" />
							<input type="submit" value="{$lang.TriggerEmails_Manage_AddButton}" class="SmallButton" style="width:150px;" />
						</form>
					{/if}

				{* Either/Both Contact Lists and Campaigns are NOT available, print buttons for these if user have the correct permissions *}
				{else}
					{if $listCount == 0 && $permissions.createList}
						<form name="frmCreateList" action="index.php" method="GET">
							<input type="hidden" name="Page" value="Lists" />
							<input type="hidden" name="Action" value="Create" />
							<input type="submit" value="{$lang.TriggerEmails_Manage_AddListButton}" class="SmallButton" style="width:150px;" />
						</form>
					{/if}
					<br />
					{if $newsletterCount == 0 && $permissions.createNewsletter}
						<form name="frmCreateNewsletter" action="index.php" method="GET">
							<input type="hidden" name="Page" value="Newsletters" />
							<input type="hidden" name="Action" value="Create" />
							<input type="submit" value="{$lang.TriggerEmails_Manage_AddCampaignButton}" class="SmallButton" style="width:150px;" />
						</form>
					{/if}
				{/if}
			</div>

			<form name="frmPageAction" method="POST" action="index.php?Page=Stats&Action=TriggerEmails&SubAction=BulkAction">
				<div class="Page_Action_Top"></div>
				{* Bulk Action and pagination -- Only print if records are available *}
				{if count($records) != 0}
					{* ----- Bulk Action ----- *}
						<div>
							<select name="ChangeType">
								<option value="" selected="selected">{$lang.ChooseAction}</option>
								<option value="delete">{$lang.Delete}</option>
								<option value="print">{$lang.Print_Stats_Selected}</option>
							</select>
							<input type="submit" name="cmdChangeType" class="Text" value="{$lang.Go}" />
						</div>
					{* ----- *}

					<div>{template="Paging"}</div>
				{/if}

				<div class="Page_Action_Bottom"></div>
			</form>
		</div>
	</div>

	<div class="Page_Contents">
		{if count($records) != 0}
			{* Tabular records *}
			<table id="triggeremails_statistics_list" border="0" cellspacing="0" cellpadding="2" width="100%" class="Text">
				<tr class="Heading3">
					<td width="5" nowrap align="center"><input type="checkbox" class="UICheckboxToggleSelector" /></td>
					<td width="5">&nbsp;</td>
					<td width="70%">{$lang.TriggerEmails_Manage_Column_TriggerName}&nbsp;<a href="index.php?Page=Stats&Action=TriggerEmails&SortBy=Name&Direction=Up"><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href="index.php?Page=Stats&Action=TriggerEmails&SortBy=Name&Direction=Down"><img src="images/sortdown.gif" border="0" /></a></td>
					<td width="10%">{$lang.TriggerEmails_Manage_Column_TriggeredBy}&nbsp;<a href="index.php?Page=Stats&Action=TriggerEmails&SortBy=TriggerType&Direction=Up"><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href="index.php?Page=Stats&Action=TriggerEmails&SortBy=TriggerType&Direction=Down"><img src="images/sortdown.gif" border="0" /></a></td>
					<td width="10%">{$lang.TriggerEmails_Manage_Column_TriggerHours}&nbsp;<a href="index.php?Page=Stats&Action=TriggerEmails&SortBy=TriggerHours&Direction=Up"><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href="index.php?Page=Stats&Action=TriggerEmails&SortBy=TriggerHours&Direction=Down"><img src="images/sortdown.gif" border="0" /></a></td>
					<td width="130">{$lang.Action}</td>
				</tr>
				{foreach from=$records item=each}
					{capture name=recordID}{$each.triggeremailsid|intval}{/capture}
					{capture name=statID}{$each.statid|intval}{/capture}
					{capture name=recordName}{$each.triggeremailsname|htmlspecialchars, ENT_QUOTES, SENDSTUDIO_CHARSET}{/capture}
					<tr class="GridRow">
						<td align="center">
							<input type="checkbox" class="UICheckboxToggleRows" value="{$recordID}:{$statID}" title="{$recordName}" />
						</td>
						<td><img src="images/m_triggeremails.gif" /></td>
						<td>{$recordName}</td>
						<td>
							{if $each.triggeremailstype == 'f'}
								{$lang.TriggerEmails_Manage_Column_TriggeredBy_CustomField}
							{elseif $each.triggeremailstype == 'n'}
								{$lang.TriggerEmails_Manage_Column_TriggeredBy_CampaignOpen}
							{elseif $each.triggeremailstype == 'l'}
								{$lang.TriggerEmails_Manage_Column_TriggeredBy_LinkClicked}
							{elseif $each.triggeremailstype == 's'}
								{$lang.TriggerEmails_Manage_Column_TriggeredBy_StaticDate}
							{else}
								-
							{/if}
						</td>
						<td>
							{capture name=temp}{$each.triggeremailshours|abs}{/capture}
							{if $each.triggeremailshours == 0}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_Immediate}
							{elseif $each.triggeremailshours == -1}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_OneHourBefore}
							{elseif $each.triggeremailshours < -1}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_HoursBefore|sprintf, $temp}
							{elseif $each.triggeremailshours == 1}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_OneHourAfter}
							{elseif $each.triggeremailshours < 1}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_HoursAfter|sprintf, $temp}
							{else}
								{$lang.N/A}
							{/if}
						</td>
						<td style="white-space:nowrap;">
							<a href="index.php?Page=Stats&Action=TriggerEmails&SubAction=view&id={$recordID}">{$lang.ViewSummary}</a>&nbsp;
							<a href="remote_stats.php?height=420&width=420&overflow=none&statstype=t&Action=print&stats[]={$statID}&triggerid[]={$recordID}" class="thickbox" title="{$lang.TriggerEmails_Stats_Print}">{$lang.Print_Stats_Selected}</a>&nbsp;
							<a href="index.php?Page=Stats&Action=TriggerEmails&SubAction=Delete&id={$statID}" title="{$lang.TriggerEmails_Stats_Delete}" class="StatsTriggerEmails_Row_Action_Delete">{$lang.Delete}</a>
						</td>
					</tr>
				{/foreach}
			</table>
		{else}
			{* No records, not printing anything? *}
		{/if}
	</div>

	<div class="Page_Footer">
		{* Bottom pagination -- Only print if records are available *}
		{if count($records) != 0}{template="Paging_Bottom"}{/if}
	</div>
</div>