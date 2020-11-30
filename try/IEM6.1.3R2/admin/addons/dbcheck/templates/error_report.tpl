<html>
	<head>
		<link rel="stylesheet" href="{$ApplicationUrl}includes/styles/stylesheet.css" type="text/css" />
		<style type="text/css">
			#report_data {
				width: 100%;
				height: 325px;
			}
		</style>
	</head>

	<body class="popupBody">
		<p>{$lang.Addon_dbcheck_DisplayReport_Intro}</p>
		<form onsubmit="return false;">
			<textarea id="report_data" onclick="SelectOnce(this);" onblur="SelectReset();">{$report}</textarea>
		</form>
		<script>
			function SelectOnce(el)
			{
				if (typeof once == 'undefined' || once != true) {
					el.select();
					once = true;
				}
			}

			function SelectReset()
			{
				once = false;
			}
		</script>
	</body>
</html>
