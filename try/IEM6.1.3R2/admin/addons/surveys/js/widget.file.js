;(function($) {
	
	var context = $(jFrame.getActiveInstance().getParam('context'));
		
	_initFileTypes();
	
	// when the file types checkbox is checked, hide the file types field, when it is
	// unchecked, show the file types field
	$('.form-element-file-types :checkbox', context).live('click', function() {
		_initFileTypes();
	});
	
	
	function _initFileTypes()
	{
		var cb = $('.form-element-file-types :checkbox', context);
		
		if (cb.is(':checked')) {
			$('.form-element-file-types span', context).hide();
		} else {
			$('.form-element-file-types span', context).show();
		}
	}

})(jQuery);