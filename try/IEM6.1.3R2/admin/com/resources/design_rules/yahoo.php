<?php
/**
* WARNING - IF YOU ARE NOT HIGHLY PROFICIENT WITH REGULAR EXPRESSIONS, DO NOT TOUCH THIS FILE.
*
* If you decide to modify this file, CREATE A BACKUP FIRST.
*
* Please read the README.TXT in this directory.
*/

$GLOBALS['Design_Rules']['Yahoo'] = array (
	array(
		'regular_expression' => '%<link%',
		'description' => 'Yahoo renames any link elements to &lt;xlink&gt;. This will disable the link action.',
		'replacement' => '<xlink',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?background-position\s*?:.*?)(\2|;)%',
		'description' => 'Yahoo doesn\'t show the CSS style: background-position',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?clip\s*?:.*?)(\2|;)%',
		'description' => 'Yahoo doesn\'t show the CSS style: clip',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?list-style-position\s*?:.*?)(\2|;)%',
		'description' => 'Yahoo doesn\'t show the CSS style: list-style-position',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?position\s*?:.*?)(\2|;)%',
		'description' => 'Yahoo doesn\'t show the CSS style: position',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
);