<script>
	function ImportCheck() {
		var checker = document.getElementById('newsletterurl');
		if (checker.value.length <= 7) {
			alert('%%LNG_Editor_ChooseWebsiteToImport%%');
			checker.focus();
			return false;
		}
		return true;
	}

	$(function() { if(%%GLOBAL_UsingWYSIWYG%% == 0) UsingWYSIWYG = false; });
</script>

	<tr>
		<td valign="top" width="150" class="FieldLabel">
			{template="Required"}
			%%LNG_HTMLContent%%:
		</td>
		<td valign="top">
			<table width="%%GLOBAL_EditorWidth%%" border="0" class="WISIWYG_Editor_Choices">
				<tr>
					<td width="20">
						<input onClick="switchContentSource('html', 1)" id="hct1" name="hct" type="radio" checked>
					</td>
					<td>
						<label for="hct1">%%LNG_HTML_Using_Editor%%</label><br>
					</td>
				</tr>
				<tr>
					<td width="20">
						<input onClick="switchContentSource('html', 2)" id="hct2" name="hct" type="radio">
					</td>
					<td>
						<label for="hct2">%%LNG_Editor_Upload_File%%</label><br>
					</td>
				</tr>
				<tr id="htmlNLFile" style="display:none">
					<td></td>
					<td>
						<img src="images/nodejoin.gif" alt="." align="top" />
						<iframe src="%%GLOBAL_APPLICATION_URL%%/admin/functions/remote.php?DisplayFileUpload&ImportType=HTML" frameborder="0" height="30" width="500" id="newsletterfile"></iframe>
					</td>
				</tr>
				<tr>
					<td width="20">
						<input onClick="switchContentSource('html', 3)" id="hct3" name="hct" type="radio">
					</td>
					<td>
						<label for="hct3">%%LNG_Editor_Import_Website%%</label>
					</td>
				</tr>
				<tr id="htmlNLImport" style="display:none">
					<td></td>
					<td>
						<img src="images/nodejoin.gif" alt="." />
						<input type="text" name="newsletterurl" id="newsletterurl" value="http://" class="Field" style="width:200px">
						<input class="FormButton" type="button" name="upload" value="%%LNG_ImportWebsite%%" onclick="ImportWebsite(this, '%%LNG_Editor_Import_Website_Wait%%', 'html', '%%LNG_Editor_ImportButton%%', '%%LNG_Editor_ProblemImportingWebsite%%')" class="Field" style="width:60px">
					</td>
				</tr>
			</table>
			<br>
			<div id="htmlEditor">
				%%GLOBAL_HTMLContent%%
			</div>
		</td>
	</tr>
	<tr id="htmlCF">
		<td>
			&nbsp;
		</td>
		<td>
			<input type="button" onclick="javascript: ShowCustomFields('html', 'myDevEditControl', '%%PAGE%%'); return false;" value="%%LNG_ShowCustomFields%%..." class="FormButton" style="width: 140px;" />
			<input type="button" onclick="javascript: InsertUnsubscribeLink('html'); return false;" value="%%LNG_InsertUnsubscribeLink%%" class="FormButton" style="width: 150px;" />
		</td>
	</tr>
