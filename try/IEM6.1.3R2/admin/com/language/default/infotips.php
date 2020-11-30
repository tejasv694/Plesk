<?php
/**
* Language file variables for the info tips.
*
* @see GetLang
*
* @version     $Id: infotips.php,v 1.19 2008/01/25 04:37:59 hendri Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Here are all of the variables for the info tips... Please backup before you start!
*
* In each case, the 'Intro' is the tip that shows up in sendstudio.
* The 'Description' gets shown when a tip is clicked.
*/

/**
* Number of generic info tips we have to choose from.
*/
define('Infotip_Size', 15);

define('LNG_Infotip_Form_Intro', 'Use double-opt-in to reduce spam reports.');

/**
* This gets shown before any info tip.
*/
define('LNG_Infotip_Intro', 'Flipmode\'s Email Marketing Deluxe Tip #');
define('LNG_Infotip_ReadMore', 'Read&nbsp;more...');

define('LNG_Infotip_List_Intro', '15 "Simple Yet Effective" Marketing Tips');

define('LNG_Infotip_1_Intro', 'To avoid having your email marked as spam, keep clear of words such as \'Free\', \'$$$\', \'Save\'  and \'Discount\' in your subject line.');
define('LNG_Infotip_1_Title', 'Avoiding the Spam Filters');


define('LNG_Infotip_2_Intro', 'For maximum click-thru rates when creating HTML emails, make sure your links are blue, underlined and optionally bold.');
define('LNG_Infotip_2_Title', 'Maximizing Click-Thru Rates');


define('LNG_Infotip_3_Intro', 'Using personalization in your emails (such as \'Hi John\' instead of \'Hi there\') will increase your open rate by up to 650%');
define('LNG_Infotip_3_Title', 'The Power of Personalization');

define('LNG_Infotip_4_Intro', 'Always make sure you include an unsubscribe link. You can do this by adding the text %%UNSUBSCRIBELINK%% anywhere in your email.');
define('LNG_Infotip_4_Title', 'One-Click Unsubscription');

define('LNG_Infotip_5_Title', 'Signup Confirmation');

define('LNG_Infotip_6_Title', 'Tuesday / Wednesday = Increased Response');

define('LNG_Infotip_7_Title', 'Repeat Email Communication');

define('LNG_Infotip_8_Intro', 'Keep the theme of your email campaigns consistent. Create a text or HTML template and use that template whenever you create a new email.');
define('LNG_Infotip_8_Title', 'Consistency is the Key');

define('LNG_Infotip_9_Intro', 'For best results when sending recurring email campaigns, always send it on the same day at the same time. For example, every 2nd Wednesday at 3pm.');
define('LNG_Infotip_9_Title', 'On Time, Every Time');

define('LNG_Infotip_10_Intro', 'Make sure your subject line is persuasive and catches your readers attention. Instead of using something like \'OurSite Newsletter Issue #1\', use a benefit, such as \'OurSite Newsletter: 10 Tips for Financial Freedom\'.');
define('LNG_Infotip_10_Title', 'The Half-a-Second Subject Line');

define('LNG_Infotip_11_Intro', 'If running a newsletter, offer your customers a free bonus (such as an eBook or special report) for signing up. Then, create an autoresponder to email them a link to that bonus 1 hour after they subscribe.');
define('LNG_Infotip_11_Title', 'The Free Bonus Hook-In');


define('LNG_Infotip_12_Intro', 'Always have some interesting content at the top of your email, as this is the part that will show in the preview window of your client\'s email program, such as MS Outlook.');
define('LNG_Infotip_12_Title', 'The Preview Pane');

define('LNG_Infotip_13_Intro', 'Try using different wording for links in your marketing emails. Then, click on the stats button above to track which links received the most clicks and use them for future campaigns.');
define('LNG_Infotip_13_Title', 'Link-Click Testing');

define('LNG_Infotip_14_Title', 'Email-Based Learning');

