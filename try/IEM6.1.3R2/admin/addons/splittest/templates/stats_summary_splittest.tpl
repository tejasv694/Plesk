<html>
	<head>
		<link rel="stylesheet" href="../../includes/styles/print.css" type="text/css" />
		<script src="../../includes/js/jquery.js"></script>

		<script>
			$(window).load(function() {
				$('table.Text th').addClass('odd');
				$('table.Text tr:even').addClass('odd');
			});
		</script>

	{if $subaction == 'print'}
		<body onLoad="window.focus();window.print();">
	{else}
		<body onLoad="">
	{/if}


{foreach from=$statsData item=Statistics}
{if $snapshot}
<fieldset>
{foreach from=$Statistics item=statsDetails}
<legend>'{$statsDetails.splitname}' {$lang.Addon_splittest_Campaign_Statistics}</legend>
<table cellspacing="0" style="width: 100%;" cellspacing="0">
	<tr>
		<tr>
			<td>
			{$statsDetails.summary_chart}
			</td>
		</tr>
		<td>
			<table border="0" width="100%" class="Text" cellspacing="0">
				<tr class="GridRow">
					<td width="30%" height="22" nowrap="nowrap" align="left">
						&nbsp;{$lang.Addon_splittest_Stats_SplitName}
					</td>
					<td width="70%" nowrap="nowrap" align="left">
						{$statsDetails.splitname}
					</td>
				</tr>
				<tr class="GridRow">
					<td width="30%" height="22" nowrap="nowrap" align="left" valign="top">
						&nbsp;{$lang.Addon_splittest_Stats_SplitType}
					</td>
					<td width="70%" nowrap="nowrap" align="left">
						{if $statsDetails.splittype == 'percentage'}
							{$lang.Addon_splittest_Manage_SplitType_Percentage}
						{else}
							{$lang.Addon_splittest_Manage_SplitType_Distributed}
						{/if}
					</td>
				</tr>
				<tr class="GridRow">
					<td width="30%" height="22" nowrap="nowrap" align="left" valign="top">
						&nbsp;{$lang.Addon_splittest_Stats_ListNames}
					</td>
					<td width="70%" height="22" nowrap="nowrap" align="left" valign="top">
						{foreach from=$statsDetails.lists item=list id=sequence}
							{$list.name}{if !$sequence.last}, {/if}
						{/foreach}
					</td>
				</tr>
				<tr class="GridRow">
					<td width="30%" height="22" nowrap="nowrap" align="left" valign="top">
						&nbsp;{$lang.Addon_splittest_Stats_CampaignNames}
					</td>
					<td width="70%" height="22" nowrap="nowrap" align="left" valign="top">
						{$statsDetails.campaign_names}
					</td>
				</tr>
				<tr class="GridRow">
					<td width="30%" height="22" nowrap="nowrap" align="left">
						&nbsp;{$lang.Addon_splittest_Winner}
					</td>
					<td width="70%" nowrap="nowrap" align="left">
						{$statsDetails.winner_message}
					</td>
				</tr>
				<tr class="GridRow">
					<td width="30%" height="22" nowrap="nowrap" align="left">
						&nbsp;{$lang.NewsletterStatistics_StartSending}
					</td>
					<td width="70%" nowrap="nowrap" align="left">
						{$statsDetails.starttime|dateformat,$DateFormat}
					</td>
				</tr>
				<tr class="GridRow">
					<td width="30%" height="22" nowrap="nowrap" align="left">
						&nbsp;{$lang.NewsletterStatistics_FinishSending}
					</td>
					<td width="70%" nowrap="nowrap" align="left">
						{$statsDetails.finishtime|dateformat,$DateFormat}
					</td>
				</tr>
				<tr class="GridRow">
					<td width="30%" height="22" nowrap="nowrap" align="left" valign="top">
						&nbsp;{$lang.Addon_splittest_SendSize}
					</td>
					<td width="70%" nowrap="nowrap" align="left">
						{$statsDetails.total_recipient_count|number_format}
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
{/foreach}
</fieldset>
{/if}

