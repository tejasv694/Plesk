<tr>
	<td colspan="2" class="Heading2">
		&nbsp;&nbsp;{$lang.Addon_splittest_Send_Step2_CronOptions}
	</td>
</tr>
<tr>
	<td width="200" class="FieldLabel">
		{template="not_required"}
		{$lang.Addon_splittest_Send_Step2_SendImmediately}
	</td>
	<td>
		<label for="sendimmediately"><input type="checkbox" name="sendimmediately" id="sendimmediately" value="1" CHECKED
		onClick="ShowSendTime(this)">&nbsp;{$lang.Addon_splittest_Send_Step2_SendImmediatelyExplain}</label>&nbsp;
	</td>
</tr>
<tr id="show_senddate" style="display:none;" width="200" class="FieldLabel">
	<td width="200" class="FieldLabel">
		{template="required"}
		{$lang.Addon_splittest_Send_Step2_SendMyCampaignWhen}&nbsp;
	</td>
	<td style="padding-left:20px" valign="top">
		<table cellspacing="0" cellpadding="0" border="0">
			<tr>
				<td width="20" valign="top"><img src="images/nodejoin.gif" /></td>
				<td valign="top">
					{$ScheduleTimeBox}&nbsp;
					<input type="hidden" name="sendtime" id="sendtime" value="" />
					<script>
						function SetSendTime() {
							var h = $('#sendtime_hours').val();
							var m = $('#sendtime_minutes').val();
							var a = $('#sendtime_ampm').val();
							var sendtime = h + ':' + m + a;
							$('#sendtime').val(sendtime);
						}

						SetSendTime();
					</script>
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr>
	<td width="200" class="FieldLabel">
		{template="not_required"}
		{$lang.Addon_splittest_Send_Step2_NotifyOwner}
	</td>
	<td>
		<label for="notifyowner"><input type="checkbox" name="notifyowner" id="notifyowner" value="1" CHECKED>&nbsp;{$lang.Addon_splittest_Send_Step2_NotifyOwnerExplain}</label>&nbsp;
	</td>
</tr>
<tr>
	<td colspan="2" class="EmptyRow">
		&nbsp;
	</td>
</tr>

