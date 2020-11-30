	<tr class="GridRow" id="event_%%GLOBAL_SubscriberID%%_%%GLOBAL_eventid%%">
		<td width="5"><input type="checkbox" value="%%GLOBAL_eventid%%" class="event_checkbox"></td>
		<td width="5"><img src="images/%%GLOBAL_Icon%%"></td>
		<td style="width: 30%;"><div style="width:380px;white-space:nowrap;overflow:hidden;">%%GLOBAL_Subject%% - <span style="color:#777777;">%%GLOBAL_Notes%%</span></div></td>
		<td style="width: 20%;" nowrap="nowrap">%%GLOBAL_Type%%</td>
		<td style="width: 20%;" nowrap="nowrap">%%GLOBAL_Date%%</td>
		<td style="width: 10%;" nowrap="nowrap">%%GLOBAL_User%%</td>
		<td style="width: 15%;" nowrap="nowrap">
			%%GLOBAL_EventEditLink%%
			%%GLOBAL_EventDeleteLink%%
		</td>
	</tr>

<script>

if (!subscribers[%%GLOBAL_SubscriberID%%]) {
	subscribers[%%GLOBAL_SubscriberID%%] = new Array;
}
subscribers[%%GLOBAL_SubscriberID%%][%%GLOBAL_eventid%%] = %%GLOBAL_EventJSON%%;

</script>
