<?php

/**
* These are the bounce rules we maintain for processing bounces.
*
* ORDER OF THESE RULES DOES MATTER.
*
* They will be processed in the order they appear in this file.
*
* Do NOT change the rule type.
* Those types are used for statistics so you can see what type of bounces you are getting.
*
* The valid rule types are:
* - emaildoesntexist (eg "user doesn't exist" or "account doesn't exist" or "invalid user")
* - domaindoesntexist (eg "unknown domain name")
* - invalidemail
* - overquota
* - relayerror
* - inactive
*
*
*******************************************************************
*         HOW TO ADD A NEW RULE TO YOUR BOUNCE PROCESSING         *
*******************************************************************
*
* To add a new rule, find a consistent message in the bounced email - for example:
* "User Does Not Exist"
* do NOT include ip addresses or server names in the rule because if you get a similar bounce from another server, it will not pick up properly.
*
* Then find the section you want to add it to, for example:
* $GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist']
* Copy a line like the one you want to modify, and change the rule:
*
* $GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'your new rule goes here';
*
* Please add new rules to the user_bounce_rules.php file between the applicable lines
* so your new rules don't get overwritten with the next sendstudio update.
*/

/*
* Set up the arrays. Don't need to do anything else here.
*/
$GLOBALS['BOUNCE_RULES'] = array(
	'soft' => array(
		'inactive' => array(),
		'overquota' => array(),
		'blockedcontent' => array()
	),
	'hard' => array(
		// we distinguish between the types so we can see the differences in the statistics area.
		'emaildoesntexist' => array(),
		'domaindoesntexist' => array(),
		'relayerror' => array(),
		'invalidemail' => array(),
		'inactive' => array(),
	),
	'delete' => array(
		'delete' => array(),
	),
);

/**
 * These are the string that needed to be in the email subject for the bounce processing
 * will even think that the email is a bounce notification.
 *
 * NOTE: If the subject is empty, SS will assume that it is a bounce notification
 */
$GLOBALS['BOUNCE_RULES_SUBJECT'] = array(
	'Mail delivery failed',
	'Delivery Notification: Delivery has failed',
	'Non delivery report',
	'Returned mail',
	'Mail System Error - Returned Mail',
	'Undeliverable message',
	'Delivery Status Notification',
	'Nondeliverable mail',
	'Warning: could not send message',
	'Undeliverable Mail',
	'Undeliverable:',
	'Undelivered Mail Returned to Sender',
	'Failure Notice',
	'Delivery Failure',
	'Message status - undeliverable',
	'Mail could not be delivered',
	'Delivery unsuccessful',
	'Mail Delivery Problem',
	'delayed 24 hours',
	'delayed 48 hours',
	'delayed 72 hours',
	'delivery report',
	'failure delivery',
	'delivery notification',
	'mail status report',
	'error delivering mail',
	'mail not delivered',
	'**Message you sent blocked by our bulk email filter**',
	'Marked As Spam:',
	'delivery_failure',
	'mail delivery error',
	'Expired Delivery Retry Notification',
	'Delivery is delayed'
);

