<?php
/**
* WARNING - IF YOU ARE NOT HIGHLY PROFICIENT WITH REGULAR EXPRESSIONS, DO NOT TOUCH THIS FILE.
*
* If you decide to modify this file, CREATE A BACKUP FIRST.
*
* Please read the README.TXT in this directory.
*/

$GLOBALS['Design_Rules']['Hotmail'] = array (
	array(
		'regular_expression' => '%<style(.*?)</\s*?style>(.*?)(?=<body|<style.*?<body)%',
		'description' => 'Hotmail doesn\'t show style tags before body tags',
		'use_preg_replace' => 1,
		'replacement' => '$2',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%@import.*?(\r|\n|\<|;)%',
		'description' => 'Hotmail will strip out any @import tags and any CSS tags found within the <style> tags.',
		'use_preg_replace' => 1,
		'replacement' => '$1',
		'match_offset' => 1
	),
	array(
		'regular_expression' => '%<link.*?>%',
		'description' => 'Hotmail removes any link elements',
		'replacement' => '',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?background-image\s*?:.*?)(\2|;)%',
		'description' => 'Hotmail doesn\'t show the CSS style: background-image',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?background-position\s*?:.*?)(\2|;)%',
		'description' => 'Hotmail doesn\'t show the CSS style: background-position',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?background-spacing\s*?:.*?)(\2|;)%',
		'description' => 'Hotmail doesn\'t show the CSS style border-spacing',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?border(?!\-top|\-left|\-right|\-bottom)\s*?:.*?)(\2|;)%',
		'description' => 'Hotmail doesn\'t show the CSS style: border',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?caption-side\s*?:.*?)(\2|;)%',
		'description' => 'Hotmail doesn\'t show the CSS style: caption-side',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' =>'%(style\s*?=\s*?)(["\'])(.*?)(\s*?clip\s*?:.*?)(\2|;)%',
		'description' => 'Hotmail doesn\'t show the CSS style: clip',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?empty-cells\s*?:.*?)(\2|;)%',
		'description' => 'Hotmail doesn\'t show the CSS style: empty-cells',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?filter\s*?:.*?)(\2|;)%',
		'description' => 'Hotmail doesn\'t show the CSS style: filter',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?list-style-image\s*?:.*?)(\2|;)%',
		'description' => 'Hotmail doesn\'t show the CSS style: list-style-image',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?list-style-position\s*?:.*?)(\2|;)%',
		'description' => 'Hotmail doesn\'t show the CSS style: list-style-position',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?margin(?!\-top|\-left|\-right|\-bottom)\s*?:.*?)(\2|;)%',
		'description' => 'Hotmail doesn\'t show the CSS style: margin',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?opacity\s*?:.*?)(\2|;)%',
		'description' => 'Hotmail doesn\'t show the CSS style: opacity',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?position\s*?:.*?)(\2|;)%',
		'description' => 'Hotmail doesn\'t show the CSS style: position',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
);
