{assign step="2"}
{template="bounce_navigation"}

<script src="includes/js/jquery/form.js"></script>
<script>

	$(function() {

		// Make sure this is hidden if coming back from a future step.
		if (!$('#autobounceoption').attr('checked')) {
			showManual();
		} else {
			showAuto();
		}

		$('#autobounceoption').click(function() {
			showAuto();
		});

		$('#bounce_process').click(function() {
			showManual();
		});

		// Set up help popups.
		var topics = ['auto_explain', 'manual_explain'];
		$(topics).each(function(i, e) {
			$('#' + e).click(function(event) {
				showHelp(e);
				// Don't actually change the option when they click the 'why?' link.
				event.stopPropagation();
				return false;
			});
		});

	});

	function showAuto()
	{
		$('#BounceButton').val('{$lang.Bounce_Auto_Button}').unbind();
		$('#BounceButton').click(function() {
		{if $system_access}
			window.location.href = 'index.php?Page=Bounce';
		{else}
			window.location.href = 'index.php?Page=Bounce&Action=BounceStep3 ';
		{/if} 
		})
		$('.YesProcessBounce').hide();
		$('#auto_settings').show();
	}

	function showManual()
	{
		$('#BounceButton').val('{$lang.Bounce_Test_Conn_Cont}').unbind();
		$('#BounceButton').click(function() {
			if (Application.Page.BounceInfo.validateFields()) {
				TestBounceDetails();
			}
		})
		$('#auto_settings').hide();
		$('.YesProcessBounce').show();
	}

	function TestBounceDetails()
	{
		var x = 'index.php?Page=Bounce&Action=PopupBounceTest' + Application.Page.BounceInfo.getBounceParameters() + '&keepThis=true&TB_iframe=true&height=240&width=400&modal=true&random=' + new Date().getTime();
		tb_show('', x, '');
	}

	function showHelp(topic)
	{
		var url = 'index.php?Page=Bounce&Action=Help&Topic=' + topic + '&keepThis=true&TB_iframe=true&height=200&width=400&random=' + new Date().getTime();
		tb_show('{$lang.BounceProcessHelp}', url, '');
	}

</script>

<form method="post" action="index.php?Page=Bounce&Action=BounceStep3">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
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
										{$lang.BounceIWouldLikeTo}:&nbsp;
									</td>
									<td>
										<label for="autobounceoption">
											<input type="radio" name="bounceoption" value="auto" id="autobounceoption"{if !$show_manual} checked="checked"{/if} />
											{$lang.Bounce_Auto_Process}
										</label>
									</td>
								</tr>
								<tr id="auto_settings" style="display:;">
									<td class="FieldLabel">
										&nbsp;
									</td>
									<td>
										<span style="background: url('images/nodejoin.gif') no-repeat center left;padding: 5px 0px 5px 30px;display:block;">
											{$lang.Bounce_Auto_Process_Steps}:
										</span>
										<ol style="margin:0; padding-left:4.5em; line-height:2;">
											<li>{$lang.Bounce_Auto_Process_Step1|sprintf,$list_url,$list_name}</li>
											<li>{$lang.Bounce_Auto_Process_Step2}</li>
											<li>{$lang.Bounce_Auto_Process_Step3}</li>
											<li>{$lang.Bounce_Auto_Process_Step4|sprintf,$cron_url}</li>
											<li>{$lang.Bounce_Auto_Process_Step5}</li>
										</ol>
									</td>
								</tr>
								<tr>
									<td width="200" class="FieldLabel">
									</td>
									<td>
										<label for="bounce_process">
											<input type="radio" name="bounceoption" value="manual" id="bounce_process"{if $show_manual} checked="checked"{/if} />
											{$lang.Bounce_Manual_Process}
										</label>
									</td>
								</tr>

								{template="bounce_details"}

								<tr>
									<td>
										&nbsp;
									</td>
									<td  style="padding-top:10px;">
										<input class="Field150 SubmitButton" id="BounceButton" type="button" value="{$lang.Bounce_Auto_Button}">
										{$lang.OR}
										<a href="index.php?Page=Bounce" onclick='return confirm("{$lang.Bounce_CancelPrompt}");'>{$lang.Cancel}</a>
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
