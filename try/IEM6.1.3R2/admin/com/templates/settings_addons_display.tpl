<script>
	function UnInstall(addon_name) {
		if (!confirm('%%LNG_Addon_Uninstall_Confirm%%')) {
			return false;
		}
		document.location = 'index.php?Page=Settings&Tab=4&Action=Addons&SubAction=Uninstall&Addon=' + escape(addon_name);
		return true;
	}
</script>
<table cellspacing="0" cellpadding="2" width="100%" class="Panel" border="0">
	<tr class="Heading3">
		<td>{$lang.Addon_Name}</td>
		<td>{$lang.Addon_Description}</td>
		<td>{$lang.Addon_RunningVersion}</td>
		<td style="text-align: center;">{$lang.Addon_Installed}</td>
		<td style="text-align: center;">{$lang.Addon_Enabled}</td>
		<td>{$lang.Action}</td>
	</tr>
	{foreach from=$records key=addon_name item=record}
		<tr class="GridRow">
			<td><span>{$record.name}</span></td>
			<td><span title="{$record.description}">{$record.short_description}</span></td>
			<td>{$record.addon_version}</td>
			<td style="text-align: center;">
				{if $record.install_details.installed}
					<a href="#" onClick="UnInstall('{$addon_name}');" title="{$lang.Addon_Tooltip_ClickToUninstall}">
						<img src="images/tick.gif" border="0" alt="uninstall" />
					</a>
				{else}
					<a href="index.php?Page=Settings&Action=Addons&Addon={$addon_name}&SubAction=install" title="{$lang.Addon_Tooltip_ClickToInstall}">
						<img src="images/cross.gif" border="0" alt="install" />
					</a>
				{/if}
			</td>
			<td style="text-align: center;">
				{if $record.install_details.enabled}
					<a href="index.php?Page=Settings&Action=Addons&Addon={$addon_name}&SubAction=disable" title="{$lang.Addon_Tooltip_ClickToDisable}">
						<img src="images/tick.gif" border="0" alt="disable" />
					</a>
				{elseif $record.install_details.configured}
					<a href="index.php?Page=Settings&Action=Addons&Addon={$addon_name}&SubAction=enable" title="{$lang.Addon_Tooltip_ClickToEnable}">
						<img src="images/cross.gif" border="0" alt="enable" />
					</a>
				{elseif $record.install_details}
					<a href="#" onClick="alert('{$lang.Addon_Alert_NeedToConfigure}'); return false;" title="{$lang.Addon_Tooltip_ClickToEnable}">
						<img src="images/cross.gif" border="0" alt="enable" />
					</a>
				{else}
					<a href="#" onClick="alert('{$lang.Addon_Alert_NeedToInstall}'); return false;" title="{$lang.Addon_Tooltip_ClickToEnable}">
						<img src="images/cross.gif" border="0" alt="enable" />
					</a>
				{/if}
			</td>
			<td>
				{if $record.install_details && $record.need_upgrade}
					<a href="index.php?Page=Settings&Action=Addons&Addon={$addon_name}&SubAction=upgrade">{$lang.Addon_Action_Text_Upgrade}</a>
				{elseif $record.install_details && $record.hasconfiguration}
					<a href="#" onClick="LoadAddonSettings('{$addon_name}', '{$record.name}'); return false;">{$lang.Addon_Action_Text_Configure}</a>
				{elseif $record.install_details}
					<a href="#" onClick="alert('{$lang.Addon_Alert_NoConfiguration}'); return false;" style="color:#cacaca;">{$lang.Addon_Action_Text_Configure}</a>
				{else}
					<span style="color:#cacaca;">{$lang.Addon_Action_Text_Configure}</span>
				{/if}
			</td>
		</tr>
	{/foreach}
</table>
<div id="addon_settings"></div>
