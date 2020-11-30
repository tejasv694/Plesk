/**
 * @author Trey Shugart
 * @date   2009-05-05
 * @todo   IMPROVE PERFORMANCE!!!
 */
;(function($) {
	

	
	$.fn.float = function(config) {
		var 
			// by utilizing the selector, this will take into account elements
			// that haven't yet been added to the DOM
			sel    = this.selector
			config = $.extend({
				// how far from the top of the window it should float
				offsetTop  : 0,
				// how far from the left of the window it should float
				offsetLeft : 0,
				// what object/selector to bind the scroll event to (must be a parent)
				scroller   : window,
				// at what z-index level the window should float
				zIndex     : 1000
			}, config);
		
		// by binding this to a scroll event we can utilize the selector passed
		$(config.scroller).bind('scroll', function(e) {
			var win = $(win);
			
			// now find the elements we are suppsed to detach and float
			$(sel).each(function() {
				var 
					t  = $(this),
				
					st = win.scrollTop() + config.offsetTop,
					sl = win.scrollLeft() + config.offsetLeft;
				
				
				if ($('.tab-survey-designers-panel ul').position().top < 0 ) {
					t.data('__float.isDetached', false);
					$('.tab-survey-designers-panel ul').css("top", "0");
					
					
				}
				
			
				if (!t.data('__float.isDetached')) {
					t
						.data('__float.isDetached', false)
						.data('__float.originalZIndex', t.css('z-index'))
						.data('__float.originalPosition', t.css('position'))
						
						.data('__float.originalOffset', t.offset())
						.data('__float.placeholder', $('<div />').css({
							width        : t.outerWidth(),
							display      : t.css('display'),
							float        : t.css('float'),
							position     : t.css('position'),
							clear        : t.css('clear'),
							marginTop    : t.css('margin-top'),
							marginRight  : t.css('margin-right'),
							marginBottom : t.css('margin-bottom'),
							marginLeft   : t.css('marginLeft')
						}));
				}
				
				// if not yet detached and we have scrolled passed it, either on the x or y axis
				if (!t.data('__float.isDetached') && (st > t.data('__float.originalOffset').top || sl > t.data('__float.originalOffset').left)) {
					// show the placeholder
					t.data('__float.placeholder').insertBefore(t);
					
					// detach it
					t
						.data('__float.isDetached', true)
						.css({
							position : 'relative',
							zIndex   : config.zIndex
						});
				// otherwise if it is detached and the window's scroll offsets are less than the original offsets
				} else if (t.data('__float.isDetached') && st < t.data('__float.originalOffset').top && sl < t.data('__float.originalOffset').left) {
					// hide the placholder
					t.data('__float.placeholder').remove();
					
					// and restore the original position
		
					t
						.data('__float.isDetached', false)
						.css({
							position : t.data('__float.originalPosition'),
							top      : 0,
							left     : 0,
							zIndex   : t.data('__float.originalZIndex')
						});
				}
		
				
				
				if (t.data('__float.isDetached')) {
					// of the scroll top is greater than the original offset top
				
					if (st >  t.data('__float.originalOffset').top) {
						// a hack
						offtop = st -  ( $('.form-menu').position().top + config.offsetTop);
					} else {
						offtop = t.data('__float.originalPosition').top;
						
					}
					
					t.css('top', offtop);

					// of the scroll left is greater than the original offset left
					//t.css('left', sl > t.data('__float.originalOffset').left ? sl : t.data('__float.originalOffset').left);
				}
			});
		});
	}
	
})(jQuery);