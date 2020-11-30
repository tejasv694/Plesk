<?php
ignore_user_abort(true);
set_time_limit(0);
ini_set('max_execution_time', '-1');
ob_end_clean();
include realpath(dirname(dirname(dirname(__FILE__)))) . '/includes/config.php';

$config = mysql_pconnect(SENDSTUDIO_DATABASE_HOST, SENDSTUDIO_DATABASE_USER, SENDSTUDIO_DATABASE_PASS);
if ($config) {
	$sel_db = mysql_select_db(SENDSTUDIO_DATABASE_NAME, $config);
}
if (!isset($_SESSION)) {
	session_start();
}
$prefix_table = SENDSTUDIO_TABLEPREFIX;

$selInstall = mysql_query("SELECT * FROM `" . $prefix_table . "addons` WHERE `addon_id` = 'alphajet'");
$selInstall_row = mysql_fetch_assoc($selInstall);
if ($selInstall_row['installed'] != 1 || $selInstall_row['configured'] != 1 || $selInstall_row['enabled'] != 1) {
	echo '<script>window.location.href="' . SENDSTUDIO_APPLICATION_URL . '/admin/index.php"</script>';
}

$select_configuration = "SELECT `areavalue` FROM `" . $prefix_table . "config_settings` WHERE `area`  = 'IMPORT_ADD' ";
$query_con = mysql_query($select_configuration);
$num_row_con = mysql_num_rows($query_con);
if ($num_row_con == 0) {
	//echo "nahi";
	$query_alter_table = mysql_query("INSERT INTO `" . $prefix_table . "config_settings` (`area`, `areavalue`) VALUES ('IMPORT_ADD', '1')");
	$query_alter_table = mysql_query("ALTER TABLE `" . $prefix_table . "list_subscribers` ADD UNIQUE( `listid`, `emailaddress`)");
	$query_alter_table2 = mysql_query("ALTER TABLE `" . $prefix_table . "list_subscribers` ENGINE = MyISAM;");
}
// INSERT INTO `emailmarketer`.`email_config_settings` (`area`, `areavalue`) VALUES ('IMPORT_ADD', '1');
?>