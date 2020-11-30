;(function($) {
	
	var context = jFrame.getActiveInstance().getParam('context');
	
	
	
	// only allow the selecting of one radio button
	// this has to be done because we need our radio buttons to have a unique name
	$('.form-element-option-list :radio', context).live('click', function() {
		$('.form-element-option-list').find(':radio:checked').removeAttr('checked');
		
		$(this).attr('checked', 'checked');
	});

})(jQuery);