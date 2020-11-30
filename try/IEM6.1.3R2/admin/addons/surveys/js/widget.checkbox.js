;(function($) {
	
	var lang    = jFrame.registry.get('lang');
	var context = $(jFrame.getActiveInstance().getParam('context'));
	
	
	
	// initialize the other field
	_initOther();
	
	
	
	$('.add-option-to-list', context).live('click', function() {


		var par   = $(this).closest('tr');
		var len   = par.parent().children().length;
		
		var clone = par.template().parse({ fieldIndex : len });
		var text  = clone.find(':text');
		
		// set default values
		text.val(lang.Addon_Surveys_WidgetValueField + len);
		text.attr('title', text.attr('title').replace(/[0-9+]$/, '') + len);
		clone.find(':checkbox').removeAttr('checked');
		clone.find('.remove-option-from-list').show();
		
		// make each input unique
		clone.find(':input').each(function() {
			var $this = $(this);
			
			$this.attr('name', $this.attr('name').replace(/\[field\]\[[^\]]*\]/, '[field][newField' + len + ']'));
		});
		
		// insert the clone after the parent
		par.after(clone);
		
		// focus the first field
		clone.find(':input:text:first').focus().select();
		
		// initialize the other field
		_initOther();
		
		return false;
	});
	
	$('.remove-option-from-list', context).live('click', function() {
		var tr = $(this).closest('tr');
		
		tr.prev().find(':text').focus().select();
		
		tr.remove();
		
		_initOther();
		
		return false;
	});
	
	$('.add-other', context).live('click', function() {
		
		$('.other-row', context).show();
		
		_initOther();
		
		return false;
	});
	
	$('.remove-other', context).live('click', function() {
		$(this).closest('tr').hide();
		
		_initOther();
		
		return false;
	});
	
	// when a key is presed do the same thing as if the option add button was pressed
	$('.form-element-option-list :text').live('keydown', function(e) {
		if (e.keyCode == 9) {
			$(this).closest('tr').find('.add-option-to-list').trigger('click');
			
			return false;
		}
	});
	
	
	
	function _initOther() {
		var others   = context.find('.add-other-container');
		var otherRow = $('.other-row', context);
		others.attr('style','display:none');
		
		if (otherRow.css('display') == 'none') {
			others.filter(':last').show();
			otherRow.find('[name*="[field]"]').attr('disabled', 'disabled');
		} else {
			otherRow.find('[name*="[field]"]').removeAttr('disabled');
		}
	}
	
})(jQuery);