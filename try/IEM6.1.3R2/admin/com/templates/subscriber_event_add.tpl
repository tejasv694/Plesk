<script>
	// Resets the form, if subscriberid and eventid are given, prints an update form. If subscriberid and listid are given prints a form for a new event.
	var etOver = false;

	var eventTypes = %%GLOBAL_EventTypesJSON%%;
	var eventTypesLoad = true;

	var googleUse = %%GLOBAL_eventGoogleUse%%;

	$(document).ready(function(){
		var elmList = $('<ul></ul>');
		var elmDiv = $('<div id="eventTypes"></div>');

		for (i = 0; i < eventTypes.length; i++) {
			elmList.append('<li>' + escapeHTML(eventTypes[i]) + '</li>');
		}

		elmDiv.append(elmList);

		// Store element offset.
		var elmOffset = $('#eventType').offset();
		var elm = $('#eventType');
		// Append the timPicker to the body and position it.
		elmDiv.appendTo('body').css({'top':elmOffset.top, 'left':elmOffset.left}).hide();

		setListEvents();

		var showEventTypes = function() {
			if (eventTypesLoad) {
				var elmOffset = $('#eventType').offset();
				var elm = $('#eventType');
				// Append the timPicker to the body and position it.
				$('#eventTypes').css({'top':elmOffset.top+5, 'left':elmOffset.left}).css('width',elm[0].clientWidth).show();
			} else {
				eventTypesLoad = true;
			}
		};

		$('#eventType').unbind().click(showEventTypes).focus(showEventTypes).blur(function() {
			if (!etOver) {
				$('#eventTypes').hide();
			}
		}).attr('autocomplete','OFF')
		.keypress(function(e) {
			switch (e.keyCode) {
				case 38: // Up arrow.
				case 63232: // Safari up arrow.
					var $selected = $("#eventTypes li.selected");
					var prev = $selected.prev().addClass("selected")[0];
					if (prev) {
						$selected.removeClass("selected");
						elmDiv[0].scrollTop = prev.offsetTop;
					}
					return false;
					break;
				case 40: // Down arrow.
				case 63233: // Safari down arrow.
					var $selected = $("#eventTypes li.selected");
					var next = $selected.length ? $selected.next().addClass("selected")[0] : $("#eventTypes li:first").addClass("selected")[0];
					if (next) {
						$selected.removeClass("selected");
						elmDiv[0].scrollTop = next.offsetTop;
					}
					return false;
					break;
				case 13: // Enter
					if (!$('#eventTypes').is(":hidden")) {
						var sel = $("#eventTypes li.selected");
						$('#eventType').val(sel.html());
						if ($('#eventSubject').val() == '') {
							$('#eventSubject').val($('#eventType').val());
						}
						$('#eventTypes').hide();
						return false;
					}
					break;
				case 27: // Escape
					$('#eventTypes').hide();
					top.tb_remove();
				break;
			}
		});
	});

	function setListEvents() {
		$('#eventTypes li').mouseover(function() {
			$('#eventTypes li.selected').removeClass('selected');
			$(this).addClass('selected');
		}).mousedown(function() {
			etOver = true;
		}).click(function() {

			$('#eventType').val(unescapeHTML($(this).html()));
			if ($('#eventSubject').val() == '') {
				$('#eventSubject').val($('#eventType').val());
			}
			etOver = false;
			$('#eventTypes').hide();
		});
	}

	function resetForm(_subscriberid,_eventid,_listid) {
		// Clear form values
		$("#eventAddForm")[0].reset();

		var now = new Date();
		var hour = now.getHours();
		var ampm = '';

		// Initialize date/time picker, set values to now
		if (hour > 12) {
			hour = hour - 12;
			ampm = 'pm';
		} else {
			ampm = 'am';
		}
		if (hour == 0) {
			hour = 12;
		}
		var minutes = now.getMinutes();
		if (minutes < 10) { minutes = "0" + String(minutes); }

		$('#eventTime')[0].value = hour + ":" + minutes + " " + ampm;
		$('#eventDate')[0].value = (now.getDate() < 10 ? '0' : '') + now.getDate() + "/" + (now.getMonth() + 1) + "/" + now.getFullYear();

		$('.time-pick').timePicker({
			show24Hours:false,
			separator:':',
			step: 30
		});
		$('.date-pick').datepicker();

		$('#eventType').children('.dxComboboxClass').remove();
		$('#eventType')[0].selectedIndex = 0;

		if (googleUse) {
			$('#eventGoogle')[0].checked = true;
		}

		if (_subscriberid && _eventid) {
			// This is an update form, load event values
			$('#eventSubject')[0].value = subscribers[_subscriberid][_eventid].eventsubject;

			var eventtype = subscribers[_subscriberid][_eventid].eventtype;
			$('#eventType').val(eventtype);

			$('#eventTypes li').removeClass('selected');
			var types = $('#eventTypes li');
			for (i = 0; i < types.length; i++) {
				if (unescapeHTML($(types[i]).html()) == eventtype) {
					$(types[i]).addClass('selected');
				}
			}

			$('#eventDate')[0].value = subscribers[_subscriberid][_eventid].date;
			$('#eventTime')[0].value = subscribers[_subscriberid][_eventid].time;
			$('#eventnotes')[0].value = subscribers[_subscriberid][_eventid].eventnotes;
			subscriberid = _subscriberid;
			eventid = _eventid;

			loadevent = [];

			$('#saveButton').html('%%LNG_Save%%');

			$('#eventGoogle')[0].checked = false;
		} else {
			// This is a form for a new event
			subscriberid = _subscriberid;
			eventid = _eventid;
			listid = _listid;

			$('#saveButton').html('%%LNG_Add_Event%%');
			//setTimeout(function() { $('#eventType').focus(); },5);
		}
	}

	// This is called when the form is submitted
	function saveEvent() {
		if ($('#eventType').val() == "") {
			alert("%%LNG_SelectAnEventType%%");
			$('#eventType').focus().select();
			return false;
		}
		if ($('#eventSubject').val() == "") {
			alert("%%LNG_EnterEventSubject%%");
			$('#eventSubject').focus().select();
			return false;
		}

		$('#eventTypes').hide();

		if ($('#eventGoogle')[0].checked) {
			googleUse = true;
		} else {
			googleUse = false;
		}

		// Add event text to eventTypes array
		var matches = 0;
		for (i = 0; i < eventTypes.length; i++) {
			if (eventTypes[i].toLowerCase() == $('#eventType').val().toLowerCase()) {
				matches++;
				break;
			}
		}
		if (!matches) {
			eventTypes[eventTypes.length] = $('#eventType').val();
			$('#eventTypes ul').append('<li>' + escapeHTML($('#eventType').val()) + '</ul>');
			setListEvents();
		}

		// Set the end date for Google Calendar event
		if ($('#eventGoogle')[0].checked) {
			var now = new Date();
			var time = $('#eventTime').val().match(/(\d+):(\d+)\s*(am|pm)/i);
			var date = $('#eventDate').val().match(/(\d+)\/(\d+)\/(\d+)/);
			now.setFullYear(Number(date[3]),Number(date[2]) - 1,Number(date[1]));
			now.setHours(Number(time[1]) + (time[3].toLowerCase() == 'pm' ? 12 : 0));
			now.setMinutes(Number(time[2]));

			now.setMinutes(now.getMinutes() + 30);

			$('#googleEndDate').val(now.getDate() + "/" + (now.getMonth() + 1) + "/" + now.getFullYear());

			var hour = now.getHours();
			var ampm = (now.getHours() >= 12 ? "PM" : "AM");
			var hour = hour % 12;
			if (hour == 0) {
				hour = 12;
			}
			var minute = now.getMinutes();
			if (minute < 10) { minute = "0" + String(minute); }
			$('#googleEndTime').val(hour + ":" + now.getMinutes() + " " + ampm);
		}

		var segment = (Application.Page.Subscriber_Manage? Application.Page.Subscriber_Manage.segmentID : (SegmentID? SegmentID : ''));
		if (segment != '') segment = '&SegmentID=' + segment;

		if (subscriberid && eventid) {
			// We're updating an event
			$('#loading_indicator').css('z-index',1024);
			$.post('index.php?Page=Subscribers&Action=Event&SubAction=EventUpdate&eventid=' + eventid + '&id=' + subscriberid + segment, $('#eventAddForm').formSerialize(),
				function(data,textStatus) {
					eval(data);
				}
			);
		} else {
			// This is a new event
			$('#loading_indicator').css('z-index',1024);
			$.post('index.php?Page=Subscribers&Action=Event&SubAction=EventSave&List=' + listid + '&id=' + subscriberid + segment,$('#eventAddForm').formSerialize(),
				function(data,textStatus) {
					eval(data);
				}
			);
		}
		return false;
	}
