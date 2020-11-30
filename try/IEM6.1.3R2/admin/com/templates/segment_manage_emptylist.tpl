<script>
	$(function() {
		if($('#sectionAddButton').html() != 'NONE') $('#sectionAddButtonContainer').show();
	});
</script>
<table cellspacing="0" cellpadding="3" width="95%" align="center">
	<tr><td class="Heading1">%%LNG_SegmentManage%%</td></tr>
	<tr><td class="Intro">%%LNG_Help_SegmentManage%%</td></tr>
	<tr><td>%%GLOBAL_Message%%</td></tr>
	<tr id="sectionAddButtonContainer" style="display:none;">
		<td class="body">
			<br />
			<div id="sectionAddButton">%%GLOBAL_List_AddButton%%</div>
		</td>
	</tr>
</table>