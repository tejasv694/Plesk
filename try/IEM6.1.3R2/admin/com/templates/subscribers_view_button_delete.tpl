<input class="FormButton" type="button" style="width: 100px;" value="%%LNG_Subscribers_View_Button_Delete%%" onClick="ConfirmDelete('%%GLOBAL_subscriberid%%', '%%GLOBAL_list%%', %%GLOBAL_SegmentID%%)">
<script>
	function ConfirmDelete(subid, listid, SegmentID) {
		if (!subid) {
			return false;
		}

		if (confirm("%%LNG_DeleteSubscriberPrompt%%")) {
			var temp = 'index.php?Page=Subscribers&Action=Manage&SubAction=Delete&List=' + listid + '&id=' + subid;
			if (SegmentID && SegmentID != 0) temp += 'SegmentID=' + SegmentID
			document.location = temp;
			return true;
		}
		return false;
	}
</script>
