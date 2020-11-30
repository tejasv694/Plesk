{foreach from=$widget.fields item=field}
	<dd{if $field.isLast} class="last"{/if}>
		{if $field.is_other}
			<input id="WidgetField_{$field.id}" type="radio" name="widget[{$widget.id}][field][0][value]" value="__other__"{if $field.is_selected} checked="checked"{/if} /> 
			<label for="WidgetField_{$field.id}">{$langvars.editResponseOtherText}</label>
			<input type="text" name="widget[{$widget.id}][field][0][other]" value="{$field.value}" />
		{else}
			<input id="WidgetField_{$field.id}" type="radio" name="widget[{$widget.id}][field][0][value]" value="{$field.value}"{if $field.is_selected} checked="checked"{/if} /> 
			<label for="WidgetField_{$field.id}">{$field.value}</label>
		{/if}
	</dd>
{/foreach}
