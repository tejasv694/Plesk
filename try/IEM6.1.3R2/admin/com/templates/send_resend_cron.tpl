<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">
			%%LNG_Send_Resend%%
		</td>
	</tr>
	<tr>
		<td class="body pageinfo">
			<p>
				%%GLOBAL_Send_ResendCount%%
				%%LNG_Send_Resend_Intro%%
			</p>
			<ul style="margin-bottom:0px">
				<li>%%GLOBAL_Send_NewsletterName%%</li>
				<li>%%GLOBAL_Send_NewsletterSubject%%</li>
				<li>%%GLOBAL_Send_SubscriberList%%</li>
				<li>%%GLOBAL_Send_TotalRecipients%%</li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="body">
			<input type="button" value="Resend Immediately" class="SmallButton" onclick="document.location='index.php?Page=Schedule&job=%%GLOBAL_JobID%%&Action=Resend';">&nbsp;<input type="button" value="%%LNG_Cancel%%" class="FormButton" onclick="document.location='index.php?Page=Newsletters';">
		</td>
	</tr>
</table>

