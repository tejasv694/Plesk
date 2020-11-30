<script src="includes/js/jquery/plugins/jquery.plugin.js"></script>
<script src="includes/js/jquery/plugins/jquery.tableSelector.js"></script>
<script src="includes/js/imodal/imodal.js"></script>
<script src="includes/js/jquery/plugins/jquery.window.js"></script>
<script src="includes/js/jquery/plugins/jquery.window-extensions.js"></script>

<link rel="stylesheet" href="includes/js/imodal/imodal.css" type="text/css" media="screen" />


<script>

	var newsletterData = '';
	
	$(function() {
		$(document.frmEditNewsletter).submit(function() {
			if (this.subject.value == '') {
				alert("%%LNG_PleaseEnterNewsletterSubject%%");
				this.subject.focus();
				return false;
			}
			syncHTMLEditor();
			Application.Modules.SpamCheck.form = document.frmEditNewsletter;
			if ('%%GLOBAL_ForceSpamCheck%%' == '1' && !Application.Modules.SpamCheck.check_passed) {
				tb_show('%%LNG_Spam_Guide_Heading%%', 'index.php?Page=Newsletters&Action=CheckSpamDisplay&Force=true&keepThis=true&TB_iframe=tue&height=480&width=600', '');
				return false;
			}
			return true
		});

		$('.CancelButton').click(function() { if(confirm("%%GLOBAL_CancelButton%%")) { document.location="index.php?Page=Newsletters" } });
		$('.SaveButton').click(function() { document.frmEditNewsletter.action = 'index.php?Page=Newsletters&Action=%%GLOBAL_SaveAction%%'; $(document.frmEditNewsletter).submit(); });
		$('.SaveExitButton').click(function() { document.frmEditNewsletter.action = 'index.php?Page=Newsletters&Action=%%GLOBAL_Action%%'; });

		$(document.frmEditNewsletter.cmdCheckSpam).click(function() {
			syncHTMLEditor();
			tb_show('%%LNG_Spam_Guide_Heading%%', 'index.php?Page=Newsletters&Action=CheckSpamDisplay&keepThis=true&TB_iframe=tue&height=480&width=600', '');
			return true;
		});

		$(document.frmEditNewsletter.cmdViewCompatibility).click(function() {
			var f = document.frmEditNewsletter;
			f.target = "_blank";

			prevAction = f.action;
			f.action = "index.php?Page=Newsletters&Action=ViewCompatibility&ShowBroken=1";
			f.submit();

			f.target = "";
			f.action = prevAction;
		});

		$(document.frmEditNewsletter.cmdPreviewEmail).click(function() {
			if (document.frmEditNewsletter.PreviewEmail.value == "") {
				alert("%%LNG_EnterPreviewEmail%%");
				document.frmEditNewsletter.PreviewEmail.focus();
				return false;
			}

			tb_show('%%LNG_SendPreview%%', 'index.php?Page=Newsletters&Action=SendPreviewDisplay&keepThis=true&TB_iframe=tue&height=250&width=550', '');
			return true;
		});

	});

	// Create an instance of the multiSelector class, pass it the output target and the max number of files
	{if $ShowAttach === true}
		$(function() {
			var multi_selector = new MultiSelector( document.getElementById( 'files_list' ), 5 );
			multi_selector.addElement( document.getElementById( 'fileUpload' ) );
		});
	{/if}
	
	function Upload() {
		if (document.frmEditNewsletter.newsletterfile.value == "") {
			alert('%%LNG_NewsletterFileEmptyAlert%%');
			document.frmEditNewsletter.newsletterfile.focus();
			return false;
		}
		$('.SaveButton').click();
	}

	function Import() {
		if (document.frmEditNewsletter.newsletterurl.value == "" || document.frmEditNewsletter.newsletterurl.value == 'http://') {
			alert('%%LNG_NewsletterURLEmptyAlert%%');
			document.frmEditNewsletter.newsletterurl.focus();
			return false;
		}
		$('.SaveButton').click();
	}

	function closePopup() {
		tb_remove();
	}

	function getMessage() {
		var message = {};
		if(document.frmEditNewsletter.myDevEditControl_html) message['myDevEditControl_html'] = document.frmEditNewsletter.myDevEditControl_html.value;
		if(document.frmEditNewsletter.TextContent) message['TextContent'] = document.frmEditNewsletter.TextContent.value;
		return message;
	}

	function getSendPreviewParam() {
		var f = document.frmEditNewsletter;
		var html = f.myDevEditControl_html ? f.myDevEditControl_html.value : '';
		// if the WYSIWYG editor is not disabled, get the very latest HTML
		if (Application.WYSIWYGEditor.isWysiwygEditorActive()) {
			html = Application.WYSIWYGEditor.getContent();
		}
		return {	'subject': f.subject.value,
					'myDevEditControl_html': html,
					'TextContent': f.TextContent ? f.TextContent.value : '',
					'PreviewEmail': f.PreviewEmail.value,
					'FromPreviewEmail': f.FromPreviewEmail.value,
					'id': %%GLOBAL_PreviewID%%
				};
	}

	function syncHTMLEditor() {
		if (Application.WYSIWYGEditor.isWysiwygEditorActive()) {
			if (document.frmEditNewsletter.myDevEditControl_html) {
				document.frmEditNewsletter.myDevEditControl_html.value = Application.WYSIWYGEditor.getContent();
			}
		}
	}

