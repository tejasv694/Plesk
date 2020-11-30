<script>
	var PAGE = {
		init: function() {
				Application.Ui.CheckboxSelection(
					'table#SplittestManageList',
					'input.UICheckboxToggleSelector',
					'input.UICheckboxToggleRows'
				);

			$('#DeleteSplitTestButton').click(function() {
				PAGE.deleteSelected();
			});

		},

		deleteSelected: function() {
			var selected = 	$('.splitTestSelection').filter(function() { return this.checked; });

			if (selected.length < 1) {
				alert("{$lang.Addon_splittest_Delete_SelectFirst}");
				return false;
			}

			if (!confirm("{$lang.Addon_splittest_Delete_ConfirmMessage}")) {
				return;
			}

			var selectedIds = [];
			for(var i = 0, j = selected.length; i < j; ++i) {
				selectedIds.push(selected[i].value);
			}

			Application.Util.submitPost('{$AdminUrl}&Action=Delete', {splitids: selectedIds});
		}
	};

	$(function() {
		PAGE.init();
	});

	function DelSplitTest(id, status)
	{
		if (id < 1) {
			return false;
		}

		if (status == 'i' || status == 'r') {
			alert('{$lang.Addon_splittest_Manage_Delete_Disabled_Alert}');
			return false;
		}

		if (!confirm('{$lang.Addon_splittest_DeleteOne_Confirm}')) {
			return false;
		}

		Application.Util.submitPost('{$AdminUrl}&Action=Delete', {splitid: id});
		return true;
	}
</script>
<table width="100%" border="0">
	<tr>
		<td class="Heading1" colspan="2">{$lang.Addon_splittest_Heading}</td>
	</tr>
	<tr>
		<td class="body pageinfo" colspan="2"><p>{$lang.Addon_splittest_Intro}</p></td>
	</tr>
	<tr>
		<td colspan="2">
			{$FlashMessages}
		</td>
	</tr>
	<tr>
		<td class="body" colspan="2">
			{$SplitTest_Create_Button}
			{if $ShowDeleteButton}
				<input class="SmallButton" type="button" style="width: 150px;" value="{$lang.Addon_splittest_DeleteButton}" name="DeleteSplitTestButton" id="DeleteSplitTestButton"/>
			{/if}
		</td>
	</tr>
	<tr>
		<td valign="bottom">
			&nbsp;
		</td>
		<td align="right">
			<div align="right">
				{$Paging}
			</div>
		</td>
	</tr>
