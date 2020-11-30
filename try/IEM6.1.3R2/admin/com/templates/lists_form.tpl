<script src="includes/js/jquery.js"></script>
<script src="includes/js/jquery/form.js"></script>
<script src="includes/js/jquery/thickbox.js"></script>
<link rel="stylesheet" type="text/css" href="includes/styles/thickbox.css" />
<script>
	$(function() {
		$(document.frmListEditor).submit(function(event) {
			try {

				var fieldNames = ["Name", "OwnerName", "OwnerEmail", "ReplyToEmail", "BounceEmail"];
				var emptyToks = ["%%LNG_EnterListName%%", "%%LNG_EnterOwnerName%%", "%%LNG_EnterOwnerEmail%%", "%%LNG_EnterReplyToEmail%%", "%%LNG_EnterBounceEmail%%"];
				var invalidToks = ["%%LNG_ListNameIsNotValid%%", "%%LNG_ListOwnerNameIsNotValid%%", "%%LNG_ListOwnerEmailIsNotValid%%", "%%LNG_ListReplyToEmailIsNotValid%%", "%%LNG_ListBounceEmailIsNotValid%%"];
				var form = this;

				var fields = jQuery.map(fieldNames, function(el, i) {
					return form.elements[el];
				});

				var error = false;
				jQuery.each(fields, function(i, el){
					if (el.value == '') {
						error = emptyToks[i];
						el.focus();
						return false;
					} else if (fieldNames[i].indexOf('Email') != -1 && !isValidEmail(el.value)) {
						error = invalidToks[i];
						el.focus();
						return false;
					}
				});

				if (error) {
					alert(error);
					return false;
				}

				var count = 0;
				for (var i = 0; i < $('#fields')[0].options.length; i++) {
					if ($('#fields')[0].options[i].selected) { count++; break; }
				}

				if (count == 0) {
					alert("%%LNG_SelectFields%%");
					$('#fields')[0].focus();
					return false;
				}

				return true;

			} catch(e) {
				alert('Unable to validate');
				return false;
			}
		});

		$('.CancelButton', document.frmListEditor).click(function() { if (confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Lists" } });

		$('.form_text', document.frmListEditor).focus(function() { this.select(); });

		$('#availablefields').click(AvailableFieldsClicked);
	});

	function AvailableFieldsClicked() {
		var availableFields = $('#availablefields').get(0);
		var visibleFields = $('#fields').get(0);
		for(var i = 0, j = availableFields.options.length; i < j; ++i) {
			var currentValue = availableFields.options[i].value;
			var currentText = availableFields.options[i].text;
			var entryInVisibleFields = $('ul li input[@value='+currentValue+']', visibleFields).get(0);

			if(availableFields.options[i].selected) {
				if(entryInVisibleFields) continue;

				var newIndex = document.frmListEditor['VisibleFields[]'].options.length;
				var newOption = new Option(currentText, currentValue);
				document.frmListEditor['VisibleFields[]'].options[newIndex] = newOption;
				$(ISSelectReplacement.add_option(document.frmListEditor['VisibleFields[]'], newOption, newIndex)).appendTo($('ul', visibleFields));
			} else {
				if(!entryInVisibleFields) continue;

				$(entryInVisibleFields).parent().remove();
				$('option[@value='+currentValue+']', document.frmListEditor['VisibleFields[]']).remove();
			}
		}
	}
</script>
<form name="frmListEditor" id="frmListEditor" method="post" action="index.php?Page=Lists&Action=%%GLOBAL_Action%%">
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
				<input class="FormButton SubmitButton" type="submit" value="%%LNG_Save%%">
				<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%" />
				<br />&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%GLOBAL_ListDetails%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_ListName%%:&nbsp;
						</td>
						<td>
							<input type="text" name="Name" class="Field250 form_text" value="%%GLOBAL_Name%%">&nbsp;%%LNG_HLP_ListName%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_ListOwnerName%%:&nbsp;
						</td>
						<td>
							<input type="text" name="OwnerName" class="Field250 form_text" value="%%GLOBAL_OwnerName%%">&nbsp;%%LNG_HLP_ListOwnerName%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_ListOwnerEmail%%:&nbsp;
						</td>
						<td>
							<input type="text" name="OwnerEmail" class="Field250 form_text" value="%%GLOBAL_OwnerEmail%%">&nbsp;%%LNG_HLP_ListOwnerEmail%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_ListReplyToEmail%%:&nbsp;
						</td>
						<td>
							<input type="text" name="ReplyToEmail" class="Field250 form_text" value="%%GLOBAL_ReplyToEmail%%">&nbsp;%%LNG_HLP_ListReplyToEmail%%
						</td>
					</tr>
					<tr style="display: %%GLOBAL_ShowBounceInfo%%">
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_ListBounceEmail%%:&nbsp;
						</td>
						<td>
							<input type="text" name="BounceEmail" class="Field250 form_text" value="%%GLOBAL_BounceEmail%%">&nbsp;%%LNG_HLP_ListBounceEmail%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_NotifyOwner%%:&nbsp;
						</td>
						<td>
							<label for="NotifyOwner"><input type="checkbox" name="NotifyOwner" id="NotifyOwner" value="1" %%GLOBAL_NotifyOwner%%>%%LNG_NotifyOwnerExplain%%</label> %%LNG_HLP_NotifyOwner%%
						</td>
					</tr>

					<tr %%GLOBAL_ShowCustomFields%%>
						<td class="EmptyRow" colspan="2">
							&nbsp;
						</td>
					</tr>
					<tr %%GLOBAL_ShowCustomFields%%>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_Add_Customfields_To_List%%
						</td>
					</tr>
					<tr %%GLOBAL_ShowCustomFields%%>
						<td class="FieldLabel">
							%%LNG_AddTheseFields%%:
						</td>
						<td>
							<select id="availablefields" name="AvailableFields[]" multiple="multiple" class="ISSelectReplacement" style="%%GLOBAL_VisibleFields_Style%%" onClick="AvailableFieldsClicked();">
								%%GLOBAL_AvailableFields%%
							</select>&nbsp;%%LNG_HLP_AddTheseFields%%
						</td>
					</tr>

					<tr>
						<td class="EmptyRow" colspan="2">
							&nbsp;
						</td>
					</tr>
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_VisibleFields%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							%%LNG_ShowTheseFields%%:
						</td>
						<td>
							<select id="fields" name="VisibleFields[]" multiple="multiple" class="ISSelectReplacement" style="%%GLOBAL_VisibleFields_Style%%">
								%%GLOBAL_VisibleFields%%
							</select>&nbsp;%%LNG_HLP_VisibleFields%%
						</td>
					</tr>
					<tr>
						<td class="EmptyRow" colspan="2">
							&nbsp;
						</td>
					</tr>
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_PredefinedCustomFields%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_CompanyName%%:&nbsp;
						</td>
						<td>
							<input type="text" name="CompanyName" class="Field250 form_text" value="%%GLOBAL_CompanyName%%">&nbsp;%%LNG_HLP_CompanyName%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_CompanyAddress%%:&nbsp;
						</td>
						<td>
							<input type="text" name="CompanyAddress" class="Field250 form_text" value="%%GLOBAL_CompanyAddress%%">&nbsp;%%LNG_HLP_CompanyAddress%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_CompanyPhone%%:&nbsp;
						</td>
						<td>
							<input type="text" name="CompanyPhone" class="Field250 form_text" value="%%GLOBAL_CompanyPhone%%" maxlength="20">&nbsp;%%LNG_HLP_CompanyPhone%%
						</td>
					</tr>
					{template="bounce_details"}
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel">&nbsp;</td>
						<td valign="top" height="30">
							<input class="FormButton SubmitButton" type="submit" value="%%LNG_Save%%" />
							<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
