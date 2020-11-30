{if $bounce_count > 0 || $delete_count > 0}
	{assign step="4"}
	{template="bounce_navigation"}
{/if}

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
		{if $bounce_count > 0 || $delete_count > 0}
			<tr style="background-color:#F9F9F9;">
				<td style="border: 1px #EAEAEA solid; padding:20px;">
					<div>
						{if $bounce_count > 0}
							<label for="inactive_hbounce">
								<span class="Bounce_Process_Option_Recommended">
									<input type="checkbox" name="inactive_hbounce" id="inactive_hbounce" checked="checked" />
									{$lang.Bounce_Flag_Hard_Bounces_Inactive}
								</span>
								<span class="Bounce_Process_Option_Intro">
									{$lang.Bounce_Flag_Hard_Bounces_Inactive_Intro}
								</span>
							</label>
							<label for="delete_hbounce">
								<span class="Bounce_Process_Option">
									<input type="checkbox" name="delete_hbounce" id="delete_hbounce" />
									{$lang.Bounce_Delete_Hard_Bounces}
								</span>
								<span class="Bounce_Process_Option_Intro">
									{$lang.Bounce_Delete_Hard_Bounces_Intro}
								</span>
							</label>
							<label for="inactive_sbounce">
								<span class="Bounce_Process_Option">
									<input type="checkbox" name="inactive_sbounce" id="inactive_sbounce" />
									{$lang.Bounce_Flag_Soft_Bounces_Inactive}
								</span>
								<span class="Bounce_Process_Option_Intro">
									{$lang.Bounce_Flag_Soft_Bounces_Inactive_Intro}
								</span>
							</label>
						{elseif $delete_count > 0}
							{$report_summary}
						{/if}
					</div>
					<div>
						<input class="Field" type="button" value="{if $bounce_count > 0}{$lang.Bounce_Process_Now}{else}{$lang.Bounce_Delete_Now}{/if}" id="ProcessBounces">{$lang.OR}
						<a href="index.php?Page=Bounce" onclick='return confirm("{$lang.Bounce_CancelPrompt}");'>{$lang.Cancel}</a>
					</div>
				</td>
			</tr>
		{else}
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
		{/if}
	</table>
</form>

<script>

$(function() {

	$('#ProcessBounces').click(function() {
		{if $bounce_count > 0}
			var option_chosen = $('#inactive_hbounce').attr('checked') || $('#delete_hbounce').attr('checked') || $('#inactive_sbounce').attr('checked');
			if (!option_chosen) {
				alert('{$lang.Bounce_PleaseChooseOption}');
				return false;
			}
		{/if}
		tb_show('', 'index.php?Page=Bounce&Action=ProcessDisplay' + getBounceOpts() + '&keepThis=true&TB_iframe=tue&height=320&width=450&modal=true', '');
	});

});

function getBounceOpts()
{
	var params = '';
	var boxes = ['inactive_hbounce', 'delete_hbounce', 'inactive_sbounce'];
	$(boxes).each(function(i, e) {
		var result = ($('#' + e).attr('checked')) ? 1 : 0;
		params += '&' + e + '=' + result;
	});
	return params;
}

$(function() {
	$('#ProcessAnother').click(function() {
		window.location.href = 'index.php?Page=Bounce';
	});
});

</script>
