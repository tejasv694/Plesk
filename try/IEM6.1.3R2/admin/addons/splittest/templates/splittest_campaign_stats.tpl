<script>
	var TabSize = 5;
</script>

<h2 class="Heading1" style="margin:0; padding:0;">Split Test Statistics for &quot;{$statsDetails.splitname}&quot;</h2>

<div>
	<br>

	<ul id="tabnav">
		<li><a href="#" class="active" onClick="ShowTab(1); return false;" id="tab1">%%LNG_NewsletterStatistics_Snapshot%%</a></li>
		<li><a href="#" onClick="ShowTab(2); return false;" id="tab2">%%LNG_NewsletterStatistics_Snapshot_OpenStats%%</a></li>
		<li><a href="#" onClick="ShowTab(3); return false;" id="tab3">%%LNG_NewsletterStatistics_Snapshot_LinkStats%%</a></li>
		<li><a href="#" onClick="ShowTab(4); return false;" id="tab4">%%LNG_NewsletterStatistics_Snapshot_BounceStats%%</a></li>
		<li><a href="#" onClick="ShowTab(5); return false;" id="tab5">%%LNG_NewsletterStatistics_Snapshot_UnsubscribeStats%%</a></li>
	</ul>

</div>

<div id="div1">
	<br />
	{capture name=winner_summary}
		{if $statsDetails.campaign_winner_type == 'None'}
			<div class="FlashError">
				<img src="images/error.gif" width="18" height="18" align="left" class="FlashError" />
				{$lang.Addon_splittest_WonNone}
			</div>
		{else}
			<div class="FlashSuccess">
				<img src="images/success.gif" width="18" height="18" align="left" class="FlashSuccess" />
				{$statsDetails.winner_message|stripslashes}
			</div>
		{/if}
	{/capture}
	<table border="0" width="100%" cellspacing="0" cellpadding="0">
	  <tr class="Heading3">
	  <td colspan="2" nowrap="nowrap" align="left">
		{$lang.Addon_splittest_Campaign_Statistics}
	  </td>
	</tr>

	<tr>
		<td valign="top">
			{$statsDetails.summary_chart}
			{$winner_summary}
		</td>
	</tr>

	<tr>
		<td width="100%" valign="top">
		  <table border="0" width="100%" cellspacing="0" cellpadding="0">
			<tr class="Heading3">
				<td>{$lang.Addon_splittest_Stats_Snapshot_Heading}</td>
			</tr>
			<tr>
				<td valign="top" rowspan="2">
					<table border="0" class="Text" cellspacing="0" cellpadding="0" width="100%" style="margin:0;">
						<tr class="GridRow">
							<td width="150" height="22" nowrap="nowrap" align="left">
								&nbsp;{$lang.Addon_splittest_Stats_SplitName}
							</td>
							<td width="*" nowrap="nowrap" align="left">
								{$statsDetails.splitname}
							</td>
						</tr>
						<tr class="GridRow">
							<td width="150" height="22" nowrap="nowrap" align="left">
								&nbsp;{$lang.Addon_splittest_Stats_SplitType}
							</td>
							<td>
								{if $statsDetails.splittype == 'percentage'}
									<span class="HelpText" onMouseOut="HideHelp('active_description');" onMouseOver="ShowQuickHelp('active_description', '{$lang.Addon_splittest_Manage_SplitType_Percentage}', '{$lang.HLP_Addon_splittest_Splittype_Percentage}');">{$lang.Addon_splittest_Manage_SplitType_Percentage}</span>
								{else}
									<span class="HelpText" onMouseOut="HideHelp('active_description');" onMouseOver="ShowQuickHelp('active_description', '{$lang.Addon_splittest_Manage_SplitType_Distributed}', '{$lang.HLP_Addon_splittest_Splittype_Distributed}');">{$lang.Addon_splittest_Manage_SplitType_Distributed}</span>
								{/if}
								<div style="font-weight:normal" id="active_description" style="display:none;"></div>
							</td>
						</tr>
						<tr class="GridRow">
							<td width="150" height="22" nowrap="nowrap" align="left" valign="top">
								&nbsp;{$lang.Addon_splittest_Stats_ListNames}
							</td>
							<td width="*" height="22" nowrap="nowrap" align="left" valign="top">
								{foreach from=$statsDetails.lists item=list id=sequence}
									<a href="index.php?Page=Subscribers&Action=Manage&Lists[]={$list.listid}">{$list.name}</a>{if !$sequence.last}, {/if}
								{/foreach}
							</td>
						</tr>
						<tr class="GridRow">
							<td width="150" height="22" nowrap="nowrap" align="left" valign="top">
								&nbsp;{$lang.Addon_splittest_Stats_CampaignNames}
							</td>
							<td width="*" height="22" nowrap="nowrap" align="left" valign="top">
								{foreach from=$statsDetails.campaign_statistics.rankings.weighted item=pair id=sequence}
									{capture name=id}{$pair|key}{/capture}
									{alias name=campaign from=$statsDetails.campaigns.$id}
									<a title="{$lang.Addon_splittest_Stats_ViewNewsletterStats}" href="index.php?Page=Stats&Action=Newsletters&SubAction=ViewSummary&id={$campaign.stats_newsletters.statid}">{$campaign.campaign_name}</a>{if !$sequence.last}, {/if} 
								{/foreach}
							</td>
						</tr>
						<tr class="GridRow">
							<td width="150" height="22" nowrap="nowrap" align="left">
								&nbsp;{$lang.Addon_splittest_Stats_DateStarted}
							</td>
							<td width="*" nowrap="nowrap" align="left">
								{$statsDetails.starttime|dateformat,$DateFormat}
							</td>
						</tr>
						<tr class="GridRow">
							<td width="150" height="22" nowrap="nowrap" align="left">
								&nbsp;{$lang.NewsletterStatistics_FinishSending}
							</td>
							<td width="*" nowrap="nowrap" align="left">
								{if $statsDetails.finishtime == 0}
									{$lang.Addon_splittest_Stats_FinishTime_NotFinished}
								{else}
									{$statsDetails.finishtime|dateformat,$DateFormat}
								{/if}
							</td>
						</tr>
						<tr class="GridRow">
							<td width="150" height="22" nowrap="nowrap" align="left">
								&nbsp;{$lang.Addon_splittest_TotalSendSize}
							</td>
							<td width="*" nowrap="nowrap" align="left">
								{$statsDetails.total_recipient_count|number_format}
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</td>
	  </tr>
	 </table>
	 <br/>
 </div>


