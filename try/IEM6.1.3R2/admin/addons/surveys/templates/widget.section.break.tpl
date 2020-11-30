<li id="{$randomId}" class="form-element no-marker {type: 'section.break'}">
	<input type="hidden" name="widget[{$randomId}][id]" value="{$widget.id}" />
	<input type="hidden" name="widget[{$randomId}][type]" value="section.break" />
	
	<div class="form-element-title ui-draggable-handle">
		<a class="form-element-duplicate" href="#" title="{$lang.Addon_Surveys_WidgetDuplicateTitle}">{$lang.Addon_Surveys_WidgetTextDuplicate}</a>
		<a class="form-element-remove" href="#" title="{$lang.Addon_Surveys_WidgetRemoveTitle}">{$lang.Addon_Surveys_WidgetRemove}</a>
		
		<div class="form-element-hr"></div>
		<ul>
			<li><input class="edit-in-place example-field strong" type="text" name="widget[{$randomId}][name]" value="{$widget.name}" title="{$lang.Addon_Surveys_WidgetSectionBreakDefaultName}" /></li>
			<li><input class="edit-in-place example-field em light" type="text" name="widget[{$randomId}][description]" value="{$widget.description}" title="{$lang.Addon_Surveys_WidgetSectionBreakDefaultDescription}" /></li>
		</ul>
		<div class="form-element-hr"></div>
	</div>
</li>