/**
 * @name Keys
 * @version 1.0.0
 * @description Binds events and/or stops propagation (if so desired) for the specified keys.
 * @author Trey Shugart
 * @date 2008-03-18
 * @copyright Copyright 2008 Trey Shugart
 * @license GNU LGPL (http://www.gnu.org/licenses/lgpl.html)
 *
 * NOTICE!
 * You have to be careful when using alerts on the keydown/keypress events because the
 * key cache won't be cleared if you release the buttons while the alert box is present.
 */
(function($) {
	
	$.extend({
		// shortcut if not specifying a selector
		keys: function(keySelectors, options, callback) {
			$('body').keys(keySelectors, options, callback);
		},

		/**
		 * @private
		 * @param event e The event to capture the key code from
		 * Retrieves the current key being pressed
		 */
		keyCode: function(e) {
			var e = (!e) ? window.event : e;
			return k = (e.keyCode) ? e.keyCode : e.which;
		},

		// map special keys
		keyName: function(e) {
			var keyMap = {
				8: 'backspace',
				9: 'tab',
				13: 'enter',
				16: 'shift',
				17: 'ctrl',
				18: 'alt',
				19: 'pause',
				19: 'break',
				20: 'caps lock',
				27: 'escape',
				32: 'space',
				33: 'page up',
				34: 'page down',
				35: 'end',
				36: 'home',
				37: 'left arrow',
				38: 'up arrow',
				39: 'right arrow',
				40: 'down arrow',
				45: 'insert',
				46: 'delete',
				47: 'left window key',
				92: 'right window key',
				93: 'select key',
				96: 'numpad 0',
				97: 'numpad 1',
				98: 'numpad 2',
				99: 'numpad 3',
				100: 'numpad 4',
				101: 'numpad 5',
				102: 'numpad 6',
				103: 'numpad 7',
				104: 'numpad 8',
				105: 'numpad 9',
				106: 'numpad *',
				107: 'numpad +',
				109: 'numpad -',
				110: 'numpad .',
				111: 'numpad /',
				112: 'f1',
				113: 'f2',
				114: 'f3',
				115: 'f4',
				116: 'f5',
				117: 'f6',
				118: 'f7',
				119: 'f8',
				120: 'f9',
				121: 'f10',
				122: 'f11',
				123: 'f12',
				144: 'num lock',
				145: 'scroll lock',
				186: ';',
				187: '=',
				188: ',',
				189: '-',
				190: '.',
				191: '/',
				192: '`',
				219: '[',
				220: '\\',
				221: ']',
				222: "'"
			};

			// map the number keys
			for (var i = 48; i <= 57; i++)
				keyMap[i] = i - 48;

			// map alpha characters
			for (var i = 65; i <= 90; i++) {
				var alpha = 'abcdefghijklmnopqrstuvwxyz';

				keyMap[i] = alpha.charAt(i - 65);
			}

			return keyMap[$.keyCode(e)];
		}
	});

	$.fn.extend({
		// if specifying a selector
		keys: function(keySelectors, options, callback) {
			var
				all,
				$$          = $(this),
				currentKeys = [],
				fn          = 'bind';

			// use event delegation if it exists
			if ($.isFunction($.fn.bond)) {
				fn = 'bond';
			} else if ($.isFunction($.fn.live)) {
				fn = 'live';
			}

			// normalize a global selector
			if ($$ === ('body' || window)) {
				$$ = $('body');
			}

			// allow a function to be passed as the first argument
			// defaulting the keySelectors to all (*)
			if ($.isFunction(keySelectors)) {
				callback     = keySelectors;
				keySelectors = '*';
			}

			// just in case they didn't pass any options
			if ($.isFunction(options)) {
				callback = options;
				options  = {};
			}

			options = $.extend({}, options);

			// variable event
			$$[fn]('keydown', function(e) {
				var
					k       = $.keyCode(e),
					element = $(this),
					ret     = true;
				
				// if the key is already stored in the cache, there is no reason to append it again; prevents multiple entries such as when holding down a key
				if ($.inArray(k, currentKeys) === -1) {
					currentKeys[currentKeys.length] = k;
				}

				// separate keys and key combos are separated by an unescaped comma, if no unescaped comma is found, there is only one set
				var sets = (keySelectors.match(/\\{0},/))
					? keySelectors.split(/\\{0},/)
					: [keySelectors];

				$.each(sets, function(i, set) {
					var
						set     = $.trim(set),
						combo   = [],
						matches = 0,
						setArr  = set.split(/\\{0}\+/);
					
					$.each(currentKeys, function(i, key) {
						// see if the user used a literal key number selector i.e. [119]+[16]+tab
						combo[i] = set.match(/\+?\[[0-9]+\]\+?/)
							? '[' + key + ']'
							: $.keyName(e);

						$.each(setArr, function(ii, match) {
							// trim whitespace for comparison later
							setArr[ii] = $.trim(match);

							if ($.trim(match.toLowerCase()) === combo[i]) {
								matches++;
							}
						});
					});

					// allow for key combinations to be in any order; combos must also not have extra keys being pressed before the match, and a '*' can be passed to do the action for any key
					if ((matches === combo.length && matches === setArr.length) || set === '*') {
						// fire a callback if passed and/or stop propagation
						if ($.isFunction(callback)) {
							ret = callback.apply(element, [e]) === false
								? false
								: true;
						}
					}
				});

				return ret;
			});

			$$[fn]('keyup', function() {
				currentKeys = [];
			});
			
			return $$;
		},
		
		unkeys : function(callback) {
			var fn = 'unbind';

			// use event delegation if it exists
			if ($.isFunction($.fn.bond)) {
				fn = 'unbond';
			} else if ($.isFunction($.fn.live)) {
				fn = 'die';
			}
			
			return $(this)[fn]('keydown', callback);
		}
	});
})(jQuery);