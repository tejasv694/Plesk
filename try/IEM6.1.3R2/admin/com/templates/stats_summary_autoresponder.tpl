<fieldset>
<legend>%%LNG_AutoresponderStatistics_Snapshot_Heading%%</legend>
<table style="width: 100%;" cellspacing="0">
	<tr>
		<td>
			<table border=0  cellspacing="0" width="100%" class="Text">
				<tr class="GridRow">
					<td width="30%" height="22" nowrap align="left">
						&nbsp;%%LNG_AutoresponderSubject%%
					</td>
					<td width="70%" nowrap align="left">
						<a title="%%LNG_PreviewThisAutoresponder%%" href="#" onclick="PreparePreview(); return false;">%%GLOBAL_AutoresponderSubject%%</a>
					</td>
				</tr>
				<tr class="GridRow">
					<td width="30%" height="22" nowrap align="left">
						&nbsp;%%LNG_SentToList%%
					</td>
					<td width="70%" nowrap align="left">
						%%GLOBAL_MailingList%%
					</td>
				</tr>
				<tr class="GridRow">
					<td width="30%" height="22" nowrap align="left" valign="top">
						&nbsp;%%LNG_AutoresponderStatistics_SentTo%%
					</td>
					<td width="70%" nowrap align="left">
						%%GLOBAL_SentToDetails%%
					</td>
				</tr>
				<tr class="GridRow">
					<td width="30%" height="22" nowrap align="left" valign="top">
						&nbsp;%%LNG_AutoresponderStatistics_SentWhen%%
					</td>
					<td width="70%" nowrap align="left">
						%%GLOBAL_SentWhen%%
					</td>
				</tr>
				<tr class="GridRow">
					<td width="30%" height="22" nowrap align="left" valign="top">
						&nbsp;%%LNG_AutoresponderStatistics_CreatedBy%%
					</td>
					<td width="70%" nowrap align="left">
						<a href="mailto:%%GLOBAL_UserEmail%%">%%GLOBAL_CreatedBy%%</a>
					</td>
				</tr>
				<tr class="GridRow">
					<td width="30%" height="22" nowrap align="left" valign="top">
						&nbsp;%%LNG_AutoresponderStatistics_Opened%%
					</td>
					<td width="70%" nowrap align="left">
						<a title="Click here to see the email address of everyone that opened this newsletter" href="%%GLOBAL_OpensURL%%">%%GLOBAL_TotalOpens%%</a> / <a title="Click here to view unique email addresses that opened this newsletter" href="%%GLOBAL_UniqueOpensURL%%">%%GLOBAL_UniqueOpens%%</a>
					</td>
				</tr>
				<tr class="GridRow">
					<td width="30%" height="22" nowrap align="left" valign="top">
						&nbsp;%%LNG_AutoresponderStatistics_Bounced%%
					</td>
					<td width="70%" nowrap align="left">
						%%GLOBAL_TotalBounces%%
					</td>
				</tr>
			</table>
      		</td>
    	</tr>
    	<tr>
      		<td>
        		%%GLOBAL_SummaryChart%%
      		</td>
    	</tr>
</table>
</fieldset>