<div class="Heading1" style="padding-top:5px;">{$lang.PageTitle}</div>

%%GLOBAL_SystemMessage%%

<table cellspacing="0" cellpadding="0" width="100%" align="center" border="0" class="IndexContent">
	<tr>
		<td colspan="2" class="body pageinfo" id="GlobalMessage">%%GLOBAL_Message%%</td>
	</tr>
	<tr>
		<td valign="top" width="450">
			<div>
				<table cellspacing="0" cellpadding="0" width="100%" border="0">
					<tr>
						<td width="446">
							%%GLOBAL_DisplayBox%%
							<table cellspacing="0" cellpadding="0" class="DashboardPanelBox1" id="GettingStartedPanel">
								<tr>
									<td id="StartLinks" class="PanelContentBox1 IndexPage_GettingStarted_Content" valign="top">
	
										<div class="PanelHeaderBox1 IndexPage_GettingStarted_Header">
											<div class="QuickLink">
												<a href="#" id="SwitchLinks">%%GLOBAL_SwitchLink%%</a>
											</div>
											<div id="HomeStartTitle">
												%%GLOBAL_StartTitle%%
											</div>
										</div>
										<div id="HomeGettingStarted" style="%%GLOBAL_HomeGettingStartedDisplay%%">
											<table width="100%" cellpadding="0" cellspacing="0">
												<tr>
													<td valign="top" width="100%" {if $showintrovideo === true}style="padding-bottom:14px;"{/if}>
														<div>
															<div class="DashboardPanelSubBox1">
																
																<div style="padding:0px 10px 15px 0px;float:left;">
																	<a href="index.php?Page=lists&Action=manage" >
																		<img border="0" src="images/but_createlist.gif" />
																	</a>
																</div>
																<div style="float:left;">
																	<a href="index.php?Page=newsletters&Action=create" >
																		<img border="0" src="images/but_createemail.gif" />
																	</a>
																</div>
															</div>
															{if $showintrovideo === true}
																<div style="clear:both;">
																	<div class="PanelHeaderBox1">
																		<div id="HomeStartTitle" style="float:left;color:#636363;">%%LNG_GettingStarted_LearnMore%%</div>
																		<div id="HideThisDiv" class="QuickLink HideThis">
																			&nbsp;<a href="#" id="HideThis">
																			%%GLOBAL_HideThisText%%
																			</a>
																		</div>
																	</div>
																	<div class="DashboardPanelSubBox2" style="clear:both;%%GLOBAL_HideThisDisplay%%">
																		<div style="padding:12px 20px 0px 60px;background:transparent url(images/learn-video.gif) left top no-repeat;">
																				<a id="VideoLearnMore" href="#">%%LNG_GettingStarted_WatchText%%</a>
																		</div>
																		<div style="padding:12px 0px 0px 60px;background:transparent url(images/learn-guide.gif) left top no-repeat;">
																			<a target="_blank" href="%%LNG_Home_Getting_Starting_Link%%">%%LNG_GettingStarted_ReadText%%</a>
																		</div>
																	</div>
																</div>
															{/if}
														</div>
													</td>
												</tr>
											</table>
										</div>
	
										<div id="HomeQuickLinks" style="%%GLOBAL_HomeQuickLinksDisplay%%">
											<table width="100%" cellpadding="0" cellspacing="0">
												<tr>
													<td valign="top" width="100%">
														
														<table width="100%" class="DashboardPanelSubBox3" cellpadding="0" cellspacing="0">
															<tr>
																<td valign="top" width="50%">
																	<ul>
																		<li><a href="index.php?Page=lists&amp;Action=manage">{$lang.QuickLinks_ViewSubscriberLists}</a></li>
																		<li><a href="index.php?Page=lists&amp;Action=create">{$lang.QuickLinks_CreateSubscriberLists}</a></li>
																		<li><a href="index.php?Page=CustomFields&amp;Action=Manage">{$lang.QuickLinks_ViewCustomFields}</a></li>
																		<li><a href="index.php?Page=newsletters&amp;Action=manage">{$lang.QuickLinks_ViewCampaigns}</a></li>
																		<li><a href="index.php?Page=newsletters&amp;Action=create">{$lang.QuickLinks_CreateCampaigns}</a></li>
																		<li><a href="index.php?Page=send">{$lang.QuickLinks_SendCampaigns}</a></li>
																		<li><a href="index.php?Page=autoresponders&amp;Action=manage">{$lang.QuickLinks_ViewAutoresponders}</a></li>
																	</ul>
																</td>
																<td valign="top" width="50%">
																	<ul>
																		<li><a href="index.php?Page=autoresponders&amp;Action=create">{$lang.QuickLinks_CreateAutoresponders}</a></li>
																		<li><a href="index.php?Page=subscribers&amp;Action=manage">{$lang.QuickLinks_ViewSubscribers}</a></li>
																		<li><a href="index.php?Page=subscribers&amp;Action=add">{$lang.QuickLinks_AddSubscribers}</a></li>
																		<li><a href="index.php?Page=templates&amp;Action=manage">{$lang.QuickLinks_ViewTemplates}</a></li>
																		<li><a href="index.php?Page=templates&amp;Action=create">{$lang.QuickLinks_CreateTemplates}</a></li>
																		<li><a href="index.php?Page=forms&amp;Action=manage">{$lang.QuickLinks_ViewForms}</a></li>
																		<li><a href="index.php?Page=forms&amp;Action=create">{$lang.QuickLinks_CreateForms}</a></li>
																	</ul>
																</td>
															</tr>
														</table>
	
													
													</td>
												</tr>
											</table>
										</div>
	
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>
			<div>
				<table cellspacing="0" cellpadding="0" width="100%" border="0">
					<tr>
						<td>
							<table width="446" cellspacing="0" cellpadding="0" class="DashboardPanel" id="HpCampaignPanel">
								<tr>
									<td id="CampaignLinks" class="PanelContent" style="height:0;padding:16px 16px 20px 16px;" valign="top">
										<div id="RecentPanel">
											<div class="PanelHeaderBox1 IndexPage_GettingStarted_Header" style="padding:0px 0px 10px 0px;">
												<div id="HomeStartTitle">%%LNG_GettingStarted_RecentCampaign%%</div>
											</div>
											<div id="CampaignOptionsLinks">

												<table width="100%" cellpadding="0" cellspacing="0">
													<tr>
														<td valign="top" width="100%" style="padding-bottom:14px;">
															<div id="CampaignOptions" class="CampaignOptionsLinks">
																<span class="CampaignShow">%%LNG_GettingStarted_Show%%</span> 
																<span id="campaignshowall" class="NonCampaignOptionsSelected"> 
																	<span class="left"></span>
																	<a href="#" class="EachCampaignOptionsLink">%%LNG_GettingStarted_AllCampaign%%</a>
																	<span class="right"></span>
																</span>
																<span id="campaignshowschedule" class="NonCampaignOptionsSelected"> 
																	<span class="left"></span>
																	<a href="#">%%LNG_GettingStarted_ScheduledCampaign%%</a>
																	<span class="right"></span>
																</span>
																<span id="campaignshowsent" class="NonCampaignOptionsSelected"> 
																	<span class="left"></span>
																	<a href="#">%%LNG_GettingStarted_SentCampaign%%</a>
																	<span class="right"></span>
																</span>
																<span id="campaignshowarchive" class="NonCampaignOptionsSelected"> 
																	<span class="left"></span>
																	<a href="#">%%LNG_GettingStarted_ArchivedCampaign%%</a>
																	<span class="right"></span>
																</span>
															</div>
														</td>
													</tr>
												</table>
											</div>
											<div id="HomeListCampaign">
											<div id="CampaignList">
											</div>
										</div>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>
		</td>
		<td id="StatsLinks" class="PanelContent" style="height:0;padding:0px 0px 0px 16px;" valign="top">
			<table width="100%" cellspacing="0" cellpadding="0" class="DashboardPanel">
				<tr>
					<td class="PanelContent" valign="top">
						<div id="GraphPanel">
							<div class="PanelHeaderBox1 IndexPage_GettingStarted_Header" style="padding:0px 0px 10px 5px;">
								<div id="HomeStartTitle">%%LNG_GettingStarted_LatestStats%%</div>
							</div>
							<div>
								<table width="100%" cellpadding="0" cellspacing="0">
									<tr>
										<td valign="top" width="100%" style="padding-left:7px;">
											<div id="CampaignStatsListId" class="CampaignStatsListClass">
												<span id="CampaignStatsListDropdownWrapper">
													<img src="images/loading.gif" />
												</span> 
											</div>
											<div id="CampaignStatsChartWrapperId"></div>
										</td>
									</tr>
								</table>
							</div>
							<div id="ViewAllStatsButtonId" class="ActionButton ViewAllStatsButton" style="display:none;">
								<a href="index.php?Page=Stats">
									<span class="RightEnd">
									</span>
									<span class="ActionIcon">
									</span>
									<span class="ButtonText">
										%%LNG_GettingStarted_ViewAllStats%%
									</span>
								</a>
							</div>
						</div>
					</td>
				</tr>
			</table>


			<div>
			<table width="100%" cellspacing="0" cellpadding="0" class="DashboardPanel">
				<tr>
					<td class="PanelContent" valign="top" style="height:0;">
						<div id="KbArticlesPanel">
							<div class="PanelHeaderBox1 IndexPage_GettingStarted_Header" style="padding:0px 0px 10px 5px;">
									<span>%%LNG_GettingStarted_RecentlyArticles%%</span>
							</div>
							<div>
								<table width="100%" cellpadding="0" cellspacing="0">
									<tr>
										<td valign="top" width="100%" style="padding-left:7px;">
											<div id="RecentContactListItem">
												<ul id="kbRecentContactListList">
													<li><img src="images/loading.gif" /></li>
												</ul>
											</div>
											<div class="ActionButton BrowseCLButton" style="display:%%GLOBAL_DisplayListButton%%;">
												<a href="index.php?Page=Lists">
													<span class="RightEnd">
													</span>
													<span class="ActionIcon">
													</span>
													<span class="ButtonText">
														%%LNG_GettingStarted_BrowseContactList%%
													</span>
												</a>
											</div>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</td>
				</tr>
			</table>
			</div>
		</td>
	</tr>
