<div id="Campaign_id" style="height:53px;clear:both;margin-bottom:5px;">
	<span class="LeftImage" style="float:left;"></span>
	<span class="RightImage" style="float:right;"></span>
	<div class="MidImage">
		<span class="CampIcon"></span>
		<span style="float:left;" class="CampaignListText">
			<div>
			{if $newsletterdetailsPage.action != 'None'}
			<a href="index.php?Page=Newsletters&Action={$newsletterdetailsPage.action}&id={$newsletterdetailsPage.newsletterid}" {$newsletterdetailsPage.name_link_param} title="{$lang.Edit} {$newsletterdetailsPage.namelong}">{$newsletterdetailsPage.name}</a>
			{else}
			{$newsletterdetailsPage.name}
			{/if}
			</div>
			<div style="padding-top:2px;">{$newsletterdetailsPage.subject}</div>
		</span>
		<span style="float:right;"  class="CampaignListText">
			{$newsletterdetailsPage.createdate}
		</span>
	</div>
</div>
