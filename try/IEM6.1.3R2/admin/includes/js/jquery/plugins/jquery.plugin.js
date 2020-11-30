/**
 * plugin
 * 
 * A jQuery plugin for basic and advanced plugin authoring.
 * 
 * Basically, Plugin is a set of jQuery functions to assist in plugin authoring while providing
 * a conventional way to do so.
 * 
 * It allows basic plugin authoring by passing a function as the second argument.
 * If an object of functions is passed, then they are added to the plugin as methods
 * and 'this' within those methods is used in the same manner as when in the jQuery
 * namespace, thus, interchangeable.
 * 
 * Enables advanced plugin authoring by allowing namespacing while retaining
 * the ability to use 'this' to refer to the selected object collection.
 * 
 * If an object is passed, the values will be added to the constructors prototype.
 * 
 * @author  Trey Shugart
 * @date    2009-08-02
 * @version 1.0.1
 * 
 * @license
 *     Copyright (c) 2009, Trey Shugart
 *     All rights reserved.
 *     
 *     Redistribution and use in source and binary forms, with or without modification, are permitted 
 *     provided that the following conditions are met:
 *     
 *     Redistributions of source code must retain the above copyright notice, this list of conditions 
 *     and the following disclaimer.
 *     
 *     Redistributions in binary form must reproduce the above copyright notice, this list of conditions 
 *     and the following disclaimer in the documentation and/or other materials provided with the 
 *     distribution.
 *     
 *     Neither the name of the the organization nor the names of its contributors may be used to endorse 
 *     or promote products derived from this software without specific prior written permission.
 *     
 *     THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR 
 *     IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY 
 *     AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER 
 *     OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 *     CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 *     SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY 
 *     THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR 
 *     OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
 *     POSSIBILITY OF SUCH DAMAGE.
 */
