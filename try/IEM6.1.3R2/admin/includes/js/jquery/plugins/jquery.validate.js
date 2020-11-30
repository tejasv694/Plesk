/**
 * @name     jQuery validate
 * @desc     A jQuery form handling and validation plugin
 * @requires jquery.plugin
 * 
 * @p
 *     jQuery Validate (not Jorn's Plugin) is designed with flexibility in mind, however, it still
 *     caters to those who need a plugin to work out of the box.
 * @p
 *     By default, it will append error messages wrapped in x/html label tags
 *     after the form element with a class of error when the form is submitted.
 *     However, it can be customized through options so that it will wrap 
 *     messages in a different element, give it a different className and
 *     validate on blur.
 * @p
 *     It allows even more behavioral cusomization through error handlers and
 *     submit handlers. Other customization options include being able to set
 *     dependecies on certain elements. That is, making one element depend
 *     on the validity of certain, custom rules that are not set as validators.
 * 
 * @version     1.1b
 * @date        2009-07-13
 * 
 * @copyright
 *     Copyright (c) 2008 - 2009 Trey Shugart (jquery.illax.in/)
 * 
 * @license
 *     Dual licensed under: 
 *         MIT - (http://www.opensource.org/licenses/mit-license.php) 
 *         GPL - (http://www.gnu.org/licenses/gpl.txt)
 */
