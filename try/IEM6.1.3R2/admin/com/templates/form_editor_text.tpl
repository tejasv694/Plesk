<table border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td>
			<table border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td valign="top"><textarea name="TextContent" id="TextContent" rows="10" cols="48" style="width: 500px" wrap="virtual">%%GLOBAL_TextContent%%</textarea></td><td valign="top">&nbsp;&nbsp;%%GLOBAL_TextHelpTip%%</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr style="display: %%GLOBAL_ShowCustomFields%%">
		<td>
			<input type="button" onclick="javascript: ShowCustomFields('TextContent'); return false;" class="SmallButton" style="width:140px" value="%%LNG_InsertACustomField%%" />
			<input type="button" onclick="javascript: InsertUnsubscribeLink('TextContent'); return false;" class="SmallButton" style="width:140px" value="%%LNG_InsertUnsubscribeLink%%" />
			<span id="spanGrab"><!-- Populated with the "Get Text Content from Editor" button if it exists --></span>
		</td>
	</tr>
</table>

<script>

	// Move the "Get Text Content from editor" button next to the "Insert unsubscribe button" if it exists
	if(document.getElementById('trGrab') != null) {
		var grab_html = $('#tdGrab').html();
		$('#spanGrab').html(grab_html);
		$('#trGrab').hide();
	}

</script>