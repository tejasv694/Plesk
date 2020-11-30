<link rel="stylesheet" href="includes/styles/useractivitylog.css" type="text/css">

{if !$IEM.User->Get('infotips')}
	<style>
		div#lastViewed {
			margin-bottom: 10px;
		}
	</style>
{/if}

<script>
	$(function() {
		if (!$.browser.mozilla) {
			$('div#userActivityLogList span.userActivityLogItem').each(function() {
				if($(this).width() > 120) $(this).css('width', '120px');
			});
		}
	});
</script>

<div id="userActivityLogList_Container">
	<div id="userActivityLogList" class="Text">
		<span class="userActivityLogLabel">{$lang.UserActivityLogLabel}</span>
		{foreach from=$records item=record id=activityRecord}
			<span class="userActivityLogItem {if $activityRecord.first}userActivityLogFirstItem{elseif $activityRecord.last}userActivityLogLastItem{/if}">
				<a href="{$record.url}" title="{$record.text|strreplace,'"', '&quot;'}">
					<img src="{$record.icon}" alt="icon" />&nbsp;{$record.text}
				</a>
			</span>
		{/foreach}
		&nbsp;
	</div>
</div>