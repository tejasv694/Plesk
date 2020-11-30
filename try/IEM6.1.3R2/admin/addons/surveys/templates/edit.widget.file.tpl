<dd class="last">
	<input type="file" name="widget[{$widget.id}][field][{$widget.values.0.id}][value]" />
	{if $widget.values.0.value}
		<input type="hidden" name="widget[{$widget.id}][field][{$widget.values.0.id}][value]" value="{$widget.values.0.value}" />
		
		
		<p>(Currently: 	<a href="index.php?Page=Addons&Addon=surveys&Action=DownloadAttach&ajax=1&formId={$surveyId}&responseId={$widget.values.0.surveys_response_id}&value={$widget.values.0.file_encode}"> {$widget.values.0.value}</a>)</p>
	{/if}
</dd>