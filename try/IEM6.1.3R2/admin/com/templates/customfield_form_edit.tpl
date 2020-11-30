<script>
	Application.Page.CustomFieldEdit = {
		onFormSubmitFunction: {},

		eventDOMReady: function(event) {
			$(document.cfForm).submit(Application.Page.CustomFieldEdit.eventFormSubmit);
			$('.CancelButton', document.cfForm).click(Application.Page.CustomFieldEdit.eventFormCancel);
		},

		eventFormSubmit: function(event) {
			for(var i in Application.Page.CustomFieldEdit.onFormSubmitFunction) {
				try {
					if(!Application.Page.CustomFieldEdit.onFormSubmitFunction[i]()) {
						event.stopPropagation();
						event.preventDefault();
						return false;
					}
				} catch(e) { }
			}

			// This function is called more than once so it's in javascript.js
			if(!ValidateCustomFieldForm('%%LNG_CustomField_NoFieldName%%', '%%LNG_CustomField_NoDefaultValue%%', '%%LNG_CustomFields_NoMultiValues%%')) {
				event.stopPropagation();
				event.preventDefault();
				return false;
			}
		},

		eventFormCancel: function(event) {
 			if(confirm("%%GLOBAL_CancelButton%%")) document.location="index.php?Page=CustomFields";
		}
	};

	Application.init.push(Application.Page.CustomFieldEdit.eventDOMReady);
</script>
<form name="cfForm" method="post" id="cfForm" action="index.php?Page=CustomFields&Action=%%GLOBAL_Action%%">
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
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel" id="customFieldsTable">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%GLOBAL_CustomFieldDetails%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							%%LNG_CustomFieldName%%:&nbsp;
						</td>
						<td>
							<input type="text" name="FieldName" id="FieldName" class="Field250" value="%%GLOBAL_FieldName%%">&nbsp;%%LNG_HLP_CustomFieldName%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_CustomFieldRequired%%&nbsp;
						</td>
						<td>
							<label for="FieldRequired"><input type="checkbox" id="FieldRequired" name="FieldRequired"%%GLOBAL_FieldRequired%%>%%LNG_CustomFieldRequiredExplain%%</label>
						</td>
					</tr>
					%%GLOBAL_SubForm%%
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td>
							<input class="FormButton" type="submit" value="%%LNG_Next%%" />
							<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
