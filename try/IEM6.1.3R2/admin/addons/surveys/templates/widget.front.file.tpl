<script type="text/javascript">

jQuery(function($) {

	$.fn.form.setValidator('FileType_{$widget.id}', function() {
		var field = $(this);
		var fileTypeStr = "{$widget.allowed_file_types}";

		if (!field.val()) {
			return true;
		}
		
		// check if first field have the value
		// if valid file types are set
			if (fileTypeStr) {

				
					var fileTypes = fileTypeStr.toLowerCase().split(/\s*,\s*/);
					var fileParts = field.val().split('.');
		
					// if the file extension isn't found, trigger the error (return false)
					if ($.inArray(fileParts[fileParts.length - 1], fileTypes) == -1) {
						return false;
					}
			}
			
		return true;
	}, $('#Widget_{$widget.id}_FilesMustEndWith').text() );

});

</script>

<input class="File FileType_{$widget.id}{if $widget.is_required} required{/if}" type="file" id="form-widget-{$widget.id}" name="widget[{$widget.id}][field][{$field.id}][value]" />
<p>
	{if $widget.fileTypes}
		<span id="Widget_{$widget.id}_FilesMustEndWith">( Please upload a file ending in  <span class="FileType">.{$widget.fileTypes|implode,'</span>, <span class="FileType">.'}</span> {$lang.or} <span class="FileType">.{$widget.lastFileType} )</span><span>
	{elseif $widget.lastFileType}
		<span id="Widget_{$widget.id}_FilesMustEndWith">( Please upload a file ending in <span class="FileType">.{$widget.lastFileType} )</span><span>
	{/if}
</p>