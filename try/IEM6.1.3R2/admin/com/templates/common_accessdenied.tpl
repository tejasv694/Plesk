<br />
<table border="0" cellspacing="0" cellpadding="0" width="95%" align="center">
	<tr>
		<td class="Message" width="20">
			<img src="images/error.gif" width="18" height="18" hspace="10" alt="error" />
		</td>

		<td class="Message" width="100%">{$ErrorMessage}</td>
	</tr>
	<tr>
		<td colspan="2">
			<br />
			<button id="btnAccessDeniedGoBack" class="FormButton" class="Button">
				{$lang.GoBack}
			</button>
		</td>
	</tr>
</table>
<br/>
<script>
	$('button#btnAccessDeniedGoBack').click(function() { history.go(-1); });
</script>