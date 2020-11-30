<link rel="stylesheet" href="includes/styles/ui.datepicker.css" type="text/css">
<link rel="stylesheet" href="includes/styles/timepicker.css" type="text/css">
<script src="includes/js/jquery/ui.js"></script>
<script>
var SegmentID = '%%GLOBAL_SegmentID%%';
// Show the loading indicator
$(document).ajaxSend(function() {
	$('#loading_indicator').css('display','block');
});
$(document).ajaxStop(function() {
	$('#loading_indicator').css('display','none');
});
</script>
<script src="includes/js/jquery/timepicker.js"></script>
<script src="includes/js/jquery/form.js"></script>
<script>%%GLOBAL_DatePickerJavascript%%</script>
<div id="eventAddFormDiv" style="display:none;">
%%GLOBAL_EventAddForm%%
</div>

{template="google_calendar_form"}

	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Subscribers_View%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Subscribers_View_Intro%%
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
			%%GLOBAL_EditButton%%
			%%GLOBAL_DeleteButton%%
			<input class="FormButton" type="button" value="%%LNG_Done%%" onClick='document.location="index.php?Page=Subscribers&Action=Manage&SubAction=Step3"'>
			<br />&nbsp;
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
							%%GLOBAL_emailaddress%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_Format%%:&nbsp;
						</td>
						<td>
							%%GLOBAL_FormatList%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_ConfirmedStatus%%:&nbsp;
						</td>
						<td>
							%%GLOBAL_ConfirmedList%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_SubscriberStatus%%:&nbsp;
						</td>
						<td>
							%%GLOBAL_StatusList%%
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