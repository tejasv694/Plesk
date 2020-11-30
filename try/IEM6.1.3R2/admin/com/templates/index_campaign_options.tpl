<select name="campaignstat" style="width:100%;" id="CampaignStatsListDropdown">
<option value="0" {if $mystatsSelected.selected == $each.statid} SELECTED {/if}>%%LNG_SubscriberActivity_Last7Days%%</option>
{foreach from=$mystats item=each}
	<option value="{$each.statid}" {if $mystatsSelected.selected == $each.statid} SELECTED {/if}>{$each.newslettername} - {$lang.DateStarted}: {$each.starttime} - ({$each.totalrecipients} {$lang.TotalRecipients})</option>
{/foreach}
</select>