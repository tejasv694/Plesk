<?php
/**
* Schema for postgresql databases.
*
* @version     $Id: schema.pgsql.php,v 1.47 2008/02/25 06:21:20 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Schema for postgresql databases.
* DO NOT CHANGE BELOW THIS LINE.
*/

$queries = array();

$queries[] = "CREATE TABLE %%TABLEPREFIX%%usergroups (
    groupid                 SERIAL          NOT NULL,
    groupname               VARCHAR(255)    NOT NULL,
    createdate              INT             NOT NULL,

    limit_list              INT             DEFAULT 0,
    limit_hourlyemailsrate  INT             DEFAULT 0,
    limit_emailspermonth    INT             DEFAULT 0,
    limit_totalemailslimit  INT             DEFAULT NULL,

    forcedoubleoptin        CHAR(1)         DEFAULT '0',
    forcespamcheck          CHAR(1)         DEFAULT '0',

    systemadmin             CHAR(1)         DEFAULT '0',
    listadmin               CHAR(1)         DEFAULT '0',
    segmentadmin            CHAR(1)         DEFAULT '0',
    templateadmin           CHAR(1)         DEFAULT '0',

    PRIMARY KEY (groupid)
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%usergroups_permissions (
    groupid         INT             NOT NULL,
    area            VARCHAR(255)    NOT NULL,
    subarea         VARCHAR(255)    DEFAULT NULL,
    FOREIGN KEY (groupid) REFERENCES %%TABLEPREFIX%%usergroups(groupid) ON DELETE CASCADE
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%usergroups_access (
    groupid         INT             NOT NULL,
    resourcetype    VARCHAR(100)    NOT NULL,
    resourceid      INT             NOT NULL,

    FOREIGN KEY (groupid) REFERENCES %%TABLEPREFIX%%usergroups(groupid)
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%users (
  userid						SERIAL			NOT NULL,
  groupid						INT				NOT NULL,
  trialuser						CHAR(1)			DEFAULT '0',
  username						VARCHAR(255)	NOT NULL,
  unique_token					VARCHAR(128)	DEFAULT NULL,
  password						VARCHAR(32)		DEFAULT NULL,
  admintype						CHAR(1)			DEFAULT NULL,
  listadmintype					CHAR(1)			DEFAULT NULL,
  templateadmintype				CHAR(1)			DEFAULT NULL,
  segmentadmintype				CHAR(1)			DEFAULT NULL,
  status						CHAR(1)			DEFAULT '0',
  fullname						VARCHAR(255)	DEFAULT NULL,
  emailaddress					VARCHAR(100)	DEFAULT NULL,
  settings						TEXT			DEFAULT NULL,
  editownsettings				CHAR(1)			DEFAULT '0',
  usertimezone					VARCHAR(10)		DEFAULT NULL,
  textfooter					TEXT			DEFAULT NULL,
  htmlfooter					TEXT			DEFAULT NULL,
  infotips						CHAR(1)			DEFAULT '1',
  smtpserver					VARCHAR(255)	DEFAULT NULL,
  smtpusername					VARCHAR(255)	DEFAULT NULL,
  smtppassword					VARCHAR(255)	DEFAULT NULL,
  smtpport						INT				DEFAULT 0,
  createdate					INT				DEFAULT 0,
  lastloggedin					INT				DEFAULT 0,
  forgotpasscode				CHAR(32)		DEFAULT '',
  usewysiwyg					CHAR(1)			DEFAULT '1',
  enableactivitylog				CHAR(1)			DEFAULT '0',
  xmlapi						CHAR(1)			DEFAULT '0',
  xmltoken						CHAR(40)		DEFAULT NULL,
  gettingstarted				INT				NOT NULL DEFAULT 0,
  googlecalendarusername		VARCHAR(255)	DEFAULT NULL,
  googlecalendarpassword		VARCHAR(255)	DEFAULT NULL,
  user_language					VARCHAR(25)		NOT NULL DEFAULT 'default',
  eventactivitytype				TEXT			DEFAULT NULL,
  credit_warning_time			INT				DEFAULT NULL,	-- The last time credit_warning was sent out to this user
  credit_warning_percentage		INT				DEFAULT NULL,	-- At which percentage
  credit_warning_fixed			INT				DEFAULT NULL,	-- or at which credit level
  adminnotify_email				VARCHAR(100)	DEFAULT NULL,
  adminnotify_send_flag			CHAR(1)			DEFAULT '0',
  adminnotify_send_threshold	INT				DEFAULT NULL,
  adminnotify_send_emailtext	TEXT			DEFAULT NULL,
  adminnotify_import_flag		CHAR(1)			DEFAULT '0',
  adminnotify_import_threshold	INT				DEFAULT NULL,
  adminnotify_import_emailtext	TEXT			DEFAULT NULL,

  PRIMARY KEY (userid),
  FOREIGN KEY (groupid) REFERENCES %%TABLEPREFIX%%usergroups(groupid) ON DELETE RESTRICT
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%autoresponders_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%autoresponders (
  autoresponderid int DEFAULT nextval('%%TABLEPREFIX%%autoresponders_sequence') NOT NULL PRIMARY KEY,
  name varchar default NULL,
  subject varchar default NULL,
  format char(1) default NULL,
  textbody text,
  htmlbody text,
  createdate int default NULL,
  active int default 0,
  pause int default 0,
  hoursaftersubscription int default NULL,
  ownerid int NOT NULL default '0',
  searchcriteria text,
  listid int default NULL,
  tracklinks char(1) default NULL,
  trackopens char(1) default NULL,
  multipart char(1) default NULL,
  queueid int default NULL,
  sendfromname varchar default NULL,
  sendfromemail varchar default NULL,
  replytoemail varchar default NULL,
  bounceemail varchar default NULL,
  charset varchar default NULL,
  embedimages char(1) default '0',
  to_firstname int default 0,
  to_lastname int default 0,
  autorespondersize int default 0
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%banned_emails_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%banned_emails (
  banid int DEFAULT nextval('%%TABLEPREFIX%%banned_emails_sequence') NOT NULL PRIMARY KEY,
  emailaddress varchar default NULL,
  list varchar(10) default NULL,
  bandate int default NULL
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%customfield_lists_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%customfield_lists (
  cflid int DEFAULT nextval('%%TABLEPREFIX%%customfield_lists_sequence') NOT NULL PRIMARY KEY,
  fieldid int NOT NULL default '0',
  listid int NOT NULL default '0'
)";


$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%customfields_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%customfields (
  fieldid int DEFAULT nextval('%%TABLEPREFIX%%customfields_sequence') NOT NULL PRIMARY KEY,
  name varchar default NULL,
  fieldtype varchar(100) default NULL,
  defaultvalue varchar default NULL,
  required char(1) default NULL,
  fieldsettings text,
  createdate int default NULL,
  ownerid int default NULL,
  isglobal char(1) default '0'
)";


$queries[] = "CREATE TABLE %%TABLEPREFIX%%form_customfields (
  formid int default NULL,
  fieldid varchar(10) default NULL,
  fieldorder int default 0
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%form_lists (
  formid int default NULL,
  listid int default NULL
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%form_pages_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%form_pages (
  pageid int DEFAULT nextval('%%TABLEPREFIX%%form_pages_sequence') NOT NULL PRIMARY KEY,
  formid int default NULL,
  pagetype varchar(100) default NULL,
  html text,
  url varchar default NULL,
  sendfromname varchar default NULL,
  sendfromemail varchar default NULL,
  replytoemail varchar default NULL,
  bounceemail varchar default NULL,
  emailsubject varchar default NULL,
  emailhtml text,
  emailtext text
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%forms_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%forms (
  formid int DEFAULT nextval('%%TABLEPREFIX%%forms_sequence') NOT NULL PRIMARY KEY,
  name varchar default NULL,
  design varchar default NULL,
  formhtml text,
  chooseformat varchar(2) default NULL,
  changeformat varchar(1) default 0,
  sendthanks varchar(1) default 0,
  requireconfirm char(1) default 0,
  ownerid int default 0,
  formtype char(1) default NULL,
  createdate int default NULL,
  contactform varchar(1) default 0,
  usecaptcha varchar(1) default 0
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%jobs_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%jobs (
  jobid int DEFAULT nextval('%%TABLEPREFIX%%jobs_sequence') NOT NULL PRIMARY KEY,
  jobtype varchar default NULL,
  jobstatus char(1) default NULL,
  jobtime int default NULL,
  jobdetails text,
  fkid int default '0',
  lastupdatetime int default '0',
  fktype varchar default NULL,
  queueid int default '0',
  ownerid int default NULL,
  approved int default 0,
  authorisedtosend int default 0,
  resendcount int default 0
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%jobs_lists (
  jobid int default NULL,
  listid int default NULL
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%list_subscribers_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%list_subscribers (
  subscriberid int DEFAULT nextval('%%TABLEPREFIX%%list_subscribers_sequence') NOT NULL PRIMARY KEY,
  listid int NOT NULL default '0',
  emailaddress varchar(200),
  domainname varchar(100),
  format char(1) default NULL,
  confirmed char(1) default NULL,
  confirmcode varchar(32) default NULL,
  requestdate int default '0',
  requestip varchar(20) default NULL,
  confirmdate int default '0',
  confirmip varchar(20) default NULL,
  subscribedate int default '0',
  bounced int default '0',
  unsubscribed int default '0',
  unsubscribeconfirmed char(1) default NULL,
  formid int default '0'
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%list_subscribers_unsubscribe (
  subscriberid int NOT NULL default '0',
  unsubscribetime int NOT NULL default '0',
  unsubscribeip varchar(20) default NULL,
  unsubscriberequesttime int default '0',
  unsubscriberequestip varchar(20) default NULL,
  listid int NOT NULL default '0',
  statid int default '0',
  unsubscribearea varchar(20) default NULL
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%lists_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%lists (
  listid int DEFAULT nextval('%%TABLEPREFIX%%lists_sequence') NOT NULL PRIMARY KEY,
  name varchar default NULL,
  ownername varchar default NULL,
  owneremail varchar(100) default NULL,
  bounceemail varchar(100) default NULL,
  replytoemail varchar(100) default NULL,
  bounceserver varchar(100) default NULL,
  bounceusername varchar(100) default NULL,
  bouncepassword varchar(100) default NULL,
  extramailsettings varchar(100) default NULL,
  companyname varchar(255) default NULL,
  companyaddress varchar(255) default NULL,
  companyphone varchar(20) default NULL,
  format char(1) default NULL,
  notifyowner char(1) default NULL,
  imapaccount char(1) default 0,
  createdate int default NULL,
  subscribecount int default '0',
  unsubscribecount int default '0',
  bouncecount int default '0',
  processbounce char(1) default '0',
  agreedelete char(1) default '0',
  agreedeleteall char(1) default '0',
  visiblefields text not null,
  ownerid int default 0
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%newsletters_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%newsletters (
  newsletterid int DEFAULT nextval('%%TABLEPREFIX%%newsletters_sequence') NOT NULL PRIMARY KEY,
  name varchar default NULL,
  format char(1) default NULL,
  subject varchar default NULL,
  textbody text,
  htmlbody text,
  createdate int default NULL,
  active int default NULL,
  archive int default NULL,
  ownerid int NOT NULL default '0'
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%queues (
  queueid int default '0',
  queuetype varchar default NULL,
  ownerid int default NULL,
  recipient int default NULL,
  processed char(1) default '0',
  sent char(1) default '0',
  processtime timestamp
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%queues_sequence";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%settings (
  cronok char(1) default '0',
  cronrun1 int default '0',
  cronrun2 int default '0',
  database_version int default '0'
)";
$queries[] = "INSERT INTO %%TABLEPREFIX%%settings(cronok, cronrun1, cronrun2, database_version) VALUES (0, 0, 0, " . SENDSTUDIO_DATABASE_VERSION . ")";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%config_settings(area varchar(255), areavalue text)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%settings_cron_schedule (
  jobtype VARCHAR(20),
  lastrun INT default '0'
)";
$queries[] = "INSERT INTO %%TABLEPREFIX%%settings_cron_schedule (jobtype, lastrun) VALUES ('send', '-1')";
$queries[] = "INSERT INTO %%TABLEPREFIX%%settings_cron_schedule (jobtype, lastrun) VALUES ('bounce', '-1')";
$queries[] = "INSERT INTO %%TABLEPREFIX%%settings_cron_schedule (jobtype, lastrun) VALUES ('autoresponder', '-1')";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%list_subscriber_bounces_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%list_subscriber_bounces (
  bounceid int DEFAULT nextval('%%TABLEPREFIX%%list_subscriber_bounces_sequence') NOT NULL PRIMARY KEY,
  subscriberid int default NULL,
  statid int default NULL,
  listid int default NULL,
  bouncetime int default NULL,
  bouncetype varchar default NULL,
  bouncerule varchar default NULL,
  bouncemessage text
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_autoresponders (
  statid int NOT NULL default '0',
  htmlrecipients int default '0',
  textrecipients int default '0',
  multipartrecipients int default '0',
  bouncecount_soft int default '0',
  bouncecount_hard int default '0',
  bouncecount_unknown int default '0',
  unsubscribecount int default '0',
  autoresponderid int default '0',
  linkclicks int default '0',
  emailopens int default '0',
  emailforwards int default '0',
  emailopens_unique int default '0',
  htmlopens int default 0,
  htmlopens_unique int default 0,
  textopens int default 0,
  textopens_unique int default 0,
  hiddenby int default 0
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%stats_emailopens_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_emailopens (
  openid int DEFAULT nextval('%%TABLEPREFIX%%stats_emailopens_sequence') NOT NULL PRIMARY KEY,
  subscriberid int default NULL,
  statid int default NULL,
  opentime int default NULL,
  openip varchar(20) default NULL,
  fromlink CHAR(1) DEFAULT 0,
  opentype char(1) DEFAULT 'u'
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%stats_linkclicks_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_linkclicks (
  clickid int DEFAULT nextval('%%TABLEPREFIX%%stats_linkclicks_sequence') NOT NULL PRIMARY KEY,
  clicktime int default NULL,
  clickip varchar(20) default NULL,
  subscriberid int default NULL,
  statid int default NULL,
  linkid int default NULL
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%links_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%links (
  linkid int DEFAULT nextval('%%TABLEPREFIX%%links_sequence') NOT NULL PRIMARY KEY,
  url varchar default NULL
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_links (
  statid int default NULL,
  linkid int default NULL
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_newsletter_lists (
  statid int default NULL,
  listid int default NULL
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_newsletters (
  statid int NOT NULL default '0' PRIMARY KEY,
  queueid int default NULL,
  jobid int default NULL,
  starttime int default NULL,
  finishtime int default NULL,
  htmlrecipients int default '0',
  textrecipients int default '0',
  multipartrecipients int default '0',
  trackopens char(1) default '0',
  tracklinks char(1) default '0',
  bouncecount_soft int default '0',
  bouncecount_hard int default '0',
  bouncecount_unknown int default '0',
  unsubscribecount int default '0',
  newsletterid int default NULL,
  sendfromname varchar(200) default NULL,
  sendfromemail varchar(200) default NULL,
  bounceemail varchar(200) default NULL,
  replytoemail varchar(200) default NULL,
  charset varchar(200) default NULL,
  sendinformation text,
  sendsize int default NULL,
  sentby int default NULL,
  notifyowner char(1) default NULL,
  linkclicks int default '0',
  emailopens int default '0',
  emailforwards int default '0',
  emailopens_unique int default '0',
  htmlopens int default 0,
  htmlopens_unique int default 0,
  textopens int default 0,
  textopens_unique int default 0,
  hiddenby int default 0,
  sendtestmode int default 0,
  sendtype varchar(100) default 'newsletter'
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%stats_sequence";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_users (
  userid int default NULL,
  statid int default NULL,
  jobid int default NULL,
  queuesize int default NULL,
  queuetime int default NULL
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%subscribers_data (
  subscriberid int NOT NULL default '0',
  fieldid int NOT NULL default '0',
  data text
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%templates_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%templates (
  templateid int DEFAULT nextval('%%TABLEPREFIX%%templates_sequence') NOT NULL PRIMARY KEY,
  name varchar default NULL,
  format char(1) default NULL,
  textbody text,
  htmlbody text,
  createdate int default NULL,
  active int default NULL,
  isglobal int default NULL,
  ownerid int NOT NULL default '0'
)";

$time = time();

$queries[] = "
	INSERT INTO %%TABLEPREFIX%%usergroups(
		groupname, createdate, systemadmin
	) VALUES (
		'System Admin',
		{$time}, '1'
	)
";

$queries[] = "
	INSERT INTO %%TABLEPREFIX%%users (
		groupid, username, password, status,
		admintype, listadmintype, templateadmintype,
		createdate, lastloggedin,
		infotips, usewysiwyg, gettingstarted
	) VALUES (
		1, 'admin', md5('password'), '1',
		'a', 'a', 'a',
		{$time}, 0,
		'1', '1', '0'
	)
";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%stats_emailforwards_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_emailforwards (
  forwardid int DEFAULT nextval('%%TABLEPREFIX%%stats_emailforwards_sequence') NOT NULL PRIMARY KEY,
  forwardtime INT,
  forwardip VARCHAR(20),
  subscriberid INT,
  statid INT,
  subscribed INT,
  listid INT,
  emailaddress varchar
)";

$queries[] = "CREATE SEQUENCE %%TABLEPREFIX%%user_stats_emailsperhour_sequence";
$queries[] = "CREATE TABLE %%TABLEPREFIX%%user_stats_emailsperhour (
  summaryid int DEFAULT nextval('%%TABLEPREFIX%%user_stats_emailsperhour_sequence') NOT NULL PRIMARY KEY,
  statid INT DEFAULT '0',
  sendtime INT DEFAULT '0',
  emailssent INT DEFAULT '0',
  userid INT DEFAULT '0'
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%queues_unsent (
	recipient int default 0 references %%TABLEPREFIX%%list_subscribers(subscriberid) ON DELETE CASCADE,
	queueid int default 0,
	reasoncode int default 0,
	reason text
)";

$queries[] = "	CREATE TABLE %%TABLEPREFIX%%modules (
				  modulename VARCHAR(50) NOT NULL,
				  moduleversion INT DEFAULT 0,
				  PRIMARY KEY(modulename)
				)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%log_system_administrator (
  logid serial NOT NULL ,
  loguserid int NOT NULL,
  logip varchar(30) NOT NULL default '',
  logdate int NOT NULL default '0',
  logtodo varchar(100) NOT NULL default '',
  logdata text NOT NULL,
  PRIMARY KEY  (logid)
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%log_system_system (
  logid serial NOT NULL ,
  logtype varchar(20),
  logmodule varchar(100) NOT NULL default '',
  logseverity char(1) NOT NULL default '4',
  logsummary varchar(250) NOT NULL,
  logmsg text NOT NULL,
  logdate int NOT NULL default '0',
  PRIMARY KEY  (logid)
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_autoresponders_recipients (
  statid INT DEFAULT 0,
  autoresponderid INT DEFAULT 0,
  send_status CHAR(1),
  recipient INT DEFAULT 0,
  reason VARCHAR(20),
  sendtime INT
)";

$queries[] = 'CREATE SEQUENCE %%TABLEPREFIX%%segments_sequence';

$queries[] = "CREATE TABLE %%TABLEPREFIX%%segments (
  segmentid INT DEFAULT nextval('%%TABLEPREFIX%%segments_sequence') NOT NULL,
  segmentname VARCHAR(255) NOT NULL,
  createdate INT DEFAULT 0,
  ownerid INT NOT NULL,
  searchinfo TEXT NOT NULL,
  PRIMARY KEY (segmentid)
)";

$queries[] = 'CREATE SEQUENCE %%TABLEPREFIX%%list_subscriber_events_sequence';

$queries[] = 'CREATE TABLE %%TABLEPREFIX%%list_subscriber_events (
	eventid INT DEFAULT nextval(\'%%TABLEPREFIX%%list_subscriber_events_sequence\') NOT NULL,
	subscriberid INT NOT NULL,
	listid INT NOT NULL,
	eventtype TEXT NOT NULL,
	eventsubject TEXT NOT NULL,
	eventdate INT NOT NULL,
	lastupdate INT NOT NULL,
	eventownerid INT NOT NULL,
	eventnotes text NOT NULL,
	PRIMARY KEY (eventid)
)';

$queries[] = "create table %%TABLEPREFIX%%addons (
	addon_id varchar(200) not null primary key,
	installed int default 0,
	configured int default 0,
	enabled int default 0,
	addon_version VARCHAR(10) default '0',
	settings text
)";

$queries[] = 'CREATE SEQUENCE %%TABLEPREFIX%%folders_sequence';

$queries[] = "CREATE TABLE %%TABLEPREFIX%%folders (
	folderid INT DEFAULT nextval('%%TABLEPREFIX%%folders_sequence') NOT NULL PRIMARY KEY,
	name VARCHAR(255),
	type CHAR(1),
	createdate INT DEFAULT 0,
	ownerid INT
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%folder_item (
	folderid INT NOT NULL REFERENCES %%TABLEPREFIX%%folders(folderid) ON DELETE CASCADE,
	itemid INT NOT NULL,
	PRIMARY KEY (folderid, itemid)
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%folder_user (
	folderid INT NOT NULL REFERENCES %%TABLEPREFIX%%folders(folderid) ON DELETE CASCADE,
	userid INT NOT NULL REFERENCES %%TABLEPREFIX%%users(userid) ON DELETE CASCADE,
	expanded CHAR(1) NOT NULL DEFAULT '1',
	ordering INT,
	PRIMARY KEY  (folderid, userid)
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%triggeremails (
	triggeremailsid         SERIAL          NOT NULL,
	active                  CHAR(1)         NOT NULL DEFAULT '0',
	createdate              INT             NOT NULL,
	ownerid                 INT             NOT NULL,
	name                    VARCHAR(100)    NOT NULL,
	triggertype             CHAR(1)         NOT NULL,

	triggerhours            INT             DEFAULT 0,
	triggerinterval         INT             DEFAULT 0,

	queueid                 INT             NOT NULL,
	statid                  INT             NOT NULL,

	PRIMARY KEY (triggeremailsid),
	UNIQUE (queueid),
	UNIQUE (statid)
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%triggeremails_data (
	triggeremailsid         INT             NOT NULL,
	datakey                 VARCHAR(25)     NOT NULL,
	datavaluestring         VARCHAR(255)    DEFAULT NULL,
	datavalueinteger        INT             DEFAULT NULL,

	FOREIGN KEY (triggeremailsid) REFERENCES %%TABLEPREFIX%%triggeremails (triggeremailsid) ON DELETE CASCADE
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%triggeremails_actions (
	triggeremailsactionid   SERIAL          NOT NULL,
	triggeremailsid         INT             NOT NULL,
	action                  VARCHAR(25)     NOT NULL,

	PRIMARY KEY (triggeremailsactionid),
	FOREIGN KEY (triggeremailsid) REFERENCES %%TABLEPREFIX%%triggeremails (triggeremailsid) ON DELETE CASCADE,

	UNIQUE (triggeremailsid, action)
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%triggeremails_actions_data (
	triggeremailsactionid   INT             NOT NULL,
	datakey                 VARCHAR(25)     NOT NULL,
	datavaluestring         VARCHAR(255)    DEFAULT NULL,
	datavalueinteger        INTEGER         DEFAULT NULL,
	triggeremailsid         INT             NOT NULL,

	FOREIGN KEY (triggeremailsactionid) REFERENCES %%TABLEPREFIX%%triggeremails_actions (triggeremailsactionid) ON DELETE CASCADE,
	FOREIGN KEY (triggeremailsid) REFERENCES %%TABLEPREFIX%%triggeremails (triggeremailsid) ON DELETE CASCADE
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%triggeremails_log (
	triggeremailsid         INT             NOT NULL,
	subscriberid            INT             NOT NULL,
	action                  VARCHAR(25)     NOT NULL,
	timestamp               INT             NOT NULL,
	note                    VARCHAR(255)    DEFAULT NULL,

	FOREIGN KEY (triggeremailsid) REFERENCES %%TABLEPREFIX%%triggeremails (triggeremailsid) ON DELETE CASCADE
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%triggeremails_log_summary (
	triggeremailsid         INT             NOT NULL,
	subscriberid            INT             NOT NULL,
	actionedoncount         INT             DEFAULT 0,
	lastactiontimestamp     INT             DEFAULT NULL,

	PRIMARY KEY (triggeremailsid, subscriberid),
	FOREIGN KEY (triggeremailsid) REFERENCES %%TABLEPREFIX%%triggeremails (triggeremailsid) ON DELETE CASCADE
)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%user_activitylog (
	lastviewid              SERIAL          NOT NULL,
	userid                  INT             NOT NULL,
	icon                    VARCHAR(255)    DEFAULT NULL,
	text                    VARCHAR(255)    DEFAULT NULL,
	url                     VARCHAR(255)    NOT NULL,
	viewed                  INT             NOT NULL,

	PRIMARY KEY (lastviewid),
	FOREIGN KEY (userid) REFERENCES %%TABLEPREFIX%%users (userid) ON DELETE CASCADE
)";

$queries[] = "
	CREATE TABLE %%TABLEPREFIX%%user_credit (
	    usercreditid            BIGSERIAL       NOT NULL,
	    userid                  INT             NOT NULL,
	    transactiontype         VARCHAR(25)     NOT NULL,
	    transactiontime         INT             NOT NULL,
	    credit                  BIGINT          NOT NULL,
	    jobid                   INT             DEFAULT NULL,
	    statid                  INT             DEFAULT NULL,
	    expiry                  INT             DEFAULT NULL,
	    PRIMARY KEY (usercreditid),
	    FOREIGN KEY (userid) REFERENCES %%TABLEPREFIX%%users (userid) ON DELETE CASCADE
	)
";

$queries[] = "
	CREATE TABLE %%TABLEPREFIX%%user_credit_summary (
	    usagesummaryid          BIGSERIAL       NOT NULL,
	    userid                  INT             NOT NULL,
	    startperiod             INT             NOT NULL,
	    credit_used             INT             NOT NULL DEFAULT 0,
	    PRIMARY KEY (usagesummaryid),
	    FOREIGN KEY (userid) REFERENCES %%TABLEPREFIX%%users (userid) ON DELETE CASCADE,
	    UNIQUE (userid, startperiod)
	)
";

$queries[] = "
	CREATE TABLE %%TABLEPREFIX%%settings_credit_warnings (
	    creditwarningid         SERIAL          NOT NULL,
	    enabled					CHAR(1)			NOT NULL DEFAULT '0',
	    creditlevel				INT				NOT NULL DEFAULT 0,
	    aspercentage			CHAR(1)			NOT NULL DEFAULT '1',
	    emailsubject			VARCHAR(255)	NOT NULL,
	    emailcontents			TEXT			NOT NULL,
	    PRIMARY KEY (creditwarningid)
	)
";

$queries[] = "
	CREATE TABLE %%TABLEPREFIX%%login_attempt (
		timestamp INTEGER NOT NULL,
		ipaddress VARCHAR(15) NOT NULL
	)
";

$queries[] = "
	CREATE TABLE %%TABLEPREFIX%%login_banned_ip (
			ipaddress VARCHAR(15) NOT NULL,
			bantime INTEGER NOT NULL,
			PRIMARY KEY(ipaddress)
	)
";

$queries[] = "
	CREATE TABLE %%TABLEPREFIX%%whitelabel_settings (
			name VARCHAR(100) NOT NULL,
			value TEXT,
			PRIMARY KEY (name)
	)
";

require(dirname(__FILE__) . '/schema.indexes.php');
