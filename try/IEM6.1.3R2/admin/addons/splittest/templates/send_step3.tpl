<form name="frmSend_Step3" method="post" action="{$AdminUrl}&Action=Send&Step=4">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				{$lang.Addon_splittest_Send_Step3_Heading}
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					{$lang.Addon_splittest_Send_Step3_Intro}
				</p>
				<ul style="margin-bottom: 0px;">
					<li>
						{$lang.Addon_splittest_Send_Step3_SendingCampaignsIntro}
						<ul>
							<li>
								{foreach from=$sendingCampaigns key=k item=campaignName id=campaignLoop}
									{if $NewsletterView}<a href="{$ApplicationUrl}?Page=Newsletters&Action=View&id={$k}" target="_blank">{/if}{$campaignName}{if $NewsletterView}</a>{/if}{if !$campaignLoop.last}, {/if}
								{/foreach}
							</li>
						</ul>
					</li>
						{if $SendingToLists}
							<li>{$lang.Addon_splittest_Send_Step3_ListsIntro}
						{/if}
						{if $SendingToSegments}
							<li>{$lang.Addon_splittest_Send_Step3_SegmentsIntro}
						{/if}
						<ul>
							<li>
								{foreach from=$sendLists key=id item=name id=listLoop}
									{$name}{if !$listLoop.last}, {/if}
								{/foreach}
							</li>
						</ul>
					</li>
					{if $CronEnabled}
						<li>
							{$JobScheduleTime}
						</li>
					{/if}
					<li>{$SendingToNumberOfContacts}</li>
				</ul>
				<br/>
			</td>
		</tr>
		{if $CronEnabled == false}
			<tr>
				<td class="body">
					<input type="button" value="{$lang.Addon_splittest_Send_Step3_SendButton}" class="SmallButton" style="width:190px" onclick="javascript: PopupWindow();">
					<input type="button" value="{$lang.Addon_splittest_Send_Step3_CancelButton}" class="FormButton" onclick="if(confirm('{$lang.Addon_splittest_Send_Step3_CancelButtonAlert}')) {document.location='{$AdminUrl}';}">
				</td>
			</tr>
		{else}
			<tr>
				<td class="body">
					<input type="submit" value="{$lang.Addon_splittest_Send_Step3_ScheduleButton}" class="SmallButton" style="width:190px">
					<input type="button" value="{$lang.Addon_splittest_Send_Step3_CancelButton}" class="FormButton" onclick="if(confirm('{$lang.Addon_splittest_Send_Step3_CancelButtonAlert}')) {document.location='{$AdminUrl}';}">
				</td>
			</tr>
		{/if}
	</table>
</form>
{if $CronEnabled == false}
	<script>
		function PopupWindow() {
			var top = screen.height / 2 - (170);
			var left = screen.width / 2 - (225);
			if(confirm('{$lang.Addon_splittest_Send_Step3_Send_ConfirmMessage}')) {
				window.open("{$AdminUrl}&Action=Send&Step=4&popup=1&Start=1", "sendWin","left=" + left + ",top="+top+",toolbar=false,status=no,directories=false,menubar=false,scrollbars=false,resizable=false,copyhistory=false,width=450,height=290");
			}
		}
	</script>
{/if}

