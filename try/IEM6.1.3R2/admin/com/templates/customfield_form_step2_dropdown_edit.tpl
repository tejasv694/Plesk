	<script>
		function AddOption() {
			var CurrentSize = $('#customFieldsTable tr td input[type=hidden]').size() + 1;

			$(	'<tr>'
				+ '<td class="FieldLabel" width="200"> {template="Not_Required"} <b>{$lang.DropDown} ' + CurrentSize + ' {$lang.Value}:</b>&nbsp;</td>'
				+ '<td class="PickListValues">'
				+ '<input name="Key[' + CurrentSize + ']" class="Field250" value="" id="key_' + CurrentSize + '" type="hidden">'
				+ '<input name="Value[' + CurrentSize + ']" class="Field250" value="" id="value_' + CurrentSize + '" type="text">'
				+ '</td>'
				+ '</tr>').insertBefore($('#customFieldsTable #additionalOption'));
		}

		Application.Page.CustomFieldEdit.onFormSubmitFunction.populateDropdownValue = function() {
			var rows = $('#customFieldsTable tr td.PickListValues');
			for(var i = 0, j = rows.length; i < j; ++i) {
				var display = $('input[@type=text]', rows[i]);
				var value = $('input[type=hidden]', rows[i]);

				if ($.trim(display.val()) == '') {
					display.val(value.val());
				} else {
					if($.trim(value.val()) == '') value.val(display.val());
				}
			}

			return true;
		}
	</script>

	<tr>
		<td width="200" class="FieldLabel">
			{template="Required"}
			%%LNG_Instructions%%:&nbsp;
		</td>
		<td>
			<input type="text" name="DefaultValue" id="DefaultValue" class="Field250" value="%%GLOBAL_DefaultValue%%">&nbsp;%%LNG_HLP_Instructions%%
		</td>
	</tr>

	%%GLOBAL_CurrentList%%

	%%GLOBAL_ExtraList%%

	<tr id="additionalOption">
		<td>&nbsp;</td>
		<td><a href="javascript:AddOption()"><img src="images/plus.gif" border="0" style="margin-right: 5px"></a><a href="javascript:AddOption()">%%LNG_AddMore%%</a></td>
	</tr>
