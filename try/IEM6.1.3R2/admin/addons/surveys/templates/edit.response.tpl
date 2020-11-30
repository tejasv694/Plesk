<link rel="stylesheet" type="text/css" href="addons/surveys/styles/view.response.css" />

<script src="includes/js/jquery/plugins/jquery.jFrame.js"></script>
<script src="includes/js/jquery/plugins/jquery.tableSelector.js"></script>

<script type="text/javascript">
	// form module jFrame instance
		new jFrame({
			controllerPath : 'addons/surveys/js/',
			cache          : false
		}, 'moduleForm');

	jFrame.getInstance('moduleForm').dispatch('edit.response', {
			formId         : {$form.id}, 
			responseNumber : {$responseNumber},
			responseId     : {$responseId}
		});

</script>

<div class="BodyContainer">
	<h2 class="PageTitle">{$lang.Addon_Surveys_editResponseTitle|sprintf,$responseNumber,$responseCount,$form.name}</h2>
	<div class="PageDescription">{$lang.Addon_Surveys_editResponseDescription}</div>
	
	{$flashMessage}
	
	<div class="Buttons FloatLeft">
		<button class="save-and-view Field">{$lang.Addon_Surveys_editResponseButtonSaveAndView}</button>
		<button class="save-and-view-next Field"{if $responseNumber == $responseCount} disabled="disabled"{/if}>{$lang.Addon_Surveys_editResponseButtonSaveAndViewNext}</button>
		<button class="save-and-keep-editing Field">{$lang.Addon_Surveys_editResponseButtonSaveAndKeepEditing}</button>
		<button class="delete Field">{$lang.Addon_Surveys_editResponseButtonDelete}</button>
		<button class="cancel Field">{$lang.Addon_Surveys_editResponseButtonCancel}</button>
	</div>

	{if $responseCount > 1}
		<div class="Buttons FloatRight">
			<button class="prev Field"{if $responseNumber == 1} disabled="disabled"{/if}>{$lang.Addon_Surveys_editResponseButtonPrevious}</button>
			<select class="Field" name="responseNumber" style="width: 133px;">
					{foreach from=$responseNumbers key=index item=num}
						<option value="{$num}"{if $num == $responseId} selected="selected"{/if}>{$lang.Addon_Surveys_editResponseResponseNumber|sprintf,$index}</option>
					{/foreach}
			</select>
			<button class="next Field"{if $responseNumber == $responseCount} disabled="disabled"{/if}>{$lang.Addon_Surveys_editResponseButtonNext}</button>
		</div>
	{/if}
	
	<div class="Clear"></div>
	
	
	
	<form id="edit-response-form" action="index.php?Page=Addons&Addon=surveys&Action=saveresponse&ajax=1" method="post" enctype="multipart/form-data">
		<input type="hidden" name="formId" value="{$form.id}" />
		<input type="hidden" name="responseId" value="{$responseId}" />
		<input type="hidden" name="responseNumber" value="{$responseNumber}" />
		
		{if $responseCount}
			<dl class="widgets">
				{foreach from=$widgets item=widget}
					{if $widget.type != 'section.break'}
						<dt>{$widget.number}. {$widget.name} {if $widget.is_required}<span class="required">*</span>{/if}</dt>
						
						{if $widget.errors}
							{foreach from=$widget.errors item=error}
								<dd class="required">{$error}</dd>
							{/foreach}
						{/if}
						
						{$widget.template}
					{/if}
				{/foreach}
			</dl>
		{else}
			<p>{$lang.Addon_Surveys_editResponseNoResponses|sprintf,$form.name}</p>
		{/if}
	</form>
	
	<div class="Buttons FloatLeft">
		<button class="save-and-view Field">{$lang.Addon_Surveys_editResponseButtonSaveAndView}</button>
		<button class="save-and-view-next Field"{if $responseNumber == $responseCount} disabled="disabled"{/if}>{$lang.Addon_Surveys_editResponseButtonSaveAndViewNext}</button>
		<button class="save-and-keep-editing Field">{$lang.Addon_Surveys_editResponseButtonSaveAndKeepEditing}</button>
		<button class="delete Field">{$lang.Addon_Surveys_editResponseButtonDelete}</button>
		<button class="cancel Field">{$lang.Addon_Surveys_editResponseButtonCancel}</button>
	</div>
	
	{if $responseCount > 1}
		<div class="Buttons FloatRight">
			<button class="prev Field"{if $responseNumber == 1} disabled="disabled"{/if}>{$lang.Addon_Surveys_editResponseButtonPrevious}</button>
			<select name="responseNumber Field" style="width: 133px;">
				{foreach from=$responseNumbers key=index item=num}
					<option value="{$num}"{if $num == $responseId} selected="selected"{/if}>{$lang.Addon_Surveys_editResponseResponseNumber|sprintf,$index}</option>
				{/foreach}
			</select>
			<button class="next Field"{if $responseNumber == $responseCount} disabled="disabled"{/if}>{$lang.Addon_Surveys_editResponseButtonNext}</button>
		</div>
	{/if}
	
	<div class="Clear"></div>
</div>