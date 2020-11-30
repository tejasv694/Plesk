<script src="includes/js/jquery/ui.js"></script>
<script src="includes/js/jquery/plugins/jquery.plugin.js"> </script>
<script src="includes/js/jquery/plugins/jquery.window.js"> </script>
<script src="includes/js/jquery/plugins/jquery.window-extensions.js"> </script>
<script src="includes/js/imodal/imodal.js"></script>


<style type="text/css">
            #blockListId { list-style-type: none; margin: 0; padding: 0; width: 60%;}
            #blockListId li { margin: 0px; padding: 0px; height: 2em;}
            html>body #blockListId li { height: 2em; line-height: 2em;}
            .ui-state-highlight {
                height: 2em;
                line-height: 2em;
                background-color:#FFF1A8;
            }
 </style>
<form name="frmDynamicContentTagsEdit" id="frmDynamicContentTagsEdit" method="post" action="{$AdminUrl}&Action=Save">
	<input type="hidden" id="id_dynamiccontenttags_id" name="dynamiccontenttags_id" value="{$DynamicContentTagId}" />
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr>
			<td class="Heading1">
				{if $FormType == 'create'}
					{$lang.Addon_dynamiccontenttags_Form_CreateHeading}
				{elseif $FormType == 'edit'}
					{$lang.Addon_dynamiccontenttags_Form_EditHeading}
				{/if}
			</td>
		</tr>
		<tr>
			<td class="body pageinfo">
				<p>
					{$lang.Addon_dynamiccontenttags_Form_Intro}
				</p>
			</td>
		</tr>
		<tr>
			<td id="FlashMessages">
				{$FlashMessages}
			</td>
		</tr>
		<tr>
			<td>
				<input class="FormButton submitButton" type="button" name="Submit_Edit" value="{$lang.Addon_dynamiccontenttags_SaveEdit}" style="width:130px" />
				<input class="FormButton submitButton" type="button" name="Submit_Exit" value="{$lang.Addon_dynamiccontenttags_SaveExit}" style="width:100px" />
				<input class="FormButton cancelButton" type="button" value="{$lang.Addon_dynamiccontenttags_Cancel}" />
				<input type="hidden" id="subactid" name="subact" value="saveexit" />
				<br />&nbsp;
				<table border="0" cellspacing="0" cellpadding="2" class="Panel">
					<tr>
						<td colspan="3" class="Heading2">
							&nbsp;&nbsp;{$lang.Addon_dynamiccontenttags_Form_General_Settings}
						</td>
					</tr>
					<tr>
						<td class="FieldLabel" width="200" nowrap="nowrap">
							<img src="images/blank.gif" width="200" height="1" /><br />
							{template="required"}
							{$lang.Addon_dynamiccontenttags_Name}:&nbsp;<br />
						</td>
						<td width="85%">
							<input type="text" id="id_dynamiccontenttags_name" name="dynamiccontenttags_name" class="Field250 form_text" value="{$dynamiccontenttags_name}" style="width:446px;margin-right:0px;" />
							{$lnghlp.Addon_dynamiccontenttags_Name}<br />
                                                        <span style="font-style:italic;">{$lang.Addon_dynamiccontenttags_NameEg}</span><br /><br />
						</td>
					</tr>
					<tr>
						<td class="FieldLabel" width="200" nowrap="nowrap">
							<img src="images/blank.gif" width="200" height="1" /><br />
							{template="required"}
							{$lang.Addon_dynamiccontenttags_Email_List}?&nbsp;
						</td>
						<td width="85%">
							<select id="SelectList" name="SelectList[]" multiple="multiple" class="ISSelectReplacement ISSelectSearch" style="{$SelectListStyle}">
								{$SelectListHTML}
							</select>
							&nbsp;{$lnghlp.Addon_dynamiccontenttags_Email_List}
						</td>
					</tr>
					<tr>
						<td class="FieldLabel" width="200" nowrap="nowrap">&nbsp;
						
						</td>
						<td width="85%">
						<span style="font-style:italic;">{$lang.Addon_dynamiccontenttags_Subscriber_List_Tips}</span>
						</td>
					</tr>
				</table>

				<table border="0" cellspacing="0" cellpadding="4" class="Panel">
					<tr>
						<td colspan="3" class="Heading2">
							&nbsp;&nbsp;{$lang.Addon_dynamiccontenttags_Form_Block_Settings}
						</td>
					</tr>
					<tr>
                                                <td class="FieldLabel" width="200" nowrap="nowrap">
							<img src="images/blank.gif" width="200" height="1" /><br />
							{template="required"}
							{$lang.Addon_dynamiccontenttags_Block}:&nbsp;
						</td>
						<td width="85%">
							<div id="blockContainers">
							<!--block container-->

							</div>
							<div style="float:left;clear:both;margin-top:5px;">
								<input class="FormButton" type="button" name="New_Block" id="Id_New_Block" value="{$lang.Addon_dynamiccontenttags_AddContentBlock}" style="width:140px" />
								<input class="FormButton" type="button" name="Delete_Blocks" id="Id_Delete_Blocks" value="{$lang.Addon_dynamiccontenttags_DeleteSelected}" style="width:130px" />
								{$lnghlp.Addon_dynamiccontenttags_Block}
							</div>

						</td>
					</tr>
				</table>

			</td>
		</tr>
	</table>
