	<table width="100%" cellpadding="5" border="0" cellspacing="1" class="Text" style="padding-top: 0px; margin-top: 0px;">
			<tr>
				<td width="100%" colspan="4">
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
				<td nowrap align="left">
					%%LNG_ForwardedBy%%
					<a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','email','up')"><img src="images/sortup.gif" width="8" height="10" style="border: 0px;"></a>
					<a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','email','down')"><img src="images/sortdown.gif" width="8" height="10" style="border: 0px;"></a>
				</td>
				<td nowrap align="left">
					%%LNG_ForwardedTo%%
					<a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','recipient','up')"><img src="images/sortup.gif" width="8" height="10" style="border: 0px;"></a>
					<a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','recipient','down')"><img src="images/sortdown.gif" width="8" height="10" style="border: 0px;"></a>
				</td>
				<td nowrap align="left">
					%%LNG_ForwardTime%%
					<a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','time','up')"><img src="images/sortup.gif" width="8" height="10" style="border: 0px;"></a>
					<a href="javascript:REMOTE_admin_table($('#adminTable%%GLOBAL_TableType%%'),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_Token%%','%%GLOBAL_CurrentPage%%','time','down')"><img src="images/sortdown.gif" width="8" height="10" style="border: 0px;"></a>
				</td>
				<td nowrap align="left">
					%%LNG_HasSubscribed%%
				</td>
			</tr>
			%%GLOBAL_Stats_Step3_Forwards_List%%
			<tr>
				<td align="right" colspan="4">
					%%GLOBAL_PagingBottom%%
				</td>
			</tr>
		</table>