<!-- Email Open Rates -->
<div id="div2" style="display:none;">
	<br/>
	<table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text">
		<tr class="Heading3">
		  <td colspan="2" nowrap="nowrap" align="left">
				{$lang.Addon_splittest_SplittestTitle} {$lang.NewsletterStatistics_Snapshot_OpenStats}
			</td>
		</tr>

		<tr>
			<td width="100%" valign="top">
				{$statsDetails.openrate_chart}
				{$winner_summary}
			</td>
		</tr>

		<tr>
		  <td valign="top" width="100%">
			  <table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text">
				<tr class="Heading3">
					<td width="200" height="22" align="left" valign="top">
						<b>{$lang.Addon_splittest_Stats_EmailCampaigns}</b>
					</td>
					<td align="center">{$lang.Addon_splittest_Stats_TotalOpens}</td>
					<td align="center">{$lang.Addon_splittest_Stats_UniqueOpens}</td>
					<td align="center" style="border-right: 2px solid #EDECEC;">{$lang.Addon_splittest_Stats_UniqueOpens} (%)</td>
					<td align="center">{$lang.Addon_splittest_Stats_TotalRecipients}</td>
					<td align="center">{$lang.Addon_splittest_Stats_TotalRecipients} (%)</td>
					<td align="center">{$lang.Addon_splittest_TotalSendSize}</td>
				</tr>
				{foreach from=$statsDetails.campaign_statistics.rankings.emailopens item=pair}
				<tr class="GridRow">
					{capture name=id}{$pair|key}{/capture}
					{alias name=campaign from=$statsDetails.campaigns.$id}
					<td>
						&nbsp;<a href="index.php?Page=Stats&Action=Newsletters&SubAction=ViewSummary&id={$campaign.stats_newsletters.statid}&tab=3" target="_blank">{$campaign.campaign_name}</a>
					</td>
					<td align="center">{$campaign.stats_newsletters.emailopens}</td>
					<td align="center">{$campaign.stats_newsletters.emailopens_unique}</td>
					<td align="center" style="border-right: 2px solid #EDECEC;">{$campaign.stats_newsletters.percent_emailopens_unique}</td>
					<td align="center">{$campaign.stats_newsletters.recipients|number_format}</td>
					<td align="center">{$campaign.stats_newsletters.final_percent_emailopens_unique}</td>
					<td align="center">{$statsDetails.total_recipient_count|number_format} {$lang.Addon_splittest_Stats_Recipient_s}</td>
				</tr>
				{/foreach}
			  </table>
			</td>
		 </tr>
	</table>
