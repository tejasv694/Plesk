{capture name=folder_id}%%GLOBAL_FolderID%%{/capture}
<li style="list-style-type:none; margin:0; padding:0;" class="Folder SortableRow not-nestable" id="folder-%%GLOBAL_FolderID%%" expanded="%%GLOBAL_Expanded%%">
	<table cellpadding="0" cellspacing="0" width="100%" class="Text" style="margin:0; padding:0;">
		<tr class="GridRow" style="cursor:pointer;">
			<td width="28" align="center" style="cursor:pointer;">
				<input type="checkbox" name="Folders[]" class="UICheckboxToggleRows" value="1" onclick="$('input:checkbox', $('#folder-%%GLOBAL_FolderID%%')).attr('checked', this.checked);">
			</td>
			<td width="22" onclick="Application.Ui.Folders.ToggleFolder(%%GLOBAL_FolderID%%, '%%GLOBAL_FolderType%%');" style="cursor:pointer;">
				<img src="images/plus.gif" border="0" alt="Expand" class="Toggle" />
			</td>
			<td width="*" onclick="Application.Ui.Folders.ToggleFolder(%%GLOBAL_FolderID%%, '%%GLOBAL_FolderType%%');" style="cursor:pointer;">
				%%GLOBAL_FolderName%%
			</td>
			<td width="240" class="HideOnDrag">
				{if $folder_id !== '0'}
				<a href="#" onclick="tb_show('{$lang.Folders_RenameFolder}', 'index.php?Page=Folders&Action=Rename&FolderID=%%GLOBAL_FolderID%%&FolderName=%%GLOBAL_FolderName_Encoded%%&keepThis=true&TB_iframe=true&height=80&width=325', '');">{$lang.Folders_Rename}</a>
				<a href="#" onclick="Application.Ui.Folders.RemoveFolder(%%GLOBAL_FolderID%%);">{$lang.Folders_Delete}</a>
				{/if}
				&nbsp;
			</td>
		</tr>
	</table>
	<ul class="SortableList Folder" style="display:none;">
		%%GLOBAL_Items%%
	</ul>
</li>
