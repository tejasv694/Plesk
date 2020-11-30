<script type="text/javascript">
	var PAGE = {
		init:						function() {
			$(document.frmSend).submit(function(event) {
				event.preventDefault();
				event.stopPropagation();
			});

			$('.CancelButton').click(function() { PAGE.cancel(); });

			$('.SubmitButton').click(function() { PAGE.submit(); });

			$('#lists').dblclick(function() { PAGE.submit(); });

			{if $DisplaySegments}
				if(document.frmSend['segments[]'].options.length == 0) {
					$('#ShowSegmentOptions').attr('disabled', true);
				}

				$('#segments').dblclick(function() { PAGE.submit(); });
			{/if}

			$('.SendFilteringOption').click(function() { PAGE.selectSendingOption(this.value); });
		},
		submit:					function() {
			{if $DisplaySegments}
				if($('#ShowSegmentOptions').get(0).checked) {
					var elm = $('.SelectedSegments').get(0);
					if(elm.selectedIndex == -1) {
						alert("{$lang.Addon_splittest_Alert_ChooseSegment}");
						return;
					}
				}
			{/if}
			if ($('#ShowListOptions').get(0).checked) {
				var elm = $('.SelectedLists').get(0);
				if(elm.selectedIndex == -1) {
					alert("{$lang.Addon_splittest_Alert_ChooseList}");
					return;
				}
			}
			document.frmSend.submit();
		},
		cancel:					function() {
			if(confirm("{$lang.Addon_splittest_Send_CancelPrompt}")) {
				document.location="{$AdminUrl}";
			}
		},
		selectSendingOption:	function(sendingOption) {
			{if $DisplaySegments}
			if(sendingOption == 2) {
				this.showSegment();
				return;
			}
			{/if}
			this.showMailingList();
		},
		{if $DisplaySegments}
		showSegment:			function(transition) {
			$('#FilteringOptions').hide();
			$('#SegmentOptions').show();
		},
		{/if}
		showMailingList:		function(transition) {
			{if $DisplaySegments}
				$('#SegmentOptions').hide(transition? 'slow' : '');
			{/if}
			$('#FilteringOptions').show(transition? 'slow' : '');
		}
	};

	$(function() { PAGE.init(); });
</script>
<form name="frmSend" method="post" action="{$AdminUrl}&Action=Send&Step=2">
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				{$lang.Addon_splittest_Send_Step1}
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					{$lang.Addon_splittest_Send_Step1_Intro}
				</p>
			</td>
		</tr>
		<tr>
			<td>
				{$FlashMessages}
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
							&nbsp;&nbsp;{$lang.Addon_splittest_Send_Step1_WhoToSendTo_Heading}
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="not_required"}
							{$lang.Addon_splittest_Send_Step1_WhoToSendTo}&nbsp;
						</td>
						<td valign="top">
							<table width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td>
										<label class="SendFilteringOption_Label" for="ShowListOptions"><input type="radio" name="ShowFilteringOptions"
										id="ShowListOptions" class="SendFilteringOption" value="1" checked="checked" />{$lang.Addon_splittest_Send_Step1_WhoToSendTo_Lists}</label>
									</td>
								</tr>
								{if $DisplaySegments}
								<tr>
									<td>
										<label class="SendFilteringOption_Label" for="ShowSegmentOptions"><input type="radio" name="ShowFilteringOptions" id="ShowSegmentOptions"
										class="SendFilteringOption" value="2" />{$lang.Addon_splittest_Send_Step1_WhoToSendTo_Segments}</label>
									</td>
								</tr>
								{/if}
							</table>
						</td>
					</tr>
				</table>
				<div id="FilteringOptions">
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;{$lang.Addon_splittest_Send_Step1_List_Heading}
							</td>
						</tr>
						<tr>
							<td width="200" class="FieldLabel">
								{template="not_required"}
								{$lang.Addon_splittest_Send_Step1_ChooseList}&nbsp;
							</td>
							<td>
								<select id="lists" name="lists[]" multiple="multiple" class="SelectedLists ISSelectReplacement ISSelectSearch">
								{foreach from=$user_lists key=k item=list}
									<option value="{$list.listid}">
									{$list.name}
									({if $list.subscribecount == 1}{$lang.Addon_splittest_SingleContact}{else}{$list.subscribecount|number_format}{$lang.Addon_splittest_MultipleContacts}{/if})
									</option>
								{/foreach}
								</select>
							</td>
						</tr>
					</table>
				</div>
				{if $DisplaySegments}
				<div id="SegmentOptions" style="display:none;">
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
						<tr>
							<td colspan="2" class="Heading2">
								&nbsp;&nbsp;{$lang.Addon_splittest_Send_Step1_Segment_Heading}
							</td>
						</tr>
						<tr>
							<td width="200" class="FieldLabel">
								{template="not_required"}
								{$lang.Addon_splittest_Send_Step1_ChooseSegment}&nbsp;
							</td>
							<td>
								<select id="segments" name="segments[]" multiple="multiple" class="SelectedSegments ISSelectReplacement">
								{foreach from=$user_segments key=k item=segment}
									<option value="{$segment.segmentid}">{$segment.segmentname}</option>
								{/foreach}
								</select>
							</td>
						</tr>
					</table>
				</div>
				{/if}
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
