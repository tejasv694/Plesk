/**
 * jQuery Example Field Plugin
 * 
 * Manages default value behavior for text fields and textareas.
 * 
 * Required
 *     plugin - http://code.google.com/p/jquery-plugin-dev/source/browse/trunk/jquery.plugin.js
 * 
 * Optional
 *     bond - http://code.google.com/p/jquery-plugin-dev/source/browse/trunk/jquery.bond.js
 */
;(function($) {
	
	var _defaultConfig = {
			attr           : 'title',
			className      : 'example-field',
			clearVal       : '',
			onFocus        : 'clear',         // clear, disable or false
			onBlur         : 'default',       // default or false
			onBeforeSubmit : 'clear',         // clear, disable or false
			onAfterSubmit  : 'default'        // default or false
		};
	
	var _exampleField = function() {
		return this;
	}
	
	_exampleField.prototype = {
		
		init : function(config) {
			$(this).filter(':text, textarea').exampleField().config($.extend({}, _defaultConfig, config)).jQuery().each(function() {
				var field = $(this);
				var form  = field.closest('form');
				
				field
					.bind('focus.exampleField', function() {
						var field  = $(this);
						var config = field.exampleField().config();
						
						switch (config.onFocus) {
							case false:
							break;
							
							case 'disable':
								_disable(field, config);
							break;
							
							case 'clear':
							default:
								_clear(field, config);
						}
						
						field.removeClass(config.className);
					})
					.bind('blur.exampleField', function() {
						var field  = $(this);
						var config = field.exampleField().config();
						
						switch (config.onBlur) {
							case false:
							break;
							
							case 'default':
							default:
								_default(field, config);
						}
					});
				
				// bind the handler to the form once to iterate through each input field and take the appropriate action
				if (!_isBound()) {
					form.bind('submit.exampleField', function() {
						$(this).find(':text, textarea').each(function() {
							var field  = $(this);
							var config = field.exampleField().config();
							
							if (config) {
								switch(config.onBeforeSubmit) {
									case false:
									break;
									
									case 'disable':
										_disable(field, config);
									break;
									
									case 'clear':
									default:
										_clear(field, config);
								}
								
								// allow enough time to occur so that form data can be
								// sent before we re-initialize the data
								setTimeout(function() {
									switch(config.onAfterSubmit) {
										case false:
										break;
										
										case 'default':
										default:
											_default(field, config);
									}
								}, 100);
							}
						});
					});
				}
				

				
				/**
				 * Checks to see if the exampleField is bound to the form yet.
				 */
				function _isBound() {
					var ret    = false;
					var events = form.data('events');
					
					if (events && typeof events['submit'] !== 'undefined') {
						$.each(events['submit'], function(handle, handler) {
							if (events['submit'][handle].type == 'exampleField') {
								ret = true;
								
								return false;
							}
						});
					}
					
					return ret;
				}
				
				field.triggerHandler('blur.exampleField');
			});
		}
		
	}
	
	
	
	function _clear(field, config) {
		if (field.val() == field.attr(config.attr)) {
			field.val(config.clearVal);
		}
	}
	
	function _disable(field, config) {
		if (field.val() == field.attr(config.attr)) {
			field.attr('disabled', 'disabled');
		}
	}
	
	function _default(field, config) {
		if (field.val() == config.clearVal) {
			field.val(field.attr(config.attr));
		}
	}
	
	
	
	$.fn.plugin.add('exampleField', _exampleField);
	
})(jQuery);