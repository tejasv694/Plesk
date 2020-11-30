<style>
.question {
	margin-bottom: 10px;
	color: #000000;
}
.question_title {
	font-weight:bold;
	
}

.question_multiplechoice .question_input ul {
	list-style-type: none;
	margin: 0px;
	padding: 0px;
}
.question_multiplechoice .question_input ul li {
	margin: 0px;
	padding: 0px;
}
.question_input {
	
}
</style>

<style type="text/css">@import url(includes/styles/ui.datepicker.css);</style>
<script src="includes/js/jquery/ui.js"></script>
<script>
$(function() {
	$('.datefield').datepicker();
});
</script>

{$Welcome}
<br>
{$questions}