</script>
<form name="frmEditNewsletter" method="post" action="index.php?Page=Newsletters&Action=%%GLOBAL_Action%%" enctype="multipart/form-data">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%GLOBAL_Heading%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%GLOBAL_Intro%%
				</p>
			</td>
		</tr>
		<tr>
			<td>
				%%GLOBAL_Message%%
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton SaveButton" type="button" value="%%LNG_SaveAndKeepEditing%%" style="width:130px" />
				<input class="FormButton_wide SaveExitButton" type="submit" value="%%LNG_SaveAndExit%%"/>
				<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%"/>
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_Newsletter_Details%%
						</td>
					</tr>
					<tr>
						<td width="10%" class="FieldLabel">
							<img src="images/blank.gif" width="200" height="1" /><br />
							{template="Required"}
							%%LNG_NewsletterSubject%%:
						</td>
						<td width="90%">
							<input type="text" name="subject" value="%%GLOBAL_Subject%%" class="Field250" style="width:300px">&nbsp;%%LNG_HLP_NewsletterSubject%%
							<br/>%%LNG_Subject_Guide_Link%%
						</td>
					</tr>

					%%GLOBAL_Editor%%

					<tr>
						<td colspan="2" class="EmptyRow">
							&nbsp;
						</td>
					</tr>
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_Attachments%%
						</td>
					</tr>
					   
					{if $ShowAttach === true}
						<tr>
							<td valign="top" class="FieldLabel">
								{template="Not_Required"}
								%%LNG_Attachments%%:&nbsp;
							</td>
							<td>
								<table border="0" cellspacing="0" cellpadding="0" id="AttachmentsTable">
									<tr>
										<td>
											<input type="file" name="attachments[]" value="" class="FormButton" id="fileUpload" style="width: 200px">&nbsp;%%LNG_HLP_Attachments%%
											<div id="files_list" style="margin-top: 5px"></div>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td colspan="2" align="left">
								%%GLOBAL_AttachmentsList%%
							</td>
						</tr>
					{else}
						<tr>						
							<td class="FieldLabel">
							</td>
							<td colspan="2">
								<p>
									%%GLOBAL_AttachmentsMsg%%
								</p>
							</td>
						</tr>
					{/if}
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_EmailValidation%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SpamKeywordsCheck%%:
						</td>
						<td>
							<input type="button" name="cmdCheckSpam" class="Field300" value="%%LNG_SpamKeywordsCheck_Button%%"/>
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_EmailClientCompatibility%%:
						</td>
						<td>
							<input type="button" name="cmdViewCompatibility" class="Field300" value="%%LNG_EmailClientCompatibility_Button%%"/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="EmptyRow">
							&nbsp;
						</td>
					</tr>
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_MiscellaneousOptions%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_NewsletterIsActive%%:
						</td>
						<td>
							<label for="active">
							<input type="checkbox" name="active" id="active" value="1"%%GLOBAL_IsActive%%>
							%%LNG_NewsletterIsActiveExplain%%
							</label>
							%%LNG_HLP_NewsletterIsActive%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_NewsletterArchive%%:
						</td>
						<td>
							<label for="archive">
							<input type="checkbox" name="archive" id="archive" value="1"%%GLOBAL_Archive%%>
							%%LNG_NewsletterArchiveExplain%%
							</label>
							%%LNG_HLP_NewsletterArchive%%
						</td>
					</tr>
					<tr>
						<td colspan="2" class="EmptyRow">
							&nbsp;
						</td>
					</tr>
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_SendPreview%%
						</td>
					</tr>
					<tr>
						<td valign="top" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SendPreviewFrom%%:
						</td>
						<td>
							<input type="text" name="FromPreviewEmail" value="%%GLOBAL_FromPreviewEmail%%" class="Field" style="width:150px">
						</td>
					</tr>
					<tr>
						<td valign="top" class="FieldLabel">
							{template="Not_Required"}
							%%LNG_SendPreviewTo%%:
						</td>
						<td>
							<input type="text" name="PreviewEmail" value="" class="Field" style="width:150px">
							<input type="button" name="cmdPreviewEmail" value="%%LNG_SendPreview%%" class="Field"/>
							%%LNG_HLP_SendPreview%%
						</td>
					</tr>
					<tr>
						<td colspan="2">
							&nbsp;
						</td>
					</tr>
				</table>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td>
							<input class="FormButton SaveButton" type="button" value="%%LNG_SaveAndKeepEditing%%" style="width:130px" />
							<input class="FormButton_wide SaveExitButton" type="submit" value="%%LNG_SaveAndExit%%" />
							<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
