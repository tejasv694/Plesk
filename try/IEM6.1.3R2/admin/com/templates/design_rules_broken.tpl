<html>
<head>
<script src="includes/js/jquery.js"></script>
<script>
	var cols = parent.document.getElementById("mainframe").cols;
	var hidden = false;
	var ruleList = 0;
	var ruleLoaded = 0;

	$(function() {
		ruleList = $('div.designRule').length;
		getBrokenRules();

		$('div#printBroken').click(function() {
			if(ruleList > ruleLoaded) alert('%%LNG_DesignRules_PrintNotFinishedLoading%%');
			else window.print();
		});

		$('div#toggleBroken a').click(function() {
			if(hidden) {
				$(this).removeClass('restore');
				$(this).addClass('minimize');
				parent.document.getElementById("mainframe").cols = cols;
				parent.document.getElementById('frame_broken').noResize = false;
				$('div#printBroken').show();
				$('div#previewIntro').show();
				$('div#previewList').show();
			} else {
				$(this).removeClass('minimize');
				$(this).addClass('restore');
				$('div#printBroken').hide();
				$('div#previewIntro').hide();
				$('div#previewList').hide();
				parent.document.getElementById("mainframe").cols = "23, *";
				parent.document.getElementById("mainframe").cols = "23, *"; // This second one is because ff has a problem when minimizing after resizing the frame
				parent.document.getElementById('frame_broken').noResize = true;
			}
			hidden = !hidden;
		});
	});

	function getBrokenRules() {
		if(ruleList > ruleLoaded) {
			var temp = $('div.designRule').get(ruleLoaded);
			$(temp).load(	'index.php?Page=Preview&Action=processEachRule',
							{'rulename':$(temp).attr('rulename')},
							function(responseText, status, response) {
								if(status == 'success') ++ruleLoaded;
								getBrokenRules();
							});
			temp = null;
		}
	}
</script>
<link rel="stylesheet" href="includes/styles/stylesheet.css" type="text/css" />
<style>
	body {
		background-color: #FFFFFF;
		margin: 0;
		padding: 0;
	}

	div#previewIntro,
	div#previewList,
	div#previewList div.designRule,
	div#previewList div.designRule div.designRuleName {
		display: block;
		clear: both;
	}

	div#previewIntro {
		padding: 5px 0 20px 0;
		margin: 5px 5px 10px 5px;
		border-bottom: 1px solid #666666;
	}

	div#previewList div.designRule {
		border: 1px solid #E9E9E9;
		margin: 5px 5px 10px 5px;
		background-color: #F9F9F9;
	}

	div#previewList div.designRule div.designRuleLoading {
		padding: 10px 4px 10px 4px;
		line-height: 16px;
		vertical-align: middle;
		text-align: left;
	}

	div#previewList div.designRule div.designRuleLoading {
		line-height: 16px;
		vertical-algin: middle;
	}

	div#previewList div.designRule div.designRuleName {
		line-height: 18px;
		background-color: #E4E4E4;
		border-bottom: 1px solid #E9E9E9;
		padding: 2px;
		font-weight: bold;
		font-size: 12px;
	}

	div#previewList div.designRule div.designRuleName img {
		vertical-align: middle;
	}

	div#previewList div.designRule div.designRuleOK {
		padding: 10px 0 10px 10px;
	}

	div#previewList div.designRule ul,
	div#previewList div.designRule li {
		margin: 0;
		padding: 0;
		list-style: none;
	}

	div#previewList div.designRule li {
		padding: 4px;
	}

	div#previewList div.designRule li.even {
		background-color: #EFEFEF;
	}

	div#previewList div.designRule li.odd {
		background-color: #FCFCFC;
	}

	div#printBroken,
	div#printBroken a {
		width: 20px;
		height: 20px;
	}

	div#printBroken {
		padding: 0px 10px 0px 10px;
		float: right;
	}

	div#printBroken a {
		display: block;
		background: url(images/printicon.gif) no-repeat;
		border: 1px solid #EFEFEF;
	}

	div#printBroken a:hover {
		background-color: #EFEFEF;
		border: 1px solid #DFDFDF;
	}


	div#toggleBroken,
	div#toggleBroken a {
		height: 13px;
		width: 13px;
	}

	div#toggleBroken {
		padding: 5px;
		float:	right;
	}

	div#toggleBroken a {
		display: block;
		border: 1px solid #EFEFEF;
	}

	div#toggleBroken a:hover {
		background-color: #EFEFEF;
		border: 1px solid #DFDFDF;
	}

	div#toggleBroken a.minimize {
		background: url(images/preview_minimize.gif) no-repeat;
	}

	div#toggleBroken a.minimize:hover {
		background: url(images/preview_minimize_hilite.gif) no-repeat;
	}

	div#toggleBroken a.restore {
		background: url(images/preview_restore.gif) no-repeat;
	}

	div#toggleBroken a.restore:hover {
		background: url(images/preview_restore_hilite.gif) no-repeat;
	}
</style>
</head>
<body>


	<div id="previewIntro">
	<div id="printBroken"><a href="javascript:void(0);" title="Print list"></a></div>
		%%LNG_DesignRules_Header%%
		%%LNG_DesignRules_Intro%%
	</div>
	<div id="previewList">%%GLOBAL_BrokenRuleList%%</div>
</body>
</html>
