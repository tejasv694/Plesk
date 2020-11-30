/**
 * Modified by Trey Shugart
 * 
 * Namespaced and implemented new methods.
 * 
 * Depends on jQuery.plugin
 */

/**
 * ORIGINAL LICENSE AND COPYRIGHT
 * 
 * Metadata - jQuery plugin for parsing metadata from elements
 *
 * Copyright (c) 2006 John Resig, Yehuda Katz, J?�rn Zaefferer, Paul McLanahan
 *
 * Dual licensed under the MIT and GPL licenses:
 * 	http://www.opensource.org/licenses/mit-license.php
 * 	http://www.gnu.org/licenses/gpl.html
 *
 * Revision: $Id: jquery.metadata.js 4187 2007-12-16 17:15:27Z joern.zaefferer $
 */
;(function() {
	
	var _defaults = {
		type   : 'class',
		name   : 'metadata',
		cre    : /({.*})/,
		single : 'metadata'
	};
	
	
	
	// add the plugin
	$.fn.plugin.add('metadata', function(config) {
		$(this).config('metadata', $.extend(_defaults, config));
	});	
	
	// define the methods
	$.extend($.fn.metadata.fn, {
		get : function(key) {
			var
				self     = $(this),
				elem     = self.get(0),
				settings = self.config('metadata');
			
			// check for empty string in single property
			if (!settings.single.length) {
				settings.single = 'metadata';
			}
			
			var data = $.data(elem, settings.single);
			
			// returned cached data if it already exists
			if (data) {
				return data;
			}
			
			data = '{}';
			
			if (settings.type == 'class') {
				var m = settings.cre.exec(elem.className);
				
				if (m) {
					data = m[1];
				}
			} else if (settings.type == 'elem') {
				if (!elem.getElementsByTagName) {
					return undefined;
				}
				
				var e = elem.getElementsByTagName(settings.name);
				
				if (e.length) {
					data = $.trim(e[0].innerHTML);
				}
			} else if (elem.getAttribute != undefined) {
				var attr = elem.getAttribute(settings.name);
				
				if (attr) {
					data = attr;
				}
			}
			
			if (data.indexOf('{') < 0) {
				data = '{' + data + '}';
			}
			
			data = eval('(' + data + ')');
			
			return typeof key === 'string'
				? data[key]
				: data;
		},
		
		filter: function(key, val) {
			var self = this;
			
			if (typeof val === 'undefined') {
				var val = '.*';
			}
			
			var d = _defaults;
			
			return $(this).filter(function(i) {
				var metadata = $(this).metadata().get();
				
				for (var i in metadata) {
					if (key === i && (typeof val === 'undefined' || metadata[i] === val)) {
						return true;
					}
				}
				
				return false;
			});
		},
		
		unset: function(key) {
			var d = _defaults;
			
			return $(this).each(function() {
				if (typeof key !== 'undefined') {
					var
						keyValRegex = new RegExp('{.*(' + key + '\\s*:\\s*(\'|\"|{)?.*(\'|\"|})?).*}'),
						keyValMatch = $(this).attr(d.type).match(keyValRegex);
					
					if (keyValMatch) {
						$(this)
							.attr(d.type, $(this).attr(d.type).replace(keyValMatch[1], '')) // replace the keyval pairs
							.attr(d.type, $(this).attr(d.type).replace(/{\s*,\s*/, '{')) // replace any extra prepended whitespace and commas
							.attr(d.type, $(this).attr(d.type).replace(/\s*,\s*}/, '}')) // replace any appended whitespace and commas
							.attr(d.type, $(this).attr(d.type).replace(/\s*{\s*}\s*/, '')); // if the metadata object is empty, remove it
					}
				} else {
					$(this).attr(d.type).replace(/{.*}/, '');
				}
			});
		},
		
		set: function(key, val) {
			var d = _defaults;
			
			return $(this).each(function() {
				var 
					attr           = $(this).attr(d.type),
					hasMeta        = attr.match(/{.*}/), 
					hasMetaContent = attr.match(/{(.*:*.*)}/),
					meta           = '{'+ (hasMetaContent ? hasMetaContent[1] + ', ' : '') + key + ': ' + _quote(val) + '}';
				
				$(this).attr(d.type, hasMeta ? attr.replace(/{.*}/, meta) : attr + ' ' + meta);
			});
		}
	});
	
	
	
	function _quote(subject) {
		switch (typeof subject) {
			case 'string':
				return "'" + subject + "'";
			break;
			
			default :
				return subject;
		}
	}

})(jQuery);