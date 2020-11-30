	<div class="question" id="question_{$questionid}">
		<input type="hidden" name="question[{$questionid}][new]" value="{$new}" class="questionNew">
		<input type="hidden" name="question[{$questionid}][page]" value="" class="question_page">
		<table cellspacing="0" cellpadding="0" class="Text body">
			<tr>
				<td class="label">{$TitleLabel}:</td>
				<td style="">
					<span class="question_row_width">
						<input type="text" name="question[{$questionid}][title]" id="question_{$questionid}_title" value="{$questionTitle}" class="Field question_title" style="width: 100%;margin-top: 2px;">
					</span>
				</td>
			</tr>
			
			<tr>
				<td class="label">%%LNG_Addon_surveys_Question_Type%%:</td>
				<td>
					<span class="question_row_width">
						<select name="question[{$questionid}][type]" id="question_{$questionid}_type" class="question_type">
							{$QuestionTypes}
						</select>
					</span>
					
					<!-- Custom Help so we can make it show higher -->
					&nbsp;&nbsp;
					<img onMouseOut="surveyHideHelp(this.parentNode);" onMouseOver="surveyShowHelp(this.parentNode, '%%LNG_Addon_surveys_Question_Type%%', '%%LNG_Addon_surveys_Question_Type_Help%%');" src="images/help.gif" width="24" height="16" border="0" class="survey_customhelp">
					<div style="display:none; top: 50px;" class="survey_customhelp_content"></div>
					<label for="question_{$questionid}_required">
						<input type="checkbox" name="question[{$questionid}][required]" id="question_{$questionid}_required" class="Field" {$questionRequired}>%%LNG_Addon_surveys_Required%%
					</label>
				</td>
			</tr>
			
			<!-- Multiple choices -->
			<tr id="question_{$questionid}_choices_row" style="{$choicesRowDisplay}">
				<td class="label">%%LNG_Addon_surveys_Choices%%:</td>
				<td>
					<span class="question_row_width">
						<input type="text" name="question[{$questionid}][choices]" class="Field question_choices" id="question_{$questionid}_choices" value="{$questionChoices}" >
					</span>
					<label for="question_{$questionid}_multiplechoices">
						&nbsp;&nbsp;<span style="width: 24px;overflow:hidden;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="checkbox" name="question[{$questionid}][multiplechoices]" id="question_{$questionid}_multiplechoices" {$questionMultiplechoices}>
						%%LNG_Addon_surveys_Can_Pick_Multiple%%
					</label>
				</td>
			</tr>
			
			<!-- Number Range -->
			<tr id="question_{$questionid}_range_row" style="{$rangeRowDisplay}">
				<td class="label">%%LNG_Range%%:</td>
				<td>
					<span class="question_row_width">
						<input type="text" name="question[{$questionid}][range]" class="Field question_range" id="question_{$questionid}_range" value="{$questionRange}" {$questionRangeDisabled}>
					</span>
					<label for="question_{$questionid}_rangeused">
						&nbsp;&nbsp;<span style="width: 24px;overflow:hidden;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="checkbox" name="question[{$questionid}][rangeused]" id="question_{$questionid}_rangeused" class="question_rangeused" {$questionRangeUsed}>
						%%LNG_Addon_surveys_RestrictRange%%
					</label>
				</td>
			</tr>
			
			<!-- File Types -->
			<tr id="question_{$questionid}_filetypes_row" style="{$filetypesRowDisplay}">
				<td class="label">%%LNG_Addon_surveys_FileTypes%%:</td>
				<td>
					<span class="question_row_width">
						<input type="text" name="question[{$questionid}][filetypes]" class="Field question_filetypes" id="question_{$questionid}_filetypes" value="{$questionFiletypes}" {$questionFiletypesDisabled}>
					</span>
					<label for="question_{$questionid}_filetypesused">
						&nbsp;&nbsp;&nbsp;%%LNG_HLP_Addon_surveys_FileTypes%%
						<input type="checkbox" name="question[{$questionid}][filetypesused]" id="question_{$questionid}_filetypesused" class="question_filetypesused" {$questionFiletypesUsed}>
						%%LNG_Addon_surveys_RestrictFileTypes%%
					</label>
				</td>
			</tr>
			
			<!-- Default Country Selection -->
			<tr id="question_{$questionid}_country_row" style="{$countryRowDisplay}">
				<td class="label">%%LNG_Addon_surveys_DefaultCountry%%:</td>
				<td>
					<span class="question_row_width">
						<select name="question[{$questionid}][country]" class="Field question_country" id="question_{$questionid}_country">{$countryOptions}</select>
					</span>
				</td>
			</tr>
			
			<!-- Rating Choices -->
			<tr id="question_{$questionid}_rating_choices_row" style="{$ratingsRowDisplay}">
				<td class="label" valign="top">%%LNG_Addon_surveys_Choices%%:</td>
				<td>
					<span class="question_row_width">
						<textarea name="question[{$questionid}][rating_choices]" id="question___questionid___rating_choices" class="question_rating_choices" style="width: 100%;height: 75px;">{$questionRatingChoices}</textarea>
					</span>
					&nbsp;&nbsp;&nbsp;%%LNG_HLP_Addon_surveys_Choices%%
				</td>
			</tr>
			
			<!-- Rating Scale -->
			<tr id="question_{$questionid}_ratings_row" style="{$ratingsRowDisplay}">
				<td class="label" valign="top">%%LNG_Addon_surveys_Scale%%:</td>
				<td>
					<span class="question_row_width">
						<select onchange="surveyUpdateRatingsScale(this);" name="question[{$questionid}][ratings_number]" style="width: 100%;">
							{$ratingsNumber}
						</select>
						<br>
						<ul class="ratings_list">
							{$ratingsList}
						</ul>
					</span>
				</td>
			</tr>
			
			<tr>
				<td class="label"></td>
				<td>
					<span class="question_remove">
						<a href="#" onclick="surveyRemoveQuestion(getQuestionID(this)); return false" id="question_{$questionid}_remove" class="">%%LNG_Addon_surveys_Remove%%</a>
						&nbsp;
					</span>
					<a href="#" onclick="surveyAddQuestion(getQuestionID(this)); return false" id="question_{$questionid}_add" class="question_add">%%LNG_Addon_surveys_AddAnotherQuestion%%</a>
					&nbsp;
					<a href="#" onclick="surveyAddPageBreak(getQuestionID(this)); return false" id="question_{$questionid}_add_pagebreak" class="question_add_pagebreak">%%LNG_Addon_surveys_Add_PageBreak%%</a>
				</td>
		</table>
	</div>