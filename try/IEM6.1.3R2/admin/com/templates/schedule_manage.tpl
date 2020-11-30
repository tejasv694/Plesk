<script>

	function sprintf() {
		var argv = sprintf.arguments;
		inputstring = argv[0];
		for (i = 1; i < argv.length; i++) {
			inputstring = inputstring.replace('%s', argv[i]);
		}
		return inputstring;
	}

	function TimeLeft(timediff) {
		if (timediff < 60) {
			if (timediff == 1) {
				return '%%LNG_TimeTaken_Seconds_One%%';
			}
			return sprintf('%%LNG_TimeTaken_Seconds_Many%%', timediff);
		}

		if (timediff >= 60 && timediff < 3600) {
			mins_left = (Math.floor(timediff / 60)).toFixed(0);
			secs_left = (Math.floor(timediff % 60)).toFixed(0);
			if (mins_left == 1) {
				mytimediff = '%%LNG_TimeTaken_Minutes_One%%';
			} else {
				mytimediff = sprintf('%%LNG_TimeTaken_Minutes_Many%%', mins_left);
			}
			if (secs_left > 0) {
				mytimediff += ', ' + sprintf('%%LNG_TimeTaken_Seconds_Many%%', secs_left);
			}
			return mytimediff;
		}

		hours_left = (Math.floor(timediff / 3600)).toFixed(0);

		if (hours_left < 24) {
			mins_left = (Math.floor(timediff % 3600) / 60).toFixed(0);

			if (hours_left == 1) {
				if (mins_left == 0) {
					return '%%LNG_TimeTaken_Hours_One%%';
				}
				return sprintf('%%LNG_TimeTaken_Hours_One_Minutes%%', mins_left);
			}

			if (mins_left == 0) {
				return sprintf('%%LNG_TimeTaken_Hours_Many%%', hours_left);
			}
			return sprintf('%%LNG_TimeTaken_Hours_Many_Minutes%%', hours_left, mins_left);
		}

		days_left = (Math.floor(hours_left / 24)).toFixed(0);

		if (days_left < 30) {
			hours_left = hours_left - (days_left * 24);

			if (days_left == 1) {
				if (hours_left == 0) {
					return '%%LNG_TimeTaken_Days_One%%';
				}
				return sprintf('%%LNG_TimeTaken_Days_One_Hours%%', hours_left);
			}

			if (hours_left == 0) {
				return sprintf('%%LNG_TimeTaken_Days_Many%%', days_left);
			}
			return sprintf('%%LNG_TimeTaken_Days_Many_Hours%%', days_left, hours_left);
		}

		// 24 months = 730
		if (days_left >= 30 && days_left <= 730) {
			months_left = (Math.floor(days_left / 30)).toFixed(0);
			days_left = days_left - (months_left * 30);

			if (months_left == 1) {
				if (days_left == 0) {
					return '%%LNG_TimeTaken_Months_One%%';
				}
				return sprintf('%%LNG_TimeTaken_Months_One_Days%%', days_left);
			}

			if (days_left == 0) {
				return sprintf('%%LNG_TimeTaken_Months_Many%%', months_left);
			}
			return sprintf('%%LNG_TimeTaken_Months_Many_Days%%', months_left, days_left);
		}

		if (days_left > 730) {
			years_left = (Math.floor(days_left / 365)).toFixed(0);

			if (years_left == 1) {
				return sprintf('%%LNG_TimeTaken_Years_One%%');
			}
			return sprintf('%%LNG_TimeTaken_Years_Many%%', years_left);
		}

	}

	// have_refreshed is used to make sure the page doesn't continually refresh and never stop.
	function UpdateMyTimer(myid, updatetime, have_refreshed) {
		if (updatetime > 0) {
			docid = 'send_status_' + myid;
			document.getElementById(docid).innerHTML = TimeLeft(updatetime);
			if (updatetime > 3600) {
				setTimeout('UpdateMyTimer("' + myid + '", ' + (updatetime - 60) + ', ' + have_refreshed + ')', 60000);
			} else {
				setTimeout('UpdateMyTimer("' + myid + '", ' + (updatetime - 1) + ', ' + have_refreshed + ')', 1000);
			}
		} else {
			if (updatetime == 0) {
				if (have_refreshed == 0) {
					setTimeout('document.location="index.php?Page=Schedule&R"', 2000);
				}
			} else {
				if (updatetime < 0) {
					setTimeout('UpdateMyTimer("' + myid + '", ' + (updatetime + 1) + ', ' + have_refreshed + ')', 1000);
				}
			}
		}
	}

	function UpdateCronTimer(timediff, maxdiff, updatecrontime) {
		if (!updatecrontime) {
			return;
		}

		if (timediff <= 0) {
			timediff = maxdiff;
		}

		$('#cronscheduletime_container').show();
		document.getElementById('cronscheduletime').innerHTML = sprintf('%%LNG_CronWillRunInApproximately%%', TimeLeft(timediff));
		setTimeout('UpdateCronTimer(' + (timediff - 1) + ', ' + (maxdiff) + ', true)', 1000);
	}
