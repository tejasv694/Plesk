<script>
	var PAGE = {
		init: function() {
				Application.Ui.CheckboxSelection(
					'table#DynamiccontenttagManageList',
					'input.UICheckboxToggleSelector',
					'input.UICheckboxToggleRows'
				);

			$('#DeleteDynamiccontenttagButton').click(function() {
				PAGE.deleteSelected();
			});

		},

		deleteSelected: function() {
			var selected = 	$('.dynamicContentTagSelection').filter(function() { return this.checked; });

			if (selected.length < 1) {
				alert("{$lang.Addon_dynamiccontenttags_Delete_SelectFirst}");
				return false;
			}

			if (!confirm("{$lang.Addon_dynamiccontenttags_Delete_ConfirmMessage}")) {
				return;
			}

			var selectedIds = [];
			for(var i = 0, j = selected.length; i < j; ++i) {
				selectedIds.push(selected[i].value);
			}

			Application.Util.submitPost('{$AdminUrl}&Action=Delete', {tagids: selectedIds});
		}
	};

	$(function() {
		PAGE.init();
	});

	function DelDynamicTag(id)
	{
		if (id < 1) {
			return false;
		}

		if (!confirm('{$lang.Addon_dynamiccontenttags_DeleteOne_Confirm}')) {
			return false;
		}

		Application.Util.submitPost('{$AdminUrl}&Action=Delete', {tagids: id});
		return true;
	}
</script>
<table width="100%" border="0">
	<tr>
		<td class="Heading1" colspan="2">{$lang.Addon_dynamiccontenttags_ViewHeading}</td>
	</tr>
	<tr>
		<td class="body pageinfo" colspan="2"><p>{$lang.Addon_dynamiccontenttags_Form_Intro}</p></td>
	</tr>
	<tr>
		<td colspan="2">
			{$FlashMessages}
		</td>
	</tr>
	<tr>
		<td class="body" colspan="2">
			{$Tags_Create_Button}
			{if $ShowDeleteButton}
				<input class="SmallButton" type="button" style="width: 100px;" value="{$lang.Addon_dynamiccontenttags_DeleteSelected}" name="DeleteDynamiccontenttagButton" id="DeleteDynamiccontenttagButton"/>
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
<form name="dynamiccontenttag" id="dynamiccontenttag">
<table class="Text" width="100%" cellspacing="0" cellpadding="0" border="0" id="DynamiccontenttagManageList">
	<tr class="Heading3">
		<td width="1" align="center">
			<input class="UICheckboxToggleSelector" type="checkbox" name="toggle"/>
		</td>
		<td width="5">&nbsp;</td>
		<td width="50%" nowrap="nowrap">
			{$lang.Addon_dynamiccontenttags_Manage_TagName}
			<a href="{$AdminUrl}&SortBy=name&Direction=asc"><img src="{$ApplicationUrl}images/sortup.gif" border="0"/></a>
			<a href="{$AdminUrl}&SortBy=name&Direction=desc"><img src="{$ApplicationUrl}images/sortdown.gif" border="0"/></a>
		</td>
		<td width="15%" nowrap="nowrap">
			{$lang.Addon_dynamiccontenttags_Manage_TagOwner}
			<a href="{$AdminUrl}&SortBy=username&Direction=asc"><img src="{$ApplicationUrl}images/sortup.gif" border="0"/></a>
			<a href="{$AdminUrl}&SortBy=username&Direction=desc"><img src="{$ApplicationUrl}images/sortdown.gif" border="0"/></a>
		</td>
		<td width="15%" nowrap="nowrap">
			{$lang.Addon_dynamiccontenttags_Manage_TagCreated}
			<a href="{$AdminUrl}&SortBy=createdate&Direction=asc"><img src="{$ApplicationUrl}images/sortup.gif" border="0"/></a>
			<a href="{$AdminUrl}&SortBy=createdate&Direction=desc"><img src="{$ApplicationUrl}images/sortdown.gif" border="0"/></a>
		</td>
		<td width="180" nowrap="nowrap">
			{$lang.Addon_dynamiccontenttags_Manage_TagAction}
		</td>
	</tr>
	{foreach from=$tags key=k item=tagsEntry}
		<tr class="GridRow" id="{$tagsEntry.tagid}">
			<td width="1">
				&nbsp;<input class="UICheckboxToggleRows dynamicContentTagSelection" type="checkbox" name="tagids[]" value="{$tagsEntry.tagid}">
			</td>
			<td>
				<img src="{$ApplicationUrl}addons/dynamiccontenttags/images/dct_add.gif" />
			</td>
			<td>
				{$tagsEntry.name}
			</td>
			<td>
				{$tagsEntry.ownerusername}
			</td>
			<td>
				{$tagsEntry.createdate|dateformat,$DateFormat}
			</td>
			<td>
				{if $EditPermission}
					<a href="{$AdminUrl}&Action=Edit&id={$tagsEntry.tagid}">{$lang.Addon_dynamiccontenttags_Manage_Edit}</a>
				{/if}
				{if $DeletePermission}
					<a href="#" onClick="return DelDynamicTag({$tagsEntry.tagid});">{$lang.Addon_dynamiccontenttags_Manage_Delete}</a>
				{/if}
				&nbsp;
			</td>
		</tr>
	{/foreach}
</table>
</form>
