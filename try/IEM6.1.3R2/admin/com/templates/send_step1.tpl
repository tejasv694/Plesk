<script>
	var PAGE = {
		init:						function() {
			$(document.frmSend).submit(function(event) {
				event.preventDefault();
				event.stopPropagation();
			});

			if(document.frmSend['segments[]'].options.length == 0)
				$('#ShowSegmentOptions').attr('disabled', true);

			$('.CancelButton').click(function() { PAGE.cancel(); });

			$('.SubmitButton').click(function() { PAGE.submit(); });

			$('#segments, #lists').dblclick(function() { PAGE.submit(); });

			$('.SendFilteringOption').click(function() { PAGE.selectSendingOption(this.value); });
		},
		submit:					function() {
			if($('#ShowSegmentOptions').get(0).checked) {
				var elm = $('.SelectedSegments').get(0);
				if(elm.selectedIndex == -1) alert("%%LNG_SelectSegment%%");
				else document.frmSend.submit();
			} else {
				var elm = $('.SelectedLists').get(0);
				if(elm.selectedIndex == -1) alert("%%LNG_SelectList%%");
				else document.frmSend.submit();
			}
		},
		cancel:					function() {
			if(confirm("%%LNG_Send_CancelPrompt%%"))
				document.location="index.php?Page=Newsletters";
		},
		selectSendingOption:	function(sendingOption) {
			if(sendingOption == 3) this.showSegment();
			else this.showMailingList();
		},
		showSegment:			function(transition) {
			$('#FilteringOptions').hide();
			$('#SegmentOptions').show();
		},
		showMailingList:		function(transition) {
			$('#SegmentOptions').hide(transition? 'slow' : '');
			$('#FilteringOptions').show(transition? 'slow' : '');
		}
	};

	$(function() { PAGE.init(); });
</script>
<form name="frmSend" method="post" action="index.php?Page=Send&Action=Step2">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Send_Step1%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Send_Step1_Intro%%
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
				<input class="FormButton SubmitButton" type="button" value="%%LNG_Next%%" />
				<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%" />
				<br />
				&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr>
						<td colspan="2" class="Heading2">
							&nbsp;&nbsp;%%LNG_FilterOptions_Send%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_ShowFilteringOptions_Send%%&nbsp;
						</td>
						<td valign="top">
							<table width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td>
										<label class="SendFilteringOption_Label" for="DoNotShowFilteringOptions"><input type="radio" name="ShowFilteringOptions" id="DoNotShowFilteringOptions" class="SendFilteringOption" value="2" checked="checked" />%%LNG_SendDoNotShowFilteringOptionsExplain%%</label>
									</td>
								</tr>
								<tr>
									<td>
										<label class="SendFilteringOption_Label" for="ShowFilteringOptions"><input type="radio" name="ShowFilteringOptions" id="ShowFilteringOptions" class="SendFilteringOption" value="1" />%%LNG_SendShowFilteringOptionsExplain%%</label>
									</td>
								</tr>
								<tr style="display:%%GLOBAL_DisplaySegmentOption%%;">
									<td>
										<label class="SendFilteringOption_Label" for="ShowSegmentOptions"><input type="radio" name="ShowFilteringOptions" id="ShowSegmentOptions" class="SendFilteringOption" value="3" />%%LNG_SendShowSegmentOptionsExplain%%</label>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				<div id="FilteringOptions" %%GLOBAL_FilteringOptions_Display%%>
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;%%LNG_MailingListDetails%%
							</td>
						</tr>
						<tr>
							<td width="200" class="FieldLabel">
								{template="Not_Required"}
								%%LNG_SendMailingList%%:&nbsp;
							</td>
							<td>
								<select id="lists" name="lists[]" multiple="multiple" class="SelectedLists ISSelectReplacement ISSelectSearch">
									%%GLOBAL_SelectList%%
								</select>&nbsp;%%LNG_HLP_SendMailingList%%
							</td>
						</tr>
					</table>
				</div>
				<div id="SegmentOptions" style="display:none;">
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;%%LNG_SegmentDetails%%
							</td>
						</tr>
						<tr>
							<td width="200" class="FieldLabel">
								{template="Not_Required"}
								%%LNG_SendToSegment%%:&nbsp;
							</td>
							<td>
								<select id="segments" name="segments[]" multiple="multiple" class="SelectedSegments ISSelectReplacement">
									%%GLOBAL_SelectSegment%%
								</select>&nbsp;%%LNG_HLP_SendToSegment%%
							</td>
						</tr>
					</table>
				</div>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel"></td>
						<td>
							<input class="FormButton SubmitButton" type="button" value="%%LNG_Next%%" />
							<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
