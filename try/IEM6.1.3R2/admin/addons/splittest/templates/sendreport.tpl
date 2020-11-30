<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">
			{$lang.Addon_splittest_Send_Step5_Finished_Heading}
		</td>
	</tr>
	<tr>
		<td class="body pageinfo">
			<p>
				{$SendReport_Intro}
			</p>
		</td>
	</tr>
	<tr>
		<td>
			{$SendReport_Details}
		</td>
	</tr>
	<tr>
		<td><br>
			<input type="button" value="%%LNG_OK%%" onclick="javascript: document.location='{$AdminUrl}';" class="FormButton">
		</td>
	</tr>
</table>
<script>
	function ShowReport(reporttype) {
		var link = '{$AdminUrl}&Action=Send&Step=15&ReportType=' + reporttype;

		var top = screen.height / 2 - (230);
		var left = screen.width / 2 - (250);

		window.open(link,"reportWin","left=" + left + ",top="+top+",toolbar=false,status=no,directories=false,menubar=false,scrollbars=false,resizable=false,copyhistory=false,width=500,height=460");
	}
</script>
