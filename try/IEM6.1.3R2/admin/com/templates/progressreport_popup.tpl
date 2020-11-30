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
		background: url(images/progressbar.gif) no-repeat -300px 0px;
		text-align: center;
		font-weight: bold;
	}

	#ProgressReportStatus {
		text-align: center;
	}

	#ProgressReportWindow_Close {
		float: right;
		cursor: pointer;
		display: none;
	}
</style>
<div id="ProgressReportContainer">
	<div id="ProgressReportWindow_Close">
		<a href="#" id="ProgressReportWindow_CloseButton">%%LNG_PopupCloseWindow%%</a>
	</div>
	<div id="ProgressReportTitle" class="Heading1">%%GLOBAL_ProgressTitle%%</div>
	<br />
	<div id="ProgressReportMessage" class="body pageinfo"><p>%%GLOBAL_ProgressMessage%%</p></div>
	<br />
	<div id="ProgressReportReport" class="body">%%GLOBAL_ProgressReport%%</div>
	<br />
	<div id="ProgressReportProgress">
		<div id="ProgressReportProgressBar">&nbsp;</div>
		<div id="ProgressReportProgressNumber">&nbsp;</div>
	</div>
	<div id="ProgressReportStatus" class="intro">%%GLOBAL_ProgressStatus%%</div>
</div>
<!-- iframe which does all of the work -->
<iframe id="fmeWorker" width="1" height="1" frameborder="0" border="0"></iframe>
<script>
	setTimeout(function() {
		var e = document.getElementById('fmeWorker');
		e.src = '%%GLOBAL_ProgressURLAction%%';
	}, 150);

	function UpdateStatus(status, percentage)
	{
		var f = document.getElementById('ProgressReportProgressBar');
		f.style.background = 'url(images/progressbar.gif) no-repeat -' + (300 - (percentage * 3)) + 'px 0px';
		f.innerHTML = parseInt(percentage) + "%";
		document.getElementById('ProgressReportStatus').innerHTML = status;
	}

	function UpdateStatusReport(report)
	{
		document.getElementById('ProgressReportReport').innerHTML = report;
	}

	$('#ProgressReportWindow_CloseButton').click(function(event) {
		parent.tb_remove();
	});
</script>
