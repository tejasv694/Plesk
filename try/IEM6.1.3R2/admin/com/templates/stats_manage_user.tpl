<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">%%GLOBAL_Heading%%</td>
	</tr>
	<tr>
		<td class="body pageinfo"><p>%%GLOBAL_Introduction%%</p></td>
	</tr>
	<tr>
		<td>
			%%GLOBAL_Message%%
		</td>
	</tr>
	<tr>
		<td class="body">
			%%TPL_Paging%%
			<table border=0 cellspacing="1" cellpadding="2" width=100% class="Text">
				<tr class="Heading3">
					<td width="4%">&nbsp;</td>
					<td width="16%">
						%%LNG_UserName%%
					</td>
					<td width="15%">
						%%LNG_Stats_UserCreateDate%%
					</td>
					<td width="10%">
						%%LNG_FullName%%
					</td>
					<td width="10%">
						%%LNG_Stats_TotalLists%%
					</td>
					<td width="15%">
						%%LNG_Stats_TotalSubscribers%%
					</td>
					<td width="15%">
						%%LNG_Stats_TotalEmailsSent%%
					</td>
					<td width="15%">
						%%LNG_Action%%
					</td>
				</tr>
			%%TPL_Stats_Manage_Row_User%%
			</table>
			%%TPL_Paging_Bottom%%
		</td>
	</tr>
</table>

<script>
	function ConfirmDelete(StatsID, StatsType) {
		if (!StatsID) {
			return false;
		}
		if (confirm("%%LNG_DeleteStatsPrompt%%")) {
			document.location='index.php?Page=Stats&Action=Delete&id=' + StatsID + '&type=' + StatsType;
		}
	}
</script>
