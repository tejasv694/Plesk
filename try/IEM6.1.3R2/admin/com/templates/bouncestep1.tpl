{assign step="1"}
{template="bounce_navigation"}

<form method="post" action="index.php?Page=Bounce&Action=BounceStep2" id="BounceListChooseForm">
	<table cellspacing="0" cellpadding="0" width="100%" align="center" >
		<tr>
			<td class="Heading1">
				{$lang.Bounce_Step1}
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					{$lang.Bounce_Step1_Intro}
				</p>
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
					<tr valign="top" style="background-color:#F9F9F9;">
						<td style="width:100%;background-color:#FFFFFF;padding-right:15px;border-right: 1px #EAEAEA solid;">
							<table border="0" cellspacing="0" cellpadding="0" width="100%" class="Panel">
								<tr>
									<td colspan="2" class="Heading2">
										&nbsp;&nbsp;{$lang.SelectAContactList}
									</td>
								</tr>
								<tr>
									<td width="200" class="FieldLabel">
										{template="Required"}
										{$lang.SelectBounceEmail}:&nbsp;
									</td>
									<td style="padding-top:5px;">
										<div class="ISSelect" style="width:300px;">
											<ul>
												{assign multiples="0"}
												{foreach from=$bounce_server_map key=key item=server}
													<li onmouseover="$(this).addClass('ISSelectOptionHover');" onmouseout="$(this).removeClass('ISSelectOptionHover');" style="cursor:pointer;">
														<label for="server_{$server.0.id}">
															<input name="list" value="{$server.0.id}" id="server_{$server.0.id}" type="radio" onclick="$('.SelectedRow').removeClass('SelectedRow'); $(this).parents('li').addClass('SelectedRow');" style="vertical-align:top;" />
															{if substr($key, 0, 1) == '~'}
																{$server.0.name}
															{else}
																{if count($server) > 1}
																	{assign multiples="1"}
																{/if}
																<span class="Bounce_ISSelector_Title">
																	{$server.0.username}@{$server.0.server}
																</span>
																<span class="Bounce_ISSelector_Description">
																	{foreach from=$server item=list id=sequence}
																		{$list.name}<br />
																	{/foreach}
																</span>
															{/if}
														</label>
													</li>
												{/foreach}
											</ul>
										</div>
										&nbsp;{$lnghlp.SelectBounceEmail}
									</td>
								</tr>
								<tr>
									<td>
										&nbsp;
									</td>
									<td>
										{if $multiples}<a href="#" class="whylistgrouped">{$lang.WhyListsGrouped}</a>{/if}
									</td>
								</tr>
								<tr>
									<td>
										&nbsp;
									</td>
									<td>
										<input class="FormButton" type="submit" value="{$lang.Next}">
										{$lang.OR}
										<a href="index.php" onclick='return confirm("{$lang.Bounce_CancelPrompt}");'>{$lang.Cancel}</a>
										<br /><br />
									</td>
								</tr>
							</table>
						</td>
						<td style="padding:0px 4px 0px 15px;"  bgcolor="#FFFFFF">
							{template="bounce_help"}
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<script>

	$('#BounceListChooseForm').submit(function() {
		var listid = $("input[name='list']:checked").val();
		if (!listid) {
			alert('{$lang.Bounce_PleaseChooseList}');
			return false;
		}
	});

	$('.whylistgrouped').click(function() {
		var url = 'index.php?Page=Bounce&Action=Help&Topic=list_group&keepThis=true&TB_iframe=true&height=200&width=400&random=' + new Date().getTime();
		tb_show('{$lang.BounceProcessHelp}', url, '');
	});

</script>
