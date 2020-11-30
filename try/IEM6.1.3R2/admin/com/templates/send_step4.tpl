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
			<p>
				%%GLOBAL_SentToTestListWarning%%
				%%GLOBAL_ImageWarning%%
				%%GLOBAL_EmailSizeWarning%%
				%%LNG_Send_Step4_Intro%%
			</p>
			<ul style="margin-bottom:0px">
				<li>%%GLOBAL_Send_NewsletterName%%</li>
				<li>%%GLOBAL_Send_NewsletterSubject%%</li>
				<li>%%GLOBAL_Send_SubscriberList%%</li>
				<li>%%GLOBAL_Send_TotalRecipients%%</li>
				%%GLOBAL_ApproximateSendSize%%
				<li>%%LNG_Send_Not_Completed%%</li>
			</ul>
			<br />
		</td>
	</tr>
	<tr>
		<td class="body">
			<input type="button" value="%%LNG_StartSending%%" class="SmallButton" style="font-weight:bold; width:190px" onclick="javascript: PopupWindow();">
			<input type="button" value="%%LNG_Cancel%%" class="FormButton" onclick="if(confirm('%%LNG_ConfirmCancelSend%%')) {document.location='index.php?Page=Newsletters';}">
		</td>
	</tr>
</table>
<script>
	function PopupWindow() {
		var top = screen.height / 2 - (170);
		var left = screen.width / 2 - (225);

		if(confirm('%%LNG_PopupSendWarning%%')) {
			window.open("index.php?Page=Send&Action=Send&Job=%%GLOBAL_JobID%%","sendWin","left=" + left + ",top="+top+",toolbar=false,status=no,directories=false,menubar=false,scrollbars=false,resizable=false,copyhistory=false,width=480,height=290");
		}
	}
</script>
