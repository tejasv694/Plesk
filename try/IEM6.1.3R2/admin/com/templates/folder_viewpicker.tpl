{capture name=mode}%%GLOBAL_Mode%%{/capture}
<div style="position:relative; display:inline; top:4px;">
	{if $mode == 'Folder'}
		<a href="index.php?Page={$IEM.CurrentPage}&amp;Mode=List"><img src="images/list_mode_off.gif" width="25" height="20" alt="%%LNG_Folders_SwitchTo%% %%LNG_Folders_ListMode%%" title="%%LNG_Folders_SwitchTo%% %%LNG_Folders_ListMode%%" border="0" /></a>
		<img src="images/folder_mode_on.gif" width="25" height="20" alt="%%LNG_Folders_CurrentlyIn%% %%LNG_Folders_FolderMode%%" title="%%LNG_Folders_CurrentlyIn%% %%LNG_Folders_FolderMode%%" border="0" />
		<a href="#" onclick="tb_show('%%LNG_Folders_AddFolder%%', 'index.php?Page=Folders&Action=Add&FolderType=list&keepThis=true&TB_iframe=true&height=80&width=325', '');"><img src="images/folder_add.gif" width="25" height="20" alt="%%LNG_Folders_AddFolder%%" title="%%LNG_Folders_AddFolder%%" border="0" /></a>
	{else}
		<img src="images/list_mode_on.gif" width="25" height="20" alt="%%LNG_Folders_CurrentlyIn%% %%LNG_Folders_ListMode%%" title="%%LNG_Folders_CurrentlyIn%% %%LNG_Folders_ListMode%%" border="0" />
		<a href="index.php?Page={$IEM.CurrentPage}&amp;Mode=Folder"><img src="images/folder_mode_off.gif" width="25" height="20" alt="%%LNG_Folders_SwitchTo%% %%LNG_Folders_FolderMode%%" title="%%LNG_Folders_SwitchTo%% %%LNG_Folders_FolderMode%%" border="0" /></a>
	{/if}
</div>
