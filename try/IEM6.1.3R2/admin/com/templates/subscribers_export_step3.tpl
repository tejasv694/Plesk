<form method="post" action="index.php?Page=Subscribers&Action=Export&SubAction=Step4" onsubmit="return CheckForm()">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%GLOBAL_Heading%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Subscribers_Export_Step3_Intro%%
				</p>
			</td>
		</tr>
		<tr>
			<td>
				%%GLOBAL_Message%%
				%%GLOBAL_SubscribersReport%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton" type="submit" value="%%LNG_NextButton%%" />
				<input class="FormButton cancel" type="button" value="%%LNG_Cancel%%" />
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_ExportFileType%%
						</td>
					</tr>
					<tr>
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_ExportFileType%%
						</td>
						<td>
							<div>
								<input id="SubscriberExportFileTypeCSV" type="radio" name="filetype" value="csv" checked="checked" onClick="ChooseFileType();" />
								<label for="SubscriberExportFileTypeCSV">%%LNG_ExportFileTypeCSV%%</label>
								&nbsp;%%LNG_HLP_ExportFileTypeCSV%%
							</div>
							<div>
								<input id="SubscriberExportFileTypeXML" type="radio" name="filetype" value="xml" onClick="ChooseFileType();" />
								<label for="SubscriberExportFileTypeXML">%%LNG_ExportFileTypeXML%%</label>
								&nbsp;%%LNG_HLP_ExportFileTypeXML%%
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="EmptyRow">
							&nbsp;
						</td>
					</tr>
					<tr id="SubscriberExportOptionHeading">
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_ExportOptions%%
						</td>
					</tr>
					<tr id="SubscriberExportOptionIncludeHeader">
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_IncludeHeader%%
						</td>
						<td>
							<select name="includeheader">
								<option value="1">%%LNG_Yes%%</option>
								<option value="0">%%LNG_No%%</option>
							</select>&nbsp;&nbsp;%%LNG_HLP_IncludeHeader%%
						</td>
					</tr>
					<tr id="SubscriberExportOptionFieldSeparator">
						<td width="200" class="FieldLabel">
							{template="Required"}
							%%LNG_Export_FieldSeparator%%:
						</td>
						<td>
							<input type="text" name="fieldseparator" value="," class="Field250">%%LNG_HLP_Export_FieldSeparator%%
						</td>
					</tr>
					<tr id="SubscriberExportOptionEnclosedBy">
						<td width="200" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_FieldEnclosedBy%%:
						</td>
						<td>
							<input type="text" name="fieldenclosedby" value='"' class="Field250">%%LNG_HLP_FieldEnclosedBy%%
						</td>
					</tr>
					<tr id="SubscriberExportOptionRowSeparator">
						<td colspan="2" class="EmptyRow">
							&nbsp;
						</td>
					</tr>
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_IncludeFields%%
						</td>
					</tr>
					%%GLOBAL_FieldOptions%%
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel">&nbsp;</td>
						<td valign="top" height="30">
							<input class="FormButton" type="submit" value="%%LNG_NextButton%%" />
							<input class="FormButton cancel" type="button" value="%%LNG_Cancel%%" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<script>
	function CheckForm() {
		var f = document.forms[0];

		if (f.fieldseparator.value == '') {
			alert("%%LNG_EnterFieldSeparator%%");
			f.fieldseparator.focus();
			return false;
		}

		return true;
	}

	function ChooseFileType() {
		var elements = ['SubscriberExportOptionHeading',
						'SubscriberExportOptionIncludeHeader',
						'SubscriberExportOptionFieldSeparator',
						'SubscriberExportOptionEnclosedBy',
						'SubscriberExportOptionRowSeparator'];
		for(var i = 0, j = elements.length; i < j; ++i)
			document.getElementById(elements[i]).style.display = document.forms[0].filetype[0].checked? '' : 'none';
	}

	/*
	 * This code is duplicated on all steps. I know, bad practice, but it's
	 * at least better than what was there before.
	 */
	jQuery(function($) {

		$('.cancel').bind('click', function() {
			if (confirm('%%LNG_Subscribers_Export_CancelPrompt%%')) {
				document.location = 'index.php?Page=Subscribers';
			}
		});
		
	});
</script>