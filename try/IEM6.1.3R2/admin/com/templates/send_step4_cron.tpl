<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">
			%%LNG_Send_Step4%%
		</td>
	</tr>
	<tr>
		<td>
			%%GLOBAL_Messages%%
		</td>
	</tr>
	<tr>
		<td class="body pageinfo">
			%%GLOBAL_SentToTestListWarning%%
			%%GLOBAL_ImageWarning%%
			%%GLOBAL_EmailSizeWarning%%
			%%LNG_Send_Step4_CronIntro%%
			<ul style="margin-bottom:-5px">
				<li>%%GLOBAL_Send_NewsletterName%%</li>
				<li>%%GLOBAL_Send_NewsletterSubject%%</li>
				<li>%%GLOBAL_Send_SubscriberList%%</li>
				<li>%%GLOBAL_Send_TotalRecipients%%</li>
				<li>%%GLOBAL_Send_ScheduleTime%%</li>
				%%GLOBAL_ApproximateSendSize%%
				<li>%%LNG_Send_Not_Completed%%</li>
			</ul>
			<br />
		</td>
	</tr>
	<tr>
		<td class="body" style="padding-top:10px">
			<input type="button" value="%%LNG_ApproveScheduledSend%%" class="SmallButton" style="width: 190px; font-weight:bold" onclick="document.location='index.php?Page=Schedule&A=1';">
			<input type="button" value="%%LNG_Cancel%%" class="FormButton" onclick="if(confirm('%%LNG_ConfirmCancelSchedule%%')) {document.location='index.php?Page=Newsletters';}">
		</td>
	</tr>
</table>
