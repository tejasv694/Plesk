<li id="{$randomId}" class="form-element {type: 'text'}">
	<input type="hidden" name="widget[{$randomId}][id]" value="{$widget.id}" />
	<input type="hidden" name="widget[{$randomId}][type]" value="text" />
	<input type="hidden" name="widget[{$randomId}][field][0][id]" value="{$widgetFields.0.id}" />
	
	<div class="ui-draggable-handle">
		<div class="form-element-title">
			<span class="form-element-required">*</span>
			<span class="title"><input class="edit-in-place example-field strong" type="text" name="widget[{$randomId}][name]" value="{$widget.name}" title="{$lang.Addon_Surveys_WidgetTextDefaultName}" /></span>
			<a class="form-element-minimize" href="#" title="{$lang.Addon_Surveys_WidgetToggleTitle}">{$lang.Addon_Surveys_WidgetTextToggle}</a>
			<a class="form-element-duplicate" href="#" title="{$lang.Addon_Surveys_WidgetDuplicateTitle}">{$lang.Addon_Surveys_WidgetTextDuplicate}</a>
			<a class="form-element-remove" href="#" title="{$lang.Addon_Surveys_WidgetRemoveTitle}">{$lang.Addon_Surveys_WidgetRemove}</a>
		</div>
	</div>
	
	<div class="form-element-content">
		<input class="edit-in-place example-field em light" type="text" name="widget[{$randomId}][description]" value="{$widget.description}" title="{$lang.Addon_Surveys_WidgetTextDefaultDescription}" />
		
		<ul>
			<li><input type="text" value="{$lang.Addon_Surveys_WidgetTextValueText}" disabled="disabled" /></li>
		</ul>
		
		<div class="form-element-options">
			<ul class="inline">
				<li>
					<input id="is-required-{$randomId}" type="checkbox" name="widget[{$randomId}][is_required]" value="1" {if $widget.is_required}checked="checked"{/if} />
					<label for="is-required-{$randomId}">{$lang.Addon_Surveys_WidgetTextOptionRequiresAnAnswer}</label>
				</li>
				<li>
					<select name="widget[{$randomId}][is_visible]">
						<option value="1" {if $widget.is_visible == '1'}selected="selected"{/if}>{$lang.Addon_Surveys_WidgetTextOptionVisibleToEveryone}</option>
						<option value="0" {if $widget.is_visible == '0'}selected="selected"{/if}>{$lang.Addon_Surveys_WidgetTextOptionVisibleToAdministrators}</option>
					</select>
					<img class="tooltip" src="images/help.gif" alt="{$lang.Addon_Surveys_WidgetTextTooltipTitleVisibility}" title="{$lang.Addon_Surveys_WidgetTextTooltipDescriptionVisibility}" />
				</li>
				<li class="clear"></li>
			</ul>
		</div>
	</div>
</li>
