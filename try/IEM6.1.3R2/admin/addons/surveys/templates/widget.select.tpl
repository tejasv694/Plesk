<script type="text/javascript">

	// the select widget uses both the checkbox and radio js logic minus the other field logic
	jFrame.getInstance('moduleForm').dispatch(['widget.checkbox', 'widget.radio'], {
			context : '#{$randomId}'
		});

</script>

<li id="{$randomId}" class="form-element {type: 'select'}">
	<input type="hidden" name="widget[{$randomId}][id]" value="{$widget.id}" />
	<input type="hidden" name="widget[{$randomId}][type]" value="select" />
	
	<div class="ui-draggable-handle">
		<div class="form-element-title">
			<span class="form-element-required">*</span>
			<span class="title"><input class="edit-in-place example-field strong" type="text" name="widget[{$randomId}][name]" value="{$widget.name}" title="{$lang.Addon_Surveys_WidgetSelectDefaultName}" /></span>
			<a class="form-element-minimize" href="#" title="{$lang.Addon_Surveys_WidgetToggleTitle}">{$lang.Addon_Surveys_WidgetTextToggle}</a>
			<a class="form-element-duplicate" href="#" title="{$lang.Addon_Surveys_WidgetDuplicateTitle}">{$lang.Addon_Surveys_WidgetTextDuplicate}</a>
			<a class="form-element-remove" href="#" title="{$lang.Addon_Surveys_WidgetRemoveTitle}">{$lang.Addon_Surveys_WidgetRemove}</a>
		</div>
	</div>
	
	<div class="form-element-content">
		<input class="edit-in-place example-field em light" type="text" name="widget[{$randomId}][description]" value="{$widget.description}" title="{$lang.Addon_Surveys_WidgetSelectDefaultDescription}" />
		
		<ul>
			<li style="max-height: 250px; overflow: auto;">
				<table style="margin: 5px 0; padding: 0; border-collapse: collapse; border: 0;">
					<tbody class="form-element-option-list">
						{foreach from=$widgetFields key=fieldIndex item=widgetField}
							<tr>
								<td style="vertical-align: top">
									<input type="hidden" name="widget[{$randomId}][field][{$fieldIndex}][id]" value="{$widgetField.id}" />
									<input id="selected-{$fieldIndex}-{$randomId}" type="radio" name="widget[{$randomId}][field][{$fieldIndex}][is_selected]" value="1" {if $widgetField.is_selected}checked="checked"{/if} />
								<td>
								<td><input id="label-$fieldIndex-{$randomId}" class="edit-in-place example-field" type="text" name="widget[{$randomId}][field][{$fieldIndex}][value]" value="{$widgetField.value}" title="{$lang.Addon_Surveys_WidgetValueField}1" /><td>
								<td><a class="add-option-to-list" href="#"></a><td>
								<td><a class="remove-option-from-list" href="#" {if $fieldIndex == 0}style="display: none;"{/if} ></a><td>
								<td>
									<span class="add-other-container" style="display: none;">
									{$lang.Addon_Surveys_WidgetTextOr}
									<a class="add-other" href="#">{$lang.Addon_Surveys_WidgetTextAddOther}</a></span>
								</td>
							</tr>
						{/foreach}
						
						<tr><td></td></tr>
					</tbody>
				</table>
			</li>
		</ul>
		
		<div class="form-element-options">
			<ul class="inline">
				<li>
					<input id="is-required-{$randomId}" type="checkbox" name="widget[{$randomId}][is_required]" value="1" {if $widget.is_required}checked="checked"{/if} />
					<label for="is-required-{$randomId}">{$lang.Addon_Surveys_WidgetSelectOptionRequiresAnAnswer}</label>
				</li>
				<li>
					<input id="is-random-{$randomId}" type="checkbox" name="widget[{$randomId}][is_random]" value="1" {if $widget.is_random}checked="checked"{/if} />
					<label for="is-random-{$randomId}">{$lang.Addon_Surveys_WidgetSelectOptionRandomize}</label>
					<img class="tooltip" src="images/help.gif" alt="{$lang.Addon_Surveys_WidgetSelectTooltipTitleRandom}" title="{$lang.Addon_Surveys_WidgetSelectTooltipDescriptionRandom}" />
				</li>
				<li>
					<select name="widget[{$randomId}][is_visible]">
						<option value="1" {if $widget.is_visible == '1'}selected="selected"{/if}>{$lang.Addon_Surveys_WidgetSelectOptionVisibleToEveryone}</option>
						<option value="0" {if $widget.is_visible == '0'}selected="selected"{/if}>{$lang.Addon_Surveys_WidgetSelectOptionVisibleToAdministrators}</option>
					</select>
					<img class="tooltip" src="images/help.gif" alt="{$lang.Addon_Surveys_WidgetSelectTooltipTitleVisibility}" title="{$lang.Addon_Surveys_WidgetSelectTooltipDescriptionVisibility}" />
				</li>
				<li class="clear"></li>
			</ul>
		</div>
	</div>
</li>
