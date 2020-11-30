<script>
$(document).ready(function() {
	$('#toggleCheck').click(function() {
		if ($('#toggleCheck')[0].checked) {
			$('input[type="checkbox"]').attr('checked','checked');
		} else {
			$('input[type="checkbox"]').attr('checked','');
		}
	});
});

</script>
<form action="{$AdminUrl}" method="post">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">%%LNG_Addon_surveys_TemplatesManage%%</td>
	</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Addon_surveys_TemplatesManageIntro%%
				</p>
			</td>
		</tr>
	<tr>
		<td>
			{$Message}
		</td>
	</tr>
	<tr>
		<td class="body">
			<table width="100%" border="0">
				<tr>
					<td>
						<div style="padding-top:10px; padding-bottom:10px">
							{$Add_Button}
							<input type="submit" value="%%LNG_DeleteSelected%%" class="Field" 	onclick="location.href='{$AdminUrl}&Action=Delete';return false;"	>
						</div>
						
					</td>
					<td align="right" valign="bottom">
						{$Paging}
					</td>
				</tr>
			</table>
			<table border=0 cellspacing="0" cellpadding="0" width=100% class="Text">
				<tr class="Heading3">
					<td width="5" nowrap align="center">
						<input type="checkbox" id="toggleCheck" class="UICheckboxToggleSelector" />
					</td>
					<td width="5">&nbsp;</td>
					<td width="80%" nowrap="nowrap">
						%%LNG_Name%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Name&Direction=Up'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Name&Direction=Down'><img src="images/sortdown.gif" border=0></a>
					</td>
					<td width="20%" nowrap="nowrap">
						%%LNG_Created%%&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Created&Direction=Up'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=%%PAGE%%&SortBy=Created&Direction=Down'><img src="images/sortdown.gif" border=0></a>
					</td>
					<td width="150">
						%%LNG_Action%%
					</td>
				</tr>
				{$Items}
			</table>
			{$Paging}
		</td>
	</tr>
</table>
</form>