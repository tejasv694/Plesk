<link rel="stylesheet" href="includes/styles/ui.datepicker.css" type="text/css">
<link rel="stylesheet" href="includes/styles/timepicker.css" type="text/css">
<script src="includes/js/jquery/ui.js"></script>

<script>
	%%GLOBAL_CustomDatepickerUI%%
</script>
{template="google_calendar_form"}
<script>
	var SegmentID = '%%GLOBAL_SegmentID%%';
	function CheckForm() {
		var f = document.frmSubscriberEditor;

		if (f.emailaddress.value == "") {
			alert("%%LNG_Subscribers_EnterEmailAddress%%");
			f.emailaddress.focus();
			return false;
		}

		%%GLOBAL_ExtraJavascript%%
		return true;
	}

	function Save() {
		if (CheckForm()) {
			var segment = (SegmentID == ''? '' : '&SegmentID=' + SegmentID);
			$(document.frmSubscriberEditor).attr('action', 'index.php?Page=Subscribers&Action=Edit&SubAction=Save&List=%%GLOBAL_list%%' + segment + '&save');
			document.frmSubscriberEditor.submit();
		}
	}

	// Show the loading indicator
	$(document).ajaxSend(function() {
		$('#loading_indicator').css('display','block');
	});
	$(document).ajaxStop(function() {
		$('#loading_indicator').css('display','none');
	});

	$(function() {
		$(document.frmSubscriberEditor).submit(function(event) {
			if (this.emailaddress.value == "") {
				event.preventDefault();
				event.stopPropagation();

				alert("%%LNG_Subscribers_EnterEmailAddress%%");
				this.emailaddress.focus();
				return false;
			}

			var f = this;
			%%GLOBAL_ExtraJavascript%%
			return true;
		});

		$('.CancelButton').click(function() {
			if (confirm("%%LNG_Subscribers_Edit_CancelPrompt%%"))
				document.location="index.php?Page=Subscribers&Action=Manage&SubAction=Step3";
		});

		$('.CustomFieldDateInput_Row').each(function() {
			var anchor = $('.CustomFieldDateInput_DatepickerAnchor', this).get(0);
			var year = $('.CustomField_Date_Year', this).get(0);
			var minYear = year.options[1].value;
			var maxYear = year.options[year.options.length - 1].value;

			$(anchor).datepicker({
				yearRange: minYear + ':' + maxYear,
				beforeShow: function() {
					var id = this.id.match(/CustomFiledDateInput_Anchor_(\d*)/);
					if(id.length != 2) return;

					var day = $('#CustomFieldDateInput_' + id[1] + ' .CustomField_Date_Day').get(0);
					var month = $('#CustomFieldDateInput_' + id[1] + ' .CustomField_Date_Month').get(0);
					var year = $('#CustomFieldDateInput_' + id[1] + ' .CustomField_Date_Year').get(0);

					if(!day || !month || !year) return;
					$(this).val($(day).val() + '/' + $(month).val() + '/' + $(year).val());
				},
				onSelect: function(date) {
					var id = this.id.match(/CustomFiledDateInput_Anchor_(\d*)/);
					if(id.length != 2) return;

					var day = $('#CustomFieldDateInput_' + id[1] + ' .CustomField_Date_Day').get(0);
					var month = $('#CustomFieldDateInput_' + id[1] + ' .CustomField_Date_Month').get(0);
					var year = $('#CustomFieldDateInput_' + id[1] + ' .CustomField_Date_Year').get(0);

					if(!day || !month || !year) return;

					var temp = date.match(/(\d{2})\/(\d{2})\/(\d{4})/);
					if(!temp || temp.length != 4) temp = ['', '', '', ''];
					$(day).val(temp[1]);
					$(month).val(temp[2]);
					$(year).val(temp[3]);
				}
			});
		});
	});
</script>

<link rel="stylesheet" href="includes/styles/timepicker.css" type="text/css">
<script>

</script>
<script src="includes/js/jquery/timepicker.js"></script>
<script src="includes/js/jquery/form.js"></script>
<div id="eventAddFormDiv" style="display:none;">
%%GLOBAL_EventAddForm%%
</div>

<form name="frmSubscriberEditor" method="post" action="index.php?Page=Subscribers&Action=Edit&SubAction=Save&List=%%GLOBAL_list%%">
<input type="hidden" name="subscriberid" value="%%GLOBAL_subscriberid%%"/>
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Subscribers_Edit%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Subscribers_Edit_Intro%%
				</p>
			</td>
		</tr>
		<tr>
			<td>
				%%GLOBAL_Message%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="button" value="%%LNG_SaveAndKeepEditing%%" style="width:130px" onclick='Save();'>
				<input class="FormButton_wide" type="submit" value="%%LNG_SaveAndExit%%">
				<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%"/>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="4" class="Heading2">
							&nbsp;&nbsp;%%LNG_EditSubscriberDetails%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_Email%%:&nbsp;
						</td>
						<td>
							<input type="text" name="emailaddress" value="%%GLOBAL_emailaddress%%" class="Field250">
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_Format%%:&nbsp;
						</td>
						<td>
							<select name="format" class="Field250">
								%%GLOBAL_FormatList%%
							</select>&nbsp;%%LNG_HLP_Format%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_ConfirmedStatus%%:&nbsp;
						</td>
						<td>
							<select name="confirmed" class="Field250">
								%%GLOBAL_ConfirmedList%%
							</select>&nbsp;%%LNG_HLP_ConfirmedStatus%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_SubscriberStatus%%:&nbsp;
						</td>
						<td>
							<select name="status" class="Field250">
								%%GLOBAL_StatusList%%
							</select>&nbsp;%%LNG_HLP_SubscriberStatus%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SubscribeRequestDate%%:&nbsp;
						</td>
						<td>
							%%GLOBAL_requestdate%%&nbsp;%%LNG_HLP_SubscribeRequestDate%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SubscribeRequestIP%%:&nbsp;
						</td>
						<td>
							%%GLOBAL_requestip%%&nbsp;%%LNG_HLP_SubscribeRequestIP%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SubscribeConfirmDate%%:&nbsp;
						</td>
						<td>
							%%GLOBAL_confirmdate%%&nbsp;%%LNG_HLP_SubscribeConfirmDate%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SubscribeConfirmIP%%:&nbsp;
						</td>
						<td>
							%%GLOBAL_confirmip%%&nbsp;%%LNG_HLP_SubscribeConfirmIP%%
						</td>
					</tr>
					<tr style='display: %%GLOBAL_ShowUnsubscribeInfo%%'>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_UnsubscribeTime%%:&nbsp;
						</td>
						<td>
							%%GLOBAL_unsubscribetime%%&nbsp;%%LNG_HLP_UnsubscribeTime%%
						</td>
					</tr>
					<tr style='display: %%GLOBAL_ShowUnsubscribeInfo%%'>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_UnsubscribeIP%%:&nbsp;
						</td>
						<td>
							%%GLOBAL_unsubscribeip%%&nbsp;%%LNG_HLP_UnsubscribeIP%%
						</td>
					</tr>
					%%GLOBAL_CustomFieldInfo%%
				</table>
			</td>
		</tr>
	</table>
</form>
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_SubscriberEvents%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%GLOBAL_SubscriberEvents_Intro%%
				</p>
			</td>
		</tr>
		<tr>
			<td>
					<div id="eventsTable">
					</div>
			</td>
		</tr>
	</table>

<script>
{template="subscribers_events_table_javascript"}
</script>
