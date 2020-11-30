/**
 * @name        Table Selector
 * @description Makes table rows that contain a radio or checkbox into a selectable list. A good and user friendly alternative to multi-select lists.
 * @version     1.0.0
 * @date        2009-05-30
 * @copyright   Copyright (c) 2009 Trey Shugart
 * @license     BSD - (http://www.opensource.org/licenses/bsd.php)
 */
;(function($) {
	
	$.fn.tableSelector = function(config) {
		var 
			tables = $(this).filter('table'),
			config = $.extend({
					classSelected      : 'selected',
					allowRadioDeselect : false
				}, config);
		
		tables.each(function() {
			var 
				trs    = $(this).find('tr'),
				inputs = trs.find(':checkbox, :radio');
			
			inputs.bind('click', function(e) {
				$(this).closest('tr').trigger('click');
			});
			
			trs.bind('click', function() {
				// select a radio or checkbox
				var input = $(this).find(':checkbox, :radio').get(0);
				
				// check or uncheck the input field if it is allowed
				if (input.checked && ($(input).is(':checkbox') || config.allowRadioDeselect)) {
					input.checked = false;
				} else {
					input.checked = true;
				}
				
				// toggle classes
				_addRemoveClasses();
			});
			
			// toggle classes
			_addRemoveClasses();
			
			
			
			function _addRemoveClasses() {
				// now add or remove classes based on what checkboxes are checked
				trs.filter(':has(:radio:checked, :checkbox:checked)').addClass(config.classSelected);
				trs.filter(':has(:radio:not(:checked), :checkbox:not(:checked))').removeClass(config.classSelected);
			}
		});
	}

})(jQuery);