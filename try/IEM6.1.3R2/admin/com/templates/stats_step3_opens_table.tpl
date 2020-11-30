	<table width="100%" cellpadding="5" border="0" cellspacing="1" class="Text" style="padding-top: 0px; margin-top: 0px;">
			<tr>
				<td width="100%" colspan="3">
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td valign="top">
								&nbsp;
							</td>
							<td valign="top" align="right">
								%%GLOBAL_Paging%%
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr class="Heading3">
				<td width="30%" nowrap align="left">
					%%LNG_EmailAddress%%
 					<a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','email','up')"><img src="images/sortup.gif" width="8" height="10" style="border: 0px;"></a>
					<a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','email','down')"><img src="images/sortdown.gif" width="8" height="10" style="border: 0px;"></a>
				</td>
				<td width="50%" nowrap align="left">
					%%LNG_DateOpened%%
                                        <a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','opened','up')"><img src="images/sortup.gif" width="8" height="10" style="border: 0px;"></a>
					<a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','opened','down')"><img src="images/sortdown.gif" width="8" height="10" style="border: 0px;"></a>
				</td>
				<td width="20%" nowrap align="left">
					%%LNG_OpenedEmailAsType%%
					<a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','type','up')"><img src="images/sortup.gif" width="8" height="10" style="border: 0px;"></a>
					<a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','type','down')"><img src="images/sortdown.gif" width="8" height="10" style="border: 0px;"></a>
				</td>
			</tr>
			%%GLOBAL_Stats_Step3_Opens_List%%
			<tr>
				<td align="right" colspan="3">
					%%GLOBAL_PagingBottom%%
				</td>
			</tr>
		</table>