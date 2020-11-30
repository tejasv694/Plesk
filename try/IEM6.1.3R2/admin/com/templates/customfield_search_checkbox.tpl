	<tr valign="top">
		<td class="FieldLabel">
			{template="Not_Required"}%%GLOBAL_FieldName%%:&nbsp;
		</td>
		<td>
			<script>
				function showAndFilter(fld, spanid)
				{
					// 0 is 'none'
					// 1 is 'any'
					// 2 is 'all'
					// if it's none or all, show "and"
					if (fld.selectedIndex == 0 || fld.selectedIndex == 2) {
						$('.' + spanid + '_or').css('display','none');
						$('.' + spanid + '_and').css('display','');
						return;
					}

					// if it's any, show the "or"
					$('.' + spanid + '_or').css('display','');
					$('.' + spanid + '_and').css('display','none');
				}
			</script>
			<table border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td rowspan="%%GLOBAL_RowSpan%%" valign="top">
						%%GLOBAL_Search_OptionList%%
					</td>
					<td>
						%%GLOBAL_OptionList%%&nbsp;%%LNG_HLP_Filter_Checkbox%%
					</td>
				</tr>
			</table>
		</td>
	</tr>
