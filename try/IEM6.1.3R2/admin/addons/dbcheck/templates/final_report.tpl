<table width="100%">
	<tr>
		<td class="Heading1">
			{if $repaired}
				{$lang.Addon_dbcheck_Heading_Repaired}
			{else}
				{$lang.Addon_dbcheck_Heading_Checked}
			{/if}
		</td>
	</tr>
	<tr>
		<td>
			{$flash_messages}
		</td>
	</tr>
	<tr>
		<td>
			{if $num_problems > 0 && !$repaired}
				<input type="button" class="SmallButton RunFix" value="{$lang.Addon_dbcheck_Button_FixProblems}" />
				<input type="button" class="SmallButton ErrorReport" value="{$lang.Addon_dbcheck_DisplayReport}" />
			{/if}
			<input type="button" class="SmallButton" value="{$lang.Addon_dbcheck_Button_Continue}" onclick="window.location.href='index.php';" />
			{if $num_problems > 0 && !$repaired}
				{* Report on the problem tables *}
				<ul>
					{foreach from=$problems item=problem key=type}
						{if $problem.num > 0}
							<li>
								{$problem.text}
								{if $repaired}
									<a href="#" class="ErrorReport">{$lang.Addon_dbcheck_DisplayError}</a>
								{/if}
							</li>
						{/if}
					{/foreach}
				</ul>
			{/if}
		</td>
	</tr>
</table>

<script>
	$('.RunFix').click(function() {
		alert("{$lang.Addon_dbcheck_Advice}");
		var url = '{$admin_url}&AJAX=1&Action=ShowPopup&Fix=true&keepThis=true&TB_iframe=true&height=240&width=400&modal=true&random=' + new Date().getTime();
		tb_show('', url, '');
	});

	$('.ErrorReport').click(function() {
		var url = '{$admin_url}&AJAX=1&Action=ShowReport&keepThis=true&TB_iframe=true&height=400&width=500';
		tb_show('{$lang.Addon_dbcheck_DisplayReport}', url, '');
	});
</script>
