		<table cellpadding="5" border="0" cellspacing="1" width="100%" class="Text" style="padding-top: 0px; margin-top: 0px;">
			<tr>
				<td width="100%" colspan="3">
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td valign="top" align="left">
							   <div style="display: %%GLOBAL_DisplayStatsLinkList%%;">
								%%GLOBAL_StatsLinkDropDown%%
                                                            </div>
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
					%%LNG_LinkClicked%%
					<a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','url','up')"><img src="images/sortup.gif" width="8" height="10" style="border: 0px;"></a>
					<a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','url','down')"><img src="images/sortdown.gif" width="8" height="10" style="border: 0px;"></a>
				</td>
				<td width="20%" nowrap align="left">
					%%LNG_LinkClickTime%%
                                        <a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','clicked','up')"><img src="images/sortup.gif" width="8" height="10" style="border: 0px;"></a>
					<a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','clicked','down')"><img src="images/sortdown.gif" width="8" height="10" style="border: 0px;"></a>
				</td>
			</tr>
			%%GLOBAL_Stats_Step3_Links_List%%
			<tr>
				<td align="right" colspan="3">
					%%GLOBAL_PagingBottom%%
				</td>
			</tr>
		</table> 
