<script src="includes/js/jquery.js"></script>
<script>
	$(function() {
		var temp = '';
		if((temp = self.parent.getSendPreviewParam()) == false) temp = '';
		$('#PreviewEmail_Message').load('index.php?Page=%%PAGE%%&Action=SendPreview', temp);
	});
</script>
<style type="text/css" media="all">
	#PreviewEmail_Container {
		padding: 0px;
		margin: 0px;
		width: auto;
		line-height: 1.3;
	}

	#PreviewEmail_Message {
		font-family:Tahoma,Arial;
		font-size:11px;
	}

	#PreviewEmail_Loading {
		text-align: center;
	}
</style>
<div id="PreviewEmail_Container">
	<div id="PreviewEmail_MessageContainer">
		<div id="PreviewEmail_Message">
			<div id="PreviewEmail_Loading"><img src="images/loading.gif" alt="loading..." /></div>
			%%LNG_SendPreview_Loading%%
		</div>
	</div>
	%%LNG_Preview_CustomFieldsNotAltered%%
</div>
