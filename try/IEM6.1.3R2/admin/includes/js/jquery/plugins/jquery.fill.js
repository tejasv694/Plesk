/**
 * jQuery Autofilling Plugin (fill)
 * 
 * Will take a set of elements, take any fields from forms and filter out any that aren't
 * forms and fill them with values from a JSON array. The passed argument can either be
 * an array or a URI to a resource that will pass back a JSON encoded array.
 * 
 * @author Trey Shugart (jquery.illax.in)
 * @date   2009-05-20
 */
;(function($) {

	$.fn.fill = function(jsonOrUri, config, filterVal) {
		if ($.isFunction(config)) {
			var config = {
					filterKey : config
				};
		}
		
		// will store all found fields
		var 
			fields = $(this),
			config = $.extend({
					fillIfEmpty : false,
					beforeAll   : 'fill.beforeAll',
					afterAll    : 'fill.afterAll',
					beforeOne   : 'fill.beforeOne',
					afterOne    : 'fill.afterOne',
					filterKey   : function(key, val) {
							return '[name="' + key + '"]';
						},
					filterVal   : function(key, val) {
							return val;
						}
				}, config);
		
		if ($.isFunction(filterVal)) {
			config.filterVal = filterVal;
		}
		
		// if it is a string, then assume a URI
		if (typeof jsonOrUri === 'string') {
			// after the call is finished, fill the fields
			$.getJson(jsonOrUri, function(json) {
				_fill.apply(fields, [json]);
			});
		// otherwise assume it is a json encoded array and fill the fields
		} else {
			_fill.apply(fields, [jsonOrUri]);
		}
		
		
		
		/**
		 * Fills the elements applied to the function using the passed json. It
		 * checks to see if the json is json array is in the correct format for
		 * filling before actually filling and firing any events.
		 * 
		 * @return void
		 * @param  json A json object.
		 */
		function _fill(json) {
			// here we check to see if it actually is a proper JSON array, if not we assume it's a fill object
			if ($.isArray(json)) {
				json = json[0];
			}
			
			var elements = $(this);
			
			// fire the before all callback
			elements.trigger(config.beforeAll);
			
			$.each(json, function(key, val) {
				var 
					key = config.filterKey.apply(elements, [key, val]),
					val = config.filterVal.apply(elements, [key, val]),
					el  = elements.filter(key);
				
				// if we don't want to fill if the value is empty and the value is empty
				// then don't fill, don't fire any events and proceed to the next value
				if (!config.fillIfEmpty && !val) {
					return;
				}
				
				// if there is no element, continue the loop
				if (!el.length) {
					return;
				}
				
				// fire the before one callback
				el.trigger(config.beforeOne);
				
				// fill the values
				// automate checkbox and radio selection
				if (el.is(':checkbox, :radio')) {
					if (typeof val !== 'object') {
						val = [val];
					}
					
					$.each(val, function(valKey, valVal) {
						el.filter('[value="' + valVal + '"]').attr('checked', 'checked');
					});
				// otherwise implement default jquery .val behavior
				// covers select boxes and multiple select boxes
				} else {
					el.val(val);
				}
				
				// fire the after one
				el.trigger(config.afterOne);
			});
			
			// fire the after all callback
			elements.trigger(config.afterAll);
		}
	};

})(jQuery);