define('LNG_Infotip_15_Title', 'Always Sign on the Dotted Line');
define('LNG_Infotip_15_Intro', 'Always include a signature at the bottom of your emails. You can use your signature to link back to your website, and even to your other products. Here\'s a sample signature: Regards, John Doe. President - Company XYZ. Visit our website at www.companyxyz.com');


/**
* To make context sensitive helptips.
* You can make up your own tips below
* And place them on specific pages by looking at the page & action from the url and placing them in the array.
*
* For example the 'Spam' info tip will be shown when the page is 'Newsletters' and action is 'Create'.
*
* Context sensitive help tips are NOT included in the generic helptips above.
*
* However you can include the generic ones as context sensitive ones if you prefer.
*
* Simply grab the tip 'number' and place it in the appropriate place..
*
* eg to show tip '15' when you are on the 'users' page (regardless of the Action).
* $GLOBALS['ContextSensitiveTips']['users'] = array('15');
*/
define('LNG_Infotip_Cron_Intro', 'Want faster sending?');

define('LNG_Infotip_Spam_Intro', 'Reduce your email being marked as spam.');
define('LNG_Infotip_Spam_Details', 'By testing your email in various email clients, including free accounts such as hotmail, gmail and yahoo you can reduce the chances of your email being marked as spam.');
define('LNG_Infotip_Spam_ReadMore', 'Read&nbsp;Tutorial...');

// The tutorials live in a specific folder - this simply points to an html file.

$GLOBALS['ContextSensitiveTips']['newsletters']['create'] = array('Spam');


define('LNG_Infotip_Autoresponders_Intro', 'How to Setup an Autoresponder.');
define('LNG_Infotip_Autoresponders_ReadMore', 'Read guide...');
$GLOBALS['ContextSensitiveTips']['autoresponders']['create'] = array('Autoresponders');

define('LNG_Infotip_AutoHowto_Intro', 'Auto-Responders - The marketers magic trick.');
define('LNG_Infotip_AutoHowto_Details', 'Sending a series of emails to potential customers automatically is a great way to increase sales and customer loyalty with minimal fuss whether or not you run an online business.');
define('LNG_Infotip_AutoHowto_ReadMore', 'Find out how...');

$GLOBALS['ContextSensitiveTips']['autoresponders']['manage'] = array('AutoHowto');


define('LNG_Infotip_GettingStarted_Intro', 'Getting started.');
define('LNG_Infotip_GettingStarted_ReadMore', 'See the guide...');
define('LNG_Infotip_GettingStarted_ReadMoreLink', '812');
/* #*#*# DISABLED! FLIPMODE! #*#*#   ?????????
	$GLOBALS['ContextSensitiveTips']['index'] = array('GettingStarted');
#*#*# / #*#*# */

define('LNG_Infotip_AddForm_Intro', 'Add a Subscription form to your website.');
define('LNG_Infotip_AddForm_ReadMore', 'Here\'s how...');

$GLOBALS['ContextSensitiveTips']['forms']['manage'] = array('AddForm');

define('LNG_Infotip_CustomFields_ReadMore', 'Here\'s how...');

$GLOBALS['ContextSensitiveTips']['customfields']['manage'] = array('CustomFields');

/**
**************************
* Changed/added in NX 1.3
**************************
*/
$GLOBALS['ContextSensitiveTips']['customfields']['create'] = array('CustomFields');
$GLOBALS['ContextSensitiveTips']['subscribers']['add'] = array('CustomFields');


$GLOBALS['Did_You_Know_Tips'] = array();
$GLOBALS['Did_You_Know_Tips'][] = ' You can place your mouse over the help icon <img align="top" src="images/help2.gif"> to get more information about a particular option.';

$GLOBALS['Did_You_Know_Tips'][] = ' Email marketing has one of the highest returns on investment of any other marketing medium.';


$GLOBALS['Did_You_Know_Tips'][] = ' You can address your readers by their first name using custom fields?';

$GLOBALS['Did_You_Know_Tips'][] = ' Educating your readers helps build trust, credibility and a desire to read your emails so when you\'re ready to promote, they\'re ready to listen.';

