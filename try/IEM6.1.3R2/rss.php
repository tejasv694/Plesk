<?php
/**
* This file handles displaying a list of archived newsletters for a person to read.
*
* @version     $Id: rss.php,v 1.15 2008/03/03 01:59:24 hendri Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
*/

// Make sure that the IEM controller does NOT redirect request.
if (!defined('IEM_NO_CONTROLLER')) {
	define('IEM_NO_CONTROLLER', true);
}

// Displaying an rss feed doesn't need a session.
if (!defined('IEM_NO_SESSION')) {
	define('IEM_NO_SESSION', true);
}

// Require base sendstudio functionality. This connects to the database, sets up our base paths and so on.
require_once dirname(__FILE__) . '/admin/index.php';

if (SENDSTUDIO_IS_SETUP != 1) {
	exit;
}

/**
* This file lets us get api's, load language files and parse templates.
*/
require_once(SENDSTUDIO_FUNCTION_DIRECTORY . '/sendstudio_functions.php');

if (!check('rss', true)) {
	exit;
}

$sendstudio_functions = new Sendstudio_Functions();

$sendstudio_functions->LoadLanguageFile('frontend');

$listid = (isset($_GET['List'])) ? (int)$_GET['List'] : 0;

if (isset($_GET['L'])) {
	$listid = (int)$_GET['L'];
}

$baselink = SENDSTUDIO_APPLICATION_URL . '/rss.php?';

if ($listid) {
	$baselink .= 'List=' . $listid;
}

$extra_url = '';
if (isset($_GET['M'])) {
	$extra_url = '&amp;M=' . (int)$_GET['M'] . '&amp;';
	if (isset($_GET['C'])) {
		$extra_url .= 'C=' . urlencode($_GET['C']);
	}
}

$list_api = $sendstudio_functions->GetApi('Lists');

$title = GetLang('NewsletterArchives');

if ($listid > 0) {
	$list_loaded = $list_api->Load($listid);

	if ($list_loaded) {
		$title = sprintf(GetLang('NewsletterArchives_List'), $list_api->Get('name'));
	}
}

$number_to_show = 10;
if (isset($_GET['Fetch'])) {
	$fetch = (int)$_GET['Fetch'];
	if ($fetch > 0) {
		$number_to_show = $fetch;
	}
}

header("Content-Type: text/xml");

$datenow = date("r");

echo '<';
?>
?xml version="1.0" encoding="<?php echo SENDSTUDIO_CHARSET; ?>" ?>
<?php
echo '<';
?>
?xml-stylesheet href="<?php echo SENDSTUDIO_APPLICATION_URL . '/admin/includes/styles'; ?>/rssdisplay.php" type="text/xsl"?>
	<rss version='2.0'>
		<channel>
			<title><?php echo htmlspecialchars($title, ENT_QUOTES, SENDSTUDIO_CHARSET); ?></title>
			<description><?php echo htmlspecialchars($title, ENT_QUOTES, SENDSTUDIO_CHARSET); ?></description>
			<generator>N/A</generator>
			<lastBuildDate><?php echo $datenow; ?></lastBuildDate>
			<ttl>20</ttl>
<?php

$archived_newsletters = $list_api->GetArchives($listid, $number_to_show);

if (empty($archived_newsletters)) {
	?>
	<item>
		<title><?php echo GetLang('NewsletterArchives_NoneSent'); ?></title>
		<description><?php echo GetLang('NewsletterArchives_NoneSent'); ?></description>
		<author><?php echo GetLang('NewsletterArchives_NoneSent'); ?></author>
		<pubdate><?php echo $datenow; ?></pubdate>
		<subject><?php echo GetLang('NewsletterArchives_NoneSent'); ?></subject>
		<link><?php echo $baselink; ?></link>
	</item>
	<?php
} else {
	foreach ($archived_newsletters as $p => $newsletter_details) {
		$author = $newsletter_details['fullname'];
		if (!$author) {
			$author = $newsletter_details['username'];
		}

		if (!empty($newsletter_details['htmlbody'])) {
			$summary = $newsletter_details['htmlbody'];
			$summary = preg_replace('%<(style|script).*?</\1>%si', ' ', $summary);
			$summary = preg_replace('%<(br|div|td|tr|li|p).*?>%si', ' ', $summary);
			$summary = str_replace('&nbsp;', ' ', $summary);
			$summary = strip_tags($summary);
		} elseif (!empty($newsletter_details['textbody'])) {
			$summary = $newsletter_details['textbody'];
		} else {
			$summary = '';
		}

		?>
		<item>
			<title><?php echo htmlspecialchars($newsletter_details['subject'], ENT_QUOTES, SENDSTUDIO_CHARSET); ?></title>
			<description><?php echo htmlspecialchars($newsletter_details['subject'], ENT_QUOTES, SENDSTUDIO_CHARSET); ?></description>
			<author><?php echo htmlspecialchars($author, ENT_QUOTES, SENDSTUDIO_CHARSET); ?></author>
			<pubdate><?php echo date('r', $newsletter_details['starttime']); ?></pubdate>
			<subject><?php echo htmlspecialchars($newsletter_details['subject'], ENT_QUOTES, SENDSTUDIO_CHARSET); ?></subject>
			<content><![CDATA[<?php echo $summary;?>]]></content>
			<link><?php echo SENDSTUDIO_APPLICATION_URL . '/display.php?List=' . $newsletter_details['listid'] . '&amp;N=' . $newsletter_details['newsletterid'] . $extra_url; ?></link>
		</item>
		<?php
	}
}
?>
	</channel>
</rss>
