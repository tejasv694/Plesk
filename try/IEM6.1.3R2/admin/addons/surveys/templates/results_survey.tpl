<link rel="stylesheet" type="text/css" href="addons/surveys/styles/tableselector.css" />
<link rel="stylesheet" type="text/css" href="addons/surveys/styles/view.response.css" />
<link rel="stylesheet" type="text/css" href="addons/surveys/styles/survey_result.css" />

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
	{if $responseCount}
		<h2 class="PageTitle">{$lang.Addon_Surveys_resultsResponseTitle|sprintf,$survey_name}</h2>
	{else}
		<h2 class="PageTitle">{$lang.Addon_Surveys_resultsResponseTitleNoResponses|sprintf,$form.name}</h2>
	{/if}
	<div class="PageDescription">{$lang.Addon_Surveys_resultsResponseDescription}</div>
	<form id="form-responses" action="index.php?Page=Addons&Addon=surveys&Action=export&ajax=1" method="post">
		<div class="Buttons">
			<input type="hidden" id="survey_id" value={$survey_id}" />
			<button class="export Field" type="button">{$lang.Addon_Surveys_Results_exportResponses}</button>
			<button class="browse Field" type="button">{$lang.Addon_Surveys_Results_browseResponse}</button>
		</div>
	</form>
	{if $responseCount}
	<dl class="widgets">	
		{foreach from=$survey_results item=each_result}
			{$each_result}
		{/foreach}
	</dl>
	{/if}		
</div>	