</div>

<!-- Click Rates -->
<div id="div3" style="display:none;">
	<br/>
	<table border="0" width="100%">
	  <tr>
		<td valign="top" width="100%">
		  <table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text">
			<tr class="Heading3">
				<td colspan="4" nowrap="nowrap" align="left">
					{$lang.Addon_splittest_SplittestTitle} {$lang.NewsletterStatistics_Snapshot_LinkStats}
				</td>
			</tr>

			<tr>
				<td valign="top">
					{$statsDetails.clickrate_chart}
					{$winner_summary}
				</td>
			</tr>

			<tr>
			  <td width="100%">
				<table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text">
					<tr class="Heading3">
						<td width="200" height="22" align="left" valign="top">
							<b>{$lang.Addon_splittest_Stats_EmailCampaigns}</b>
						</td>
						<td align="center">{$lang.Addon_splittest_Stats_UniqueClicks}</td>
						<td align="center">{$lang.Addon_splittest_Stats_UniqueClicks} (%)</td>
						<td align="center" style="border-right: 2px solid #EDECEC;">{$lang.Addon_splittest_Stats_TotalClicks}</td>
						<td align="center">{$lang.Addon_splittest_Stats_TotalRecipients}</td>
						<td align="center">{$lang.Addon_splittest_Stats_TotalRecipients} (%)</td>
						<td align="center">{$lang.Addon_splittest_TotalSendSize}</td>
					</tr>
					{foreach from=$statsDetails.campaign_statistics.rankings.linkclicks item=pair}
					<tr class="GridRow">
						{capture name=id}{$pair|key}{/capture}
						{alias name=campaign from=$statsDetails.campaigns.$id}
						<td>
							&nbsp;<a href="index.php?Page=Stats&Action=Newsletters&SubAction=ViewSummary&id={$campaign.stats_newsletters.statid}&tab=3" target="_blank">{$campaign.campaign_name}</a>
						</td>
						<td align="center">{$campaign.stats_newsletters.linkclicks_unique}</td>
						<td align="center">{$campaign.stats_newsletters.percent_linkclicks_unique}</td>
						<td align="center" style="border-right: 2px solid #EDECEC;">{$campaign.stats_newsletters.linkclicks}</td>
						<td align="center">{$campaign.stats_newsletters.recipients|number_format}</td>
						<td align="center">{$campaign.stats_newsletters.final_percent_linkclicks_unique}</td>
						<td align="center">{$statsDetails.total_recipient_count|number_format} {$lang.Addon_splittest_Stats_Recipient_s}</td>
					</tr>
					{/foreach}
				</table>
			  </td>
		  </table>
		</td>
	  </tr>
	</table>
</div>