</script>

<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">%%LNG_ScheduleManage%%</td>
	</tr>
	<tr id="cronscheduletime_container" style="display:none;">
		<td class="body pageinfo" style="padding-bottom:5px;">
			<p>
			%%LNG_Help_ScheduleManage%%
			</p>
			<div id="cronscheduletime" style="background-color:#EEEEEE; padding: 5px 5px 8px 10px; margin-bottom: 10px;"></div>
			<script>UpdateCronTimer('%%GLOBAL_CronTimeLeft%%', %%GLOBAL_CronTimeDifference%%, %%GLOBAL_UpdateCronTime%%);</script>
		</td>
	</tr>
	<tr>
		<td>
			%%GLOBAL_Message%%
		</td>
	</tr>
	<tr>
		<td>
			%%GLOBAL_CronWarning%%
		</td>
	</tr>
	<tr>
		<td class=body>
			<form name="schedulesform" method="post" action="index.php?Page=Schedule&Action=Delete" onsubmit="return DeleteSelectedItems(this);">
			<table width="100%" border="0">
				<tr>
					<td class="Text">
						%%GLOBAL_Newsletters_SendButton%%
						<input type="submit" name="DeleteSchedulesButton" value="%%LNG_Delete_Selected%%" class="SmallButton">
					</td>
					<td align="right">
						%%TPL_Paging%%
					</td>
				</tr>
			</table>
			<table border=0 cellspacing="0" cellpadding="2" width=100% class="Text">
				<tr class="Heading3">
					<td width="5" nowrap align="center">
						<input type="checkbox" name="toggle" onClick="javascript: toggleAllCheckboxes(this);">
					</td>
					<td width="5">&nbsp;</td>
					<td width="28%">
						%%LNG_NewsletterName%% -
						%%LNG_NewsletterSubject%%
					</td>
					<td width="12%">
						%%LNG_Schedule_NewsletterType%%
					</td>
					<td width="15%">
						%%LNG_MailingList%%
					</td>
					<td width="15%">
						%%LNG_DateScheduled%%
					</td>
					<td width="20%">
						%%LNG_Status%%
					</td>
					<td width="120">
						%%LNG_Action%%
					</td>
				</tr>
				%%TPL_Schedule_Manage_Row%%
			</table>
			</form>
			%%TPL_Paging_Bottom%%
		</td>
	</tr>
</table>

<script>
	function DeleteSelectedItems(formObj) {
		items_found = 0;
		for (var i=0;i < formObj.length;i++)
		{
			fldObj = formObj.elements[i];
			if (fldObj.type == 'checkbox')
			{
				if (fldObj.checked) {
					items_found++;
					break;
				}
			}
		}

		if (items_found <= 0) {
			alert("%%LNG_ChooseSchedulesToDelete%%");
			return false;
		}

		if (confirm("%%LNG_ConfirmRemoveSchedules%%")) {
			return true;
		}
		return false;
	}

	function ConfirmDelete(JobID) {
		if (!JobID) {
			return false;
		}
		if (confirm("%%LNG_DeleteSchedulePrompt%%")) {
			document.location='index.php?Page=Schedule&Action=Delete&job=' + JobID;
			return true;
		}
	}
</script>
