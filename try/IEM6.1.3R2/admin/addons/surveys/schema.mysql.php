<?php

// an array of tables that are created.
// we need this so if the addon is uninstalled, we know what we need to clean up.
$tables = array('surveys', 'surveys_fields', 'surveys_widgets', 'surveys_response', 'surveys_response_value');

// the actual queries we're going to run.
$queries = array();

$queries[] = 'CREATE TABLE %%TABLEPREFIX%%surveys (
	  	id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
	  	userid INT DEFAULT 0 REFERENCES %%TABLEPREFIX%%users(userid),
	 	name tinytext,
	  	description text,
	  	created int(11) NOT NULL,
	  	updated int(11) default NULL,

	  	surveys_header enum(\'headertext\',\'headerlogo\') NOT NULL default \'headertext\',
		surveys_header_text varchar(255) NOT NULL,
		surveys_header_logo varchar(255) NOT NULL,

		email varchar(255) NOT NULL,
	  	email_feedback tinyint(1) unsigned NOT NULL default 0,
	  	after_submit enum(\'show_message\',\'show_uri\') NOT NULL default \'show_message\',
	  	show_message text NOT NULL,
	  	show_uri text NOT NULL,
	  	error_message text NOT NULL,
	  	submit_button_text tinytext NOT NULL
		) CHARACTER SET=UTF8 ENGINE=INNODB';

$queries[] = 'CREATE TABLE %%TABLEPREFIX%%surveys_fields (
		  id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
		  surveys_widget_id int(11) unsigned NOT NULL default 0,
		  value text,
		  is_selected tinyint(1) unsigned NOT NULL default 0,
		  is_other tinyint(1) unsigned NOT NULL default 0,
		  other_label_text tinytext NOT NULL,
		  display_order int(11) unsigned NOT NULL default 0,
		  KEY surveys_widget_id (surveys_widget_id)
		  ) CHARACTER SET=UTF8 ENGINE=INNODB';

$queries[] = 'CREATE TABLE %%TABLEPREFIX%%surveys_widgets (
	  	id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
	  	surveys_id int(11) unsigned NOT NULL default 0,
	 	name tinytext,
	  	description text,
	  	type varchar(255) NOT NULL,
	  	is_required tinyint(1) unsigned NOT NULL default 0,
	  	is_random tinyint(1) unsigned NOT NULL default 0,
	  	is_visible tinyint(1) unsigned NOT NULL default 0,
	  	allowed_file_types text,
	  	display_order int(11) unsigned NOT NULL default 0,
		KEY surveys_id (surveys_id)
		) CHARACTER SET=UTF8 ENGINE=INNODB';

$queries[] = 'CREATE TABLE %%TABLEPREFIX%%surveys_response (
	  	id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
	  	surveys_id int(11) unsigned NOT NULL default 0,
	  	datetime datetime NOT NULL,
		KEY surveys_id (surveys_id)
		) CHARACTER SET=UTF8 ENGINE=INNODB';


$queries[] = 'CREATE TABLE %%TABLEPREFIX%%surveys_response_value (
	  	id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
	  	surveys_response_id int(11) unsigned NOT NULL default 0,
	  	surveys_widgets_id int(11) unsigned NOT NULL default 0,
	  	value text,
	  	is_othervalue tinyint(1) unsigned NOT NULL default 0,
	  	file_value varchar(64),
	  	KEY surveys_response_id (surveys_response_id),
	  	KEY surveys_widgets_id (surveys_widgets_id)
		) CHARACTER SET=UTF8 ENGINE=INNODB';


// $queries[] = 'CREATE UNIQUE INDEX %%TABLEPREFIX%%surveys_response_value ON %%TABLEPREFIX%%surveys_response_value(surveys_response_id, surveys_widgets_id)';
// $queries[] = 'CREATE UNIQUE INDEX %%TABLEPREFIX%%split_stats_newsletters_split_news ON %%TABLEPREFIX%%splittest_statistics_newsletters(split_statid, newsletter_statid)';
