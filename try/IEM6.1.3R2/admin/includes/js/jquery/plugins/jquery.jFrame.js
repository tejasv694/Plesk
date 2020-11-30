/**
 * @name    jFrame
 * @version 1.2.0
 * @description
 * A simple, lightweight framework resting atop jQuery to assist in lazy-loading,
 * enforcing convention, organization, namespacing, script caching and automatic 
 * execution when the DOM is ready.
 * 
 * @date      2009-07-05
 * @author    Trey Shugart
 * @copyright Copyright (c) 2009 Trey Shugart (shugartweb.com/jquery/)
 * @license
 * MIT - (http://www.opensource.org/licenses/mit-license.php) 
 * GPL - (http://www.gnu.org/licenses/gpl.txt)
 */
;(function($) {
	
	var 
		// hold instances
		_instances     = {},
		_dispatchStack = [],
		// holds configuration for instances
		_config        = {},
		// default configuration for each instance
		_defaultConfig = {
			controllerPath : '/js/jFrame/',
			cache          : false,
			onReady        : true
		},
		// references to loaded files
		_loaded        = [],
		// cache of loaded files
		_cache         = {},
		_registry      = {};
	
	
	
	/**
	 * @name        jFrame
	 * @description The global jFrame constructor
	 * @return      Object jFrame
	 */
	window.jFrame = function(config, instanceName) {
		// if accessed statically, return false
		if (!this instanceof jFrame) {
			return false;
		}
		
		// so we can reference 'this' out of context
		var self = this;
		
		// if no instance name was set, set it to 'default'
		if (typeof instanceName !== 'string')
			instanceName = 'default';
		
		// reference the instance
		_instances[instanceName] = self;
		
		// holds the parameters set for a dispatch call
		_params = {};
		
		
		
		/*
		 * Config methods are assigned here to use the instanceName so we
		 * don't have to tell the config methods which instance we want
		 * grab the config for. It could be set as a property, but we only
		 * want private access to it.
		 */
		
		/**
		 * @name        setConfig
		 * @description Sets a configuration variable and doing any necessary
		 *              normalization of config variables.
		 * @return      Object jFrame
		 */
		this.setConfig = function(key, val) {
			// if the first argument isn't an object, make it one
			// we do this so we can pass a hash as well as a string 
			// key and value
			if (!typeof key === 'object')
				key = {
					key : val
				};
			
			// normalize the script path
			if (typeof key['controllerPath'] !== 'undefined')
				key['controllerPath'] = _normalizePath(key['controllerPath']);
			
			// set the config
			_config[instanceName] = $.extend(_config[instanceName], key);
			
			return self;
		};
		
		/**
		 * @name        getConfig
		 * @description Gets a configuration variable.
		 * @return      Mixed
		 */
		this.getConfig = function(key) {
			return _config[instanceName][key];
		};
		
		/**
		 * 
		 */
		this.setParam = function(key, val) {
			_params[key] = val;
			
			return this;
		}
		
		/**
		 * 
		 */
		this.getParam = function(key) {
			return _params[key];
		}
		
		/**
		 * 
		 */
		this.getInstanceName = function() {
			return instanceName;
		};
		
		/**
		 * @name        dispatch
		 * @description Dispatches a call for path relative to the root set in the config excluding the .js extension.
		 * @return      Object jFrame
		 */
		this.dispatch = function(routes, params, onError) {
			var 
				cache  = self.getConfig('cache'),
				routes = $.isArray(routes) ? routes : [routes];
			
			// iterate over each route and dispatch in the order given
			$.each(routes, function(index, route) {
				var file = self.getConfig('controllerPath') + '/' + (route || 'index') + '.js';
				
				// automate the jQuery DOM ready event
				if (self.getConfig('onReady')) {
					jQuery(function($) {
						_execute();
					});
				// otherwise just execute it
				} else {
					_execute();
				}
				
				
				
				// executes the loaded file
				function _execute() {
					// add the parameters for this dispatch call if they exists
					if (typeof params === 'object') {
						for (var i in params) {
							self.setParam(i, params[i]);
						}
					}
					
					// add this to the dispatch stack
					_dispatchStack[_dispatchStack.length] = self;
					
					// if the load was successful
					if (!jFrame.load(file, cache) && typeof onError === 'function') {
						onError.apply(self);
					}
					
					// remove it from the dispatch stack
					_dispatchStack.splice(_dispatchStack.length - 1, 1);
					
					// clear the current dispatch params
					_params = {};
				}
			});
			
			return self;
		};
		
		
		
		// extend the default config with the passed config and assign it to
		// this instance
		self.setConfig($.extend(_defaultConfig, config));
		
		return self;
	};
	
	
	
	/**
	 * 
	 */
	jFrame.getActiveInstance = function() {
		return _dispatchStack[_dispatchStack.length - 1];
	};
	
	/**
	 * 
	 */
	jFrame.getDispatchStack = function() {
		return _dispatchStack;
	};
	
	/**
	 * @name        getInstance
	 * @description Returns the specified instance of jFrame.
	 * @return      Object jFrame
	 */
	jFrame.getInstance = function(instanceName) {
		// default instance name
		if (typeof instanceName !== 'string')
			instanceName = 'default';
		
		// return the stored instance
		return _instances[instanceName];
	};
	
	/**
	 * @name        isLoaded
	 * @description Checks to see if a file has been loaded yet.
	 * @return      Boolean
	 * @param       String file - The file to check if it was loaded.
	 */
	jFrame.isLoaded = function(file) {
		return jQuery.inArray(file, _loaded) > -1;
	};
	
	/**
	 * 
	 */
	jFrame.load = function(file, cache) {
		var 
			self = this,
			ret  = false;
		
		// if we are caching and it's in cache, return that
		// notice, that an empty file will return undefined,
		// so we check here to see if it's been loaded only
		if (jFrame.isLoaded(file)) {
			eval(_cache[file]);
			
			return true;
		}
		
		// make a syncrhonious request because we want to have access
		// to the js right after loading without having to use a callback
		jQuery.ajax({
			async      : false,
			cache      : cache,
			dataType   : 'script',
			global     : false,
			success    : function(data, textStatus) {
				_loaded[_loaded.length] = file;
				_cache[file]            = data;
				
				ret = true;
			},
			url        : file
		});
		
		// return the function
		return ret;
	};
	
	
	
	jFrame.registry = {
		get : function(key) {
			return _registry[key];
		},
		
		set : function(key, val) {
			_registry[key] = val;
			
			return jFrame.registry;
		}
		
	};
	
	
	
	/**
	 * 
	 */
	function _camelCase(str, ucFirst) {
		var strs = str.split(/[^a-z^0-9^_]/);
		
		$.each(strs, function(i) {
			strs[i] = strs[i].charAt(0).toUpperCase() + strs[i].substring(1);
		});
		
		var str = strs.join('');
		
		if (!ucFirst)
			str = str.charAt(0).toLowerCase() + str.substring(1);
		
		return str;
	};
	
	/**
	 * @name        _normalizePath
	 * @description Normalizes the input path and returns it.
	 * @return      String
	 */
	function _normalizePath(path) {
		// trim whitespace
		path = $.trim(path);
		
		// nomralize directory separators
		path = path.replace(/\\/g, '/');
		
		// trim off starting forward slash
		if (path.charAt(0) === '/') {
			path = path.substring(1)
		}
		
		// trim off ending forward slash
		if (path.charAt(path.length - 1) === '/') {
			path = path.substring(0, path.length - 1)
		}
		
		return path;
	};
	
})(jQuery);