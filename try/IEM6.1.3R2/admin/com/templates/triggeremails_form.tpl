<style type="text/css">@import url(includes/styles/ui.datepicker.css);</style>
<script src="includes/js/jquery/ui.js"></script>
<script type="text/javascript">
	{template="ui.datepicker.custom_iem"}

	Application.Page.TriggerEmailsForm = {
		_language: {	trigger_name_empty: '{$lang.TriggerEmails_Form_Field_TriggerName_Error|addslashes}',
						triggertype_error_not_selected: '{$lang.TriggerEmails_Form_Field_TriggerType_Error|addslashes}',

						triggertype_datecustomfields_not_vailable: '{$lang.TriggerEmails_Form_Field_TriggerType_DateCustomField_NotAvailable|addslashes}',
						triggertype_datecustomfields_customfield_not_selected: '{$lang.TriggerEmails_Form_Field_TriggerType_DateCustomField_Error_ChooseCustomField|addslashes}',
						triggertype_datecustomfields_list_not_selected: '{$lang.TriggerEmails_Form_Field_TriggerType_DateCustomField_Error_ChooseList|addslashes}',
						triggertype_datecustomfields_creation_prompt: '{$lang.TriggerEmails_Form_Field_TriggerType_DateCustomField_Prompt_CreateCustomField|addslashes}',
						triggertype_datecustomfields_instruction: '{$lang.TriggerEmails_Form_Field_TriggerType_DateCustomField_ChooseCustomField_Instruction|addslashes}',

						triggertype_staticdate_empty_date: '{$lang.TriggerEmails_Form_Field_TriggerType_StaticDate_Error|addslashes}',
						triggertype_staticdate_empty_listid: '{$lang.TriggerEmails_Form_Field_TriggerType_StaticDate_List_Error|addslashes}',

						triggertype_linkclicked_error_choosenewsletter: '{$lang.TriggerEmails_Form_Field_TriggerType_LinkClicked_Error_ChooseNewsletter}',
						triggertype_linkclicked_error_chooseanothernewsletter: '{$lang.TriggerEmails_Form_Field_TriggerType_LinkClicked_Error_ChooseAnotherNewsletter}',
						triggertype_linkclicked_not_available: '{$lang.TriggerEmails_Form_Field_TriggerType_LinkClicked_NotAvailable|addslashes}',

						triggertype_newsletteropen_error_choosenewsletter: '{$lang.TriggerEmails_Form_Field_TriggerType_NewsletterOpened_Error_ChooseNewsletter|addslashes}',

						triggertime_invalid_time: '{$lang.TriggerEmails_Form_Field_When_Error_InvalidTime|addslashes}',

						triggeractions_not_choosen: '{$lang.TriggerEmails_Form_Field_TriggerActions_Not_Choosen|addslashes}',

						triggeractions_send_field_newsletterid: '{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Error|addslashes}',
						triggeractions_send_field_fromname_empty: '{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_FromName_Error|addslashes}',
						triggeractions_send_field_fromemail_empty: '{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_FromEmail_EmptyError|addslashes}',
						triggeractions_send_field_replyemail_empty: '{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_ReplyEmail_EmptyError|addslashes}',
						triggeractions_send_field_bounceemail_empty: '{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_BounceEmail_EmptyError|addslashes}',

						triggeractions_addlist_empty_listid: '{$lang.TriggerEmails_Form_Field_TriggerAction_AddList_Error|addslashes}',

						triggeractions_removefromlist_label_generic: '{$lang.TriggerEmails_Form_Field_TriggerAction_RemoveList|addslashes}',
						triggeractions_removefromlist_label_datecustomfield: '{$lang.TriggerEmails_Form_Field_TriggerAction_RemoveList_f|addslashes}',
						triggeractions_removefromlist_label_staticdate_one: '{$lang.TriggerEmails_Form_Field_TriggerAction_RemoveList_s_One|addslashes}',
						triggeractions_removefromlist_label_staticdate_many: '{$lang.TriggerEmails_Form_Field_TriggerAction_RemoveList_s_Many|addslashes}',
						triggeractions_removefromlist_label_linkclicked: '{$lang.TriggerEmails_Form_Field_TriggerAction_RemoveList_l|addslashes}',
						triggeractions_removefromlist_label_newsletteropen: '{$lang.TriggerEmails_Form_Field_TriggerAction_RemoveList_n|addslashes}'},

		_optionsDatePickerStaticDate: {	yearRange:'-100:+100',
										dateFormat: 'yy-mm-dd',
										altField: 'div.TriggerType_s_options input[type=text]',
										altFormat: 'DD, d M yy'},


		_cacheList: {$availableLists|GetJSON},
		_cacheListCustomfields: {$availableCustomFields|GetJSON},
		_cacheNewsletterLinks: {$availableLinks|GetJSON},

		_currentlySelectedLinkID_Newsletter: [],



		eventDOMReady: function(event) {
			$('ul#tabnav a').click(Application.Page.TriggerEmailsForm.eventChangeTab);
			$(document.frmTriggerForm).submit(Application.Page.TriggerEmailsForm.eventSubmitForm);
			$('.cancelButton', document.frmTriggerForm).click(Application.Page.TriggerEmailsForm.eventClickCancel);
			$("input[name='record[triggertype]']", document.frmTriggerForm).click(Application.Page.TriggerEmailsForm.eventChangeTriggerType);
			$(document.frmTriggerForm['record[data][listid]']).change(Application.Page.TriggerEmailsForm.eventChangeList);
			$(document.frmTriggerForm['record[data][linkid_newsletterid]']).change(Application.Page.TriggerEmailsForm.eventChangeTriggerLinkNewsletter);
			$(document.frmTriggerForm['record[data][newsletterid]']).change(Application.Page.TriggerEmailsForm.eventChangeTriggerNewsletterOpen);
			$(document.frmTriggerForm['toprocess[when]']).change(Application.Page.TriggerEmailsForm.eventChangeTimeWhen);
			$(document.frmTriggerForm['record[triggeractions][send][enabled]']).click(Application.Page.TriggerEmailsForm.eventClickSendTriggerActions);
			$(document.frmTriggerForm['record[triggeractions][addlist][enabled]']).click(Application.Page.TriggerEmailsForm.eventClickAddListTriggerActions);
			$('#hrefPreview').click(Application.Page.TriggerEmailsForm.eventPreviewCampaign);

			$(document.frmTriggerForm['record[data][staticdate]']).datepicker(Application.Page.TriggerEmailsForm._optionsDatePickerStaticDate);

			$('input[type=text]', document.frmTriggerForm).focus(Application.Page.TriggerEmailsForm.eventTextFocus);



			var temp = $('div.TriggerType_s_options input#record_data_staticdate_display', document.frmTriggerForm);
			if (temp.val().trim() == '') {
				var tempDate = new Date();

				temp.val($.datepicker.formatDate('DD, d M yy', tempDate));
				$(document.frmTriggerForm['record[data][staticdate]']).val($.datepicker.formatDate('yy-mm-dd', tempDate));

				delete tempDate;
			} else temp.val($.datepicker.formatDate('DD, d M yy', $.datepicker.parseDate('yy-mm-dd', temp.val())));
			temp.click(Application.Page.TriggerEmailsForm.eventClickStaticDataDisplay);
			delete temp;

			var temp = document.frmTriggerForm['toprocess[time]'].value;
			var tempUnit = '1';
			if (temp != 0) {
				if ((temp % 168) == 0) {
					temp = temp / 168;
					tempUnit = '168';
				} else if ((temp % 24) == 0) {
					temp = temp / 24;
					tempUnit = '24';
				}
			}

			if (tempUnit != 'hours') {
				document.frmTriggerForm['toprocess[time]'].value = temp;
				$('option[value=' + tempUnit + ']', document.frmTriggerForm['toprocess[timeunit]']).attr('selected', true);
			}
			delete tempUnit;
			delete temp;



			document.frmTriggerForm['record[name]'].focus();
		},


		eventSubmitForm: function(event) {
			try { if(Application.Page.TriggerEmailsForm.checkForm()) return true; } catch(e) { alert(e); }
			event.stopPropagation();
			event.preventDefault();
			return false;
		},

		eventClickCancel: function(event) {
			if(confirm('{$lang.TriggerEmails_Form_Prompt_Cancel}'))
				Application.Util.submitGet('index.php', {Page: 'TriggerEmails'});
		},

		eventChangeTriggerType: function(event) {
			var triggerType = this.value;

			$('tr#TriggerTime_Row div#TriggerTime_Choosen').show();
			$('tr#TriggerTime_Row div#TriggerTime_NotChoosen').hide();

			var tempShowOptionSelector = 'div.TriggerType_' + triggerType + '_options, tr.TriggerType_' + triggerType + '_options';
			$('div.TriggerType_options, tr.TriggerType_options', document.frmTriggerForm).filter(':not(' + tempShowOptionSelector + ')').hide();
			$(tempShowOptionSelector, document.frmTriggerForm).show();
			delete tempShowOptionSelector;

			Application.Page.TriggerEmailsForm.populateTriggerTimeWhen(triggerType);
			Application.Page.TriggerEmailsForm.populateTriggerTimeInterval(triggerType);
			Application.Page.TriggerEmailsForm.changeRemoveFromListLabel();
		},

		eventChangeTab: function(event) {
			event.stopPropagation();
			event.preventDefault();
			ShowTab(this.id.match(/tab(\d+)/)[1]);
		},

		eventChangeList: function(event) {
			var selectedIndex = this.selectedIndex;
			var selectedList = this.options[selectedIndex].value;

			if (selectedIndex == 0) $('div#TriggerType_f_data_customfield').hide();
			else {
				Application.Page.TriggerEmailsForm.populateDateCustomfields(selectedList);
				Application.Page.TriggerEmailsForm.changeRemoveFromListLabel();
				$('div#TriggerType_f_data_customfield').show();
			}
		},

		eventChangeTriggerLinkNewsletter: function(event) {
			var selectedIndex = this.selectedIndex;
			var selectedNewsletter = this.options[selectedIndex].value;

			if (selectedIndex == 0) $('div#TriggerType_l_data_links').hide();
			else if(Application.Util.isDefined(Application.Page.TriggerEmailsForm._cacheNewsletterLinks[selectedNewsletter])) {
				Application.Page.TriggerEmailsForm.populateLinks(selectedNewsletter);
				$('div#TriggerType_l_data_links').show();
			} else {
				Application.Page.TriggerEmailsForm._currentlySelectedLinkID_Newsletter.unshift(selectedNewsletter);
				$('img#TriggerType_l_data_links_loading').show();
				$.ajax({	type: "POST",
							dataType: 'json',
							url: "index.php?Page=TriggerEmails&Action=ajax",
							data: {	ajaxType:		'listlinksfornewsletters',
									newsletterid:	selectedNewsletter},
							error: Application.Page.TriggerEmailsForm.callbackRequestLinkError,
							success: Application.Page.TriggerEmailsForm.callbackRequestLinkSuccess,
							complete: Application.Page.TriggerEmailsForm.callbackRequestLinkComplete});
			}

			Application.Page.TriggerEmailsForm.changeRemoveFromListLabel();
		},

		eventChangeTriggerNewsletterOpen: function(event) {
			Application.Page.TriggerEmailsForm.changeRemoveFromListLabel();
		},

		eventClickSendTriggerActions: function(event) {
			var showSending = this.checked;
			$('div#triggeremails_triggeractions_send_options')[showSending? 'show' : 'hide']();
			$('a#tab2')[showSending? 'show' : 'hide']();
		},

		eventClickAddListTriggerActions: function(event) {
			$('div#triggeremails_triggeractions_addlist_options')[this.checked? 'show' : 'hide']();
		},

		eventPreviewCampaign: function(event) {
			var baseurl = "index.php?Page=Newsletters&Action=Preview&id=";
			var campaignListObject = document.frmTriggerForm['record[triggeractions][send][newsletterid]'];

			if (campaignListObject.selectedIndex < 1) {
				alert("{$lang.TriggerEmails_Form_Field_TriggerAction_Send_PreviewAlert|addslashes}");
				campaignListObject.focus();
				return false;
			}

			window.open(baseurl + $(campaignListObject).val() , "pp");
			return false;
		},

		eventChangeTimeWhen: function(event) {
			if (this.value == 'on') {
				$('span#TriggerTime_TimeUnit').hide();
				return;
			}

			$('span#TriggerTime_TimeUnit_Postfix').html(this.options[this.selectedIndex].text.toLowerCase());
			$('span#TriggerTime_TimeUnit').show();
		},

		eventClickStaticDataDisplay: function(event) {
			$(document.frmTriggerForm['record[data][staticdate]']).datepicker('show');
		},

		eventTextFocus: function(event) {
			this.select();
		},


		callbackRequestLinkError: function(request, textStatus, errorThrown) {
			alert('Unable to make request');
		},

		callbackRequestLinkSuccess: function(data, textStatus) {
			if (!data.status) return;
			for (var newsletterid in data.data) {
				if (Application.Util.isArray(data.data[newsletterid])) {
					Application.Page.TriggerEmailsForm._cacheNewsletterLinks[newsletterid] = false;
					continue;
				}

				Application.Page.TriggerEmailsForm._cacheNewsletterLinks[newsletterid] = data.data[newsletterid];
			}
		},

		callbackRequestLinkComplete: function(request, textStatus) {
			var newsletterid = Application.Page.TriggerEmailsForm._currentlySelectedLinkID_Newsletter.shift();

			if (Application.Page.TriggerEmailsForm._currentlySelectedLinkID_Newsletter.length == 0) {
				Application.Page.TriggerEmailsForm.populateLinks(newsletterid);
				Application.Page.TriggerEmailsForm.changeRemoveFromListLabel();
				$('img#TriggerType_l_data_links_loading').hide();
				$('div#TriggerType_l_data_links').show();
			}
		},


		checkForm: function() {
			var f = document.frmTriggerForm;
			var triggerType = $("input[name='record[triggertype]']:checked", f).val();
			var triggerActions = [];
			var triggerhours = 0;

			if ($.trim(f['record[name]'].value) == '') return this.checkFormInvalid(f['record[name]'], this._language.trigger_name_empty);
			if (!triggerType) return this.checkFormInvalid(f['record[triggertype]'][0], this._language.triggertype_error_not_selected);
			switch(triggerType) {
				case 'f':
					if (f['record[data][listid]'].selectedIndex == 0) return this.checkFormInvalid(f['record[data][listid]'], this._language.triggertype_datecustomfields_list_not_selected);
					if ($(f['record[data][customfieldid]']).val() == '') return this.checkFormInvalid(f['record[data][customfieldid]'], this._language.triggertype_datecustomfields_creation_prompt, 1, 'index.php?Page=CustomFields&Action=Create');
					if ($(f['record[data][customfieldid]']).val() == '0') return this.checkFormInvalid(f['record[data][customfieldid]'], this._language.triggertype_datecustomfields_customfield_not_selected);
				break;

				case 's':
					if ($.trim(f['record[data][staticdate]'].value) == '') return this.checkFormInvalid($(f['record[data][staticdate]']).prev().get(0), this._language.triggertype_staticdate_empty_date);
					if($('option:selected', f['record[data][staticdate_listids][]']).size() == 0)
						return this.checkFormInvalid(f['record[data][staticdate_listids][]'], this._language.triggertype_staticdate_empty_listid);
				break;

				case 'l':
					if (f['record[data][linkid_newsletterid]'].selectedIndex == 0) return this.checkFormInvalid(f['record[data][linkid_newsletterid]'], this._language.triggertype_linkclicked_error_choosenewsletter);
					if ($('option', f['record[data][linkid]']).length == 0 || $(f['record[data][linkid]']).val() == 0) return this.checkFormInvalid(f['record[data][linkid]'], this._language.triggertype_linkclicked_error_chooseanothernewsletter);
				break;

				case 'n':
					if (f['record[data][newsletterid]'].selectedIndex == 0) return this.checkFormInvalid(f['record[data][newsletterid]'], this._language.triggertype_newsletteropen_error_choosenewsletter);
				break;
			}

			if ($(f['toprocess[when]']).val() != 'on') {
				triggerhours = Math.abs(parseInt($.trim(f['toprocess[time]'].value)));
				if (triggerhours != f['toprocess[time]'].value) return this.checkFormInvalid(f['toprocess[time]'], this._language.triggertime_invalid_time);
			}

			var temp = $('input[type=checkbox].triggeractions:checked', f);
			for(var i = 0, j = temp.size(); i < j; ++i) triggerActions.push(temp.get(i).name.match(/record\[triggeractions\]\[(\w+)\]/)[1]);
			temp = null;
			delete temp;

			if (triggerActions.length == 0) {
				return this.checkFormInvalid(f['record[triggeractions][send][newsletterid]'], this._language.triggeractions_not_choosen);
			}

			if ($.inArray('send', triggerActions) != -1) {
				if(f['record[triggeractions][send][newsletterid]'].value == 0) return this.checkFormInvalid(f['record[triggeractions][send][newsletterid]'], this._language.triggeractions_send_field_newsletterid);
				if($.trim(f['record[triggeractions][send][sendfromname]'].value) == '') return this.checkFormInvalid(f['record[triggeractions][send][sendfromname]'], this._language.triggeractions_send_field_fromname_empty, 2);
				if($.trim(f['record[triggeractions][send][sendfromemail]'].value) == '') return this.checkFormInvalid(f['record[triggeractions][send][sendfromemail]'], this._language.triggeractions_send_field_fromemail_empty, 2);
				if($.trim(f['record[triggeractions][send][replyemail]'].value) == '') return this.checkFormInvalid(f['record[triggeractions][send][replyemail]'], this._language.triggeractions_send_field_replyemail_empty, 2);
				if(f['record[triggeractions][send][bounceemail]'] && $.trim(f['record[triggeractions][send][bounceemail]'].value) == '') return this.checkFormInvalid(f['record[triggeractions][send][bounceemail]'], this._language.triggeractions_send_field_bounceemail_empty, 2);
			}

			if ($.inArray('addlist', triggerActions) != -1) {
				if($('option:selected', f['record[triggeractions][addlist][listid][]']).size() == 0)
					return this.checkFormInvalid(f['record[triggeractions][addlist][listid][]'], this._language.triggeractions_addlist_empty_listid);
			}

			f['record[triggerhours]'].value = triggerhours * ($(f['toprocess[when]']).val() == 'before'? -1 : 1) * ($(f['toprocess[timeunit]']).val());

			return true;
		},

		populateTriggerTimeWhen: function(triggerType) {
			var options = '';
			var time = parseInt(document.frmTriggerForm['toprocess[time]'].value);

			if (triggerType == 'f' || triggerType == 's') options = '<option value="before">{$lang.TriggerEmails_Form_Field_When_Context_Before}</option>';
			options += '<option value="on" ' + (time? '' : 'selected="selected" ') + '>'
			options += (triggerType == 'f' || triggerType == 's')? '{$lang.TriggerEmails_Form_Field_When_Context_On}' : '{$lang.TriggerEmails_Form_Field_When_Context_When}'
			options += '</option>';
			options += '<option value="after" ' + ((time && triggerType != 'f')? 'selected="selected" ' : '') + '>{$lang.TriggerEmails_Form_Field_When_Context_After}</option>';

			$(document.frmTriggerForm['toprocess[when]']).html(options).triggerHandler('change');
		},

		populateTriggerTimeInterval: function(triggerType) {
			var selector = '';

			switch(triggerType) {
				case 'f': case 's': selector = 'TriggerTime_TimeUnit_Interval_Date'; break;
				case 'l': selector = 'TriggerTime_TimeUnit_Interval_Link'; break;
				case 'n': selector = 'TriggerTime_TimeUnit_Interval_EmailOpen'; break;
			}

			if (selector == '') return;

			$('span.TriggerTime_TimeInterval', document.frmTriggerForm).filter('not:(span#' + selector + ')').hide();
			$('span#' + selector).show();
		},

		populateDateCustomfields: function(selectedList) {
			var obj = document.frmTriggerForm['record[data][customfieldid]'];

			if (!this._cacheListCustomfields[selectedList] || !this._cacheListCustomfields[selectedList].date) {
				var temp = this._language.triggertype_datecustomfields_not_vailable.replace('%s', this._cacheList[selectedList].name);
				$(obj).html('<option value="">' + temp + '</option>').hide();
				$('div#TriggerType_f_data_customfield span').html(temp).show();
			} else {
				var options = '<option value="0">' + this._language.triggertype_datecustomfields_instruction + '</option>';
				for(var fieldid in this._cacheListCustomfields[selectedList].date)
					options += '<option value="' + fieldid + '">' + this._cacheListCustomfields[selectedList].date[fieldid].name + '</option>';
				$('div#TriggerType_f_data_customfield span').hide();
				$(obj).html(options).show();
			}
		},

		populateLinks: function(selectedNewsletter) {
			var obj = document.frmTriggerForm['record[data][linkid]'];
			if (!this._cacheNewsletterLinks[selectedNewsletter]) {
				var temp = this._language.triggertype_linkclicked_not_available;
				$(obj).html('<option value="0">' + temp + '</option>').hide();
				$('div#TriggerType_l_data_links span').html(temp).show();
			} else {
				var options = '';
				for(var linkid in this._cacheNewsletterLinks[selectedNewsletter])
					options += '<option value="' + linkid + '" title="' + this._cacheNewsletterLinks[selectedNewsletter][linkid] + '">' + this._cacheNewsletterLinks[selectedNewsletter][linkid] + '</option>';
				$('div#TriggerType_l_data_links span').hide();
				$(obj).html(options).show();
			}
		},

		checkFormInvalid: function(element, errorMsg, tab, redirect) {
			if(redirect) {
				if(confirm(errorMsg.replace(/\\n/g, '\n'))) {
					document.location.href = redirect;
					return false;
				}
			}

			if(!tab) tab = 1;

			ShowTab(tab);
			if (!redirect) alert(errorMsg);
			try { element.focus(); } catch(e) { }
			return false;
		},

		changeRemoveFromListLabel: function() {
			var f = document.frmTriggerForm;
			var triggerType = $("input[name='record[triggertype]']:checked", f).val();
			var label = this._language.triggeractions_removefromlist_label_generic;

			switch (triggerType) {
				case 'f':
					var temp = f['record[data][listid]'].selectedIndex;
					if (temp != 0) label = this._language.triggeractions_removefromlist_label_datecustomfield.replace(/%s/, f['record[data][listid]'].options[temp].text);
				break;

				case 's':
					var selectedListText = [];

					var obj = f['record[data][staticdate_listids][]'];
					for (var i = 0, j = obj.options.length; i < j; ++i)
						if (obj.options[i].selected) selectedListText.push(obj.options[i].text);
					delete obj;

					switch (selectedListText.length) {
						case 0: break;
						case 1: label = this._language.triggeractions_removefromlist_label_staticdate_one.replace(/%s/, selectedListText[0]); break;
						default: label = this._language.triggeractions_removefromlist_label_staticdate_many.replace(/%s/, '<ul style="margin-top:5px; margin-bottom:5px; color: auto;"><li>' + selectedListText.join('</li><li>') + '</li></ul>'); break;
					}
				break;

				case 'n':
					var temp = f['record[data][newsletterid]'].selectedIndex;
					if (temp != 0) label = this._language.triggeractions_removefromlist_label_newsletteropen.replace(/%s/, f['record[data][newsletterid]'].options[temp].text);
				break;

				case 'l':
					var temp = f['record[data][linkid_newsletterid]'].selectedIndex;
					if (temp != 0) label = this._language.triggeractions_removefromlist_label_linkclicked.replace(/%s/, f['record[data][linkid_newsletterid]'].options[temp].text);
				break;
			}

			$('label#triggeremails_triggeractions_removelist_enabled_label', f).html(label);
		}
	};

	Application.init.push(Application.Page.TriggerEmailsForm.eventDOMReady);