$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = ' is FULL';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Quota exceeded';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'user is over quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'exceeds size limit';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'user has full mailbox';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Mailbox disk quota exceeded';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'over the allowed quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'User mailbox exceeds allowed size';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'does not have enough space';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'mailbox is full';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Can\'t create output';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'mailbox full';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'File too large';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'too many messages on this mailbox';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'too many messages in this mailbox';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Not enough storage space';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Over quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'over the maximum allowed number of messages';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Recipient exceeded email quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'The user has not enough diskspace available';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Mailbox has exceeded the limit';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'exceeded storage allocation';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Quota violation';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = '522_mailbox_full';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'account is full';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'incoming mailbox for user ';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'message would exceed quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'recipient exceeded dropfile size quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'not able to receive any more mail';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'user is invited to retry';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'User account is overquota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'mailfolder is full';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'exceeds allowed message count';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'message is larger than the space available';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'recipient storage full';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'mailbox is full';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Mailbox has exceeded the limit';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'The user\'s space has used up.';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'user is over their quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'exceed the quota for the mailbox';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'exceed maximum allowed storage';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Inbox is full';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'over quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'maildir has overdrawn his diskspace quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'disk full';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Quota exceed';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Storage quota reached';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'user overdrawn his diskspace quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'exceeded his/her quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'quota for the mailbox';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'The incoming mailbox for user';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'exceeded the space quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'mail box space not enough';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'insufficient disk space';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'over their disk quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Message would exceed ';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'User is overquota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Requested mailbox exceeds quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'exceed mailbox quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'over the storage quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'over disk quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'mailbox_quota_exceeded';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Status: 5.2.2';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'over the maximum allowed mailbox size';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'Delivery failed: Over quota';
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'exceed the quota for the mailbox';

// look up 'perror 28'
$GLOBALS['BOUNCE_RULES']['soft']['overquota'][] = 'errno=28';


$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Your e-mail was rejected for policy reasons on this gateway';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '550 Protocol violation';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Blacklisted';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'is refused. See http://spamblock.outblaze.com';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '550 Rule imposed mailbox access for';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Message cannot be accepted, content filter rejection';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Mail appears to be unsolicited';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'rejected for policy reasons';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Spam rejected';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Error: content rejected';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Message Denied: Restricted attachment';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Denied by policy';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'has exceeded maximum attachment count limit';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Blocked for spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Blocked for abuse';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Message held for human verification';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'considered unsolicited bulk e-mail';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'message held before permitting delivery';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'envelope sender is in my badmailfrom';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'listed in multi.surbl.org';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'black listed url host';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'this message scored ';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'on spam scale';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'message filtered';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'rejected as bulk';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'message content rejected';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Mail From IP Banned';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Connection refused due to abuse';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'mail server is currently blocked';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Spam origin';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'extremely high on spam scale';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'is not accepting mail from this sender';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'spamblock';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'blocked using ';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'HTML tag unacceptable';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'appears to be spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'not accepting mail with attachments or embedded images';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'message contains potential spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'You have been blocked by the recipient';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'message looks like spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'message looks like a spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Message contains unacceptable attachment';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'high spam probability';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'email is considered spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Spam detected';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Message identified as SPAM';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'blocked because it contains FortiGuard - AntiSpam blocking URL';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = ' This message has been blocked because it contains FortiSpamshield blocking URL';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Sender is on domain\'s blacklist';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'This message does not comply with required standards';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Message rejected because of unacceptable content';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '554 Transaction failed';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '5.7.1 reject content';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '5.7.1 URL/Phone Number Filter';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '5.7.1 Message cannot be accepted, spam rejection';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Mail contained a URL rejected by SURBL';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'This message has been flagged as spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'they are not accepting mail';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'not accepting mail with attachments or embedded images';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '550 POSSIBLE SPAM';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'headers consistent with spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '5.7.1 Content-Policy reject';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'rejected by an anti-spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'rejected by anti-spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'is on RBL list';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'sender denied';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Your message was rejected because it appears to be part of a spam bomb';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'it is spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '5.7.1 bulkmail';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Message detected as spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '5.7.1 Blocked';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'identified SPAM';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Error: SPAM';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'message is banned';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'junk mail';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'bulk mail rejected';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'SPAM not accepted';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'rejected By DCC';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Spam Detector';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '5.7.1 Message rejected';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '5.7.1 Rejected as SPAM';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Message rejected due to the attachment filtering policy';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Message rejected due to content restrictions';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Spam is not allowed';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Blocked by policy';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'content filter';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'spam filter';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'filter rejection';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'rejected by spam-filter';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Forbidden for policy reasons';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'looked like SPAM';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Message blocked';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'not delivered for policy reasons';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'high on spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '5.7.1 Rejected - listed at ';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'invalid message content';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '550 This message scored ';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Blocked by SPAM';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'This message has been blocked';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'SURBL filtered by ';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'message classified as bulk';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '554 Message rejected';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'mail rejected for spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '554 5.7.1 ';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'message that you send was considered spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'message that you sent was considered spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '554 5.7.0 Reject';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '550 Spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Message rejected';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '550 Rejected';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Message rejected: Conversion failure';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Sorry, message looks lik';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'email has been identified as SPAM';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'possible spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = '550 Content Rejected';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Message not allowed by spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'has been quarantined';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'blocked as spam';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'a stray CR character';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'no longer accepts messages with';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'DNSBL:To request removal of';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'won\'t accept this email';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Rejected by filter processing';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'marked by Telerama as SPAM';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'triggered a spam block';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Message classified as spam by Bogofilter';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'http://postmaster.info.aol.com/errors/421dynt1.html';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Spam limit has been reached';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'One of the words in the message is blocked';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Your email has been automatically rejected';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'message from policy patrol email filtering';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'blocked by filter rules';
$GLOBALS['BOUNCE_RULES']['soft']['blockedcontent'][] = 'Mail rejected by Windows Live Hotmail for policy reasons';