{if $open}
<fieldset>
<legend>{$lang.NewsletterStatistics_Snapshot_OpenStats}</legend>
{foreach from=$Statistics item=statsDetails}
<table>
	<tr>
		<td valign="top">
			{$statsDetails.openrate_chart}
		</td>
	</tr>
		<tr>
		<td valign="top" width="100%">
			<tr>
				<td valign="top"	width="100%">
					<table border="0" width="100%" class="Text" cellspacing="0">
					<tr class="GridRow">
						<th height="22" nowrap="nowrap" align="left" valign="top">
							<b>&nbsp;{$lang.Addon_splittest_Stats_EmailCampaigns}</b>
						</th>
						<th align="center">{$lang.Addon_splittest_Stats_TotalOpens}</th>
						<th align="center">{$lang.Addon_splittest_Stats_UniqueOpens}</th>
						<th align="center" style="border-right: 2px solid #EDECEC;">{$lang.Addon_splittest_Stats_UniqueOpens} (%)</th>
						<th align="center">{$lang.Addon_splittest_Stats_TotalRecipients}</th>
						<th align="center">{$lang.Addon_splittest_Stats_TotalRecipients} (%)</th>
						<th align="center">{$lang.Addon_splittest_TotalSendSize}</th>
					</tr>
					<tr>
						{foreach from=$statsDetails.campaign_statistics.rankings.emailopens item=rankings}
						<tr>
							{foreach from=$rankings item=i key=campaignId}
								{foreach from=$statsDetails.campaigns key=id item=campaign}
									{if $id == $campaignId}
									<td align="left">
										<a title="'{$campaign.campaign_name}' - {$lang.Opens_Summary}" href="index.php?Page=Stats&Action=Newsletters&SubAction=ViewSummary&id={$campaign.stats_newsletters.statid}&tab=2">{$campaign.campaign_name}</a>
									</td>
									<td align="center">{$campaign.stats_newsletters.emailopens}</td>
									<td align="center">{$campaign.stats_newsletters.emailopens_unique}</td>
									<td align="center" style="border-right: 2px solid #EDECEC;">{$campaign.stats_newsletters.percent_emailopens_unique}</td>
									<td align="center">{$campaign.stats_newsletters.recipients|number_format}</td>
									<td align="center">{$campaign.stats_newsletters.final_percent_emailopens_unique}</td>
									<td align="center">{$statsDetails.total_recipient_count|number_format} {$lang.Addon_splittest_Stats_Recipient_s}</td>
									{/if}
								{/foreach}
							{/foreach}
						</tr>
						{/foreach}
					</tr>
				</table>
			</td>
			</tr>
		</table>
		</td>
	</tr>
</table>
{/foreach}
</fieldset>
{/if}

{if $click}
<fieldset>
<legend>{$lang.NewsletterStatistics_Snapshot_LinkStats}</legend>
{foreach from=$Statistics item=statsDetails}
	<table border="0" width="100%">
		<tr>
			<td width="100%" valign="top">
			{$statsDetails.clickrate_chart}
		</td>
		<tr>
		<td valign="top" colspan="3"	width="100%">
			<table border="0" width="100%" class="Text" cellspacing="0">
			<tr class="GridRow">
				<th width="100" height="22" nowrap="nowrap" align="left" valign="top">
					<b>&nbsp;{$lang.Addon_splittest_Stats_EmailCampaigns}</b>
				</th>
				<th align="center">{$lang.Addon_splittest_Stats_UniqueClicks}</th>
				<th align="center">{$lang.Addon_splittest_Stats_UniqueClicks} (%)</th>
				<th align="center" style="border-right: 2px solid #EDECEC;">{$lang.Addon_splittest_Stats_TotalClicks}</th>
				<th align="center">{$lang.Addon_splittest_Stats_TotalRecipients}</th>
				<th align="center">{$lang.Addon_splittest_Stats_TotalRecipients} (%)</th>
				<th align="center">{$lang.Addon_splittest_TotalSendSize}</th>
			</tr>
			<tr>
				{foreach from=$statsDetails.campaign_statistics.rankings.linkclicks item=rankings}
				<tr>
					{foreach from=$rankings item=i key=campaignId}
						{foreach from=$statsDetails.campaigns key=id item=campaign}
							{if $id == $campaignId}
							<td align="left" width="200">
								<a title="'{$campaign.campaign_name}' - {$lang.LinkClicks_Summary}" href="index.php?Page=Stats&Action=Newsletters&SubAction=ViewSummary&id={$campaign.stats_newsletters.statid}&tab=3">{$campaign.campaign_name}</a>
							</td>
							<td align="center">{$campaign.stats_newsletters.linkclicks_unique}</td>
							<td align="center">{$campaign.stats_newsletters.percent_linkclicks_unique}</td>
							<td align="center" style="border-right: 2px solid #EDECEC;">{$campaign.stats_newsletters.linkclicks}</td>
							<td align="center">{$campaign.stats_newsletters.recipients|number_format}</td>
							<td align="center">{$campaign.stats_newsletters.final_percent_linkclicks_unique}</td>
							<td align="center">{$statsDetails.total_recipient_count|number_format} {$lang.Addon_splittest_Stats_Recipient_s}</td>
							{/if}
						{/foreach}
					{/foreach}
				</tr>
				{/foreach}
			</tr>
			</table>
		</td>
	</tr>
</table>
{/foreach}
</fieldset>
{/if}

