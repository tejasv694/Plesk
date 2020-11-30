	<tr>
		<td colspan="2">
			<table width="100%" cellspacing="0" cellpadding="0" align="center" class="message_box">
				<tbody><tr>
					<td class="Message">
						<img width="20" height="16" align="left" src="images/infoballon.gif"/>
						%%LNG_CustomField_Checkbox_Help%%
					</td>
				</tr>
			</tbody></table>
		</td>
	</tr>
	<tr>
		<td width="200" class="FieldLabel">
			{template="Required"}
			%%LNG_CustomField_Values%%:&nbsp;
		</td>
		<td>
			<textarea type="text" name="MultiValues" id="MultiValues" class="Field250" style="width:450px; height:200px">%%GLOBAL_CurrentList%%</textarea>
			<div class="aside">%%LNG_CustomField_Checkbox_Hint%%</div>
			(<a href="#" onclick="SortMultiValues(); return false;">%%LNG_CustomFields_Sort_Alpha%%</a>)<br /><br />
		</td>
	</tr>
