<script src="includes/js/jquery.js"></script>
<script>
	function processCheckResult() {
		if ($('#html_is_spam').val() != 1 && $('#text_is_spam').val() != 1) {
			// Spam check passed.
			$('#SpamCheck_Message').html('');
			parent.Application.Modules.SpamCheck.check_passed = true;
			parent.tb_remove();
			parent.Application.Modules.SpamCheck.form.submit();
		} else {
			// Spam check failed.
			$('#ForceInfo').show();
			$('#Disclaimer').show();
			parent.$('#TB_ajaxWindowTitle').html('{$lang.Spam_Check_Failed}');
		}
	}

	$(function() {
		var temp = '';
		if ((temp = self.parent.getMessage()) == false) {
			temp = '';
		}
		var callback = function() {};
		{if $force}
			parent.Application.Modules.SpamCheck.check_passed = false;
			callback = processCheckResult;
		{else}
			$('#Disclaimer').show();
		{/if}
		$('#SpamCheck_Message').load('index.php?Page=Newsletters&Action=CheckSpam', temp, callback);
	});
</script>
<style type="text/css" media="all">
	#SpamCheck_Container {
		padding: 0px;
		margin: 0px;
		width: auto;
	}

	#SpamCheck_Message {
		font-family:Tahoma,Arial;
		font-size:11px;
	}

	div.spamRule_Success {
		vertical-align: middle;
		padding: 4px 3px 4px 3px;
	}

	div.spamRule_Success img {
		vertical-align: middle;
		padding-left: 2px;
		padding-right: 2px;
	}

	div.spamRuleBroken_row {
		background-color: #F9F9F9;
		display: block;
		clear: both;
	}

	div.spamRuleBroken_row_rulename {
		float: left;
		padding: 3px 0px 3px 5px;
	}

	div.spamRuleBroken_row_rulescore {
		float: right;
		width: 80px;
		text-align: right;
		padding: 3px 15px 3px 5px;
	}

	div.spamRuleBroken_graph {
		border: 1px gray solid;
		height:5px;
		background-color:#eeeeee;
	}
</style>
<div id="SpamCheck_Container">
	{if $force}
		<div id="ForceInfo" class="Info" style="display:none">{$lang.Spam_Guide_Forced}</div>
		<br />
	{/if}
	<div id="SpamCheck_MessageContainer">
		<div id="SpamCheck_Message">
			<div id="SpamCheck_Loading"><img src="images/loading.gif" align="top" alt="loading..." />&nbsp;&nbsp;{$lang.Spam_Loading}</div>
		</div>
		<br />
	</div>
	<br />
	<div class="Message" style="display:none;" id="Disclaimer">{$lang.Spam_Guide_Intro}</div>
</div>
