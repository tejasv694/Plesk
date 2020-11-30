<div id="check">
	<img src="images/loading.gif" alt="{$lang.LoadingMessage}" />
	<script>
		$.ajax({
			async: false,
			type: "post",
			url: "remote.php",
			data: "what=save_version&latest=" + latest_version + "&feature=" + feature_version + "&latest_critical=" + latest_critical + "&feature_critical=" + feature_critical
		});
		$.ajax({
			type: "get",
			url: "index.php?Page=Addons&Addon=updatecheck&Action=report&Ajax=true",
			success: function(data, textStatus) {
				var status = 'FlashSuccess';
				var img = 'success';
				if (data == '{$lang.Addon_updatecheck_NoNew}') {
					status = 'FlashError';
					img = 'error';
				}
				data += "<div align=\"center\"><br /><input type=\"button\" value=\" {$lang.OK} \" class=\"FormButton\" style=\"margin-top:10px; margin-bottom:0;\" onclick=\"parent.tb_remove();\" /></div>";
				$('#check').html(data);
			}
		});
	</script>
</div>