</script>
<style>
	.PanelRow {
		display: block;
		clear: both;
	}

	.PanelRowFieldTitle {
		float: left;
		width: 250px;
	}
</style>
<div class="PageBodyContainer">
	<div class="Heading1">{$PAGE.heading}</div>
	<div class="Intro" {if trim($PAGE.messages) == ''}style="margin-bottom:3px;"{/if}>{$lang.TriggerEmails_Intro}</div>
	{if trim($PAGE.messages) != ''}<div style="margin-top:5px;">{$PAGE.messages}</div>{/if}

	<form name="frmTriggerForm" action="index.php?Page=TriggerEmails&Action=Save" method="POST">
		<input type="hidden" name="ProcessThis" value="1" />
		<input type="hidden" name="record[triggeremailsid]" value="{$record.triggeremailsid}" />
		<table cellspacing="0" cellpadding="3" width="100%" align="center">
			<tr>
				<td>
					<input class="FormButton submitButton" type="submit" value="{$lang.Save}" />
					<input class="FormButton cancelButton" type="button" value="{$lang.Cancel}" />
					<br />&nbsp;

					{* ----- Define tabs *}
						<div style="margin-bottom:10px;">
							<ul id="tabnav">
								<li>
									<a href="#" class="active" id="tab1">
										<span>{$lang.TriggerEmails_Tab_General}</span>
									</a>
								</li>
								<li>
									<a href="#" id="tab2" {if !$record.triggeractions.send.enabled}style="display:none;"{/if}>
										<span>{$lang.TriggerEmails_Tab_SendingOptions}</span>
									</a>
								</li>
							</ul>
						</div>
					{* ----- *}


					{* ----- Tab 1: General *}
						<div id="div1">
							<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
								<tr>
									<td colspan="2" class="Heading2">
										&nbsp;&nbsp;{$lang.TriggerEmails_Form_Header_TriggerDetails}
									</td>
								</tr>

								{* Trigger Name *}
								<tr>
									<td class="FieldLabel" width="10%">
										<img src="images/blank.gif" width="200" height="1" /><br />
										{template="Required"}
										{$lang.TriggerEmails_Form_Field_TriggerName}:&nbsp;
									</td>
									<td width="90%">
										<input type="text" name="record[name]" class="Field250" value="{$record.name}" />
										<br />
										<span class="aside">{$lang.TriggerEmails_Form_Field_TriggerName_Description}</span>
									</td>
								</tr>

								{* ----- Event that will trigger this particular "Trigger Emails" *}
									<tr>
										<td class="FieldLabel">
											<img src="images/blank.gif" width="200" height="1" /><br />
											{template="Required"}
											{$lang.TriggerEmails_Form_Field_TriggerType_Title}:&nbsp;
										</td>
										<td>
											{* ----- Triggered based on date custom field *}
												<div>
													<input	type="radio"
															name="record[triggertype]"
															id="TriggerTypeDateCustomFieldEvent"
															class="TriggerType"
															value="f"
															{if $record.triggertype == 'f'}checked="checked"{/if} />
													<label for="TriggerTypeDateCustomFieldEvent">{$lang.TriggerEmails_Form_Field_TriggerType_DateCustomField}</label>

													<div class="TriggerType_f_options TriggerType_options" {if $record.triggertype != 'f'}style="display:none;"{/if}>
														<div>
															<img width="20" height="20" src="images/nodejoin.gif" />
															<select name="record[data][listid]" class="Field250">
																<option value="0">{$lang.TriggerEmails_Form_Field_TriggerType_DateCustomField_ChooseList_Instruction}</option>
																{foreach from=$availableLists item=each}
																	<option value="{$each.listid}" {if $record.data.listid == $each.listid}selected="selected"{/if}>{$each.name|htmlspecialchars, ENT_QUOTES, SENDSTUDIO_CHARSET}</option>
																{/foreach}
															</select>
														</div>
														<div id="TriggerType_f_data_customfield" {if !$record.data.listid}style="display:none;"{/if}>
															{capture name=processedDateCustomFields trim=true}
																{foreach from=$availableCustomFields key=listid item=customFieldLists}
																	{if $record.data.listid == $listid}
																		{foreach from=$customFieldLists.date key=customFieldKey item=customFieldRecord}
																			<option value="{$customFieldKey}"{if $record.data.customfieldid == $customFieldKey} selected="selected"{/if}>
																				{$customFieldRecord.name|htmlspecialchars, ENT_QUOTES, SENDSTUDIO_CHARSET}
																			</option>
																		{/foreach}
																	{/if}
																{/foreach}
															{/capture}
															<img width="20" height="20" src="images/blank.gif" />
															<span {if $processedDateCustomFields != ''}style="display:none;"{/if}>{$lang.TriggerEmails_Form_Field_TriggerType_DateCustomField_NotAvailable|sprintf, $record.listname}</span>
															<select name="record[data][customfieldid]" {if $processedDateCustomFields == ''}style="display:none;"{/if}>
																<option value="{if $processedDateCustomFields == ''}{else}0{/if}">{$lang.TriggerEmails_Form_Field_TriggerType_DateCustomField_ChooseCustomField_Instruction}</option>
																{$processedDateCustomFields}
															</select>
														</div>
													</div>
												</div>
											{* ----- *}

											{* ----- Triggered based on static date *}
												<div>
													<input	type="radio"
															name="record[triggertype]"
															id="TriggerTypeStaticDateEvent"
															class="TriggerType"
															value="s"
															{if $record.triggertype == 's'}checked="checked"{/if} />
													<label for="TriggerTypeStaticDateEvent">{$lang.TriggerEmails_Form_Field_TriggerType_StaticDate}</label>

													<div class="TriggerType_s_options TriggerType_options" {if $record.triggertype != 's'}style="display:none;"{/if}>
														<div>
															<img width="20" height="20" src="images/nodejoin.gif" />
															<input type="text" class="Field150" id="record_data_staticdate_display" value="{$record.data.staticdate}" readonly="readonly"  />
															<input type="hidden" name="record[data][staticdate]" value="{$record.data.staticdate}" />
														</div>
													</div>
												</div>
											{* ----- *}

											{* ----- Triggered based on link being clicked *}
												<div>
													<input	type="radio"
															name="record[triggertype]"
															id="TriggerTypeLinkClickedEvent"
															class="TriggerType"
															value="l"
															{if $record.triggertype == 'l'}checked="checked"{/if} />
													<label for="TriggerTypeLinkClickedEvent">{$lang.TriggerEmails_Form_Field_TriggerType_LinkClicked}</label>
													<div class="TriggerType_l_options TriggerType_options" {if $record.triggertype != 'l'}style="display:none;"{/if}>
														<div>
															<img width="20" height="20" src="images/nodejoin.gif" />
															<select name="record[data][linkid_newsletterid]">
																<option value="0">{$lang.TriggerEmails_Form_Field_TriggerType_LinkClicked_ChooseNewsletter_Instruction}</option>
																{foreach from=$availableNewsletters item=each}
																	<option value="{$each.newsletterid}" {if $record.data.linkid_newsletterid == $each.newsletterid}selected="selected"{/if}>{$each.name|htmlspecialchars, ENT_QUOTES, SENDSTUDIO_CHARSET}</option>
																{/foreach}
															</select>
														</div>

														<div id="TriggerType_l_data_links" {if !$record.data.linkid_newsletterid}style="display:none;"{/if}>
															{capture name=processedLinks trim=true}
																{foreach from=$availableLinks key=newsletterid item=linkList}
																	{if $record.data.linkid_newsletterid == $newsletterid}
																		{foreach from=$linkList key=linkid item=linkURL}
																			<option value="{$linkid}" {if $record.data.linkid == $linkid} selected="selected"{/if}>
																				{$linkURL|htmlspecialchars, ENT_QUOTES, SENDSTUDIO_CHARSET}
																			</option>
																		{/foreach}
																	{/if}
																{/foreach}
															{/capture}
															<img width="20" height="20" src="images/blank.gif" alt=" " />
															<span {if $processedLinks != ''}style="display:none;"{/if}>{$lang.TriggerEmails_Form_Field_TriggerType_LinkClicked_NotAvailable}</span>
															<select name="record[data][linkid]" {if $processedLinks == ''}style="display:none;"{/if}>{$processedLinks}</select>
															<img id="TriggerType_l_data_links_loading" src="images/loading.gif" alt="loading..." style="display:none;" />
														</div>
													</div>
												</div>
											{* ----- *}

											{* ----- Triggered based on newsletter being opened *}
												<div>
													<input	type="radio"
															name="record[triggertype]"
															id="TriggerTypeNewsletterOpenedEvent"
															class="TriggerType"
															value="n"
															{if $record.triggertype == 'n'}checked="checked"{/if} />
													<label for="TriggerTypeNewsletterOpenedEvent">{$lang.TriggerEmails_Form_Field_TriggerType_NewsletterOpened}</label>
													<div class="TriggerType_n_options TriggerType_options" {if $record.triggertype != 'n'}style="display:none;"{/if}>
														<div>
															<img width="20" height="20" src="images/nodejoin.gif" style="float:left;" />
															&nbsp;
															<select name="record[data][newsletterid]">
																<option value="0">{$lang.TriggerEmails_Form_Field_TriggerType_NewsletterOpened_ChooseNewsletter_Instruction}</option>
																{foreach from=$availableNewsletters item=each}
																	<option value="{$each.newsletterid}" {if $record.data.newsletterid == $each.newsletterid}selected="selected"{/if}>
																		{$each.name|htmlspecialchars, ENT_QUOTES, SENDSTUDIO_CHARSET}
																	</option>
																{/foreach}
															</select>
														</div>
													</div>
												</div>
											{* ----- *}
										</td>
									</tr>
								{* ----- *}


								{* ----- Special case for "Static Date" where it needs to be assigned to lists *}
									<tr class="TriggerType_s_options TriggerType_options" {if $record.triggertype != 's'}style="display:none;"{/if}>
										<td class="FieldLabel" width="10%">
											<img src="images/blank.gif" width="200" height="1" /><br />
											{template="Required"}
											{$lang.TriggerEmails_Form_Field_TriggerType_StaticDate_ListTitle}:
										</td>
										<td>
											<select name="record[data][staticdate_listids][]" id="record_data_staticdate_listids" multiple="multiple" class="ISSelectReplacement ISSelectSearch" onchange="Application.Page.TriggerEmailsForm.changeRemoveFromListLabel();">
												{foreach from=$availableLists item=each}
													{if $each.listid != $record.listid}
														<option value="{$each.listid}"
															{if (is_array($record.data.staticdate_listids) && in_array($each.listid, $record.data.staticdate_listids)) || ($record.data.staticdate_listids == $each.listid)}
																selected="selected"
															{/if}>
															{$each.name|htmlspecialchars, ENT_QUOTES, SENDSTUDIO_CHARSET}
														</option>
													{/if}
												{/foreach}
											</select>
										</td>
									</tr>
								{* ----- *}


								{* ----- Trigger Time *}
									<tr id="TriggerTime_Row">
										<td class="FieldLabel" width="10%">
											<img src="images/blank.gif" width="200" height="1" /><br />
											{template="Required"}
											{$lang.TriggerEmails_Form_Field_When_Title}:
										</td>
										<td>
											<input type="hidden" name="record[triggerhours]" value="{$record.triggerhours}" />
											<div id="TriggerTime_Choosen" {if !$record.triggeremailsid}style="display:none;"{/if}>
												<select name="toprocess[when]" class="Field250" style="width:auto;">
													{if $record.triggertype == 'f' || $record.triggertype == 's'}
														<option value="before" {if $record.triggerhours < 0}selected="selected"{/if}>
															{$lang.TriggerEmails_Form_Field_When_Context_Before}
														</option>
													{/if}
													<option value="on" {if $record.triggerhours == 0}selected="selected"{/if}>{$lang.TriggerEmails_Form_Field_When_Context_On}</option>
													<option value="after" {if $record.triggerhours > 0}selected="selected"{/if}>{$lang.TriggerEmails_Form_Field_When_Context_After}</option>
												</select>
												<span id="TriggerTime_TimeUnit" {if !$record.triggerhours}style="display:none;"{/if}>
													&nbsp;&nbsp;&nbsp;
													<input type="text" name="toprocess[time]" class="Field50" style="width:auto;" size="4" maxlength="4" value="{$record.triggerhours|abs}" />
													<select name="toprocess[timeunit]" class="Field250" style="width:auto;">
														<option value="1" selected="selected">{$lang.TriggerEmails_Form_Field_When_Unit_Hours}</option>
														<option value="24">{$lang.TriggerEmails_Form_Field_When_Unit_Days}</option>
														<option value="168">{$lang.TriggerEmails_Form_Field_When_Unit_Weeks}</option>
													</select>
													<span id="TriggerTime_TimeUnit_Postfix">{if $record.triggerhours < 0}{$lang.TriggerEmails_Form_Field_When_Context_Before}{elseif $record.triggerhours > 0}{$lang.TriggerEmails_Form_Field_When_Context_After}{/if}</span>
												</span>
												<span id="TriggerTime_TimeUnit_Interval_Date" class="TriggerTime_TimeInterval" {if $record.triggertype != 'f' && $record.triggertype != 's'}style="display:none;"{/if}>
													<select name="record[triggerinterval]" class="Field250" style="width:auto;">
														<option value="0" {if $record.triggerinterval == 0}selected="selected"{/if}>{$lang.TriggerEmails_Form_Field_When_Interval_TheDate}</option>
														<option value="1" {if $record.triggerinterval == 1}selected="selected"{/if}>{$lang.TriggerEmails_Form_Field_When_Interval_TheNextAnniversary}</option>
														<option value="-1" {if $record.triggerinterval == -1}selected="selected"{/if}>{$lang.TriggerEmails_Form_Field_When_Interval_TheAnniversaryOfTheDate}</option>
													</select>
													{$lnghlp.TriggerEmails_Form_Field_When_Help}
												</span>
												<span id="TriggerTime_TimeUnit_Interval_Link" class="TriggerTime_TimeInterval" {if $record.triggertype != 'l'}style="display:none;"{/if}>{$lang.TriggerEmails_Form_Field_When_Interval_LinkClicked}</span>
												<span id="TriggerTime_TimeUnit_Interval_EmailOpen" class="TriggerTime_TimeInterval" {if $record.triggertype != 'n'}style="display:none;"{/if}>{$lang.TriggerEmails_Form_Field_When_Interval_OpenNewsletter}</span>
											</div>
											{if !$record.triggeremailsid}
												<div id="TriggerTime_NotChoosen"><span class="aside">{$lang.TriggerEmails_Form_Field_When_Instruction}</span></div>
											{/if}
										</td>
									</tr>
								{* ----- *}

								{* ----- When trigger do the following actions *}
									<tr>
										<td class="FieldLabel">
											<img src="images/blank.gif" width="200" height="1" /><br />
											{template="Required"}
											{$lang.TriggerEmails_Form_Field_TriggerAction}:&nbsp;
										</td>
										<td>
											{* When triggered, send *}
											<div>
												<div>
													<input type="checkbox" name="record[triggeractions][send][enabled]" id="triggeremails_triggeractions_send_enabled" class="triggeractions" value="1" {if $record.triggeractions.send.enabled}checked="checked"{/if} />
													<label for="triggeremails_triggeractions_send_enabled">
														{$lang.TriggerEmails_Form_Field_TriggerAction_Send}
													</label>
												</div>
												<div id="triggeremails_triggeractions_send_options" {if !$record.triggeractions.send.enabled}style="display:none;"{/if}>
													<img width="20" height="20" src="images/nodejoin.gif" />
													<select name="record[triggeractions][send][newsletterid]" class="Field250">
														<option value="0">{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Instruction}</option>
														{foreach from=$availableNewsletters item=each}
															<option value="{$each.newsletterid}" {if $record.triggeractions.send.newsletterid == $each.newsletterid}selected="selected"{/if}>{$each.name|htmlspecialchars, ENT_QUOTES, SENDSTUDIO_CHARSET}</option>
														{/foreach}
													</select>
													{$lnghlp.TriggerEmails_Form_Field_TriggerAction_Send_Help}
													<a href="#" id="hrefPreview"><img border="0" src="images/magnify.gif"/>&nbsp;{$lang.Preview}</a>
												</div>
											</div>

											{* When triggered, add to lists *}
											<div>
												<div>
													<input type="checkbox" name="record[triggeractions][addlist][enabled]" id="triggeremails_triggeractions_addlist_enabled" class="triggeractions" value="1" {if $record.triggeractions.addlist.enabled}checked="checked"{/if} />
													<label for="triggeremails_triggeractions_addlist_enabled">
														{$lang.TriggerEmails_Form_Field_TriggerAction_AddList}
													</label>
												</div>
												<div id="triggeremails_triggeractions_addlist_options" {if !$record.triggeractions.addlist.enabled}style="display:none;"{/if}>
													<table cellpadding="0" cellspacing="0" border="0">
														<tr>
															<td valign="top" width="22"><img width="20" height="20" src="images/nodejoin.gif" /></td>
															<td>
																<select name="record[triggeractions][addlist][listid][]" id="record_triggeractions_addlist_listid" multiple="multiple" class="ISSelectReplacement ISSelectSearch">
																	{foreach from=$availableLists item=each}
																		{if $each.listid != $record.listid}
																			<option value="{$each.listid}"
																				{if (is_array($record.triggeractions.addlist.listid) && in_array($each.listid, $record.triggeractions.addlist.listid)) || ($record.triggeractions.addlist.listid == $each.listid)}
																					selected="selected"
																				{/if}>
																				{$each.name|htmlspecialchars, ENT_QUOTES, SENDSTUDIO_CHARSET}
																			</option>
																		{/if}
																	{/foreach}
																</select>
																{$lnghlp.TriggerEmails_Form_Field_TriggerAction_AddList_Help}
															</td>
														</tr>
													</table>
												</div>
											</div>

											{* When triggered, remove from lists *}
											<table cellspacing="0" cellpadding="0">
												<tr>
													<td valign="top">
														<input type="checkbox" name="record[triggeractions][removelist][enabled]" id="triggeremails_triggeractions_removelist_enabled" class="triggeractions" value="1" {if $record.triggeractions.removelist.enabled}checked="checked"{/if} />
														&nbsp;
													</td>
													<td valign="top">
														<label id="triggeremails_triggeractions_removelist_enabled_label" for="triggeremails_triggeractions_removelist_enabled">
															{$lang.TriggerEmails_Form_Field_TriggerAction_RemoveList}
														</label>
													</td>
												</tr>
											</table>
										</td>
									</tr>
								{* ----- *}

								{* ----- Active? *}
									<tr>
										<td class="FieldLabel" width="10%">
											<img src="images/blank.gif" width="200" height="1" /><br />
											{template="Required"}
											{$lang.TriggerEmails_Form_Field_Active_Title}:
										</td>
										<td>
											<input type="checkbox" name="record[active]" id="triggeremails_active" value="1" {if $record.active}checked="checked"{/if} />
											<label for="triggeremails_active">{$lang.TriggerEmails_Form_Field_Active_Instruction}</label>
										</td>
									</tr>
								{* ----- *}
							</table>
						</div>
					{* ----- *}


					{* ----- Advanced sending option tab ----- *}
						<div id="div2" style="display:none;">
							<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
								<tr>
									<td colspan="2" class="Heading2">
										&nbsp;&nbsp;{$lang.TriggerEmails_Form_Header_TriggerOptions}
									</td>
								</tr>

								{* The FROM name header that is going to be used to send *}
								<tr class="SendingOptionsFields">
									<td class="FieldLabel">
										<img src="images/blank.gif" width="200" height="1" /><br />
										{template="Required"}
										{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_FromName}:&nbsp;
									</td>
									<td>
										<input type="text" name="record[triggeractions][send][sendfromname]" class="Field250 form_text" value="{$record.triggeractions.send.sendfromname}" />{$lnghlp.TriggerEmails_Form_Field_TriggerAction_Send_Field_FromName}
									</td>
								</tr>

								{* The FROM header email that is going to be used to send *}
								<tr class="SendingOptionsFields">
									<td class="FieldLabel">
										<img src="images/blank.gif" width="200" height="1" /><br />
										{template="Required"}
										{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_FromEmail}:&nbsp;
									</td>
									<td>
										<input type="text" name="record[triggeractions][send][sendfromemail]" class="Field250 form_text" value="{$record.triggeractions.send.sendfromemail}" />{$lnghlp.TriggerEmails_Form_Field_TriggerAction_Send_Field_FromEmail}
									</td>
								</tr>

								{* The REPLY-TO header email that is going to be used to send *}
								<tr class="SendingOptionsFields">
									<td class="FieldLabel">
										<img src="images/blank.gif" width="200" height="1" /><br />
										{template="Required"}
										{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_ReplyEmail}:&nbsp;
									</td>
									<td>
										<input type="text" name="record[triggeractions][send][replyemail]" class="Field250 form_text" value="{$record.triggeractions.send.replyemail}" />{$lnghlp.TriggerEmails_Form_Field_TriggerAction_Send_Field_ReplyEmail}
									</td>
								</tr>

								{* The BOUNCE header email that is going to be used to send *}
								{if $allowSetBounceDetails}
									<tr class="SendingOptionsFields">
										<td class="FieldLabel">
											<img src="images/blank.gif" width="200" height="1" /><br />
											{template="Required"}
											{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_BounceEmail}:&nbsp;
										</td>
										<td>
											<input type="text" name="record[triggeractions][send][bounceemail]" class="Field250 form_text" value="{$record.triggeractions.send.bounceemail}" />{$lnghlp.TriggerEmails_Form_Field_TriggerAction_Send_Field_BounceEmail}
										</td>
									</tr>
								{/if}

								{* "First Name" custom field *}
								<tr style="display:none;">
									<td class="FieldLabel">
										<img src="images/blank.gif" width="200" height="1" /><br />
										{template="Not_Required"}
										{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_FieldFirstName}:&nbsp;
									</td>
									<td>
										<select name="record[triggeractions][send][firstnamefield]" class="Field250">
											<option value="0">{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_FieldFirstName_Instruction}</option>
											{foreach from=$availableNameCustomFields item=each}
												<option value="{$each.fieldid}" {if $record.triggeractions.send.firstnamefield == $each.fieldid}selected="selected"{/if}>{$each.name|htmlspecialchars, ENT_QUOTES, SENDSTUDIO_CHARSET}</option>
											{/foreach}
										</select>
										{$lnghlp.TriggerEmails_Form_Field_TriggerAction_Send_Field_FieldFirstName}
									</td>
								</tr>

								{* "Last Name" custom field *}
								<tr style="display:none;">
									<td class="FieldLabel">
										<img src="images/blank.gif" width="200" height="1" /><br />
										{template="Not_Required"}
										{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_FieldLastName}:&nbsp;
									</td>
									<td>
										<select name="record[triggeractions][send][lastnamefield]" class="Field250">
											<option value="0">{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_FieldLastName_Instruction}</option>
											{foreach from=$availableNameCustomFields item=each}
												<option value="{$each.fieldid}" {if $record.triggeractions.send.lastnamefield == $each.fieldid}selected="selected"{/if}>{$each.name|htmlspecialchars, ENT_QUOTES, SENDSTUDIO_CHARSET}</option>
											{/foreach}
										</select>
										{$lnghlp.TriggerEmails_Form_Field_TriggerAction_Send_Field_FieldLastName}
									</td>
								</tr>

								{* Send email as multipart *}
								<tr>
									<td class="FieldLabel">
										<img src="images/blank.gif" width="200" height="1" /><br />
										{template="Not_Required"}
										{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_Multipart}:&nbsp;
									</td>
									<td>
										<input type="checkbox" name="record[triggeractions][send][multipart]" id="triggeremails_form_record_multipart" class="form_checkbox" value="1"{if $record.triggeractions.send.multipart} checked="checked" {/if}/>
										<label for="triggeremails_form_record_multipart">{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_Multipart_Label}</label>
										{$lnghlp.TriggerEmails_Form_Field_TriggerAction_Send_Field_Multipart}
									</td>
								</tr>

								{* Track email opens *}
								<tr>
									<td class="FieldLabel">
										<img src="images/blank.gif" width="200" height="1" /><br />
										{template="Not_Required"}
										{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_TrackOpens}:&nbsp;
									</td>
									<td>
										<input type="checkbox" name="record[triggeractions][send][trackopens]" id="triggeremails_form_record_trackopens" class="form_checkbox" value="1"{if $record.triggeractions.send.trackopens} checked="checked" {/if}/>
										<label for="triggeremails_form_record_trackopens">{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_TrackOpens_Label}</label>
										{$lnghlp.TriggerEmails_Form_Field_TriggerAction_Send_Field_TrackOpens}
									</td>
								</tr>

								{* Track link clicks *}
								<tr>
									<td class="FieldLabel">
										<img src="images/blank.gif" width="200" height="1" /><br />
										{template="Not_Required"}
										{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_TrackLinks}:&nbsp;
									</td>
									<td>
										<input type="checkbox" name="record[triggeractions][send][tracklinks]" id="triggeremails_form_record_tracklinks" class="form_checkbox" value="1"{if $record.triggeractions.send.tracklinks} checked="checked" {/if}/>
										<label for="triggeremails_form_record_tracklinks">{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_TrackLinks_Label}</label>
										{$lnghlp.TriggerEmails_Form_Field_TriggerAction_Send_Field_TrackLinks}
									</td>
								</tr>

								{* Embed images *}
								{if $allowEmbedImages}
									<tr>
										<td class="FieldLabel">
											<img src="images/blank.gif" width="200" height="1" /><br />
											{template="Not_Required"}
											{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_EmbedImages}:&nbsp;
										</td>
										<td>
											<input type="checkbox" name="record[triggeractions][send][embedimages]" id="triggeremails_form_record_embedimages" class="form_checkbox" value="1"{if $record.triggeractions.send.embedimages} checked="checked" {/if}/>
											<label for="triggeremails_form_record_embedimages">{$lang.TriggerEmails_Form_Field_TriggerAction_Send_Field_EmbedImages_Label}</label>
											{$lnghlp.TriggerEmails_Form_Field_TriggerAction_Send_Field_EmbedImages}
										</td>
									</tr>
								{/if}
							</table>
						</div>
					{* ----- *}

					<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
						<tr>
							<td width="200" class="FieldLabel">&nbsp;</td>
							<td valign="top" height="30">
								<input class="FormButton submitButton" type="submit" value="{$lang.Save}" />
								<input class="FormButton cancelButton" type="button" value="{$lang.Cancel}" />
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</form>
</div>