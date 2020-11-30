<div id="div4" style="display:none">
	<div class="body">
		<br>%%GLOBAL_DisplayBouncesIntro%%
		<br><br>
	</div>

	<div>
		%%GLOBAL_Calendar%%
	</div>
	<br>

	<table width="100%" border="0" class="Text">
		<tr><td valign=top width="250" rowspan="2">
		<div class="MidHeading" style="width:100%"><img src="images/m_stats.gif" width="20" height="20" align="absMiddle">&nbsp;%%LNG_Bounce_Summary%%</div>
			<UL class="Text">
				<LI>%%LNG_Stats_TotalBounces%%%%GLOBAL_TotalBounceCount%%</li>
				<LI>%%LNG_Stats_TotalSoftBounces%%%%GLOBAL_TotalSoftBounceCount%%</li>
				<LI>%%LNG_Stats_TotalHardBounces%%%%GLOBAL_TotalHardBounceCount%%</li>
			</UL>
		</td>
		</tr>
		<tr>
			<td>
				%%GLOBAL_BounceChart%%
			</td>
		</tr>
	</table>

		<!-- stats_step3_bounces_table -->
		%%GLOBAL_Loading_Indicator%%
                <div id="adminTable%%GLOBAL_TableType%%"></div>
        
                <script>
                  REMOTE_admin_table($("#adminTable%%GLOBAL_TableType%%"),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_TableToken%%',1,'time','down');
                </script>
		<br><br>
	</div>
</div>

<script>
	function ChangeBounceType() {
		cbouncetype = document.getElementById('choosebt');
		selected = cbouncetype.selectedIndex;
		bouncetype = cbouncetype.options[selected].value;
		REMOTE_parameters = '&bouncetype=' + bouncetype;
		REMOTE_admin_table($("#adminTable%%GLOBAL_TableType%%"),'%%GLOBAL_TableURL%%','','%%GLOBAL_TableType%%','%%GLOBAL_TableToken%%',1,'time','down');
	}
</script>
