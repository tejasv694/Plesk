(function($){
	$.fn.interspireAjaxForm = function (options) {
		var self = this;

		this.form = this;

		this.showLoadingMessage = function (show) {
			if (show) {
				$('.LoadingMessage', self.form).show();
			} else {
				$('.LoadingMessage', self.form).hide();
			}
		};

		this._beforeSubmit = function () {
			if (typeof self.customBinds.beforeSubmit == 'function') {
				if (self.customBinds.beforeSubmit.apply(self, arguments) === false) {
					return false;
				}
			}
			self.clearMessages();
			self.disableForm();
			self.showLoadingMessage(true);
		};

		this._complete = function (xml, result) {
			if (typeof self.customBinds.complete == 'function') {
				if (self.customBinds.complete.apply(self, [xml, result]) === false) {
					return false;
				}
			}
			self.enableForm();
			self.showLoadingMessage(false);
		};

		this._success = function (xml) {
			if (typeof self.customBinds.success == 'function') {
				if (self.customBinds.success.apply(self, [xml]) === false) {
					return false;
				}
			}

			$('alert', xml).each(function(){
				var message = $(this).text();
				if (message) {
					alert(message);
				}
			});

			$('message', xml).each(function(){
				//	find a general form message and display it
				var message = $(this).text();
				var html = ($(this).attr('html') == 'true');
				if (message) {
					if (html) {
						$('.' + self.options.formMessageClassName, self.form).css('display', '').html(message);
					} else {
						$('.' + self.options.formMessageClassName, self.form).css('display', '').text(message);
					}
				}
			});

			$('error', xml).each(function(){
				//	find a general form error and display it
				var message = $(this).text();
				var html = ($(this).attr('html') == 'true');
				if (message) {
					if (html) {
						$('.' + self.options.formErrorClassName, self.form).css('display', '').html(message);
					} else {
						$('.' + self.options.formErrorClassName, self.form).css('display', '').text(message);
					}
				}
			});

			$('field', xml).each(function(){
				//	find messages for each field and display them
				var fieldResponse = $(this);
				var highlight = (fieldResponse.attr('highlight') == 'true');	//	xml, this comes through as a string, convert it
				var name = fieldResponse.attr('name');
				var message = fieldResponse.attr('message');
				var html = (fieldResponse.attr('html') == 'true');

				if (highlight) {
					$(':input[name=' + name + ']', self.form).parents('dd').eq(0).addClass(self.options.highlightClassName);
				}

				if (message) {
					if (html) {
						$('.' + self.options.highlightMessageClassNamePrefix + name, self.form).css('display', '').html(message);
					} else {
						$('.' + self.options.highlightMessageClassNamePrefix + name, self.form).css('display', '').text(message);
					}
				}
			});

			$('redirect', xml).each(function(){
				//	find a redirect instruction and action it
				var url = $(this).text();
				if (url) {
					window.location.href = url;
				}
			});
		};

		this.clearMessages = function () {
			//	removes highlights / messages from the form which are otherwise not removed by a browser's form.reset() function

			$(':input', self.form).each(function(){
				//	remove highlights from individual elements
				$(this).parents('dd').eq(0).removeClass(self.options.highlightClassName);
				$('.' + self.options.highlightMessageClassNamePrefix + $(this).attr('name'), self.form).css('display', 'none');
			});

			//	remove general messages
			$('.' + self.options.formMessageClassName + ', .' + self.options.formErrorClassName, self.form).css('display', 'none');
		};

		this._disableForm = function () {
			self.disabledElements = [];
			$(':input', self.form).each(function(){
				if (!this.disabled) {
					this.disabled = true;
					self.disabledElements.push(this);
				}
			});
		};

		this.disableForm = function () {
			//	disable the form outside of the submit handling thread so the form can submit properly
			setTimeout(self._disableForm, 1);
		};

		this.enableForm = function () {
			$(self.disabledElements).each(function(){
				this.disabled = false;
			});
		};

		this.defaults = {
			highlightClassName: 'Highlight',
			highlightMessageClassNamePrefix: 'HighlightMessage_',
			formErrorClassName: 'FormError',
			formMessageClassName: 'FormMessage',
			dataType: 'xml'
		};

		this.options = $.extend(this.defaults, options);

		this.customBinds = {};

		if (typeof this.options.beforeSubmit == 'function') {
			this.customBinds.beforeSubmit = this.options.beforeSubmit;
		}

		if (typeof this.options.success == 'function') {
			this.customBinds.success = this.options.success;
		}

		if (typeof this.options.complete == 'function') {
			this.customBinds.complete = this.options.complete;
		}

		this.options.beforeSubmit = this._beforeSubmit;
		this.options.success = this._success;
		this.options.complete = this._complete;

		return this.each(function(){
			var obj = $(this);
			obj.ajaxForm(self.options);
		});
	};
})(jQuery);
