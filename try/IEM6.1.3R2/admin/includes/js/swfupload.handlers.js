var TotalItemsToUpload = 0;
var UploadErrorFiles = new Array();
var UploadDuplicateFiles = new Array();
var FileCount = 0;

function fileQueueError(file, errorCode, message) {

		var errorName = "";
		if (errorCode === SWFUpload.errorCode_QUEUE_LIMIT_EXCEEDED) {
			errorName = "You have attempted to queue too many files.";
		}

		if (errorName !== "") {
			alert(errorName);
			return;
		}

		switch (errorCode) {
		case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
			alert("The following file was not uploaded because it did not have any content: \n\n" + file.name);
			break;
		case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
			alert("The following file was not uploaded because it was too big (Max Size " + getMaxFileSize() + "): \n\n" + file.name);
			break;
		case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
		case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
		default:
			console.log(file);
			alert("Only files ending in .bmp gif .jpg .jpeg and .tiff can be uploaded. The following files were not uploaded because they are not valid images: \n\n" + file.name);
			break;
		}
}

function fileDialogComplete(numFilesSelected, numFilesQueued) {

	if (numFilesQueued  < 1){
		return;
	}
	$.iModal({ width: 500, title: 'Uploading Images',
		data: $('#ProgressWindow').html(),
		buttons: ''});

	TotalItemsToUpload = numFilesQueued;
	FileCount = 1;
	UploadErrorFiles = new Array();
	UploadDuplicateFiles = new Array();

	$('.progressBarStatus').html('Uploading Image 1 of ' + numFilesQueued);

	try {
		if (numFilesQueued > 0) {
			this.startUpload();
		}
	} catch (ex) {
		this.debug(ex);
	}
}

function uploadProgress(file, bytesLoaded) {
	var percent = Math.ceil((bytesLoaded / file.size) * 100);

	$('.progressBarPercentage').css('width', parseInt(percent) + "%");
	$('.progressPercent').html(percent+ "%");

}

function uploadSuccess(file, serverData) {
		var result = $.evalJSON(serverData);
		if(result.Filedata.duplicate){
			UploadDuplicateFiles.push(result.Filedata.name);
			return;
		}

		if(result.Filedata.errorfile != ''){
			UploadErrorFiles.push(result.Filedata.name);

		}else if(result.Filedata.error == 0){
			// success!
			AdminImageManager.AddImage( result.Filedata.name, ImgUrl + result.Filedata.name,  result.Filedata.filesize, result.Filedata.width, result.Filedata.height, result.Filedata.origwidth + ' x ' + result.Filedata.origheight,  result.Filedata.id);
		}
}

function GetDisplayName(name){
	if(name.length < 25){
		return name;
	}

	var first = name.substr(0, 12);
	var last = name.substr((name.length-12));
	return first + '...' + last;
}

function uploadComplete(file) {

	if (this.getStats().files_queued > 0) {
		$('.progressBarPercentage').css('width', "0%");
		$('.progressPercent').html("0%");
		FileCount++;
		$('.progressBarStatus').html('Uploading Image '+FileCount+' of ' + TotalItemsToUpload);
		$('.ProgressBarText').html('Uploading ' + file.name + '...');
		this.startUpload();
	} else {
		$.iModal.close();
			if(UploadErrorFiles.length > 0){
				var imageList = '';
				var thisImage = '';
				for(i in UploadErrorFiles){
					thisImage = UploadErrorFiles[i];
					imageList += '<li>' + $('<p>' + thisImage + '</p>').text() + '</li>'; // strips out any html
				}
				if(UploadErrorFiles.length == TotalItemsToUpload){
					$('#MainMessage').errorMessage('The following images were not uploaded because they are not valid image files: <ul>' + imageList + '</ul>');
				}else{
					$('#MainMessage').warningMessage('The following images were not uploaded because they are not valid image files (Any image not listed here was uploaded successfully) <ul>' + imageList + '</ul>');
				}
			}else if(UploadDuplicateFiles.length > 0){
				var imageList = '';
				var thisImage = '';
				for(i in UploadDuplicateFiles){
					thisImage = UploadDuplicateFiles[i];
					if ( $(thisImage).text().search('{') < 0) {
						imageList += '<li>' + $('<p>' + thisImage + '</p>').text() + '</li>'; // strips out any html
					}
				}
				$('#MainMessage').warningMessage('All images were uploaded sucessfully with the exception of the following which were found to be duplicates. Please rename these files and try again. <ul>' + imageList + '</ul>');
			}else{
				// The 4 selected images have been uploaded and are shown below
				// The selected image has been uploaded and is shown below.
				if(FileCount == 1){
					$('#MainMessage').successMessage('The selected image has been uploaded and is shown below.');
				}else{
					$('#MainMessage').successMessage('The ' + FileCount + ' selected images have been uploaded and are shown below.');
				}
				$('#deleteButton').show();
				$('#pagination').show();
			}
	}
	var sendPOST = 'what=imagemanagerimagenumshown';
	$.post('remote.php', sendPOST,
	function(json){
		var result = $.evalJSON(json);
		if(result.text){
			$('#ImgNum').html(result.text);
		}
	});
}

function uploadError(file, errorCode, message) {
	var imageName =  "error.gif";
	var progress;

	switch (errorCode) {
	case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
		try {
			progress = new FileProgress(file,  this.customSettings.upload_target);
			progress.setCancelled();
			progress.setStatus("Cancelled");
			progress.toggleCancel(false);
		}
		catch (ex1) {
			this.debug(ex1);
		}
		break;
	case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
		try {
			progress = new FileProgress(file,  this.customSettings.upload_target);
			progress.setCancelled();
			progress.setStatus("Stopped");
			progress.toggleCancel(true);
		}
		catch (ex2) {
			this.debug(ex2);
		}
	case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
		imageName = "uploadlimit.gif";
		break;
	default:
		alert(message);
		break;
	}
}

function getMaxFileSize(){
	filesize = iem.util.defaultVal(MaxFileSize, '2MB');
	return filesize;
}