<script>
	Application.Page.TriggerEmailsManage = {
		eventDOMReady: function(event) {
			$(document.frmPageAction).submit(Application.Page.TriggerEmailsManage.eventSubmitBulkAction);

			if (document.frmPageAction.cmdAddList)
				$(document.frmPageAction.cmdAddList).click(Application.Page.TriggerEmailsManage.eventAddList);

			if (document.frmPageAction.cmdAddNewsletter)
				$(document.frmPageAction.cmdAddNewsletter).click(Application.Page.TriggerEmailsManage.eventAddNewsletter);

			if (document.frmPageAction.cmdAddTrigger)
				$(document.frmPageAction.cmdAddTrigger).click(Application.Page.TriggerEmailsManage.eventAddTrigger);

			if($('#triggeremails_record_list').size() != 0) {
				Application.Ui.CheckboxSelection(	'table#triggeremails_record_list',
													'input.UICheckboxToggleSelector',
													'input.UICheckboxToggleRows');

				$(	'#triggeremails_record_list .TriggerEmails_Row_Action_DisableTrigger'
					+ ', #triggeremails_record_list .TriggerEmails_Row_Action_EnableTrigger'
					+ ', #triggeremails_record_list .TriggerEmails_Row_Action_Delete'
					+ ', #triggeremails_record_list .TriggerEmails_Row_Action_Copy').click(Application.Page.TriggerEmailsManage.eventActionRow);
			}
		},

		eventAddList: function(event) {
			document.location.href = 'index.php?Page=Lists&Action=Create';
		},

		eventAddNewsletter: function(event) {
			document.location.href = 'index.php?Page=Newsletters&Action=Create';
		},

		eventAddTrigger: function(event) {
			document.location.href = 'index.php?Page=TriggerEmails&Action=Create';
		},

		eventActionRow: function(event) {
			event.stopPropagation();
			event.preventDefault();

			var id = this.href.match(/id=(\d+)$/)[1];
			var url = this.href.replace(/&{0,1}id=(\d+)$/, '');
			var action = this.href.match(/Action=(\w+)/)[1];

			if (action == 'Delete') {
				if (!confirm('{$lang.TriggerEmails_Manage_PromptDelete_One}')) return;
			}

			Application.Util.submitPost(url, {'id':id});
		},

		eventSubmitBulkAction: function(event) {
			event.stopPropagation();
			event.preventDefault();

			if(this.ChangeType.selectedIndex == 0) {
				alert("{$lang.PleaseChooseAction}");
				return false;
			}

			var selectedIDs = [];
			var selectedRows = $('#triggeremails_record_list input.UICheckboxToggleRows').filter(':checked');
			var action = $(this.ChangeType).val();
			for(var i = 0, j = selectedRows.size(); i < j; ++i) selectedIDs.push(selectedRows.get(i).value);

			if (selectedIDs.length == 0) {
				alert("{$lang.TriggerEmails_Manage_PromptChoose}");
				return false;
			}

			if (action == 'delete') {
				if (!confirm('{$lang.TriggerEmails_Manage_PromptDelete}')) return;
			}

			Application.Util.submitPost(this.action, {	'Which': action,
														'IDs': selectedIDs});
		}
	};

	Application.init.push(Application.Page.TriggerEmailsManage.eventDOMReady);
