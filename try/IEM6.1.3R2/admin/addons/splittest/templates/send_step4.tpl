<table cellspacing="0" cellpadding="3" width="100%" align="center">
	<tr>
		<td class="Heading1">
			{$lang.Addon_splittest_Send_Step4_Heading}
		</td>
	</tr>
	<tr>
		<td class="body pageinfo">
			<div style="padding:10px; background-color:#FAD163">{$lang.Addon_splittest_Send_Step4_KeepBrowserWindowOpen}</div>
		</td>
	</tr>
	<tr>
		<td class="body">
			<ul style="line-height:1.5; margin-left:30px; padding-left:0px">
				<li>{$Send_NumberAlreadySent}</li>
				<li>{$Send_NumberLeft}</li>
				<li>{$SendTimeSoFar}</li>
				<li>{$SendTimeLeft}</li>
			</ul>
			<input type="button" class="SmallButton" style="width:260px" value="{$lang.Addon_splittest_Send_Step4_PauseSending}" onclick="PauseSending()" />
		</td>
	</tr>
</table>
<script>
	function PauseSending() {
		window.opener.document.location = '{$AdminUrl}&Action=Send&Step=10';
		window.opener.focus();
		window.close();
	}
</script>

<script>
	setTimeout('window.location="{$AdminUrl}&Action=Send&Step=4&popup=1"', 1);
</script>
