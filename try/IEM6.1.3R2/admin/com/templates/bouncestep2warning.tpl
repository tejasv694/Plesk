{assign step="2"}
{template="bounce_navigation"}

<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">
			{$lang.Bounce_Step2}
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
						<input class="Field150" type="button" value="{$lang.Bounce_Review_Settings}" onclick="window.location.href='index.php?Page=Bounce&Action=BounceStep2';">
									{$lang.OR}
						<a href="index.php?Page=Bounce" onclick='return confirm("{$lang.Bounce_CancelPrompt}");'>{$lang.Cancel}</a>
					</td>
				</tr>
				<tr valign="top">
					<td>
						<div class="Heading1" style="color:#676767; padding:15px 0px 10px 0px;">
							{$problem_name}
						</div>
						{if $problem_type == 'unknown'}
							{$lang.Bounce_Help_PossibleSolutions_Unknown}
						{else}
							{$lang.Bounce_Help_PossibleSolutions}
						{/if}
					</td>
				</tr>
				<tr valign="top">
					<td>
						<ul>
							{foreach from=$problem_advice key=title item=article}
								<li><a href="#" onclick="LaunchHelp({$article})">{$title}</a></li>
							{/foreach}
						</ul>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<!--

Error Report
============

{$error_report}

-->
