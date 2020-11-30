<script src="includes/js/jquery/ui.js"></script>

<script>

	var SortFieldNum = 0;
	var FormLoaded = '%%GLOBAL_FormLoaded%%';
	var LoadFormType = '%%GLOBAL_LoadFormType%%';
	var LoadDesign = '%%GLOBAL_LoadDesign%%';

	var LoadChangeFormat = '%%GLOBAL_LoadChangeFormat%%';

	var LoadedLists = new Array(%%GLOBAL_LoadedLists%%);
	var LoadedFields = new Array(%%GLOBAL_LoadedFields%%);

	var LoadedOrder = new Array(%%GLOBAL_LoadedOrder%%);

	var RequiredFields_Values = {	'e':	'%%LNG_Email_Required_For_Form%%',
									'cf':	'%%LNG_SubscriberFormat_For_Form%%',
									'cl':	'%%LNG_ChooseList_For_Form%%'};


	function ShowFields(fld, listid) {
		mytype = document.getElementById('FormType');
		var selectedVal = mytype[mytype.selectedIndex].value;

		if (selectedVal != 'u')
		{
			if (fld.checked) {
				document.getElementById('customfields_' + listid).style.display = '';
			} else {
				for(i = 0; i < document.forms[0].elements.length; i++) {
					if(document.forms[0].elements[i].type == "checkbox") {
						fld = document.forms[0].elements[i];
						fldid = fld.id;
						fldcheck = fldid.indexOf('fields_' + listid);

						if (fldcheck == 0) {
							fld.checked = false;
							UpdateOrderBox(fld);
						}
					}
				}
				document.getElementById('customfields_' + listid).style.display = 'none';
			}
		}

		list_count = CountLists();

		fieldorderbox = document.getElementById('fieldorder');

		var fieldOrderBox = $('.FieldOrderHiddenValues');

		for(var i = 0, j = fieldOrderBox.size(); i < j; ++i) {
			if(fieldOrderBox.get(i).value == 'cl') {
				RemoveFieldOrderValue(fieldOrderBox.get(i));
				break;
			}
		}


		if (list_count <= 1) {
			return;
		}

		AddFieldOrderValue(RequiredFields_Values['cl'], 'cl');
	}

	function UpdateOrderBox(fld) {
		var fieldOrderBox = $('.FieldOrderHiddenValues');

		var fldname = document.getElementById(fld.id + '_label');

		if (fld.checked) {
			found_options = new Array();
			for (var i = 0, j = fieldOrderBox.size(); i < j; i++) {
				found_options.push($(fieldOrderBox.get(i)).val());
			}

			if (!inArray(fld.value, found_options)) {
				if (fldname.innerText && typeof(fldname.innerText) != "undefined") {
					AddFieldOrderValue(fldname.innerText, fld.value);
				} else {
					AddFieldOrderValue(fldname.textContent, fld.value);
				}
			}
			return;
		}

		var remove = true;
		var fldvalue = fld.value;
		var tempIncludedCustomField = $('.ListCustomFieldToInclude');
		for(var i = 0, j = tempIncludedCustomField.size(); i < j; ++i) {
			var tempEach = tempIncludedCustomField.get(i);
			if(!tempEach.checked) continue;

			if(fldvalue == tempEach.value) {
				remove = false;
				break;
			}
		}

		if(remove) {
			for (var i = 0, j = fieldOrderBox.size(); i < j; i++) {
				if($(fieldOrderBox.get(i)).val() == fld.value) {
					RemoveFieldOrderValue(fieldOrderBox.get(i));
					return;
				}
			}
		}
	}

	function CheckForm() {
		var f = document.forms[0];

		if (f.FormName.value == '') {
			alert("%%LNG_EnterFormName%%");
			f.FormName.focus();
			return false;
		}

		mytype = document.getElementById('FormType');
		var selectedVal = mytype[mytype.selectedIndex].value;
		if (selectedVal != 'f') {
			find_lists = CountLists();
			if (!find_lists) {
				alert('%%LNG_ChooseFormLists%%');
				return false;
			}
		}

		formchanged = FormHasChanged();

		hidden_fieldorder = [];
		var fieldOrderBox = $('.FieldOrderHiddenValues');
		for(var i = 0, j = fieldOrderBox.size(); i < j; ++i) {
			hidden_fieldorder.push($(fieldOrderBox.get(i)).val());
		}
		document.getElementById('hidden_fieldorder').value = hidden_fieldorder.join(';');

		if (formchanged) {
			if (confirm("%%LNG_FormHasBeenChanged%%")) {
				return true;
			}
			return false;
		}

		return true;
	}

	function FormHasChanged() {
		if (FormLoaded <= 0) return;
		if (LoadFormType == 's' || LoadFormType == 'u') {
			return false;
		}

		var f = document.forms[0];
		for(i = 0; i < f.elements.length; i++) {
			if(f.elements[i].type == "checkbox") {
				if(f.elements[i].id.indexOf('list_') == 0) {
					lid = f.elements[i].id.replace('list_','');
					if(f.elements[i].checked) {
						if (!inArray(lid, LoadedLists)) {
							return true;
						}
					}
				}
			}
		}

		fieldorderbox = $('input[name="fieldorder[]"]');
		for (vk = 0; vk < fieldorderbox.length; vk++) {
			fldval = fieldorderbox[vk].value;
			if (fldval != LoadedOrder[vk]) {
				return true;
			}
		}

		design = document.getElementById('FormDesign');
		form_design = design[design.selectedIndex].value;

		if (LoadDesign != form_design) {
			return true;
		}

		// send to friend only needs to check the design.
		if (LoadFormType == 'f') {
			return false;
		}

		changeformat_checked = document.getElementById('SubscriberChangeFormat').checked;
		if (changeformat_checked != LoadChangeFormat) {
			return true;
		}

		// since nothing else has changed, we can assume the form has not been changed.
		return false;
	}

	function ShowPreview() {
		if (CheckForm()) {
			var f = document.forms[0];
			document.forms[0].target = "_blank";
			prevAction = document.forms[0].action;

			document.forms[0].action = 'index.php?Page=Forms&Action=Preview';
			document.forms[0].submit();
			document.forms[0].target = "";
			document.forms[0].action = prevAction;
		}
	}

	function CountLists() {
		var f = document.forms[0];
		found_lists = 0;
		for(i = 0; i < f.elements.length; i++) {
			if(f.elements[i].type == "checkbox") {
				if(f.elements[i].id.indexOf('list_') == 0) {
					if(f.elements[i].checked) {
						found_lists++;
					}
				}
			}
		}
		return found_lists;
	}

	function ChangeFormType(selectedVal) {
		var elementToHide = [];
		var elementToShow = [];
		var allowableValues = [];
		var checkFormat = true;

		switch(selectedVal) {
			case 'u':
				elementToHide = [	'#chooseformatstyle', '#contactformstyle', '#changeformatstyle'];

				elementToShow = [	'#sendthanks', '#requireconfirm', '#chooseliststyle1', '#chooseliststyle2',
									'#chooseliststyle3', '#chooseliststyle4', '#ChooseListOptionMessage', '#captchaformstyle'];

				allowableValues = ['e', 'cl'];
				checkFormat = false;
			break;
			case 'm':
				elementToHide = [	'#sendthanks', '#requireconfirm', '#contactformstyle', '#chooseformatstyle'];

				elementToShow = [	'#chooseliststyle1', '#chooseliststyle2', '#chooseliststyle3', '#chooseliststyle4',
									'#changeformatstyle', '#ChooseListOptionMessage', '#captchaformstyle'];

				allowableValues = ['e', 'cl'];
				checkFormat = false;
			break;
			case 'f':
				elementToHide = [	'#sendthanks', '#requireconfirm',
									'#chooseliststyle1', '#chooseliststyle2', '#chooseliststyle3', '#chooseliststyle4',
									'#chooseformatstyle', '#contactformstyle', '#captchaformstyle', '#changeformatstyle', '#ChooseListOptionMessage'];

				allowableValues = ['e'];
				checkFormat = false;
			break;
			default:
				elementToHide = ['#changeformatstyle'];

				elementToShow = [	'#sendthanks', '#requireconfirm',
									'#chooseliststyle1', '#chooseliststyle2', '#chooseliststyle3', '#chooseliststyle4',
									'#chooseformatstyle', '#contactformstyle', '#captchaformstyle', '#ChooseListOptionMessage'];

				allowableValues = ['e'];
			break;
		}

		if(elementToHide.length != 0) $(elementToHide.join(', ')).hide();
		if(elementToShow.length != 0) $(elementToShow.join(', ')).show();

		var tempListCount = CountLists();
		var tempFieldOrder = $('.FieldOrderHiddenValues');
		for(var i = 0, j = tempFieldOrder.size(); i < j; ++i) {
			var tempRemove = true;

			if(jQuery.inArray(tempFieldOrder.get(i).value, allowableValues) != -1) tempRemove = false;
			if(tempFieldOrder.get(i).value == 'cl' && tempListCount <= 1) tempRemove = true;

			if(tempRemove) RemoveFieldOrderValue(tempFieldOrder.get(i));
		}


		if(checkFormat && $('#SubscriberChooseFormat').val() == 'c') CheckFormat();
	}

	function ChangeOrderOptions(selectedVal) {
		if (selectedVal == 'c') CheckFormat();
		else {
			var fieldOrderBox = $('.FieldOrderHiddenValues');

			for(var i = 0, j = fieldOrderBox.size(); i < j; ++i) {
				if(fieldOrderBox.get(i).value == 'cf') {
					RemoveFieldOrderValue(fieldOrderBox.get(i));
					break;
				}
			}
		}
	}

	function ChangeDisplayOrderOptions(fld) {
		if(fld.checked) CheckFormat();
		else {
			var fieldOrderBox = $('.FieldOrderHiddenValues');

			for(var i = 0, j = fieldOrderBox.size(); i < j; ++i) {
				if(fieldOrderBox.get(i).value == 'cf') {
					RemoveFieldOrderValue(fieldOrderBox.get(i));
					break;
				}
			}
		}

	}

	function CheckFormat() {
		var foundFomat = false;
		var fieldOrderBox = $('.FieldOrderHiddenValues');

		for(var i = 0, j = fieldOrderBox.size(); i < j; ++i) {
			if(fieldOrderBox.get(i).value == 'cf') {
				foundFomat = true;
				break;
			}
		}

		if (!foundFomat) AddFieldOrderValue(RequiredFields_Values['cf'], 'cf');
	}

	function AddFieldOrderValue(text, value) {
		var elm = $('<li id="SortFieldLI' + SortFieldNum + '" style="cursor: move; background-color:#E3E3E3">'
					+ '<input type="hidden" name="fieldorder[]" class="FieldOrderHiddenValues" value="' + value + '" />'
					+ '<span></span>'
					+'</li>');
		$(elm.children().get(1)).text(text);
		elm.appendTo($('ul#FieldOrderList'));

		window.setTimeout("$('#SortFieldLI" + SortFieldNum + "').css('background-color', '#EAEAEA');", 500);
		window.setTimeout("$('#SortFieldLI" + SortFieldNum + "').css('background-color', '#F0F0F0');", 600);
		window.setTimeout("$('#SortFieldLI" + SortFieldNum + "').css('background-color', '#FFF');", 700);

		SortFieldNum++;
		$('ul#FieldOrderList').sortable('refresh');
	}

	function RemoveFieldOrderValue(item) {
		$(item).parent().remove();
		$('ul#FieldOrderList').sortable('refresh');
	}

	function ResizeSortBox() {
		sortbox_height = ((($('#FieldOrderList').children().size()) * 25)+20);

		if(sortbox_height > 244) {
			sortbox_height = 244;
		}

		$('#SortFieldList').css('height', sortbox_height + 'px');
		window.setTimeout('ResizeSortBox();', '300');
	}

	$(function() {
		$(document.frmFormEditor).submit(function(event) {
			if(!CheckForm()) {
				event.preventDefault();
				event.stopPropagation();
			}
		});

		$('.CancelButton', document.frmFormEditor).click(function() {
			 if(confirm("%%GLOBAL_CancelButton%%")) {
			 	document.location="index.php?Page=Forms"
			 }
		});

		$('.hrefShowPreview').click(function(event) {
			event.preventDefault();
			event.stopPropagation();
			ShowPreview();
		});

		$(document.frmFormEditor.FormType).change(function() {
			ChangeFormType($(this).val());
		});

		$(document.frmFormEditor.SubscriberChooseFormat).change(function() {
			ChangeOrderOptions($(this).val());
		});

		$(document.frmFormEditor.SubscriberChangeFormat).click(function() {
			ChangeDisplayOrderOptions(this);
		});

		$('.ListToInclude').click(function() {
			var id = $(this).attr('id').match(/list_(\d*)/);
			if(id.length != 2) return;
			ShowFields(this, id[1]);
		});

		$('.ListCustomFieldToInclude').click(function() {
			UpdateOrderBox(this);
		});

		$('ul#FieldOrderList').sortable();
		ResizeSortBox();
	});

