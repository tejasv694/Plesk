(function() {
        var iModalButtons = ''
            + '<button id="tinymce-module-form-imodal-close" type="button" style="float: left;">' + 'Cancel' + '</button>'
            + '<button id="tinymce-module-form-imodal-insert" type="button" style="float: right; font-weight: bold;" disabled="disabled">' + 'Insert Survey Link' + '</button>';

	tinymce.create('tinymce.plugins.interspireSurvey', {
		init : function(ed, url) {
			ed.addCommand('mceInsertFeedbackForm', function() {
                                var win = $.fn.window.create({
	                                	title    : 'Link to a Survey',
	    								width	 : 400,	
	    								height	 : 250,
	    								uri      : 'index.php?Page=Addons&Addon=surveys&Action=tinymceSurveylist&ajax=1',
                                        autoOpen : true
                                }).buttons(iModalButtons);
                                win.jQuery().bind('windowAfterOpen', function() {

                                        $('#tinymce-module-form-imodal-close').bind('click', function() {
                                                $.fn.window.closeAll();
                                        });

                                        $('#tinymce-module-form-imodal-insert').bind('click', function() {
                                                _insertAndClose.apply($('#tinymce-module-form-list').find(':radio:checked'));
                                        });

                                        $('#tinymce-module-form-list tr')
                                                .bind('click', function() {
                                                        $('#tinymce-module-form-imodal-insert').removeAttr('disabled');
                                                })
                                                .bind('dblclick', function() {
                                                        _insertAndClose.apply($(this).closest('tr').find(':radio'));
                                                });

                                        function _insertAndClose()
                                        {
	                                        	var radio = $(this);
	        									var id    = radio.val();
	        								 	var surveyname = $('input[name=surveyId]:checked + label').text();
	
	        									// insert a placeholder for the feedback form, the id query string variable is set so it can be parsed
	        									// by the script that will replace the placeholder with the actual form on the front end
	        									// tinyMCE.activeEditor.selection.setContent('<img id=\"feedback-form-placeholder-' + id + '\" src=\"' + iwp.config.get('adminPath') + '/index.php?section=module&action=custom&module=form&moduleAction=tinymce.placeholder&formId=' + id + '\" />');
	        									// editor.execCommand('mceInsertContent', false, '<img id=\"feedback-form-placeholder-' + id + '\" src=\"' + iwp.config.get('adminPath') + '/index.php?section=module&action=custom&module=form&moduleAction=tinymce.placeholder&formId=' + id + '\" />');
	
	        									var selectedtext =  tinyMCE.activeEditor.selection.getContent();
	        									//alert(selectedtext);
	        									if (selectedtext) {
	        											ed.execCommand('mceInsertContent', false, '<a href=\"%%SURVEY_' + id + '_LINK%%\">' + selectedtext + '</a>');
	        									} else {
	        											ed.execCommand('mceInsertContent', false, '<a href=\"%%SURVEY_' + id + '_LINK%%\">Click here to take our survey</a>');
	        									}
	
	        									// close the modal
	        									$.fn.window.closeAll();
                                        }
                                });
			});

                        ed.addButton('interspiresurvey', {
                                title : 'Insert a link to the Survey Page',
                                image : 'addons/surveys/images/tinymce_icon.png',
                                cmd   : 'mceInsertFeedbackForm'
                        });
		}
	});
	tinymce.PluginManager.add('interspiresurvey', tinymce.plugins.interspireSurvey);
})();
