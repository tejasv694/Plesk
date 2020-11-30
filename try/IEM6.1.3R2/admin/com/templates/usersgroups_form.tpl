<link rel="stylesheet" type="text/css" href="includes/styles/thickbox.css" />
<style type="text/css">
	select#groupRecord_Permissions { height: 200px; }
	div.groupRecord_Access_Resource_List { padding: 5px 0px 5px 0px; }
	div.groupRecord_Access_Resource_List select { height: 80px; }
	
	.groupRecord_COMMON_NodeJoin {
		background: transparent url(images/nodejoin.gif) top left no-repeat scroll;
		padding-left: 25px;
		line-height: 25px;
	}
</style>

<form name="frmUsersGroups" method="post" action="index.php?Page=UsersGroups&Action=saveRecord">
	<input type="hidden" name="requestToken" value="{$requestToken}" />
	<input type="hidden" name="record[groupid]" value="{$record.groupid}" />
	<table cellspacing="0" cellpadding="0" width="100%" align="center">
		<tr><td class="Heading1">
			{if $record.groupid}
				{$lang.UsersGroups_Form_EditGroup}
			{else}
				{$lang.UsersGroups_Form_CreateGroup}
			{/if}
		</td></tr>
		<tr><td class="body pageinfo"><p>
			{if $record.groupid}
				{$lang.UsersGroups_Form_EditGroup_Intro}
			{else}
				{$lang.UsersGroups_Form_CreateGroup_Intro}
			{/if}
		</p></td></tr>
		{if trim($PAGE.messages) != ''}<tr><td>{$PAGE.messages}</td></tr>{/if}
		<tr>
			<td class="body">
				<input class="FormButton" type="submit" value="{$lang.Save}"/>
				<input class="FormButton CancelButton" type="button" value="{$lang.Cancel}"/>
			</td>
		</tr>
		<tr><td class="EmptyRow">&nbsp;</td></tr>
		<tr>
			<td>
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="Panel">
					<tr><td class=Heading2 colspan=2 style="padding-left:10px">{$lang.UsersGroups_Form_Header_GroupDetails}</td></tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							{$lang.UsersGroups_Field_GroupName}:
						</td>
						<td>
							<input type="text" name="record[groupname]" value="{$record.groupname}" class="Field450" />
							{$lnghlp.UsersGroups_Field_GroupName}
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Required"}
							{$lang.UsersGroups_Field_Permissions}:
						</td>
						<td>
							<input
								type="checkbox"
								id="isSystemAdministrator"
								name="record[permissions][]"
								value="system.system"
								
								{if $isSystemAdmin}
								checked="checked"
								{/if}
								
								{if $isLastAdminWithUsers}
								disabled="disabled"
								{/if}
							/>
							<label for="isSystemAdministrator">{$lang.systemAdminLabel}</label>
							{if $isSystemAdmin && $isLastAdminWithUsers}
							<input type="hidden" name="record[permissions][]" value="system.system" />
							<p class="Message" style="margin: 2px 0; width: 446px;">{$lang.UsersGroups_SystemAdminCheckboxDisabledMessage}</p>
							{/if}
							
							<div class="hideOnSystemAdminSelect groupRecord_COMMON_NodeJoin">
								<select id="groupRecord_Permissions" name="record[permissions][]" multiple="multiple" class="ISSelectReplacement">
									{foreach from=$permissionList key=permissionGroupValue item=permissionInGroups id=permissionGroupLoop}
										<optgroup label="{$permissionInGroups.text}">
											{foreach from=$permissionInGroups.children key=permissionAreaValue item=permissionAreaItem id=permissionInGroupLoop}
												{capture name=permissionOptionKey}{$permissionGroupValue}.{$permissionAreaValue}{/capture}
												<option value="{$permissionOptionKey}" {if in_array($permissionOptionKey, $record.permissions_stupid_template)}selected="selected"{/if}>
													{$permissionAreaItem.text}
												</option>
											{/foreach}
										</optgroup>
									{/foreach}
								</select>
							</div>
						</td>
					</tr>

					<tr class="hideOnSystemAdminSelect"><td class="EmptyRow" colspan="2">&nbsp;</td></tr>
					<tr class="hideOnSystemAdminSelect"><td class=Heading2 colspan=2 style="padding-left:10px">{$lang.UsersGroups_Form_Header_GroupAccess}</td></tr>
					<tr class="hideOnSystemAdminSelect">
						<td class="FieldLabel">
							{template="Required"}
							{$lang.UsersGroups_Field_Access_Lists}:
						</td>
						<td>
							<select name="record[listadmin]" class="Field450 groupRecord_Access_Resource_Selector" {if is_array($record.permissions.system) && (in_array('system', $record.permissions.system) || in_array('list', $record.permissions.system))}disabled="disabled"{/if}>
								<option value="1" {if $record.listadmin}selected="selected"{/if}>{$lang.UsersGroups_access_lists_all}</option>
								<option value="0" {if !$record.listadmin}selected="selected"{/if}>{$lang.UsersGroups_access_lists_custom}</option>
							</select>
							{$lnghlp.UsersGroups_Field_Access_Lists}
							<div id="groupRecord_record_listadmin_resources" class="groupRecord_Access_Resource_List">
								{if count($availableLists) == 0}
									{$lang.UsersGroups_access_lists_none}
								{else}
									<div {if $record.listadmin}style="display:none;"{/if}>
										<select name="record[access][lists][]" multiple="multiple" class="ISSelectReplacement ISSelectSearch">
											{foreach from=$availableLists item=each}
												<option value="{$each.listid}" {if is_array($record.access.lists) && in_array($each.listid, $record.access.lists)}selected="selected"{/if}>{$each.name}</option>
											{/foreach}
										</select>
									</div>
								{/if}
							</div>
						</td>
					</tr>
					<tr class="hideOnSystemAdminSelect">
						<td class="FieldLabel">
							{template="Required"}
							{$lang.UsersGroups_Field_Access_Segments}:
						</td>
						<td>
							<select name="record[segmentadmin]" class="Field450 groupRecord_Access_Resource_Selector" {if is_array($record.permissions.system) && (in_array('system', $record.permissions.system))}disabled="disabled"{/if}>
								<option value="1" {if $record.segmentadmin}selected="selected"{/if}>{$lang.UsersGroups_access_segments_all}</option>
								<option value="0" {if !$record.segmentadmin}selected="selected"{/if}>{$lang.UsersGroups_access_segments_custom}</option>
							</select>
							{$lnghlp.UsersGroups_Field_Access_Segments}
							<div id="groupRecord_record_segmentadmin_resources"  class="groupRecord_Access_Resource_List">
								{if count($availableSegments) == 0}
									{$lang.UsersGroups_access_segments_none}
								{else}
									<div {if $record.segmentadmin}style="display:none;"{/if}>
										<select name="record[access][segments][]" multiple="multiple" class="ISSelectReplacement ISSelectSearch">
											{foreach from=$availableSegments item=each}
												<option value="{$each.segmentid}" {if is_array($record.access.segments) && in_array($each.segmentid, $record.access.segments)}selected="selected"{/if}>{$each.segmentname}</option>
											{/foreach}
										</select>
									</div>
								{/if}
							</div>
						</td>
					</tr>
					<tr class="hideOnSystemAdminSelect">
						<td class="FieldLabel">
							{template="Required"}
							{$lang.UsersGroups_Field_Access_Templates}:
						</td>
						<td>
							<select name="record[templateadmin]" class="Field450 groupRecord_Access_Resource_Selector" {if is_array($record.permissions.system) && (in_array('system', $record.permissions.system) || in_array('template', $record.permissions.system))}disabled="disabled"{/if}>
								<option value="1" {if $record.templateadmin}selected="selected"{/if}>{$lang.UsersGroups_access_templates_all}</option>
								<option value="0" {if !$record.templateadmin}selected="selected"{/if}>{$lang.UsersGroups_access_templates_custom}</option>
							</select>
							{$lnghlp.UsersGroups_Field_Access_Templates}
							<div id="groupRecord_record_templateadmin_resources" class="groupRecord_Access_Resource_List">
								{if count($availableTemplates) == 0}
									{$lang.UsersGroups_access_templates_none}
								{else}
									<div {if $record.templateadmin}style="display:none;"{/if}>
										<select name="record[access][templates][]" multiple="multiple" class="ISSelectReplacement ISSelectSearch">
											{foreach from=$availableTemplates key=templateid item=templateName}
												<option value="{$templateid}" {if is_array($record.access.templates) && in_array($templateid, $record.access.templates)}selected="selected"{/if}>{$templateName}</option>
											{/foreach}
										</select>
									</div>
								{/if}
							</div>
						</td>
					</tr>
					
					<tr><td class="EmptyRow" colspan="2">&nbsp;</td></tr>
					<tr><td class=Heading2 colspan=2 style="padding-left:10px">{$lang.UsersGroups_Form_Header_GroupRestrictions}</td></tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							{$lang.UsersGroups_Field_Restriction_MailingList}:
						</td>
						<td>
							<div>
								<input type="checkbox" id="recordGroup_Restriction_limit_list_flag" class="recordGroup_Restrictions_Flag" {if $record.limit_list == 0}checked="checked"{/if}/>
								<label for="recordGroup_Restriction_limit_list_flag">{$lang.UsersGroups_Restrictions_Lists_Unlimited}</label>
								{$lnghlp.UsersGroups_Field_Restriction_MailingList}
							</div>
							<div id="recordGroup_Restriction_limit_list_container" class="groupRecord_COMMON_NodeJoin" {if $record.limit_list == 0}style="display:none;"{/if}>
								{$lang.UsersGroups_Restrictions_Lists_Finite}
								<input type="text" class="Field50" value="{$record.limit_list}" name="record[limit_list]"/>
								{$lnghlp.UsersGroups_Restrictions_Lists_Finite}
							</div>
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							{$lang.UsersGroups_Field_Restriction_EmailsPerHour}:
						</td>
						<td>
							<div>
								<input type="checkbox" id="recordGroup_Restriction_hourlyemailsrate_flag" class="recordGroup_Restrictions_Flag" {if $record.limit_hourlyemailsrate == 0}checked="checked"{/if}/>
								<label for="recordGroup_Restriction_hourlyemailsrate_flag">{$lang.UsersGroups_Restrictions_EmailsPerHour_Unlimited}</label>
								{$lnghlp.UsersGroups_Field_Restriction_EmailsPerHour}
							</div>
							<div id="recordGroup_Restriction_hourlyemailsrate_container" class="groupRecord_COMMON_NodeJoin" {if $record.limit_hourlyemailsrate == 0}style="display:none;"{/if}>
								{$lang.UsersGroups_Restrictions_EmailsPerHour_Finite}
								<input type="text" class="Field50" value="{$record.limit_hourlyemailsrate}" name="record[limit_hourlyemailsrate]"/>
								{$lnghlp.UsersGroups_Restrictions_EmailsPerHour_Finite}
							</div>
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							{$lang.UsersGroups_Field_Restriction_EmailsPerMonth}:
						</td>
						<td>
							<div>
								<input type="checkbox" id="recordGroup_Restriction_emailspermonth_flag" class="recordGroup_Restrictions_Flag" {if $record.limit_emailspermonth == 0}checked="checked"{/if}/>
								<label for="recordGroup_Restriction_emailspermonth_flag">{$lang.UsersGroups_Restrictions_EmailsPerMonth_Unlimited}</label>
								{$lnghlp.UsersGroups_Field_Restriction_EmailsPerMonth}
							</div>
							<div id="recordGroup_Restriction_emailspermonth_container" class="groupRecord_COMMON_NodeJoin" {if $record.limit_emailspermonth == 0}style="display:none;"{/if}>
								{$lang.UsersGroups_Restrictions_EmailsPerMonth_Finite}
								<input type="text" class="Field50" value="{$record.limit_emailspermonth}" name="record[limit_emailspermonth]"/>
								{$lnghlp.UsersGroups_Restrictions_EmailsPerMonth_Finite}
							</div>
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							{$lang.UsersGroups_Field_Restriction_TotalEmails}:
						</td>
						<td>
							<div>
								<input type="checkbox" id="recordGroup_Restriction_totalemailslimit_flag" class="recordGroup_Restrictions_Flag" {if $record.limit_totalemailslimit == 0}checked="checked"{/if}/>
								<label for="recordGroup_Restriction_totalemailslimit_flag">{$lang.UsersGroups_Restrictions_TotalEmails_Unlimited}</label>
								{$lnghlp.UsersGroups_Field_Restriction_TotalEmails}
							</div>
							<div id="recordGroup_Restriction_totalemailslimit_container" class="groupRecord_COMMON_NodeJoin" {if $record.limit_totalemailslimit == 0}style="display:none;"{/if}>
								{$lang.UsersGroups_Restrictions_TotalEmails_Finite}
								<input type="text" class="Field50" value="{$record.limit_totalemailslimit}" name="record[limit_totalemailslimit]"/>
								{$lnghlp.UsersGroups_Restrictions_TotalEmails_Finite}
							</div>
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							{$lang.UsersGroups_Field_Restrictions_ForceDoubleOptIn}
						</td>
						<td>
							<input type="checkbox" id="recordGroup_Restriction_ForceDoubleOptIn" name="record[forcedoubleoptin]" value="1" {if $record.forcedoubleoptin}checked="checked"{/if} />
							<label for="recordGroup_Restriction_ForceDoubleOptIn">{$lang.UsersGroups_Restrictions_ForceDoubleOptIn_Explain}</label>
							{$lnghlp.UsersGroups_Field_Restrictions_ForceDoubleOptIn}
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							{template="Not_Required"}
							{$lang.UsersGroups_Field_Restrictions_ForceSpamCheck}
						</td>
						<td>
							<input type="checkbox" id="recordGroup_Restriction_ForceSpamCheck" name="record[forcespamcheck]" value="1" {if $record.forcespamcheck}checked="checked"{/if} />
							<label for="recordGroup_Restriction_ForceSpamCheck">{$lang.UsersGroups_Restrictions_ForceSpamCheck_Explain}</label>
							{$lnghlp.UsersGroups_Field_Restrictions_ForceSpamCheck}
						</td>
					</tr>

					<tr><td colspan="2">&nbsp;</td></tr>
					<tr>
						<td>&nbsp;</td>
						<td>
							<input class="FormButton" type="submit" value="{$lang.Save}"/>
							<input class="FormButton CancelButton" type="button" value="{$lang.Cancel}"/>
						</td>
					</tr>
				</table>
			</td>
		</tr>

	</table>
