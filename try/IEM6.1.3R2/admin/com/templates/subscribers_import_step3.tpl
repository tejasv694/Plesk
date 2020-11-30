<script>
	$(document).ready(function() {
		$('tr.ImportRow').each(function() {
			//$(this).mouseover(function() { $(this).css('background-color', '#EFEFEF'); });
			//$(this).mouseout(function() { $(this).css('background-color', ''); });
		});
	});
</script>
<style>
tr.ImportRowHeader th {
	font-family:Tahoma,Arial;
	font-size:11px;
	font-weight: normal;
	color:#000000;
	text-align: left;
	padding-bottom: 10px;
}

tr.ImportRowHeader,
tr.ImportRow {
	padding: 4px 8px 4px 8px;
}

th.ImportColFromFile,
th.ImportColAvailable {
	padding: 3px 0 3px 0;
}

td.ImportColFromFile,
td.ImportColAvailable {

}
</style>
<form method="post" action="index.php?Page=Subscribers&Action=Import&SubAction=Step4" onsubmit="return CheckForm();" enctype="multipart/form-data">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Subscribers_Import_Step3%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Subscribers_Import_Step3_Intro%%
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
				<input class="FormButton" type="submit" value="%%LNG_NextButton%%">
				<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Import_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Import" }'>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_ImportFields%%
						</td>
					</tr>
					%%GLOBAL_ImportFieldList%%
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel">&nbsp;</td>
						<td valign="top" height="30">
							<input class="FormButton" type="submit" value="%%LNG_NextButton%%">
							<input class="FormButton" type="button" value="%%LNG_Cancel%%" onClick='if(confirm("%%LNG_Subscribers_Import_CancelPrompt%%")) { document.location="index.php?Page=Subscribers&Action=Import" }'>
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
		return true;
	}
</script>