$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = '542 Rejected';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Remote sending only allowed with authentication';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = '550 authentication required';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'sorry, that domain isn\'t in my list of allowed rcpthosts';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'sorry, that domain isn\'t in my list of allowed rcpthosts';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'has installed an invalid MX record with an IP address instead of a domain name on the right hand side.';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'all relevant MX records point to non-existent hosts';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'not capable to receive mail';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'CNAME lookup failed temporarily';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'TLS connect failed: timed out';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'timed out while receiving the initial server greeting';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'malformed or unexpected name server reply';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'temporarily deferred';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'unreachable for too long';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Please receive your mail before sending';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = ' but connection died';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Failed; 4.4.7 (delivery time expired)';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'unable to connect successfully to the destination mail server';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'This message is looping';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Connection timed out';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'failed on DATA command';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Can\'t open mailbox';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Delivery failed 1 attempt';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Hop count exceeded';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Command rejected';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Unable to create a dot-lock';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Command died with status';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = '550 System error';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Connection refused';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Command time limit exceeded';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Resources temporarily unavailable';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'error on maildir delivery';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'this message has been in the queue too long';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'loops back to myself';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'temporary failure';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'temporary problem';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Temporary error on maildir delivery';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'The host does not have any mail exchanger';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = '5.7.1 Transaction failed';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'delivery temporarily suspended';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Undeliverable message';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'user path no exist';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'user path does not exist';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'maildir delivery failed';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Resources temporarily not available';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'has exceeded the max emails per hour';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'several matches found in domino';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'internal software error';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'internal server error';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'cannot store document';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'delivery time expired';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'delivery expired (message too old)';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'operation timed out';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = '4.3.2 service shutting down';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'loop count exceeded';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'unable to deliver a message to';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'delivery was refused';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Too many results returned';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'your "received:" header counts';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Error in processing';
$GLOBALS['BOUNCE_RULES']['soft']['remoteconfigerror'][] = 'Error opening input/output file';

// Hotmail send this back for the following reason:
// Reasons for rejection may be related to content with spam-like characteristics or IP/domain reputation problems.
$GLOBALS['BOUNCE_RULES']['soft']['localconfigerror'][] = 'SC-001 Mail rejected by Windows Live Hotmail for policy reasons.';