$GLOBALS['Did_You_Know_Tips'][] = ' A good email provides for good information, but a great email has personality. Don\'t be afraid to inject some of your personality into your email campaigns.';

$GLOBALS['Did_You_Know_Tips'][] = ' You can schedule emails to be sent out at later dates. You can create 12 emails now and schedule them to be sent out every month of the year automatically.';

$GLOBALS['Did_You_Know_Tips'][] = ' You can send emails to specific groups of people such as those that haven\'t clicked a link in your email campaign.';

$GLOBALS['Did_You_Know_Tips'][] = ' You can view who opened your email campaigns in the statistics section.';

$GLOBALS['Did_You_Know_Tips'][] = ' You can see who clicked on a particular link in your email campaigns in the statistics section.';

$GLOBALS['Did_You_Know_Tips'][] = ' If you have a store or shop front, you can ask customers to give you their email address by filling out a printed subscription form.';

$GLOBALS['Did_You_Know_Tips'][] = ' If you want to see what your emails look like in different email programs you can do so by selecting the \'View your email in different email programs\' button when creating your email campaigns.';

$GLOBALS['Did_You_Know_Tips'][] = ' If your attachments are very large they can slow down your sending rate. An alternative to this is that you store your attachments on your server and link to them from your email campaign.';

$GLOBALS['Did_You_Know_Tips'][] = ' If your web hosting provider has placed email sending limits on your server you can slow down how fast you send your emails by setting the \'Max per hour\' limit in the Settings page.';

$GLOBALS['Did_You_Know_Tips'][] = ' To help keep your email campaigns and autoresponders consistent you can create a template from which to base all your new emails. This way you can create it once and then simply fill it out with new information.';

$GLOBALS['Did_You_Know_Tips'][] = ' You can create Subscribe, Unsubscribe, Send to a Friend and Modify Details forms in a simple step by step process by clicking on the Website Forms link';

$GLOBALS['Did_You_Know_Tips'][] = ' Making use of your statistics can help you see what subject lines and email campaigns were more effective.';

$GLOBALS['Did_You_Know_Tips'][] = ' Effective email campaigns are planned beforehand. Know your target audience and find out what they want before you email them.';

$GLOBALS['Did_You_Know_Tips'][] = ' Having your complete contact information inside each email can help potential customers reach you quickly.';

$GLOBALS['Did_You_Know_Tips'][] = ' You should promote your email program at every customer touchpoint: on your website, inside confirmation emails, on your customer service desk and in retail stores.';


/**
**************************
* Changed/added in NX 1.4
**************************
*/
$GLOBALS['Did_You_Know_Tips'][] = ' You can preview your email campaign in multiple different email clients. Just click the button when creating an email campaign.';

/**
**************************
* Changed/added in 5.0.0
**************************
*/


define('LNG_Infotip_6_Intro', 'The best days to send a marketing or sales email to your contacts has been proven to be Tuesday and Wednesday.');

define('LNG_Infotip_7_Intro', 'Why not setup an autoresponder to send to your contacts 1 hour after they signup. You can use it to tell them more about your company, products or services.');


define('LNG_Infotip_14_Intro', 'Setup an email-based course for your contacts. To do this, simply create a series of autoresponders (for example, 5) containing unique content. Then, schedule the first one to go out after 24 hours, the second after 48 hours, etc.');

define('LNG_Infotip_Autoresponders_Details', 'This guide will help get you started setting up your first autoresponder so you can email your contacts automatically helping turn leads into customers.');

define('LNG_Infotip_GettingStarted_Details', 'Creating and sending your first email campaign is easy. Start by creating a contact list, custom fields, a subscription form and content for your email campaign. Finally, send your campaign to your contacts.');

define('LNG_Infotip_CustomFields_Details', 'To collect more than just an email address from your contacts you need to create and add custom fields to your subscription form. Collect simple information such as their name, or even advanced information such as their location or favorite color.');

define('LNG_Infotip_CustomFields_Intro', 'Learn how to collect your contacts name, age, sex, etc using custom fields.');

