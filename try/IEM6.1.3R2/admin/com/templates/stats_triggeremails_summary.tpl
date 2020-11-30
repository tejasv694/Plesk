<script>
	var TabSize = 7;

	function PreparePreview(id) {
		var openurl = 'index.php?Page=Newsletters&Action=View&id=' + id;
		window.open(openurl, 'pp');
	}

	function ChangeLink(page,column,sort) {
		chooselink_id = document.getElementById('chooselink');
		selected = chooselink_id.selectedIndex;
		linkid = chooselink_id[selected].value;
		REMOTE_parameters = '&link=' + linkid;
		REMOTE_admin_table($('#statsTriggerEmails_OpenTable'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_TableToken%%',page,column,sort);

		UpdateLinkSummary();
	}

	function ChangeBounceType() {}

	function UpdateLinkSummary() {
		/*
		if (document.getElementById('chooselink')) {
			chooselink_id = document.getElementById('chooselink');
			selected = chooselink_id.selectedIndex;
			linkid = chooselink_id[selected].value;
		} else {
			linkid = 'a';
		}

		// Update link stats
		$.get('remote_stats.php?Action=get_linkstats&link=' + linkid + '&token=%%GLOBAL_TableToken%%','',
			function(data) {
				eval(data);
				$('#totalclicks').html(linksjson.linkclicks);
				$('#clickthrough').html(linksjson.clickthrough);
				$('#averageclicks').html(linksjson.averageclicks);
				$('#uniqueclicks').html(linksjson.uniqueclicks);
			}
		);*/
	}

	$(function() {
		if ($('#adminTabletriggeremails_opens').size() != 0) {
			REMOTE_admin_table($('#adminTabletriggeremails_opens'), '', '', 'triggeremails_opens', '{$PAGE.session_token}', 1, 'opened', 'down');
		}

		if ($('#adminTabletriggeremails_links').size() != 0) {
			REMOTE_admin_table($('#adminTabletriggeremails_links'), '', '', 'triggeremails_links', '{$PAGE.session_token}', 1, 'email', 'up');
		}

		if ($('#adminTabletriggeremails_bounces').size() != 0) {
			REMOTE_admin_table($('#adminTabletriggeremails_bounces'), '', '', 'triggeremails_bounces', '{$PAGE.session_token}', 1, 'time', 'down');
		}

		if ($('#adminTabletriggeremails_unsubscribes').size() != 0) {
			REMOTE_admin_table($('#adminTabletriggeremails_unsubscribes'), '', '', 'triggeremails_unsubscribes', '{$PAGE.session_token}', 1, 'time', 'down');
		}

		if ($('#adminTabletriggeremails_forwards').size() != 0) {
			REMOTE_admin_table($('#adminTabletriggeremails_forwards'), '', '', 'triggeremails_forwards', '{$PAGE.session_token}', 1, 'email', 'up');
		}
	});
</script>
<div class="PageBodyContainer">
	<div class="Page_Header">
		<div class="Heading1">{$lang.TriggerEmails_Stats_Title}</div>
		<div class="Intro">{$lang.TriggerEamils_Stats_Page_Intro|sprintf,$record.triggeremailsname}</div>
	</div>

	{* ----- Tab navigations *}
		<div style="display: block; clear: both;">
			<br>
			<ul id="tabnav">
				<li><a href="#" {if $PAGE.whichtab == 1}class="active"{/if} onClick="ShowTab(1); return false;" id="tab1">{$lang.TriggerEmails_Stats_Tab_Snapshots}</a></li>
				<li><a href="#" {if $PAGE.whichtab == 2}class="active"{/if} onClick="ShowTab(2); return false;" id="tab2">{$lang.TriggerEmails_Stats_Tab_OpenStats}</a></li>
				<li><a href="#" {if $PAGE.whichtab == 3}class="active"{/if} onClick="ShowTab(3); return false;" id="tab3">{$lang.TriggerEmails_Stats_Tab_LinkStats}</a></li>
				<li><a href="#" {if $PAGE.whichtab == 4}class="active"{/if} onClick="ShowTab(4); return false;" id="tab4">{$lang.TriggerEmails_Stats_Tab_BounceStats}</a></li>
				<li><a href="#" {if $PAGE.whichtab == 5}class="active"{/if} onClick="ShowTab(5); return false;" id="tab5">{$lang.TriggerEmails_Stats_Tab_UnsubscribeStats}</a></li>
				<li><a href="#" {if $PAGE.whichtab == 6}class="active"{/if} onClick="ShowTab(6); return false;" id="tab6">{$lang.TriggerEmails_Stats_Tab_ForwardStats}</a></li>
				<li><a href="#" {if $PAGE.whichtab == 7}class="active"{/if} onClick="ShowTab(7); return false;" id="tab7">{$lang.TriggerEmails_Stats_Tab_SubscriberStats}</a></li>
				<li><a href="#" {if $PAGE.whichtab == 8}class="active"{/if} onClick="ShowTab(8); return false;" id="tab8">{$lang.TriggerEmails_Stats_Tab_FailedStats}</a></li>
			</ul>
		</div>
	{* ----- *}


	{* ----- Tabs *}
		{* ----- Tab 1: Snapshot *}
			<div id="div1" style="display: {if $PAGE.whichtab == 1}block{else}none{/if}; clear: both;">
				<div style="display: block; clear: both; padding-top:10px; padding-bottom:8px;">{$tabs.snapshot.intro}</div>
				<table width="100%" border="0" padding="0">
					<tr>
						<td width="45%" valign="top">
							<table border=0 width="100%" class="Text"  cellspacing="0">
								<tr class="Heading3">
									<td colspan="2" nowrap align="left">
										{$lang.TriggerEmails_Stats_Snapshots_Heading}
									</td>
								</tr>
								<tr class="GridRow">
									<td width="30%" height="22" nowrap align="left" valign="top">
										&nbsp;{$lang.NewsletterStatistics_SentTo}
									</td>
									<td width="70%" nowrap align="left">
										{$record.processed_totalsent}
									</td>
								</tr>
								<tr class="GridRow">
									<td width="30%" height="22" nowrap align="left" valign="top">
										&nbsp;{$lang.TriggerEmails_Stats_Snapshots_CreatedBy}
									</td>
									<td width="70%" nowrap align="left">
										<a href="mailto:{$record.owneremail}">{if trim($record.ownername) == ''}{$record.ownerusername}{else}{$record.ownername}{/if}</a>
									</td>
								</tr>
								<tr class="GridRow">
									<td width="30%" height="22" nowrap align="left" valign="top">
										&nbsp;{$lang.NewsletterStatistics_Opened}
									</td>
									<td width="70%" nowrap align="left">
										<a	title="{$lang.TriggerEmails_Stats_Snapshots_Tooltip_Open}" href="{$tabs.snapshot.url_open_url}" {if !$PAGE.unique_open}onclick="ShowTab(2); return false;"{/if}>{$tabs.snapshot.newsletter_totalopen}</a>
										/
										<a title="{$lang.TriggerEmails_Stats_Snapshots_Tooltip_UniqueOpen}" href="{$tabs.snapshot.url_openunique_url}" {if $PAGE.unique_open}onclick="ShowTab(2); return false;"{/if}>{$tabs.snapshot.newsletter_uniqueopen}</a>
									</td>
								</tr>
								<tr class="GridRow">
									<td width="30%" height="22" nowrap align="left" valign="top">
										&nbsp;{$lang.NewsletterStatistics_Bounced}
									</td>
									<td width="70%" nowrap align="left">
										{$tabs.snapshot.newsletter_bounce}
									</td>
								</tr>
							</table>
						</td>
						<td width="55%">{$tabs.snapshot.summary_chart}</td>
					</tr>
				</table>
			</div>
		{* ----- *}

		{* ----- Tab 2: Open *}
			<div id="div2" style="display: {if $PAGE.whichtab == 2}block{else}none{/if}; clear: both;">
				<div class="body" style="display: block; clear: both; padding-top:10px; padding-bottom:8px;">{$tabs.open.intro}</div>
				<div style="padding-bottom: 10px;">{$tabs.open.calendar}</div>

				{if !$record.processed_timeframe_emailopens_total}
					<div>{$tabs.open.message}</div>
				{else}
					<table width="100%" border="0" class="Text">
						<tr>
							<td valign=top width="250" rowspan="2">
								<div class="MidHeading" style="width:100%"><img src="images/m_stats.gif" width="20" height="20" align="absMiddle">&nbsp;{$lang.Opens_Summary}</div>
								<ul class="Text">
									<li>
										<span class="HelpText" onmouseover="ShowHelp('total_open_explain', '{$lang.TotalOpens}', '{$lang.Stats_TotalOpens_Description}');" onmouseout="HideHelp('total_open_explain');">{$lang.TotalOpens}{$tabs.open.email_opens_total}</span>
										<div id="total_open_explain" style="display:none;"></div>
									</li>
									<li>{$lang.MostOpens}{$tabs.open.most_open_date}</li>
									<li>
										<span class="HelpText" onmouseover="ShowHelp('total_uniqueopen_explain', '{$lang.TotalUniqueOpens}', '{$lang.Stats_TotalUniqueOpens_Description}');" onmouseout="HideHelp('total_uniqueopen_explain');">{$lang.TotalUniqueOpens}{$tabs.open.email_opens_unique}</span>
										<div id="total_uniqueopen_explain" style="display:none;"></div>
									</li>
									<li>{$lang.AverageOpens}{$tabs.open.average_opens}</li>
									<li>{$lang.OpenRate}{$tabs.open.open_rate}</li>
								</ul>
							</td>
							<td>{$tabs.open.open_chart}</td>
						</tr>
					</table>

					<div id="adminTabletriggeremails_opens"></div>
				{/if}
			</div>
		{* ----- *}

		{* ----- Tab 3: Links *}
			<div id="div3" style="display: {if $PAGE.whichtab == 3}block{else}none{/if}; clear: both;">
				<div class="body" style="display: block; clear: both; padding-top:10px; padding-bottom:8px;">{$tabs.links.intro}</div>

				<div style="padding-bottom: 10px;">{$tabs.links.calendar}</div>

				{if !$record.processed_timeframe_linkclicks_total}
					<div>{$tabs.links.message}</div>
				{else}
					<table width="100%" border="0" class="Text">
						<tr>
							<td valign=top width="250" rowspan="2">
								<div class="MidHeading" style="width:100%"><img src="images/m_stats.gif" width="20" height="20" align="absMiddle">&nbsp;{$lang.LinkClicks_Summary}</div>
								<ul class="Text">
									<li>{$lang.Stats_TotalClicks}: {$tabs.links.linkclicks_total}</li>
									<li>{$lang.Stats_TotalUniqueClicks}: {$tabs.links.linkclicks_individuals}</li>
									<li>{$lang.Stats_UniqueClicks}: {$tabs.links.linkclicks_unique}</li>
									<li>{$lang.Stats_MostPopularLink}: <a href="{$tabs.links.most_popular_link}" title="{$tabs.links.most_popular_link}" target="_blank">{$tabs.links.most_popular_link_short}</a></li>
									<li>{$lang.Stats_AverageClicks}: {$tabs.links.average_clicks}</li>
									<li>{$lang.Stats_Clickthrough}: {$tabs.links.click_through}</li>
								</ul>
							</td>
							<td>{$tabs.links.link_chart}</td>
						</tr>
					</table>

			        <div id="adminTabletriggeremails_links"></div>
				{/if}
			</div>
		{* ----- *}

		{* ----- Tab 4: Bounce *}
			<div id="div4" style="display: {if $PAGE.whichtab == 4}block{else}none{/if}; clear: both;">
				<div class="body" style="display: block; clear: both; padding-top:10px; padding-bottom:8px;">{$tabs.bounces.intro}</div>

				<div style="padding-bottom: 10px;">{$tabs.bounces.calendar}</div>

				{if !$record.processed_timeframe_bounces}
					<div>{$tabs.bounces.message}</div>
				{else}
					<table width="100%" border="0" class="Text">
						<tr>
							<td valign=top width="250" rowspan="2">
								<div class="MidHeading" style="width:100%"><img src="images/m_stats.gif" width="20" height="20" align="absMiddle">&nbsp;{$lang.Bounce_Summary}</div>
								<ul class="Text">
									<li>{$lang.Stats_TotalBounces}{$tabs.bounces.bounces_total}</li>
									<li>{$lang.Stats_TotalSoftBounces}{$tabs.bounces.bounces_soft}</li>
									<li>{$lang.Stats_TotalHardBounces}{$tabs.bounces.bounces_hard}</li>
								</ul>
							</td>
							<td>{$tabs.bounces.bounce_chart}</td>
						</tr>
					</table>

			        <div id="adminTabletriggeremails_bounces"></div>
				{/if}
			</div>
		{* ----- *}

		{* ----- Tab 5: Unsubscribe *}
			<div id="div5" style="display: {if $PAGE.whichtab == 5}block{else}none{/if}; clear: both;">
				<div class="body" style="display: block; clear: both; padding-top:10px; padding-bottom:8px;">{$tabs.unsubscribes.intro}</div>

				<div style="padding-bottom: 10px;">{$tabs.unsubscribes.calendar}</div>

				{if !$record.processed_timeframe_unsubscribes}
					<div>{$tabs.unsubscribes.message}</div>
				{else}
					<table width="100%" border="0" class="Text">
						<tr>
							<td valign=top width="250" rowspan="2">
								<div class="MidHeading" style="width:100%"><img src="images/m_stats.gif" width="20" height="20" align="absMiddle">&nbsp;{$lang.Unsubscribe_Summary}</div>
								<ul class="Text">
									<li>{$lang.Stats_TotalUnsubscribes}:{$tabs.unsubscribes.unsubscribes_total}</li>
									<li>{$lang.Stats_MostUnsubscribes}:{$tabs.unsubscribes.unsubscribes_most}</li>
								</ul>
							</td>
							<td>{$tabs.unsubscribes.unsubscribe_chart}</td>
						</tr>
					</table>

			        <div id="adminTabletriggeremails_unsubscribes"></div>
				{/if}
			</div>
		{* ----- *}

		{* ----- Tab 6: Forward *}
			<div id="div6" style="display: {if $PAGE.whichtab == 6}block{else}none{/if}; clear: both;">
				<div class="body" style="display: block; clear: both; padding-top:10px; padding-bottom:8px;">{$tabs.forwards.intro}</div>

				<div style="padding-bottom: 10px;">{$tabs.forwards.calendar}</div>

				{if !$record.processed_timeframe_forwards}
					<div>{$tabs.forwards.message}</div>
				{else}
					<table width="100%" border="0" class="Text">
						<tr>
							<td valign=top width="250" rowspan="2">
								<div class="MidHeading" style="width:100%"><img src="images/m_stats.gif" width="20" height="20" align="absMiddle">&nbsp;{$lang.Forwards_Summary}</div>
								<ul class="Text">
									<li>{$lang.ListStatsTotalForwards}{$tabs.forwards.forward_total}</li>
									<li>{$lang.ListStatsTotalForwardSignups}{$tabs.forwards.forward_signups}</li>
								</ul>
							</td>
							<td>{$tabs.forwards.forwards_chart}</td>
						</tr>
					</table>

			        <div id="adminTabletriggeremails_forwards"></div>
				{/if}
			</div>
		{* ----- *}

		{* ----- Tab 7: Recipients *}
			<div id="div7" style="display: {if $PAGE.whichtab == 7}block{else}none{/if}; clear: both;">
				<div class="body" style="display: block; clear: both; padding-top:10px; padding-bottom:8px;">{$tabs.recipients.intro}</div>

				<div style="padding-bottom: 10px;">{$tabs.recipients.calendar}</div>

				{if !is_array($tabs.recipients.records) || count($tabs.recipients.records) == 0}
					<div>{$tabs.recipients.message}</div>
				{else}
					<table width="100%" cellpadding="5" border="0" cellspacing="1" class="Text" style="padding-top: 0px; margin-top: 0px;">
						<tr>
							<td width="100%" colspan="3">
								<table width="100%" border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td valign="top">&nbsp;</td>
										<td valign="top" align="right">{$tabs.recipients.pagination_top}</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr class="Heading3">
							<td nowrap align="left">{$lang.EmailAddress}</td>
							<td nowrap align="left">{$lang.SentWhen}</td>
						</tr>
						{foreach from=$tabs.recipients.records item=each}
							<tr>
								<td nowrap align="left">{$each.note}</td>
								<td nowrap align="left">{$each.processed_senttime}</td>
							</tr>
						{/foreach}
						<tr>
							<td align="right" colspan="3">{$tabs.recipients.pagination_bottom}</td>
						</tr>
					</table>
				{/if}
			</div>
		{* ----- *}

		{* ----- Tab 8: Failed sending *}
			<div id="div8" style="display: {if $PAGE.whichtab == 8}block{else}none{/if}; clear: both;">
				<div class="body" style="display: block; clear: both; padding-top:10px; padding-bottom:8px;">{$tabs.failed.intro}</div>

				<div style="padding-bottom: 10px;">{$tabs.failed.calendar}</div>

				{if !is_array($tabs.failed.records) || count($tabs.failed.records) == 0}
					<div>{$tabs.failed.message}</div>
				{else}
					<table width="100%" cellpadding="5" border="0" cellspacing="1" class="Text" style="padding-top: 0px; margin-top: 0px;">
						<tr>
							<td width="100%" colspan="3">
								<table width="100%" border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td valign="top">&nbsp;</td>
										<td valign="top" align="right">{$tabs.failed.pagination_top}</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr class="Heading3">
							<td nowrap align="left">{$lang.EmailAddress}</td>
							<td nowrap align="left">{$lang.SentWhen}</td>
						</tr>
						{foreach from=$tabs.failed.records item=each}
							<tr>
								<td nowrap align="left">{$each.note}</td>
								<td nowrap align="left">{$each.processed_senttime}</td>
							</tr>
						{/foreach}
						<tr>
							<td align="right" colspan="3">{$tabs.failed.pagination_bottom}</td>
						</tr>
					</table>
				{/if}
			</div>
		{* ----- *}
	{* ----- *}
</div>