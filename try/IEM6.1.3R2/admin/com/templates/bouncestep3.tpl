{assign step="3"}
{template="bounce_navigation"}

<form method="post" action="">
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
							{if $email_count > 0}
							<input class="Field" type="button" id="FindBounces" value="{$lang.Bounce_Find_Bounces}">
								{$lang.OR}
							{else}
								{$lang.BounceAccountEmpty}
							{/if}
							<a href="index.php?Page=Bounce" onclick='return confirm("{$lang.Bounce_CancelPrompt}");'>{$lang.Cancel}</a>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<script>

$(function() {

	$('#FindBounces').click(function() {
		tb_show('', 'index.php?Page=Bounce&Action=ProcessDisplay&keepThis=true&TB_iframe=tue&height=320&width=450&modal=true', '');
	});

});

</script>