</table>
<form name="splittestlist" id="splittestlist">
<table class="Text" width="100%" cellspacing="0" cellpadding="0" border="0" id="SplittestManageList">
	<tr class="Heading3">
		<td width="1" align="center">
			<input class="UICheckboxToggleSelector" type="checkbox" name="toggle"/>
		</td>
		<td width="5">&nbsp;</td>
		<td width="20%" nowrap="nowrap">
			{$lang.Addon_splittest_Manage_SplitName}
			<a href="{$AdminUrl}&SortBy=splitname&Direction=asc"><img src="{$ApplicationUrl}images/sortup.gif" border="0"/></a>
			<a href="{$AdminUrl}&SortBy=splitname&Direction=desc"><img src="{$ApplicationUrl}images/sortdown.gif" border="0"/></a>
		</td>
		<td width="*" nowrap="nowrap">
			{$lang.Addon_splittest_Manage_SplitCampaigns}
		</td>
		<td width="10%" nowrap="nowrap">
			{$lang.Addon_splittest_Manage_SplitType}
			<a href="{$AdminUrl}&SortBy=splittype&Direction=asc"><img src="{$ApplicationUrl}images/sortup.gif" border="0"/></a>
			<a href="{$AdminUrl}&SortBy=splittype&Direction=desc"><img src="{$ApplicationUrl}images/sortdown.gif" border="0"/></a>
		</td>
		<td width="10%" nowrap="nowrap">
			{$lang.Addon_splittest_Manage_SplitCreated}
			<a href="{$AdminUrl}&SortBy=createdate&Direction=asc"><img src="{$ApplicationUrl}images/sortup.gif" border="0"/></a>
			<a href="{$AdminUrl}&SortBy=createdate&Direction=desc"><img src="{$ApplicationUrl}images/sortdown.gif" border="0"/></a>
		</td>
		<td width="10%" nowrap="nowrap">
			{$lang.Addon_splittest_Manage_SplitLastSent}
			<a href="{$AdminUrl}&SortBy=lastsent&Direction=asc"><img src="{$ApplicationUrl}images/sortup.gif" border="0"/></a>
			<a href="{$AdminUrl}&SortBy=lastsent&Direction=desc"><img src="{$ApplicationUrl}images/sortdown.gif" border="0"/></a>
		</td>
		<td width="180" nowrap="nowrap">
			{$lang.Addon_splittest_Manage_SplitAction}
		</td>
	</tr>
	{foreach from=$splitTests key=k item=splittestEntry}
		<tr class="GridRow" id="{$splittestEntry.splitid}">
			<td width="1">
				<input class="UICheckboxToggleRows splitTestSelection" type="checkbox" name="splitids[]" value="{$splittestEntry.splitid}">
			</td>
			<td>
					<img src="{$ApplicationUrl}/addons/splittest/images/m_splittests.gif" border="0"/>
			</td>
			<td>
				{$splittestEntry.splitname}
			</td>
			<td>
				{$splittestEntry.campaign_names}
			</td>
			<td>
				<span class="HelpText" onMouseOut="HideHelp('splitDisplay{$splittestEntry.splitid}');" onMouseOver="ShowQuickHelp('splitDisplay{$splittestEntry.splitid}', '{$splittestEntry.tipheading}', '{$splittestEntry.tipdetails}');">
				{if $splittestEntry.splittype == 'distributed'}
					{$lang.Addon_splittest_Manage_SplitType_Distributed}
				{elseif $splittestEntry.splittype == 'percentage'}
					{$lang.Addon_splittest_Manage_SplitType_Percentage}
				{/if}
				</span><br /><div id="splitDisplay{$splittestEntry.splitid}" style="display: none;"></div>
			</td>
			<td>
				{$splittestEntry.createdate|dateformat,$DateFormat}
			</td>
			<td>
				{if $splittestEntry.lastsent == 0}
					{$lang.Addon_splittest_Manage_LastSent_Never}
				{else}
					{$splittestEntry.lastsent|dateformat,$DateFormat}
				{/if}
			</td>
			<td>
				{if $SendPermission}
					{if $splittestEntry.jobstatus == 'i'}
						<a href="{$AdminUrl}&Action=Send&id={$splittestEntry.splitid}&Step=20">{$lang.Addon_splittest_Pause}</a>
					{elseif $splittestEntry.jobstatus == 'p'}
						<a href="{$AdminUrl}&Action=Send&id={$splittestEntry.splitid}&Step=30">{$lang.Addon_splittest_Resume}</a>
					{elseif $splittestEntry.jobstatus == 'w'}
						{if $ScheduleSendPermission}
							<a href="{$ApplicationUrl}index.php?Page=Schedule">{$lang.Addon_splittest_WaitingToSend}</a>
						{else}
							<a href="{$AdminUrl}&Action=Send&id={$splittestEntry.splitid}&Step=20">{$lang.Addon_splittest_WaitingToSend}</a>
						{/if}
					{elseif $splittestEntry.jobstatus == 't'}
						<span class="HelpText" onMouseOut="HideHelp('splitDisplayTimeout{$splittestEntry.splitid}');"
						onMouseOver="ShowQuickHelp('splitDisplayTimeout{$splittestEntry.splitid}', '{$splittestEntry.TimeoutTipHeading}', '{$splittestEntry.TimeoutTipDetails}');">
							{if $ScheduleSendPermission}
								{$lang.Addon_splittest_TimeoutMode}
							{else}
								<a href="{$AdminUrl}&Action=Send&id={$splittestEntry.splitid}&Step=20">{$lang.Addon_splittest_TimeoutMode}</a>
							{/if}
						</span><div id="splitDisplayTimeout{$splittestEntry.splitid}" style="display: none;"></div>
					{else}
						<a href="{$AdminUrl}&Action=Send&id={$splittestEntry.splitid}">{$lang.Addon_splittest_Manage_Send}</a>
					{/if}
				{/if}
				{if $EditPermission}
					{if $splittestEntry.jobstatus == 'i' || $splittestEntry.jobstatus == 'r'}
						&nbsp;<a href="#" onClick="alert('{$lang.Addon_splittest_Manage_Edit_Disabled_Alert}'); return false;" title="{$lang.Addon_splittest_Manage_Edit_Disabled}">{$lang.Addon_splittest_Manage_Edit}</a>
					{else}
						&nbsp;<a href="{$AdminUrl}&Action=Edit&id={$splittestEntry.splitid}">{$lang.Addon_splittest_Manage_Edit}</a>
					{/if}
				{/if}
				{if $CopyPermission}
					&nbsp;<a href="{$AdminUrl}&Action=Create&Copy={$splittestEntry.splitid}">{$lang.Addon_splittest_Manage_Copy}</a>
				{/if}
				{if $DeletePermission}
					&nbsp;<a href="#" {if $splittestEntry.jobstatus == 'i' || $splittestEntry.jobstatus == 'r'} title="{$lang.Addon_splittest_Manage_Delete_Disabled}"{/if} onClick="return DelSplitTest({$splittestEntry.splitid}, '{$splittestEntry.jobstatus}');">{$lang.Addon_splittest_Manage_Delete}</a>
				{/if}
				&nbsp;
			</td>
		</tr>
	{/foreach}
</table>
</form>
