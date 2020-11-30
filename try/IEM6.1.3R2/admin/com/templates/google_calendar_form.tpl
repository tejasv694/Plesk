<script>
var gcAllDay = %%GLOBAL_GoogleCalendarAllDay%%;

	function googleCalendarInitPicker(datefield,timefield) {
		// Initialize date/time picker, set values to now
		
		var now = new Date();
		
		var minutes = now.getMinutes();
		
		if (minutes >= 45) {
			now.setHours(now.getHours() + 1);
			minutes = 0;
		} else if (minutes >= 30) {
			minutes = 30;
		} else if (minutes >= 15) {
			minutes = 30;
		} else {
			minutes = 0;
		}
		now.setMinutes(minutes);
		
		if (timefield[0].id == 'gcTimeTo') {
			now.setMinutes(now.getMinutes() + 30);
			minutes = now.getMinutes();
		}
		
		var hour = now.getHours();
		var ampm = '';
		if (hour > 12) {
			hour = hour % 12;
			ampm = 'pm';
		} else {
			ampm = 'am';
		}
		if (hour == 0) {
			hour = 12;
		}
		
		
		timefield.timePicker({
			show24Hours:false,
			separator:':',
			step: 30
		}).change(function() {
			googleCalendarDates(this)
		});
		

		if (minutes < 10) { minutes = "0" + String(minutes); }
		timefield.val(hour + ":" + minutes + " " + ampm);
		
		datefield.datepicker({
			onSelect: function() {
				googleCalendarDates(this);
			}
		});
		
		datefield.val((now.getDate() < 10 ? '0' : '') + now.getDate() + "/" + (now.getMonth() + 1) + "/" + now.getFullYear());
	}
	
	function googleCalendarDates(field) {
		var allday = $('#gcAllDay')[0].checked;
		
		if (!allday) {
			if (!(reTimeFrom = $('#gcTimeFrom').val().match(/(\d+):(\d+)\s*(am|pm)/i))) {
				//alert("%%LNG_EnterValidDate%%");
				//return false;
			}
			if (!(reTimeTo = $('#gcTimeTo').val().match(/(\d+):(\d+)\s*(am|pm)/i))) {
				//alert("%%LNG_EnterValidDate%%");
				//return false;
			}
		}

		if (!(reDateFrom = $('#gcDateFrom').val().match(/(\d+)\/(\d+)\/(\d+)/))) {
			//alert("%%LNG_EnterValidDate%%");
			//return false;
		}
		if (!(reDateTo = $('#gcDateTo').val().match(/(\d+)\/(\d+)\/(\d+)/))) {
			//alert("%%LNG_EnterValidDate%%");
			//return false;
		}
		
		dateFrom = new Date();
		dateFrom.setFullYear(reDateFrom[3],reDateFrom[2] - 1,reDateFrom[1]);
		dateTo = new Date();
		dateTo.setFullYear(reDateTo[3],reDateTo[2] - 1,reDateTo[1]);
		if (!allday) {
			reTimeFrom = {
				hour: Number(reTimeFrom[1]),
				minute: Number(reTimeFrom[2]),
				ampm: reTimeFrom[3].toLowerCase()
			}
			reTimeTo = {
				hour: Number(reTimeTo[1]),
				minute: Number(reTimeTo[2]),
				ampm: reTimeTo[3].toLowerCase()
			}
			dateFrom.setHours( (reTimeFrom.hour % 12) + (reTimeFrom.ampm == 'pm' ? 12 : 0) );
			dateFrom.setMinutes(reTimeFrom.minute);
			dateTo.setHours( (reTimeTo.hour % 12) + (reTimeTo.ampm == 'pm' ? 12 : 0) );
			dateTo.setMinutes(reTimeTo.minute);
		}

		
		if (dateFrom.getTime() >= dateTo.getTime()) {
			if (field.id == 'gcDateFrom') {
				sourcedate = dateFrom;
				timechange = 30;
				timefield = $('#gcTimeTo');
				datefield = $('#gcDateTo');
				datefield2 = $('#gcDateFrom');
			} else if (field.id == 'gcDateTo') {
				sourcedate = dateTo;
				timechange = -30;
				timefield = $('#gcTimeFrom');
				datefield = $('#gcDateFrom');
				datefield2 = $('#gcDateTo');
			}	else if (!allday) {
				if (field.id == 'gcTimeFrom') {
					sourcedate = dateFrom;
					timechange = 30;
					timefield = $('#gcTimeTo');
					datefield = $('#gcDateTo');
					datefield2 = $('#gcDateFrom');
				} else {
					sourcedate = dateTo;
					timechange = -30;
					timefield = $('#gcTimeFrom');
					datefield = $('#gcDateFrom');
					datefield2 = $('#gcDateTo');
				}
			}
			
			sourcedate.setMinutes(sourcedate.getMinutes() + timechange);
			
			datefield.val(pad(sourcedate.getDate(),2) + "/" + pad(sourcedate.getMonth() + 1,2) + "/" + sourcedate.getFullYear());
			
			hour = sourcedate.getHours();
			ampm = "am";
			if (hour >= 12) {
				hour = hour % 12;
				ampm = "pm";
			}
			if (hour == 0) {
				hour = 12;
			}
			
			timefield.val(pad(hour,2) + ":" + pad(sourcedate.getMinutes(),2) + " " + ampm.toUpperCase());
		}
	}
	
	function pad(number,length) {
		var str = '' + number;
		while (str.length < length)
		str = '0' + str;
		return str;
	}

	
	function googleCalendarForm(fieldid,fromform,dataobj) {
		if (dataobj == null) {
			dataobj = {
				what: '%%LNG_FollowUp%%',
				datefrom: '',
				timefrom: '',
				dateto: '',
				timeto: '',
				allday: gcAllDay,
				where: '',
				description: "%%LNG_GoogleCalendarDescription%%\n\n%%GLOBAL_GoogleCalendarLink%%"
			}
		}
		if (fieldid) {
			if (fromform) {
				var datestring = $('*[id="CustomFields[' + fieldid + '][dd]"]').val() + "/" + $('*[id="CustomFields[' + fieldid + '][mm]"]').val() + "/" + $('*[id="CustomFields[' + fieldid + '][yy]"]').val();
				if (datestring.match(/\d{1,2}\/\d{1,2}\/\d{4}/)) {
					dataobj.datefrom = dataobj.dateto = datestring;
				}
			} else {
				if (date_field_dates[fieldid].day) {
					dataobj.datefrom = date_field_dates[fieldid].day + "/" + date_field_dates[fieldid].month + "/" + date_field_dates[fieldid].year;
					dataobj.dateto = date_field_dates[fieldid].day + "/" + date_field_dates[fieldid].month + "/" + date_field_dates[fieldid].year;
				}
			}
		}
		
		$('#gcWhat').val(dataobj.what);
		
		googleCalendarInitPicker($('#gcDateFrom'),$('#gcTimeFrom'));
		googleCalendarInitPicker($('#gcDateTo'),$('#gcTimeTo'));
		
		if (dataobj.datefrom)
			$('#gcDateFrom').val(dataobj.datefrom);
		if (dataobj.dateto)
			$('#gcDateTo').val(dataobj.dateto);
		if (dataobj.timeto)
			$('#gcTimeFrom').val(dataobj.timefrom);
		if (dataobj.timeto)
			$('#gcTimeTo').val(dataobj.timeto);
		
		$('#gcWhere').val(dataobj.where);
		$('#gcDescription').val(dataobj.description);
		
		if (dataobj.allday) {
			$('#gcTimeFrom').css('display','none');
			$('#gcTimeTo').css('display','none');
			$('#gcAllDay').attr('checked',true);
		} else {
			$('#gcAllDay').attr('checked',false);
		}
		
		return true;
	}
	$(document).ready(function (){
		$('#gcAllDay').click(function() {
			if ($('#gcAllDay').attr('checked')) {
				$('#gcTimeFrom').css('display','none');
				$('#gcTimeTo').css('display','none');
				gcAllDay = true;
			} else {
				$('#gcTimeFrom').css('display','');
				$('#gcTimeTo').css('display','');
				gcAllDay = false;
			}
		});
	});

	function googleCalendarSubmit() {
		if ($('#gcWhat').val() == '') {
			alert('%%LNG_GoogleCalendarEnterDescription%%');
		} else if ($('#gcDescription').val() == '') {
			alert('%%LNG_GoogleCalendarEnterDescription%%');
		} else {
			$('#loading_indicator').css('z-index',1024);
			$.post('remote.php',$('#gcForm').formSerialize(),
				function (data) {
					eval(data);
				}
			);
			if ($('#gcAllDay')[0].checked) {
				gcAllDay = true;
			} else {
				gcAllDay = false;
			}
		}
	}
	
	$(document).ajaxSend(function() {
		$('.loadingImage').css('display','');
	});
	$(document).ajaxStop(function() {
		$('.loadingImage').css('display','none');
	});
