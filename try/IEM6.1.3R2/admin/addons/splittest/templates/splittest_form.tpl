<script src="includes/js/jquery/ui.js"></script>

<script>

	var PAGE = {
		init: function() {

			var frm = document.frmSplitTestEdit;

			// Are we editing a campaign, and if so is it focused on link clicks?
			if('{$weight_linkclick}' != '0') {
				document.getElementById('weight_type').selectedIndex = 1;
			}

			if (frm.distributed.checked == true) {
				$('#weightFieldLabel').css({ display: 'none' });
				$('#weightDiv').css({ display: 'none' });
			}

			PAGE.normaliseHours();

			$(frm).submit(function(event) {
				return PAGE.submit();
			});

			$('.cancelButton').click(function() {
				PAGE.cancel();
			});

			$('#percentage_hoursafter').click(function() {
				$('#percentage').click();
				$('#percentage_hoursafter').select();
			});

			$('#percentage_percentage').click(function() {
				$('#percentage').click();
				$('#percentage_percentage').select();
			});

			$('#hoursafter_timerange').change(function() {
				$('#percentage').click();
			});

			$('#percentage').click(function() {
				PAGE.checkPercentageSplitType();
			});

			$('#distributed').click(function() {
				PAGE.checkDistributedSplitType();
			});

			$('#weight_type').change(function() {
				if($(this).val() == 'open') {
					// Winner is based on open rate
					$('#weight_openrate').val(100);
					$('#weight_linkclick').val(0);
				}
				else {
					// Winner is based on click rate
					$('#weight_openrate').val(0);
					$('#weight_linkclick').val(100);
				}
			});

			$('#hrefPreview').click(function() {
				var campaigns = PAGE.getSelectedCampaigns();
				if (!campaigns.length) {
					alert("{$lang.Addon_splittest_PreviewNoneSelected}");
					$('#splittest_campaigns').focus();
					return false;
				}
				$(campaigns).each(function(i, e) {
					window.open('index.php?Page=Newsletters&Action=Preview&id=' + e, 'campaign' + e);
				});
				this.blur();
				return false;
			});
		},

		calculateWeight: function(whatchanged) {
			var fldname = 'weight_' + whatchanged;
			var fld = document.getElementById(fldname);
			var newvalue = parseInt(fld.value, 10);
			if (newvalue < 0 || newvalue > 100 || isNaN(newvalue)) {
				fld.focus();
				return;
			}

			var otherfield_name = '';

			switch (whatchanged)
			{
				case 'openrate':
					otherfield_name = 'linkclick';
				break;

				case 'linkclick':
					otherfield_name = 'openrate';
				break;
			}

			if (otherfield_name == '') {
				return;
			}

			var newweight = 100 - newvalue;
			$('#weight_' + otherfield_name).get(0).value = newweight;
		},

		checkPercentageSplitType: function() {
			$('#weightFieldLabel').css({ display: 'block' });
			$('#weightDiv').css({ display: 'block' });
		},

		checkDistributedSplitType: function() {
			$('#weightFieldLabel').css({ display: 'none' });
			$('#weightDiv').css({ display: 'none' });
		},

		checkWeightings: function(frm) {
			weight_openrate = parseInt(frm.weight_openrate.value, 10);
			if (weight_openrate < 0 || weight_openrate > 100 || isNaN(weight_openrate)) {
				alert('{$lang.Addon_splittest_form_InvalidWeight_Alert}');
				return false;
			}

			weight_linkclick = parseInt(frm.weight_linkclick.value, 10);
			if (weight_linkclick < 0 || weight_linkclick > 100 || isNaN(weight_linkclick)) {
				alert('{$lang.Addon_splittest_form_InvalidWeight_Alert}');
				return false;
			}

			if ((weight_openrate + weight_linkclick) != 100) {
				alert('{$lang.Addon_splittest_form_InvalidWeight_IncorrectTotal}');
				return false;
			}
			return true;
		},

		getSelectedCampaigns: function() {
			var el = document.frmSplitTestEdit['splittest_campaigns[]'];
			var selected = [];

			for(var i = 0, j = el.options.length; i < j; ++i) {
				if(el.options[i].selected) {
					selected.push(el.options[i].value);
				}
			}
			return selected;
		},

		getSplitType: function() {
			if ($("#distributed").get(0).checked) {
				return 'distributed';
			}
			if ($("#percentage").get(0).checked) {
				return 'percentage';
			}
			return null;
		},

		getTimeRange: function(frm) {
			var splittype = this.getSplitType();
			if (splittype != 'percentage') {
				return 0;
			}

			var timerange = null;

			var trange = frm.hoursafter_timerange;
			for (var i=0, j=trange.options.length; i<j; ++i) {
				if (trange.options[i].selected) {
					timerange = trange.options[i].value;
					break;
				}
			}

			if (frm.percentage_hoursafter.value.length < 1) {
				return null;
			}

			var newvalue = 0;
			if (timerange == 'days') {
				newvalue = parseInt(frm.percentage_hoursafter.value, 10) * 24;
			} else {
				newvalue = parseInt(frm.percentage_hoursafter.value, 10);
			}

			return newvalue;
		},

		// The 'Hours After' value may need to be displayed in days rather than hours.
		normaliseHours: function() {
			var frm = document.frmSplitTestEdit;
			var hours_after = frm.percentage_hoursafter.value;
			var type = frm.hoursafter_timerange;
			var hours = parseInt(hours_after, 10);
			if (isNaN(hours)) {
				hours_after = 0;
			}
			if (hours >= 24 && hours % 24 == 0) {
				hours_after = hours / 24;
				$(type).val('days');
			}
			hours_after = hours;
		},

		submit: function() {
			var frm = document.frmSplitTestEdit;
			var data = {
				splitName: encodeURIComponent($.trim(frm.splitname.value)),
				campaigns: this.getSelectedCampaigns(),
				splittype: this.getSplitType(),
				hours: this.getTimeRange(frm),
				percentage: $.trim(frm.percentage_percentage.value)
			};

			if (data.splitName == '') {
				alert('{$lang.Addon_splittest_form_EnterName_Alert}');
				frm.splitname.focus();
				return false;
			}

			if (data.campaigns.length < 2) {
				alert('{$lang.Addon_splittest_form_SelectCampaigns_Alert}');
				return false;
			}

			if (data.splittype == 'percentage') {
				if (data.percentage.length < 1 || data.percentage < {$Percentage_Minimum} || data.percentage > {$Percentage_Maximum}) {
					alert('{$lang.Addon_splittest_form_ChoosePercent_Alert|sprintf,$Percentage_Minimum,$Percentage_Maximum}');
					frm.percentage_percentage.focus();
					frm.percentage_percentage.select();
					return false;
				}
				if (data.hours == null || data.hours < {$Percentage_HoursAfter_Minimum} || data.hours > {$Percentage_HoursAfter_Maximum}) {
					alert('{$lang.Addon_splittest_form_ChooseHoursAfter_Alert|sprintf,$Percentage_HoursAfter_Minimum,$Percentage_HoursAfter_Maximum_Days}');
					frm.percentage_hoursafter.focus();
					frm.percentage_hoursafter.select();
					return false;
				}

				weightings_ok = PAGE.checkWeightings(frm);
				if (!weightings_ok) {
					return false;
				}
			}
			// Convert the days to hours in the form only once everything validates.
			frm.percentage_hoursafter.value = data.hours;
			return true;
		},

		cancel: function() {
			{if $FormType == 'create'}
				var confmsg = '{$lang.Addon_splittest_form_Cancel_Create}';
			{elseif $FormType == 'edit'}
				var confmsg = '{$lang.Addon_splittest_form_Cancel_Edit}';
			{/if}

			if (confirm(confmsg)) {
				window.location.href = "{$BaseAdminUrl}";
			}
		}
	};

	$(function() {
		PAGE.init();
	});
