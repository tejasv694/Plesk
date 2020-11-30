<tr>
	<td colspan="2" class="EmptyRow">
		&nbsp;
	</td>
</tr>
<tr>
	<td colspan="2" class="Heading2">
		&nbsp;&nbsp;%%LNG_FormThanksEmailOptions%%
	</td>
</tr>
{template="Form_EmailOptions"}
<tr>
	<td class="FieldLabel" width="200">
		{template="Required"}
		%%LNG_ThanksSubject%%:&nbsp;
	</td>
	<td>
		<input type="text" class="Field250" name="thankssubject" value="%%GLOBAL_ThanksSubject%%">&nbsp;%%LNG_HLP_ThanksSubject%%
	</td>
</tr>
<tr>
	<td class="FieldLabel" width="200">
		{template="Not_Required"}
		%%LNG_ThanksHTMLVersion%%:&nbsp;
	</td>
	<td>
		%%GLOBAL_EditorHTML%%
	</td>
</tr>
<tr id="trGrab">
	<td>&nbsp;</td><td id="tdGrab"><input class="FormButton" type="button" name="" value="%%LNG_GetTextContent%%" style="width:200px" onClick="grabTextContent('TextContent','%%GLOBAL_HTMLEditorName%%');"></td>
</tr>
<tr>
	<td class="FieldLabel" width="200">
		{template="Not_Required"}
		%%LNG_ThanksTextVersion%%:&nbsp;
	</td>
	<td>
		%%GLOBAL_EditorText%%
	</td>
</tr>