</script>

<div id="googleCalendarForm">
<form id="gcForm">
<input type="hidden" name="what" value="GoogleCalendar">
<table class="Text" style="padding:10px;width: 100%;">
	<tr>
		<td colspan="2" style="padding-bottom:5px;">
			%%GLOBAL_GoogleCalendarIntroText%%
		</td>
	</tr>
	<tr>
		<td width="5" nowrap="nowrap">%%LNG_What%%:</td>
		<td><input type="text" id="gcWhat" class="Field" style="width: 100%;"  name="google[what]"></td>
	</tr>
	<tr>
		<td>%%LNG_When%%:</td>
		<td>
			<span id="gcDates">
				<input type="text" class="Field" id="gcDateFrom" style="width: 80px;" name="google[datefrom]">
				<input type="text" class="Field" id="gcTimeFrom" style="width: 80px;" name="google[timefrom]">
				%%LNG_to%%
				<input type="text" class="Field" id="gcDateTo" style="width: 80px;" name="google[dateto]">
				<input type="text" class="Field" id="gcTimeTo" style="width: 80px;" name="google[timeto]">
			</span>
			<input type="checkbox" class="Field" name="google[allday]" id="gcAllDay">
			<label for="gcAllDay">
				%%LNG_AllDay%%
			</label>
		</td>
	</tr>
	<tr>
		<td>%%LNG_Where%%:</td>
		<td><input type="text" style="width: 100%;" class="Field" id="gcWhere" name="google[where]"></td>
	</tr>
	<tr>
		<td valign="top">%%LNG_Description%%:</td>
		<td><textarea id="gcDescription" style="width: 100%; height: 100px;" class="Field" name="google[description]"></textarea></td>
	</tr>
	<tr>
		<td></td>
		<td>
			<button class="FormButton" id="gcSave" onclick="googleCalendarSubmit(); return false;">%%LNG_Save%%</button>
			<button class="FormButton" id="gcCancel" onclick="top.tb_remove(); return false;">%%LNG_Cancel%%</button>
			<img src="images/searching.gif" class="loadingImage" style="display: none;">
		</td>
	</tr>
</table>
</form>
</div>