<table>
	<tr>
		<td class="Heading1">
			{$lang.Addon_checkpermissions_Heading}
		</td>
	</tr>
	<tr>
		<td>
			{$lang.Addon_checkpermissions_Intro}
		</td>
	</tr>
	<tr>
		<td>
			{if $ShowOk == true}
			<br />
			{$lang.Addon_checkpermissions_FollowingFileFolders} {$lang.Addon_checkpermissions_FollowingFileFolders_OK}:
			<ul>
			{foreach from=$PermissionsOk key=k item=permissionChecked id=permissionsokloop}
				<li style="color:#339933;">
					{$permissionChecked}
				</li>
			{/foreach}
			</ul>
			<br />
			{/if}

			{if $ShowFailed == true}
				{$lang.Addon_checkpermissions_FollowingFileFolders} {$lang.Addon_checkpermissions_FollowingFileFolders_NotOK}:
				<ul>
				{foreach from=$PermissionsFailed key=k item=permissionChecked id=permissionsnotokloop}
					<li style="color:#993333;">
						{$permissionChecked}
					</li>
				{/foreach}
				</ul>
				{$lang.Addon_checkpermissions_WhatToDo}
				<br />
			{/if}
			<br />
			<input type="button" class="SmallButton" value="{$lang.Addon_checkpermissions_CheckAgain}" onclick="window.location.href='{$AdminUrl}';" />
		</td>
	</tr>
</table>
