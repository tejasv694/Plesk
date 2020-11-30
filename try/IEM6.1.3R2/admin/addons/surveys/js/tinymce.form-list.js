;(function($) {

	var 
		// language variables
		lang = jFrame.registry.get('jsonLangvars'),
	
		// define the iModal buttons
		iModalButtons = ''
			+ '<button id="tinymce-module-form-imodal-close" type="button" style="float: left;">' + lang.tinymceIModalCancel + '</button>'
			+ '<button id="tinymce-module-form-imodal-insert" type="button" style="float: right; font-weight: bold;">' + lang.tinymceIModalInsert + '</button>';
	
	// create the plugin
	tinymce.create('tinymce.plugins.moduleForm', {
		
		init : function(editor, url) {
			editor.addButton('moduleForm', {
				title   : 'Insert a Feedback Form',
				image   : '../modules/form/images/tinymce_icon.png',
				onclick : function() {
					$.iModal.modal.init({
							title   : lang.tinymceIModalTitle,
							buttons : iModalButtons,
							type    : 'ajax',
							url     : 'http://beast/trey/iwp/branches-5-1/admin/index.php?section=module&action=custom&module=form&moduleController=admin&moduleAction=tinymce.form-list'
						});
				}
			});
		},
		
		getInfo : function() {
			
		}
		
	});
	
	// add the plugin
	tinymce.PluginManager.add('moduleForm', tinymce.plugins.moduleForm);
	
	// imodal close button
	$('#tinymce-module-form-imodal-close').live('click', function() {
		$.iModal.modal.close();
	});
	
	// imodal insert button
	$('#tinymce-module-form-imodal-insert').live('click', function() {
		_insertAndClose.apply($('#tinymce-module-form-list').find(':radio:checked'));
	});
	
	// when a form is double clicked, insert it
	$('#tinymce-module-form-list tr').live('dblclick', function() {
			_insertAndClose.apply($(this).closest('tr').find(':radio'));
		});
	
	
	/**
	 * Inserts the selected feedback form and closes the iModal, but only
	 * if a form is actually selected for insertion.
	 */
	function _insertAndClose()
	{
		var 
			radio = $(this),
			id    = radio.val();
		
		// insert a placeholder for the feedback form, the id query string variable is set so it can be parsed 
		// by the script that will replace the placeholder with the actual form on the front end
		tinyMCE.activeEditor.selection.setContent('<img id="feedback-form-placeholder-' + id + '" src="http://beast/trey/iwp/branches-5-1/admin/index.php?section=module&action=custom&module=form&moduleAction=tinymce.placeholder&formId=' + id + '" />');
		
		// close the modal
		$.iModal.modal.close();
	}

})(jQuery);