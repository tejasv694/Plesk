<style type="text/css">@import url(includes/styles/ui.datepicker.css);</style>
<script src="includes/js/jquery/ui.js"></script>
<script>
	%%GLOBAL_CustomDatepickerUI%%
</script>
<script>
	var PAGE = {
		INIT_VALUES:					'%%GLOBAL_InitialValues%%',


		_cacheShortMonthNames:			null,
		_cacheShortDayNames:			null,
		_cacheMinDayNames:				null,

		_counterRowIndexes:				0,
		_queueRemove:					[],

		_disableRules:					false,
		_disableSubmit:					false,

		_disableRemoveRule:				0,

		_cacheRules:					{	'email':		{	'type':			'text',
																'value':		'email',
																'text':			'%%LNG_Email%%',
																'display':		true,
																'operatortype':	'text'},
											'format':		{	'type':			'dropdown',
																'value':		'format',
																'text':			'%%LNG_Format%%',
																'display':		true,
																'operatortype':	'dropdown'},
											'confirmation':	{	'type':			'dropdown',
																'value':		'confirmation',
																'text':			'%%LNG_ConfirmedStatus%%',
																'display':		true,
																'operatortype':	'dropdown'},
											'status':		{	'type':			'dropdown',
																'value':		'status',
																'text':			'%%LNG_FilterStatus%%',
																'display':		true,
																'operatortype':	'dropdown'},
											'subscribe':	{	'type':			'date',
																'value':		'subscribe',
																'text':			'%%LNG_FilterByDate%%',
																'display':		true,
																'operatortype':	'date'},
											'link':			{	'type':			'dropdown',
																'value':		'link',
																'text':			'%%LNG_ClickedOnLink%%',
																'display':		true,
																'operatortype':	'link'},
											'campaign':		{	'type':			'dropdown',
																'value':		'campaign',
																'text':			'%%LNG_OpenedNewsletter%%',
																'display':		true,
																'operatortype':	'campaign'}},
		_cacheRulesCustomFields:		[	{'value': 'isempty', 'text': '%%LNG_SegmentFormOperator_common_customfields_isempty%%'},
											{'value': 'isnotempty', 'text': '%%LNG_SegmentFormOperator_common_customfields_isnotempty%%'}],
		_cacheRuleOperations:			{	'text':		[	{'value': 'equalto', 'text': '%%LNG_SegmentFormOperator_text_equalto%%'},
															{'value': 'notequalto', 'text': '%%LNG_SegmentFormOperator_text_notequalto%%'},
															{'value': 'like', 'text': '%%LNG_SegmentFormOperator_text_like%%'},
															{'value': 'notlike', 'text': '%%LNG_SegmentFormOperator_text_notlike%%'}],
											'dropdown':	[	{'value': 'equalto', 'text': '%%LNG_SegmentFormOperator_dropdown_equalto%%'},
															{'value': 'notequalto', 'text': '%%LNG_SegmentFormOperator_dropdown_notequalto%%'}],
											'number':	[	{'value': 'equalto', 'text': '%%LNG_SegmentFormOperator_number_equalto%%'},
															{'value': 'notequalto', 'text': '%%LNG_SegmentFormOperator_number_notequalto%%'},
															{'value': 'greaterthan', 'text': '%%LNG_SegmentFormOperator_number_greaterthan%%'},
															{'value': 'lessthan', 'text': '%%LNG_SegmentFormOperator_number_lessthan%%'},
															{'value': 'between', 'text': '%%LNG_SegmentFormOperator_number_between%%'}],
											'multiple':	[	{'value': 'equalto', 'text': '%%LNG_SegmentFormOperator_multiple_equalto%%'},
															{'value': 'notequalto', 'text': '%%LNG_SegmentFormOperator_multiple_notequalto%%'}],
											'date': 	[	{'value': 'equalto', 'text': '%%LNG_SegmentFormOperator_date_equalto%%'},
															{'value': 'notequalto', 'text': '%%LNG_SegmentFormOperator_date_notequalto%%'},
															{'value': 'greaterthan', 'text': '%%LNG_SegmentFormOperator_date_greaterthan%%'},
															{'value': 'lessthan', 'text': '%%LNG_SegmentFormOperator_date_lessthan%%'},
															{'value': 'between', 'text': '%%LNG_SegmentFormOperator_date_between%%'}],
											'link':		[	{'value': 'equalto', 'text': '%%LNG_SegmentFormOperator_link_equalto%%'},
															{'value': 'notequalto', 'text': '%%LNG_SegmentFormOperator_link_notequalto%%'}],
											'campaign':	[	{'value': 'equalto', 'text': '%%LNG_SegmentFormOperator_campaign_equalto%%'},
															{'value': 'notequalto', 'text': '%%LNG_SegmentFormOperator_campaign_notequalto%%'}]},
		_cacheRuleValues:				{	'format':		[	{'value': 'h', 'text': '%%LNG_Format_HTML%%'},
																{'value': 't', 'text': '%%LNG_Format_Text%%'}],
											'confirmation':	[	{'value': '1', 'text': '%%LNG_Confirmed%%', 'selected': true},
																{'value': '0', 'text': '%%LNG_Unconfirmed%%'}],
											'status':		[	{'value': 'a', 'text': '%%LNG_Active%%'},
																{'value': 'b', 'text': '%%LNG_Bounced%%'},
																{'value': 'u', 'text': '%%LNG_Unsubscribed%%'}]},
		_cacheMailingList:				{},

		_htmlRemoveButton:				'&nbsp;<a href="#" class="RuleRowRemoveButton"><img src="images/delicon.gif" alt="remove" border="0" /></a>',

		_htmlAddButton:					'&nbsp;<a href="#" class="RuleRowAddButton"><img src="images/addicon.gif" alt="add" border="0" /></a>',

		_permanentRules: {				'email': true,
										'format': true,
										'confirmation': true,
										'status': true,
										'link': true,
										'campaign': true,
										'subscribe': true},



		init:							function() {
			$(document.frmSegmentEditor).submit(function(event) {
				event.preventDefault();
				event.stopPropagation();
			});

			$('.cancelButton').click(function() {
				if(confirm('%%LNG_SegmentFormAlertCancel%%')) Application.Util.submitGet('index.php', {Page:'Segment'});
			});

			$('.submitButton').click(function() { PAGE.submit(); });

			$('#SelectList', document.frmSegmentEditor).click(function() { PAGE.listChanged(); });

			$(document.frmSegmentEditor.MatchType).click(function() { PAGE.ruleAddMatchType(); });

			if(this.INIT_VALUES != '' && this.INIT_VALUES != '{}') {
				try {
					var data = eval('(' + PAGE.INIT_VALUES + ')');

					if(data.ruleCache) this._populateCache(data.ruleCache);

					if(data.rules) {
						// This is just a temporary measure of interpreting the data
						// for grouping segment rules
						for(var i = 0, j = data.rules[0].rules.length; i < j; ++i) {
							PAGE.ruleAddRow({	rulename: data.rules[0].rules[i].rules.ruleName,
												ruleoperator: data.rules[0].rules[i].rules.ruleOperator,
												rulevalue: data.rules[0].rules[i].rules.ruleValues},
											true);
						}
					}

					PAGE.ruleAddRow(true);
				} catch(e) {
					alert('%%LNG_SegmentFormAlertInitializingValues%%');
				}
			} else {
				for(var i = 0; i < 5; ++i) PAGE.ruleAddRow(true);
			}

			document.frmSegmentEditor.SegmentName.focus();
		},
		submit:							function() {
			if(this._disableSubmit) return;
			this._disableSubmit = true;

			var frm = document.frmSegmentEditor;
			var data = {
				SegmentID: frm.SegmentID.value,
				SegmentName: $.trim(frm.SegmentName.value).replace(/\"/g, '&quot;'),
				Lists: this.listGetSelected(),
				Rules: this.ruleGetAll()
			};

			if(data.SegmentName == '') {
				alert('%%LNG_SegmentFormAlertSpecifySegmentName%%');
				frm.SegmentName.focus();
				this._disableSubmit = false;
				return;
			}

			if(data.Lists.length == 0) {
				alert('%%LNG_SegmentFormAlertAtLeastOneList%%');
				this._disableSubmit = false;
				return;
			}

			if(data.Rules.length == 0) {
				alert('%%LNG_SegmentFormAlertAtLeastOneRule%%');
				this._disableSubmit = false;
				return;
			}

			Application.Util.submitPost('index.php?Page=Segment&Action=Save', data);
		},


		ruleAddRow:						function(data, noTransition) {
			if(this._disableRules) return;
			var rows = $('#sectionRuleContainer').children().get();
			var html = 	$(	'<div class="RuleRow RuleUnprocessed" width="100%" rowIndex="' + this._counterRowIndexes + '">' +
							'<select name="RuleField[' + this._counterRowIndexes + ']" class="RuleRowRuleName Field250" disabled="disabled" style="width: 200px;"></select>' +
							'&nbsp;<select name="RuleOperation[' + this._counterRowIndexes + ']" class="RuleRowRuleOperator Field250" disabled="disabled" style="width: 125px;"></select>' +
							'<span class="RuleRowRuleValues" style="width: 300px;"></span>' +
							'<span class="RuleRowRuleConnector" style="width: 30px;"></span>' +
							'<span class="RuleRowRuleActions">' + this._htmlAddButton + (rows.length > 0? this._htmlRemoveButton : '&nbsp;') + '</span>' +
							'</div>');

			if(!noTransition) $(html).hide();
			$(html).appendTo('#sectionRuleContainer');
			if(!noTransition) $(html).fadeIn('normal');

			if(rows.length == 1) {
				if($('.RuleRowRemoveButton', rows[0]).size() == 0) {
					var temp = $(this._htmlRemoveButton);
					if(!noTransition) $(temp).hide();
					$(temp).appendTo($('.RuleRowRuleActions', rows[0]));
					if(!noTransition) $(temp).fadeIn('normal');
					this._ruleApplyRemoveCommand(rows[0]);
				}
			}

			$('#sectionRuleContainer .RuleUnprocessed').each(function() {
				$('.RuleRowRuleName', this).change(function() { PAGE.ruleChanged($(this).parent().get(0)); });
				$('.RuleRowRuleOperator', this).change(function() { PAGE.ruleOperatorChanged($(this).parent().get(0)); });
				$('.RuleRowAddButton', this).click(function(event) { PAGE.ruleAddRow(); event.preventDefault(); event.stopPropagation(); });

				PAGE.ruleRefreshRow(this, data);

				$(this).removeClass('RuleUnprocessed');
			});

			++this._counterRowIndexes;

			this.ruleAddMatchType();
		},
		ruleRemoveRow:					function(row) {
			if(this._disableRemoveRule < 0) this._disableRemoveRule = 0;
			if(this._disableRules || this._disableRemoveRule != 0) return;
			if($('#sectionRuleContainer').children().length == 1) return;
			++this._disableRemoveRule;

			$(row).fadeOut('normal', function() {
				$(this).remove();

				var children = $('#sectionRuleContainer').children().get();
				if(children.length == 1) $('.RuleRowRemoveButton', children[0]).fadeOut('normal', function() { $(this).remove(); });

				--PAGE._disableRemoveRule;
				if(PAGE._queueRemove.length > 0) PAGE.ruleRemoveRow(PAGE._queueRemove.pop());

				PAGE.ruleAddMatchType();
			});
		},
		ruleAddMatchType:				function() {
			var matchType = '&nbsp;' + ($('#matchType_All, #matchType_Or', document.frmSegmentEditor).filter(':checked').val() == 'or'? '%%LNG_OR%%' : '%%LNG_AND%%') + '&nbsp;';
			var temp = $('.RuleRowRuleConnector');
			if(temp.size() > 0) {
				for(var i = 0, j = temp.size(); i < j; ++i) {
					$(temp.get(i)).css('visibility', '');
					$(temp.get(i)).html(matchType);
				}

				$(temp.get(temp.size() - 1)).css('visibility', 'hidden');
			}
		},
		ruleRefreshAllRows:				function() {
			$('#sectionRuleContainer').children().each(function(i,n) { PAGE.ruleRefreshRow(n); });
		},
		ruleRefreshRow:					function(row, data) {
			if(this._disableRules) return;
			if(this._isObjectEmpty(this._cacheRules)) return;

			var changed = false;
			var tempRuleName = $('.RuleRowRuleName', row).get(0);
			var tempRuleOperator = $('.RuleRowRuleOperator', row).get(0);

			changed = this._addRuleSelectOptions(tempRuleName, (data? data.rulename : null));
			$(tempRuleName).attr('disabled', false);

			if(changed)
				changed = this._addOperatorSelectOptions(	tempRuleName.options[tempRuleName.selectedIndex].value,
															tempRuleOperator,
															(data? data.ruleoperator : null));

			$(tempRuleOperator).attr('disabled', false);

			if(changed) this._adjustInputField(row, (data? data.rulevalue : null));

			this._ruleApplyRemoveCommand(row);
		},
		ruleDisable:					function(row) {
			if(row) $('input, select, a', row).attr('disabled', true);
			else {
				$('#sectionRuleContainer input, #sectionRuleContainer select, #sectionRuleContainer a').attr('disabled', true);
				this._disableRules = true;
			}
		},
		ruleEnable:						function(row) {
			if(row) $('input, select, a', row).attr('disabled', false);
			else {
				$('#sectionRuleContainer input, #sectionRuleContainer select, #sectionRuleContainer a').attr('disabled', false);
				this._disableRules = false;
			}
		},
		ruleChanged:					function(row) {
			var tempRuleName = $('.RuleRowRuleName', row).get(0);
			var tempRuleOperator = $('.RuleRowRuleOperator', row).get(0);

			switch($(tempRuleName).val()) {
				case 'link':
					if(!this._cacheRuleValues['link']) this._ajaxRequestLinks(row);
					else {
						this._addOperatorSelectOptions(tempRuleName.options[tempRuleName.selectedIndex].value, tempRuleOperator);
						this._adjustInputField(row);
					}
				break;
				case 'campaign':
					if(!this._cacheRuleValues['campaign']) this._ajaxRequestCampaigns(row);
					else {
						this._addOperatorSelectOptions(tempRuleName.options[tempRuleName.selectedIndex].value, tempRuleOperator);
						this._adjustInputField(row);
					}
				break;
				default:
					this._addOperatorSelectOptions(tempRuleName.options[tempRuleName.selectedIndex].value, tempRuleOperator);
					this._adjustInputField(row);
				break;
			}
		},
		ruleOperatorChanged:			function(row) {
			var tempRuleName = $('.RuleRowRuleName', row).get(0);
			var tempRuleOperator = $('.RuleRowRuleOperator', row).get(0);
			var tempRuleValues = $('.RuleRowRuleValues', row).get(0);
			var tempRuleOperatorValue = tempRuleOperator.options[tempRuleOperator.selectedIndex].value;

			var tempRuleValueChildren = $(tempRuleValues).children().filter('span');
			if(tempRuleValueChildren.size() > 1) {
				tempRuleValueChildren.filter(':first').attr('name', 'RuleValue[]');
				tempRuleValueChildren.filter(':gt(0)').fadeOut('normal', function() { $(this).remove(); });
			}

			if(tempRuleOperatorValue == 'isempty' || tempRuleOperatorValue == 'isnotempty') {
				$(tempRuleValues).find('select, input[type=text]').attr('disabled', true);
				$(tempRuleValues).find('img.datepicker_trigger').hide();
			} else {
				$(tempRuleValues).find('select, input[type=text]').attr('disabled', false);
				$(tempRuleValues).find('img.datepicker_trigger').show();
			}

			if(tempRuleOperatorValue == 'between') {
				var rule = this._cacheRules[tempRuleName.options[tempRuleName.selectedIndex].value];
				if(!rule) return;

				switch(rule.type) {
					case 'date':
						this._appendDateBetween(row);
					break;
					case 'number':
						this._appendNumberBetween(row);
					break;
				}
			}
		},
		ruleCheckAll:					function() {
			var okCount = 0;
			var rows = $('#sectionRuleContainer').children();

			for(var i = 0, j = rows.length; i < j; ++i) {
				if($.trim($($('.RuleRowUserInput', rows.get(i)).get(0)).val()) != '')
					++okCount;
			}

			return (okCount > 0);
		},
		ruleGetAll:						function() {
			var rules = [];
			var currentGroup = null;
			var rows = $('#sectionRuleContainer').children();

			for(var i = 0, j = rows.length; i < j; ++i) {
				var values = [];
				var userInput = $('.RuleRowUserInput', rows.get(i)).get();
				var ok = true;

				for(var k = 0, l = userInput.length; k < l; ++k) {
					if($(userInput[k]).is('input[type="checkbox"]') && !userInput[k].checked) continue;
					var temp = $(userInput[k]).val();
					if($.trim(temp) == '' && !userInput[k].disabled) { ok = false; break; }
					values.push(temp.replace(/\"/g, '&quot;'));
				}

				if(ok) {
					// This is just a placeholder data structure for the next version of segmenting
					// It is a hack so that the "back-end" will receive the expected data structure
					// UI must be changed in order to take full advantage of segmenting

					var selectedConnector = $('#matchType_All, #matchType_Or').filter(':checked').val();

					if(currentGroup == null)
						currentGroup = { type: 'group', connector: selectedConnector, rules: []};

					currentGroup.rules.push({	type: 'rule',
												connector: selectedConnector,
												rules: {	ruleName: $($('.RuleRowRuleName', rows.get(i)).get(0)).val(),
															ruleOperator: $($('.RuleRowRuleOperator', rows.get(i)).get(0)).val(),
															ruleValues: values }});
				}
			}

			if(currentGroup != null) rules.push(currentGroup);

			return rules;
		},


		listChanged:					function() {
			var el = this.getForm()['SelectList[]'];
			var request = [];
			var disabled = {};
			var enabled = {};
			var removed = {};

			for(var i = 0, j = el.options.length; i < j; ++i) {
				if(el.options[i].selected && !this._cacheMailingList[el.options[i].value]) {
					request.push(el.options[i].value);
				} else if(this._cacheMailingList[el.options[i].value]) {
					for(var k = 0, l = this._cacheMailingList[el.options[i].value].length; k < l; ++k) {
						if(el.options[i].selected) {
							enabled[this._cacheMailingList[el.options[i].value][k]] = true;
						} else {
							disabled[this._cacheMailingList[el.options[i].value][k]] = true;
						}
					}
				}
			}

			for(var i in this._cacheRules) {
				if(!!this._permanentRules[i]) continue;
				var temp = this._cacheRules[i].display;
				this._cacheRules[i].display = (!disabled[i] || !!enabled[i]);
				if(temp == true && this._cacheRules[i].display == false) removed[i] = true;
			}

			var ruleNames = $('.RuleRowRuleName');
			for(var i = 0, j = ruleNames.size(); i < j; ++i) {
				if(removed[$(ruleNames.get(i)).val()])
					this._queueRemove.push($(ruleNames.get(i)).parent().get(0));
			}
			if(this._queueRemove.length > 0) this.ruleRemoveRow(this._queueRemove.pop());

			if(request.length != 0) {
				this._listRequestListData(request);
			} else {
				this.ruleRefreshAllRows();
			}
		},
		listGetSelected:				function() {
			var el = this.getForm()['SelectList[]'];
			var selected = [];

			for(var i = 0, j = el.options.length; i < j; ++i) {
				if(el.options[i].selected) selected.push(el.options[i].value);
			}

			return selected;
		},
		listGetALLIDs:					function() {
			var el = this.getForm()['SelectList[]'];
			var output = [];

			for(var i = 0, j = el.options.length; i < j; ++i)
				output.push(el.options[i].value);

			return output;
		},



		getForm:						function() { return document.frmSegmentEditor; },

		_isObjectEmpty:					function(object) { for(var i in object) { return false; } return true; },
		_addRuleSelectOptions:			function(elm, data) {
			var changed = true;
			var selected = (elm.selectedIndex >= 0)? elm.options[elm.selectedIndex].value : null;
			$(elm).html('');

			var selectOptions = {'basic': [], 'customfields': []};

			for(var i  in this._cacheRules) {
				if(!this._cacheRules[i].display) continue;
				var temp = '<option value="' + this._cacheRules[i].value + '" title="' + this._cacheRules[i].text.replace(/"/, '&quot;') + '"';

				if(data) {
					if(this._cacheRules[i].value == data)
						temp += ' selected="selected"';
						elm.selectedIndex = elm.options.length - 1;
				} else {
					if (this._cacheRules[i].value == selected) {
						changed = false;
						temp += ' selected="selected"';
					}
				}

				temp += '>' + this._cacheRules[i].text + '</option>';

				if(this._permanentRules[i]) {
					selectOptions.basic.push(temp);
				} else {
					selectOptions.customfields.push(temp);
				}
			}

			$(	'<optgroup label="{$lang.SegmentCustomField_Basic}">' + selectOptions.basic.join('') + '</optgroup>'
				+ (selectOptions.customfields.length == 0? '' : '<optgroup label="{$lang.SegmentCustomField_CustomField}">' + selectOptions.customfields.join('') + '</optgroup>')).appendTo(elm);

			return changed;
		},
		_addOperatorSelectOptions:		function(type, elm, data) {
			if(!this._cacheRules[type]) return false;
			var customFields = (parseInt(type)? true : false);
			type = this._cacheRules[type].operatortype;
			if(!this._cacheRuleOperations[type]) return false;

			var selected = elm.selectedIndex;
			var options = this._cacheRuleOperations[type].slice();

			if(customFields) $.merge(options, this._cacheRulesCustomFields);
			elm.options.length = 0;

			for(var i = 0, j = options.length; i < j; ++i) {
				elm.options[elm.options.length] = new Option(options[i].text, options[i].value);
				if(data && options[i].value == data) elm.selectedIndex = elm.options.length - 1;
			}

			return (elm.selectedIndex != selected);
		},
		_ruleApplyRemoveCommand:		function(row) {
			$('.RuleRowRemoveButton', row).click(function(event) {
				event.preventDefault();
				event.stopPropagation();
				PAGE.ruleRemoveRow($(this).parent().parent().get(0));
			});
		},
		_listRequestListData:			function(listid) {
			if(listid.length == 0) return;

			this.ruleDisable();

			$.post(	'index.php?Page=Segment&Action=AJAX',
					{	'ajaxType': 'CustomFieldUsedByList',
						'listid[]': listid},
					function(response) { PAGE._listRequestListData_CB(response); });
		},
		_listRequestListData_CB:		function(response) {
			try { this._populateCache(eval('('+response+')')); }
			catch(e) { alert('%%LNG_SegmentFormAlertErrorRequestingData%%'); }

			this.ruleEnable();
			this.ruleRefreshAllRows();
		},
		_ajaxRequestLinks:				function(row) {
			this.ruleDisable(row);
			$.post(	'index.php?Page=Segment&Action=AJAX',
					{	'ajaxType':	'GetAvailableLinks',
						'listid[]':	this.listGetALLIDs()},
					function(response) {
						try {
							PAGE._cacheRuleValues['link'] = eval('('+response+')');
						} catch(e) { alert('%%LNG_SegmentFormAlertErrorRequestingData%%'); }

						if(row) {
							PAGE.ruleEnable(row);
							PAGE._addOperatorSelectOptions($($('.RuleRowRuleName', row).get(0)).val(), $('.RuleRowRuleOperator', row).get(0));
							PAGE._adjustInputField(row);
						}
					});
		},
		_ajaxRequestCampaigns:			function(row) {
			this.ruleDisable(row);
			$.post(	'index.php?Page=Segment&Action=AJAX',
					{	'ajaxType':	'GetAvailableCampaigns'},
					function(response) {
						try {
							PAGE._cacheRuleValues['campaign'] = eval('('+response+')');
						} catch(e) { alert('%%LNG_SegmentFormAlertErrorRequestingData%%'); }

						if(row) {
							PAGE.ruleEnable(row);
							PAGE._addOperatorSelectOptions($($('.RuleRowRuleName', row).get(0)).val(), $('.RuleRowRuleOperator', row).get(0));
							PAGE._adjustInputField(row);
						}
					});
		},
		_populateCache:					function(cache) {
			if(cache.customfields) {
				for(var i in cache.customfields) {
					var each = cache.customfields[i];
					this._cacheRules[i] = {
						'type': each.fieldtype,
						'value': i,
						'text': each.name,
						'display': true,
						'operatortype': each.operatortype
					};
				}
			}

			if(cache.values) {
				for(var i in cache.values) {
					this._cacheRuleValues[i] = cache.values[i];
				}

			}

			if(cache.list) {
				for(var i in cache.list)
					this._cacheMailingList[i] = cache.list[i];
			}
		},
		_adjustInputField:				function(row, data) {
			var ruleName = $('.RuleRowRuleName', row).get(0);
			var ruleOperator = $('.RuleRowRuleOperator', row).get(0);
			var ruleValues = $('.RuleRowRuleValues', row).get(0);
			var ruleType = this._cacheRules[ruleName.options[ruleName.selectedIndex].value].type;
			var ruleOperatorValue = ruleOperator[ruleOperator.selectedIndex].value;

			if(ruleName.selectedIndex >= 0) {
				$(ruleValues).children().remove();

				switch(ruleType) {
					case 'number':
						var temp = $('<input type="text" class="RuleRowUserInput Field250 NumberInputType" name="RuleValue[' + $(row).attr('rowIndex') + '][]" style="width: 150px;" />');
						temp.appendTo(ruleValues);
						this._applyNumberInputboxBehaviour(temp.get(0));

						if(ruleOperator.options[ruleOperator.selectedIndex].value == 'between')
							this._appendNumberBetween(row);
					break;
					case 'date':
						var temp = $('<span><input type="text" readonly="readonly" class="RuleRowUserInput Field250 DateInputType" name="RuleValue[' + $(row).attr('rowIndex') + '][]" style="width: 125px;" /></span>');
						temp.appendTo(ruleValues);

						$(temp.children().get(0)).datepicker({yearRange:'-100:+100'});

						if(ruleOperator.options[ruleOperator.selectedIndex].value == 'g')
							this._appendDateBetween(row);
					break;
					case 'radiobutton':
					case 'dropdown':
						var optionsHTML = '';
						var options = this._cacheRuleValues[ruleName.options[ruleName.selectedIndex].value];
						if(options) {
							for (var i = 0, j = options.length; i < j; ++i) {
								optionsHTML += 	'<option value="' + options[i].value + '"' +
												'" title="' + (options[i].title? options[i].title.replace(/"/, '&quot;') : options[i].text.replace(/"/, '&quot;')) + '"' +
												(options[i].selected? 'selected="selected"' : '') + '>' +
												options[i].text + '</option>';
							}
						}

						$('<select class="RuleRowUserInput Field250" name="RuleValue[' + $(row).attr('rowIndex') + ']" style="width:150px;">' + optionsHTML + '</select>').appendTo(ruleValues);
					break;
					case 'checkbox':
						var tempSelectorPlaceholderHTML = '<div id="RuleValue_MultipleSelectorPlaceholder_' + $(row).attr('rowIndex') + '" style="display:none;">';
						var options = this._cacheRuleValues[ruleName.options[ruleName.selectedIndex].value];
						if(options) {
							if(!data) data = [];
							var tempDisplay = [];

							for(var i = 0, j = options.length; i < j; ++i) {
								var tempSelected = '';

								if(jQuery.inArray(options[i].value, data) != -1) {
									tempSelected = ' checked="checked"';
									tempDisplay.push(options[i].text);
								}

								tempSelectorPlaceholderHTML += 	'<input type="checkbox"'+
																' name="RuleValue[' + $(row).attr('rowIndex') + '][]"'+
																' title="' + options[i].text + '"'+
																' id="' + 'RuleValue_' + $(row).attr('rowIndex') + '_' + i + '"' +
																' value="'+ options[i].value +'"'+
																' class="RuleRowUserInput CheckboxInputType"'+ tempSelected + ' />';
								tempSelectorPlaceholderHTML += '<label for="' + 'RuleValue_' + $(row).attr('rowIndex') + '_' + i + '">';
								tempSelectorPlaceholderHTML += options[i].text;
								tempSelectorPlaceholderHTML += '</label><br/>';
							}

							data = tempDisplay;
						}
						tempSelectorPlaceholderHTML += '</div>';
						$(tempSelectorPlaceholderHTML).appendTo(ruleValues);

						var tempOuputPlaceholder = $('<input id="RuleValue_Placeholder_' + $(row).attr('rowIndex') + '" type="text" readonly="readonly" value="'+ (data? data.join(', ') : '%%LNG_SegmentFormCheckbox_SelectInstruction%%') +'" title="%%LNG_SegmentFormCheckbox_SelectTooltip%%" />');
						tempOuputPlaceholder.appendTo(ruleValues);
						tempOuputPlaceholder.click(function() {
							var id = $(this).attr('id').match(/RuleValue_Placeholder_(\d*)/)[1];
							tb_show('%%LNG_SegmentFormCheckbox_SelectWindowTitle%%', '#TB_inline?height=300&width=400&inlineId=RuleValue_MultipleSelectorPlaceholder_'+id, false);
						});

						$('.RuleRowUserInput', row).click(function() {
							var temp = $('.RuleRowUserInput', $(this).parent().get(0)).get();
							var values = [];
							for(var i = 0, j = temp.length; i < j; ++i) if(temp[i].checked) values.push(temp[i].title);

							var rowid = $(this).attr('id').match(/RuleValue_(\d*?)_/)[1];
							$('#RuleValue_Placeholder_' + rowid).val(values.join(', '));
						});
					break;
					case 'textarea':
					case 'text':
					default:
						var temp = $('<input type="text" class="RuleRowUserInput Field250 TextInputType" name="RuleValue[' + $(row).attr('rowIndex') + ']" style="width: 150px;" />');
						temp.appendTo(ruleValues);
						temp.focus(function() { this.select(); });
					break;
				}
			}

			if(data) {
				var userInput = $('.RuleRowUserInput', row);
				if(ruleType != 'checkbox') {
					if(isArray(data)) {
						for(var i = 0, j = data.length; i < j; ++i) {
							if(userInput.get(i))
								$(userInput.get(i)).val(data[i]);
						}

					} else $(userInput.get(0)).val(data);
				}
			}

			if(ruleOperatorValue == 'isempty' || ruleOperatorValue == 'isnotempty') {
				$(ruleValues).find('select, input[type=text]').attr('disabled', true);
				$(ruleValues).find('img.datepicker_trigger').hide();
			} else {
				$(ruleValues).find('select, input[type=text]').attr('disabled', false);
				$(ruleValues).find('img.datepicker_trigger').show();
			}
		},
		_applyNumberInputboxBehaviour:	function(inputbox) {
			$(inputbox).focus(function() { this.select(); });
			$(inputbox).keypress(function(event){
				if(event.which > 31 && (event.which < 48 || event.which > 57) && event.which != 46 && event.which != 44 && event.which != 45) {
					event.stopPropagation();
					event.preventDefault();
				}
			});
		},
		_appendDateBetween:				function(row) {
			var temp = $('<span>&nbsp;%%LNG_AND%%&nbsp;<input type="text" readonly="readonly" class="RuleRowUserInput" name="RuleValue[' + $(row).attr('rowIndex') + '][]" /></span>');
			temp.appendTo($('.RuleRowRuleValues', row).get(0));
			$(temp.children().get(0)).datepicker({yearRange:'-100:+100'});
		},
		_appendNumberBetween:			function(row) {
			var temp = $('<span>&nbsp;%%LNG_AND%%&nbsp;<input type="text" class="RuleRowUserInput" name="RuleValue[' + $(row).attr('rowIndex') + '][]" /></span>');
			temp.appendTo($('.RuleRowRuleValues', row).get(0));
			this._applyNumberInputboxBehaviour(temp.get(0));
		}
	};


	$(function() { PAGE.init(); });
</script>
<form name="frmSegmentEditor" id="frmSegmentEditor" method="post" action="index.php">
	<input type="hidden" name="SegmentID" value="%%GLOBAL_SegmentID%%" />
	<table cellspacing="0" cellpadding="3" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%GLOBAL_Heading%%
			</td>
		</tr>
		<tr>
			<td class="body">
				%%GLOBAL_Intro%%
			</td>
		</tr>
		<tr>
			<td>
				%%GLOBAL_Message%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton submitButton" type="button" value="%%LNG_Save%%">
				<input class="FormButton cancelButton" type="button" value="%%LNG_Cancel%%" />
				<br />&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_SegmentFormHeaderDetails%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel" width="10%">
							<img src="images/blank.gif" width="200" height="1" /><br />
							{template="Required"}
							%%LNG_SegmentName%%:&nbsp;
						</td>
						<td width="90%">
							<input type="text" name="SegmentName" class="Field250 form_text" style="width:445px" value="%%GLOBAL_SegmentName%%" />%%LNG_HLP_SegmentName%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_SegmentFormFieldMailingList%%:&nbsp;
						</td>
						<td>
							<select id="SelectList" name="SelectList[]" multiple="multiple" class="ISSelectReplacement ISSelectSearch" style="%%GLOBAL_SelectListStyle%%">
								%%GLOBAL_SelectListHTML%%
							</select>&nbsp;%%LNG_HLP_SegmentFormFieldMailingList%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_SegmentFormFieldMatchType%%:&nbsp;
						</td>
						<td>
							<input type="radio" id="matchType_All" name="MatchType" value="and"%%GLOBAL_MatchType_AND%%/><label for="matchType_All">%%LNG_SegmentFormMatchAllRule%%</label>
							&nbsp;%%LNG_HLP_SegmentFormFieldMatchType%%
							<br />
							<input type="radio" id="matchType_Or" name="MatchType" value="or"%%GLOBAL_MatchType_OR%%/><label for="matchType_Or">%%LNG_SegmentFormMatchAnyRule%%</label>
						</td>
					</tr>
					<tr><td class="EmptyRow" colspan="2">&nbsp;</td></tr>
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_SegmentFormHeaderRules%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel" valign="top">
							<img src="images/blank.gif" width="200" height="1" /><br />
							{template="Required"}
							%%LNG_SegmentFormRuleDescription%%:
						</td>
						<td><div id="sectionRuleContainer"></div></td>
					</tr>
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel">&nbsp;</td>
						<td valign="top" height="30">
							<input class="FormButton submitButton" type="button" value="%%LNG_Save%%" />
							<input class="FormButton cancelButton" type="button" value="%%LNG_Cancel%%" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
