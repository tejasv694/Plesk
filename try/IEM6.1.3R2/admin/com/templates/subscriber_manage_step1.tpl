<script>
	var PAGE = {
		init:						function() {
			$(document.frmManageSubscriberStep1).submit(function(event) {
				event.preventDefault();
				event.stopPropagation();
			});

			if(document.frmManageSubscriberStep1['segments[]'].options.length == 0)
				$('#ShowSegmentOptions').attr('disabled', true);

			$('.CancelButton').click(function() { PAGE.cancel(); });

			$('.SubmitButton').click(function() { PAGE.submit(); });

			$('#segments, #lists').dblclick(function() { PAGE.submit(); });

			$('.SendFilteringOption').click(function() { PAGE.selectSendingOption(this.value); });
		},
		submit:					function() {
			var filteringOptions = parseInt($('.SendFilteringOption:checked').val());

			switch(filteringOptions) {
				case 1:
				case 2:
					var list = $('#lists').get(0);
					if(list.selectedIndex == -1) {
						alert("%%LNG_SelectList%%");
						return;
					}

					if(filteringOptions == 2) {
						var url = 'index.php?Page=Subscribers&Action=Manage&SubAction=step3';

						for (var i = 0, j = list.options.length; i < j; i++) {
							if (list.options[i].selected){
								url += '&Lists[]=' + list.options[i].value;
							}
						}

						$(document.frmManageSubscriberStep1).attr('action', url);
					}
				break;
				case 3:
					var segments = $('#segments').get(0);
					if(segments.selectedIndex == -1) {
						alert("%%LNG_SelectSegment%%");
						return;
					}

					var url = 'index.php?Page=Subscribers&Action=Manage&SubAction=step3';

					for (var i = 0, j = segments.options.length; i < j; i++) {
						if (segments.options[i].selected){
							url += '&Segment[]=' + segments.options[i].value;
						}
					}

					$(document.frmManageSubscriberStep1).attr('action', url);
				break;
			}

			document.frmManageSubscriberStep1.submit();
		},
		cancel:					function() {
			if(confirm("%%LNG_Subscribers_Manage_CancelPrompt%%"))
				document.location="index.php?Page=Subscribers";
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
<form name="frmManageSubscriberStep1" method="post" action="index.php?Page=Subscribers&Action=Manage&SubAction=step2">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				%%LNG_Subscribers_AdvancedSearch%%
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					%%LNG_Subscribers_Manage_Intro%%
				</p>
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
							&nbsp;&nbsp;%%LNG_FilterOptions_Subscribers%%
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							%%LNG_ShowFilteringOptionsLabel_Manage%%&nbsp;
						</td>
						<td valign="top">
							<table width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td colspan="2">
										<label for="ShowFilteringOptions"><input type="radio" name="ShowFilteringOptions" id="ShowFilteringOptions" class="SendFilteringOption" value="1" checked="checked" />%%LNG_SubscribersShowFilteringOptionsExplain%% </label>
									</td>
								</tr>
								<tr style="display:%%GLOBAL_DisplaySegmentOption%%;">
									<td colspan="2">
										<label class="SendFilteringOption_Label" for="ShowSegmentOptions"><input type="radio" name="ShowFilteringOptions" id="ShowSegmentOptions" class="SendFilteringOption" value="3" />%%LNG_SubscribersShowSegmentsExplain%%</label>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				<div id="FilteringOptions">
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;%%LNG_MailingListDetails%%
							</td>
						</tr>
						<tr>
							<td width="200" class="FieldLabel">
								{template="Not_Required"}
								%%LNG_MailingList%%:&nbsp;
							</td>
							<td>
								<select id="lists" name="lists[]" multiple="multiple" class="ISSelectReplacement ISSelectSearch" style="%%GLOBAL_SelectListStyle%%">
									%%GLOBAL_SelectList%%
								</select>&nbsp;%%LNG_HLP_MailingList%%
							</td>
						</tr>
					</table>
				</div>
				<div id="SegmentOptions" style="display:none;">
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;%%LNG_SubscriberSegmentDetails%%
							</td>
						</tr>
						<tr>
							<td width="200" class="FieldLabel">
								{template="Not_Required"}
								%%LNG_Segments%%:&nbsp;
							</td>
							<td>
								<select id="segments" name="segments[]" multiple="multiple" class="SelectedSegments ISSelectReplacement">
									%%GLOBAL_SelectSegment%%
								</select>&nbsp;%%LNG_HLP_SubscriberFilterBySegments%%
							</td>
						</tr>
					</table>
				</div>
				<table width="100%" cellspacing="0" cellpadding="2" border="0" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel">&nbsp;</td>
						<td valign="top" height="30">
							<input class="FormButton SubmitButton" type="submit" value="%%LNG_Next%%" />
							<input class="FormButton CancelButton" type="button" value="%%LNG_Cancel%%" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
