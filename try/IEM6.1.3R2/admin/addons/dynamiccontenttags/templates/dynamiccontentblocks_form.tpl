<style type="text/css">@import url(includes/styles/ui.datepicker.css);</style>
<script src="includes/js/jquery/ui.js"></script>
<script src="includes/js/jquery/jquery.json-1.3.min.js"></script>
<script src="includes/js/jquery/plugins/jquery.plugin.js"> </script>
<script src="includes/js/jquery/plugins/jquery.window.js"> </script>
<script src="includes/js/jquery/plugins/jquery.window-extensions.js"> </script>
<script src="includes/js/imodal/imodal.js"></script>
<script>
	%%GLOBAL_CustomDatepickerUI%%
</script>
<script>
	var PAGE = {
		_counterRowIndexes:				0,
		_queueRemove:					[],
		_cacheExistingRules:			[],
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
											'campaign':		{	'type':			'dropdown',
																'value':		'campaign',
																'text':			'%%LNG_OpenedNewsletter%%',
																'display':		true,
																'operatortype':	'campaign'}},
		_cacheRulesCustomFields:		[],
		_cacheRuleOperations:			{	'text':		[	{'value': 'equalto', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_text_equalto%%'},
															{'value': 'notequalto', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_text_notequalto%%'},
															{'value': 'like', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_text_like%%'},
															{'value': 'notlike', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_text_notlike%%'}],
											'dropdown':	[	{'value': 'equalto', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_dropdown_equalto%%'},
															{'value': 'notequalto', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_dropdown_notequalto%%'}],
											'number':	[	{'value': 'equalto', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_number_equalto%%'},
															{'value': 'notequalto', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_number_notequalto%%'},
															{'value': 'greaterthan', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_number_greaterthan%%'},
															{'value': 'lessthan', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_number_lessthan%%'}],
											'multiple':	[	{'value': 'equalto', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_multiple_equalto%%'},
															{'value': 'notequalto', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_multiple_notequalto%%'}],
											'date': 	[	{'value': 'equalto', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_date_equalto%%'},
															{'value': 'notequalto', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_date_notequalto%%'},
															{'value': 'greaterthan', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_date_greaterthan%%'},
															{'value': 'lessthan', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_date_lessthan%%'}],
											'campaign':	[	{'value': 'equalto', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_campaign_equalto%%'},
															{'value': 'notequalto', 'text': '%%LNG_Addon_dynamiccontenttagsOperator_campaign_notequalto%%'}]},
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
										'campaign': true,
										'subscribe': true},



		init:							function() {
			var frm = document.frmDynamicContentBlocks;
			var existingBlockid = frm.id_blockid.value;
			this._initCustomFields();
			 $(document.frmDynamicContentBlocks).submit(function(event) {
				 event.preventDefault();
				 event.stopPropagation();
			 });

			$('.blockCancelButton').click(function() {
				if(confirm('%%LNG_Addon_dynamiccontenttagsOperator_FormAlertCancel%%')) win.close();
			});

			$('.blockSubmitButton').click(function() { PAGE.submit(); });

			$(document.frmDynamicContentBlocks.MatchType).click(function() { PAGE.ruleAddMatchType();});
			// adding the value for existing block
			if ($('#blockid_'+existingBlockid+'_data').length > 0) {
				var encodedBlockData = $('#blockid_'+existingBlockid+'_data').val();
				var ExistingBlockName = $.evalJSON(encodedBlockData).BlockName;
				var ExistingBlockDefaultSet = $('#blockid_'+existingBlockid+'_activated').val();
				var ExistingBlockSortOrder = $('#blockid_'+existingBlockid+'_sortorder').val();
				var ExistingBlockRules = $.evalJSON(encodedBlockData).Rules;
				var ExistingBlockContent = $.evalJSON(encodedBlockData).Content;
				$('#id_dynamiccontenttags_block_name').val(ExistingBlockName);
				if ($('#blockcontent_html').length > 0) {
					$('#blockcontent_html').val(ExistingBlockContent);
				} else {
					$('#blockcontent').val(ExistingBlockContent);
				}
				if (ExistingBlockDefaultSet == 1) {
					$('#dynamiccontenttags_block_defaultset').attr('CHECKED', 'CHECKED');
				}
                                if (ExistingBlockSortOrder >= 0) {
                                    $('#sortorder_blockid').val(ExistingBlockSortOrder);
                                }

				try {
					if(ExistingBlockRules) {
						for(var i = 0, j = ExistingBlockRules[0].rules.length; i < j; ++i) {
							PAGE._cacheExistingRules.push({rulename: ExistingBlockRules[0].rules[i].rules.ruleName,
												ruleoperator: ExistingBlockRules[0].rules[i].rules.ruleOperator,
												rulevalue: ExistingBlockRules[0].rules[i].rules.ruleValues});
							PAGE.ruleAddRow({rulename: ExistingBlockRules[0].rules[i].rules.ruleName,
												ruleoperator: ExistingBlockRules[0].rules[i].rules.ruleOperator,
												rulevalue: ExistingBlockRules[0].rules[i].rules.ruleValues}, true);
						}
					}
					PAGE.ruleAddRow(true);
				} catch(e) {
					alert('%%LNG_Addon_dynamiccontenttags_BlockAlertInitValues%%');
				}
			} else {
					PAGE.ruleAddRow(true);
			}
			if ($('.class_defaultset').text()  == '' || $('#id_defaultset_' + existingBlockid).text()  == '{$lang.Addon_dynamiccontenttags_Block_DefaultString}') {
				$('#dynamiccontenttags_block_defaultset').attr('CHECKED', 'CHECKED');
				$('#dynamiccontenttags_block_defaultset').attr('DISABLED', 'DISABLED');
			}

			$('.HelpToolTip').hover(
			function() {
				$('<div class="HelpToolTip_Placeholder" style="display:inline; position: absolute; width: 240px; background-color: #FEFCD5; border: solid 1px #E7E3BE; padding: 10px;">'
					+ '<span class="helpTip"><b>' + $(this).children('.HelpToolTip_Title').text() + '</b></span>'
					+ '<br /><img src="images/1x1.gif" width="1" height="5" />'
					+ '<br /><div style="padding-left: 10px; padding-right: 5px;font-weight:normal;">' + $(this).children('.HelpToolTip_Contents').text() + '</div>'
					+ '</div>').appendTo(this);
			},
			function() {
				$('div.HelpToolTip_Placeholder', this).remove();			}
			);

			document.frmDynamicContentBlocks.dynamiccontenttags_block_name.focus();

		},
		submit:							function() {
			if(this._disableSubmit) return;
			this._disableSubmit = true;

			var frm = document.frmDynamicContentBlocks;
                        var existingTagid = frm.id_tagid.value;

                        var maxSortOrder = frm.sortorder_blockid.value;
                        if (frm.sortorder_blockid.value < 0) {
                            $('.blocksortorder_class').each(function() {
                                if (maxSortOrder < $(this).val()) {
                                    maxSortOrder = $(this).val()
                                }
                            });
                        }


			var blockContent = '';
			if (Application.WYSIWYGEditor.isWysiwygEditorActive()) {
				blockContent = Application.WYSIWYGEditor.getContent();
                                blockContent = blockContent.replace(/\n/g, "");
                                blockContent = blockContent.replace(/<meta.*\/>/g, "");
                                blockContent = blockContent.replace(/<head.*\/head>/g, "");
                                blockContent = blockContent.replace( /<(\/?)html.*?>/g, "");
                                blockContent = blockContent.replace(/<(\/?)body.*?>/g, "");
			} else {
				blockContent = $('#blockcontent_html').val();
                                blockContent = blockContent.replace(/\n/g, "");
                                blockContent = blockContent.replace(/<meta.*\/>/g, "");
                                blockContent = blockContent.replace(/<head.*\/head>/g, "");
                                blockContent = blockContent.replace( /<(\/?)html.*?>/g, "");
                                blockContent = blockContent.replace(/<(\/?)body.*?>/g, "");
			}

			var DefaultSetBit = 0;
			if (frm.dynamiccontenttags_block_defaultset.checked) {
				DefaultSetBit = 1;
			}
			
			var data = {
				BlockID: frm.id_blockid.value,
				BlockDefaultSet: DefaultSetBit,
				BlockSortOrder: maxSortOrder,
				BlockName: $.trim(frm.dynamiccontenttags_block_name.value).replace(/\"/g, '&quot;'),
				Rules: this.ruleGetAll(),
				Content: blockContent.replace(/'/g, "&#39;")
			};

			if(data.BlockName == '') {
				alert('%%LNG_Addon_dynamiccontenttagsOperator_FormAlertSpecifyBlockName%%');
				frm.dynamiccontenttags_block_name.focus();
				this._disableSubmit = false;
				return;
			}

			if(data.Rules.length == 0) {
				alert('%%LNG_Addon_dynamiccontenttagsOperator_FormAlertAtLeastOneRule%%');
				this._disableSubmit = false;
				return;
			}

 			var encoded = $.toJSON(data);

                        if (existingTagid > 0) {
                            $.post('index.php?Page=Addons&Addon=dynamiccontenttags&Action=updateblock&ajax=1',
                                {'blockid': frm.id_blockid.value
                                , 'tagid': existingTagid
                                , 'name': $.trim(frm.dynamiccontenttags_block_name.value).replace(/\"/g, '&quot;')
                                , 'rules': encoded
                                , 'activated': DefaultSetBit
                                , 'sortorder': maxSortOrder
                                },
                                function(response) {
                                    BlockInterface.Add(response,$.evalJSON(encoded).BlockName, $.evalJSON(encoded).BlockDefaultSet, $.evalJSON(encoded).BlockSortOrder, encoded);
                                });
                        } else {
                            BlockInterface.Add($.evalJSON(encoded).BlockID,$.evalJSON(encoded).BlockName, $.evalJSON(encoded).BlockDefaultSet, $.evalJSON(encoded).BlockSortOrder, encoded);
                        }

                        win.close();
			var mesg = '{$lang.Addon_dynamiccontenttags_UpdateBlock_Success}';
                        if (existingTagid > 0 && $.evalJSON(encoded).BlockID.length == 32) {
                            mesg = '{$lang.Addon_dynamiccontenttags_CreateFullBlock_Success}';
                        } else if ($.evalJSON(encoded).BlockID.length == 32) {
                            mesg = '{$lang.Addon_dynamiccontenttags_CreateBlock_Success}';
			}
			$('#FlashMessages').html('<div class="FlashSuccess"><img class="FlashSuccess" align="left" width="18" height="18" src="images/success.gif"/>'+mesg+'</div>');
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
			var matchType = '&nbsp;' + ($('#matchType_All, #matchType_Or', document.frmDynamicContentBlocks).filter(':checked').val() == 'or'? '%%LNG_OR%%' : '%%LNG_AND%%') + '&nbsp;';
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
			$('#sectionRuleContainer').children().each(function(i,n) {
					PAGE.ruleRefreshRow(n, PAGE._cacheExistingRules[0]);
					PAGE._cacheExistingRules.shift();
			});
		},
		ruleRefreshRow:					function(row, data) {
			if(this._disableRules) return;
			if(this._isObjectEmpty(this._cacheRules)) return;
			var changed = false;
			var tempRuleName = $('.RuleRowRuleName', row).get(0);
			var tempRuleOperator = $('.RuleRowRuleOperator', row).get(0);
			changed = this._addRuleSelectOptions(tempRuleName, (data? data.rulename : null));
			$(tempRuleName).attr('disabled', false);


                      if (tempRuleName.options[tempRuleName.selectedIndex].value == 'campaign' && !this._cacheRuleValues['campaign']) {

                        this.ruleDisable(row);
                        this._ajaxRequestCampaigns(row, data.rulevalue);

                      }
                	changed = this._addOperatorSelectOptions(	tempRuleName.options[tempRuleName.selectedIndex].value,
															tempRuleOperator,
															(data? data.ruleoperator : null));
        		this._adjustInputField(row, (data? data.rulevalue : null));

			$(tempRuleOperator).attr('disabled', false);

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
				case 'campaign':
					if(!this._cacheRuleValues['campaign']) this._ajaxRequestCampaigns(row, null);
        				this._addOperatorSelectOptions(tempRuleName.options[tempRuleName.selectedIndex].value, tempRuleOperator);
					this._adjustInputField(row);
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
		ruleGetAll:						function() {
			var rules = [];
			var currentGroup = null;
			var rows = $('#sectionRuleContainer').children();

			$('#sectionRuleContainer').each(function() {
				var values = [];
				var ruleName = '';
				var ruleOperator = '';
				var ruleValue = '';
				var ruleType = '';
				var ok = false;
				$(this).children('.RuleRow').children().each(function() {
					if ($(this).attr('class') != 'RuleRowRuleConnector' && $(this).attr('class') != 'RuleRowRuleActions') {
						var rowIndex = $(this).parent().attr('rowindex');
						if($(this).hasClass('RuleRowRuleName')) {
							ruleName = $(this).val();
						} else if ($(this).hasClass('RuleRowRuleOperator')) {
							ruleOperator = $(this).val();
						} else if ($(this).hasClass('RuleRowRuleValues')) {
                            ruleType = $('#ruletypeid_' + rowIndex).val();

							// for checkboxes
							if ($(this).children('.rulecbrow').length) {
								ruleValue = $(this).children('#RuleValue_Placeholder_' + rowIndex).val();
							} else if ($(this).children('.ruledate').length) {
								ruleValue = $(this).children('.ruledate').children('input').val();
							} else { // general input
								ruleValue = $(this).children('.RuleRowUserInput').val();
							}

							if($.trim(ruleValue) != '') {
								values.push(ruleValue.replace(/\"/g, '&quot;'));
								var selectedConnector = $('#matchType_All').val();

								if(currentGroup == null) {
									currentGroup = { type: 'group', connector: selectedConnector, rules: []};
								}
								currentGroup.rules.push({ type: 'rule',
                                                                    connector: selectedConnector,
                                                                    rules: {ruleName: ruleName,
                                                                        ruleOperator: ruleOperator,
                                                                        ruleValues: ruleValue,
                                                                        ruleType: ruleType
                                                                    }});
							}
						}
					}
				});
			});
			if(currentGroup != null)
				rules.push(currentGroup);
			return rules;
		},
		getForm:						function() { return document.frmDynamicContentBlocks; },
		getTagForm:						function() { return document.frmDynamicContentTagsEdit; },

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

			$(	'<optgroup label="{$lang.Addon_dynamiccontenttags_Basic}">' + selectOptions.basic.join('') + '</optgroup>'
				+ (selectOptions.customfields.length == 0? '' : '<optgroup label="{$lang.Addon_dynamiccontenttags_CustomField}">' + selectOptions.customfields.join('') + '</optgroup>')).appendTo(elm);

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
		_initCustomFields: function() {
			var el = this.getTagForm()['SelectList[]'];
			var request = [];
			var disabled = {};
			var enabled = {};
			var removed = {};

			for(var i = 0; i < el.options.length; i++) {
				if(el.options[i].selected && !this._cacheMailingList[el.options[i].value]) {
					request.push(el.options[i].value);
				}
			}
			if(request.length != 0) {
				this._listRequestListData(request);
			}
		},

		_listRequestListData: function(listid) {
			if(listid.length == 0) return;
			$.post(	'index.php?Page=Addons&Addon=dynamiccontenttags&Action=customfieldusedbylist&ajax=1',
				{	'ajaxType': 'CustomFieldUsedByList',
					'listid[]': listid},
					function(response) { PAGE._listRequestListData_CB(response); });
		},
		_listRequestListData_CB:		function(response) {
			var cache = eval('('+response+')');
			try { this._populateCache(eval('('+response+')')); }
			catch(e) { alert('%%LNG_Addon_dynamiccontenttags_AlertErrorRequestingData%%'); }
			this.ruleRefreshAllRows();
		},

		_ajaxRequestCampaigns:			function(row, dataval) {
			this.ruleDisable(row);
			$.post(	'index.php?Page=Segment&Action=AJAX',
					{	'ajaxType':	'GetAvailableCampaigns'},
					function(response) {
						try {
							PAGE._cacheRuleValues['campaign'] = eval('('+response+')');
						} catch(e) { alert('%%LNG_Addon_dynamiccontenttags_AlertErrorRequestingData%%'); }

						if(row) {
							PAGE.ruleEnable(row);
							PAGE._adjustInputField(row, dataval);
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
                        var ruleTypeHtml = ' <input type="hidden" value="'+ruleType+'" name="ruletype" id="ruletypeid_'+$(row).attr('rowIndex')+'" /> ';
			if(ruleName.selectedIndex >= 0) {
				$(ruleValues).children().remove();

				switch(ruleType) {
					case 'number':
						var temp = $(ruleTypeHtml + '<input type="text" class="RuleRowUserInput Field250 NumberInputType" name="RuleValue[' + $(row).attr('rowIndex') + '][]" style="width: 150px;" />');
						temp.appendTo(ruleValues);
						this._applyNumberInputboxBehaviour(temp.get(0));

						if(ruleOperator.options[ruleOperator.selectedIndex].value == 'between')
							this._appendNumberBetween(row);
					break;
					case 'date':
						var temp = $(ruleTypeHtml + '<span class="ruledate"><input type="text" readonly="readonly" class="RuleRowUserInput Field250 DateInputType" name="RuleValue[' + $(row).attr('rowIndex') + '][]" style="width: 125px;" /></span>');
						temp.appendTo(ruleValues);

						$(temp.children().get(0)).datepicker({yearRange:'-100:+100'});
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

						$(ruleTypeHtml + '<select class="RuleRowUserInput Field250" name="RuleValue[' + $(row).attr('rowIndex') + ']" style="width:150px;">' + optionsHTML + '</select>').appendTo(ruleValues);
					break;
					case 'checkbox':
						var tempSelectorPlaceholderHTML = ruleTypeHtml + '<div class="rulecbrow" id="RuleValue_MultipleSelectorPlaceholder_' + $(row).attr('rowIndex') + '" style="display:none;">';
						var options = this._cacheRuleValues[ruleName.options[ruleName.selectedIndex].value];
						existingData = data;
						if(options) {
							if(!data) {
								data = [];
							}
							var tempDisplay = [];

							for(var i = 0, j = options.length; i < j; ++i) {
								var tempSelected = '';
								if(jQuery.inArray(options[i].value, data) != -1) {
									tempSelected = ' checked="checked"';
									tempDisplay.push(options[i].text);
								}

								tempSelectorPlaceholderHTML += 	'<input type="checkbox" onclick="PAGE.popSelect($(this));" '+
																' name="RuleValue[' + $(row).attr('rowIndex') + '][]"'+
																' title="' + options[i].text + '"'+
																' id="' + 'RuleValue_' + $(row).attr('rowIndex') + '_' + i + '"' +
																' value="'+ options[i].value +'"'+
																' class="RuleRowUserInput CheckboxInputType"'+ tempSelected + ' />';
								tempSelectorPlaceholderHTML += options[i].text + '<br />';
							}

							data = tempDisplay;
						}
						tempSelectorPlaceholderHTML += '</div>';
						$(tempSelectorPlaceholderHTML).appendTo(ruleValues);

						var defValue = '';
						if (existingData && isArray(existingData)) {
							defValue = existingData.join(', ');
						} else if (existingData) {
							defValue = existingData;
						} else {
							defValue = '%%LNG_Addon_dynamiccontenttags_SelectInstruction%%';
						}
						var tempOuputPlaceholder = $('<input id="RuleValue_Placeholder_' + $(row).attr('rowIndex') + '" type="text" readonly="readonly" value="'+ defValue +'" title="%%LNG_Addon_dynamiccontenttags_SelectTooltip%%" />');
						tempOuputPlaceholder.appendTo(ruleValues);
						tempOuputPlaceholder.click(function() {
							var id = $(this).attr('id').match(/RuleValue_Placeholder_(\d*)/)[1];

							var existingPlaceHolder = $('#RuleValue_Placeholder_' + id).val()
							var existingPlaceHolderArr = existingPlaceHolder.split(", ");

							var placeHolderSel = $('#RuleValue_MultipleSelectorPlaceholder_' + id).children('input[@type="checkbox"]');

							for (var i=0; i<placeHolderSel.length;i++) {
								if(jQuery.inArray(placeHolderSel[i].value, existingPlaceHolderArr) != -1) {
									$('#' + placeHolderSel[i].id).attr('CHECKED','CHECKED');
								}
							}

							checkboxWin = $.fn.window.create({
								title:'%%LNG_Addon_dynamiccontenttags_SelectWindowTitle%%',
								height:500,
								width:700,
								content:$('#RuleValue_MultipleSelectorPlaceholder_'+id).html()
							});
                                                        $.fn.window.zIndexStart = 0;
							checkboxWin.open();
						});
					break;
					case 'textarea':
					case 'text':
					default:
						var temp = $(ruleTypeHtml + '<input type="text" class="RuleRowUserInput Field250 TextInputType" name="RuleValue[' + $(row).attr('rowIndex') + ']" style="width: 150px;" />');
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
		},
		popSelect: function(row) {
			var temp = $('.RuleRowUserInput', $(row).parent().get(0)).get();
			var values = [];
			for(var i = 0, j = temp.length; i < j; ++i) if(temp[i].checked) values.push(temp[i].title);

			var rowid = $(row).attr('id').match(/RuleValue_(\d*?)_/)[1];
			$('#RuleValue_Placeholder_' + rowid).val(values.join(', '));
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
			$(temp.children().get(0)).datepicker();
		},
		_appendNumberBetween:			function(row) {
			var temp = $('<span>&nbsp;%%LNG_AND%%&nbsp;<input type="text" class="RuleRowUserInput" name="RuleValue[' + $(row).attr('rowIndex') + '][]" /></span>');
			temp.appendTo($('.RuleRowRuleValues', row).get(0));
			this._applyNumberInputboxBehaviour(temp.get(0));
		}
	};
</script>

<form name="frmDynamicContentBlocks" id="frmDynamicContentBlocks" onsubmit="return false;" method="post" action="{$AdminUrl}&Action={if $FormType == 'create'}Create{elseif $FormType == 'edit'}Edit&id={$DynamicContentBlockId}{/if}">
	<input id="id_blockid" type="hidden" name="blockid" value="%%GLOBAL_blockid%%" />
	<input id="id_tagid" type="hidden" name="tagid" value="%%GLOBAL_tagid%%" />
	<input id="sortorder_blockid" type="hidden" name="sortorder_blockid" value="-1" />

	<table cellspacing="2" cellpadding="0" width="97%" align="center">
		<tr>
			<td class="body pageinfo">
				<p>
					{$lang.Addon_dynamiccontenttags_Block_Form_Intro}
				</p>
			</td>
		</tr>
		<tr>
			<td>
				{$FlashMessages}
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton blockSubmitButton" class="Field50" type="submit" name="Submit_Exit" value="{$lang.Addon_dynamiccontenttags_Save}" />
				<input class="FormButton blockCancelButton" class="Field50" type="button" value="{$lang.Addon_dynamiccontenttags_Cancel}" />
				<br />&nbsp;
				<table border="0" cellspacing="0" cellpadding="0" class="Panel">
					<tr>
						<td colspan="3" class="Heading2">
							&nbsp;&nbsp;{$lang.Addon_dynamiccontenttags_Block_Name}&nbsp;&nbsp;{$lnghlp.Addon_dynamiccontenttags_Block_Name}
						</td>
					</tr>
					<tr>
						<td width="85%">
							<input style="width:99%;" class="Field" type="text" id="id_dynamiccontenttags_block_name" name="dynamiccontenttags_block_name" value=""/><br />
						</td>
					</tr>
				</table>

				<table border="0" cellspacing="0" cellpadding="0" class="Panel">
					<tr>
						<td colspan="3" class="Heading2">
							&nbsp;&nbsp;{$lang.Addon_dynamiccontenttags_Block_Rules}&nbsp;&nbsp;{$lnghlp.Addon_dynamiccontenttags_Block_Rules}
						</td>
					</tr>
					<tr>
						<td width="90%">
						<div id="sectionRuleContainer">
						</div>
						</td>
					</tr>
				</table>

				<table border="0" cellspacing="10" cellpadding="0" class="Panel">
					<tr>
						<td colspan="3" class="Heading2">
							&nbsp;&nbsp;{$lang.Addon_dynamiccontenttags_BlockContent}&nbsp;&nbsp;{$lnghlp.Addon_dynamiccontenttags_BlockContent}
						</td>
					</tr>
					<tr>
						<td width="85%" style="padding-top:5px;">
							%%GLOBAL_BlockEditor%%
						</td>
					</tr>
					<tr>
						<td width="85%">
							<label for="">
								<input id="dynamiccontenttags_block_defaultset" type="checkbox" name="dynamiccontenttags_block_defaultset" value="1"/>
							{$lang.Addon_dynamiccontenttags_Block_DefaultSet}
							</label>
							&nbsp;&nbsp;{$lnghlp.Addon_dynamiccontenttags_Block_DefaultSet}
						</td>
					</tr>
				</table>
				<input class="FormButton blockSubmitButton" class="Field50" type="submit" name="Submit_Exit" value="{$lang.Addon_dynamiccontenttags_Save}" />
				<input class="FormButton blockCancelButton" class="Field50" type="button" value="{$lang.Addon_dynamiccontenttags_Cancel}" />
				<br />&nbsp;
			</td>
		</tr>
	</table>

	<input type="hidden" id="matchType_All" value="and" />
</form>
<script>
$(document).ready(function () {
	PAGE.init();
});
</script>
