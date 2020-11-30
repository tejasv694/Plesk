<script src="includes/js/jquery/form.js"></script>

<div style="padding:10px; margin:0;">
	<form id="print_form" action="" onsubmit="return false;">
		<input type="hidden" name="Action" value="print" />
		{$lang.SelectStatisticsToPrint}:
		<div style="padding:0; margin:0; margin-top:10px; margin-bottom:10px;">
			{$print_options}
		</div>
		<div style="text-align:center; padding:0; margin:0; margin-top:30px;">
			<input type="submit" name="action" value="%%LNG_Addon_splittest_Stats_PrintSelected%%" onclick="return printStats('{$path}');" />
			<input type="submit" name="action" value="%%LNG_Preview%%" onclick="return preview('{$path}');" />
			<input type="submit" name="action" value="%%LNG_Cancel%%" onclick="tb_remove(); return false;" />
		</div>
	</form>
</div>
<iframe id="print_frame" style="height:0; border:0;"></iframe>
<script>
  function printStats(path) {
    $('#print_frame')[0].src = path + 'print_stats.php?' + $('#print_form').formSerialize();
  }

  function preview(path) {
	var url = path + 'print_stats.php?subaction=preview&';
    window.open(url + $('#print_form').formSerialize(), 'print_preview', 'toolbar=no,location=no,status=no,copyhistory=no,scrollbars=yes,resizable=yes');
    return false;
  }
</script>
