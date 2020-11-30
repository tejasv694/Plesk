<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td>

<div class="Heading1">%%LNG_Stats_UserStatistics%%</div>

<script>
	var TabSize = 2;
</script>

<div>
	<br>

	<ul id="tabnav">
		<li><a href="#" class="active" onClick="ShowTab(1)" id="tab1">%%LNG_Stats_UserStatistics%%</a></li>
		<li><a href="#" onClick="ShowTab(2)" id="tab2">%%LNG_UserStatistics_Snapshot_EmailsSent%%</a></li>
	</ul>

</div>

<div id="div1">
	<div class="body pageinfo">
		<br>%%GLOBAL_SummaryIntro%%
		<br><br>
	</div>
	<table width="100%" border="0">
		<tr>
			<td width="45%" valign="top" rowspan="2">
				<table border=0 width="100%" class="Text" cellspacing="0">
					<tr class="Heading3">
						<td colspan="2" nowrap align="left">
							%%LNG_UserStatisticsSnapshot%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left">
							&nbsp;%%LNG_Stats_UserCreateDate%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_UserCreateDate%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left">
							&nbsp;%%LNG_Stats_UserLastLoggedIn%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_LastLoggedInDate%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left">
							&nbsp;%%LNG_UserLastNewsletterSent%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_LastNewsletterSentDate%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left">
							&nbsp;%%LNG_Stats_TotalLists%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_ListsCreated%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left">
							&nbsp;%%LNG_UserAutorespondersCreated%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_AutorespondersCreated%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left">
							&nbsp;%%LNG_UserNewslettersSent%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_NewslettersSent%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left">
							&nbsp;%%LNG_Stats_TotalEmailsSent%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_EmailsSent%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							&nbsp;%%LNG_Stats_TotalOpens%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_TotalOpens%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							&nbsp;%%LNG_Stats_TotalUniqueOpens%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_UniqueOpens%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							&nbsp;%%LNG_Stats_TotalBounces%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_TotalBounces%%
						</td>
					</tr>
				</table>

			</td>
			<td align="center" width="55%">
			 %%GLOBAL_SummaryChart%%
			</td>
		</tr>
	</table>
</div>


<div id="div2" style="display:none">
	%%GLOBAL_UsersSummaryPage%%
</div>


		</td>
	</tr>
</table>