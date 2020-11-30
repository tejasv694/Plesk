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
			<br/>
			<input type="button" value="%%LNG_StartSending%%" class="SmallButton" onclick="javascript: PopupWindow();">&nbsp;<input type="button" value="%%LNG_Cancel%%" class="FormButton" onclick="document.location='index.php?Page=Newsletters';">
		</td>
	</tr>
</table>
<script>
	function PopupWindow() {
		var top = screen.height / 2 - (170);
		var left = screen.width / 2 - (140);

		window.open("index.php?Page=Send&Action=Send&Job=%%GLOBAL_JobID%%&Resend","sendWin","left=" + left + ",top="+top+",toolbar=false,status=no,directories=false,menubar=false,scrollbars=false,resizable=false,copyhistory=false,width=360,height=200");
	}
</script>
