<script type="text/javascript" src="includes/js/jquery/jquery.growinguploader.js"></script>
<script type="text/javascript" src="includes/js/jquery/ajax.file.upload.js"></script>

<div id="noFlashProgressWindow" style="display: none; ">
</div>
<div id="uploadFormNoFlash">
%%LNG_NoFlashImageUploadIntro%%<br /><br />
<form method="post" action="" enctype="multipart/form-data">
	<div class="Uploader"><input type="file" name="Filedata"  class="Field noflashUploadField" id="growingUpload_first" /> 
	<input type="button" class="Field" value="Clear" /></div>
</form>
</div>

<script type="text/javascript">

$('#uploadButton').bind('click', AdminImageManager.UploadNonFlashImages);
$('#noFlashProgressWindow').html($('#ProgressWindow').html());

</script>