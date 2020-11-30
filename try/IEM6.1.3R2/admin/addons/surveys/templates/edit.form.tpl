

<link rel="stylesheet" type="text/css" href="../modules/form/styles/edit.form.css" />
<script type="text/javascript" src="../javascript/jquery/plugins/jquery.form.js"></script>
<script type="text/javascript" src="includes/js/jquery/plugins/jquery.jFrame.js"></script>

<script type="text/javascript">
		// form module jFrame instance
		new jFrame({
			controllerPath : 'addons/surveys/js/',
			cache          : false
		}, 'moduleForm');
	
	
	jFrame.getInstance('moduleForm').dispatch('edit.form');
	
</script>

<form id="form-canvas" action="index.php?section=module&action=custom&module=form&moduleController=admin&moduleAction=save.form" method="post">	
	<input type="hidden" name="form[id]"{if $form.id}value="{$form.id}"{/if} />
	
	<div class="BodyContainer">
		<div class="Intro"> 
			<h2 class="Heading1">{$langvars.editFormTitle}</h2>
			<p>{$langvars.editFormDescription}</p>
			
			{$flashMessage}
			
			<div>
				<button type="submit" name="saveAndContinue" value="1">{$langvars.editFormButtonSaveAndContinue}</button>
				<button type="submit" name="saveAndExit" value="1">{$langvars.editFormButtonSaveAndExit}</button>
				<button type="button" class="cancel">{$langvars.editFormButtonExit}</button>
			</div>
		</div>
		
		<div class="form-menu ui-tabs">
			<ul class="tabnav">
				<li><a href="#tab-form-fields">{$langvars.tabFormFields}</a></li>
				<li><a href="#tab-form-options">{$langvars.tabFormOptions}</a></li>
			</ul>
			
			<div id="tab-form-fields">
				<ul>
					<li id="form-element-text" class="{type: 'text'}">{$langvars.formMenuText}</li>
					<li id="form-element-textarea" class="{type: 'textarea'}">{$langvars.formMenuTextarea}</li>
					<li id="form-element-radio" class="{type: 'radio'}">{$langvars.formMenuRadio}</li>
					<li id="form-element-checkbox" class="{type: 'checkbox'}">{$langvars.formMenuCheckbox}</li>
					<li id="form-element-select" class="{type: 'select'}">{$langvars.formMenuSelect}</li>
					<li id="form-element-select-country" class="{type: 'select-country'}">{$langvars.formMenuSelectCountries}</li>
					<li id="form-element-file" class="{type: 'file'}">{$langvars.formMenuFile}</li>
					<li id="form-element-section-break" class="{type: 'section.break'}">{$langvars.formMenuSectionBreak}</li>
				</ul>
			</div>
			
			<div id="tab-form-options">
				<fieldset>
					<ul>
						<li>
							<div>
								<input type="checkbox" id="email-feedback" name="form[email_feedback]" value="1" {if $form.email_feedback == 1}checked="checked"{/if} />
								<label for="email-feedback">{$langvars.formEmailFeedbackLabel} <span class="required">*</span></label>
								<img class="tooltip" style="position: relative; top: 2px;" src="images/help.gif" alt="{$langvars.formTooltipTitleEmailFeedback}" title="{$langvars.formTooltipDescriptionEmailFeedback}" />
								<div id="email-feedback-to-container">
									<img src="images/nodejoin.gif" />
									<input id="email-feedback-to" name="form[email]" type="text" value="{$form.email}" />
								</div>
							</div>
						</li>
						
						<li>
							<div>
								<input type="radio" id="form-show-message" name="form[after_submit]" value="show_message" {if $form.after_submit == 'show_message'}checked="checked"{/if} />
								<label for="form-show-message">{$langvars.formShowMessageLabel}</label>
								<img class="tooltip" style="position: relative; top: 2px;" src="images/help.gif" alt="{$langvars.formTooltipTitleShowMessage}" title="{$langvars.formTooltipDescriptionShowMessage}" />
								<div id="show-message-container">
									<img src="images/nodejoin.gif" style="vertical-align: top;" />
									<textarea name="form[show_message]" style="width: 140px; height: 100px;">{$form.show_message}</textarea>
								</div>
							</div>
						</li>
						
						<li>
							<div>
								<input type="radio" id="form-show-page" name="form[after_submit]" value="show_uri" {if $form.after_submit == 'show_uri'}checked="checked"{/if} />
								<label for="form-show-page">{$langvars.formShowPageLabel}</label>
								<img class="tooltip" style="position: relative; top: 2px;" src="images/help.gif" alt="{$langvars.formTooltipTitleShowUri}" title="{$langvars.formTooltipDescriptionShowUri}" />
								<div id="show-message-container">
									<img src="images/nodejoin.gif" />
									<input name="form[show_uri]" type="text" value="{$form.show_uri}" style="width: 120px;" />
									<a id="show-page-uri-browser" href="#"><img src="../modules/form/images/insertlink.gif" alt="{$langvars.formShowPageAlt}" style="vertical-align: -2px; padding-left: 2px;" /></a>
								</div>
							</div>
						</li>
						
						<li style="padding-left: 5px;">
							<div>
								<label for="form-show-message">{$langvars.formErrorMessageLabel} <span class="required">*</span></label>
								<img class="tooltip" style="position: relative; top: 2px;" src="images/help.gif" alt="{$langvars.formTooltipTitleErrorMessage}" title="{$langvars.formTooltipDescriptionErrorMessage}" />
								<div><textarea name="form[error_message]" style="width: 158px; height: 100px;">{$form.error_message}</textarea></div>
							</div>
						</li>
						
						<li style="padding-left: 5px;">
							<div>
								<label for="form-show-message">{$langvars.formSubmitButtonTextLabel} <span class="required">*</span></label>
								<img class="tooltip" style="position: relative; top: 2px;" src="images/help.gif" alt="{$langvars.formTooltipTitleSubmitButtonText}" title="{$langvars.formTooltipDescriptionSubmitButtonText}" />
								<div><input type="text" name="form[submit_button_text]" style="width: 158px;" value="{$form.submit_button_text}" /></div>
							</div>
						</li>
					</ul>
				</fieldset>
			</div>
		</div>
		
		<div class="FloatLeft">
			<div id="canvas">
				<h2 style="margin: 0;"><input id="form-title" class="edit-in-place" type="text" name="form[name]" value="{$form.name}" title="{$langvars.formDefaultName}" style="width: 98.2%;" /></h2>
				<p style="margin: 2px 0 0 0;"><input class="edit-in-place" type="text" name="form[description]" value="{$form.description}" title="{$langvars.formDefaultDescription}" style="width: 98.2%;" /></p>
				<div class="hr"></div>
				<ul>
					{if $widgetTemplates}
						{foreach from=$widgetTemplates item=widgetTemplate}
							{$widgetTemplate}
						{/foreach}
					{/if}
				</ul>
				<div id="canvas-empty">
					{$langvars.canvasEmptyText}
				</div>
			</div>
			
			<div>
				<button type="submit" name="saveAndContinue" value="1">{$langvars.editFormButtonSaveAndContinue}</button>
				<button type="submit" name="saveAndExit" value="1">{$langvars.editFormButtonSaveAndExit}</button>
				<button type="button" class="cancel">{$langvars.editFormButtonExit}</button>
			</div>
		</div>
		
		<div class="clear"></div>
	</div>
</form>

<!-- draggable helper template -->
<div id="__template__form-element-drag-helper" style="display: none;">
	<div class="form-element-drag-helper" style="width: 220px; height: 70px;">
		#{img}
		<p>#{text}</p>
	</div>
</div>

<!-- sortable helper template -->
<div id="__template__form-element-sort-helper" style="display: none;">
	<div class="form-element-sort-helper" style="width: 700px; height: 70px;"></div>
</div>