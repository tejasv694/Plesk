<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
<title>{$survey.surveys_header_text}</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>

<link rel="stylesheet" type="text/css" href="admin/addons/surveys/styles/survey_front.css" />

<script src="admin/includes/js/jquery.js"></script>
<script src="admin/includes/js/jquery/plugins/jquery.plugin.js"></script>
<script src="admin/includes/js/jquery/plugins/jquery.validate.js"></script>

<script type="text/javascript">

jQuery(function($) {

	$('form#survey-{$survey.id}').bind('submit', function() {
		$('#submit').attr("disabled", true);	
	});

	$(':input.Other').bind('click', function() {  
		$(this).prev().prev().attr("checked", "checked");
	});
	
	// set the default survey error handler
	$.fn.form.setErrorHandler('default', function(errors) {
		var form = $(this);

		// remove the error messages
		form.find('.Widget div.Error').remove();
		
		// foreach error, insert them
		$.each(errors, function(errorIndex, error) {
			// fields with the same name represent radio and checkboxes
			var fieldsWithSameName = form.find('[name="' + error.field.attr('name') + '"]');

			// put a error message after the widget title, but do it only once for each widget
			if (fieldsWithSameName.index(error.field) == fieldsWithSameName.length - 1) {
				//error.field.closest('.Widget').find('label:first').after('<div class="Error">' + error.message + '</div>');
				error.field.closest('.Widget').find('label:first').after('<div class="Error"> ' + error.message + '</div>');
			}
		});

		// focus the first error field
		errors[0].field.focus();

		// and alert the first error message
		alert(errors[0].message);
		$('#submit').removeAttr("disabled");		
	});

	// initialize the survey validator
	$('#survey-{$survey.id}').form().init({
			errorClass : 'Required'
		});

	$('#survey-{$survey.id}').form().getFields().each(function() {
		var field = $(this);			
		var label = field.closest('.Widget').find('label:first');
		flabel = label.text();
		flabel = flabel.replace(" \*","\"");
		field.form().setErrorMessage('required', 'The "' + flabel + ' field is required.');
	});

	// language pack the error messages
	$('#survey-{$survey.id}').form().setErrorMessage('required', "{$lang.Addon_Surveys_ErrorRequired}");

	// "Other" fields must not be empty if their corresponding
	// radio button is checked
	$('#survey-{$survey.id} :input.Other').form().setDependency(function(field) {
		if ($(this).siblings(':radio:checked, :checkbox:checked').length && $.trim($(this).val()) == '') {
			$('#submit').removeAttr("disabled");		
			return false;
		}
		return true;
	}, "{$lang.Addon_Surveys_ErrorRequiredOther}");

	// disabling button on submit
	//alert( $('.required:first').form().getErrorMessage() );
});

</script>


<div class="Module_survey">
	<form id="survey-{$survey.id}" action="{$action}" method="post" enctype="multipart/form-data">
		<div class="Name">
		{if $survey.surveys_header == "headerlogo"}
			<img src="admin/temp/surveys/{$survey.id}/{$survey.surveys_header_logo}" />
		{else}
			<h1>{$survey.surveys_header_text}</h1>
		{/if}
		</div>
		
		{if $survey.description}
			<p class="Description">{$survey.description}</p>
		{/if}

		<fieldset>
			{if $errorMessage}
				<p class="Message Error">{$errorMessage}</p>
			{/if}

			{if $successMessage}
				<p class="Message Success">{$successMessage}</p>
			{/if}

			{foreach from=$widgets item=widget}
				<div class="Widget {$widget.className}{if $widget.is_required} NotBlank{/if}">
					<label for="Widget_{$widget.id}">{$widget.name} {if $widget.is_required}<span class="Required">*</span>{/if}</label>

					{if $widget.errors}
						<ul class="Errors">
							{foreach from=$widget.errors item=error}
								<li>{$error}</li>
							{/foreach}
						</ul>
					{/if}

					{if $widget.description}
						<p class="Description">{$widget.description}</p>
					{/if}
						{$widget.template}
				</div>
			{/foreach}

			<div class="Buttons">
				<button id="submit" type="submit">{$survey.submit_button_text}</button>
			</div>
		</fieldset>
	</form>
</div>
</body>
</html>