</table>

<script src="functions/amcharts/amcolumn/swfobject.js"></script>
<script>
	function ActivateGettingStarted()
	{
		$('#HomeGettingStarted').fadeIn();
		$('#HomeQuickLinks')[0].style.display = 'none';
		$('#HomeStartTitle').html("%%LNG_GettingStarted_Header%%");
		$('#SwitchLinks').html('%%LNG_SwitchtoQuickLinks%%');
		$.ajax({
			type: 'post',
			url: 'index.php?Action=switch',
			data: {'To': 'gettingstarted'}
		});
	}

	function DisplayLearnMore(val)
	{
		if (val) {
			var linkText = "%%LNG_GettingStarted_HideThis%%";
			var display = "block";
			$('.DashboardPanelSubBox2').slideDown();
		} else {
			var linkText = "%%LNG_GettingStarted_ShowMore%%";
			var display = "none";
			$('.DashboardPanelSubBox2').slideUp();
		}
		$('#HideThis').html(linkText);
		$.ajax({
			type: 'post',
			url: 'index.php?Action=hidethis',
			data: {'To': display}
		});
	}

	function ActivateQuickLinks()
	{
		$('#HomeQuickLinks').fadeIn();
		$('#HomeGettingStarted')[0].style.display = 'none';
		$('#HomeStartTitle').html('%%LNG_IWouldLikeTo%%');
		$('#SwitchLinks').html('%%LNG_SwitchtoGettingStartedLinks%%');
		$.ajax({
			type: 'post',
			url: 'index.php?Action=switch',
			data: {'To': 'quicklinks'}
		});
	}
	
	var IndexPage = {
		GetCampaignList: function(ListOption) {
		$('#CampaignList').html('<img src="images/loading.gif" />');
			$.ajax({
				type: 'post',
				url: 'index.php?Action=getcampaignlist',
				data: {'To': ListOption},
				success: function(resp){
					$('#CampaignList').hide();
					$('#CampaignList').html(resp);
					$('#CampaignList').fadeIn();
				}
			});
		}, 
		GetCampaignDropDown: function(SelectedCampaignChart) {
			$.ajax({
				type: 'post',
				url: 'index.php?Action=getcampaigndropdown',
				data: {'SelectedCampaignChart': SelectedCampaignChart},
				success: function(resp){
					$('#CampaignStatsListDropdownWrapper').html(resp);
					$('#CampaignStatsListDropdown').change(function() {
						$('#ViewAllStatsButtonId').show();
						$.ajax({
							type: 'post',
							url: 'index.php?Action=getcampaignchart',
							data: {'StatId': $(this).val()},
							success: function(resp) {
								$('#CampaignStatsChartWrapperId').html(resp);
							}
						});
						return false;
					});
					$.ajax({
						type: 'post',
						url: 'index.php?Action=getcampaignchart',
						data: {'StatId': SelectedCampaignChart},
						success: function(resp) {
							$('#CampaignStatsChartWrapperId').html(resp);
						}
					});
				}
			});
		},
		VideoTour: function () {
			var w = 660;
			var h = 600;
			var l = screen.availWidth/2-(w/2);
			var t = screen.availHeight/2-(h/2);
			window.open('%%LNG_Home_Video_Link%%', 'iemVideoTour', 'width='+w+',height='+h+',top='+t+',left='+l);
			return false;
		}
	}

	$(function() {
		// Setup panes
		$('#HideThis').click(function(event) {
			var showMore = ($('.DashboardPanelSubBox2')[0].style.display == 'none');
			DisplayLearnMore(showMore);
			return false;
		});
		
		if ($('#HomeGettingStarted')[0].style.display == 'none') {
			ActivateQuickLinks();
		} else {
			ActivateGettingStarted();
		}

		$('.CampaignOptionsSelected').removeClass('CampaignOptionsSelected');
		$('#%%GLOBAL_CampaignSelectedLink%%').addClass('CampaignOptionsSelected');
		IndexPage.GetCampaignList('%%GLOBAL_CampaignSelectedLink%%');
		IndexPage.GetCampaignDropDown('%%GLOBAL_CampaignSelectedChart%%');
		
		$('#kbRecentContactListList').load('index.php?Action=getrecentlists');

		if ($('#GlobalMessage').html() == '') {
			$('#GlobalMessage').hide();
		}

		$('#SwitchLinks').click(function() {
			if ($('#HomeGettingStarted')[0].style.display == 'none') {
				ActivateGettingStarted();
			} else {
				ActivateQuickLinks();
			}
			return false;
		});
		
		$('#VideoLearnMore').click(function() {IndexPage.VideoTour();});
		
		$('.CampaignOptionsSelected,.NonCampaignOptionsSelected').click(function() {
			$('.CampaignOptionsSelected').removeClass('CampaignOptionsSelected');
			$(this).addClass('CampaignOptionsSelected');
			IndexPage.GetCampaignList($(this).attr('id'));
			$.ajax({
				type: 'post',
				url: 'index.php?Action=campaignview',
				data: {'To': $(this).attr('id')}
			});
			return false;
		});
	});
</script>

%%GLOBAL_VersionCheckInfo%%
