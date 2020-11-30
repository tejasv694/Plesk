<script>
	$(function() {
		if(%%GLOBAL_DisplayCreateButton%%)
			$('#sectionCreateButton').show();
	});
</script>
<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr><td class="Heading1">%%LNG_SegmentManage%%</td></tr>
	<tr><td class="Intro">%%LNG_Help_SegmentManage%%</td></tr>
	<tr><td class="body">%%GLOBAL_Message%%</td></tr>
	<tr id="sectionCreateButton" style="display:none;">
		<td class="body">
			<form name="frmCommands" action="index.php" method="get">
				<input type="hidden" name="Page" value="Segment" />
				<input type="hidden" name="Action" value="Create" />
				<input type="submit" value="%%LNG_SegmentManageCreateNew%%" title="%%LNG_SegmentManageCreateNew_Title%%" class="SmallButton" />
			</form>
		</td>
	</tr>
</table>