$GLOBALS['BOUNCE_RULES']['soft']['localconfigerror'][] = 'Remote host said: 542 Rejected';
$GLOBALS['BOUNCE_RULES']['soft']['localconfigerror'][] = 'Remote host said: 554 Failure';
$GLOBALS['BOUNCE_RULES']['soft']['localconfigerror'][] = 'Could not complete sender verify callout';
$GLOBALS['BOUNCE_RULES']['soft']['localconfigerror'][] = 'Sender verification error';
$GLOBALS['BOUNCE_RULES']['soft']['localconfigerror'][] = 'Mail only accepted from IPs with valid reverse lookups';
$GLOBALS['BOUNCE_RULES']['soft']['localconfigerror'][] = 'lost connection with';
$GLOBALS['BOUNCE_RULES']['soft']['localconfigerror'][] = 'sender id (pra) not permitted';
$GLOBALS['BOUNCE_RULES']['soft']['localconfigerror'][] = 'could indicate a mail loop';
$GLOBALS['BOUNCE_RULES']['soft']['localconfigerror'][] = 'but sender was rejected';
$GLOBALS['BOUNCE_RULES']['soft']['localconfigerror'][] = 'Address does not pass the Sender Policy Framework';
$GLOBALS['BOUNCE_RULES']['soft']['localconfigerror'][] = 'only accepts mail from known senders';
$GLOBALS['BOUNCE_RULES']['soft']['localconfigerror'][] = 'Name service error';

// iiNet started to require sender SMTP to have valid PTR record
$GLOBALS['BOUNCE_RULES']['soft']['localcondiferror'][] = 'You will need to add a PTR record (also known as reverse lookup) before you are able to send email into the iiNet network.';
$GLOBALS['BOUNCE_RULES']['soft']['localcondiferror'][] = 'does not have a valid PTR record associated with it.';


$GLOBALS['BOUNCE_RULES']['soft']['inactive'][] = 'refused to talk to me: 452 try later';


$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'user account disabled';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'this account has been disabled or discontinued';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'user account is expired';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'User is inactive';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'inactive user';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'extended inactivity new mail is not currently being accepted';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'Sorry, I wasn\'t able to establish an SMTP connection';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'message refused';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'permission denied';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'user mailbox is inactive';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'mailbox temporarily disabled';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'Blocked address';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'Account inactive as unread';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'Account inactive';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'account expired';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'User hasn\'t entered during last ';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'Account closed due to inactivity';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'This account is not allowed';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'Mailbox_currently_suspended';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'Mailbox disabled';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'Mailaddress is administratively disabled';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'Mailbox currently suspended';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'Account has been suspended';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'account is not active';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'recipient never logged onto';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = ' is disabled';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'account has been temporarily suspended';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'deactivated mailbox';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'disabled due to inactivity';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'not an active address';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'inactive on this domain';

/**
* according to the rfc:
* http://tools.ietf.org/html/rfc3463
* 5.2.1 is 'mailbox inactive'.
*/
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'Status: 5.2.1';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'said: 550 5.2.1';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'account is locked';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'account deactivated';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'disabled mailbox';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'user mailbox is inactive';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'Mailaddress is administrativley disabled';
$GLOBALS['BOUNCE_RULES']['hard']['inactive'][] = 'unavailable to take delivery of the message';


