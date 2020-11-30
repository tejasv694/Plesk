<script>
	Application.Page.SegmentManage = {
		eventDOMReady: function(event) {
			Application.Ui.CheckboxSelection(	'table#SegmentManageList',
												'input.ToggleSelector',
												'input.SegmentManageSegmentSelection');

			$(document.frmSegment.cmdAddSegment).click(Application.Page.SegmentManage.eventAddSegment);
			$(document.frmSegment.cmdChangeType).click(Application.Page.SegmentManage.eventActionCommandClicked);
			$('.SortableColumn').click(Application.Page.SegmentManage.eventClickSortableColumnHeader);
			$('.SegmentManageDeleteLink').click(Application.Page.SegmentManage.eventDeleteSegment);

			Application.Page.SegmentManage.getSegmentCount();

			if($('#GLOBAL_Message').html() != '') $('#GLOBAL_Message_Container').show();
		},
		eventAddSegment: function(event) {
			Application.Util.submitGet('index.php', {	Page:	'Segment',
														Action:	'Create'});
		},
		eventActionCommandClicked: function(event) {
			if(document.frmSegment.ChangeType.selectedIndex == 0) {
				alert('%%LNG_PleaseChooseAction%%');
				return;
			}

			if($('input.SegmentManageSegmentSelection:checked').size() == 0) {
				alert('%%LNG_SegmentAlertChooseSegment%%');
				return;
			}

			switch(document.frmSegment.ChangeType.value) {
				case 'BulkDelete':
					var selected = 	$('.SegmentManageSegmentSelection').filter(function() { return this.checked; });
					var selectedNames = [];

					for(var i = 0, j = selected.length; i < j; ++i) {
						selectedNames.push(selected[i].title);
					}

					if(confirm('%%LNG_SegmentManageConfirmDeleteMany%%'.replace('%s', '\'' + selectedNames.join('\', \'') + '\''))) {
						document.frmSegment.action = 'index.php?Page=Segment&Action=Delete';
						document.frmSegment.submit();
					}
				break;
			}
		},
		eventClickSortableColumnHeader: function(event) {
			event.preventDefault();

			var param = this.id.match(/hrefColumnSort_(.*?)_(.*)/);

			if(param.length != 3) return;
			Application.Util.submitGet('index.php', {	Page:		'Segment',
														SortBy:		param[1],
														Direction:	param[2]});
		},
		eventDeleteSegment: function(event) {
			event.preventDefault();

			if(confirm('%%LNG_SegmentManageConfirmDeleteOne%%'.replace('%s', this.title))) {
				var segmentID = $(this).attr('id').match(/hrefSegmentManageDelete_(\d*)/);
				if(segmentID.length != 2) return;

				Application.Util.submitPost('index.php?Page=Segment&Action=Delete', { Segments: segmentID[1]});
			}
		},
		getSegmentCount: function() {
			var elm = $('.SegmentSubscriberCount_Unprocessed').get(0);

			if(elm) {
				var segmentid = $(elm).attr('id').match(/sectionSegmentSubscriberCount_(\d*)/);
				if(segmentid.length != 2) return;
				$.post(	'index.php?Page=Segment&Action=Ajax',
						{	ajaxType: 'GetSubscriberCount',
							segmentID: segmentid[1]},
						function(response) {
							var elm = $('.SegmentSubscriberCount_Unprocessed').get(0);
							$(elm).removeClass('SegmentSubscriberCount_Unprocessed');

							try {
								var temp = eval('(' + response + ')');
								$(elm).html('' + temp.output);
							} catch(e) {
								$(elm).html('-');
							}

							Application.Page.SegmentManage.getSegmentCount();
						});
			}
		}
	};

	Application.init.push(Application.Page.SegmentManage.eventDOMReady);
</script>
<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr><td class="Heading1">%%LNG_SegmentManage%%</td></tr>
	<tr><td class="Intro" style="padding-bottom:10px">%%LNG_Help_SegmentManage%%</td></tr>
	<tr id="GLOBAL_Message_Container" style="display:none;"><td id="GLOBAL_Message">%%GLOBAL_Message%%</td></tr>
	<tr>
		<td class=body>
			<form name="frmSegment" method="post" action="index.php?Page=Segment" style="margin: 0px;padding: 0px;">
				<span class=body>%%GLOBAL_ListsReport%%</span>
				<table width="100%" border="0">
					<tr>
						<td valign="bottom">
							<div style="padding-bottom:10px; display: %%GLOBAL_CreateButtonDisplayProperty%%;">
								<input type="button" name="cmdAddSegment" value="%%LNG_SegmentManageCreateNew%%" title="%%LNG_SegmentManageCreateNew_Title%%" class="SmallButton" />
							</div>
							<select name="ChangeType">
								<option value="" SELECTED>%%LNG_ChooseAction%%</option>
								<option value="BulkDelete">%%LNG_SegmentDelete%%</option>
							</select>
							<input type="button" name="cmdChangeType" value="%%LNG_Go%%" class="Text" />
						</td>
						<td align="right" valign="bottom">
							{template="Paging"}
						</td>
					</tr>
				</table>
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Text" id="SegmentManageList">
					<tr class="Heading3">
						<td width="5" nowrap align="center">
							<input type="checkbox" name="chkToggle" class="ToggleSelector" />
						</td>
						<td width="5">&nbsp;</td>
						<td width="80%">
							%%LNG_SegmentName%%&nbsp;<a id="hrefColumnSort_SegmentName_Up" class="SortableColumn" href="index.php?Page=Segment"><img src="images/sortup.gif" border="0" /></a>&nbsp;<a id="hrefColumnSort_SegmentName_Down" class="SortableColumn" href="index.php?Page=Segment"><img src="images/sortdown.gif" border="0" /></a>
						</td>
						<td width="20%">
							%%LNG_Created%%&nbsp;<a id="hrefColumnSort_CreateDate_Up" class="SortableColumn" href="index.php?Page=Segment"><img src="images/sortup.gif" border="0" /></a>&nbsp;<a id="hrefColumnSort_CreateDate_Down" class="SortableColumn" href="index.php?Page=Segment"><img src="images/sortdown.gif" border="0" /></a>
						</td>
						<td width="130" align="center">%%LNG_Subscribers%%</td>
						<td width="130">%%LNG_Action%%</td>
					</tr>
					%%GLOBAL_SegmentList%%
				</table>
				{template="Paging_Bottom"}
			</form>
		</td>
	</tr>
</table>
