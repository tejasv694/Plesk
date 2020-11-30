<script>
$(document).ready(function() {
	$('#toggleCheck').click(function() {
		if ($('#toggleCheck')[0].checked) {
			$('input[type="checkbox"]').attr('checked','checked');
		} else {
			$('input[type="checkbox"]').attr('checked','');
		}
	});
	
	$('a.deleteButton').click(function() {
		var con = confirm("{$lang.Addon_survey_Confirm_Delete_Multi}");
		if (!con) {
			return false;	
		}
	});

	$('#deleteButton').click(function() {
		var selected = 	$('input[name="survey_select[]"]').filter(function() { return this.checked; });

		if (selected.length < 1) {
			alert("{$lang.Addon_surveys_SurveyDeleted_PleaseSelect}");
			return false;
		}
	
		var con = confirm("{$lang.Addon_survey_Confirm_Delete_Multi}");
		if (con) {				
			document.manageSurvey.action = 'index.php?Page=Addons&Addon=surveys&Action=Delete'; 
			$(document.manageSurvey).submit();
		}
	});
	
	
});

</script>
<form action="{$AdminUrl}" name="manageSurvey" method="post">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">%%LNG_Addon_surveys_Manage%%</td>
	</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Addon_surveys_ManageIntro%%
				</p>
			</td>
		</tr>
	<tr>
		<td>
			{$FlashMessages}
		</td>
	</tr>
	<tr>
		<td class="body">
			<table width="100%" border="0">
				<tr>
					<td>
						<div style="padding-top:10px; padding-bottom:10px">
							{$Add_Button} 
							{$Delete_Button}
						</div>
						
					</td>
					<td align="right" valign="bottom">
						{$Paging}
					</td>
				</tr>
			</table>
			<table border=0 cellspacing="0" cellpadding="0" width=100% class="Text">
				<tr class="Heading3">
					<td width="5" nowrap align="center">
						<input type="checkbox" id="toggleCheck" class="UICheckboxToggleSelector" />
					</td>
					<td width="5">&nbsp;</td>
					<td width="20%" nowrap="nowrap">
						%%LNG_Addon_Surveys_Default_TableHead_Name%%&nbsp;<a href='{$AdminUrl}&SortBy=Name&Direction=Up'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='{$AdminUrl}&SortBy=Name&Direction=Down'><img src="images/sortdown.gif" border=0></a>
					</td>
					<td width="20%" nowrap="nowrap">
						%%LNG_Addon_Surveys_Default_TableHead_Created%%&nbsp;<a href='{$AdminUrl}&SortBy=Created&Direction=Up'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='{$AdminUrl}&SortBy=Created&Direction=Down'><img src="images/sortdown.gif" border=0></a>
					</td>
					<td width="20%" nowrap="nowrap">
						%%LNG_Addon_Surveys_Default_TableHead_Updated%%&nbsp;<a href='{$AdminUrl}&SortBy=Updated&Direction=Up'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='{$AdminUrl}&SortBy=Updated&Direction=Down'><img src="images/sortdown.gif" border=0></a>
					</td>
					<td width="20%" nowrap="nowrap">
						%%LNG_Addon_Surveys_Default_TableHead_Responses%%&nbsp;<a href='{$AdminUrl}&SortBy=ResponseCount&Direction=Up'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='{$AdminUrl}&SortBy=ResponseCount&Direction=Down'><img src="images/sortdown.gif" border=0></a>
					</td>
					<td width="150">
						%%LNG_Action%%
					</td>
				</tr>
				{$Items}
			</table>
			
		</td>
	</tr>
</table>
</form>