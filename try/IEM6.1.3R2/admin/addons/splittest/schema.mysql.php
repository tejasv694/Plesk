<?php

// an array of tables that are created.
// we need this so if the addon is uninstalled, we know what we need to clean up.
$tables = array('splittests', 'splittest_campaigns', 'splittest_statistics', 'splittest_statistics_newsletters');

// the actual queries we're going to run.
$queries = array();

$queries[] = 'CREATE TABLE %%TABLEPREFIX%%splittests (
		splitid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		splitname VARCHAR(200),
		splittype VARCHAR(100),
		splitdetails TEXT,
		createdate INT DEFAULT 0,
		userid INT DEFAULT 0 REFERENCES %%TABLEPREFIX%%users(userid),
		jobid INT DEFAULT 0,
		jobstatus CHAR(1) DEFAULT NULL,
		lastsent INT DEFAULT 0
	) CHARACTER SET=UTF8 ENGINE=INNODB
	';

$queries[] = 'CREATE TABLE %%TABLEPREFIX%%splittest_campaigns (
		splitid INT DEFAULT 0 REFERENCES %%TABLEPREFIX%%splittests(splitid),
		campaignid INT DEFAULT 0 REFERENCES %%TABLEPREFIX%%newsletters(newsletterid)
	) CHARACTER SET=UTF8 ENGINE=INNODB
	';

$queries[] = 'CREATE TABLE %%TABLEPREFIX%%splittest_statistics (
		split_statid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		splitid INT NOT NULL DEFAULT 0,
		jobid INT NOT NULL DEFAULT 0,
		starttime INT NOT NULL DEFAULT 0,
		finishtime INT NOT NULL DEFAULT 0,
		hiddenby INT NOT NULL DEFAULT 0
	) CHARACTER SET=UTF8 ENGINE=INNODB
	';

$queries[] = 'CREATE TABLE %%TABLEPREFIX%%splittest_statistics_newsletters (
		split_statid INT NOT NULL DEFAULT 0 REFERENCES %%TABLEPREFIX%%splittest_statistics(split_statid),
		newsletter_statid INT NOT NULL DEFAULT 0 REFERENCES %%TABLEPREFIX%%stats_newsletters(statid)
	) CHARACTER SET=UTF8 ENGINE=INNODB
	';

$queries[] = 'CREATE UNIQUE INDEX %%TABLEPREFIX%%split_campaigns_split_campaign ON %%TABLEPREFIX%%splittest_campaigns(splitid, campaignid)';
$queries[] = 'CREATE UNIQUE INDEX %%TABLEPREFIX%%split_stats_newsletters_split_news ON %%TABLEPREFIX%%splittest_statistics_newsletters(split_statid, newsletter_statid)';
