/**
 * jQuery Bond - jQuery live events (including focus and blur) via event delegation
 * 
 * @version 1.1.2
 * @date    2009-05-13
 * 
 * Copyright (c) 2008 Trey Shugart (jquery.illax.in)
 * 
 * Dual licensed under: 
 *  MIT - (http://www.opensource.org/licenses/mit-license.php) 
 *  GPL - (http://www.gnu.org/licenses/gpl.txt)
 */
;(function($) {
	
	var
		_bound        = [],
		_alreadyBound = [];
	
	/**
	 * @param String   events The type of event(s) to bind separated by spaces.
	 * @param Mixed    data   Data to attach to the event object, or optionally the callback method if no data is to be bound.
	 * @param Function fn     The callback to fire on the matched element(s).
	 */
	$.fn.bond = function(events, data, fn) {
		if (!this.selector) {
			return this;
		}
		
		var 
			delegator    = $('body'),
			delegatorRaw = delegator.get(0),
			to           = this.selector,
			fn           = $.isFunction(data) ? data : fn,
			types        = _splitEvents(events);
		
		$.each(types, function(ii, type) {
			_bound[_bound.length] = {
				'type' : type,
				'to'   : to,
				'data' : data,
				'fn'   : fn
			};
			
			if ($.inArray(type, _alreadyBound) === -1) {
				if (type === 'focus' || type === 'blur' || type === 'change') {
					if (delegatorRaw.addEventListener) {
						delegatorRaw.addEventListener(type, function(e){
							e.data = data;
							
							_fire.apply(this, [e]);
						}, true);
					} else {
						function _handler() {
							switch (type) {
								case 'focus':
									return 'onfocusin';
								case 'blur':
									return 'onfocusout';
								default:
									return type;
							}
						}
						
						// normalize event for IE
						delegatorRaw[_handler()] = function() {
							var e = {
								type            : type,
								target          : window.event.srcElement,
								pageX           : window.event.clientX,
								pageY           : window.event.clientY,
								preventDefault  : function() { window.event.returnValue = false; },
								stopPropagation : function() { window.event.cancelBubble = true; },
								data            : data
							}
							_fire.apply(this, [e]);
						}
					}
				} else {
					delegator.bind(type, data, function(e) {
						_fire.apply(this, [e]);
					});
				}
				
				// allows us to check to see if it's already been bound
				_alreadyBound[_alreadyBound.length] = type;
			}
		});
		
		return $(this);
		
		
		
		function _fire(e) {
			if (_bound.length) {
				$.each(_bound, function(i, b) {
					if (e.type === b.type && (!e.target || $(e.target).is(b.to))) {
						if (b.fn.apply(e.target, [e]) === false) {
							e.preventDefault();
						}
					}
				});
			}
		}
	}
	
	$.fn.unbond = function(events) {
		if (typeof events === 'undefined') {
			return $(this);
		}
		
		var 
			events     = _splitEvents(events),
			safe       = [];
		
		if (_bound.length) {
			for (var i in _bound) {
				if ($.inArray(_bound[i].type, events) === -1 && !$(this).is(_bound[i].to)) {
					safe[safe.length] = _bound[i];
				}
			}
		}
		
		// assign the new array
		_bound = safe;
		
		return $(this);
	}
	
	function _splitEvents(events) {
		return events.split(/\s+/);
	}
	
})(jQuery);