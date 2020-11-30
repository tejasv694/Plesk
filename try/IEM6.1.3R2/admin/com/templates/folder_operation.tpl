<meta http-equiv="Content-Type" content="text/html; charset=%%GLOBAL_CHARSET%%" />
<link rel="stylesheet" href="includes/styles/stylesheet.css" type="text/css" />

<!--[if IE]>
<style type="text/css">
	@import url("includes/styles/ie.css");
</style>
<![endif]-->

<script src="includes/js/jquery.js"></script>
<script>
	$(function() {
		$('#FolderOperation_CloseButton').click(function(event) {
			self.parent.closePopup();
		});
	});
</script>
<style type="text/css" media="all">

	body {
		margin: 0;
		padding: 0;
		padding-left: 1em;
	}

	#FolderOperation_Container {
		padding: 0;
		margin: 0;
		width: 340px;
	}

	#FolderOperation_Close {
		float: right;
		cursor: pointer;
	}

	#FolderOperation_MessageContainer {
		overflow: hidden;
		padding: 0;
		margin: 0;
	}

	#FolderOperation_Message {
		padding: 0;
		margin: 0;
		padding-top: 1em;
	}

	#FolderOperation_Loading {
		text-align: center;
	}

</style>
{capture name=folder_type}%%GLOBAL_FolderType%%{/capture}
{capture name=folder_id}%%GLOBAL_FolderID%%{/capture}
{capture name=folder_name}%%GLOBAL_FolderName%%{/capture}
{capture name=operation}%%GLOBAL_FolderOperation%%{/capture}
<div id="FolderOperation_Container">
	<div id="FolderOperation_MessageContainer">
		<div id="FolderOperation_Message">
			<form id="FolderOperation_Form">
			{if $operation == 'add'}
				%%LNG_Folders_NewFolderName%%:<br />
				<input type="text" id="folder_name" class="Field150" />
				<input type="submit" value="%%LNG_Folders_Button_Add%%" class="FormButton" />
			{elseif  $operation == 'rename'}
				%%LNG_Folders_NewFolderName%%:<br />
				<input type="hidden" id="folder_id" value="{$folder_id}" />
				<input type="text" id="folder_name" class="Field150" value="{$folder_name}" />
				<input type="submit" value="%%LNG_Folders_Button_Rename%%" class="FormButton" />
			{/if}
				<input type="button" value="%%LNG_Folders_Button_Cancel%%" class="CancelButton FormButton" />
			<script>
			$(function() {
				
				var focus_box = function() {
					$("#folder_name").focus().select()
				}
				// Time-delay hack to get focus working
				setTimeout(focus_box, 100);

				// Set up cancel button
				$('input.CancelButton').click(function() {
					self.parent.closePopup();
				});

			});
			$('#FolderOperation_Form').submit(function(event) {
				$.ajax({
					cache: false,
					url: 'index.php?Page=Folders&Action=ajax',
					type: 'POST',
					dataType: 'json',
					data:	{
							{if $operation == 'add'}
								AjaxType: 'Add',
							{elseif $operation == 'rename'}
								AjaxType: 'Rename',
							{/if}
							folder_name: $('#folder_name').attr('value'),
							folder_type: '{$folder_type}',
							folder_id: '{$folder_id}'
							},
					success: function(response) {
								if (response.status && response.status != 'OK' && response.message) {
									alert(response.message);
									$("#folder_name").select();
									return;
								}
								self.parent.Application.Ui.Folders.ReloadTable();
								self.parent.closePopup();
							},
					error:	function(response) {
								$('#FolderOperation_Message').html(response.responseText)
							}
				});
				return false;
			});
			</script>
		</div>
	</div>
	<br />
</div>
