<?php

// an array of tables that are created.
// we need this so if the addon is uninstalled, we know what we need to clean up.
$tables = array('list_tags', 'dynamic_content_tags', 'dynamic_content_block');

// postgresql stuff has sequences as well as tables.
// this makes it easier to know what is what and we get a consistent name for everything.
$sequences = array('dynamic_content_tags_sequence', 'dynamic_content_block_sequence');

// the actual queries we're going to run.
$queries = array();


$queries[] = 'CREATE SEQUENCE %%TABLEPREFIX%%dynamic_content_tags_sequence';
$queries[] = 'CREATE SEQUENCE %%TABLEPREFIX%%dynamic_content_blocks_sequence';
$queries[] = '
CREATE TABLE %%TABLEPREFIX%%dynamic_content_tags (
  tagid INT NOT NULL DEFAULT nextval(\'%%TABLEPREFIX%%dynamic_content_tags_sequence\') PRIMARY KEY,
  name VARCHAR(255),
  createdate INT DEFAULT 0,
  ownerid INT DEFAULT 0
	)
	';

$queries[] = 'CREATE SEQUENCE %%TABLEPREFIX%%dynamic_content_block_sequence';
$queries[] = '
CREATE TABLE %%TABLEPREFIX%%dynamic_content_block (
  blockid INT NOT NULL DEFAULT nextval(\'%%TABLEPREFIX%%dynamic_content_blocks_sequence\') PRIMARY KEY,
  tagid INT DEFAULT 0 REFERENCES %%TABLEPREFIX%%dynamic_content_tags(tagid),
  name VARCHAR(255),
  rules TEXT,
  activated CHAR(1) DEFAULT NULL,
  sortorder INT DEFAULT 0
	)
	';

$queries[] = '
CREATE TABLE %%TABLEPREFIX%%list_tags (
  tagid INT DEFAULT 0 REFERENCES %%TABLEPREFIX%%dynamic_content_tags(tagid),
  listid INT DEFAULT 0 REFERENCES %%TABLEPREFIX%%lists(listid)
)
	';

$queries[] = 'CREATE UNIQUE INDEX %%TABLEPREFIX%%dynamic_content_tags_lists ON %%TABLEPREFIX%%list_tags(tagid, listid)';
