;(function($) {

	var _win         = $(window);
	var _body        = $('body');
	var _windowStack = [];
	var _isOldIE     = $.browser.msie && $.browser.version.match(/^(6|7)/);


	/**
	 * When the escape key is pressed, close the topmost window.
	 */
	_win.bind('keyup', function(e) {
		if (_windowStack.length && e.keyCode === 27) {
			var win = $(_windowStack[_windowStack.length - 1]).window();

			if (win.config('closeOnEscape')) {
				win.close();
			}
		}
	});
	
	/**
	 * When the main window is resized, center any open windows and resize them if the browser window is smaller.
	 * If the browser window's viewable area grows larger than the resized window, it will be restored to either
	 * the viewport size or the original dimensions - whichever comes first.
	 * 
	 * @todo Disable auto-centering unless set in the config. This would require a change to the window.resizeWindow
	 *       event so that if the bottom of the window, where it stands, is outside the viewable area, it is then
	 *       resized without centering.
	 */
	_win.bind('resize.windowResize', function() {
		var all = $.fn.window.getAll()
		
		if (!all) {
			return;
		}
		
		all.jQuery().each(function() {
			var obj    = $(this);
			var win    = obj.window();
			var config = win.config();
			
			if (!config.autoResize) {
				return;
			}
			
			var maxWidth  = _win.width();
			var maxHeight = _win.height();
			var oldWidth  = obj.data('__window.width');
			var oldHeight = obj.data('__window.height');
			
			win.center();
			
			if (!oldWidth) {
				oldWidth = win.width();
				
				obj.data('__window.width', oldWidth);
			}
			
			if (!oldHeight) {
				oldHeight = win.height();
				
				obj.data('__window.height', oldHeight);
			}
			
			// generate width
			if (maxWidth > oldWidth) {
				win.width(oldWidth);
			} else {
				win.width(maxWidth + config.autoResizeOffsetWidth);
			}
			
			// generate height
			if (maxHeight > oldHeight) {
				win.height(oldHeight);
			} else {
				win.height(maxHeight + config.autoResizeOffsetHeight);
			}
		});
	});
	
	/**
	 * Auto-centering. The all windows, if specified in the config will be auto-centered when the
	 * browser window is resized.
	 */
	_win.bind('resize.windowCenter', function() {
		var all = $.fn.window.getAll();
		
		if (!all) {
			return;
		}
		
		all.jQuery().each(function() {
			var win = $(this).window();
			
			if (win.config('autoCenter')) {
				win.center();
			}
		});
	});



	/**
	 * Constructor for the window plugin.
	 */
	var _windowPlugin = function() {
		return this;
	}



	/**
	 * This is the starting zIndex for all windows.
	 */
	_windowPlugin.zIndexStart = 10000;

	/**
	 * The default template for a window used for creating a new window
	 * from scratch using jQuery.fn.window.create.
	 */
	_windowPlugin.defaultTemplate = '<div></div>';

	/**
	 * The default options that are applied to each window.
	 */
	_windowPlugin.defaultConfig = {
			classWindow            : 'ui-window',
			classModal             : 'ui-window-modal',
			selectorTitle          : false,
			selectorContent        : '.ui-window',
			selectorClose          : false,
			position               : 'fixed',
			modal                  : {
				opacity : 0.5 
			},
			title                  : false,
			content                : false,
			width                  : false,
			height                 : false,
			uri                    : false,
			uriData                : {},
			autoOpen               : false,
			autoResize             : true,
			autoCenter             : true,
			autoResizeOffsetWidth  : 0,
			autoResizeOffsetHeight : 0,
			closeOnEscape          : true,
			destroyAfterClose      : true,
			animateOpen            : function() {
				$(this).show();
			},
			animateClose           : function() {
				$(this).hide();
			},
			animateCenter          : function(top, left) {
				$(this).css({
					top  : top  > 0 ? top  : 0, 
					left : left > 0 ? left : 0
				});
			},
			animateWidth           : function(width) {
				$(this).width(width);
			},
			animateHeight          : function(height) {
				$(this).height(height);
			},
			animateModalOpen       : function() {
				$(this).show();
			},
			animateModalClose      : function() {
				$(this).hide();
			}
		};

	/**
	 * Creates a new window using the specified template or the default template
	 * and returns it in the window object namespace.
	 *
	 * @return jQuery.fn.window
	 */
	_windowPlugin.create = function(config, template) {
		// the template is either a passed in one, or the default one
		var template = $(template || $.fn.window.defaultTemplate);

		// append it to the body, it will be hidden upon initialization later
		$('body').append(template);
		
		// return the window plugin instance
		return template.window().init(config);
	}
	
	/**
	 * Returns the topmost window.
	 * 
	 * @return jQuery.fn.window
	 */
	_windowPlugin.getTop = function() {
		if (!_windowStack.length)
			return false;
		
		return $(_windowStack[_windowStack.length - 1]).window();
	}
	
	/**
	 * Returns all windows that are currently open.
	 * 
	 * @return jQuery.fn.window
	 */
	_windowPlugin.getAll = function() {
		if (!_windowStack.length)
			return false;
		
		var jq;
		
		// for each window in the stack, add it to the jQuery collection
		$.each(_windowStack, function(i, el) {
			if (typeof jq === 'undefined') {
				jq = $(el);
			} else {
				jq.add(el);
			}
		});
		
		// return the collection as a window instance
		return jq.window();
	}

	/**
	 * Closes all open windows and returns the static window object.
	 *
	 * @return jQuery.fn.window
	 */
	_windowPlugin.closeAll = function() {
		for (var i = 0; i < _windowStack.length; i++) {
			$(_windowStack[i]).window().close();
		}

		return _windowPlugin;
	}

	_windowPlugin.prototype = {

		/**
		 * Initializes the window.
		 *
		 * @param object options The options to initialize the window with.
		 *
		 * @return object jQuery.fn.window
		 */
		init : function(opts) {
			this.jQuery().each(function() {
				var obj    = $(this);
				var self   = obj.window();
				var config = {};

				$.extend(config, _windowPlugin.defaultConfig, opts);

				// set defaults
				self.config(config).jQuery().addClass(config.classWindow).css({
					display  : 'none',
					position : config.position
				});

				// on window resize, resize the modal to the window size
				_win.bind('resize', function() {
					// if a modal is set to be used, bind it to the window
					var modal = self.getModal();

					if (modal) {
						modal.css({
							width  : _win.width(),
							height : _win.height()
						});
					}
				});

				// bind the close event handler
				obj.find(config.selectorClose).bind('click', function() {
					self.close();
				});

				// when the window is clicked, bring it to the front
				obj.bind('click', function() {
					self.bringToFront();
				});

				// if there is a title, set it
				if (config.title) {
					self.title(config.title);
				}

				// if there is content, set it
				if (config.content) {
					self.content(config.content);
				}
				
				// if there is a default width, set it
				if (config.width) {
					self.width(config.width);
				}
				
				// if a default height is set, set it
				if (config.height) {
					self.height(config.height);
				}

				// if there is a url, load it
				if (config.uri) {
					self.load(config.uri, config.uriData, function() {
						// open if we are auto-opening
						if (config.autoOpen) {
							// center then open
							self.center().open();
						}
					});
				} else {
					// open if we are auto-opening
					if (config.autoOpen) {
						// center then open
						self.center().open();
					} else {
						// otherwise just center
						self.center();
					}
				}
			});

			return this;
		},

		/**
		 * Opens the windows applied to the passed objects.
		 *
		 * @return object jQuery.fn.window
		 */
		open : function() {
			var self = this;

			this.jQuery().each(function() {
				var obj     = $(this);
				var self    = obj.window();
				var options = self.config();

				// if no options are defined yet, return
				if (typeof options === 'undefined')
					return;

				// trigger the before open event
				obj.trigger('windowBeforeOpen');

				// add it to the window stack if it's not already open
				if (!self.isOpen()) {
					_windowStack[_windowStack.length] = obj.get(0);
				}

				// if we are to have a modal layer, open it (create it first if it doesn't exist)
				if (options.modal) {
					// get the modal
					var modal = self.getModal();

					// if it doesn't exist
					if (!modal) {
						// create it
						modal = $('<div class="' + options.classModal + '" />').css(options.modal).css({
							position : 'fixed',
							left     : 0,
							top      : 0,
							zIndex   : _getModalZIndex(),
							width    : _win.width(),
							height   : _win.height()
						}).hide().appendTo('body');

						// bind it to the window
						obj.data('window.modal', modal);
					}

					// open the modal
					options.animateModalOpen.apply(self.getModal());
				}

				// bring it to the front
				self.bringToFront();

				// now open the window
				options.animateOpen.apply(self);

				// trigger the before open event
				obj.trigger('windowAfterOpen');
			});

			return this;
		},

		/**
		 * Closes the windows applied to the passed objects.
		 *
		 * @return object jQuery.fn.window
		 */
		close : function() {
			var self = this;

			this.jQuery().each(function() {
				var obj     = $(this);
				var self    = obj.window();
				var modal   = obj.data('window.modal');
				var options = self.config();

				// trigger the before open event
				obj.trigger('windowBeforeClose');

				// remove it from the window stack
				for (var i = 0; i < _windowStack.length; i++) {
					if (_windowStack[i] === obj.get(0)) {
						_windowStack.splice(i, 1);

						break;
					}
				}

				// if there is a modal close it
				if (modal) {
					options.animateModalClose.apply(modal);
				}

				// now close the window itself
				options.animateClose.apply(self);

				// trigger the before open event
				obj.trigger('windowAfterClose');

				// remove it after closing if desired
				if (options.destroyAfterClose) {
					self.destroy();
				}
			});

			return this;
		},

		/**
		 * Centers the windows applied to the passed objects.
		 *
		 * @param object opts Animation options
		 *
		 * @return object jQuery.fn.window
		 */
		center : function() {
			this.jQuery().each(function() {
				var win     = $(this);
				var self    = win.window();
				var reHide  = false;
				var opacity = 0;
				var options = self.config();

				// temporarily make it visible, but just barely, to get the proper dimensions
				if (!self.isOpen()) {
					opacity = win.css('opacity');
					reHide  = true;

					win.css('opacity', .1).show();
				}

				var top  = _win.height() / 2 - win.outerHeight() / 2;
				var left = _win.width() / 2 - win.outerWidth() / 2;

				// rehide it if previously hidden and reset the opacity
				if (reHide) {
					win.css('opacity', opacity).hide();

					// just use css to set the offset if it isn't open
					win.css({ top : top + 'px', left : left + 'px' });
				} else {
					// otherwise apply the custom callback function
					options.animateCenter.apply(win, [top, left]);
				}
			});

			return this;
		},

		/**
		 * Sets the width of the passed window's content area.
		 *
		 * @param mixed  width String percentage, or integer width
		 *
		 * @return object jQuery.fn.window
		 */
		width : function(width) {
			if (width) {
				$(this).each(function() {
					var win     = $(this);
					var self    = win.window();
					var options = self.config();
					var newW    = width;
					var content = self.getContent();

					// if a percentage is passed, calculate the percentage of
					// the bounding window
					if (width.toString().match('%')) {
						newW = parseInt(width.replace('%', ''), 10);
						newW = (newW / 100) * _win.width();
					}
					
					// save the width so we can set it back later
					var oldContentWidth = content.width();
					
					// floating left will collapse the main window so it can expand to the content (ie7 compatible)
					win.css('float', 'left');
					
					// set the content width, so the window auto-expands for it
					content.css('width', newW + 'px');
					
					// then we grab the offset of the new bounding window's width in comparison to the content
					var windowWidthOffset = win.outerWidth() - newW;
					var contentWidth      = newW - windowWidthOffset;
					
					// now set the float to none and auto expand it
					win.css('float', 'none').css('width', 'auto');
					
					// then we reset the content width to its old width
					content.css('width', oldContentWidth + 'px');
					
					if (self.isOpen()) {
						if (_isOldIE) {
							options.animateWidth.apply(win, [newW]);
						}
						
						// animate the content portion of the window
						options.animateWidth.apply(content, [contentWidth]);
					} else {
						if (_isOldIE) {
							win.css('width', newW + 'px');
						}
						
						// css will work even if the element is hidden
						content.css('width', contentWidth + 'px');
					}
				});
				
				return this;
			}

			return this.jQuery().outerWidth();
		},

		/**
		 * Sets the height of the passed window's content area.
		 *
		 * @param mixed  height String percentage, or integer height
		 *
		 * @return object jQuery.fn.window
		 */
		height : function(height) {
			if (height) {
				$(this).each(function() {
					var win     = $(this);
					var self    = win.window();
					var options = self.config();
					var newH    = height;
					var content = self.getContent();

					// if a percentage is passed, calculate the percentage of
					// the bounding window
					if (height.toString().match('%')) {
						newH = parseInt(height.replace('%', ''), 10);
						newH = (newH / 100) * _win.height();
					}
					
					// save the height so we can set it back later
					var oldContentHeight = content.height();
					
					// set the content height, so the window auto-expands for it
					win.css('height', 'auto');
					content.css('height', newH + 'px');
					
					// then we grab the offset of the new bounding window's height in comparison to the content
					var windowHeightOffset = win.outerHeight() - newH;
					var contentHeight      = newH - windowHeightOffset;
					
					if (self.isOpen()) {
						if (_isOldIE) {
							options.animateHeight.apply(win, [newH]);
						}
						
						// animate the content portion of the window
						options.animateHeight.apply(content, [contentHeight]);
					} else {
						if (_isOldIE) {
							win.css('height', newH + 'px');
						}
						
						// css will work even if the element is hidden
						content.css('height', contentHeight + 'px');
					}
				});
				
				return this;
			}

			return this.jQuery().outerHeight();
		},

		/**
		 * Sets the dimensions of the passed window's content area.
		 *
		 * @param mixed width
		 * @param mixed height
		 *
		 * @return object jQuery.fn.window
		 */
		dimensions : function(width, height) {
			// if no height is passed, assume the width dimension
			if (typeof height === 'undefined') {
				var height = width;
			}
			
			this.jQuery().each(function() {
				var self = $(this).window();

				self.width(width);
				self.height(height);
			});

			return this;
		},

		/**
		 * Loads data and inserts it into the content area.
		 *
		 * @param string   uri      The URI to load into the content area.
		 * @param object   data     Data to be passed to the URI.
		 * @param function callback The callback to fire when complete.
		 *
		 * @return jQuery.fn.window
		 */
		load : function(uri, data, callback) {
			// get the content element and load the data into it
			this.getContent().load(uri, data, callback);

			return this;
		},

		/**
		 * Loads the passed uri via ajax, passing any data, and applies the returned data to the window content.
		 *
		 * @param  string        uri  The URI to load.
		 * @param  string|object data The query string, or object to be passed as ajax data.
		 *
		 * @return Object jQuery.fn.window
		 */
		loadAndOpen : function(uri, data, callback) {
			var self = this;

			self.load(uri, data, function() {
				self.open();
			});

			return self;
		},

		/**
		 * Sets the title of the window via the options.selectorTitle.
		 *
		 * @param string title The title of the window.
		 *
		 * @return object|string jQuery.fn.window object or string if no title is passed
		 */
		title : function(title) {
			if (typeof title === 'undefined') {
				var options = $(this[0]).window().config();

				return $(this).find(options.selectorTitle).html()
			}

			$(this).each(function() {
				var options = $(this).window().config();

				$(this).find(options.selectorTitle).html(title);
			});

			return this;
		},

		/**
		 * Sets the content of the passed windows.
		 *
		 * @param  string content The content to be set in the window content area.
		 *
		 * @return object jQuery.fn.window
		 */
		content : function(content) {
			if (typeof content === 'undefined') {
				var options = $(this[0]).window().config();

				return $(this).find(options.selectorContent).html()
			}

			$(this).each(function() {
				var options = $(this).window().config();

				$(this).find(options.selectorContent).html(content);
			});

			return this;
		},

		/**
		 * Returns the modal dialog of the passed window if it exists.
		 *
		 * @return Object jQuery
		 */
		getModal : function() {
			var modal = $(this).data('window.modal');

			if (modal) {
				return $(modal);
			}

			return false;
		},

		/**
		 * Retrieves the title element of the window.
		 *
		 * @return object jQuery
		 */
		getTitle : function() {
			return _getElementFromWindow(this, $(this).window().config().selectorTitle);
		},

		/**
		 * Retrieves the content element of the window.
		 *
		 * @return object jQuery
		 */
		getContent : function() {
			return _getElementFromWindow(this, $(this).window().config().selectorContent);
		},

		/**
		 * Returns whether the first matched element in the set is open or not.
		 *
		 * @return bool
		 */
		isOpen : function() {
			var obj = $(this).get(0);

			for (var i = 0; i < _windowStack.length; i++) {
				if (obj === _windowStack[i]) {
					return true;
				}
			}

			return false;
		},

		/**
		 * Brings the first matched element to the front of the window stack.
		 *
		 * @return object jQuery.fn.window
		 */
		bringToFront : function() {
			var obj = $(this).get(0);

			for (var i = 0; i < _windowStack.length; i++) {
				// if it is the current element and it's not already the front one
				if (obj === _windowStack[i] && i !== (_windowStack.length - 1)) {
					// remove it
					_windowStack.splice(i, 1);

					// then append it
					_windowStack[_windowStack.length] = obj;

					break;
				}
			}

			_organizeZIndicies();
		},

		/**
		 * Destroys the current window. Destroying consist of removing the modal as
		 * well as the window itself. If it is open, it is removed from the window
		 * stack. The window is NOT closed before it is destroyed, thus no closing
		 * events are fired.
		 */
		destroy : function() {
			this.jQuery().each(function() {
				var win   = $(this);
				var modal = $(this).window().getModal();

				// before the window is destroyed, fire the event only one event is
				// triggered here because after it is destroyed, an event cannot be
				// triggered on it, thus no Before or After is placed in the event
				win.trigger('windowDestroy');

				// if the window is still open, we need to remove it from the stack
				for (var i = 0; i < _windowStack.length; i++) {
					if (win.get(0) === _windowStack[i]) {
						_windowStack.splice(i, 1);

						break;
					}
				}

				// remove the modal
				if (modal) {
					modal.remove();
				}

				// remove the window
				win.remove();
			});
		}
	};



	function _organizeZIndicies() {
		this.jQuery().each(function() {
			var obj  = $(this);
			var self = obj.window();

			// organize the z-indicies for each element and its modal in the stack
			for (var i = 0; i < _windowStack.length; i++) {
				var curZIndex  = ((i + 1) * 2) + parseInt($.fn.window.zIndexStart, 10);
				var curElement = $(_windowStack[i]);
				var curModal   = curElement.window().getModal();

				curElement.css('z-index', curZIndex);

				if (curModal) {
					curModal.css('z-index', curZIndex - 1);
				}
			}
		});

		return this;
	}

	function _getElementFromWindow(winObj, selector) {
		var winObj = $(winObj);

		return winObj.is(selector) ? winObj : winObj.find(selector);
	}

	function _getWindowZIndex() {
		return $.fn.window.zIndexStart + (_windowStack.length * 2);
	}

	function _getModalZIndex() {
		return _getWindowZIndex() - 1;
	}



	$.fn.plugin.add('window', _windowPlugin);

})(jQuery);