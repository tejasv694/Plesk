;(function($) {

	var
		$                         = jQuery,
		lang                      = jFrame.registry.get('lang'),
		form                      = $('#form-canvas'),
		pageTitle                 = $('#page-title-form-name'),
		canvas                    = $('#canvas > ul'),
		canvasEmpty               = $('#canvas-empty'),
		loadingWidgets            = $('#loading-widgets'),
		formFields                = $('.tab-survey-designers-panel li'),
		fieldTemplate             = $('#field-template'),
		widgetTitles              = $('.form-element .ui-draggable-handle'),
		duplicateButtons          = $('.form-element-duplicate'),
		removeButtons             = $('.form-element-remove'),
		templates                 = $('#form-element-templates > ol > li'),
		emailFeedback             = $('#email-feedback'),
		cancelBtn                 = $('button.cancel'),
		emailFeedbackToContainer  = $('#email-feedback-to-container');
	
	// example field initialization
	form.find('[name*="name"]').exampleField().init({ onBeforeSubmit : false });
	form.find('[name*="description"]').exampleField().init();
	
	// ajax form
	form.ajaxForm({
			dataType : 'json',
			success  : function(json) {
	
				if (json.success) {
		
					if (json.redirect) {
						var newredirect = String(json.redirect);
						newredirect = newredirect.replace(/\&amp;/g,'&');				
						window.location = newredirect;
					
					} else {
						$('#MainMessage').successMessage(json.message);
					}
				} else {
			
					$('#MainMessage').errorMessage(json.message, json.messages);
					
					for (var i in json.errors) {
						var
							tabs     = $('.form-menu'),
							field    = $('[name="' + i + '"]'),
							tabField = tabs.find(field.selector);
						
						// if a field was found in the tabs
						if (tabField.length) {
							tabs.tabs('select', 1);
						}
						
						// if just a field was found, focus it
						if (field.length) {
							field.focus();
						}
						
						// we are only doing this for the first field
						break;
					}
				}
				
				$('.example-field[name*="description"]').removeAttr('disabled');
			}
		});
	
	// toggle the email feedback option depending on the state of the checkbox
	_toggleEmailFeedback();
	
	// initialize the required fields
	$('.form-element').each(function() {
		_toggleRequired(this);
	});
	
	// set default values
	$('#canvas .example-field').each(function() {
		if ($(this).val() == '') {
			$(this).val($(this).attr('title'));
		}
	});
	
	// initialize the tabs
	$('.form-menu').tabs();
	
	// clear the floating problem in firefox
	$('.form-menu').bind('tabsselect', function(event, ui) {
		$('.tab-survey-designers-panel ul').css("top", "0");		
	});	
	
	// detatch and float the left menu when scrolled past the window's bounds
	$('.tab-survey-designers-panel ul').float({
			offsetTop : 20
	});

	
	// add a class to the form fields in the menu so that we 
	// can identify them after they are dragged onto the canvas
	formFields.addClass('__widget_placeholder__');
	
	// initialize the canvas state
	_markEmpty();
	
	
	
	// form feedback email toggling
	emailFeedback.bind('click', _toggleEmailFeedback);
	
	// draggable form elements
	formFields.draggable({
		connectToSortable : canvas.selector,
		helper            : function() {
			var
				src  = $(this).css('background-image').replace(/url\(("|')?([^"]+)("|')?\)/i, '$2'),
				text = $(this).text();
		
			
			return $('#__template__form-element-drag-helper > div').template().parse({
				img  : '<img src="' + src + '" alt="' + text + '" />',
				text : text
			});
		}
	});
	
	// droppable and sortable canvas
	canvas
		.sortable({
			handle      : '.form-element-title',
			placeholder : 'ui-sortable-placeholder',
			axis        : 'y',
			delay       : 100,
			helper      : function(e, ui) {
					return $('#__template__form-element-sort-helper > div').template().parse();
				}
		});
	
	// bind sorting events

	canvas
		.bind('sortstart', function(e, ui) {
			ui.helper.find('p').text(ui.helper.text().replace('#{text}', ui.item.find('[name*="[label]"]:first').val()));
		})
		.bind('sortreceive', function(e, ui) {
			var 
				type   = ui.item.metadata().get('type'),
				action = ui.item.metadata().get('action') || type;
				//el     = _createWidget(type);
			
			// get the new widget's html and replace the placholder with it
			// section=module&action=custom&module=form&moduleController=build&moduleAction=textarea
			_markEmpty();
			
		
			$.getJSON('index.php?Page=Addons&Addon=surveys&Action=Build&ajax=1&widget=' + type, _addWidget);
			
			return;
		})
		.bind('sortover', function(e, ui) {
			_markEmpty();
		})
		.bind('sortdeactivate', function(e, ui) {
			_markEmpty();
		});

	
	// duplicate form elements when duplicate buttons are clicked
	$('.form-element-duplicate').live('click', function() {
		var
			widget = _getParentWidget(this),
			type   = widget.metadata().get('type');
		
		// insert a placeholder to be replaced
		// we have to give it some content or else it will not be displayed (but it's hidden anyways)
		widget.after('<li class="__widget_placeholder__">_</li>');
		
		// load it and replace the placeholder
		$.getJSON('index.php?Page=Addons&Addon=surveys&Action=Build&ajax=1&widget=' + type, _addWidget);
		
		return false;
	});
	
	// remove elements from canvas, live event
	removeButtons
		.bond('click', function() {
			var formElement = $(this).closest('.form-element');
			
			formElement.addClass('form-element-removing');
			
			var con = confirm('Are you sure you want to remove ' + $(this).closest('.form-element').find('.form-element-title :input').val() + '?\n\nThis action cannot be undone.');
			
			if (con) {
				var 
					id = formElement.find('> :hidden:first').val();
				
				formElement.remove();
				
				_markEmpty();
			} else {
				formElement.removeClass('form-element-removing');
			}
			
			return false;
		});
	
	// when the required checkbox is clicked, toggle the required star in the title
	$('[name$="[is_required]"]').bond('click', function() {
		_toggleRequired(_getParentWidget(this));
	});
	
	// minimizing form elements
	$('.form-element-minimize').live('click', function() {
		_minimize(this);
		
		return false;
	});
	
	// maximizing form elements
	$('.form-element-maximize').live('click', function() {
		_maximize(this);
		
		return false;
	});
	
	$('input#form-title').blur(function() {
		$('input[name="form[surveys_header_text]"]').val($('input#form-title').val());
	});
	
	
	// when the cancel button is pressed, confirm, then delete the form
	/*
	cancelBtn.bind('click', function() {
		if (confirm(lang.editFormConfirmCancel)) {
			window.location = 'index.php?section=module&action=custom&module=form&moduleController=admin&moduleAction=index';
		}
	}); */
	
	// uri browser
	// new IWPUrlBrowser($('#show-page-uri-browser'), $('#show-page-uri-browser').prev());
	
	
	/*
	 * Private functions
	 */
	
	/**
	 * @name        _randId
	 * @description If an object is passed, it applies the random id to it, otherwise it just generates one and returns it.
	 * @return      String - The random id that was just created.
	 * @param       obj    - The object to set the random id of, if any.
	 * @param       prefix - A prefix to prepend to the randomly generated id
	 */
	function _randId(obj, prefix) {
		var 
			prefix = prefix || 'id-',
			rand   = prefix + new Date().getTime();
		
		if (obj) {
			$(obj).eq(0).attr('id', rand);
		}
		
		return rand;
	}
	
	/**
	 * @name        _minimize
	 * @description Minimizes the form element container
	 * @return      Object Parent form element
	 * @param       obj The button that was clicked
	 */
	function _minimize(obj) 
	{
		$(obj)
			.removeClass('form-element-minimize')
			.addClass('form-element-maximize');
		
		return _getParentWidget(obj).find('.form-element-content').hide();
	}
	
	/**
	 * @name        _maximize
	 * @description Maximizes the form element container
	 * @return      Object Parent form element
	 * @param       obj The button that was clicked
	 */
	function _maximize(obj) 
	{
		$(obj)
			.removeClass('form-element-maximize')
			.addClass('form-element-minimize');
		
		return _getParentWidget(obj).find('.form-element-content').show();
	}
	
	function _getParentWidget(ofTHis)
	{
		var obj = $(ofTHis);
		
		if (!obj.is('.form-element')) {
			obj = obj.closest('.form-element');
		}
		
		return obj;
	}
	
	/**
	 * If the canvas is empty, it marks the template as empty, if not
	 * then it removes the marker. Returns whether the canvas is empty
	 * or not after marking it.
	 * 
	 * @return Boolean - Whether the canvas is empty or not.
	 */
	function _markEmpty() 
	{
		if (_isEmpty()) {
			canvasEmpty.show();
			
			return true;
		} else {
			canvasEmpty.hide();
			
			return false;
		}
	}
	
	/**
	 * @name        _getNumWidgets
	 * @description Returns the number of form elements on the canvas.
	 * @return      Integer - The length of the number of widgets present on the canvas.
	 */
	function _getNumWidgets() 
	{
		return canvas.find(' > .form-element').length;
	}
	
	/**
	 * @name        _isEmpty
	 * @description Returns whether the canvas is empty or not.
	 * @return      Boolean - Whether the canvas is empty or not.
	 */
	function _isEmpty() 
	{
		return _getNumWidgets() === 0 && canvas.find('.ui-sortable-placeholder').length === 0;
	}
	
	/**
	 * @name        _toggleEmailFeedback
	 * @description Toggles the email feedback option depending on the checkbox state.
	 * @return      Void
	 */
	function _toggleEmailFeedback()
	{
		if (emailFeedback.get(0).checked) {
			emailFeedbackToContainer.show();
		} else {
			emailFeedbackToContainer.hide();
		}
	}
	
	/**
	 * @name        _toggleRequired
	 * @description Toggles the required star depending on the "Requires an answer" checkbox state.
	 * @return      Void
	 */
	function _toggleRequired(widget)
	{
		var 
			widget   = $(widget),
			star     = widget.find('.form-element-required'),
			required = widget.find('[name$="[is_required]"]');
		
		return star[required.is(':checked') ? 'fadeIn' : 'fadeOut'](200);
	}
	
	/**
	 * @name        _createWidget
	 * @description Creates a new form element of type: type, and populates it's fields with
	 *              any fill data if it is passed in.
	 * @return      Object      - Returns the newly created widget.
	 * @param       String type - The type of widget to create.
	 */
	function _createWidget(type, action)
	{
		var 
			action   = action || type,
			randomId = _randId(null, 'form-element-'),
			el       = templates.metadata().filter('type', type);
		
		// if the template isn't saved yet, save it
		if (!$.fn.template.exists('form-element-' + type)) {
			el.template().save('form-element-' + type);
		}
		
		// load the template and parse it
		el = $.fn.template.load('form-element-' + type).template().parse({ randomId : randomId });
		
		// return it
		return el;
	}

	
	var 
	_dataKey = '__tooltip',
	_template = '<div style="border: 1px solid rgb(231, 227, 190); padding: 10px; display: none; z-index: 1000; position: absolute; width: 190px; background-color: rgb(254, 252, 213); color: rgb(0, 0, 0);"><span class="helpTip"><b>#{title}</b></span><br/><img height="5" width="1" alt="" src="images/blank.gif"/><br/><div class="helpTip" style="padding-left: 5px; padding-right: 5px;">#{content}</div></div>';

	// use event delegation so it works with elemets that 
	// haven't been added to the DOM yet
	$('img.tooltip')
		// show the tooltip when moused over
		.live('mouseover', function(e) {
			var t = $(this);
			
			// reference to the objects tooltip if it doesn't exist
			if (!t.data(_dataKey)) {
				var template = $(_template).template().parse({
						title   : t.attr('alt'),
						content : t.attr('title') 
					}).appendTo('body');
				
				t
					.data(_dataKey, template)
					.removeAttr('title');
			}
			
			// show the tooltip
			t.data(_dataKey)
				.css({
					position : 'absolute',
					top      : e.pageY,
					left     : e.pageX
				})
				.fadeIn();
		})
		// close
		.live('mouseout', function(e) {
			$(this).data(_dataKey).fadeOut();
		})
		// when the mose is moved, move the tooltip with it
		.live('mousemove', function(e) {
			var t = $(this);
			
			// Either IE fires the mousemove event before the mouseover event, or the mouseover
			// event doesn't complete before the mousemove event fires. Whichever way, we need
			// to check to make sure the data is stored on the tooltip, otherwise we can't do
			// anything with it and IE will spit the dummy.
			if (!t.data(_dataKey)) {
				return false;
			}
			
			var
				w    = $(window),
				t    = t.data(_dataKey),
				wh   = w.height(),
				ww   = w.width(),
				st   = w.scrollTop(),
				sl   = w.scrollLeft(),
				bh   = $('body').height(),
				oh   = t.outerHeight(),
				ow   = t.outerWidth(),
				top  = e.pageY,
				left = e.pageX + 10;
			
			if (((e.pageY - st) + oh) > wh) {
				top = (wh + st) - oh - 10;
			}
			
			if (((e.pageX - sl) + ow) > ww) {
				left = e.pageX - ow - 10;
			}
			
			t.css({
				top  : top,
				left : left
			});
		});
	
	/**
	 * Adds a widget to the canvas. Expects a JSON object as the only parameter.
	 * The JSON object must have an html property of the html to use for the widget.
	 * 
	 * @param object json
	 * 
	 * @return void
	 */
	function _addWidget(json)
	{
		var html = $(json.html);
		
		// replace the placeholder with the widget
		canvas.find('.__widget_placeholder__').replaceWith(html);
		
		// find the first text box in the newly created element and focus it

		
		// initialize name example fields; 
		// we have to use a setTimeout because the above .select() will cause IE7 to trigger the
		// clearing of the field (example field behavior) even though it is bound after
		setTimeout(function() {
			html.find('[name*="name"]').exampleField().init({ onBeforeSubmit : false });
		}, 100);
		
		html.find(':text:first').select();
		
		// initialize description example fields
		html.find('[name*="description"]').exampleField().init();
		
		_markEmpty();
	}

})(jQuery);