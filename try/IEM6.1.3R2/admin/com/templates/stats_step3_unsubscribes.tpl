<div id="div5" style="display:none">
	<div class="body">
		<br>%%GLOBAL_DisplayUnsubscribesIntro%%
		<br><br>
	</div>

	<div>
		%%GLOBAL_Calendar%%
	</div>
	<br>

<table width="100%" border="0" class="Text">
		<tr><td valign=top width="250" rowspan="2">
		<div class="MidHeading" style="width:100%"><img src="images/m_stats.gif" width="20" height="20" align="absMiddle">&nbsp;%%LNG_Unsubscribe_Summary%%</div>
						<UL class="Text"> 
							<LI>%%LNG_Stats_TotalUnsubscribes%%: %%GLOBAL_TotalUnsubscribes%%</li>
							<LI>%%LNG_Stats_MostUnsubscribes%%: %%GLOBAL_MostUnsubscribes%%</li>
						</UL>
		</td>
		<td align="center">
		  %%GLOBAL_UnsubscribeChart%%
		</td>
		</tr>
	</table>
		<!-- stats_step3_unsubscribes_table -->
		%%GLOBAL_Loading_Indicator%%
                <div id="adminTable%%GLOBAL_TableType%%"></div>
        
                <script>
                  REMOTE_admin_table($("#adminTable%%GLOBAL_TableType%%"),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_TableToken%%',1,'email','up');
                </script>
		<br><br>
	</div>
</div>
