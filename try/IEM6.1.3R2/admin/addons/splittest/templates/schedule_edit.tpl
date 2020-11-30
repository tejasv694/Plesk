<script>

	var PAGE = {
		init: function() {
			$('.cancelButton').click(function() {
				PAGE.cancel();
			});
			SetSendTime();
		},

		cancel: function() {
			if(confirm('{$lang.Addon_splittest_Schedule_Edit_CancelPrompt}')) {
				document.location="index.php?Page=Schedule";
			}
		}
	};

	$(function() {
		PAGE.init();
	});

	// this is a 'global' function as the datetime box creator needs it to be.
	// it automatically puts "onchange" triggers to call this.
	function SetSendTime() {
		var h = $('#sendtime_hours').val();
		var m = $('#sendtime_minutes').val();
		var a = $('#sendtime_ampm').val();
		var sendtime = h + ':' + m + a;
		$('#sendtime').val(sendtime);
	}

</script>

<form method="post" action="{$ApplicationUrl}index.php?Page=Schedule&Action=Update&job={$Jobid}">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				{$lang.Addon_splittest_Schedule_Edit_Heading}
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					{$lang.Addon_splittest_Schedule_Edit_Intro}
				</p>
			</td>
		</tr>
		<tr>
			<td class="body">
				{$FlashMessages}
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton submitButton" type="submit" value="{$lang.Addon_splittest_Save}" />
				<input class="FormButton cancelButton" type="button" value="{$lang.Addon_splittest_Cancel}" />
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;{$lang.Addon_splittest_Schedule_Splittest_Details}
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="not_required"}
							{if $SendType == 'list'}
								{$lang.Addon_splittest_Schedule_MailingLists}
							{else}
								{$lang.Addon_splittest_Schedule_Segments}
							{/if}
						</td>
						<td>
							{$lang.Addon_splittest_Schedule_ListIntro}
							{foreach from=$sendinglist key=listid item=listname id=listLoop}
								<b>{$listname}</a></b>{if !$listLoop.last}, {/if}
							{/foreach}
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="not_required"}
							{$lang.Addon_splittest_Schedule_SendingNewsletters}&nbsp;
						</td>
						<td>
							{foreach from=$campaigns key=campaignid item=campaignname id=campaignLoop}
								<a href="index.php?Page=Newsletters&Action=View&id={$campaignid}" target="_blank">{$campaignname}</a>{if !$campaignLoop.last}, {/if}
							{/foreach}
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="required"}
							{$lang.Addon_splittest_Schedule_SendWhen}
						</td>
						<td>
							<input type="hidden" name="sendtime" id="sendtime" value="" />
							{$ScheduleTimeBox}&nbsp;{$lnghlp.Addon_splittest_Schedule_SendWhen}
						</td>
					</tr>
					<tr>
						<td>
							&nbsp;
						</td>
						<td>
							<input class="FormButton submitButton" type="submit" value="{$lang.Addon_splittest_Save}" />
							<input class="FormButton cancelButton" type="button" value="{$lang.Addon_splittest_Cancel}" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
