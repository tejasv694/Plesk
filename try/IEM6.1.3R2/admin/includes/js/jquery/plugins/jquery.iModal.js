;(function($) {

	/*
	 * iModal wrapper code
	 *
	 * All new code attempting to use $.iModal should use the window plugin.
	 */

	var undefined;
	var _modal;
	var _defaults = {
			overlay: 50,
			overlayCss: {},
			containerCss: {},
			close: true,
			closeTitle: 'Close',
			onOpen: null,
			onShow: null,
			onClose: null,
			onError: null,
			onBeforeClose: null,
			type: 'string',
			url: '',
			urlData: {},
			width: 630,
			buttons: false,
			zIndex: 0,
			title: '',
			data: ''
		};



	$.iModal = function(config, html) {
		var oldOptions = {};

		$.extend(oldOptions, _defaults, config);

		var newOptions = {
				modal         : { opacity : parseFloat('.' + (parseInt(oldOptions.overlay) || 5)) },
				width         : oldOptions.width,
				selectorClose : oldOptions.close ? '.close' : false
			};

		// create the modal
		_modal = $.fn.window.create(html).init(newOptions).title(oldOptions.title).content(oldOptions.data).jQuery();
		
		// get the overlay
		var overlay = _modal.window().getModal();

		// content css
		_modal.find('.content-container').css(oldOptions.containerCss);

		// set modal css
		if (overlay) {
			overlay.css(oldOptions.overlayCss);
		}

		// if there are buttons
		if (oldOptions.buttons) {
			_modal.find('.content').addClass('with-buttons');
			_modal.find('.buttons').html(oldOptions.buttons).show();
		}

		// if allowing to close
		if (oldOptions.close) {
			// the close button title
			_modal.find('.close').attr('title', oldOptions.title);
		} else {
			_modal.find('.close').hide();
		}

		// bind events
		_modal.bind('windowBeforeOpen', oldOptions.onOpen || function() {});
		_modal.bind('windowAfterOpen', oldOptions.onShow || function() {});
		_modal.bind('windowBeforeClose', oldOptions.onBeforeClose || function() {});
		_modal.bind('windowAfterClose', oldOptions.onClose || function() {});

		// after the window is closed, set the _modal variable to null
		_modal.bind('windowAfterClose', function() {
			var overlay = _modal.window().getModal();

			if (overlay) {
				overlay.remove();
			}

			_modal.remove();

			_modal = undefined;
		});

		// if a url is passed, then it's ajax time
		if (oldOptions.url) {
			_modal.window().loadAndOpen(oldOptions.url, oldOptions.urlData);
		} else {
			_modal.window().open();
		}

		return _modal;
	};

	$.iModal.close = function () {
		if (typeof _modal !== 'undefined') {
			return _modal.window().close();
		}
	};

	$.modal = function() {
	};

	$.modal.close = function () {
		$.iModal.close();
	};

	$.fn.iModal = function (options) {
		return $.iModal(options, $('<div />').append(this).html());
	};

})(jQuery);