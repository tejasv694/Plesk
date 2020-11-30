<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">%%LNG_Stats_Users_Step1_Heading%%</td>
	</tr>
	<tr>
		<td class="body pageinfo"><p>%%LNG_Stats_Users_Step1_Intro%%</p></td>
	</tr>
	<tr>
		<td>
			%%GLOBAL_Message%%
		</td>
	</tr>
	<tr>
		<td class="body">
			<table width="100%" border="0">
				<tr>
					<td valign="top">
					</td>
					<td align="right">
						%%TPL_Paging%%
					</td>
				</tr>
			</table>
			<form>
				<table border=0 cellspacing="0" cellpadding="2" width=100% class="Text">
					<tr class="Heading3">
						<td width="5"></td>
						<td width="30%">
							%%LNG_UserName%%&nbsp;<a href='index.php?Page=Stats&Action=User&SortBy=UserName&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=Stats&Action=User&SortBy=UserName&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border=0></a>
						</td>
						<td width="30%">
							%%LNG_FullName%%&nbsp;<a href='index.php?Page=Stats&Action=User&SortBy=Name&Direction=Up&%%GLOBAL_SearchDetails%%'><img src="images/sortup.gif" border=0></a>&nbsp;<a href='index.php?Page=Stats&Action=User&SortBy=Name&Direction=Down&%%GLOBAL_SearchDetails%%'><img src="images/sortdown.gif" border=0></a>
						</td>
						<td width="20%">
							%%LNG_Status%%
						</td>
						<td width="20%">
							%%LNG_UserType%%
						</td>
						<td width="100">
							%%LNG_Action%%
						</td>
					</tr>
				%%TPL_Stats_Users_Manage_Row%%
				</table>
			%%TPL_Paging_Bottom%%
		</td>
	</tr>
</table>