</form>

<script src="includes/js/jquery/thickbox.js"></script>
<script>

(function($) {

Application.Page.UsersGroups_Form = {
	eventReady: function() {
		$(document.frmUsersGroups).submit(Application.Page.UsersGroups_Form.eventSubmitForm);
		$('input.CancelButton', document.frmUsersGroups).click(Application.Page.UsersGroups_Form.eventCancelForm);
		$('option', document.frmUsersGroups['record[permissions][]']).click(Application.Page.UsersGroups_Form.eventPermissionsClick);
		$('select.groupRecord_Access_Resource_Selector').change(Application.Page.UsersGroups_Form.eventResourceSelectorChanged);
		$('input.recordGroup_Restrictions_Flag').click(Application.Page.UsersGroups_Form.eventRestrictionsFlagClicked);
		$('input[type=text]').focus(Application.Page.UsersGroups_Form.eventTextBoxFocus);

		/*
		 * If the user is checked as a system administrator, then they have access to all
		 * permissions, so remove the ability to change permissions by hiding the UI to
		 * do so and disabling the input fields inside of it.
		 */
		$('#isSystemAdministrator').bind('click', _togglePermissionsSelector);

		// we must toggle this on load to initialize it
		_togglePermissionsSelector();

		document.frmUsersGroups['record[groupname]'].focus();
	},

	eventSubmitForm: function(event) {
		var isSystemAdmin   = $('#isSystemAdministrator').is(':checked');
		var form            = document.frmUsersGroups;
		var groupName       = form['record[groupname]'];
		var permissions     = form['record[permissions][]'];
		var options         = $('option', permissions);
		var selectedOptions = options.filter(':selected');
		
		if (groupName.value.trim() == '') {
			groupName.focus();

			alert('{$lang.UsersGroups_Form_JS_Alert_GroupName_Empty}');
			
			return false;
		}

		if (!isSystemAdmin && selectedOptions.size() == 0) {
			if (!confirm('{$lang.UsersGroups_Form_JS_Confirm_Permissions_Empty}')) {
				return false;
			}
		}

		$('select.groupRecord_Access_Resource_Selector', form).attr('disabled', false);

		return true;
	},

	eventCancelForm: function(event) {
		if (confirm('{$lang.ConfirmCancel}'))
			document.location.href='index.php?Page=UsersGroups';
	},

	eventResourceSelectorChanged: function(event) {
		try {
			var name = event.target.name.replace(/\[|\]/g, '_');
			
			$('#groupRecord_' + name + 'resources > div')[$(event.target).val() == 1? 'hide' : 'show']();
		} catch(e) {
			
		}
	},

	eventRestrictionsFlagClicked: function(event) {
		try {
			var id = event.target.id.replace(/_flag$/, '_container');

			if (event.target.checked) $('div#' + id).hide().children('input').val(0);
			else $('div#' + id).show().children('input').focus();
		} catch(e) {
			
		}
	},

	eventPermissionsClick: function(event) {
		var regex = new RegExp('^system\.(.+)');

		if (!regex.test(event.target.value))
			return true;

		var admin_permission_name = event.target.value.replace(regex, '$1');
		var associations          = [
			{option_value: 'system', list_name: 'record[segmentadmin]'},
			{option_value: 'list', list_name: 'record[listadmin]'},
			{option_value: 'template', list_name: 'record[templateadmin]'}
		];

		var system_system = $('#isSystemAdministrator').is(':checked');
		
		if (admin_permission_name != 'system' && system_system)
			return true;
		
		for (var i = 0, j = associations.length; i < j; ++i) {
			var disabled = event.target.selected;
			
			if (admin_permission_name != 'system' && associations[i].option_value != admin_permission_name)
				continue;
			
			if (admin_permission_name == 'system' && associations[1].option_value != 'system' && !system_system)
				disabled = $('option[value="system.' + associations[i].option_value + '"]', document.frmUsersGroups['record[permissions][]']).get(0).selected;

			$(document.frmUsersGroups[associations[i].list_name]).val(disabled? '1' : '0').change().attr('disabled', disabled);
		}
	},

	eventTextBoxFocus: function(event) {
		event.target.select();
	}
};

Application.init.push(Application.Page.UsersGroups_Form.eventReady);



/**
 * Toggles the permissions selector based on if the system admin
 * checkbox is checked or not.
 * 
 * @return void
 */
function _togglePermissionsSelector() {
	var cb  = $('#isSystemAdministrator');
	var sel = $('.hideOnSystemAdminSelect');
	
	if (cb.is(':checked')) {
		sel
			.hide()
			.find(':input')
			.attr('disabled', 'disabled');
	} else {
		sel
			.show()
			.find(':input')
			.removeAttr('disabled');
	}
}

})(jQuery);

</script>
