<script>
	$('#AddSplitTestButton').attr('onclick', '');
	$('#AddSplitTestButton').click(function() {
		if ($('input.UICheckboxToggleRows:checked').length < 2) {
			alert('{$alert_msg}');
			return false;
		}
		var ids = $('input.UICheckboxToggleRows:checked').map(function() { return $(this).val(); });
		Application.Util.submitPost('{$url}', {'passthru_campaigns': $.makeArray(ids)});
		return false;
	});
</script>
