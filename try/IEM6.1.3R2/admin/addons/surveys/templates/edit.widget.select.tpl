<dd class="last">
	<input type="hidden" name="widget[{$widget.id}][id]" value="{$widget.id}" />
	<select name="widget[{$widget.id}][field][0][value]">
		<option value="">{$langvars.pleaseSelectAnOption}</option>
		{foreach from=$widget.fields item=field}
			<option value="{$field.value}"{if $field.is_selected} selected="selected"{/if}>{$field.value}</option>
		{/foreach}
	</select>
</dd>