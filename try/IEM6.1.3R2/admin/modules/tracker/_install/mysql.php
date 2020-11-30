<?php
	$queries = array();

	$queries[] = "	CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "module_tracker (
					  statid INT NOT NULL,
					  stattype VARCHAR(50) NOT NULL,
					  trackername VARCHAR(50) NOT NULL,
					  newsletterid INT,
					  datastring TEXT,
					  PRIMARY KEY(statid, stattype, trackername)
					  #, FOREIGN KEY(newsletterid) REFERENCES " . SENDSTUDIO_TABLEPREFIX . "newsletters(newsletterid)
					) Engine=innoDB CharSet=UTF8;";
?>
