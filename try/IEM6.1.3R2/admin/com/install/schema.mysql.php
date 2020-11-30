<?php
/**
* Schema for mysql databases.
*
* @version     $Id: schema.mysql.php,v 1.53 2008/02/06 06:54:54 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Schema for mysql databases.
* DO NOT CHANGE BELOW THIS LINE.
*/

$queries = array();

$queries[] = "CREATE TABLE %%TABLEPREFIX%%usergroups (
    groupid                 INT             NOT NULL AUTO_INCREMENT,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%usergroups_permissions (
    groupid         INT             NOT NULL,
    area            VARCHAR(255)    NOT NULL,
    subarea         VARCHAR(255)    DEFAULT NULL,
    FOREIGN KEY (groupid) REFERENCES %%TABLEPREFIX%%usergroups(groupid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%usergroups_access (
    groupid         INT             NOT NULL,
    resourcetype    VARCHAR(100)    NOT NULL,
    resourceid      INT             NOT NULL,

    FOREIGN KEY (groupid) REFERENCES %%TABLEPREFIX%%usergroups(groupid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%users (
  userid						INT				AUTO_INCREMENT NOT NULL,
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
  eventactivitytype				LONGTEXT		DEFAULT NULL,
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
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%autoresponders (
  autoresponderid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) default NULL,
  subject varchar(255) default NULL,
  format char(1) default NULL,
  textbody longtext,
  htmlbody longtext,
  createdate int(11) default 0,
  active int default 0,
  pause int default 0,
  hoursaftersubscription int(11) default 0,
  ownerid int(11) NOT NULL default 0 references %%TABLEPREFIX%%users(userid),
  searchcriteria mediumtext,
  listid int(11) default 0 references %%TABLEPREFIX%%lists(listid),
  tracklinks char(1) default 1,
  trackopens char(1) default 1,
  multipart char(1) default 1,
  queueid int(11) default 0,
  sendfromname varchar(255) default NULL,
  sendfromemail varchar(255) default NULL,
  replytoemail varchar(255) default NULL,
  bounceemail varchar(255) default NULL,
  charset varchar(255) default NULL,
  embedimages char(1) default '0',
  to_firstname int default 0 references %%TABLEPREFIX%%customfields(fieldid),
  to_lastname int default 0 references %%TABLEPREFIX%%customfields(fieldid),
  autorespondersize int default 0
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%banned_emails (
  banid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  emailaddress varchar(255) default NULL,
  list varchar(10) default NULL,
  bandate int(11) default NULL
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%customfield_lists (
  cflid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  fieldid int(11) NOT NULL default '0' references %%TABLEPREFIX%%customfields(fieldid),
  listid int(11) NOT NULL default '0' references %%TABLEPREFIX%%lists(listid)
) character set utf8 engine=innodb";


$queries[] = "CREATE TABLE %%TABLEPREFIX%%customfields (
  fieldid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) default NULL,
  fieldtype varchar(100) default NULL,
  defaultvalue varchar(255) default NULL,
  required char(1) default 0,
  fieldsettings mediumtext,
  createdate int(11) default 0,
  ownerid int(11) default 0 references %%TABLEPREFIX%%users(userid),
  isglobal char(1) default '0'
) character set utf8 engine=innodb";


$queries[] = "CREATE TABLE %%TABLEPREFIX%%form_customfields (
  formid int(11) default 0 references %%TABLEPREFIX%%forms(formid),
  fieldid varchar(10) default 0 references %%TABLEPREFIX%%customfields(fieldid),
  fieldorder int default 0
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%form_lists (
  formid int(11) default 0 references %%TABLEPREFIX%%forms(formid),
  listid int(11) default 0 references %%TABLEPREFIX%%lists(listid)
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%form_pages (
  pageid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  formid int(11) default 0 references %%TABLEPREFIX%%forms(formid),
  pagetype varchar(100) default NULL,
  html longtext,
  url varchar(255) default NULL,
  sendfromname varchar(255) default NULL,
  sendfromemail varchar(255) default NULL,
  replytoemail varchar(255) default NULL,
  bounceemail varchar(255) default NULL,
  emailsubject varchar(255) default NULL,
  emailhtml longtext,
  emailtext longtext
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%forms (
  formid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) default NULL,
  design varchar(255) default NULL,
  formhtml longtext,
  chooseformat varchar(2) default NULL,
  changeformat varchar(1) default 0,
  sendthanks varchar(1) default 0,
  requireconfirm varchar(1) default 0,
  ownerid int(11) default 0 references %%TABLEPREFIX%%users(userid),
  formtype char(1) default NULL,
  createdate int(11) default 0,
  contactform varchar(1) default 0,
  usecaptcha varchar(1) default 0
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%jobs (
  jobid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  jobtype varchar(255) default NULL,
  jobstatus char(1) default NULL,
  jobtime int(11) default 0,
  jobdetails text,
  fkid int(11) default '0',
  lastupdatetime int(11) default '0',
  fktype varchar(255) default NULL,
  queueid int(11) default '0',
  ownerid int(11) default 0 references %%TABLEPREFIX%%users(userid),
  approved int default 0 references %%TABLEPREFIX%%users(userid),
  authorisedtosend int default 0 references %%TABLEPREFIX%%users(userid),
  resendcount int default 0
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%jobs_lists (
  jobid int(11) default 0 references %%TABLEPREFIX%%jobs(jobid),
  listid int(11) default 0 references %%TABLEPREFIX%%lists(listid)
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%list_subscribers (
  subscriberid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  listid int(11) NOT NULL default '0' references %%TABLEPREFIX%%lists(listid),
  emailaddress varchar(200),
  domainname varchar(100),
  format char(1) default NULL,
  confirmed char(1) default 0,
  confirmcode varchar(32) default NULL,
  requestdate int(11) default '0',
  requestip varchar(20) default NULL,
  confirmdate int(11) default '0',
  confirmip varchar(20) default NULL,
  subscribedate int(11) default '0',
  bounced int(11) default '0',
  unsubscribed int(11) default '0',
  unsubscribeconfirmed char(1) default 0,
  formid int(11) default '0'
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%list_subscribers_unsubscribe (
  subscriberid int(11) NOT NULL default '0' references %%TABLEPREFIX%%list_subscribers(subscriberid),
  unsubscribetime int(11) NOT NULL default '0',
  unsubscribeip varchar(20) default NULL,
  unsubscriberequesttime int(11) default '0',
  unsubscriberequestip varchar(20) default NULL,
  listid int(11) NOT NULL default '0' references %%TABLEPREFIX%%lists(listid),
  statid int(11) default '0',
  unsubscribearea varchar(20) default NULL
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%lists (
  listid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) default NULL,
  ownername varchar(255) default NULL,
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
  notifyowner char(1) default 0,
  imapaccount char(1) default 0,
  createdate int(11) default 0,
  subscribecount int(11) default '0',
  unsubscribecount int(11) default '0',
  bouncecount int(11) default '0',
  processbounce char(1) default '0',
  agreedelete char(1) default '0',
  agreedeleteall char(1) default '0',
  visiblefields text not null,
  ownerid int(11) default 0 references %%TABLEPREFIX%%users(userid)
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%newsletters (
  newsletterid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) default NULL,
  format char(1) default NULL,
  subject varchar(255) default NULL,
  textbody longtext,
  htmlbody longtext,
  createdate int(11) default 0,
  active int default 0 references %%TABLEPREFIX%%users(userid),
  archive int default 0 references %%TABLEPREFIX%%users(userid),
  ownerid int(11) NOT NULL default '0' references %%TABLEPREFIX%%users(userid)
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%queues (
  queueid int(11) default '0',
  queuetype varchar(255) default NULL,
  ownerid int(11) NOT NULL default '0' references %%TABLEPREFIX%%users(userid),
  recipient int(11) default 0 references %%TABLEPREFIX%%list_subscribers(subscriberid),
  processed char(1) default '0',
  sent char(1) default '0',
  processtime datetime
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%queues_sequence (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY
) character set utf8 engine=innodb";
$queries[] = "INSERT INTO %%TABLEPREFIX%%queues_sequence(id) VALUES (0)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%settings (
  cronok char(1) default '0',
  cronrun1 int(11) default '0',
  cronrun2 int(11) default '0',
  database_version int default '0'
) character set utf8 engine=innodb";
$queries[] = "INSERT INTO %%TABLEPREFIX%%settings(cronok, cronrun1, cronrun2, database_version) VALUES (0, 0, 0, " . SENDSTUDIO_DATABASE_VERSION . ")";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%config_settings(area varchar(255), areavalue text) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%settings_cron_schedule (
  jobtype VARCHAR(20),
  lastrun INT default '0'
) character set utf8 engine=innodb";
$queries[] = "INSERT INTO %%TABLEPREFIX%%settings_cron_schedule (jobtype, lastrun) VALUES ('send', '-1')";
$queries[] = "INSERT INTO %%TABLEPREFIX%%settings_cron_schedule (jobtype, lastrun) VALUES ('bounce', '-1')";
$queries[] = "INSERT INTO %%TABLEPREFIX%%settings_cron_schedule (jobtype, lastrun) VALUES ('autoresponder', '-1')";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%list_subscriber_bounces (
  bounceid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  subscriberid int(11) default 0 references %%TABLEPREFIX%%list_subscribers(subscriberid),
  statid int(11) default 0,
  listid int(11) default 0 references %%TABLEPREFIX%%lists(listid),
  bouncetime int(11) default 0,
  bouncetype varchar(255) default NULL,
  bouncerule varchar(255) default NULL,
  bouncemessage longtext
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_autoresponders (
  statid int(11) NOT NULL default '0',
  htmlrecipients int(11) default '0',
  textrecipients int(11) default '0',
  multipartrecipients int(11) default '0',
  bouncecount_soft int(11) default '0',
  bouncecount_hard int(11) default '0',
  bouncecount_unknown int(11) default '0',
  unsubscribecount int(11) default '0',
  autoresponderid int(11) default '0' references %%TABLEPREFIX%%autoresponders(autoresponderid),
  linkclicks int(11) default '0',
  emailopens int(11) default '0',
  emailforwards int(11) default '0',
  emailopens_unique int(11) default '0',
  htmlopens int default 0,
  htmlopens_unique int default 0,
  textopens int default 0,
  textopens_unique int default 0,
  hiddenby int default 0 references %%TABLEPREFIX%%users(userid),
  PRIMARY KEY(statid)
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_emailopens (
  openid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  subscriberid int(11) default 0 references %%TABLEPREFIX%%list_subscribers(subscriberid),
  statid int(11) default 0,
  opentime int(11) default 0,
  openip varchar(20) default NULL,
  fromlink CHAR(1) DEFAULT 0,
  opentype char(1) DEFAULT 'u'
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_linkclicks (
  clickid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  clicktime int(11) default 0,
  clickip varchar(20) default NULL,
  subscriberid int(11) default 0 references %%TABLEPREFIX%%list_subscribers(subscriberid),
  statid int(11) default 0,
  linkid int(11) default 0 references %%TABLEPREFIX%%links(linkid)
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_links (
  statid int(11) default 0,
  linkid int(11) default 0 references %%TABLEPREFIX%%links(linkid)
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%links (
  linkid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  url varchar(255) default NULL
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_newsletter_lists (
  statid int(11) default NULL,
  listid int(11) default 0 references %%TABLEPREFIX%%lists(listid)
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_newsletters (
  statid int(11) NOT NULL default '0',
  queueid int(11) default 0,
  jobid int(11) default 0 references %%TABLEPREFIX%%jobs(jobid),
  starttime int(11) default 0,
  finishtime int(11) default 0,
  htmlrecipients int(11) default '0',
  textrecipients int(11) default '0',
  multipartrecipients int(11) default '0',
  trackopens char(1) default '0',
  tracklinks char(1) default '0',
  bouncecount_soft int(11) default '0',
  bouncecount_hard int(11) default '0',
  bouncecount_unknown int(11) default '0',
  unsubscribecount int(11) default '0',
  newsletterid int(11) default 0 references %%TABLEPREFIX%%newsletters(newsletterid),
  sendfromname varchar(200) default NULL,
  sendfromemail varchar(200) default NULL,
  bounceemail varchar(200) default NULL,
  replytoemail varchar(200) default NULL,
  charset varchar(200) default NULL,
  sendinformation mediumtext,
  sendsize int(11) default 0,
  sentby int(11) default 0 references %%TABLEPREFIX%%users(userid),
  notifyowner char(1) default 0,
  linkclicks int(11) default '0',
  emailopens int(11) default '0',
  emailforwards int(11) default '0',
  emailopens_unique int(11) default '0',
  htmlopens int default 0,
  htmlopens_unique int default 0,
  textopens int default 0,
  textopens_unique int default 0,
  hiddenby int default 0 references %%TABLEPREFIX%%users(userid),
  sendtestmode int default 0,
  sendtype varchar(100) default 'newsletter',
  PRIMARY KEY (statid)
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_sequence (
  id int(11) NOT NULL auto_increment,
  PRIMARY KEY (id)
) character set utf8 engine=innodb";
$queries[] = "INSERT INTO %%TABLEPREFIX%%stats_sequence(id) VALUES (0)";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_users (
  userid int(11) default 0 references %%TABLEPREFIX%%users(userid),
  statid int(11) default 0,
  jobid int(11) default 0 references %%TABLEPREFIX%%jobs(jobid),
  queuesize int(11) default 0,
  queuetime int(11) default 0
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%subscribers_data (
  subscriberid int(11) NOT NULL default '0' references %%TABLEPREFIX%%list_subscribers(subscriberid),
  fieldid int(11) NOT NULL default '0' references %%TABLEPREFIX%%customfields(fieldid),
  data text
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%templates (
  templateid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) default NULL,
  format char(1) default NULL,
  textbody longtext,
  htmlbody longtext,
  createdate int(11) default 0,
  active int default 0 references %%TABLEPREFIX%%users(userid),
  isglobal int default 0 references %%TABLEPREFIX%%users(userid),
  ownerid int(11) NOT NULL default '0' references %%TABLEPREFIX%%users(userid)
) character set utf8 engine=innodb";

$queries[] = "
	INSERT INTO %%TABLEPREFIX%%usergroups(
		groupname, createdate, systemadmin
	) VALUES (
		'System Admin',
		UNIX_TIMESTAMP(NOW()), '1'
	)
";

$queries[] = "
	INSERT INTO %%TABLEPREFIX%%users(
		groupid, username, password, status,
		admintype, listadmintype, templateadmintype,
		createdate, lastloggedin,
		infotips, usewysiwyg, gettingstarted
	) VALUES (
		1, 'admin', md5('password'), '1',
		'a', 'a', 'a',
		UNIX_TIMESTAMP(NOW()), 0,
		'1', '1', '0'
	)
";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_emailforwards (
  forwardid INT AUTO_INCREMENT PRIMARY KEY,
  forwardtime INT default 0,
  forwardip VARCHAR(20),
  subscriberid INT references %%TABLEPREFIX%%list_subscribers(subscriberid),
  statid INT default 0,
  subscribed INT default 0,
  listid INT references %%TABLEPREFIX%%lists(listid),
  emailaddress VARCHAR(255)
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%user_stats_emailsperhour (
	summaryid INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	statid INT DEFAULT '0',
	sendtime INT DEFAULT '0',
	emailssent INT DEFAULT '0',
	userid INT DEFAULT '0' references %%TABLEPREFIX%%users(userid)
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%queues_unsent (
	recipient int default 0 references %%TABLEPREFIX%%list_subscribers(subscriberid) ON DELETE CASCADE,
	queueid int default 0,
	reasoncode int default 0,
	reason text
) character set utf8 engine=innodb";

$queries[] = "	CREATE TABLE %%TABLEPREFIX%%modules (
				  modulename VARCHAR(50) NOT NULL,
				  moduleversion INT DEFAULT 0,
				  PRIMARY KEY(modulename)
				) Engine=innoDB CharSet=UTF8";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%log_system_administrator (
  logid int not null primary key auto_increment,
  loguserid int NOT NULL,
  logip varchar(30) NOT NULL default '',
  logdate int NOT NULL default '0',
  logtodo varchar(100) NOT NULL default '',
  logdata text NOT NULL
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%log_system_system (
  logid int not null primary key auto_increment,
  logtype varchar(20),
  logmodule varchar(100) NOT NULL default '',
  logseverity char(1) NOT NULL default '4',
  logsummary varchar(250) NOT NULL,
  logmsg text NOT NULL,
  logdate int NOT NULL default '0'
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%stats_autoresponders_recipients (
  statid INT DEFAULT 0,
  autoresponderid INT DEFAULT 0,
  send_status CHAR(1),
  recipient INT DEFAULT 0,
  reason VARCHAR(20),
  sendtime INT
  ) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%segments (
  segmentid INT AUTO_INCREMENT NOT NULL,
  segmentname VARCHAR(255) NOT NULL,
  createdate INT(11) DEFAULT 0,
  ownerid INT(11) NOT NULL,
  searchinfo TEXT NOT NULL,
  PRIMARY KEY (segmentid)
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%list_subscriber_events (
	eventid int(11) NOT NULL auto_increment,
	subscriberid int(11) NOT NULL,
	listid int(11) NOT NULL,
	eventtype text NOT NULL,
	eventsubject text NOT NULL,
	eventdate int(11) NOT NULL,
	lastupdate int(11) NOT NULL,
	eventownerid int(11) NOT NULL,
	eventnotes text NOT NULL,
	PRIMARY KEY  (eventid)
) character set utf8 engine=innodb;";

$queries[] = "create table %%TABLEPREFIX%%addons (
	addon_id varchar(200) not null primary key,
	installed int default 0,
	configured int default 0,
	enabled int default 0,
	addon_version VARCHAR(10) default '0',
	settings text
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%folders (
	folderid INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(255),
	type CHAR(1),
	createdate INT(11) DEFAULT 0,
	ownerid INT(11)
) CHARACTER SET UTF8 ENGINE=INNODB";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%folder_item (
	folderid INT(11) NOT NULL REFERENCES %%TABLEPREFIX%%folders(folderid) ON DELETE CASCADE,
	itemid INT(11) NOT NULL,
	PRIMARY KEY (folderid, itemid)
) CHARACTER SET UTF8 ENGINE=INNODB";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%folder_user (
	folderid INT(11) NOT NULL REFERENCES %%TABLEPREFIX%%folders(folderid) ON DELETE CASCADE,
	userid INT(11) NOT NULL REFERENCES %%TABLEPREFIX%%users(userid) ON DELETE CASCADE,
	expanded CHAR(1) NOT NULL DEFAULT '1',
	ordering INT(11),
	PRIMARY KEY  (folderid, userid)
) CHARACTER SET UTF8 ENGINE=INNODB";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%triggeremails (
	triggeremailsid         INT             AUTO_INCREMENT NOT NULL,
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
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%triggeremails_data (
	triggeremailsid         INT             NOT NULL,
	datakey                 VARCHAR(25)     NOT NULL,
	datavaluestring         VARCHAR(255)    DEFAULT NULL,
	datavalueinteger        INT             DEFAULT NULL,

	FOREIGN KEY (triggeremailsid) REFERENCES %%TABLEPREFIX%%triggeremails (triggeremailsid) ON DELETE CASCADE
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%triggeremails_actions (
	triggeremailsactionid	INT             AUTO_INCREMENT NOT NULL,
	triggeremailsid         INT             NOT NULL,
	action                  VARCHAR(25)     NOT NULL,

	PRIMARY KEY (triggeremailsactionid),
	FOREIGN KEY (triggeremailsid) REFERENCES %%TABLEPREFIX%%triggeremails (triggeremailsid) ON DELETE CASCADE,

	UNIQUE (triggeremailsid, action)
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%triggeremails_actions_data (
	triggeremailsactionid   INT             NOT NULL,
	datakey                 VARCHAR(25)     NOT NULL,
	datavaluestring         VARCHAR(255)    DEFAULT NULL,
	datavalueinteger        INT             DEFAULT NULL,
	triggeremailsid         INT             NOT NULL,

	FOREIGN KEY (triggeremailsactionid) REFERENCES %%TABLEPREFIX%%triggeremails_actions (triggeremailsactionid) ON DELETE CASCADE,
	FOREIGN KEY (triggeremailsid) REFERENCES %%TABLEPREFIX%%triggeremails (triggeremailsid) ON DELETE CASCADE
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%triggeremails_log (
	triggeremailsid         INT             NOT NULL,
	subscriberid            INT             NOT NULL,
	action                  VARCHAR(25)     NOT NULL,
	timestamp               INT             NOT NULL,
	note                    VARCHAR(255)    DEFAULT NULL,

	FOREIGN KEY (triggeremailsid) REFERENCES %%TABLEPREFIX%%triggeremails (triggeremailsid) ON DELETE CASCADE
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%triggeremails_log_summary (
	triggeremailsid         INT             NOT NULL,
	subscriberid            INT             NOT NULL,
	actionedoncount         INT             DEFAULT 0,
	lastactiontimestamp     INT             DEFAULT NULL,

	PRIMARY KEY (triggeremailsid, subscriberid),
	FOREIGN KEY (triggeremailsid) REFERENCES %%TABLEPREFIX%%triggeremails (triggeremailsid) ON DELETE CASCADE
) character set utf8 engine=innodb";

$queries[] = "CREATE TABLE %%TABLEPREFIX%%user_activitylog (
	lastviewid              INT             AUTO_INCREMENT NOT NULL,
	userid                  INT             NOT NULL,
	icon                    VARCHAR(255)    DEFAULT NULL,
	text                    VARCHAR(255)    DEFAULT NULL,
	url                     VARCHAR(255)    NOT NULL,
	viewed                  INT             NOT NULL,

	PRIMARY KEY (lastviewid),
	FOREIGN KEY (userid) REFERENCES %%TABLEPREFIX%%users (userid) ON DELETE CASCADE
) character set utf8 engine=innodb";

$queries[] = "
	CREATE TABLE %%TABLEPREFIX%%user_credit (
	    usercreditid            BIGINT          UNSIGNED NOT NULL AUTO_INCREMENT,
	    userid                  INT             NOT NULL,
	    transactiontype         VARCHAR(25)     NOT NULL,
	    transactiontime         INT             UNSIGNED NOT NULL,
	    credit                  BIGINT          NOT NULL,
	    jobid                   INT             DEFAULT NULL,
	    statid                  INT             DEFAULT NULL,
	    expiry                  INT             DEFAULT NULL,
	    PRIMARY KEY (usercreditid),
	    FOREIGN KEY (userid) REFERENCES %%TABLEPREFIX%%users (userid) ON DELETE CASCADE
	) CHARACTER SET UTF8 ENGINE=INNODB
";

$queries[] = "
	CREATE TABLE %%TABLEPREFIX%%user_credit_summary (
	    usagesummaryid          BIGINT          UNSIGNED NOT NULL AUTO_INCREMENT,
	    userid                  INT             NOT NULL,
	    startperiod             INT             NOT NULL,
	    credit_used             INT             NOT NULL DEFAULT 0,
	    PRIMARY KEY (usagesummaryid),
	    FOREIGN KEY (userid) REFERENCES %%TABLEPREFIX%%users (userid) ON DELETE CASCADE,
	    UNIQUE KEY (userid, startperiod)
	) CHARACTER SET UTF8 ENGINE=INNODB
";

$queries[] = "
	CREATE TABLE %%TABLEPREFIX%%settings_credit_warnings (
	    creditwarningid         INT             NOT NULL AUTO_INCREMENT,
	    enabled					CHAR(1)			NOT NULL DEFAULT '0',
	    creditlevel				INT				NOT NULL DEFAULT 0,
	    aspercentage			CHAR(1)			NOT NULL DEFAULT '1',
	    emailsubject			VARCHAR(255)	NOT NULL,
	    emailcontents			MEDIUMTEXT		NOT NULL,
	    PRIMARY KEY (creditwarningid)
	) CHARACTER SET UTF8 ENGINE=INNODB
";

$queries[] = "
	CREATE TABLE %%TABLEPREFIX%%login_attempt (
		timestamp INTEGER NOT NULL,
		ipaddress VARCHAR(15) NOT NULL
	) CHARACTER SET UTF8 ENGINE=INNODB
";

$queries[] = "
	CREATE TABLE %%TABLEPREFIX%%login_banned_ip (
			ipaddress VARCHAR(15) NOT NULL,
			bantime INTEGER NOT NULL,
			PRIMARY KEY(ipaddress)
	) CHARACTER SET UTF8 ENGINE=INNODB
";

$queries[] = "
	CREATE TABLE %%TABLEPREFIX%%whitelabel_settings (
			name VARCHAR(100) NOT NULL,
			value TEXT,
			PRIMARY KEY (name)
	) CHARACTER SET UTF8 ENGINE=INNODB
";

$queries[] = "
	CREATE TABLE %%TABLEPREFIX%%xNulled_by_Flipmode (
			name VARCHAR(100) NOT NULL,
			value TEXT,
			PRIMARY KEY (name)
	) CHARACTER SET UTF8 ENGINE=INNODB
";
require(dirname(__FILE__) . '/schema.indexes.php');
