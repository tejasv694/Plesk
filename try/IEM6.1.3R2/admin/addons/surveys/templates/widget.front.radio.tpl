<ul class="Fields">
	{foreach from=$widget.fields item=field}
		<li>
			<input class="Radio{if $widget.is_required} required{/if}" type="radio" id="WidgetField_{$field.id}" name="widget[{$widget.id}][field][0][value]" value="{$field.value}" {if $field.is_selected}checked="checked"{/if} />
			{if $field.is_other}
				<label for="WidgetField_{$field.id}">{$field.other_label_text}</label>
				<input class="Other" type="text" name="widget[{$widget.id}][field][0][other]" value="" />
			{else}
				<label for="WidgetField_{$field.id}">{$field.value}</label>
			{/if}
		</li>
	{/foreach}
</ul>