</form>

<script type="text/javascript" >
var win;
function ShowBlockForm(blockId, tagId) {
	if (location.href.indexOf('?') != -1) {
		var url_part = location.href.split(/\?/);
		var url_to_indexphp = url_part[0];
	} else {
		var url_to_indexphp = location.href;
	}
	var title = '%%LNG_Addon_dynamiccontenttags_Block_Form_CreateHeading%%';
	var temp = url_to_indexphp + '?Page=Addons&Addon=dynamiccontenttags&Action=showblockform&id=' + blockId + '&tagId=' + tagId + '&ajax=1';
            win = $.fn.window.create({
		title:title,
		height:500,
		width:700,
		uri:temp
	});
	win.open();

}

$(document).ready(function() {
	$('#Id_New_Block').click(function() {
		if ($('#SelectList > ul > li > input:checked').size() < 1) {
			alert ('{$lang.Addon_dynamiccontenttagsOperator_FormAlertSpecifyBlockList}');
			return;
		}
        var randomBlockId = $('#id_dynamiccontenttags_id').val();
		ShowBlockForm(0, randomBlockId);
	});

	$('#Id_Delete_Blocks').click(function() {
		if($('.blocksid_class:checked').size() < 1) {
			alert ('{$lang.Addon_dynamiccontenttags_Delete_SelectBlocks}');
			return;
		}
		// remove the blocks
		if (confirm('{$lang.Addon_dynamiccontentblocks_Delete_ConfirmMessage}')) {
			var selectedDeleteBlocks = new Array;
			$('.blocksid_class:checked').each(function() {
				selectedDeleteBlocks.push($(this).val());
			})
			BlockInterface.DeleteBlock(selectedDeleteBlocks);
		}
	});
	$('#SelectList > ul > li').click(function() {
		if (!$(this).hasClass('SelectedRow')) {
			var otherSelectedList = [];
			$('#SelectList > ul > .SelectedRow > input').each(function() {
				otherSelectedList.push($(this).val());
			});
			BlockInterface.CleanListBlock($(this).children().val(), otherSelectedList);
		}
	});
	$('.cancelButton').click(function() {
		if (confirm('{$lang.Addon_dynamiccontenttagsOperator_FormAlertCancel}')) {
			window.location.href = "{$AdminUrl}";
		}

	});
	$('.submitButton').click(function() {
		if ($('#id_dynamiccontenttags_name').val() == '') {
			alert ('{$lang.Addon_dynamiccontenttagsOperator_FormAlertSpecifyTagName}');
			$('#id_dynamiccontenttags_name').focus();
			return;
		}

		if ($('#SelectList > ul > li > input:checked').size() < 1) {
			alert ('{$lang.Addon_dynamiccontenttagsOperator_FormAlertSpecifyTagList}');
			return;
		}

		if ($('.blocks_class').size() < 1) {
			alert ('{$lang.Addon_dynamiccontenttagsOperator_FormAlertSpecifyBlock}');
			return;
		}

		if ($('.class_defaultset').size() > 0) {
                    var defaultSet = false;
                    $('.class_defaultset').each(function(){
                        if ($(this).text() != '') {
                            defaultSet = true;
                        }
                    });
                    if (!defaultSet) {
                        alert ('{$lang.Addon_dynamiccontenttagsOperator_FormAlertSpecifyDefaultBlockFirst}');
                        return;
                    }
		}

		$('#subactid').val('saveexit');
		if ($(this).attr('name') == 'Submit_Edit') {
			$('#subactid').val('saveedit');
		}

		$.post(	'index.php?Page=Addons&Addon=dynamiccontenttags&Action=checkduplicatetag&ajax=1',
		{'tagid': $('#id_dynamiccontenttags_id').val(), 'tagname': $('#id_dynamiccontenttags_name').val()},
		function(response) {
                    if (response != '') {
                        alert (response);
                        $('#id_dynamiccontenttags_name').focus();
                        return;
                    } else {
                        document.frmDynamicContentTagsEdit.submit();
                    }
		});
		return;
	});
        BlockInterface.TriggerDeleteButton();

	{$dynamiccontenttags_blocks}

        $(function() {
		$("#blockListId").sortable({
                    opacity: 0.6,
                    cursor:'crosshair',
                    placeholder: 'ui-state-highlight',
                    update: function() {
			var order = $(this).sortable("serialize");
			$.post("index.php?Page=Addons&Addon=dynamiccontenttags&Action=updateblocksorder&ajax=1", order,
                        function(response){
			});
                    }
		});
        });

});

