<div>
	<strong><em>{$heading}</em></strong>
	<br /><br />
	{$message}
	<div style="text-align:center; padding-top:1.2em;">
		<input type="button" id="popup_close" value="&nbsp;{$lang.OK}&nbsp;" />
	</div>
	<script>
	$('#popup_close').click(function() {
		parent.tb_remove();
	});
	</script>
</div>
