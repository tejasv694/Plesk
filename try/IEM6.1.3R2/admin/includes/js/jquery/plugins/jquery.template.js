/**
 * @name        Template
 * @version     1.1.0
 * @description Allows the use of an html template. Will replace var placeholders with matching keyVals.
 *              Template requires Plugin, a plugin for authoring plugins.
 * @date        2009-05-21
 * @author      Trey Shugart
 * @copyright   Copyright (c) 2009 Trey Shugart (http://jquery.illax.in)
 * @license     MIT (http://www.opensource.org/licenses/mit-license.php)
 */
;(function($) {

	/**
	 * @name        _cache
	 * @description Holds the saved templates.
	 */
	var _cache = {};
	
	
	
	// register the plugin
	$.fn.plugin.add('template');
	
	/**
	 * @static
	 * @name        load
	 * @description Loads the template specified by id.
	 * @return      Object - Template instance with the specified template loaded.
	 * @param       id     - The template id you wish to load.
	 */
	$.fn.template.load = function(id) {
		if (typeof _cache[id] === 'undefined') {
			return $();
		}
		
		return $(_cache[id]);
	};
	
	/**
	 * @static
	 * @name        exists
	 * @description	Checks to see whether the specified template exists or not.
	 * @return      Boolean - Whether or not the template with the specified id exists.
	 * @param       id      - The id of the template you wish to check for.
	 */
	$.fn.template.exists = function(id) {
		return typeof _cache[id] === 'string';
	};
	
	/**
	 * @instance
	 * @name        save
	 * @description Takes the object passed to jQuery and creates a template out of it. The
	 *              object passed in can be anything acceptable by jQuery (i.e. selector, 
	 *              DOM object, DOM string, etc.).
	 * @return      Object        - Template instance.
	 * @param       id            - The id to save/cache the template with.
	 * @param       removeFromDom - Whether or not to remove from the DOM. Defaults to true.
	 */
	$.fn.template.fn.save = function(id, removeFromDom) {
		if (typeof removeFromDom === 'undefined') {
			removeFromDom = true;
		}
		
		var el = $(this).eq(0);
		
		if (!el.length) {
			return this.jQuery();
		}
		
		_cache[id] = _getHtml(el);
		
		if (removeFromDom) {
			el.remove();
		}
		
		return this.jQuery();
	};
	
	/**
	 * @instance
	 * @name        parse
	 * @description Parses the passed in template. Can be called in conjunction with load
	 *              (i.e. $.fn.template.load(id).parse(keyVals);) or by calling straight
	 *              from a jQuery object (i.e. $(myTemplate).template().parse();).
	 * @return      jQuery  - object of the parsed template
	 * @param       keyVals - Key/value pairs of variables to replace in the template, if any.
	 */
	$.fn.template.fn.parse = function(keyVals) {
		return $(_parse(_getHtml(this), keyVals));
	};
	
	
	
	/**
	 * 
	 */
	function _parse(html, keyVals)
	{
		// if there are variables to replace, then do it
		if (typeof keyVals === 'object') {
			// replace each placeholder
			for (var i in keyVals) {
				html = html.replace(new RegExp('\#((\{' + i + '\})|(\%7B' + i + '%7D))', 'gi'), keyVals[i]);
			}
		}
		
		// return the resulting html
		return html;
	}
	
	/**
	 * 
	 */
	function _getHtml(obj)
	{
		// get the outer html
		return $('<div />').append($(obj).clone()).html();
	}

})(jQuery);