$GLOBALS['Did_You_Know_Tips'][] = ' You can setup autoresponders to automatically email your contacts at pre-defined intervals. It\'s like email marketing on steroids.';

$GLOBALS['Did_You_Know_Tips'][] = ' It\'s best to send your email campaigns at regular intervals like once per week, once per month so your contacts get used to receiving them.';

$GLOBALS['Did_You_Know_Tips'][] = ' You can collect as much or as little as you like about your contacts by using custom fields.';

$GLOBALS['Did_You_Know_Tips'][] = ' You can send a reminder email to contacts that haven\'t yet confirmed their subscription.';

$GLOBALS['Did_You_Know_Tips'][] = ' If your contacts add your email address to their address book your emails are more likely to make it to their inbox.';

$GLOBALS['Did_You_Know_Tips'][] = ' The subject line of your email campaign is the first thing your contacts will see. Make sure that it grabs their attention to make sure they read your email.';

$GLOBALS['Did_You_Know_Tips'][] = ' You should make use of the top of your email campaigns as many contacts will see this in a preview pane and will decide to look further depending on what they see here.';

$GLOBALS['Did_You_Know_Tips'][] = ' You can improve the number of contacts to your list by promoting the benefits beforehand.';

$GLOBALS['Did_You_Know_Tips'][] = ' A good list of contacts is like having cash in your bank. Every email campaign can generate solid leads and revenue.';

$GLOBALS['Did_You_Know_Tips'][] = ' Limiting the amount of personal information you ask for in subscription forms makes subscribing quicker which will increase the number of contacts you receive.';

define('LNG_Infotip_Form_Details', 'A confirmation email (double opt-in) verifies that the owner of the email address is also the person who signed up to your contact list. This can reduce your emails from being marked as spam by unintending recipients.');

define('LNG_Infotip_5_Intro', 'To reduce the number of bogus email addresses in your contact list, always use a double opt-in subscription system.');

define('LNG_Infotip_AddForm_Details', 'To collect leads from your website, you should add a subscription form to your website so your website visitors can subscribe to your contact list to receive more information from you.');

$GLOBALS['Did_You_Know_Tips'][] = ' If you save your bounce account details with your contact lists you can start to process your bounced emails automatically. This will keep your contact lists cleaner.';


define('LNG_Infotip_Cron_Details', 'Did you know that enabling cron (see <a href="#" onClick="LaunchHelp(\'%%WHITELABEL_INFOTIPS%%\',\'819\'); return false;">documentation</a> or contact your administrator) will allow you to schedule email campaigns to be sent at a future date, as well as sending emails much faster? It will also stop you from having to keep the popup window open.');

define('LNG_Infotip_Spam_ReadMoreLink', '802');
define('LNG_Infotip_Autoresponders_ReadMoreLink', '815');
define('LNG_Infotip_AutoHowto_ReadMoreLink', '797');
define('LNG_Infotip_AddForm_ReadMoreLink', '813');
define('LNG_Infotip_CustomFields_ReadMoreLink', '814');


define('LNG_Infotip_1_ReadMoreLink', '802');
define('LNG_Infotip_2_ReadMoreLink', '820');
define('LNG_Infotip_3_ReadMoreLink', '814');
define('LNG_Infotip_4_ReadMoreLink', '821');
define('LNG_Infotip_5_ReadMoreLink', '822');
define('LNG_Infotip_6_ReadMoreLink', '823');
define('LNG_Infotip_7_ReadMoreLink', '815');
define('LNG_Infotip_8_ReadMoreLink', '824');
define('LNG_Infotip_9_ReadMoreLink', '825');
define('LNG_Infotip_10_ReadMoreLink', '800');
define('LNG_Infotip_11_ReadMoreLink', '826');
define('LNG_Infotip_12_ReadMoreLink', '827');
define('LNG_Infotip_13_ReadMoreLink', '829');
define('LNG_Infotip_14_ReadMoreLink', '828');
define('LNG_Infotip_15_ReadMoreLink', '830');
