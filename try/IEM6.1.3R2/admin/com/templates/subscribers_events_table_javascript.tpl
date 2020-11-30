
	function eventsTable(sortby,sortdir,page,perpage) {
		var segment = (Application.Page.Subscriber_Manage? Application.Page.Subscriber_Manage.segmentID : (SegmentID? SegmentID : ''));
		if (segment != '') segment = '&SegmentID=' + segment;
		var url = "index.php?Page=Subscribers&Action=Event&SubAction=EventTable&id=%%GLOBAL_subscriberid%%&List=%%GLOBAL_listid%%" + segment;
		if (sortby && sortdir) {
			url = url + "&SortBy=" + sortby + "&Direction=" + sortdir;
		}
		if (page) {
			url = url + "&DisplayPage=" + page;
		}
		if (perpage) {
			url = url + "&SetPerPage=" + perpage;
		}

		$.get(url,'',function(data,status) {
			$('#eventsTable').html(data);
			tb_init('a.thickbox, area.thickbox, input.thickbox');
		});
	}
	function subscriberEventAdded() {
		eventsTable();
		resetForm();
		tb_remove();
	}

	function subscriberEventDelete(subscriberid,eventid) {
		if (confirm('%%LNG_ConfirmEventDelete%%')) {
			var segment = (Application.Page.Subscriber_Manage? Application.Page.Subscriber_Manage.segmentID : (SegmentID? SegmentID : ''));
			if (segment != '') segment = '&SegmentID=' + segment;
			$.get('index.php?Page=Subscribers&Action=Event&SubAction=EventDelete&id=' + subscriberid + '&eventid=' + eventid + segment,'',
				function(data,textStatus) {
					eval(data);
				}
			);
		}

		return false;
	}
	function subscriberEventDeleted() {
		eventsTable();
	}
	function eventsDeleteSelected(subscriberid) {
		var eventids = '';
		if ($(".event_checkbox:checked").length == 0) {
			alert('%%LNG_SelectAnEvent%%');
			return false;
		} else {
			$(".event_checkbox:checked").each(function(i) {
				eventids = eventids + "&eventids[]=" + $(this).val();
			});

			var segment = (Application.Page.Subscriber_Manage? Application.Page.Subscriber_Manage.segmentID : (SegmentID? SegmentID : ''));
			if (segment != '') segment = '&SegmentID=' + segment;
			if (confirm('%%LNG_ConfirmMultipleEventDelete%%')) {
				$.get('index.php?Page=Subscribers&Action=Event&SubAction=EventDelete&id=%%GLOBAL_subscriberid%%' + eventids + segment,'',
					function(data,textStatus) {
						eval(data);
					}
				);
			}
		}
	}
	function toggleCheckAll(master) {
		if (master.checked) {
			$('.event_checkbox').attr('checked','checked');
		} else {
			$('.event_checkbox').attr('checked','');
		}
	}

	eventsTable();