;(function($) {
	
	var 
		_errorHandlers  = {},
		_submitHandlers = {},
		_validators     = {},
		_defaultConfig = {
			useClassAsType            : true,
			useTitleAsError           : true,
			validateOnBlur            : false,
			validateOnBlurAfterSubmit : false,
			focus                     : true,
			filter                    : ':enabled',
			ignore                    : ':hidden',
			errorHandler              : 'default',
			blurHandler               : 'default',
			submitHandler             : 'default',
			errorWrapper              : 'label',
			errorClass                : 'error'
		};
	
	
	
	/**
	 * @name jQuery.fn.form
	 * @desc Shortcut for initializing and validating all in one call. This is
	 *       generally how jQuery.form will be used. This utilizes custom submit
	 *       handlers, error handlers, dependencies and settings while also 
	 *       providing default behavior if nothing is set.
	 *       
	 *       By Default, errors will be appended after the form element and wrapped
	 *       in a &lt;label&gt; element with the for attribute the same as the 
	 *       field's id attribute.
	 * 
	 * @param {Object} options The opitions to be used with this form
	 * 
	 * @return {Object}
	 */
	function form() {
		return this;
	}
	
	/**
	 * @name jQuery.form.setErrorHandler
	 * @desc Sets an error handler and gives it a name so it can be referenced. The callback handles
	 *       the appending and showing of errors. Two parameters are passed to the callback. The first
	 *       is the form object and the second is an array of objects containing object.field and 
	 *       object.message. The former is the field in which the error occurred, and the latter 
	 *       is the error message.
	 * @param {String}   name     The name of the error handler
	 * @param {Function} callback The callback to handle the errors
	 * @return The error handler that was just set
	 */
	form.setErrorHandler = function(name, callback) {
		_errorHandlers[name] = callback;
		
		return this;
	}
	
	/**
	 * @name jQuery.form.setSubmitHandler
	 * @desc Sets a submit handler and gives it a name so it can be referenced. The callback handles
	 *       how the form is submitted and whether it is submitted (by returning true or false). The
	 *       only parameter passed to the callback is the form object.
	 * 
	 * @param {String} name       The name of the submit handler
	 * @param {Function} callback The callback to handle the submission
	 * 
	 * @return The submit handler that was just set
	 */
	form.setSubmitHandler = function(name, callback) {
		_submitHandlers[name] = callback;
		
		return this;
	}
	
	/**
	 * @name jQuery.form.setValidator
	 * @desc Adds a validation callback for a certain type of field and gives
	 *       it an optional default message. Two parameters are passed to the
	 *       callback. The first is the form object and the second is the
	 *       field being validated.
	 * 
	 * @param {String}   type           The type/name of the validator being added
	 * @param {Function} callback       The callback performed to perform validation. Returns true or false indicating pass or failure respectively.
	 * @param {String}   defaultMessage The default message to be displayed upon error if no other error messages are set.
	 * 
	 * @return The validator that was just set
	 */
	form.setValidator = function(type, callback, defaultMessage) {
		_validators[type]              = {};
		_validators[type]['validator'] = callback;
		_validators[type]['message']   = defaultMessage;
		
		return _validators[type];
	},
	
	/**
	 * 
	 */
	form.removeValidator = function(type) {
		_validators = $(_validators).filter(function() {
			$(this).get(0).type !== 'type';
		});
		
		return _validators;
	},
	
	form.prototype = {
		
		/**
		 * Initializes the form and sets up validation.
		 * 
		 * @return object jQuery.fn.form
		 * 
		 * @param object[optional] config
		 */
		init : function(config) {
			var self  = this;
			var forms = $(this).filter('form');
			
			forms.each(function() {
				var form   = $(this);
				var config = form.form().config($.extend({}, _defaultConfig, config)).config();
				
				// if we aren't validating on blur, then don't set the event
				if (config.validateOnBlur && !config.validateOnBlurAfterSubmit) {
					_setBlurHandler.apply(this);
				}
				
				// unbind and rebind to prevent double-binding
				form.unbind('submit.form').bind('submit.form', function() {
					if (config.validateOnBlurAfterSubmit) {
						_setBlurHandler.apply(this);
					}
					
					if (self.validate().hasErrors()) {
						self.handleErrors();
						
						return false;
					}
					
					return self.handleSubmit();
				});
				
				
				
				/**
				 * Sets the blur handler
				 */
				function _setBlurHandler() {
					// if we are validating on blur...
					if (config.validateOnBlur) {
						_getFields.apply(form).each(function() {
							var field = $(this);

							// unbind and rebind
							if (!field.data('form.hasBlurHandler')) {
								field.data('form.hasBlurHandler', true).unbind('blur.form').bind('blur.form', function() {
									$(this).form().validate().handleErrors(config.blurHandler);
								});
							}
						});
					}
				}
			});
		},
		
		/**
		 * @name jQuery.fn.handleErrors
		 * @desc This method as well as being used internally, can also be used for more
		 *       ways to customize form error handling. This invokes the proper error
		 *       handler for the selected form. Usually, in situations such as this,
		 *       validate() will be called prior to calling handleErrors since it 
		 *       validates the form and attaches the errors to their respective fields.
		 * 
		 * @param {String|Function} name The name of the handler to invoke. If unspecified, the handler is taken from the options attached to the selected form.
		 */
		handleErrors : function(name) {
			var
				form   = _getForm.apply(this),
				errors = this.getErrors(),
				func   = typeof name !== 'undefined' 
					? name 
					: form.form().config().errorHandler;
			
			return $.isFunction(func) 
				? func.apply(form, [errors]) 
				: _errorHandlers[func].apply(form, [errors]);
		},
		
		/**
		 * The same as handleErrors, but invokes form submission.
		 * 
		 * @param mixed name Behaves the same way as handleErrors
		 */
		handleSubmit : function(name) {
			var
				$form   = _getForm.apply(this),
				$fields = _getFields.apply(this),
				func    = typeof name !== 'undefined' ? name : $form.form().config().submitHandler;
			
			return $.isFunction(func) 
				? func.apply($form, [$fields]) 
				: _submitHandlers[func].apply($form, [$fields]);
		},
		
		/**
		 * Builds a form object from the specified form's fields. If arguments are
		 * passed, they are expected to be strings of each form elements name that
		 * you want to return. If the first argument is an array, then that is
		 * expected to contain all of the names of the fields you want to return.
		 * 
		 * @return object jQuery set of fields
		 * 
		 * @param mixed filterBy String or Array of field names to return
		 */
		getFields : function(filterBy) {
		
			var
				$form      = _getForm.apply(this),
				filterBy   = typeof filterBy === 'string' ? [filterBy] : filterBy || [],
				selectors  = [];
			
			$.each(filterBy, function(i, el) {
				selectors[selectors.length] = ':input[name="' + el + '"]';
			});
			
			return selectors.length
				? $form.find(selectors.join(', '))
				: $form.find(':input');
		},
		
		/**
		 * Sets the type or types (depending on if a string or array is passed)
		 * of the fields in the collection. If a type already exists, this will 
		 * then be an additional type. If this type exists then it will be 
		 * overwritten.
		 * 
		 * @return object
		 * 
		 * @param mixed types A string or array of types to set the field to
		 */
		setTypes : function(types) {
			return $(this).each(function(i, field) {
				var types = typeof types === 'string' ? [types] : types;
				
				$.each(types, function(ii, type) {
					_add(field, 'type', type);
				});
			});
		},
		
		/**
		 * Removes the passed type(s) from the selected fields
		 * 
		 * @return object
		 * 
		 * @param mixed types A string or array of types to remove from the field
		 */
		removeTypes : function(str) {
			return $(this).each(function(i, field) {
				var types = typeof types === 'string' ? [types] : types;
				
				$.each(types, function(ii, type) {
					_remove(field, 'type', type);
				});
			});
		},
		
		/**
		 * Returns the type of a single field
		 * 
		 * @return array Array of types
		 */
		getTypes : function() {
			return _get(this, 'type');
		},
		
		/**
		 * Checks to see if the passed field is a given type
		 * 
		 * @return boolean
		 * 
		 * @param string type The type to check the field against
		 */
		isType : function(type) {
			var form = _getForm.apply(this).eq(0),
				field = $(this);
			
			if (form.form().config().useClassAsType && field.hasClass(type)) {
				return true;
			}
			
			var types = _get(field, type);
			
			if (types && $.inArray(type, types)) {
				return true;
			}
			
			return false;
		},
		
		/**
		 * Filters any fields that don't match the given type
		 * 
		 * @return object
		 * 
		 * @param string type The type to filter by
		 */
		filterByType : function(type) {
			return $(this).filter(function() {
				return $(this).form().isType(type);
			});
		},
		
		/**
		 * Retrieves all error messages associated with the specified form and returns
		 * an array.
		 * 
		 * @return array Array of Objects that contain the field object and error messages array
		 */
		getErrors : function() {
			var errors = [];
			
			_getFields.apply(this).each(function(i, field) {
				var fieldErrors = _get(field, 'errors') || [];
				
				if (typeof fieldErrors !== 'undefined') {
					$.each(fieldErrors, function(ii, error) {
						errors[errors.length] = {
							field   : $(field),
							message : error
						};
					});
				}
			});
			
			return errors;
		},
		
		/**
		 * Checks to see if the current form has any errors
		 * 
		 * @return boolean
		 */
		hasErrors : function() {
			return $(this).form().getErrors().length > 0 ? true : false;
		},
		
		/**
		 * Sets an error message for a field with a specific type
		 * 
		 * @return object
		 * 
		 * @param string type    The type of error to attach the message to
		 * @param string message The message to set
		 */
		setErrorMessage : function(type, message) {
			return $(this).each(function(index, field) {
				var $field = $(field);
				
				if ($field.form().isType(type))
					_add($(field), 'errorMessages.' + type, message);
			});
		},
		
		/**
		 * Retrieves error messages for a given type on the given field. Error
		 * messages are defined manually using setErrorMessage, in the
		 * elements title attribute (or specified attribute), or by using the
		 * default message supplied by the validator.
		 * 
		 * @return string The message set for that type
		 * 
		 * @param string type
		 */
		getErrorMessage : function(type) {
			var $form  = _getForm.apply(this);
			var $field = $(this);
			var msg    = _get($field, 'errorMessages.' + type);
			
			msg = typeof msg !== 'undefined' && msg !== '' 
				? msg 
				: $form.form().config().useTitleAsError 
					? $field.attr('title') 
					: undefined;
			
			msg = typeof msg !== 'undefined' && msg !== '' 
				? msg 
				: _validators[type].message;
			
			return msg;
		},
		
		/**
		 * Checks to see if the given field is valid for the given type. Only
		 * works on the first matched element and assumes it's a form field.
		 * If the validator doesn't exist, it returns true.
		 */
		isValid : function(type) {
			return typeof _validators[type] === 'undefined'
				? true
				: _validators[type].validator.apply($(this).get(0));
		},
		
		/**
		 * Checks the validity of a form, sets error messages, checks dependencies
		 * and returns the form plugin.
		 * 
		 * @return boolean
		 */
		validate : function() {
			var errors  = 0;
			var $this   = $(this);
			var form    = _getForm.apply(this);
			var fields  = _getFields.apply(this);
			var options = form.form().config();
			
			// check types and classes against validators
			fields.filter(options.filter).not(options.ignore).each(function(i, field) {
				var field     = $(field);
				var curerrors = 0;
				
				// remove the current errors; they will be validated and
				// added again if they still exist
				field.removeData('form.errors');
				
				// iterate through each validator
				for (ii in _validators) {
					// check to see if the field is of the validator type and if so, validate it
					if ((form.form().config().useClassAsType && field.hasClass(ii)) || field.form().isType(ii)) {
						// if it isn't valid
						if (!field.form().isValid(ii)) {
							// add the error to the field
							_add(field, 'errors', field.form().getErrorMessage(ii));
							
							curerrors++;
							errors++;
						}
					}
				}
			});
			
			// check dependencies if the current field is valid
			fields.each(function(i, field) {
				var field        = $(field);
				var dependencies = _get(field, 'dependencies');
				
				// if there are dependencies
				if (typeof dependencies !== 'undefined') {
					// loop over each one
					$.each(dependencies, function(ii, dependency) {
						// and validate against the dependency in the same manner as a validator
						if ($.isFunction(dependency.callback) && !dependency.callback.apply(field)) {
							var msg = typeof dependency.errorMessage !== 'undefined' 
								? dependency.errorMessage 
								: field.form().getErrorMessage(ii);
							
							// add the error to the field
							_add(field, 'errors', msg);
							
							errors++;
						}
					});
				}
			});
			
			return this;
		},
		
		/**
		 * Sets a dependency callback to be executed when and if the specified field passes all previous 
		 * validation rules. The call back must return a boolean value. True is a pass, false triggers an 
		 * error using the passed msg.
		 * 
		 * @return object
		 * 
		 * @param function fn  The callback that determines whether or not to trigger an error
		 * @param string   msg The message to display if fn returns false
		 */
		setDependency : function(fn, msg) {
			_getFields.apply(this).each(function(i, field) {
				_add(field, 'dependencies', {callback: fn, errorMessage: msg});
			});
			
			return this;
		},
		
		/**
		 * Removes a dependency callback from a field. Only works with
		 * named callbacks.
		 * 
		 * @return object
		 * 
		 * @param function fn
		 */
		removeDependency : function(fn) {
			_getFields.apply(this).each(function(i, field) {
				if (typeof fn === 'undefined') {
					$(field).removeData('form.dependencies');
				} else {
					_remove(field, 'dependencies', fn);
				}
			});
			
			return this;
		}
	}
	
	
	
	// add the plugin
	$.fn.plugin.add('form', form);
	
	
	
	// INTERNALS
	
	function _add(el, key, val) {
		return $(el).each(function(index, field) {
			var $field  = $(field);
			var c       = $field.data('form.' + key);
			c           = typeof c === 'undefined' ? [] : c;
			c[c.length] = val;
			$field.data('form.' + key, c);
		});
	};
	
	function _remove(el, key, val) {
		return $(el).each(function(index, field) {
			var $field       = $(field);
			var currentTypes = $field.data('form.' + key);
			
			if (typeof currentTypes === 'object') {
				var filtered = currentTypes.filter(function(t, i, arr) {
					return t !== val;
				});
				
				$field.data('form.' + key, filtered);
			}
		});
	};
	
	function _get(el, key) {
		return $(el).eq(0).data('form.' + key);
	};
	
	function _isForm() {
		var form = $(this).get(0);
		
		return form && form.tagName.toLowerCase() === 'form';
	}
	
	function _getForm() {
		return _isForm.apply(this) ? $(this).eq(0) : $(this).closest('form').eq(0);
	}
	
	function _getFields() {
		var form   = _getForm.apply(this);
		
		// if there isn't a form, we can't grab any fields, so 
		// we return an empty jQuery set
		if (!form.length) {
			return $();
		}
		
		var config = form.form().config();
		var fields = form.find(':input');
		
		if (config.ignore) {
			fields = fields.not(config.ignore);
		}
		
		if (config.filter) {
			fields = fields.filter(config.filter);
		}
		
		return fields;
	}
	
	
	
	// ERROR HANDLERS
	
	form.setErrorHandler('default', function(errors) {
		var form    = $(this);
		var fields  = form.form().getFields();
		var options = form.form().config();
		
		// remove the error classes on the fields
		fields.removeClass(options.errorClass);
		
		// remove each error label
		fields.each(function() {
			$(this).siblings('.' + options.errorClass).remove();
		});
		
		// handle the display for each error
		$.each(errors, function(i, error) {
			var field   = error.field;
			var forAttr = options.errorWrapper === 'label' 
					? ' for="' + field.attr('id') + '"' 
					: '';
			
			var fieldName          = field.attr('name');
			var fieldsWithSameName = $('[name="' + fieldName + '"]');
			var index              = fieldsWithSameName.index(field);
			
			if (fieldsWithSameName.length - 1 == index) {
				field.after('<' + options.errorWrapper + forAttr + ' class="' + options.errorClass + '">' + error.message + '</' + options.errorWrapper + '>');
				field.addClass(options.errorClass);
			}
		});
		
		// focus if we are focusing
		if (options.focus) {
			errors[0].field.focus();
		}
	});
	
	
	
	// SUBMIT HANDLERS
	
	form.setSubmitHandler('default', function(fields) {
		var form    = $(this);
		var options = form.form().config();
		
		form.find(options.errorWrapper + '.' + options.errorClass).remove();
		fields.removeClass(options.errorClass);
		
		return true;
	});
	
	
	
	// VALIDATORS
	
	form.setValidator('required', function() {
		var field = $(this),
			form  = field.closest('form');
		
		// if it is a checkbox or radio button, then we need to check to see if one of the
		// fields with the same name is checked
		if (field.is(':checkbox, :radio')) {
			return otherFields = $('[name="' + field.attr('name') + '"]:checked').length
				? true
				: false;
		} else {
			if (/^\s*$/g.test((field.val() || '').toString())) {
				return false;
			}
		}
		
		return true;
	}, 'This field is required');
	
	form.setValidator('email', function() {
		var field = $(this),
			form  = field.closest('form');
		
		if (!field.form().isValid('required')) {
			return true;
		}
		
		return /[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9_\-\.]+\.[a-zA-Z]+/.test(field.val());
	}, 'Please enter a valid email address');
	
	/**
	 * Checks to see if the value is a number
	 */
	form.setValidator('number', function() {
		var field = $(this);
		
		if (!field.form().isValid('required')) {
			return true;
		}
		
		return field.val() === '' || /\d/.test(field.val());
	}, 'Value must contain a number');
	
	/**
	 * Validates a minimum value
	 */
	form.setValidator('min', function() {
		var field = $(this),
			val   = field.val();
		
		if (!field.form().isValid('required')) {
			return true;
		}
		
		var val = parseFloat((val || '').toString().replace(/[^\.^\-\d]/g, '') || 0),
			min = field.data('form.validators.min.number');
		
		min = parseFloat(typeof min === 'number' ? min : $(min).val());
		
		return val >= min;
	}, 'Value is too small');
	
	/**
	 * Validates a maximum value
	 */
	form.setValidator('max', function() {
		var field = $(this);
		
		if (!field.form().isValid('required')) {
			return true;
		}
		
		var val = parseFloat((field.val() || '').toString().replace(/[^\.^\-\d]/g, '') || 0);
			max = $field.data('form.valiators.max.number');
		
		max = parseFloat(typeof max === 'number' ? max : $(max).val());
		
		return val <= max;
	}, 'Value is to large');
	
})(jQuery);
