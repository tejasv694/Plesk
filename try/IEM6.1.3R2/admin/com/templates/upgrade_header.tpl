<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>%%LNG_ControlPanel%%</title>
<link rel="shortcut icon" href="images/favicon.ico" type="image/vnd.microsoft.icon">
<link rel="icon" href="images/favicon.ico" type="image/vnd.microsoft.icon">
<meta http-equiv="Content-Type" content="text/html; charset=%%GLOBAL_CHARSET%%">
<link rel="stylesheet" href="includes/styles/stylesheet.css" type="text/css">
<link rel="stylesheet" href="includes/styles/tabmenu.css" type="text/css">
<link rel="stylesheet" href="includes/styles/thickbox.css" type="text/css">

<!--[if IE]>
<style type="text/css">
	@import url("includes/styles/ie.css");
</style>
<![endif]-->

<script src="includes/js/jquery.js"></script>
<script src="includes/js/jquery/thickbox.js"></script>
<script src="includes/js/javascript.js"></script>
<script>
function sizeBox() {
	var w = $(window).width();
	var h = $(window).height();
	$('#box').css('position', 'absolute');
	$('#box').css('top', h/2-($('#box').height()/2)-50);
	$('#box').css('left', w/2-($('#box').width()/2));
}

$(document).ready(function() {
	sizeBox();
});

$(window).resize(function() {
	sizeBox();
});
</script>
</head>

<body>
	<div id="box" style="width: 600px; top: 20px; margin:auto;">
