<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">%%GLOBAL_Heading%%</td>
	</tr>
	<tr>
		<td class="body pageinfo"><p>%%GLOBAL_Introduction%%</p></td>
	</tr>
	<tr>
	<tr>
		<td class="body">
			%%TPL_Paging%%
			<input class="FormButton" type="button" value="%%LNG_Back%%" onClick='document.location="%%GLOBAL_BackLink%%";'>
			<br />
			&nbsp;
			<table border=0 cellspacing="1" cellpadding="2" width=100% class="Text">
				<tr class="Heading3">
					<td width="4%">
						&nbsp;
					</td>
					<td>
						%%LNG_SubscriberEmail%%
					</td>
					<td width="20%">
						%%LNG_ClickURL%%
					</td>
					<td width="20%">
						%%GLOBAL_TimeHeading%%
					</td>
					<td width="10%">
						%%GLOBAL_IPHeading%%
					</td>
				</tr>
				%%TPL_Stats_SubArea_Row%%
			</table>
			%%TPL_Paging_Bottom%%
		</td>
	</tr>
</table>
