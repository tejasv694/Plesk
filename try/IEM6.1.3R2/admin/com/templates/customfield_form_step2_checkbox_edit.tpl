%%GLOBAL_CurrentList%%

%%GLOBAL_ExtraList%%

<script>
	function AddOption() {
		var CurrentSize = $('#customFieldsTable tr td input[type=hidden]').size() + 1;

		$(	'<tr>'
			+ '<td class="FieldLabel" width="200"> {template="Not_Required"} <b>{$lang.Checkbox} ' + CurrentSize + ' {$lang.Value}:</b>&nbsp;</td>'
			+ '<td>'
			+ '<input type="text" name="Value[' + CurrentSize + ']" class="Field250" id="value_' + CurrentSize + '" />'
			+ '<input type="hidden" name="Key[' + CurrentSize + ']" id="key_' + CurrentSize + '" />&nbsp;'
			+ '</td>'
			+ '</tr>').insertBefore('table#customFieldsTable tr#additionalOption');
	}

</script>

<tr id="additionalOption">
	<td>&nbsp;</td>
	<td><a href="javascript:AddOption()"><img src="images/plus.gif" border="0" style="margin-right: 5px"></a><a href="javascript:AddOption()">%%LNG_AddMore%%</a></td>
</tr>