$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = '550 5.1.1 User unknown';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'invalid mailbox';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'mailbox unavailable';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'invalid address';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'this user doesn\'t have a yahoo.com account';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'permanent fatal errors';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'No mailbox here by that name';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'User not known';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Remote host said: 553';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'said: 553 sorry,';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'No such user';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'No such recipient';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'unknown user';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'mailbox not found';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'No such user here';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Delivery to the following recipients failed';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'unknown or illegal alias';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'not listed in domino directory';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'unrouteable address';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Destination server rejected recipients';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'unable to validate recipient';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'No such virtual user here';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'The recipient cannot be verified';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'bad address ';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Recipient unknown';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'mailbox is currently unavailable';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Invalid User';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'recipient rejected';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'invalid recipient';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'not our customer';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Unknown account ';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'This user doesn\'t have a ';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'no users here by that name';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'account closed';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'user not found';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'This address no longer accepts mail';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'does not like recipient';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Delivery to the following recipient failed permanently';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'User Does Not Exist';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'The mailbox is not available on this system';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = ' does not exist';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'not a valid mailbox';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'server doesn\'t handle mail for that user';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'No such account ';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'unknown recipient';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'user invalid';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'User reject the mail';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'The following recipients are unknown';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'User unknown in virtual mailbox';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'User unknown in virtual alias table';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'User is unknown';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'user unknown';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Unrouteable address';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'mailbox unavailable';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'This address does not receive mail';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Recipient no longer on server';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'retry timeout exceeded';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'retry time not reached for any host after a long failure period';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'unknown address or alias';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = '> does not exist';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Recipient address rejected';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Recipient not allowed';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Address rejected';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Address invalid';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Unknown local part';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Unknown local-part';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'mail receiving disabled';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'bad destination email address';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'deactivated due to abuse';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'no such address';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'user_unknown';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'recipient not found';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'User unknown in local recipient table';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'This recipient e-mail address was not found';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'no valid recipients';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'This user doesn\'t have a yahoo';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'mailbox not available';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'not a valid user';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Unknown destination address';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Unknown address error';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'recipient\'s account is disabled';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Unable to chdir to maildir';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'undeliverable to the following';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'invalid domain mailbox user';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Permanent error in automatic homedir creation';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Invalid or unknown virtual user';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Your e-mail has not been delivered';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Your email has not been delivered';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Your mail has not been delivered';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Not a valid recipient';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Please check the recipients e-mail address';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'email has changed';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'This address is no longer valid';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'unknown email address';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'no longer in use';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'not have a final email delivery point';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'non esiste';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'no recipients';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'permanent fatal delivery';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'address is not valid';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'unavailable mailbox';

/**
* according to the rfc:
* http://tools.ietf.org/html/rfc3463
* 5.1.1 is always 'email doesn't exist'.
*/
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = '550 5.1.1';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Status: 5.1.1';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'account does not exist';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'The recipient name is not recognized';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'can\'t create user output file';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'no such user here';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'There is no user by that name';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'No such mailbox';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'not a recognised email account';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'address is no longer active';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'This is a permanent error. The following address';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Unable to find alias user';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'sorry, no mailbox';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'doesn\'t have an account';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'not a valid email account';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'I have now left ';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'I am no longer with';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Invalid final delivery user';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'no longer available';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'unknown address';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'isn\'t in my list of allowed recipients';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'recipients are invalid';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'recipient is invalid';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'mailbox is not valid';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'invalid e-mail address';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'doesn\'t_have_a_yahoo';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'not known at this site';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'email name is not found';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'address doesn\'t exist';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'destination addresses were unknown';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'no existe';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'does not have an email';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = '_does_not_exist_here';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'User unknown in virtual mailbox table';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'user is no longer available';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'unknown user account';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'Addressee unknown';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = 'This Gmail user does not exist';
$GLOBALS['BOUNCE_RULES']['hard']['emaildoesntexist'][] = '554 delivery error: This user doesn\'t have';

$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'name or service not known';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'I couldn\'t find any host named';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'message could not be delivered for \d+ days';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'I couldn\'t find a mail exchanger or IP address';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'address does not exist';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'No such domain at this location';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'an MX or SRV record indicated no SMTP service';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'I couldn\'t find any host by that name';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'Domain does not exist; please check your spelling';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'Domain not used for mail';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'Domain must resolve';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'unrouteable mail domain';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'no route to host';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'host not found';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'Host or domain name not found';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'illegal host/domain';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'bad destination host';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'no matches to nameserver query';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'no such domain';
$GLOBALS['BOUNCE_RULES']['hard']['domaindoesntexist'][] = 'Cannot resolve the IP address of the following domain';


