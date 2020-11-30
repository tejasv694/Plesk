<link rel="stylesheet" type="text/css" href="addons/surveys/styles/tableselector.css" />
<link rel="stylesheet" type="text/css" href="addons/surveys/styles/view.response.css" />

<script src="includes/js/jquery/plugins/jquery.tableSelector.js"></script>
<script src="includes/js/jquery/plugins/jquery.jFrame.js"></script>

<script type="text/javascript">
		// form module jFrame instance
		new jFrame({
			controllerPath : 'addons/surveys/js/',
			cache          : false
		}, 'moduleForm');
		
	jFrame.getInstance('moduleForm').dispatch('result.survey');
	
</script>


<div class="BodyContainer">
	<h2 class="PageTitle">{$lang.Addon_Surveys_ResultsDefaultTitle}</h2>
	<div class="PageDescription">{$lang.Addon_Surveys_ResultsDefaultDescription}</div>
	
	{$flashMessage}
	<div id="no-forms-message"></div>
	{if $surveys}
		<form id="form-responses" action="index.php?Page=Addons&Addon=surveys&Action=result" method="post">
			<div class="Buttons">
				<button class="Field" type="submit">{$lang.Addon_Surveys_ResultsViewResults}</button>
				<button class="cancel Field" type="button">{$lang.Addon_Surveys_ResultsViewResponsesCancel}</button>
			</div>
			
			<h3 class="Heading2">{$lang.Addon_Surveys_ResultsDefaultForm}</h3>
			<fieldset class="inline">
				<ul>
					<li>
						<label>
							<span class="required">*</span>
							{$lang.Addon_Surveys_ResultsSurveySelect}
						</label>
						<div>
							<div class="table-selector-container" style="min-height: 100px;">
								<table class="table-selector" style="width: 350px;">
									<tbody>
										{foreach from=$surveys key=k item=form}
											<tr>
												<td><input {if $form.responseCount == 0}class="no-responses"{/if} type="radio" name="surveyId" value="{$form.id}" /></td>
												<td>{$form.name}</td>
												<td>
													{$form.responseCount}
													{if $form.responseCount == 1}
														{$lang.Addon_Surveys_responseSingular}
													{else}
														{$lang.Addon_Surveys_responsePlural}
													{/if}
												</td>
											</tr>
										{/foreach}
									</tbody>
								</table>
							</div>
						</div>
					</li>
					<li>
						<label></label>
						<div>
							<button class="Field" type="submit">{$lang.Addon_Surveys_ResultsViewResults}</button>
							<button class="cancel Field" type="button">{$lang.Addon_Surveys_exportResponsesCancel}</button>
						</div>
					</li>
				</ul>
		</form>
	{else}
		<div id="no-forms-message"></div>
		<script type="text/javascript">jQuery(function($) { $('#no-forms-message').infoMessage('{$lang.Addon_Surveys_exportResponsesNoForms|addslashes}'); });</script>
	{/if}
</div>