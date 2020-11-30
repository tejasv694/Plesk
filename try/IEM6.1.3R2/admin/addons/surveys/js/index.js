;(function($) {
	
	var 
		form       = $('#form-forms'),
		lang       = jFrame.registry.get('lang'),
		deleteBtns = $('table a.delete'),
		deleteBtn  = $('button.delete'),
		createBtn  = $('button.create'),
		checkAll   = $('table :checkbox:first'),
		checkboxes = $('table :checkbox:not(:first)');
	
	
	
	// when the check all checkbox is checked, check all of the checkboxes, otherwise
	// uncheck all of the checkboxes
	checkAll.bind('click', function() {
		if (this.checked) {
			checkboxes.attr('checked', 'checked');
		} else {
			checkboxes.removeAttr('checked');
		}
	});
	
	// create a form
	createBtn.bind('click', function() {
		window.location = 'index.php?section=module&action=custom&module=form&moduleController=admin&moduleAction=edit.form';
	});
	
	// when the delete button is clicked, make sure they selected forms they want to delete and that they really want to delete the form
	deleteBtn.bind('click', function() {
		if (_getSelected().length) {
			if (confirm(lang.formConfirmDeleteMultiple)) {
				form.attr('action', 'index.php?section=module&action=custom&module=form&moduleController=admin&moduleAction=delete.form').submit();
			}
		} else {
			alert(lang.formSelectFormsToDelete);
		}
	});
	
	// when the delete action is clicked, make sure they actually want to delete the form
	deleteBtns.bind('click', function() {
		$(this).closest('tr').addClass('deleting');
		
		return confirm(lang.formConfirmDeleteSingle);
	});
	
	
	
	/**
	 * Returns the selected checkboxes in the form list.
	 * 
	 * @return object
	 */
	function _getSelected()
	{
		return checkboxes.filter(':checked');
	}

})(jQuery);