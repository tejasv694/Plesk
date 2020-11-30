<script type="text/javascript">
	function PreviewHTMLContent() {
			var html;
			html = $('textarea.ContentsTextEditor').val();
			win = window.open(", ", 'popout', 'toolbar = no, status = no');
		  	win.document.write("" + html + "");
	}
</script>

<textarea name="%%GLOBAL_Name%%" id="%%GLOBAL_Name%%" rows="10" cols="60" class="ContentsTextEditor">%%GLOBAL_HTMLContent%%</textarea>
<input type="button" onclick="javascript: PreviewHTMLContent(); return false;" value="%%LNG_PreviewHTMLContent%%" class="FormButton" style="width: 150px;" />
<br /><br />
<div class="aside">%%LNG_TextWidthLimit_Explaination%%</div>

