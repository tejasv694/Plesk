<script type="text/javascript">

	jFrame.getInstance('moduleForm').dispatch('widget.file', {
			context : '#{$randomId}'
		});

</script>

<li id="{$randomId}" class="form-element {type: 'file'}">
	<input type="hidden" name="widget[{$randomId}][id]" value="{$widget.id}" />
	<input type="hidden" name="widget[{$randomId}][type]" value="file" />
	<input type="hidden" name="widget[{$randomId}][field][0][id]" value="{$widgetFields.0.id}" />
	
	<div class="ui-draggable-handle">
		<div class="form-element-title">
			<span class="form-element-required">*</span>
			<span class="title"><input class="edit-in-place example-field strong" type="text" name="widget[{$randomId}][name]" value="{$widget.name}" title="{$lang.Addon_Surveys_WidgetFileDefaultName}" /></span>
			<a class="form-element-minimize" href="#" title="{$lang.Addon_Surveys_WidgetToggleTitle}">{$lang.Addon_Surveys_WidgetTextToggle}</a>
			<a class="form-element-duplicate" href="#" title="{$lang.Addon_Surveys_WidgetDuplicateTitle}">{$lang.Addon_Surveys_WidgetTextDuplicate}</a>
			<a class="form-element-remove" href="#" title="{$lang.Addon_Surveys_WidgetRemoveTitle}">{$lang.Addon_Surveys_WidgetRemove}</a>
		</div>
	</div>
	
	<div class="form-element-content">
		<input class="edit-in-place example-field em light" type="text" name="widget[{$randomId}][description]" value="{$widget.description}" title="{$lang.Addon_Surveys_WidgetFileDefaultDescription}" />
		
		<ul>
			<li>
				<input type="text" value="{$lang.Addon_Surveys_WidgetFileValueFile}" disabled="disabled" style="width: 200px;" />
				<button type="button" disabled="disabled">{$lang.Addon_Surveys_WidgetFileTextBrowse}</button>
			</li>
		</ul>
	
		<div class="form-element-options">
			<ul class="inline">
				<li>
					<input id="is-required-{$randomId}" type="checkbox" name="widget[{$randomId}][is_required]" value="1" {if $widget.is_required}checked="checked"{/if} />
					<label for="is-required-{$randomId}">{$lang.Addon_Surveys_WidgetFileOptionRequiresAnAnswer}</label>
				</li>
				<li>
					<select name="widget[{$randomId}][is_visible]">
						<option value="1" {if $widget.is_visible == '1'}selected="selected"{/if}>{$lang.Addon_Surveys_WidgetFileOptionVisibleToEveryone}</option>
						<option value="0" {if $widget.is_visible == '0'}selected="selected"{/if}>{$lang.Addon_Surveys_WidgetFileOptionVisibleToAdministrators}</option>
					</select>
					<img class="tooltip" src="images/help.gif" alt="{$lang.Addon_Surveys_WidgetFileTooltipTitleVisibility}" title="{$lang.Addon_Surveys_WidgetFileTooltipDescriptionVisibility}" />
				</li>
				<li class="form-element-file-types">
					<input id="allow-all-file-types-{$randomId}" type="checkbox" name="widget[{$randomId}][all_file_types]" value="1" {if $widget.allowed_file_types ==""}checked="checked"{/if} />
					<label for="allow-all-file-types-{$randomId}">{$lang.Addon_Surveys_WidgetFileOptionAllowAllFileTypes}</label>
					<span style="display: none;">
						<input type="text" name="widget[{$randomId}][allowed_file_types]" value="{$widget.allowed_file_types}" />
						<img class="tooltip" src="images/help.gif" alt="{$lang.Addon_Surveys_WidgetFileTooltipTitleAllowedFileTypes}" title="{$lang.Addon_Surveys_WidgetFileTooltipDescriptionAllowedFileTypes}" />
					</span>
				</li>
				<li class="clear"></li>
			</ul>
		</div>
	</div>
</li>