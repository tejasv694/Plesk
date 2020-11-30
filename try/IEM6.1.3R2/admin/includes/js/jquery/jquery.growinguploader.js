(function($){
	$.fn.growingUploader = function (options) {
		var _me = this;

		var options = $.extend({
			fileSelector: ':file',
			clearSelector: ':button',
			createUniqIds: true,
			minimum: 1,
			maximum: 0,
			first: true
		}, options);

		var file = $(options.fileSelector, this);
		var clear = $(options.clearSelector, this);

		this.fileChange = function () {
			var val = file.val();
			if (val != lastval) {
				lastval = val;
				var emptyfiles = $(':file[value=]', this.parent());

				if (val) {
					//	duplicate the row if there are no more blank file inputs
					if (!emptyfiles.length) {
						this.each(function(){
							node = $(this).clone();
							$(node).find(options.fileSelector).attr('id', 'growingUpload_' + _me.randomString());
							$(node).insertAfter(this).growingUploader({first:false});

						});
					}
				} else {
					//	remove the row if there are other blank file inputs
					if (emptyfiles.length > 1) {
						this.each(function(){
							$(this).remove();
						});
					}
				}
			}

			if (val) {
				$(options.clearSelector, this).show();
			} else {
				$(options.clearSelector, this).hide();
			}
		};

		this.clearClick = function () {
			file.val('');
			this.fileChange();
		};

		if (options.first) {
			var lastval = file.val();
			//	wrap our first upload container in a parent div so we can examine all uploaders as a collection
			this.appendTo($('<div></div>').insertBefore(this));
		} else {
			var lastval = '';
			file.val(lastval);
		}

		this.fileChange();

		this.randomString = function() {
			var chars = "0123456789abcdefghiklmnopqrstuvwxyz";
			var string_length = 8;
			var randomstring = '';
			for (var i=0; i<string_length; i++) {
				var rnum = Math.floor(Math.random() * chars.length);
				randomstring += chars.substring(rnum,rnum+1);
			}
			return randomstring;
		}


		return this.each(function(){
			file.change(function(){ _me.fileChange(); });
			clear.click(function(){ _me.clearClick(); });
		});
	};
})(jQuery);

$(function(){
	$('.Uploader').growingUploader();
});