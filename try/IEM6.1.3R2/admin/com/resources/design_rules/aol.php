<?php
/**
* WARNING - IF YOU ARE NOT HIGHLY PROFICIENT WITH REGULAR EXPRESSIONS, DO NOT TOUCH THIS FILE.
*
* If you decide to modify this file, CREATE A BACKUP FIRST.
*
* Please read the README.TXT in this directory.
*/

$GLOBALS['Design_Rules']['AOL'] = array (
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?background-spacing\s*?:.*?)(\2|;)%',
		'description' => 'AOL doesn\'t show the CSS style: background-spacing',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?caption-side\s*?:.*?)(\2|;)%',
		'description' => 'AOL doesn\'t show the CSS style: caption-side',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?empty-cells\s*?:.*?)(\2|;)%',
		'description' => 'AOL doesn\'t show the CSS style: empty-cells',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?opacity\s*?:.*?)(\2|;)%',
		'description' => 'AOL doesn\'t show the CSS style: opacity',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?white-space\s*?:.*?)(\2|;)%',
		'description' => 'AOL doesn\'t show the CSS style: white-space',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	)
);