$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'relaying denied';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'access denied';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = '554 denied';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'they are not accepting mail from';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'Relaying not allowed';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'not permitted to relay through this server';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'Sender verify failed';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'Although I\'m listed as a best-preference MX or A for that host';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'mail server permanently rejected message';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'too many hops, this message is looping';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'loop: too many hops';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'relay not permitted';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'This mail server requires authentication when attempting to send to a non-local e-mail address.';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'is currently not permitted to relay';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'Unable to relay for';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'not a gateway';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'This system is not configured to relay mail';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'we do not relay';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'relaying mail to';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'Relaying is prohibited';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'Cannot relay';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'relaying disallowed';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'Authentication required for relay';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = '5.7.1 Unable to deliver to ';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'message could not be delivered';
$GLOBALS['BOUNCE_RULES']['hard']['relayerror'][] = 'dns loop';


$GLOBALS['BOUNCE_RULES']['hard']['invalidemail'][] = 'bad address syntax';
$GLOBALS['BOUNCE_RULES']['hard']['invalidemail'][] = 'domain missing or malformed';
$GLOBALS['BOUNCE_RULES']['hard']['invalidemail'][] = '550_Invalid_recipient';
$GLOBALS['BOUNCE_RULES']['hard']['invalidemail'][] = 'Invalid Address';
$GLOBALS['BOUNCE_RULES']['hard']['invalidemail'][] = 'not our customer';
$GLOBALS['BOUNCE_RULES']['hard']['invalidemail'][] = 'Bad destination mailbox address';


/**
* Be extra careful with the 'delete' type. This should only include 100% safe to delete messages.
* For example, distinct out of office replies
* or 'unable to send email for 4 hours' type messages.
*/
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'This is an automatically generated Delivery Status Notification. THIS IS A WARNING MESSAGE ONLY. YOU DO NOT NEED TO RESEND YOUR MESSAGE.';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'This is a warning message only';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I am on vacation or otherwise unable to read my email';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I am unavailable to read your message at this time.';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I am away until ';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'when I return to the office';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I am out of town';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I will be on vacation ';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I shall be out of office ';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I will be away ';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I am away from ';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I will be out of the office ';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I am on sabbatical';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'will not be responding promptly';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I am out of the country';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I am currently out of office';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I am currently out of the office';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I am on vacation ';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'on personal leave';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'unavailable to read your message';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'away from the office';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'YOU DO NOT NEED TO RESEND YOUR MESSAGE';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I am unavailable to read your message';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you for recent email';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you for your email';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'This is an auto respond';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thanks for contacting ';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you for contacting ';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you for your e-mail';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'out of the office';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'This is an autoreply';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Your message has been received';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'has received your email';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'have received your email';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'have received your mail';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'have received your message';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thanks for contacting me';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I will be out on vacation';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you For mailing';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I\'m on vacation';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I have got your mail';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I am currently away';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thanks for the mail';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thanks for inquiring';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you for e mail';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Your message has been received';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you for contacting';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you for taking time to contact';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'automatic response';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I will read your message';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'read your message as soon';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I will be on leave';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'get back to you as soon';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'very much for your inquiry';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'for your inquiry';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you for your mail';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thanks for your mail';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you for your message';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'thank you for e-mail';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'will be absent from';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'not available right now';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'This is an automated reply';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I will reply shortly';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'out of office';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'This is an autoresponder';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thanks for your email';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'currently on holiday';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'on maternity leave';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'We have received your e-mail';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'We have received your mail';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'will try to reply';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'is on holiday';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'currently on vacation';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you for your recent e-mail';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you for your recent email';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you for your recent mail';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I got your email';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you for your communiqu';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'respond to your email as soon as possible';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'away right now';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'will get back to you soon';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thanks for writing';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'reply as soon as possible';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'reply you soon';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'AutoReply';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Auto Response';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thank you';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Thanks for writing';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'I will be away';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'On Vacation';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'Out of Office';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'away from e-mail';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'thank you for your enquiry';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'thanks for the email';

// 4 hour delay messages
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'after more than 72 hours';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'after more than 48 hours';
$GLOBALS['BOUNCE_RULES']['delete']['delete'][] = 'after more than 24 hours';

$user_rules = dirname(__FILE__) . '/user_bounce_rules.php';
if (is_file($user_rules)) {
	require($user_rules);
}

?>