;(function($) {
	
	var 
		undefined,
		_plugins = {
			static   : [],
			instance : []
		},
		_options = {};
	
	
	
	// add static methods; see _init for documentation
	$.extend($.plugin = {}, _init(false));
	
	// add instance methods; see _init for documentation
	$.fn.extend($.fn.plugin = {}, _init(true));
	
	/**
	 * @name jQuery.config
	 * @description
	 * A way of setting options, statically, for a given plugin. Works in the same manner as jQuery.fn.config,
	 * but assigns it globally for the plugin rather than on specific objects. This is a good way to assign
	 * default options to a plugin. If no options are passed, then the options for that given plugin are returned.
	 * 
	 * @chainable no
	 * @return    Mixed
	 * @param     String plugin
	 * @param     Object options
	 */
	$.config = function(plugin, opts) {
		if (typeof opts === 'undefined')
			return _options[plugin] || undefined;
		
		_options[plugin] = $.extend(_options[plugin], opts);
		
		return $;
	};
	
	/**
	 * @name jQuery.fn.config
	 * @description
	 * Sets options for each passed object if an options object is passed. If only a pluginName is passed,
	 * then the options that are currently set for that plugin are returned.
	 * 
	 * @chainable yes
	 * @return    Object jQuery
	 * @param     String plugin  - The plugin in which to apply the options for
	 * @param     Object options - The options to extend the currently applied options with (if any)
	 * 
	 * @example
	 * // setting options
	 * jQuery('div').config('myPlugin', {
	 * 	param1 : 1,
	 * 	param2 : 'some value'
	 * });
	 * 
	 * @example
	 * // retrieving options
	 * var options = jQuery('div').config('myPlugin');
	 * 
	 * alert(options.param2); // 'some value'
	 * 
	 * @example
	 * // extending default options
	 * jQuery('div').config('myPlugin', jQuery.extend({
	 * 	default1 : 'default value 1',
	 * 	default2 : 'defallt value 2'
	 * }, {
	 * 	default1 : 'new def 1',
	 * 	default2 : 'new def 2'
	 * }));
	 * 
	 * alert(jQuery('div').config('myPlugin').default1); // 'new def 1'
	 */
	$.fn.config = function(plugin, opts) {
		var $this = $(this);
		
		if (typeof opts === 'undefined')
			return $this.data(plugin + '.config');
		
		return $this.each(function() {
			var 
				$this   = $(this),
				curOpts = $this.data(plugin + '.config') || {};
			
			$this.data(plugin + '.config', $.extend(curOpts, opts));
		});
	};
	
	
	
	/**
	 * Returns an object to extend jQuery or jQuery.fn with.
	 * 
	 * @return Object
	 * @param  Boolean fn - Whether or not to add it to jQuery's prototype
	 */
	function _init(fn) {
		return {
			
			/**
			 * @name jQuery.plugin.add
			 * @description
			 * Adds a static plugin. When the plugin is called, though, it returns an instance of itself 
			 * so you can add instance methods:
			 * // adding
			 * jQuery.myPlugin.fn.method = function() { return this; };
			 * // calling
			 * jQuery.myPlugin().method()
			 * 
			 * For static methods:
			 * jQuery.myPlugin.method = function() { return this; };
			 * jQuery.myPlugin.method();
			 * 
			 * @chainable yes
			 * @return    jQuery
			 * @param     String          name - The name of the plugin
			 * @param     Function|Object func - A function or hash of methods available to the plugin
			 * 
			 * @example
			 * $.addPlugin('date', {
			 * 	__construct : function() {
			 * 		this.date = new Date();
			 * 		
			 * 		return this;
			 * 	},
			 * 	get : function() {
			 * 		return this.date;
			 * 	},
			 * 	set : function(d) {
			 * 		this.date = new Date(d);
			 * 		
			 * 		return this;
			 * 	}
			 * });
			 * 
			 * alert($.date().set('March 22, 2009 2:03 PM').get());
			 */
			
			/**
			 * @name jQuery.fn.plugin.add
			 * @description
			 * Adds a plugin to jQuery's prototype. You can use 'this' when inside the plugin to refer to
			 * the selected objects. You can also call a plugin method with 'this', but not jQuery methods
			 * unless wrapped with jQuery(this).
			 * 
			 * @chainable yes
			 * @return    jQuery.fn
			 * @param     name - The name of the plugin to add
			 * @param     func - The function, or object of functions to add
			 * 
			 * @example
			 * // the following are identical as plugins don't get applied to specific DOM objects
			 * jQuery.fn.addPlugin('test');
			 * jQuery().addPlugin('test');
			 * jQuery('div').addPlugin('test');
			 * 
			 * @example
			 * <script type="text/javascript">
			 * jQuery(function($) {
			 * 
			 * $.fn.addPlugin('test1', function() {
			 * 	alert($(this).text());
			 * 	
			 * 	return $(this);
			 * });
			 * 
			 * $.fn.addPlugin('test2', {
			 * 	__construct : function(alertText) { alert(alertText); },
			 * 	alertAttr   : function(attr) { alert($(this).attr(attr)); return this; },
			 * 	alertText   : function() { alert($(this).text()); return this; }
			 * });
			 * 
			 * $('#test1').bind('click', function() {
			 * 	var btn = $(this);
			 * 	
			 * 	btn.test1().text('clicked');
			 * 	
			 * 	setTimeout(function() {
			 * 		btn.text('test1');
			 * 	}, 1000);
			 * });
			 * 
			 * $('#test2').bind('click', function() {
			 * 	var btn = $(this);
			 * 	
			 * 	btn.test2('constructing...').alertAttr('id').alertText().jQuery.text('clicked!');
			 * 	
			 * 	setTimeout(function() {
			 * 		btn.text('test2');
			 * 	}, 1000);
			 * });
			 * 
			 * });
			 * </script>
			 * 
			 * <button id="test1" type="button">test1</button>
			 * <button id="test2" type="button">test2</button>
			 */
			add : function(name, func) {
				return _add(fn, name, func);
			},
			
			
			
			/**
			 * @name jQuery.plugin.remove
			 * @description 
			 * Removes a plugin.
			 * 
			 * @chainable yes - on jQuery, not jQuery.fn
			 * @return    jQuery
			 * @param     String name - The name of the plugin to remove.
			 * 
			 * @example
			 * jQuery.removePlugin('myPlugin');
			 * 
			 * @example
			 * // in combination with jQuery.isPlugin
			 * jQuery.addPlugin('myPlugin', function() {});
			 * jQuery.isPlugin('myPlugin'); // true
			 * jQuery.removePlugin('myPlugin');
			 * jQuery.isPlugin('myPlugin'); // false
			 */
			
			/**
			 * @name jQuery.fn.plugin.remove
			 * @description 
			 * Removes a plugin.
			 * 
			 * @chainable yes - on jQuery, not jQuery.fn
			 * @return    jQuery
			 * @param     String name - The name of the plugin to remove.
			 * 
			 * @example
			 * jQuery.fn.removePlugin('myPlugin');
			 * 
			 * @example
			 * // in combination with jQuery.isPlugin
			 * jQuery.fn.addPlugin('myPlugin', function() {});
			 * jQuery.fn.isPlugin('myPlugin'); // true
			 * jQuery.fn.removePlugin('myPlugin');
			 * jQuery.fn.isPlugin('myPlugin'); // false
			 */
			remove : function(name) {
				return _remove(fn, name);
			},
			
			
			
			/**
			 * @name jQuery.plugin.has
			 * @description
			 * Checks to see if a plugin exists. Will return false for functions in
			 * jQuery's core.
			 * 
			 * @chainable no
			 * @return    Boolean
			 * @param     String  name - The name of the plugin
			 * 
			 * @example
			 * jQuery.addPlugin('myPlugin', function() {}); // do something
			 * jQuery.isPlugin('myPlugin'); // true
			 * jQuery.isPlugin('myOtherPlugin'); // false
			 * jQuery.isPlugin('support'); // false, already in the core
			 */
			
			/**
			 * @name jQuery.fn.plugin.has
			 * @description
			 * Checks to see if a plugin exists. Will return false for functions in
			 * jQuery's core.
			 * 
			 * @chainable no
			 * @return    Boolean
			 * @param     String  name - The name of the plugin
			 * 
			 * @example
			 * jQuery.fn.addPlugin('myPlugin', function() {}); // do something
			 * jQuery.fn.isPlugin('myPlugin'); // true
			 * jQuery.fn.isPlugin('myOtherPlugin'); // false
			 * jQuery.fn.isPlugin('click'); // false, already in the core
			 */
			has : function(name) {
				return _has(fn, name);
			},
			
			
			
			/**
			 * @name jQuery.plugin.list
			 * @description
			 * Returns an array of static plugin names
			 * 
			 * @return Array
			 */
			
			/**
			 * @name jQuery.fn.plugin.list
			 * @description
			 * Returns an array of instance plugin names
			 * 
			 * @return Array
			 */
			list : function() {
				return _list(fn);
			}
		};
	};
	
	/*
	 * Adds plugins to jQuery.fn or jQuery
	 * 
	 * @return jQuery or jQuery.fn (.prototype)
	 * @param  Boolean         fn   - Whether to add to jQuery.fn or jQuery
	 * @param  String          name - The name of the plugin
	 * @param  Function|Object func - The function or hash of functions to add
	 */
	function _add(fn, name, func) {
		var config           = {};
		var applyTo          = _applyTo(fn);
		var staticOrInstance = _staticOrInstance(fn);
		var func             = $.isFunction(func) ? func : function() {};
		
		// the constructor for the plugin
		function _constructor(jq, args) {
			if (fn) {
				// 'this' will not mimic jQuery unless given the length property
				this.length   = 0;
				this.selector = jq.selector;
				this.context  = jq.context;
				
				// mimic 'this' in jQuery, but for the plugin namespace
				Array.prototype.push.apply(this, $.makeArray(jq));
			}
			
			// allow the constructor to be called every time the plugin is instantiated
			func.apply(this, args);
			
			// return plugin namespace (and dom objects)
			return this;
		};
		
		// when called, construct the plugin
		applyTo[name] = function() {
			return new _constructor(this, arguments);
		};
		
		// add methods already bound to the passed constructor, if any, including the prototype
		// this is necessary to copy all static methods, but won't copy instance methods in IE
		for (var i in func) {
			applyTo[name][i] = _constructor[i] = func[i];
		}
		
		// allow extending of new plugin via .fn or .prototype
		// adding _constructor.prototype = func.prototype to the end of this line is necessary
		// to get instance methods to work properly in IE
		applyTo[name].fn = applyTo[name].prototype = _constructor.prototype = func.prototype;
		
		// add a $/jQuery function to chain the jQuery namespace back in
		applyTo[name].fn.$ = applyTo[name].fn.jQuery = function() {
			return $(this);
		};
		
		// static configuration method
		applyTo[name].config = function(k, v) {
			if (typeof k === 'undefined') {
				return config;
			}
			
			if (typeof k === 'object') {
				for (var i in k) {
					applyTo[name].config(i, k[i]);
				}
			}
			
			if (typeof k === 'string') {
				if (typeof v === 'undefined') {
					return config[k];
				}
				
				config[k] = v;
			}
			
			return applyTo[name];
		}
		
		// instance configuration method
		applyTo[name].fn.config = function(k, v) {
			var obj     = $(this);
			var dataKey = name + '.config';
			var data    = obj.data(dataKey);
			
			// return the existing config
			if (typeof k === 'undefined') {
				return data;
			}
			
			// accept an object
			if (typeof k === 'object') {
				// foreach passed config variable
				for (var i in k) {
					// set it
					this.config(i, k[i]);
				}
			}
			
			// accept a string
			if (typeof k === 'string') {
				// if v is undefined, then return the config variable
				if (typeof v === 'undefined') {
					return data[k];
				}
				
				// if the config hasn't been set yet, initialize it
				if (typeof data === 'undefined') {
					data = {};
				}
				
				// set config value
				data[k] = v;
				
				// set the config data
				obj.data(dataKey, data);
			}
			
			// return the instance
			return this;
		}
		
		// add a plugin to the plugin registry
		_plugins[staticOrInstance][_plugins[staticOrInstance].length] = name;
		
		delete func;
		
		// return jQuery or jQuery.fn
		return applyTo;
	};
	
	/*
	 * Removes static or instance plugins
	 * 
	 * @return jQuery or jQuery.fn (.prototype)
	 * @param  Boolean fn   - Whether to remove from jQuery.fn or jQuery
	 * @param  String  name - The name of the plugin to remove
	 */
	function _remove(fn, name) {
		var 
			applyTo          = _applyTo(fn),
			staticOrInstance = _staticOrInstance(fn),
			index            = $.inArray(name, _plugins[staticOrInstance]);
		
		if (index > -1) {
			_plugins[staticOrInstance].splice(index, 1);
			
			applyTo[name] = undefined;
		}
		
		return applyTo;
	};
	
	/*
	 * Checks for static/instance plugins.
	 * 
	 * @return Boolean
	 * @param  Boolean fn   - Whether to check for instance/static plugin
	 * @param  String  name - The name of the plugin to check for
	 */
	function _has(fn, name) {
		return $.inArray(name, _plugins[_staticOrInstance(fn)]) > -1;
	};
	
	/*
	 * Returns the plugins for jQuery or jQuery.fn.
	 * 
	 * @return Array
	 * @param  Boolean fn - Whether or not to return instance or static plugins
	 */
	function _list(fn) {
		return _plugins[_staticOrInstance(fn)];
	};
	
	/*
	 * Returns either jQuery or jQuery.fn
	 * 
	 * @return Object  jQuery || jQuery.fn
	 * @param  Boolean fn - Whether or not to return jQuery.fn or jQuery
	 */
	function _applyTo(fn) {
		return fn ? $.fn : $;
	};
	
	/*
	 * Returns either 'static' or 'isntance' - the respective keys to the '_plugin' hash.
	 * 
	 * @return String
	 * @param  Boolean fn - Whether or not to return 'instance' or 'static'
	 */
	function _staticOrInstance(fn) {
		return fn ? 'instance' : 'static';
	};
	
})(jQuery);