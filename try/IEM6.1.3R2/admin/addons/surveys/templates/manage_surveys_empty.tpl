<form action="{$AdminUrl}&Action=Create" method="post">
<input type="hidden" name="Page" value="Surveys">
<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">%%LNG_Addon_surveys_Manage%%</td>
	</tr>
	<tr>
		<td class="body pageinfo"><p>%%LNG_Addon_surveys_ManageIntro%%</p></td>
	</tr>
	<tr>
		<td class="body">
			<div style='background-color:#FFF1A8; padding:5px 5px 8px 10px; margin-bottom:10px'>
				<img src='images/success.gif' width='18' height='18' align='left' style='padding-right:4px; margin-top:-2px' />%%LNG_Addon_surveys_NoSurveys%%
			</div>
		</td>
	</tr>
	<tr><td class="body">{$Add_Button}</td></tr>
</table>
</form>