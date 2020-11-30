<select class="Select{if $widget.is_required} required{/if}" id="widget{$widget.id}" name="widget[{$widget.id}][field][0][value]">
	<option value="">{$langvars.pleaseSelectAnOption}</option>
	{foreach from=$widget.fields item=field}
		<option value="{$field.value}"{if $field.is_selected}selected="selected"{/if}>{$field.value}</option>
	{/foreach}
</select>