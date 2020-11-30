<table cellspacing="0" cellpadding="3" width="100%" align="center">
	<tr>
		<td class="Heading1">
			%%LNG_Send_Step5%%
		</td>
	</tr>
	<tr>
		<td class="body pageinfo">
			%%LNG_Send_Step5_KeepBrowserWindowOpen%%
		</td>
	</tr>
	<tr>
		<td class="body">
			<ul style="line-height:1.5; margin-left:30px; padding-left:0px">
				<li>%%GLOBAL_Send_NumberAlreadySent%%</li>
				<li>%%GLOBAL_Send_NumberLeft%%</li>
				<li>%%GLOBAL_SendTimeSoFar%%</li>
				<li>%%GLOBAL_SendTimeLeft%%</li>
			</ul>
			<input type="button" class="SmallButton" style="width:260px" value="%%LNG_PauseSending%%" onclick="PauseSending()" />
		</td>
	</tr>
</table>
<script>
	function PauseSending() {
		window.opener.document.location = 'index.php?Page=Send&Action=PauseSend&Job=%%GLOBAL_JobID%%';
		window.opener.focus();
		window.close();
	}
</script>

<script>
	setTimeout('window.location="index.php?Page=Send&Action=Send&Job=%%GLOBAL_JobID%%&Started=1"', 1);
</script>
