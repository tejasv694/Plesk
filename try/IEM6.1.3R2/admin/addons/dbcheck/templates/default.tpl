<table width="100%">
	<tr>
		<td class="Heading1">
			{$lang.Addon_dbcheck_Heading}
		</td>
	</tr>
	<tr>
		<td>
			{$FlashMessages}
		</td>
	</tr>
	<tr>
		<td class="body">
			{$lang.Addon_dbcheck_Intro}
		</td>
	</tr>
	<tr>
		<td>
			<br />
			<input type="button" class="SmallButton" value="{$lang.Addon_dbcheck_Button_Start}" onclick="RunCheck();" />
		</td>
	</tr>
</table>

<script>
	function RunCheck()
	{
		var x = '{$AdminUrl}&AJAX=1&Action=ShowPopup&keepThis=true&TB_iframe=true&height=240&width=400&modal=true&random=' + new Date().getTime();
		tb_show('', x, '');
	}
</script>
