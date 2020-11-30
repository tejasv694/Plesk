<form method="post" action="index.php?Page=Schedule&Action=Update&job=%%GLOBAL_JobID%%">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Schedule_Edit%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Help_Schedule_Edit%%
				</p>
			</td>
		</tr>
		<tr>
			<td class="body">
				%%GLOBAL_Message%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_Save%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_ScheduleEditCancel_Prompt%%")) { document.location="index.php?Page=Schedule" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_NewsletterDetails%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_MailingList%%:&nbsp;
						</td>
						<td>
							%%GLOBAL_Send_SubscriberList%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SendNewsletter%%:&nbsp;
						</td>
						<td>
							%%GLOBAL_Send_NewsletterName%%&nbsp;&nbsp;
							<a href="#" onclick="javascript: PreparePreview(); return false;"><img src="images/magnify.gif" border="0">&nbsp;%%LNG_Preview%%</a>
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_SendTime%%:&nbsp;
						</td>
						<td>
							<input type="hidden" name="sendtime" id="sendtime" value="" />
							%%GLOBAL_SendTimeBox%%&nbsp;%%LNG_HLP_SendTime%%
						</td>
					</tr>
					<tr>
						<td>
							&nbsp;
						</td>
						<td>
							<input class="FormButton" type="submit" value="%%LNG_Save%%">
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_ScheduleEditCancel_Prompt%%")) { document.location="index.php?Page=Schedule" }'>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<script>
	function PreparePreview() {
		var baseurl = "index.php?Page=Newsletters&Action=Preview&id=";
		var realId = %%GLOBAL_NewsletterID%%;
		window.open(baseurl + realId , "pp");
	}

	function SetSendTime() {
		var h = $('#sendtime_hours').val();
		var m = $('#sendtime_minutes').val();
		var a = $('#sendtime_ampm').val();
		var sendtime = h + ':' + m + a;
		$('#sendtime').val(sendtime);
	}

	$(function() { SetSendTime(); })
</script>