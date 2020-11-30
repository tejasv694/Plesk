<html><head><link rel="stylesheet" href="{$ApplicationUrl}includes/styles/stylesheet.css" type="text/css"></head>
<body class="popupBody"><div class="popupContainer">
<style type="text/css" media="all">
	#ProgressReportContainer {
		padding: 0px;
		margin: 0px;
		width: auto;
	}

	#ProgressReportProgress {
		margin: 0px;
		text-align: center;
	}

	#ProgressReportProgressBar {
		padding: 0px;
		height: 20px;
		margin: auto;
		width: 300px;
		border: 1px solid #CCCCCC;
		background: url("{$TemplateUrl}images/progressbar.gif") no-repeat -300px 0px;
		text-align: center;
		font-weight: bold;
	}

	#ProgressReportStatus {
		text-align: center;
	}
</style>
<div id="ProgressReportContainer">
	<div id="ProgressReportTitle" class="Heading1">{$lang.Addon_checkpermissions_ProgressTitle}</div>
	<br />
	<div id="ProgressReportMessage" class="body pageinfo"><p>{$lang.Addon_checkpermissions_ProgressIntro}</p></div>
	<br />
	<div id="ProgressReportReport" class="body"></div>
	<br />
	<div id="ProgressReportProgress">
		<div id="ProgressReportProgressBar">&nbsp;</div>
	</div>
	<div id="ProgressReportStatus" class="intro">&nbsp;</div>
</div>
<!-- iframe which does all of the work -->
<iframe id="fmeWorker" width="1" height="1" frameborder="0" border="0"></iframe>
<script>
	setTimeout(function() {
		var e = document.getElementById('fmeWorker');
		e.src = '{$AdminUrl}&AJAX=1&Action=CheckPermissions&r={$RandomValue}';
	}, 2);
	function UpdateStatus(status, percentage)
	{
		var f = document.getElementById('ProgressReportProgressBar');
		f.style.background = 'url("{$TemplateUrl}images/progressbar.gif") no-repeat -' + (300 - (percentage * 3)) + 'px 0px';
		f.innerHTML = parseInt(percentage) + "%";
		document.getElementById('ProgressReportStatus').innerHTML = status;
	}

	function UpdateStatusReport(report)
	{
		document.getElementById('ProgressReportReport').innerHTML = report;
	}

	function ProcessFinished()
	{
		self.parent.location = '{$AdminUrl}&Action=Finished';
	}

	function ProcessFailed()
	{
		self.parent.location = '{$AdminUrl}&Action=Finished';
	}
</script>

</div></body></html>