<!-- Bounce Count -->
<div id="div4" style="display:none;">
	<br/>
	<table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text">
		<tr class="Heading3">
			<td colspan="4" nowrap="nowrap" align="left">
				{$lang.Addon_splittest_SplittestTitle} {$lang.NewsletterStatistics_Snapshot_BounceStats}
			</td>
		</tr>

		<tr>
			<td width="100%" valign="top">
				{$statsDetails.bouncerate_chart}
				{$winner_summary}
			</td>
		</tr>

		<tr>
			<td>
			  <table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text">
				<tr class="Heading3">
					<td width="200" height="22" align="left" valign="top">
						<b>{$lang.Addon_splittest_Stats_EmailCampaigns}</b>
					</td>
					<td align="center">{$lang.BounceSoft}</td>
					<td align="center">{$lang.BounceHard}</td>
					<td align="center">{$lang.BounceUnknown}</td>
					<td align="center" style="border-right: 2px solid #EDECEC;">{$lang.Addon_splittest_Stats_TotalBounces} (%)</td>
					<td align="center">{$lang.Addon_splittest_Stats_TotalRecipients}</td>
					<td align="center">{$lang.Addon_splittest_Stats_TotalRecipients} (%)</td>
					<td align="center">{$lang.Addon_splittest_TotalSendSize}</td>
				</tr>
				{foreach from=$statsDetails.campaign_statistics.rankings.bouncecount_total item=pair}
				<tr class="GridRow">
					{capture name=id}{$pair|key}{/capture}
					{alias name=campaign from=$statsDetails.campaigns.$id}
					<td>
						&nbsp;<a href="index.php?Page=Stats&Action=Newsletters&SubAction=ViewSummary&id={$campaign.stats_newsletters.statid}&tab=4" target="_blank">{$campaign.campaign_name}</a>
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
		    </table>
		</td>
	  </tr>
	</table>
</div>


<!-- Unsubscribes -->
<div id="div5" style="display:none;">
	<br/>
	<table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text">
		<tr class="Heading3">
			<td colspan="4" nowrap="nowrap" align="left">
				{$lang.Addon_splittest_SplittestTitle} {$lang.Newsletter_Summary_Graph_unsubscribechart}
			</td>
		</tr>

		<tr>
			<td width="65%" valign="top">
				{$statsDetails.unsubscribes_chart}
				{$winner_summary}
			</td>
		</tr>

		<tr>
			<td>
				<table border="0" cellspacing="0" cellpadding="0" width="100%" class="Text">
					<tr class="Heading3">
						<td width="200" height="22" align="left" valign="top">
							<b>{$lang.Addon_splittest_Stats_EmailCampaigns}</b>
						</td>
						<td align="center">{$lang.UnsubscribeCount}</td>
						<td align="center" style="border-right: 2px solid #EDECEC;">{$lang.Stats_TotalUnsubscribes} (%)</td>
						<td align="center">{$lang.Addon_splittest_Stats_TotalRecipients}</td>
						<td align="center">{$lang.Addon_splittest_Stats_TotalRecipients} (%)</td>
						<td align="center">{$lang.Addon_splittest_TotalSendSize}</td>
					</tr>
					{foreach from=$statsDetails.campaign_statistics.rankings.unsubscribes item=pair}
						{capture name=id}{$pair|key}{/capture}
						{alias name=campaign from=$statsDetails.campaigns.$id}
						<tr class="GridRow">
							<td>
								&nbsp;<a href="index.php?Page=Stats&Action=Newsletters&SubAction=ViewSummary&id={$campaign.stats_newsletters.statid}&tab=5" target="_blank">{$campaign.campaign_name}</a>
							</td>
							<td align="center">{$campaign.stats_newsletters.unsubscribecount}</td>
							<td align="center" style="border-right: 2px solid #EDECEC;">{$campaign.stats_newsletters.percent_unsubscribecount}</td>
							<td align="center">{$campaign.stats_newsletters.recipients|number_format}</td>
							<td align="center">{$campaign.stats_newsletters.final_percent_unsubscribecount}</td>
							<td align="center">{$statsDetails.total_recipient_count|number_format} {$lang.Addon_splittest_Stats_Recipient_s}</td>
						</tr>
					{/foreach}
		  		</table>
			</td>
	  	</tr>
	</table>
</div>
