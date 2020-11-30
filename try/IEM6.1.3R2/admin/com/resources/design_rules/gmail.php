<?php
/**
* WARNING - IF YOU ARE NOT HIGHLY PROFICIENT WITH REGULAR EXPRESSIONS, DO NOT TOUCH THIS FILE.
*
* If you decide to modify this file, CREATE A BACKUP FIRST.
*
* Please read the README.TXT in this directory.
*/

$GLOBALS['Design_Rules']['GMail'] = array (
	array(
		'regular_expression' => '%(.*?)<body(.*?)</body>(.*)%',
		'description' => 'Gmail removes anything before the body tags',
		'use_preg_replace' => 1,
		'replacement' => '<body$2</body>',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%(<link[^>]*?>)%',
		'description' => 'Gmail removes any link elements',
		'replacement' => '',
		'match_offset' => 1
	),

	array(
		'regular_expression' => '%<style.*?>%',
		'description' => 'Gmail doesn\'t show any style tags',
		'replacement' => '',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?background-image\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: background-image',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?background-position\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: background-position',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?background-repeat\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style background-repeat',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?background-color\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style background. You can use the HTML tag "bgcolor" instead of, or in conjunction with your "background" CSS tag.',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?(?<!border-|margin-)bottom\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: bottom',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?(?<!border-|margin-)left\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: left',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?(?<!border-|margin-)right\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: right',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?(?<!border-|margin-)top\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: top',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?clear\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: clear',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?clip\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: clip',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?cursor\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style cursor',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?filter\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: filter',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?float\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: float',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(h\d\s*?style\s*?=\s*?)(["\'])(.*?)(\s*?font-family\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style for headers: font-family. You can use the HTML tag "font face" instead of, or in conjunction with your "font-family" CSS tag.',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?(?<!line\-)height\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: height',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?list-style-image\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: list-style-image',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?opacity\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: opacity',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?position\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: position',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?visibility\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: visibility',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?z-index\s*?:.*?)(\2|;)%',
		'description' => 'Gmail doesn\'t show the CSS style: z-index',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 4
	),
);