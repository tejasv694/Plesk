;(function($) {

	jFrame.registry.set('lang', {"resultNoResponses":"No responses have been collected for this survey yet.","resultSelectForm":"Please choose a survey first."});
	
	var lang = jFrame.registry.get('lang');
	
	$('table.table-selector').tableSelector();
	
	$('button.cancel').bind('click', function() {
		if (confirm(lang.exportResponsesConfirmCancel)) {
			window.location = 'index.php?section=module&action=custom&module=form&moduleController=admin&moduleAction=index';
		}
	});
	
	$(':button.export').bind('click', function() {
		window.location = 'index.php?Page=Addons&Addon=surveys&Action=export&ajax=1&surveyId=' + $('#survey_id').attr("value");
	});
	
	$('.browse').click(function() {
		 window.location = 'index.php?Page=Addons&Addon=surveys&Action=viewresponses&surveyId=' + $('#survey_id').attr("value");
	});

	$(':submit').bind('click', function() {
		// if there are no responses for this form, notify the user
		if ($('#form-responses').find(':radio:checked').hasClass('no-responses')) {
			alert(lang.resultNoResponses);
			return false;
		}
	});

	$('#form-responses').bind('submit', function() {
		if ($(':radio:checked').length == 0) {
			alert(lang.resultSelectForm);
			
			return false;
		}
	});
	
	$('a.others_hide').live('click', function() {
		targethide = '#others' + this.id;
		targethref ="a#" + this.id;
			if ($(targethide).is(':visible')) {
				$(targethref).html('Show Answers');
			} else {
				$(targethref).html('Hide Answers');
			}
		$(targethide).toggle();
	});


	jQuery.fn.infoMessage = function(msg) {

		$(this).html('<table cellspacing="0" cellpadding="0" width="100%" id="MessageTable" ><tr><td><table border="0" cellspacing="0" cellpadding="0"><tr><td class="Message" width="20" valign="top"><img  id="MessageImage" src="images/info.gif"  hspace="10" vspace="5"></td><td class="Message" width="100%" style="padding-top: 8px;padding-bottom: 5px;" id="MessageText">'+msg+'</td></tr>    </table></td></tr></table>');
		if($(this).css('display') == 'none'){
			$(this).show('slow');
		}
		$('#'+$(this).attr('id') + ' .Message').animate({ backgroundColor: '#A6D3E1' }).animate({ backgroundColor: '#F4F4F4' });
	}
	
})(jQuery);


function showResponsesAnswer(offset,widgetid,surveyid,total_others) { 
	targetdiv = "tbody#othersmore_" + widgetid;
	// loading image
	$(targetdiv).html('<tr><td></td><td><br /><img src="images/loadingAnimation.gif"><br /></td></tr>');
	$.get("index.php?Page=Addons&Addon=surveys&Action=result_responseslist&widgetId="+ widgetid + "&total_others="+ total_others + "&start=" + offset + "&surveyId=" + surveyid + "&ajax=1", 
		function(data){
			$(targetdiv).html(data);
		}
	);
}



