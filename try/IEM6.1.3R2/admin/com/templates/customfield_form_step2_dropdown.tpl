	<tr>
		<td width="200" class="FieldLabel">
			{template="Required"}
			%%LNG_Instructions%%:&nbsp;
		</td>
		<td>
			<input type="text" name="DefaultValue" id="DefaultValue" class="Field250" value="%%GLOBAL_DefaultValue%%">&nbsp;%%LNG_HLP_Instructions%%
		</td>
	</tr>
	<tr>
		<td width="200" class="FieldLabel">
			{template="Required"}
			%%LNG_CustomField_Values%%:
		</td>
		<td>
			<textarea type="text" name="MultiValues" id="MultiValues" class="Field250" style="width:450px; height:200px">%%GLOBAL_CurrentList%%</textarea>
			<div class="aside">%%LNG_CustomField_Dropdown_Hint%%</div>
			(<a href="#" onclick="SortMultiValues(); return false;">%%LNG_CustomFields_Sort_Alpha%%</a>)<br /><br />
		</td>
	</tr>
