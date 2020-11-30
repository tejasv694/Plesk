// This is a supplementary JavaScript that must be parsed in by IEM engine in order to further customize
// JQuery's ui.datepicker, should be included as an inline script due to the fact that this has to be parsed in by the templating engine

$.extend($.datepicker,{
	CUSTOM_IEM_MONTH_NAMES:				[	'%%LNG_Jan%%', '%%LNG_Feb%%', '%%LNG_Mar%%', '%%LNG_Apr%%',
											'%%LNG_May%%', '%%LNG_Jun%%', '%%LNG_Jul%%', '%%LNG_Aug%%',
											'%%LNG_Sep%%', '%%LNG_Oct%%', '%%LNG_Nov%%', '%%LNG_Dec%%'],
	CUSTOM_IEM_DAY_NAMES:				['%%LNG_Sun%%', '%%LNG_Mon%%', '%%LNG_Tue%%', '%%LNG_Wed%%', '%%LNG_Thu%%', '%%LNG_Fri%%', '%%LNG_Sat%%'],

	customIEM_ShortenArrayString:		function(source, strlength) {
		var temp = [];
		for(var i = 0, j = source.length; i < j; ++i)
			temp.push(source[i].substring(0,strlength));
		return temp;
	}
});

$.datepicker.setDefaults({	speed: '',
							showOn: 'both',
							buttonImage: 'images/calendar.gif',
							buttonImageOnly: true,
							dateFormat: 'dd/mm/yy',
							monthNames: $.datepicker.CUSTOM_IEM_MONTH_NAMES,
							monthNamesShort: $.datepicker.customIEM_ShortenArrayString($.datepicker.CUSTOM_IEM_MONTH_NAMES, 3),
							dayNames: $.datepicker.CUSTOM_IEM_DAY_NAMES,
							dayNamesShort: $.datepicker.customIEM_ShortenArrayString($.datepicker.CUSTOM_IEM_DAY_NAMES, 3),
							dayNamesMin: $.datepicker.customIEM_ShortenArrayString($.datepicker.CUSTOM_IEM_DAY_NAMES, 2)});