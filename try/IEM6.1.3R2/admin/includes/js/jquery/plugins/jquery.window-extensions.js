/**
 * These are extensions for the window plugin.
 */
;(function($) {
	
	/**
	 * Sets the defalut configuration for the window
	 */
	$.extend($.fn.window.defaultConfig, {
		position               : 'fixed',//$.browser.msie && $.browser.version.match(/^(6|7)/) ? 'absolute' : 'fixed',
		selectorTitle          : '.title',
		selectorContent        : '.content',
		selectorClose          : '.modalClose',
		width                  : '50%',
		autoResizeOffsetHeight : 8,
		animateOpen            : function() {
			// overridden to center the window before it is opened
			$(this).window().center().jQuery().show();
		}
	}, $.fn.window.defaultConfig);
	
	/**
	 * This will be the default template used when using jQuery.fn.window.create()
	 * to create a new window. Certain cassNames are still there to be compatible
	 * with the iModal wrapper
	 */
	$.fn.window.defaultTemplate = ''
		+ '<div style="overflow: visible">'
			+ '<a class="modalClose" title="Close"></a>'
			
			+ '<div class="top-border"></div>'
			+ '<div class="right-border"></div>'
			+ '<div class="bottom-border"></div>'
			+ '<div class="left-border"></div>'
			
			+ '<div class="top-left-corner"></div>'
			+ '<div class="top-right-corner"></div>'
			+ '<div class="bottom-right-corner"></div>'
			+ '<div class="bottom-left-corner"></div>'
			+ '<div class="content-container" style="overflow: visible">'
				+ '<div class="title"></div>'
				
				+ '<div class="content"></div>'
				
				+ '<div class="buttons" style="display: none;"></div>'
			+ '</div>'
		+ '</div>';
	
	/**
	 * Sets the buttons for the window. This assumes the default template is being used.
	 * 
	 * @return jQuery.fn.window
	 * 
	 * @param mixed buttons A string or object of buttons to be set in the buttons area.
	 */
	$.fn.window.fn.buttons = function(buttons) {
		// if set to true, show the buttons
		
		if (buttons === true) {
			this.jQuery().find('.content').addClass('with-buttons');
			this.jQuery().find('.buttons').show();
		// if set to false, hide the buttons
		} else if (buttons === false) {
			this.jQuery().find('.content').removeClass('with-buttons');
			this.jQuery().find('.buttons').hide();
		// if it is anything else and not defined, set the buttons then show them
		} else if (typeof buttons !== undefined) {
			// normalize to a jQuery array
			var buttons = $(buttons);
			
			// reset the innerHTML and set the buttons
			this.jQuery().find('.buttons').html('').append(buttons);
			
			// show the buttons
			this.buttons(true);
		}
		
		return this;
	}

})(jQuery);