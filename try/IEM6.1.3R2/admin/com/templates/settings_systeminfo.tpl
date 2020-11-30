<table cellspacing="0" cellpadding="0" width="100%" align="center" style="margin-left: 4px;">
	<tr>
		<td class="Heading1">%%LNG_Settings_SystemInformation%%</td>
	</tr>
	<tr>
		<td class="body pageinfo"><p>%%LNG_Help_Settings_SystemInformation%%</p></td>
	</tr>
	<tr>
		<td>
			<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
				<tr>
					<td colspan="2" class="Heading2">
						<div style="float:right;">
							<a href="index.php?Page=Settings&Action=showinfo" target="_blank">%%LNG_ShowFullSystemInfo%%</a>
						</div>
						&nbsp;&nbsp;%%LNG_ServerInfo%%
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{template="Not_Required"}
						%%LNG_ProductVersion%%:
					</td>
					<td>
						%%GLOBAL_ProductVersion%%
					</td>
				</tr>
				<tr style="display: %%GLOBAL_ShowProd%%">
					<td class="FieldLabel">
						{template="Not_Required"}
						%%LNG_ProductEdition%%:
					</td>
					<td>
						%%GLOBAL_ProductEdition%%
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{template="Not_Required"}
						%%LNG_Charset%%:
					</td>
					<td>
						<input type="hidden" name="defaultcharset" value="%%GLOBAL_DefaultCharset%%">
						%%GLOBAL_CharsetDescription%%
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{template="Not_Required"}
						%%LNG_ServerTimeZone%%:
					</td>
					<td valign="top">
						<input type="hidden" name="servertimezone" value="%%GLOBAL_ServerTimeZone%%">
						%%GLOBAL_ServerTimeZoneDescription%%
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{template="Not_Required"}
						%%LNG_CurrentServerTime%%:
					</td>
					<td>
						%%GLOBAL_ServerTime%%
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{template="Not_Required"}
						%%LNG_PHPVersion%%:
					</td>
					<td>
						%%GLOBAL_PHPVersion%%
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{template="Not_Required"}
						%%LNG_SafeModeEnabled%%:
					</td>
					<td>
						%%GLOBAL_SafeModeEnabled%%
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{template="Not_Required"}
						%%LNG_ImapSupportFound%%:
					</td>
					<td>
						%%GLOBAL_ImapSupportFound%%
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{template="Not_Required"}
						%%LNG_CurlSupportFound%%:
					</td>
					<td>
						%%GLOBAL_CurlSupportFound%%
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{template="Not_Required"}
						%%LNG_DOMEnabled%%:
					</td>
					<td>
						%%GLOBAL_DOMEnabled%%
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{template="Not_Required"}
						%%LNG_GDVersion%%:
					</td>
					<td>
						%%GLOBAL_GDVersion%%
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{template="Not_Required"}
						%%LNG_ModSecurity%%:
					</td>
					<td>
						%%GLOBAL_ModSecurity%%
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{template="Not_Required"}
						%%LNG_ServerSoftware%%:
					</td>
					<td>
						%%GLOBAL_ServerSoftware%%
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{template="Not_Required"}
						%%LNG_DatabaseVersion%%:
					</td>
					<td>
						%%GLOBAL_DatabaseVersion%%
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
