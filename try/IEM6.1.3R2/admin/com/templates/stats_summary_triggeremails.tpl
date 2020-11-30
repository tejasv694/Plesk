<fieldset>
	<legend>{$lang.TriggerEmails_Stats_Snapshots_Heading}</legend>
	<table style="width: 100%;" cellspacing="0">
		<tr>
			<td>
				<table border=0  cellspacing="0" width="100%" class="Text">
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left">
							&nbsp;{$lang.TriggerEmails_Stats_TriggerName}
						</td>
						<td width="70%" nowrap align="left">
							{$record.triggeremailsname}
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							{$lang.TriggerEmails_Manage_Column_TriggeredBy}
						</td>
						<td width="70%" nowrap align="left">
							{if $record.triggeremailstype == 'f'}
								{$lang.TriggerEmails_Manage_Column_TriggeredBy_CustomField}
							{elseif $record.triggeremailstype == 'n'}
								{$lang.TriggerEmails_Manage_Column_TriggeredBy_CampaignOpen}
							{elseif $record.triggeremailstype == 'l'}
								{$lang.TriggerEmails_Manage_Column_TriggeredBy_LinkClicked}
							{else}
							{/if}
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							&nbsp;{$lang.TriggerEmails_Manage_Column_TriggerHours}
						</td>
						<td width="70%" nowrap align="left">
							{capture name=temp}{$record.triggeremailshours|abs}{/capture}
							{if $each.triggerhours == 0}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_Immediate}
							{elseif $each.triggerhours == -1}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_OneHourBefore}
							{elseif $each.triggerhours < -1}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_HoursBefore|sprintf, $temp}
							{elseif $each.triggerhours == 1}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_OneHourAfter}
							{elseif $each.triggerhours < 1}
								{$lang.TriggerEmails_Manage_Column_TriggerDays_HoursAfter|sprintf, $temp}
							{else}
								{$temp}
							{/if}
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							&nbsp;{$lang.TriggerEmails_Manage_Column_Owner}
						</td>
						<td width="70%" nowrap align="left">
							<a href="mailto:{$record.owneremail}">{$record.ownerusername} {if trim($record.ownername) != ''}({$record.ownername}){/if}</a>
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							&nbsp;{$lang.NewsletterStatistics_Opened}
						</td>
						<td width="70%" nowrap align="left">
							{$info.total_open} / {$info.unique_open}
						</td>
					</tr>
					<tr class="GridRow">
						<td width="30%" height="22" nowrap align="left" valign="top">
							&nbsp;{$lang.NewsletterStatistics_Bounced}
						</td>
						<td width="70%" nowrap align="left">
							{$info.total_bounce}
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