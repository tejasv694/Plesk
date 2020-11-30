<script src="includes/js/jquery.js"></script>
<script>
	$(function() {
	
		var p = self.parent.Application.Page.BounceInfo;
		var params = p.getBounceParameters();

		if (!params) {
			params = '';
		}

		var url = 'index.php?Page=%%GLOBAL_Page%%&Action=TestBounceSettings';
		$('#TestBounceWindow_Message').load(url, encodeURIComponent(params));

		$('#TestBounceWindow_CloseButton').click(function(event) {
			p.closeBounceTest();
		});
	});
</script>
<style type="text/css" media="all">
	#TestBounceWindow_Container {
		padding: 0px;
		margin: 0px;
		width: auto;
	}

	#TestBounceWindow_Close {
		float: right;
		cursor: pointer;
	}

	#TestBounceWindow_MessageContainer {
		height: 105px;
		overflow: auto;
	}

	* html div #TestBounceWindow_MessageContainer {
		height: 115px;
	}

	#TestBounceWindow_Message {
		padding-left: 5px;
		padding-right: 5px;
	}

	#TestBounceWindow_Loading {
		text-align: center;
	}
</style>
<div id="TestBounceWindow_Container">
	<div id="TestBounceWindow_Close">
		<a href="#" id="TestBounceWindow_CloseButton">%%LNG_PopupCloseWindow%%</a>
	</div>
	<div class="Heading1">%%LNG_Bounce_TestHeading%%</div>
	<br />
	<div id="TestBounceWindow_MessageContainer" class="Message">
		<div id="TestBounceWindow_Message" class="Message">
			<div id="TestBounceWindow_Loading"><img src="images/loading.gif" alt="Loading..." /></div>
			<br />
			%%LNG_Bounce_StartTesting%%
		</div>
	</div>
	<br />
</div>
