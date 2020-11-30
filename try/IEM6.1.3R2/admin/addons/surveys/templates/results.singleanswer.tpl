<dt>
	{$question_number}. {$question}
	<p>{$question.description}</p>
</dt>	
<dd>
	<table class="survey_results">
		<tr><th colspan="2"></th><th class="survey_stats">Percent</th><th class="survey_stats">Responses</th></tr>		
		<tr><td width="15%" align="right"><a class="others_hide" id="more_{$question_id}">Hide Answers</a> (Various)</td> <td colspan="" width="300"></td> <td class="survey_stats" width="50">{$percentage}%</td> <td class="survey_stats" width="50">{$totalresponse}</td></tr>
		
		<tbody id="othersmore_{$question_id}">
				{foreach from=$other_answers key=index item=other_answer}
					<tr>
						<td width="15%"></td><td width="300" text-align="right">{$index}. {$other_answer.value}</td><td class="browse"><a href="index.php?Page=Addons&Addon=surveys&Action=viewresponses&surveyId={$surveyId}&responseId={$other_answer.id}">Browse...</a></td><td></td>
					</tr>
				{/foreach}
					<td></td>
					<td align="right"> 
					Previous  
					| 
					{if $totalresponse <= 10 }
							Next
					{else}
						<a href="#" onclick="showResponsesAnswer(10,{$question_id},{$surveyId},{$totalresponse}); return false;"> Next >></a>			
					{/if}
					&nbsp;&nbsp;</td>
					<td></td>
		</tbody>

	</table>
</dd>