</script>

<form name="frmFormEditor" method="post" action="index.php?Page=Forms&Action=%%GLOBAL_Action%%">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%GLOBAL_Heading%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%GLOBAL_Intro%%
				</p>
			</td>
		</tr>
		<tr>
			<td>
				%%GLOBAL_Message%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_Next%%" />
				<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%" />
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%GLOBAL_FormDetails%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_ChooseAFormType%%:&nbsp;
						</td>
						<td>
							<select name="FormType" id="FormType">
								%%GLOBAL_FormTypeList%%
							</select>&nbsp;&nbsp;&nbsp;%%LNG_HLP_FormType%%
						</td>
					</tr>
					<tr>
						<td width="10%" class="FieldLabel">
							<img src="images/blank.gif" width="220" height="1" /><br />
							{template="Required"}
							%%LNG_NameThisForm%%:&nbsp;
						</td>
						<td width="90%">
							<input type="text" name="FormName" class="Field250" value="%%GLOBAL_FormName%%">&nbsp;%%LNG_HLP_FormName%%
							<div class="aside">%%LNG_FormName_Hint%%</div>
						</td>
					</tr>
					{capture name=forcedoubleoptin}%%GLOBAL_ForceDoubleOptIn%%{/capture}
					{if $forcedoubleoptin == 1}
						<input type="hidden" id="RequireConfirmation" name="RequireConfirmation" value="1" />
					{else}
					<tr id="requireconfirm" style="display: %%GLOBAL_RequireConfirmStyle%%">
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_RequireConfirmation%%?&nbsp;
						</td>
						<td>
							<label for="RequireConfirmation"><input type="checkbox" id="RequireConfirmation" name="RequireConfirmation" value="1" %%GLOBAL_RequireConfirmation%%>%%LNG_RequireConfirmationExplain%%</label> %%LNG_HLP_RequireConfirmation%%
						</td>
					</tr>
					{/if}
					<tr id="sendthanks" style="display: %%GLOBAL_SendThanksStyle%%">
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SendThanks%%&nbsp;
						</td>
						<td>
							<label for="SendThanks"><input type="checkbox" name="SendThanks" id="SendThanks" value="1" %%GLOBAL_SendThanks%%>%%LNG_SendThanksExplain%%</label> %%LNG_HLP_SendThanks%%
						</td>
					</tr>
					<tr id="contactformstyle" style="display: %%GLOBAL_ContactFormStyle%%">
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_ContactForm%%?&nbsp;
						</td>
						<td>
							<label for="ContactForm"><input type="checkbox" name="ContactForm" id="ContactForm" value="1" %%GLOBAL_ContactForm%%>%%LNG_ContactFormExplain%%</label> %%LNG_HLP_ContactForm%%
						</td>
					</tr>
					<tr>
						<td colspan="2" class="EmptyRow">&nbsp;</td>
					</tr>






					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_FormAdvancedOptions%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_FormDesign%%:&nbsp;
						</td>
						<td>
							<select name="FormDesign" id="FormDesign">
								%%GLOBAL_DesignList%%
							</select>
							&nbsp;&nbsp;%%LNG_HLP_FormDesign%%<a class="hrefShowPreview" href="#"><img src="images/magnify.gif" border="0" hspace="5"></a><a class="hrefShowPreview" href="#">%%LNG_PreviewThisDesign%%</a>
						</td>
					</tr>
					<tr id="chooseformatstyle" style="display: %%GLOBAL_ChooseFormatStyle%%">
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_UserChooseFormat%%:&nbsp;
						</td>
						<td>
							<select id="SubscriberChooseFormat" name="SubscriberChooseFormat">
								%%GLOBAL_SubscriberChooseFormat%%
							</select>&nbsp;&nbsp;&nbsp;%%LNG_HLP_SubscriberChooseFormat%%
						</td>
					</tr>
					<tr id="changeformatstyle" style="display: %%GLOBAL_ChangeFormatStyle%%">
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_SubscriberChangeFormat%%:&nbsp;
						</td>
						<td>
							<input type="checkbox" id="SubscriberChangeFormat" name="SubscriberChangeFormat" value="1"%%GLOBAL_SubscriberChangeFormat%% /><label for="SubscriberChangeFormat">%%LNG_SubscriberChangeFormatExplain%%</label>
							&nbsp;%%LNG_HLP_SubscriberChangeFormat%%
						</td>
					</tr>
					<tr id="captchaformstyle" style="display: %%GLOBAL_CaptchaFormStyle%%">
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_UseCaptcha%%?&nbsp;
						</td>
						<td>
							{if IEM_Support::hasGdLibrary()}
							<label for="UseCaptcha"><input type="checkbox" name="UseCaptcha" id="UseCaptcha" value="1" %%GLOBAL_UseCaptcha%%>%%LNG_UseCaptchaExplain%%</label> %%LNG_HLP_UseCaptcha%%
							{else}
							<p><em>%%LNG_UseCaptchaNoGd%%</em></p>
							{/if}
						</td>
					</tr>
					<tr id="chooseliststyle3" style="display: %%GLOBAL_ChooseListOptionsStyle%%">
						<td colspan="2" class="EmptyRow">&nbsp;</td>
					</tr>
					<tr id="chooseliststyle1" style="display: %%GLOBAL_ChooseListOptionsStyle%%">
						<td colspan="2" class="Heading2">&nbsp;&nbsp;%%LNG_ListsToInclude%%</td>
					</tr>
					<tr id="ChooseListOptionMessage" style="display: %%GLOBAL_ChooseListOptionsMessageStyle%%">
						<td colspan="2">%%GLOBAL_FormCustomFieldMessage%%</td>
					</tr>
					<tr id="chooseliststyle2" style="display: %%GLOBAL_ChooseListOptionsStyle%%">
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_IncludeLists%%
						</td>
						<td>
							<div id="ListsToUse" style="width: 446px; height: 244px; overflow: auto; border:1px solid #7F9DB9; background-color: #ffffff;">
								%%GLOBAL_MailingListBoxes%%
							</div>
						</td>
					</tr>
					<tr id="chooseliststyle4" style="display: %%GLOBAL_ChooseListStyle%%">
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_OrderCustomFields%%
						</td>
						<td>
							<div id="SortFieldList" style="width: 446px; height: 244px; overflow: auto; border:1px solid #7F9DB9; background-color: #ffffff;">
								<ul id="FieldOrderList" style="list-style-type: none; cursor: default; padding: 0; margin: 0; margin-left: 3px;">
									%%GLOBAL_FieldOrderList%%
								</ul>
							</div>
							<div class="aside">%%LNG_FormCustomFieldSortExplain%%</div>
						</td>
					</tr>
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td valign="top" height="30">
							<input type="submit" value="%%LNG_Next%%" class="FormButton" />
							<input type="button" value="%%LNG_Cancel%%" class="FormButton CancelButton" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<input type="hidden" name="hidden_fieldorder" value="" id="hidden_fieldorder">
</form>
