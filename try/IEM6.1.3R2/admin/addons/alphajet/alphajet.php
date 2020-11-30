<?php
if (!class_exists('Interspire_Addons', false)) {
	require_once dirname(dirname(__FILE__)) . '/interspire_addons.php';
}
class Addons_alphajet extends Interspire_Addons {
	function install() {
		$this->enable = true;
		$this->configured = true;
		try {
			parent::install();
		} catch (Interspire_Addons_Exception $e) {
			throw new Exception("Unable to install addon $this->GetId();" . $e->getMessage());
		}
		return true;
	}

	function GetEventListeners() {
		$listeners = array();
		$listeners[] = array(
			'eventname' => 'IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS',
			'trigger_details' => array(
				'Addons_alphajet',
				'GetTextMenuItems',
			),
			'trigger_file' => '{%IEM_ADDONS_PATH%}/alphajet/alphajet.php',
		);
		return $listeners;
	}

/*	function GetEventListeners() {
$listeners = array();
$listeners[] = array(
'eventname' => 'IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS',
'trigger_details' => array(
'Addons_alphajet',
'SetMenuItems',
),
'trigger_file' => '{%IEM_ADDONS_PATH%}/alphajet/alphajet.php',
);
return $listeners;
}*/

	/*static function SetMenuItems(EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS $data) {
	$self = new self;

	$alphajet_menu = array(
	'alphajet_button' => array(
	array(
	'text' => GetLang('Addon_alphajet_Menu_Text'),
	'link' => 'addons/alphajet/importer.php" onclick=\""',
	'show' => true,
	'description' => GetLang('Addon_alphajet_Menu_Description'),
	'image' => 'surveys_views.gif',
	),
	array(
	'text' => GetLang('Addon_alphajet_Menu_Text_Suppression'),
	'link' => 'addons/alphajet/suppression.php" onclick=\""',
	'show' => true,
	'description' => GetLang('Addon_alphajet_Menu_Description'),
	'image' => 'surveys_add.gif',
	),
	),
	);

	$menuItems = $data->data;*/

	/**
	 * Putting the survey menu to where it belongs..
	 */

	/*	$new_menuItems = array();
	foreach ($menuItems as $key => $eachmenu) {
	$new_menuItems[$key] = $eachmenu;

	// put it after newsletter
	if ($key == 'newsletter_button') {
	$new_menuItems['alphajet_button'] = $alphajet_menu['alphajet_button'];
	}
	}

	$data->data = $new_menuItems;
	}

	static function RegisterAddonPermissions() {
	$description = self::LoadDescription('alphajet');
	$perms = array(
	'alphajet' => array(
	'addon_description' => GetLang('Addon_Settings_Survey_Header'),
	),
	);
	self::RegisterAddonPermission($perms);
	}*/

	static function GetTextMenuItems(EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS $data) {

		$user = GetUser();
		if (!$user->Admin()) {
			return;
		}

		try {
			$me = new self;
			$me->Load();
		} catch (Exception $e) {
			return;
		}

		if (!$me->enabled) {
			return;
		}

		if (!isset($data->data['tools'])) {
			$data->data['tools'] = array();
		}

		$data->data['tools'][] = array(
			'text' => GetLang('Addon_alphajet_Menu_Text'),
			// FIXME: This is a bit of hackery that is perhaps too clever.
			'link' => "addons/alphajet/importer.php\" onclick=\"",
			'description' => GetLang('Addon_alphajet_Menu_Description'),

		);
		$data->data['tools'][] = array(
			'text' => GetLang('Addon_alphajet_Menu_Text_Suppression'),
			// FIXME: This is a bit of hackery that is perhaps too clever.
			'link' => "addons/alphajet/suppression.php\" onclick=\"",
			'description' => GetLang('Addon_alphajet_Menu_Description'),

		);
		unset($me);
	}

	/*public function Admin_Action_Default()
{
$this->GetTemplateSystem();
$tpl = $this->template_system;
$tpl->ParseTemplate('template1');
}*/
}
?>