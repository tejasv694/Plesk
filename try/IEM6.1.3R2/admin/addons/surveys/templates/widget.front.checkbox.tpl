<ul class="Fields">
	{foreach from=$widget.fields item=field}
		<li>
			<input class="Checkbox{if $widget.is_required} required{/if}" type="checkbox" id="widget-field-{$field.id}" name="widget[{$widget.id}][field][{$widget.id}][value][]" value="{$field.value}" {if $field.is_selected}checked="checked"{/if} />
			{if $field.is_other}
				<label for="widget-field-{$field.id}">{$field.other_label_text}</label>
				<input class="Other" type="text" name="widget[{$widget.id}][field][{$widget.id}][other]" value="" />
			{else}
				<label class="form-widget-field-label" for="widget-field-{$field.id}">{$field.value}</label>
			{/if}
		</li>
	{/foreach}
</ul>