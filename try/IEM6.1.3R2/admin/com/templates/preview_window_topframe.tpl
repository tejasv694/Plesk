<link rel="stylesheet" href="includes/styles/stylesheet.css" type="text/css">
<link rel="stylesheet" href="includes/styles/tabmenu.css" type="text/css">

<!--[if IE]>
<style type="text/css">
	@import url("includes/styles/ie.css");
</style>
<![endif]-->

<script>
	function switchView(i)
	{
	        if (top.frames[2]) { var display_frame = top.frames[2]; } // For email client compatibility page
	        else { var display_frame = top.frames[1]; } // For preview campaign page

		display_frame.document.location.href = '%%GLOBAL_APPLICATION_URL%%/admin/index.php?Page=Preview&Action=Display&Type=' + i;

		if (i == 'html') {
			document.getElementById('description').style.display = '';
			document.getElementById('description_hide').style.display = 'none';

			if (parent.document.getElementById("mainframe")) parent.document.getElementById("mainframe").cols = "18%, *";
		} else {
			document.getElementById('description').style.display = 'none';
			document.getElementById('description_hide').style.display = '';

			if (parent.document.getElementById("mainframe")) parent.document.getElementById("mainframe").cols = "0, *";
		}
	}

	function changeDisplay(newdisplay) {
		parent.frame_display.location = '%%GLOBAL_APPLICATION_URL%%/admin/index.php?Page=Preview&Action=Display&Type=' + newdisplay;

		var broken_location = '%%GLOBAL_APPLICATION_URL%%/admin/index.php?Page=Preview&Action=BrokenRules#';
		if (newdisplay != 'html') {
			broken_location += 'broken_rule_' + newdisplay;
		}
		parent.frame_broken.location = broken_location;
	}
</script>

<body style="background-color: #F3F2E9; padding: 0;">
<table border="0" cellspacing="0" cellpadding="0" width="100%" style="padding-left: 8px; padding-top: 8px">
	<tr>
		<td style="font-family: Tahoma, Arial; font-size: 12px;">
			<div id="description" style="display: %%GLOBAL_ShowDescription%%">
				&nbsp;%%GLOBAL_DesignRules_Description%%
			</div>
			<div id="description_hide" style="display: %%GLOBAL_HideDescription%%">
				&nbsp;
			</div>
		</td>
		<td align="right">
			<select id="f" onChange="switchView(this.options[this.selectedIndex].value)">
				%%GLOBAL_SwitchOptions%%
			</select>
			&nbsp;
		</td>
	</tr>
</table>