</script>
<div class="PageBodyContainer">
	<div class="Page_Header">
		<div class="Heading1">{$lang.TriggerEmails_Manage}</div>
		<div class="Intro">{$lang.TriggerEmails_Intro}</div>
		{if trim($PAGE.messages) != ''}
			<div style="margin-top:5px;">{$PAGE.messages}</div>
		{/if}

		<div class="Page_Action">
			<div style="{if trim($PAGE.messages) == ''}padding-top: 10px;{/if} padding-bottom: 10px;">
				{* Contact Lists and Campaigns are available, print the "Add Trigger" button *}
				{if $listCount != 0 && $newsletterCount != 0}
					{if $permissions.create}
						<form name="frmCreateTrigger" action="index.php" method="GET">
							<input type="hidden" name="Page" value="TriggerEmails" />
							<input type="hidden" name="Action" value="Create" />
							<input type="submit" value="{$lang.TriggerEmails_Manage_AddButton}" class="SmallButton" style="width:auto;" />
						</form>
					{/if}

				{* Either/Both Contact Lists and Campaigns are NOT available, print buttons for these if user have the correct permissions *}
				{else}
					{if $listCount == 0 && $permissions.createList}
						<form name="frmCreateList" action="index.php" method="GET">
							<input type="hidden" name="Page" value="Lists" />
							<input type="hidden" name="Action" value="Create" />
							<input type="submit" value="{$lang.TriggerEmails_Manage_AddListButton}" class="SmallButton" style="width:auto;" />
						</form>
					{/if}
					<br />
					{if $newsletterCount == 0 && $permissions.createNewsletter}
						<form name="frmCreateNewsletter" action="index.php" method="GET">
							<input type="hidden" name="Page" value="Newsletters" />
							<input type="hidden" name="Action" value="Create" />
							<input type="submit" value="{$lang.TriggerEmails_Manage_AddCampaignButton}" class="SmallButton" style="width:auto;" />
						</form>
					{/if}
				{/if}
			</div>

			<form name="frmPageAction" action="index.php?Page=TriggerEmails&Action=BulkAction">
				<div class="Page_Action_Top"></div>

				{* Bulk Action and pagination -- Only print if records are available *}
				{if count($records) != 0}
					{* ----- Bulk Action ----- *}
						<div>
							<select name="ChangeType">
								<option value="" selected="selected">{$lang.ChooseAction}</option>
								{if $permissions.delete}<option value="delete">{$lang.TriggerEmails_Manage_BulkAction_Delete}</option>{/if}
								{if $permissions.activate}
									<option value="activate">{$lang.TriggerEmails_Manage_BulkAction_Activate}</option>
									<option value="deactivate">{$lang.TriggerEmails_Manage_BulkAction_Deactivate}</option>
								{/if}
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
			<table id="triggeremails_record_list" border="0" cellspacing="0" cellpadding="2" width="100%" class="Text">
				<tr class="Heading3">
					<td width="5" nowrap align="center"><input type="checkbox" class="UICheckboxToggleSelector" /></td>
					<td width="5">&nbsp;</td>
					<td width="55%">{$lang.TriggerEmails_Manage_Column_TriggerName}&nbsp;<a href="index.php?Page=TriggerEmails&SortBy=Name&Direction=Up"><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href="index.php?Page=TriggerEmails&SortBy=Name&Direction=Down"><img src="images/sortdown.gif" border="0" /></a></td>
					<td width="12%" style="white-space:nowrap;">{$lang.TriggerEmails_Manage_Column_CreateDate}&nbsp;<a href="index.php?Page=TriggerEmails&SortBy=CreateDate&Direction=Up"><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href="index.php?Page=TriggerEmails&SortBy=CreateDate&Direction=Down"><img src="images/sortdown.gif" border="0" /></a></td>
					<td width="12%" style="white-space:nowrap;">{$lang.TriggerEmails_Manage_Column_TriggeredBy}&nbsp;<a href="index.php?Page=TriggerEmails&SortBy=TriggerType&Direction=Up"><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href="index.php?Page=TriggerEmails&SortBy=TriggerType&Direction=Down"><img src="images/sortdown.gif" border="0" /></a></td>
					<td width="12%" style="white-space:nowrap;">{$lang.TriggerEmails_Manage_Column_TriggerHours}&nbsp;<a href="index.php?Page=TriggerEmails&SortBy=TriggerHours&Direction=Up"><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href="index.php?Page=TriggerEmails&SortBy=TriggerHours&Direction=Down"><img src="images/sortdown.gif" border="0" /></a></td>
					<td width="8%" align="center" style="white-space:nowrap;">{$lang.TriggerEmails_Manage_Column_Status}&nbsp;<a href="index.php?Page=TriggerEmails&SortBy=Active&Direction=Up"><img src="images/sortup.gif" border="0" /></a>&nbsp;<a href="index.php?Page=TriggerEmails&SortBy=Active&Direction=Down"><img src="images/sortdown.gif" border="0" /></a></td>
					<td width="130">{$lang.Action}</td>
				</tr>
				{foreach from=$records item=each}
					{capture name=recordID}{$each.triggeremailsid|intval}{/capture}
					{capture name=recordName}{$each.name|htmlspecialchars, ENT_QUOTES, SENDSTUDIO_CHARSET}{/capture}
					<tr class="GridRow">
						<td align="center">
							<input type="checkbox" class="UICheckboxToggleRows" value="{$recordID}" title="{$recordName}" />
						</td>
						<td><img src="images/m_triggeremails.gif" /></td>
						<td>{$recordName}</td>
						<td>{$each.procstr_createdate}</td>
						<td>
							{if $each.triggertype == 'f'}
								{$lang.TriggerEmails_Manage_Column_TriggeredBy_CustomField}
							{elseif $each.triggertype == 'n'}
								{$lang.TriggerEmails_Manage_Column_TriggeredBy_CampaignOpen}
							{elseif $each.triggertype == 'l'}
								{$lang.TriggerEmails_Manage_Column_TriggeredBy_LinkClicked}
							{elseif $each.triggertype == 's'}
								{$lang.TriggerEmails_Manage_Column_TriggeredBy_StaticDate}
							{else}
								-
							{/if}
						</td>
						<td>
							{capture name=temp}{$each.triggerhours|abs}{/capture}
							{if $each.triggerhours == 0}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_Immediate}
							{elseif $each.triggerhours == -1}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_OneHourBefore}
							{elseif $each.triggerhours < -1}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_HoursBefore|sprintf, $temp}
							{elseif $each.triggerhours == 1}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_OneHourAfter}
							{elseif $each.triggerhours > 1}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_HoursAfter|sprintf, $temp}
							{else}
								{$lang.N/A}
							{/if}
						</td>
						<td align="center">
							{if $each.active == 1}
								{if $permissions.activate}<a href="index.php?Page=TriggerEmails&Action=Disable&id={$recordID}" class="TriggerEmails_Row_Action_DisableTrigger" title="{$lang.TriggerEmails_Manage_Tips_DisableTrigger}">{/if}
									<img src="images/tick.gif" alt="{$lang.Status_Active}" alt="{$lang.TriggerEmails_Manage_Tips_DisableTrigger}" border="0" />
								{if $permissions.activate}</a>{/if}
							{else}
								{if $permissions.activate}<a href="index.php?Page=TriggerEmails&Action=Enable&id={$recordID}" class="TriggerEmails_Row_Action_EnableTrigger" title="{$lang.TriggerEmails_Manage_Tips_EnableTrigger}">{/if}
									<img src="images/cross.gif" alt="{$lang.Status_Inactive}" alt="{$lang.TriggerEmails_Manage_Tips_EnableTrigger}" border="0" />
								{if $permissions.activate}</a>{/if}
							{/if}
						</td>
						<td style="white-space:nowrap;">
							{if $permissions.edit}<a href="index.php?Page=TriggerEmails&Action=Edit&id={$recordID}">{$lang.Edit}</a>{/if}
							{if $permissions.create}&nbsp;<a href="index.php?Page=TriggerEmails&Action=Copy&id={$recordID}" class="TriggerEmails_Row_Action_Copy">{$lang.Copy}</a>{/if}
							{if $permissions.delete}&nbsp;<a href="index.php?Page=TriggerEmails&Action=Delete&id={$recordID}" class="TriggerEmails_Row_Action_Delete">{$lang.Delete}</a>{/if}
						</td>
					</tr>
				{/foreach}
			</table>
		{/if}
	</div>

	<div class="Page_Footer">
		{* Bottom pagination -- Only print if records are available *}
		{if count($records) != 0}{template="Paging_Bottom"}{/if}
	</div>
</div>