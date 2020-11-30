<div style="display: none" id="ProgressWindow">
<div id="ProgressBarDiv" style="text-align: center;"><br/><span id="ProgressBarText" class="ProgressBarText">%%LNG_ImageManagerImageUpload%%</span><br/><br/><br/>
	<div style="border: 1px solid #ccc; width: 300px; padding: 0px; margin: 0 auto; position: relative;">
		<div class="progressBarPercentage" style="margin: 0; padding: 0; background: url('images/progressbar.gif') no-repeat; height: 20px; width: 0%; ">
			&nbsp;
		</div>
		<div style="position: absolute; top: 0px; left: 0; text-align: center; width: 300px; font-weight: bold;line-height:1.5;color:#333333;font-family:Tahoma;font-size:11px;" class="progressPercent">&nbsp;</div>
	</div>
	<span id="progressBarStatus" class="progressBarStatus" style="text-align: center; font-size: 10px; color: gray; padding-top: 5px;">&nbsp;</span>
</div>

</div>

<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">%%LNG_ImageManagerManage%%</td>
	</tr>
	<tr>
		<td class="body pageinfo">
			<p>%%LNG_Help_ImageManagerManage%%</p>
		</td>
	</tr>
	<tr>
		<td>
			<div id="MainMessage">
			%%GLOBAL_Message%%
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<table width="100%" border="0">
				<tr>
					<td valign="top" valign="bottom">
						<span id="spanButtonPlaceholder"></span>
						%%GLOBAL_ImageManager_AddButton%%
						%%GLOBAL_ImageManager_DeleteButton%%
					</td>
					<td id="pagination" align="right" valign="top" style="display:%%GLOBAL_DisplayImagePanel%%;">
						<div style="float:right" >
						{$Pagination}
						</div>
						<div style="float:right; padding-right:10px;">
						%%LNG_SortBy%%: <select name="SortBy" class="Field" style="width: 170px; margin-bottom:4px;" onChange="ChangeImageManagerSorting(this, '{$PageNumber}');">
						%%GLOBAL_SortList%%
						</select>
						</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<div id="hasImages" style="display: %%GLOBAL_DisplayImagePanel%%;">
<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="body">
			<table border=0 cellspacing="0" cellpadding="0" width=100% class="Text">
				<tr class="Heading3">
					<td nowrap>
						<input type="checkbox" name="toggleAllChecks" class="UICheckboxToggleSelector" id="toggleAllChecks" style="margin: 3px 0pt 0pt 3pt; float: left;" onclick="AdminImageManager.CheckAllCheckBoxes(this);"/> <label style="display: block; padding-top: 4px; float: left; padding-left: 10px;" for="toggleAllChecks"><span id="ImgNum">%%GLOBAL_NumImageShown%%</span></label>
					</td>
				</tr>
				<tr class="GridRow">
					<td style="padding:0pt;">
						<div id="imagesList"><script type="text/javascript">
						<!--
						%%GLOBAL_dirImages%%
						//-->
						</script>
					
						</div>
					</td>
			</table>
		</td>
	</tr>
</table>
</div>