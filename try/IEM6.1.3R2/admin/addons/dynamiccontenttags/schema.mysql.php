<?php

// an array of tables that are created.
// we need this so if the addon is uninstalled, we know what we need to clean up.
$tables = array('list_tags', 'dynamic_content_tags', 'dynamic_content_block');

// the actual queries we're going to run.
$queries = array();

$queries[] = '
CREATE TABLE %%TABLEPREFIX%%dynamic_content_tags (
  tagid INTEGER(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  createdate INTEGER(11) UNSIGNED NOT NULL,
  ownerid INTEGER(11) UNSIGNED NOT NULL,
  PRIMARY KEY(tagid)
) CHARACTER SET=UTF8 ENGINE=INNODB
	';

$queries[] = '
CREATE TABLE %%TABLEPREFIX%%dynamic_content_block (
  blockid INTEGER(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  tagid INTEGER(11) UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  rules LONGTEXT NOT NULL,
  activated CHAR(1) NULL,
  sortorder INTEGER(4) UNSIGNED NOT NULL,
  PRIMARY KEY(blockid)
) CHARACTER SET=UTF8 ENGINE=INNODB
	';

$queries[] = '
CREATE TABLE %%TABLEPREFIX%%list_tags (
  tagid INTEGER(11) UNSIGNED NOT NULL,
  listid INTEGER(11) UNSIGNED NOT NULL,
  PRIMARY KEY (tagid, listid)
) CHARACTER SET=UTF8 ENGINE=INNODB
	';
