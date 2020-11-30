<table class="subscriberEventTable" id="event_%%GLOBAL_SubscriberID%%_%%GLOBAL_eventid%%">
	<tr>
		<td colspan="2" class="eventtype">
			%%GLOBAL_Type%%
			<span style="font-weight: normal;display:%%GLOBAL_EventLinkDisplay%%">
			( %%GLOBAL_EventEditLink%% %%GLOBAL_EventOr%% %%GLOBAL_EventDeleteLink%% )
			</span>
		</td>
	</tr>
	<tr>
		<td class="eventsubject">%%LNG_Subject%%:</td>
		<td>%%GLOBAL_Subject%%</td>
	</tr>
	<tr>
		<td class="eventdate">%%LNG_Date%%:</td>
		<td>%%GLOBAL_Date%%</td>
	</tr>
	<tr>
		<td class="eventnotes">%%LNG_Notes%%:</td>
		<td>%%GLOBAL_Notes%%</td>
	</tr>
</table>
<script>
if (!subscribers[%%GLOBAL_SubscriberID%%]) {
	subscribers[%%GLOBAL_SubscriberID%%] = new Array;
}
subscribers[%%GLOBAL_SubscriberID%%][%%GLOBAL_eventid%%] = %%GLOBAL_EventJSON%%;
</script>