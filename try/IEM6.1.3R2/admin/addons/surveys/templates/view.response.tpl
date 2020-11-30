<link rel="stylesheet" type="text/css" href="addons/surveys/styles/view.response.css" />
<script src="includes/js/jquery/plugins/jquery.jFrame.js"></script>
<script src="includes/js/jquery/plugins/jquery.tableSelector.js"></script>

<script type="text/javascript">
	// form module jFrame instance
		new jFrame({
			controllerPath : 'addons/surveys/js/',
			cache          : false
		}, 'moduleForm');

	jFrame.getInstance('moduleForm').dispatch('view.response', {
			surveyId       : {$form.id}, 
			responseNumber : {$responseNumber},
			responseId     : {$responseId}
		});
		
</script>
<div class="BodyContainer">
	{if $responseCount}
		<h2 class="PageTitle">{$lang.Addon_Surveys_viewResponseTitle|sprintf,$responseNumber,$responseCount,$form.name}</h2>
	{else}
		<h2 class="PageTitle">{$lang.Addon_Surveys_viewResponseTitleNoResponses|sprintf,$form.name}</h2>
	{/if}
	<div class="PageDescription">{$lang.Addon_Surveys_viewResponseDescription}</div>
	
	<div id="MainMessage">{$FlashMessages}</div>
	
	{if $responseCount}
		<div class="Buttons FloatLeft">
			<button class="edit-response Field">{$lang.Addon_Surveys_viewResponseButtonEdit}</button>
			<button class="delete Field">{$lang.Addon_Surveys_viewResponseButtonDelete}</button>
		</div>
		
		{if $responseCount > 1}
			<div class="Buttons FloatRight">
				<button class="prev"{if $responseNumber == 1} disabled="disabled"{/if}>{$lang.Addon_Surveys_viewResponseButtonPrevious}</button>
				<select name="responseNumber" style="width: 133px;">
					{foreach from=$responseNumbers key=index item=num}
						<option value="{$num}"{if $num == $responseId} selected="selected"{/if}>{$lang.Addon_Surveys_editResponseResponseNumber|sprintf,$index}</option>
					{/foreach}
				</select>
				<button class="next"{if $responseNumber == $responseCount} disabled="disabled"{/if}>{$lang.Addon_Surveys_viewResponseButtonNext}</button>
			</div>
		{/if}
		
		<div class="Clear"></div>
		
		<dl class="widgets">
			{foreach from=$widgets item=widget}
				{if $widget.type != 'section.break'}
					<dt>{$widget.number}. {$widget.name}</dt>
					{if $widget.values}
						{foreach from=$widget.values item=value}
							{if $value.value}
								<dd{if $value.isLast} class="last"{/if}>
									{if $widget.type == 'file'}
										<a href="index.php?Page=Addons&Addon=surveys&Action=downloadAttach&formId={$form.id}&responseId={$responseId}&value={$value.file_encode}&ajax=1">{$value.value}</a>
									{elseif $widget.type == 'textarea'}
										{$value.value|nl2br}
									{else}
										{$value.value}
									{/if}
								</dd>
							{else}
								<dd{if $value.isLast} class="last"{/if}>{$lang.Addon_Surveys_viewResponseNotProvided}</dd>
							{/if}
						{/foreach}
					{else}
						<dd class="last">{$lang.Addon_Surveys_viewResponseNotProvided}</dd>
					{/if}
				{/if}
			{/foreach}
		</dl>
		
		<div class="Buttons FloatLeft">
			<button class="edit-response Field">{$lang.Addon_Surveys_viewResponseButtonEdit}</button>
			<button class="delete Field">{$lang.Addon_Surveys_viewResponseButtonDelete}</button>
			<button class="cancel Field">{$lang.Addon_Surveys_viewResponseButtonCancel}</button>
		</div>
		
		{if $responseCount > 1}
			<div class="Buttons FloatRight">
				<button class="prev"{if $responseNumber == 1} disabled="disabled"{/if}>{$lang.Addon_Surveys_viewResponseButtonPrevious}</button>
				<select name="responseNumber" style="width: 133px;">
					{foreach from=$responseNumbers key=index item=num}
						<option value="{$num}"{if $num == $responseId} selected="selected"{/if}>{$lang.Addon_Surveys_editResponseResponseNumber|sprintf,$index}</option>
					{/foreach}
				</select>
				<button class="next"{if $responseNumber == $responseCount} disabled="disabled"{/if}>{$lang.Addon_Surveys_viewResponseButtonNext}</button>
			</div>
		{/if}
		
		<div class="Clear"></div>
	{/if}
</div>