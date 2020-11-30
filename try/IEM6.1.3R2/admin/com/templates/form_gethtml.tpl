<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">
			%%LNG_FormGetHTML_Heading%%
		</td>
	</tr>
	<tr>
		<td class="body pageinfo">
			<p>
				%%LNG_FormGetHTML_Introduction%%
			</p>
		</td>
	</tr>
	<tr>
		<td>
			<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
				<tr>
					<td colspan="2" class="Heading2">
						&nbsp;&nbsp;%%LNG_FormHTML%%
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<textarea onclick="this.select()" name="code" style="width:99%" rows="20">%%GLOBAL_HTMLCode%%</textarea>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<form name="frmOK" action="index.php" method="GET">
	<input type="hidden" name="Page" value="Forms" />
	<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
		<tr>
			<td valign="top" height="30">
				<input class="FormButton submitButton" type="submit" value="%%LNG_OK%%" />
			</td>
		</tr>
	</table>
</form>