var BlockInterface = {
	GetTotalBlock: function () {
		var TotalBlock = $('#blockListId').children().length;
		return TotalBlock;
	},
	Add: function(id, name, activated, sortorder, encodedRules) {
		nameDisplay = name.slice(0,70);
		if (name.length > 70) {
			nameDisplay = nameDisplay + ' ...';
		}
		var blockHtml =
		  ' <li title="' + name + '" class="blocks_class ui-state-default" id="blockid_'+id+'" onmouseover="$(this).addClass(\'ISSelectOptionHover\');" onmouseout="$(this).removeClass(\'ISSelectOptionHover\');" style="clear:both;">'
		+ ' <label style="float:left;width:407px;" for="id_number" style="cursor:pointer;" onclick="if($(this).parent().hasClass(\'SelectedRow\')) {  $(this).parent().removeClass(\'SelectedRow\'); $(this).children().attr(\'checked\', false); } else { $(this).parent().addClass(\'SelectedRow\'); $(this).children().attr(\'checked\',true); }">'
                + ' <input id=\'blockid_'+id+'\' class="blocksid_class" name="blockids" value="'+id+'" type="checkbox" style="vertical-align:top;" />'+nameDisplay+'&nbsp;<span class="class_defaultset" id="id_defaultset_'+id+'" for="defaultset"></span></label>'
		+ ' '
		+ ' <span style="margin-left:10px;">'
		+ ' <label title="Edit" style="margin-right:2px;" for="id_edit">'
		+ ' <a href="#" onclick="ShowBlockForm(\''+id+'\','+$('#id_dynamiccontenttags_id').val()+');"><img style="border:0;" src="images/layoutEdit.png" /></a>'
		+ ' </label>'
		+ ' <label title="Delete" style="margin-right:2px;" id="blockid_'+id+'_deletelbl" for="block_delete">'
		+ ' <a href="#" onclick="if(!confirm(\'{$lang.Addon_dynamiccontentblocks_Delete_ConfirmMessage}\')) {return;} BlockInterface.DeleteBlock('+id+')"><img style="border:0;" src="images/layoutDeleteRed.png" /></a>'
		+ ' </label>'
		+ ' </span>'
		+ ' <input class="blocks_name_class" id="blockid_'+id+'_name" type="hidden" name="blocks['+id+'][name]" value="'+name+'" />'
		+ ' <input id="blockid_'+id+'_data" class="blocks_data_class" name="blocks['+id+'][data]" value=\''+encodedRules+'\' type="hidden"/>'
		+ ' <input class="blockidhidden_class" name="blockids[]" value="'+id+'" type="hidden"/>'
		+ ' <input id=\'blockid_'+id+'_activated\' class="blockid_class" name="blocks['+id+'][activated]" value="'+activated+'" type="hidden"/>'
		+ ' <input id=\'blockid_'+id+'_sortorder\' class="blocksortorder_class" name="blocks['+id+'][sortorder]" value="'+sortorder+'" type="hidden"/>'
		+ ' </li>'
		;

		if(this.GetTotalBlock() <= 0) {
			blockHtml = '<ul class="ISSelect" style="width:465px;height:100px;clear:both;float:left;" id="blockListId">'+blockHtml+'</ul>'
			$('#blockContainers').html(blockHtml);
		} else {
			if ($('#blockid_'+id).length) {
				$('#blockid_'+id).replaceWith(blockHtml);
			} else {
				$('#blockListId').append(blockHtml);
			}
		}


		if (activated == 1) {
			this.SetActivate(id);
		}
                this.TriggerDeleteButton();
	},
	SetActivate: function(id) {
		$('.class_defaultset').text('');
		$('#id_defaultset_'+id).text('{$lang.Addon_dynamiccontenttags_Block_DefaultString}');
		$('.blockid_class').val(0);
		$('#blockid_'+id+'_activated').val(1);
	},
	CleanListBlock: function(id, list) {
		$.post(	'index.php?Page=Addons&Addon=dynamiccontenttags&Action=customfieldcomparebylist&ajax=1',
		{	'ajaxType': 'CustomFieldCompareByList',
		'listid[]': id, 'otherlistid[]': list},
		function(response) {
			var message = '';
			var results = eval('('+response+')');
			var blockIds = new Array();
			if(results) {
				listCustomFields = new Array();
				for(var i=0; i<results.length; i++) {
					listCustomFields.push(results[i]);
				}

				if ($('.blocks_data_class').length > 0) {
					$('.blocks_data_class').each(function() {
						var ExistingBlockRules = $.evalJSON($(this).val()).Rules;
						var ExistingBlockName = $.evalJSON($(this).val()).BlockName;
						var ExistingBlockId = $(this).next().val();
						var CatchedBlock = false;
						for(var k = 0; k < ExistingBlockRules[0].rules.length; k++) {
							for(var i=0; i<listCustomFields.length; i++) {
								if (listCustomFields[i] == ExistingBlockRules[0].rules[k].rules.ruleName ) {
									CatchedBlock = true;
								}
							}
						}
						if (CatchedBlock) {
							message += "\n - " + ExistingBlockName;
							blockIds.push(ExistingBlockId);
						}
					});
				}



			}
			if (message != '') {
				if (confirm("{$lang.Addon_dynamiccontenttags_ListAffectBlocks}: " + message)) {
					BlockInterface.DeleteBlock(blockIds);
					return;
				}
				$('#ISSelectSelectList_'+id).children().click();
				return;
			}
		});
	},
        TriggerDeleteButton: function() {
            if ($('.blocks_class').size() < 1) {
                $('#Id_Delete_Blocks').attr('disabled', 'disabled');
            } else {
                $('#Id_Delete_Blocks').removeAttr('disabled');
            }
        },
	DeleteBlock: function(id) {
                // checking if it's ok to delete the blocks
                var blockArray = [id];
                if (isArray(id)) {
                    blockArray = id;
                }
                if ($('.blocks_class').size() == blockArray.length) {
                    alert ('{$lang.Addon_dynamiccontenttags_FormAlertMinimumBlock}');
                    return;
                }

                // Check if the default block is selected
                var defaultBlockSelected = false;
                for (var i=0; i<blockArray.length; i++) {
                    if( $('#id_defaultset_' + eval(blockArray[i])).text()  != '' ) {
                        defaultBlockSelected = true;
                    }
                }

		$.post(	'index.php?Page=Addons&Addon=dynamiccontenttags&Action=deleteblock&ajax=1',
		{'ajaxType': 'DeleteBlock',
		'blockid[]': id},
		function(response) {
			if (response.result > 0) {
                                for (var i=0; i<blockArray.length; i++) {
                                        $('#blockid_' + blockArray[i]).remove();
                                }
                                // Set the highest priority's block to default block.
                                if (defaultBlockSelected) {
                                    // Update the block list interface
                                    var defaultBlockId = $('#blockListId li:first-child').children('.blockidhidden_class').val();
                                    $('#id_defaultset_' + defaultBlockId).text('{$lang.Addon_dynamiccontenttags_Block_DefaultString}');
                                    $('#blockid_' + defaultBlockId + '_activated').val('1');
                                    // Update the default set in database
                                    $.post("index.php?Page=Addons&Addon=dynamiccontenttags&Action=setdefaultblock&ajax=1",
                                    {'ajaxType': 'SetDefaultBlock',
                                    'blockId':defaultBlockId},
                                    function(response){
                                    });

                                }
			}
			$('#FlashMessages').html(response.message);
                        BlockInterface.TriggerDeleteButton();
		}, "json");
	}
}
</script>