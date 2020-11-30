// Show the event row, called when the 'plus/minus' button is clicked
function subscriberEventsShow(subscriberid) {
	var row = $('#subscriber' + subscriberid);
	var event_row = $('#subscriber' + subscriberid + "_Events");
	var image = subscriberEventsRowImage(subscriberid);

	if (event_row[0].style.display != 'none' && event_row[0].style.display != '') {
		subscriberEventsHideRow(row,event_row,image);
	} else {
		if (!subscriberEventsFetched(event_row)) {
			// Fetch the events if they haven't already been fetched
			subscriberEventsUpdate(subscriberid,row,event_row,image);
		} else {
			// If they have been fetched just expand the event row
			subscriberEventsShowRow(row,event_row,image);
		}
	}
}

// Returns true if events for a subscriber were already fetched, false if they weren't
function subscriberEventsFetched(event_row) {
	return ($(event_row).children().filter('.dataCol').children().filter('.dataArea').html() == "" ? false : true );
}

// Hides the event row
function subscriberEventsHideRow(row,event_row,image) {
	event_row.css('display','none');

	$(row[0]).removeClass('subscriberEventRowActive');

	// Restore mouseover events
	row[0].onmouseover = function(){ this.className='GridRowOver'; };
	row[0].onmouseout = function(){ this.className='GridRow'; };

	image[0].src = 'images/plus.gif';
}

// Shows the event row
function subscriberEventsShowRow(row,event_row,image) {
	// Disable mouseover events
	row[0].onmouseover = '';
	row[0].onmouseout = '';

	// Display the events row
	$(row[0]).addClass('subscriberEventRowActive');
	//event_row.addClass('subscriberEventRowActive');

	if ($.browser.msie) {
		event_row.css('display','block');
	} else {
		event_row.css('display','table-row');
	}

	image[0].src = 'images/minus.gif';
}

// Returns the plus/minus image for a row
function subscriberEventsRowImage(subscriberid) {
	var row = $('#subscriber' + subscriberid);
	var image = row.children().filter('td.eventButton').children().filter('span.eventButton').children().filter('img');
	if (!image.length) { return false; }
	return image;
}

// Fetchs the events for a subscriber
function subscriberEventsUpdate(subscriberid,row,event_row,image) {
	var segment = (Application.Page.Subscriber_Manage? Application.Page.Subscriber_Manage.segmentID : (SegmentID? SegmentID : ''));
	if (segment != '') segment = '&SegmentID=' + segment;
	$.get('index.php?Page=Subscribers&Action=Event&SubAction=EventList&id=' + subscriberid + segment,'',
		function(data,textStatus) {
			$(event_row).children().filter('.dataCol').children().filter('.dataArea').html(data);
			subscriberEventsShowRow(row,event_row,image);
			tb_init('a.thickbox, area.thickbox, input.thickbox');
		}
	);
}

// Deletes an event for a subscriber, called when the Delete link is clicked
function subscriberEventDelete(subscriberid,eventid) {
	var segment = (Application.Page.Subscriber_Manage? Application.Page.Subscriber_Manage.segmentID : (SegmentID? SegmentID : ''));
	if (segment != '') segment = '&SegmentID=' + segment;
	if (confirm('%%LNG_ConfirmEventDelete%%')) {
		$.get('index.php?Page=Subscribers&Action=Event&SubAction=EventDelete&id=' + subscriberid + '&eventid=' + eventid + segment,'',
			function(data,textStatus) {
				eval(data);
			}
		);
	}

	return false;
}

// Deletes an event from the page. If no events remain it also deletes the plus/minus button
function subscriberEventDeleted(subscriberid,eventid) {
	// subscribers[9].length - 1;
	var event_row = $('#event_' + subscriberid + "_" + eventid);

	// Number of remaining events
	var events = $(event_row[0].parentNode).children().length - 1;

	// Hide the row
	event_row.css('display','none');
	// Remove it from the DOM
	event_row.remove();
	// Remove it from the array of events
	delete(subscribers[subscriberid][eventid]);

	if (events == 0) {
		// Remove the event +/- button
		subscriberEventsShow(subscriberid);
		image = subscriberEventsRowImage(subscriberid);
		image.css('display','none');
		image.remove();
	}

}

// Adds an event to the page and adds the plus/minus button if it is the first event
function subscriberEventAdded(subscriberid,event_button) {
	var row = $('#subscriber' + subscriberid);

	// Add the plus/minus button if there isn't one

	if (!subscriberEventsRowImage(subscriberid)) {
		row.children().filter('td.eventButton').children().filter('span.eventButton').html(event_button);
	}

	var image = subscriberEventsRowImage(subscriberid);

	var event_row = $('#subscriber' + subscriberid + '_Events');

	subscriberEventsUpdate(subscriberid,row,event_row,image);

	top.tb_remove();
}

$(document).ready(function(){
	resetForm();
});

var subscribers = new Array; // Array of subscriber event data

var subscriberid; // Subscriberid and listid the form is for
var listid;
var loadevent = []; // Load this event when loading the form (for editing an event)
var eventid; // Set to eventid when editing an event

// Show the loading indicator

$(document).ajaxSend(function() {
	$('.loadingImage').css('display','');
});
$(document).ajaxStop(function() {
	$('.loadingImage').css('display','none');
});
