{assign step="5"}
{template="bounce_navigation"}

<form>
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				{$heading}
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
			</td>
		</tr>
		<tr>
			<td>
				{$message}
			</td>
		</tr>
		<tr>
			<td>
				<table cellpadding="0" cellspacing="0">
					<tr valign="top">
						<td>
							<input class="Field" type="button" value="{$lang.Bounce_Process_Once_More}" id="ProcessAnother">{$lang.OR}
							<a href="index.php">{$lang.Bounce_Process_Finished}</a>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<script>

$(function() {
	$('#ProcessAnother').click(function() {
		window.location.href = 'index.php?Page=Bounce';
	});
});

</script>
