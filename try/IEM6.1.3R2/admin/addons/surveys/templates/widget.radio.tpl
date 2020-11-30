<script type="text/javascript">

	// the radio widget takes some logic from the checkbox js
	jFrame.getInstance('moduleForm').dispatch(['widget.checkbox', 'widget.radio'], {
			context : '#{$randomId}'
		});

</script>

<li id="{$randomId}" class="form-element {type: 'radio'}">
	<input type="hidden" name="widget[{$randomId}][id]" value="{$widget.id}" />
	<input type="hidden" name="widget[{$randomId}][type]" value="radio" />
	
	<div class="ui-draggable-handle">
		<div class="form-element-title">
			<span class="form-element-required">*</span>
			<span class="title"><input class="edit-in-place example-field strong" type="text" name="widget[{$randomId}][name]" value="{$widget.name}" title="{$lang.Addon_Surveys_WidgetRadioDefaultName}" /></span>
			<a class="form-element-minimize" href="#" title="{$lang.Addon_Surveys_WidgetToggleTitle}">{$lang.Addon_Surveys_WidgetTextToggle}</a>
			<a class="form-element-duplicate" href="#" title="{$lang.Addon_Surveys_WidgetDuplicateTitle}">{$lang.Addon_Surveys_WidgetTextDuplicate}</a>
			<a class="form-element-remove" href="#" title="{$lang.Addon_Surveys_WidgetRemoveTitle}">{$lang.Addon_Surveys_WidgetRemove}</a>
		</div>
	</div>
	
	<div class="form-element-content">
		<input class="edit-in-place example-field em light" type="text" name="widget[{$randomId}][description]" value="{$widget.description}" title="{$lang.Addon_Surveys_WidgetRadioDefaultDescription}" />
		
		<ul>
			<li style="max-height: 250px; overflow: auto;">
				<table style="margin: 5px 0; padding: 0; border-collapse: collapse; border: 0;">
					<tbody class="form-element-option-list">
						{foreach from=$widgetFields key=fieldIndex item=widgetField}
							<tr>
								<td style="vertical-align: top">
									<input type="hidden" name="widget[{$randomId}][field][{$fieldIndex}][id]" value="{$widgetField.id}" />
									<input id="selected-{$fieldIndex}-{$randomId}" type="radio" name="widget[{$randomId}][field][{$fieldIndex}][is_selected]" value="1" {if $widgetField.is_selected}checked="checked"{/if} />
								</td>
								<td><input id="label-$fieldIndex-{$randomId}" class="edit-in-place example-field" type="text" name="widget[{$randomId}][field][{$fieldIndex}][value]" value="{$widgetField.value}" title="{$lang.Addon_Surveys_WidgetValueField}1" /></td>
								<td><a class="add-option-to-list" href="#"></a></td>
								<td><a class="remove-option-from-list" href="#" {if $fieldIndex == 0}style="display: none;"{/if} ></a></td>
								<td>
									<span class="add-other-container" style="display: none;">
									{$lang.Addon_Surveys_WidgetTextOr}
									<a class="add-other" href="#">{$lang.Addon_Surveys_WidgetTextAddOther}</a></span>
								</td>
							</tr>
						{/foreach}
						
						<tr class="other-row" {if !$widgetFieldOther}style="display: none;"{/if} >
							<td style="vertical-align: top">
								<input type="hidden" name="widget[{$randomId}][field][other][id]" value="{$widgetFieldOther.id}" />
								<input type="hidden" name="widget[{$randomId}][field][other][is_other]" value="1" />
								<input type="radio" name="widget[{$randomId}][field][other][is_selected]" value="1" {if $widgetFieldOther.is_selected}checked="checked"{/if} />
							</td>
							<td>
								<div>
									<input class="edit-in-place example-field" 
										   type="text" 
										   name="widget[{$randomId}][field][other][other_label_text]" 
										   value="{if $widgetFieldOther}{$widgetFieldOther.other_label_text}{else}{$lang.Addon_Surveys_WidgetTextOther}{/if}"
										   title="{$lang.Addon_Surveys_WidgetTextOther}"/>
								<div>
								<div>
									<label for="other-text-{$randomId}"><img src="images/nodejoin.gif" alt=" - " /></label>
									<input id="other-text-{$randomId}" type="text" value="{$lang.Addon_Surveys_WidgetValueOther}" disabled="disabled" style="width: 227px;" />
								</div>
							</td>
							<td></td>
							<td style="vertical-align: top;"><a class="remove-other" href="#"></a><td>
							<td></td>
						</tr>
					</tbody>
				</table>
			</li>
		</ul>
		
		<div class="form-element-options">
			<ul class="inline">
				<li>
					<input id="is-required-{$randomId}" type="checkbox" name="widget[{$randomId}][is_required]" value="1" {if $widget.is_required}checked="checked"{/if} />
					<label for="is-required-{$randomId}">{$lang.Addon_Surveys_WidgetRadioOptionRequiresAnAnswer}</label>
				</li>
				<li>
					<input id="is-random-{$randomId}" type="checkbox" name="widget[{$randomId}][is_random]" value="1" {if $widget.is_random}checked="checked"{/if} />
					<label for="is-random-{$randomId}">{$lang.Addon_Surveys_WidgetRadioOptionRandomize}</label>
					<img class="tooltip" src="images/help.gif" alt="{$lang.Addon_Surveys_WidgetRadioTooltipTitleRandom}" title="{$lang.Addon_Surveys_WidgetRadioTooltipDescriptionRandom}" />
				</li>
				<li>
					<select name="widget[{$randomId}][is_visible]">
						<option value="1" {if $widget.is_visible == '1'}selected="selected"{/if}>{$lang.Addon_Surveys_WidgetRadioOptionVisibleToEveryone}</option>
						<option value="0" {if $widget.is_visible == '0'}selected="selected"{/if}>{$lang.Addon_Surveys_WidgetRadioOptionVisibleToAdministrators}</option>
					</select>
					<img class="tooltip" src="images/help.gif" alt="{$lang.Addon_Surveys_WidgetRadioTooltipTitleVisibility}" title="{$lang.Addon_Surveys_WidgetRadioTooltipDescriptionVisibility}" />
				</li>
				<li class="clear"></li>
			</ul>
		</div>
	</div>
</li>
