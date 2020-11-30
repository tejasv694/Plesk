	<style>
		td.QuickView
		{
			background-color: #dbf3d1;
			padding: 10pt;
		}

		tr.QuickView td
		{
			background-color: #dbf3d1;
		}
	</style>
	<script>
		function ShowLog(id)
		{
			var basename = "addon_{$AddonId}_"+id;
			var tr = document.getElementById(basename);
			var trQ = document.getElementById('Show_' + basename);
			var tdQ = document.getElementById('ShowCell_' + basename);
			var img = document.getElementById('expand'+id);

			if(img.src.indexOf("plus.gif") > -1)
			{
				img.src = "{$TemplateUrl}images/minus.gif";

				for(i = 0; i < tr.childNodes.length; i++)
				{
					if(tr.childNodes[i].style != null)
						tr.childNodes[i].style.backgroundColor = "#dbf3d1";
				}

				$(trQ).find('.QuickView').load('{$AdminUrl}&AJAX=1&Action=ViewLog&id='+id, {}, function() {
					trQ.style.display = "";
				});
			}
			else
			{
				img.src = "{$TemplateUrl}images/plus.gif";

				for(i = 0; i < tr.childNodes.length; i++)
				{
					if(tr.childNodes[i].style != null)
						tr.childNodes[i].style.backgroundColor = "";
				}
				trQ.style.display = "none";
			}
		}

		function DeleteLogs()
		{
			if (!confirm('{$lang.Addon_systemlog_Alert_DeleteSelected}')) {
				return false;
			}

			frm = document.getElementById('LogForm');
			logs_found = 0;
			for (var i=0;i < frm.length;i++)
			{
				fldObj = frm.elements[i];
				if (fldObj.type == 'checkbox')
				{
					if (fldObj.checked) {
						logs_found++;
						break;
					}
				}
			}

			if (logs_found == 0) {
				alert('{$lang.Addon_systemlog_ChooseLogsToDelete_Alert}');
				return false;
			}

			frm.action = frm.action + '&Action=Delete';
			frm.submit();
		}

		function DeleteAllLogs()
		{
			// Please choose at least one entry to delete.
			if (confirm('{$lang.Addon_systemlog_Alert_DeleteAll}')) {
				frm = document.getElementById('LogForm');
				frm.action = frm.action + '&Action=DeleteAll';
				frm.submit();

				return true;
			}
			return false;
		}
	</script>
	<form id="LogForm" method="post" action="{$AdminUrl}">
	<table width="100%" border="0" class="text" cellpadding="2" cellspacing="0">
		<tr>
			<td colspan="2"class="Heading1">{$lang.Addon_systemlog_Logs}</td>
		</tr>
		<tr>
			<td colspan="2" class="body pageinfo"><p>{$lang.Addon_systemlog_Logs_Introduction}</p></td>
		</tr>
		<tr>
			<td colspan="2">
				{$FlashMessages}
			</td>
		</tr>
		<tr>
			<td valign="bottom">
				<div>
					<input type="button" value="{$lang.Addon_systemlog_DeleteButton}" onClick="return DeleteLogs();" class="SmallButton" style="width: 160px;">&nbsp;
					<input type="button" value="{$lang.Addon_systemlog_DeleteAllButton}" onClick="return DeleteAllLogs();" class="SmallButton" style="width: 160px;">&nbsp;
				</div>
			</td>
			<td align="right">
				<div align="right">
					{$Paging}
				</div>
			</td>
		</tr>
	</table>
	<table class="text" width="100%" cellspacing="0" cellpadding="2" border="0" style="margin-top:10px;">
		<tr class="Heading3">
			<td width="1" align="center">
				<input type="checkbox" onclick="javascript: toggleAllCheckboxes(this);" name="toggle"/>
			</td>
			<td>&nbsp;</td>
			<td>
				{$lang.Addon_systemlog_Severity}
			</td>
			<td>
				&nbsp;
			</td>
			<td>
				{$lang.Addon_systemlog_Summary}
			</td>
			<td>
				{$lang.Addon_systemlog_Module}
			</td>
			<td>
				{$lang.Addon_systemlog_Date}
			</td>
		</tr>
	{foreach from=$logsList key=k item=logentry}
		<tr class="GridRow" id="{$logentry.rowid}">
			<td width="1">
				<input type="checkbox" name="logids[]" value="{$logentry.logid}">
			</td>
			<td width="1">
				<img src="{$TemplateUrl}images/{$logentry.image}" width="18" height="18">
			</td>
			<td width="80">
				{$logentry.severity}
			</td>
			<td align="center" style="width: 15px;">
				<a href="#" onClick="ShowLog('{$logentry.logid}'); return false;"><img id="expand{$logentry.logid}" src="{$TemplateUrl}images/plus.gif" border="0"></a>
			</td>
			<td>
				{$logentry.logsummary}
			</td>
			<td>
				{$logentry.logmodule}
			</td>
			<td>
				{$logentry.logdate}
			</td>
		</tr>
		<tr id="Show_{$logentry.rowid}" style="display: none;">
			<td colspan="3">&nbsp;</td>
			<td id="ShowCell_{$logentry.rowid}" class="QuickView" colspan="4"></td>
			<td colspan="2">&nbsp;</td>
		</tr>
	{/foreach}
</table>
</form>