</script>

<form name="frmSplitTestEdit" id="frmSplitTestEdit" method="post" action="{$AdminUrl}&Action={if $FormType == 'create'}Create{elseif $FormType == 'edit'}Edit&id={$splitid}{/if}">
	<input type="hidden" id="action" name="action" value="{$action}" />
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				{if $FormType == 'create'}
					{$lang.Addon_splittest_Form_CreateHeading}
				{elseif $FormType == 'edit'}
					{$lang.Addon_splittest_Form_EditHeading}
				{/if}
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					{$lang.Addon_splittest_Form_Intro}
				</p>
			</td>
		</tr>
		<tr>
			<td>
				{$FlashMessages}
			</td>
		</tr>
		<tr>
			<td>
				{if $ShowSend}<input class="FormButton submitButton" type="submit" name="Submit_Send" value="{$lang.Addon_splittest_SaveSend}" style="width:100px" />{/if}
				<input class="FormButton submitButton" type="submit" name="Submit_Exit" value="{$lang.Addon_splittest_SaveExit}" style="width:100px" />
				<input class="FormButton cancelButton" type="button" value="{$lang.Addon_splittest_Cancel}" />
				<br />&nbsp;
				<table border="0" cellspacing="0" cellpadding="0" class="Panel">
					<tr>
						<td colspan="3" class="Heading2">
							&nbsp;&nbsp;{$lang.Addon_splittest_Form_Settings}
						</td>
					</tr>
					<tr>
						<td class="FieldLabel" width="200" nowrap="nowrap">
							<img src="images/blank.gif" width="200" height="1" /><br />
							{template="required"}
							{$lang.Addon_splittest_Form_CampaignName}:&nbsp;
						</td>
						<td width="85%">
							<input type="text" id="splitname" name="splitname" class="Field250 form_text" value="{$splitname}" style="width:446px;" /> <br />
							<span class="aside">{$lang.Addon_splittest_Form_CampaignName_Aside}</span>
							<br /><br />
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="required"}
							{$lang.Addon_splittest_Form_ChooseCampaigns}:&nbsp;
						</td>
						<td>
							<select id="splittest_campaigns" name="splittest_campaigns[]" multiple="multiple" class="ISSelectReplacement splittest_campaigns">
							{foreach from=$campaigns key=k item=campaignDetails}
								<option value="{$campaignDetails.newsletterid}"{if $campaignDetails.selected == 1} selected="selected"{/if}>{$campaignDetails.name}</option>
							{/foreach}
							</select>
							&nbsp;&nbsp;&nbsp;{$lnghlp.Addon_splittest_Form_AddCampaigns}
							<a id="hrefPreview" href="#"><img border="0" src="images/magnify.gif"/> {$lang.Addon_splittest_PreviewSelected}</a>
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="required"}
							{$lang.Addon_splittest_ChooseWinnerBy}:
						</td>
						<td style="padding-top:5px">
							<select id="weight_type" name="weight_type" style="width:446px">
								<option value="open">{$lang.Addon_splittest_Winner_Open}</option>
								<option value="click">{$lang.Addon_splittest_Winner_Click}</option>
							</select>
							<input type="hidden" id="weight_openrate" value="{$weight_openrate}" name="weights[openrate]" />
							<input type="hidden" id="weight_linkclick" value="{$weight_linkclick}" name="weights[linkclick]" />
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="required"}
							{$lang.Addon_splittest_Form_SplitType}
						</td>
						<td style="padding-top:5px">
							<input type="radio" id="distributed" name="splittype" value="distributed" {if $splitType == 'distributed'}checked="checked"{/if} />
							<label for="distributed">
								<strong>{$lang.Addon_splittest_Distributed_Intro}</strong>
								&nbsp;{$lnghlp.Addon_splittest_Splittype_Distributed}
								<ul style="color:#676767; margin-top:5px; margin-bottom:5px">
									<li>{$lang.Addon_splittest_Distributed_List_1}</li>
									<li>{$lang.Addon_splittest_Distributed_List_2}</li>
								</ul>
							</label>
							<input type="radio" id="percentage" name="splittype" value="percentage" {if $splitType == 'percentage'}checked="checked"{/if} />
							<label for="percentage">
								<strong>{$lang.Addon_splittest_Percentage_Intro}</strong>
								&nbsp;{$lnghlp.Addon_splittest_Splittype_Percentage}
								<ul style="color:#676767; margin-top:5px; margin-bottom:5px">
									<li>{$lang.Addon_splittest_Percentage_List_1_1} <input type="text" id="percentage_percentage" name="percentage_percentage" style="font-size: 11px; width: 25px;" value="{$percentage_percentage}" />% {$lang.Addon_splittest_Percentage_List_1_2}</li>
									<li>
										{$lang.Addon_splittest_Percentage_List_2_1}
										<input type="text" id="percentage_hoursafter" name="percentage_hoursafter" style="font-size: 11px; width: 25px;" value="{$splitHoursAfter}" />
										 <select name="hoursafter_timerange" id="hoursafter_timerange" style="width: 70px;">
											<option value="hours"{if $splitHoursAfter_TimeRange == 'hours'} selected="selected"{/if}>{$lang.Addon_splittest_Hours}</option>
											<option value="days"{if $splitHoursAfter_TimeRange == 'days'} selected="selected"{/if}>{$lang.Addon_splittest_Days}</option>
										 </select> {$lang.Addon_splittest_Percentage_List_2_2}
									</li>
									<li>{$lang.Addon_splittest_Percentage_List_3}</li>
								</ul>
							</label>
						</td>
					</tr>
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td class="FieldLabel">&nbsp;</td>
						<td valign="top" height="30">
							{if $ShowSend}<input class="FormButton submitButton" type="submit" name="Submit_Send" value="{$lang.Addon_splittest_SaveSend}" style="width:100px" />{/if}
							<input class="FormButton submitButton" type="submit" name="Submit_Exit" value="{$lang.Addon_splittest_SaveExit}" style="width:100px" />
							<input class="FormButton cancelButton" type="button" value="{$lang.Addon_splittest_Cancel}" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
