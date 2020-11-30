<table cellspacing="0" cellpadding="0" width="100%" align="center">
<tr>
	<td>

<div class="Heading1">%%LNG_Stats_NewsletterStatistics%% for &quot;%%GLOBAL_NewsletterName%%&quot;</div>

<script>
	var TabSize = 6;
</script>

<div>
	<br>

	<ul id="tabnav">
		<li><a href="#" class="active" onClick="ShowTab(1)" id="tab1">%%LNG_NewsletterStatistics_Snapshot%%</a></li>
		<li><a href="#" onClick="ShowTab(2)" id="tab2">%%LNG_NewsletterStatistics_Snapshot_OpenStats%%</a></li>
		<li><a href="#" onClick="ShowTab(3)" id="tab3">%%LNG_NewsletterStatistics_Snapshot_LinkStats%%</a></li>
		<li><a href="#" onClick="ShowTab(4)" id="tab4">%%LNG_NewsletterStatistics_Snapshot_BounceStats%%</a></li>
		<li><a href="#" onClick="ShowTab(5)" id="tab5">%%LNG_NewsletterStatistics_Snapshot_UnsubscribeStats%%</a></li>
		<li><a href="#" onClick="ShowTab(6)" id="tab6">%%LNG_NewsletterStatistics_Snapshot_ForwardStats%%</a></li>
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
							%%LNG_NewsletterStatistics_Snapshot_Heading%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left">
							&nbsp;%%LNG_NewsletterSubject%%
						</td>
						<td width="70%" nowrap align="left">
							<a title="%%LNG_PreviewThisNewsletter%%" href="#" onclick="PreparePreview(); return false;">%%GLOBAL_NewsletterSubject%%</a>
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left">
							&nbsp;%%LNG_Stats_Newsletters_SelectList_Intro%%
						</td>
						<td width="70%" nowrap align="left">
							<a title="%%LNG_EditThisNewsletter%%" href="index.php?Page=Newsletters&Action=Edit&id=%%GLOBAL_NewsletterID%%">%%GLOBAL_NewsletterName%%</a>
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							&nbsp;%%GLOBAL_SentToLists%%
						</td>
						<td width="70%" height="22" nowrap align="left" valign="top">
							%%GLOBAL_MailingLists%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left">
							&nbsp;%%LNG_NewsletterStatistics_StartSending%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_StartSending%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left">
							&nbsp;%%LNG_NewsletterStatistics_FinishSending%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_FinishSending%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left">
							&nbsp;%%LNG_NewsletterStatistics_SendingTime%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_SendingTime%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							&nbsp;%%LNG_NewsletterStatistics_SentTo%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_SentToDetails%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							&nbsp;%%LNG_NewsletterStatistics_SentBy%%
						</td>
						<td width="70%" nowrap align="left">
							<a href="mailto:%%GLOBAL_UserEmail%%">%%GLOBAL_SentBy%%</a>
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							&nbsp;%%LNG_NewsletterStatistics_Opened%%
						</td>
						<td width="70%" nowrap align="left">
							<a href="%%GLOBAL_OpensURL%%" title="%%LNG_Stats_TotalOpens_Description%%">%%GLOBAL_TotalOpens%%</a>
							/
							<a href="%%GLOBAL_UniqueOpensURL%%" title="%%LNG_Stats_TotalUniqueOpens_Description%%">%%GLOBAL_UniqueOpens%%</a>
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							&nbsp;%%LNG_OpenRate%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_OpenRate%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							&nbsp;%%LNG_Stats_Clickthrough%%:
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_ClickThroughRate%%
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							&nbsp;%%LNG_NewsletterStatistics_Bounced%%
						</td>
						<td width="70%" nowrap align="left">
							%%GLOBAL_TotalBounces%%
						</td>
					</tr>
				</table>
			</td>

			<td></td>

			</tr><tr>
			<td width="55%">
				%%GLOBAL_SummaryChart%%
			</td>
		</tr>
	</table>
</div>
%%GLOBAL_OpensPage%%
%%GLOBAL_LinksPage%%
%%GLOBAL_BouncesPage%%
%%GLOBAL_UnsubscribesPage%%
%%GLOBAL_ForwardsPage%%

<script>
	function PreparePreview() {
		var openurl = "index.php?Page=Newsletters&Action=View&id=%%GLOBAL_NewsletterID%%";
		window.open(openurl, "pp");
	}
</script>

		</td>
	</tr>
</table>