{if $bounce}
<fieldset>
<legend>{$lang.NewsletterStatistics_Snapshot_BounceStats}</legend>
{foreach from=$Statistics item=statsDetails}
	<table>
		<tr>
			<td width="100%" valign="top">
			{$statsDetails.bouncerate_chart}
		</td>
		</tr>
		<tr>
		<td width="100%" valign="top">
			<table border="0" width="100%" class="Text" cellspacing="0">
			<tr class="GridRow">
				<th height="22" nowrap="nowrap" align="left" valign="top">
					<b>&nbsp;{$lang.Addon_splittest_Stats_EmailCampaigns}</b>
				</th>
				<th align="center">{$lang.BounceSoft}</th>
				<th align="center">{$lang.BounceHard}</th>
				<th align="center">{$lang.BounceUnknown}</th>
				<th align="center" style="border-right: 2px solid #EDECEC;">{$lang.Addon_splittest_Stats_TotalBounces} (%)</th>
				<th align="center">{$lang.Addon_splittest_Stats_TotalRecipients}</th>
				<th align="center">{$lang.Addon_splittest_Stats_TotalRecipients} (%)</th>
				<th align="center">{$lang.Addon_splittest_TotalSendSize}</th>
			</tr>
			{foreach from=$statsDetails.campaigns key=id item=campaign}
				<tr>
					<td align="left">
						<a title="'{$campaign.campaign_name}' - {$lang.Newsletter_Summary_Graph_bouncechart}" href="index.php?Page=Stats&Action=Newsletters&SubAction=ViewSummary&id={$campaign.stats_newsletters.statid}&tab=4">{$campaign.campaign_name}</a>
					</td>
					<td align="center">{$campaign.stats_newsletters.bouncecount_soft}</td>
					<td align="center">{$campaign.stats_newsletters.bouncecount_hard}</td>
					<td align="center">{$campaign.stats_newsletters.bouncecount_unknown}</td>
					<td align="center" style="border-right: 2px solid #EDECEC;">{$campaign.stats_newsletters.percent_bouncecount_total}</td>
					<td align="center">{$campaign.stats_newsletters.recipients|number_format}</td>
					<td align="center">{$campaign.stats_newsletters.final_percent_bouncecount_total}</td>
					<td align="center">{$statsDetails.total_recipient_count|number_format} {$lang.Addon_splittest_Stats_Recipient_s}</td>
				</tr>
			{/foreach}
			</tr>
			</table>
		</td>
		</tr>
	</table>
{/foreach}
</fieldset>
{/if}

{if $unsubscribe}
<fieldset>
<legend>{$lang.Newsletter_Summary_Graph_unsubscribechart}</legend>
{foreach from=$Statistics item=statsDetails}
	<table>
		<tr>
			<td valign="top">
			{$statsDetails.unsubscribes_chart}
		</td>
		</tr>
		<tr>
		<td valign="top">
			<table border="0" width="100%" class="Text" cellspacing="0">
			<tr class="GridRow">
				<th width="100" height="22" nowrap="nowrap" align="left" valign="top">
					<b>&nbsp;{$lang.Addon_splittest_Stats_EmailCampaigns}</b>
				</th>
				<th align="center">{$lang.UnsubscribeCount}</th>
				<th align="center" style="border-right: 2px solid #EDECEC;">{$lang.Stats_TotalUnsubscribes} (%)</th>
				<th align="center">{$lang.Addon_splittest_Stats_TotalRecipients}</th>
				<th align="center">{$lang.Addon_splittest_Stats_TotalRecipients} (%)</th>
				<th align="center">{$lang.Addon_splittest_TotalSendSize}</th>
			</tr>
			<tr>
				{foreach from=$statsDetails.campaigns key=id item=campaign}
					<tr>
						<td align="left">
							<a title="'{$campaign.campaign_name}' - {$lang.NewsletterStatistics_Snapshot_UnsubscribeStats}" href="index.php?Page=Stats&Action=Newsletters&SubAction=ViewSummary&id={$campaign.stats_newsletters.statid}&tab=5">{$campaign.campaign_name}</a>
						</td>
						<td align="center">{$campaign.stats_newsletters.unsubscribecount}</td>
						<td align="center" style="border-right: 2px solid #EDECEC;">{$campaign.stats_newsletters.percent_unsubscribecount}</td>
						<td align="center">{$campaign.stats_newsletters.recipients|number_format}</td>
						<td align="center">{$campaign.stats_newsletters.final_percent_unsubscribecount}</td>
						<td align="center">{$statsDetails.total_recipient_count|number_format} {$lang.Addon_splittest_Stats_Recipient_s}</td>
					</tr>
				{/foreach}
			</tr>
			</table>
		</td>
		</tr>
	</table>
{/foreach}
</fieldset>
{/if}

{/foreach}

</body>

</html>
