{capture name=row_key trim=true}%%GLOBAL_Key%%{/capture}
<tr>
	<td width="200" class="FieldLabel">
		{template="Not_Required"}
		{if $row_key == ''}<b>{/if}{$lang.RadioButton} %%GLOBAL_KeyNumber%% {$lang.Value}:{if $row_key == ''}</b>{/if}&nbsp;
	</td>
	<td>
		<input type="text" name="Value[%%GLOBAL_KeyNumber%%]" class="Field250" value="%%GLOBAL_Value%%" id="value_%%GLOBAL_KeyNumber%%">&nbsp;{$lnghlp.Radiobutton_Value}
		<input type="hidden" name="Key[%%GLOBAL_KeyNumber%%]" value="%%GLOBAL_Key%%" id="key_%%GLOBAL_KeyNumber%%">
		{if $row_key != ''}<br />{$lang.Radiobutton_Key}:&nbsp;"%%GLOBAL_Key%%"&nbsp;{$lnghlp.Radiobutton_Key}{/if}
	</td>
</tr>