</script>

<form onsubmit="return false;" class="Text" id="eventAddForm" style="padding: 15px;">
<table style="width: 100%;">
	<tr>
		<td valign="top" width="100">%%LNG_EventType%%:</td>
		<td style="width: 90%;">
			<input type="text" id="eventType" class="Field" name="event[type]" />
		</td>
	</tr>
	<tr>
		<td valign="top" width="100">%%LNG_Subject%%:</td>
		<td>
			<input type="text" id="eventSubject" class="Field" name="event[subject]" style="width: 100%;"/>
		</td>
	</tr>
	<tr>
		<td>%%LNG_Date%%:</td>
		<td>
			<input type="text" name="event[date]" class="date-pick Field" id="eventDate" style="width: 100px;">
			<input type="text" name="event[time]" class="time-pick Field" id="eventTime" value="%%GLOBAL_Time%%" style="width: 60px;">
			<span style="%%GLOBAL_eventGoogleDisplay%%">
				<label for="eventGoogle">
					<input type="checkbox" id="eventGoogle" name="event[google][log]">
					%%LNG_AddToGoogleCalendar%%
				</label>
				<input type="hidden" name="event[google][enddate]" id="googleEndDate" value="">
				<input type="hidden" name="event[google][endtime]" id="googleEndTime" value="">
				<input type="hidden" name="event[google][link]" id="googleLink" value="%%GLOBAL_GoogleCalendarLink%%">
			</span>
		</td>
	</tr>
	<tr>
		<td valign="top">%%LNG_Notes%%:</td>
		<td>
			<textarea style="width: 99%; height: 240px;" class="Field" name="event[notes]" id="eventnotes"></textarea>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td><button id="saveButton" onclick="return saveEvent();">%%LNG_Add_Event%%</button> <button onclick="top.tb_remove();">%%LNG_Cancel%%</button> <img src="images/searching.gif" class="loadingImage" style="display: none;"></td>
</table>
</form>
