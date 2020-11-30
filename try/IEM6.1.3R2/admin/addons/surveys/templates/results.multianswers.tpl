<dt>
	{$question_number}. {$question}
	<p>{$question.description}</p>
</dt>	
<dd>
	<table class="survey_results">
		<tbody>
			<tr><th colspan="2"></th><th class="survey_stats">Percent</th><th class="survey_stats">Responses</th></tr>
		{foreach from=$stats key=answer item=stat}
			{if $maxstats == $stat && $maxstats != 0}
				<tr class="winner">
				<td width="15%" align="right">{$answer}</td>
				<td width="300"><div class="bar"><div class="survey_percentage" style="width: {$percentage.$answer}%;"></div></div></td>
				<td class="winner">{$percentage.$answer}%</td><td class="winner">{$stat}</td>
			
			{else}
				<tr>
				<td width="15%" align="right">{$answer}</td>
				<td width="300"><div class="bar"><div class="survey_percentage" style="width: {$percentage.$answer}%;"></div></div></td>
				<td class="survey_stats">{$percentage.$answer}%</td><td class="survey_stats">{$stat}</td>
			{/if}	
			</tr>
		{/foreach}
		</tbody>	
		{if $total_others > 0 }	
			{if $maxstats == $total_others && $maxstats != 0}
				<tr class="winner">				
			{else}
				<tr>
			{/if}
					<td align="right"><a class="others_hide" id="more_{$question_id}">Hide Answers</a> ({$other_label})</td><td width="300"><div class="bar"><div class="survey_percentage" style="width: {$percentage.others}%;"></div></div></td>
					<td class="survey_stats">{$percentage.others}%</td><td class="survey_stats">{$total_others}</td>
				</tr>
			<tbody id="othersmore_{$question_id}">
				{foreach from=$other_answers key=index item=other_answer}
					<tr>
						<td></td><td width="300">{$index}. {$other_answer.value}</td><td class="browse"><a href="index.php?Page=Addons&Addon=surveys&Action=viewresponses&surveyId={$surveyId}&responseId={$other_answer.id}">Browse...</a></td><td></td>
					</tr>
				{/foreach}
					<tr>
						<td></td><td align="right"> Previous  
						| 
						{if $totalresponse <= 10 }
								Next
						{else}
							<a href="#" onclick="showResponsesAnswer(10,{$question_id},{$surveyId},{$total_others}); return false;"> Next >></a>&nbsp;&nbsp;</td><td></td>
						{/if}
					</tr>
			</tbody>
		{/if}
	</table>
</dd>