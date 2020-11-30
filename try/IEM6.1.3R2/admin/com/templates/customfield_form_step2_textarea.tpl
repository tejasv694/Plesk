<tr>
	<td width="200" class="FieldLabel">
		{template="Not_Required"}
		%%LNG_DefaultValue%%:&nbsp;
	</td>
	<td>
		<textarea style="font-size: 11px; width: 350px; height:100px; font-family: Tahoma;" name="DefaultValue">%%GLOBAL_DefaultValue%%</textarea>&nbsp;%%LNG_HLP_DefaultValue%%
	</td>
</tr>
<tr>
        <td class="FieldLabel">
                {template="Not_Required"}
                %%LNG_ApplyDefault%%&nbsp;
        </td>
        <td>
                <label for="ApplyDefault"><input type="checkbox" id="ApplyDefault" name="ApplyDefault" %%GLOBAL_ApplyDefault%%>%%LNG_ApplyDefaultToExistingExplain%%</label>
        </td>
</tr>
<tr>
	<td width="200" class="FieldLabel">
		{template="Not_Required"}
		%%LNG_TextAreaRows%%:&nbsp;
	</td>
	<td>
		<input type="text" name="Rows" class="Field50" value="%%GLOBAL_Rows%%">&nbsp;%%LNG_HLP_TextAreaRows%%
	</td>
</tr>
<tr>
	<td width="200" class="FieldLabel">
		{template="Not_Required"}
		%%LNG_TextAreaColumns%%:&nbsp;
	</td>
	<td>
		<input type="text" name="Columns" class="Field50" value="%%GLOBAL_Columns%%">&nbsp;%%LNG_HLP_TextAreaColumns%%
	</td>
</tr>
