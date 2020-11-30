<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=%%GLOBAL_CHARSET%%">
<link rel="stylesheet" href="%%GLOBAL_APPLICATION_URL%%/admin/includes/styles/stylesheet.css" type="text/css">
<script src="%%GLOBAL_APPLICATION_URL%%/admin/includes/js/jquery.js"></script>
<script src="%%GLOBAL_APPLICATION_URL%%/admin/includes/js/javascript.js"></script>
<script>
	function UploadFile() {
		if (document.getElementById('newsletterfile').value == '') {
			alert('%%LNG_Editor_ChooseFileToUpload%%');
			return false;
		}
		Butt = document.getElementById('uploadButton');
		Butt.value = '%%LNG_Editor_Import_File_Wait%%';
		Butt.style.width = "150px";
		Butt.disabled = true;
		return true;
	}
</script>
<body style="margin: 0px; padding: 0px; background-color: #F9F9F9; background-image: none;">
<form STYLE="margin: 0px; padding: 0px;" method="post" action="%%GLOBAL_APPLICATION_URL%%/admin/functions/remote.php?ImportType=%%GLOBAL_ImportType%%" enctype="multipart/form-data" onsubmit="return UploadFile();">
<input type="hidden" name="what" value="importfile">
<input type="file" name="newsletterfile" id="newsletterfile" value="" class="Field" style="font-size:13px;">
<input class="FormButton" type="submit" id="uploadButton" name="upload" value="%%LNG_UploadNewsletter%%" style="width:60px">
</form>
</body>
</html>
