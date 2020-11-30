/*
 * Interspire Modal 1.0
 * (c) 2008 Interspire Pty. Ltd.
 *
 * Based on SimpleModal 1.1.1 - jQuery Plugin
 * http://www.ericmmartin.com/projects/simplemodal/
 * http://plugins.jquery.com/project/SimpleModal
 * http://code.google.com/p/simplemodal/
 *
 * Copyright (c) 2007 Eric Martin - http://ericmmartin.com
 *
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * Revision: $Id$
 *
 */
(function ($) {
	$.iModal = function(options) {
		return $.iModal.modal.init(options);
	};
	
	$.modal = function() {
	};
	
	$.modal.close = function () {
		return $.iModal.modal.close(true);
	};
	
	$.iModal.close = function () {
		return $.iModal.modal.close(true);
	};

	$.fn.iModal = function (options) {
		options = $.extend({}, {
			type: 'inline',
			inline: $(this).html()
		}, options);
		return $.iModal.modal.init(options);
	};
	
	$.iModal.defaults = {
		overlay: 50,
		overlayCss: {},
		containerCss: {},
		close: true,
		closeTitle: 'Close',
		onOpen: null,
		onShow: null,
		onClose: null,
		onBeforeClose: null,
		type: 'string',
		width: '630',
		buttons: '',
		title: ''
	};
	
	$.iModal.modal = {
		options: null,
		init: function(options) {
			// Can\'t have more than one modal window open at a time
			if($('#ModalContentContainer').length > 0) {
				return this;
			}
			this.options = $.extend({}, $.iModal.defaults, options);

			if(this.options.type == 'inline') {
				this.options.data = $(this.options.inline).html();
				$(this.options.inline).html('');
			}
			
			this.generateModal();
			return this;
		},
		
		displayModal: function(data)
		{
			this.hideLoader();
			modalContent = '';
			if(!$.browser.msie || $.browser.version >= 7) {
				modalContent = '<div id="ModalTopLeftCorner"></div><div id="ModalTopBorder"></div><div id="ModalTopRightCorner"></div><div id="ModalLeftBorder"></div><div id="ModalRightBorder"></div><div id="ModalBottomLeftCorner"></div><div id="ModalBottomRightCorner"></div><div id="ModalBottomBorder"></div>';
			}
			if(data.indexOf('ModalTitle')>0 && data.indexOf('ModalContent')>0){
				modalContent += '<div id="ModalContentContainer">'+data+'</div>';
			}else{
				modalContent += '<div id="ModalContentContainer"><div class="ModalTitle">'+this.options.title+'</div><div class="ModalContent">'+data+ '</div><div class="ModalButtonRow">'+this.options.buttons+'</div></div>';
			}
				
			cssPosition = 'fixed';
			if($.browser.msie && $.browser.version < 7) {
				cssPosition = 'absolute';
			}
			$('<div/>')
				.attr('id', 'ModalContainer')
				.addClass('modalContainer')
				.css({
					position: cssPosition,
					zIndex: 3100,
					width: this.options.width+'px',
					marginLeft: '-'+(this.options.width/2)+'px'
				})
				.hide()
				.appendTo('body')
				.html('<div class="modalData">'+modalContent+'</div>')
			;
			if($('#ModalContainer').find('.ModalButtonRow, #ModalButtonRow').length > 0) {
				$('#ModalContainer .ModalContent, #ModalContainer #ModalContent').addClass('ModalContentWithButtons');
			}
			if(this.options.close) {
				modal = this;
				$('<a/>')
					.addClass('modalClose')
					.attr('title', this.options.closeTitle)
					.appendTo('#ModalContainer')
					.click(function(e) {
						e.preventDefault();
						modal.close();
					})
				;
				$(document).bind('keypress', function(e) {
					if(e.keyCode == 27) {
						$('#ModalContainer .modalClose').click();
					}
				});
			}
			
			if($.isFunction(this.options.onOpen)) {
				this.options.onOpen.apply(this)
			}
			else {
				$('#ModalContainer').show();
			}
		},
		
		showLoader: function()
		{
			$('<div/>')
				.attr('id', 'ModalLoadingIndicator')
				.html('Test')
				.appendTo('body');
			;
		},
		
		hideLoader: function()
		{
			$('#ModalLoadingIndicator').remove();
		},
		
		generateModal: function()
		{
			$('<div/>')
				.attr('id', 'ModalOverlay')
				.addClass('modalOverlay')
				.css({
					opacity: this.options.overlay / 100,
					height: '100%',
					width: '100%',
					position: 'fixed',
					left: 0,
					top: 0,
					zIndex: 3000
				})
				.appendTo('body')
			;
			
			if($.browser.msie && $.browser.version < 7) {
				wHeight = $(document.body).height()+'px';
				wWidth = $(document.body).width()+'px';
				$('#ModalOverlay').css({
					position: 'absolute',
					height: wHeight,
					width: wWidth
				});
				$('<iframe/>')
					.attr('src', 'javascript:false;')
					.attr('id', 'ModalTempiFrame')
					.css({opacity: 0, position: 'absolute', width: wWidth, height: wHeight, zIndex: 1000, top: 0, left: 0})
					.appendTo('body')
				;				
			}
			
			this.showLoader();
			if(this.options.type == 'ajax') {
				modal = this;
				data = {};
				if(this.options.urlData != undefined) {
					data = this.options.urlData;
				}
				$.ajax({
					url: this.options.url,
					success: function(data) {
						modal.displayModal(data);
					},
					data: data
				});
			}
			else {
				this.displayModal(this.options.data);
			}
		},
		
		close: function(external)
		{
			if($.isFunction(this.options.onBeforeClose)) {
				this.options.onBeforeClose.apply(this, []);
			}
			
			if(this.options.type == 'inline') {
				$(this.options.inline).html(this.options.data);
			}
						
			if($.isFunction(this.options.onClose) && !external) {
				this.options.onClose.apply(this);
			}
			else {
				$('#ModalContainer').remove();
			}
			
			$('#ModalLoadingIndicator').remove();
			$('#ModalOverlay').remove();
			$('#ModalTempiFrame').remove();
		}
	};
})(jQuery);


function ModalBox(title, content){
	var str = '<div class="ModalTitle">'+title+'</div><div class="ModalContent">'+content+ '</div><div class="ModalButtonRow"></div>';
	$.iModal({ data: str });
}

function ModalBoxInline(title, content, width, withCloseButton){
	if(typeof(width) == 'undefined'){
		var width = 800;
	}
	if(typeof(withCloseButton) == 'undefined'){
		var withCloseButton = false;
	}
	if(withCloseButton){
		var str = '<div class="ModalTitle">'+title+'</div><div class="ModalContent">'+$(content).html()+ '</div><div class="ModalButtonRow"></div>';
	}else{
		var str = '<div class="ModalTitle">'+title+'</div><div class="ModalContent">'+$(content).html()+ '</div><div class="ModalButtonRow"><input type="button" class="CloseButton FormButton" value="Close Window" onclick="$.iModal.close();" /></div>';
	}
	$.iModal({ 'data': str, 'width':width });
}

