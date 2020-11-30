
	{foreach from=$other_answers key=index item=other_answer}
	<tr>
		<td></td>
		<td width="300">{$index}. {$other_answer.value}</td>
		<td class="browse"> <a href="index.php?Page=Addons&Addon=surveys&Action=viewresponses&surveyId={$surveyId}&responseId={$other_answer.id}">Browse...</a></td><td></td>
	</tr>
	{/foreach}
	
	<tr>
		<td></td>
		<td align="right"> 
		{if $prevpage !== false}
			<a href="#" onclick="showResponsesAnswer({$prevpage},{$question_id},{$surveyId},{$total_others}); return false;"><< Previous</a>  
		{else}
			Previous
		{/if}
		| 
		{if $nextpage !== false}
			<a href="#" onclick="showResponsesAnswer({$nextpage},{$question_id},{$surveyId},{$total_others}); return false;">Next >></a>
		{else}
			Next
		{/if}
		&nbsp;&nbsp;</td>
		<td></td>
	</tr>
