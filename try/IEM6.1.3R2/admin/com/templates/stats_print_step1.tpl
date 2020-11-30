<script src="includes/js/jquery/form.js"></script>
<div style="padding:10px;">
	<form id="print_form" action="" onsubmit="return false;">
		<input type="hidden" name="Action" value="print" />
		<input type="hidden" name="subaction" value="step2" />
		%%LNG_SelectStatisticsToPrint%%:<br /><br />
		%%GLOBAL_PrintOptions%%<br />
		<div style="text-align:center;">
			<input type="submit" name="action" value="%%LNG_Print_Stats_Selected%%" onclick="return print_();" />
			<input type="submit" name="action" value="%%LNG_Preview%%" onclick="return preview();" />
			<input type="submit" name="action" value="%%LNG_Cancel%%" onclick="tb_remove(); return false;" />
		</div>
	</form>
</div>
<iframe id="print_frame" style="height:0px; border:0px;"></iframe>
<script>
  function print_() {
    $('#print_frame')[0].src = 'remote_stats.php?' + $('#print_form').formSerialize();
  }
  function preview() {
    window.open('remote_stats.php?Preview=1&' + $('#print_form').formSerialize(),'print_preview','toolbar=no,location=no,status=no,copyhistory=no,scrollbars=yes,resizable=yes');
    return false;
  }
</script>