<?php
/**
* WARNING - IF YOU ARE NOT HIGHLY PROFICIENT WITH REGULAR EXPRESSIONS, DO NOT TOUCH THIS FILE.
*
* If you decide to modify this file, CREATE A BACKUP FIRST.
*
* Please read the README.TXT in this directory.
*/

$GLOBALS['Design_Rules']['Outlook 2007'] = array (
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?azimuth\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: azimuth',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?background-attachment\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: background-attachment',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?background-image\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: background-image',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?background-position\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: background-position',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?background-repeat\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: background-repeat',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?background-spacing\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: border-spacing',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?(?<!border-|margin-)bottom\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: bottom',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?caption-side\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: caption-side',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?clear\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: clear',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?clip\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: clip',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?content\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: content',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?counter-increment\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: counter-increment',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?counter-reset\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: counter-reset',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?cue-before\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: cue-before',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?cue-after\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: cue-after',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?cue(?!-before|-after)\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: cue',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?cursor\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: cursor',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?display\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: display',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?elevation\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: elevation',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?empty-cells\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: empty-cells',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?float\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: float',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?font-size-adjust\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: font-size-adjust',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?font-stretch\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: font-stretch',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?(?<!-|\w)height\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: height',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?(?<!border-|margin-)left\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: left',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?line-break\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: line-break',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?list-style-image\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: list-style-image',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?list-style-position\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: list-style-position',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?marginheight\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t support the CSS style: marginheight',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 1
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?marker-offset\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: marker-offset',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?max-height\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: max-height',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?max-width\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: max-width',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?min-height\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: min-height',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?min-width\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: min-width',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?orphans\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: orphans',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?outline\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: outline',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?outline-color\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: outline-color',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?outline-style\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: outline-style',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?outline-width\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: outline-width',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?overflow\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: overflow',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?overflow-x\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: overflow-x',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?overflow-y\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: overflow-y',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?pause-after\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: pause-after',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?pause-before\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: pause-before',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?pause\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: pause',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?pitch\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: pitch',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?pitch-range\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: pitch-range',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?play-during\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: play-during',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*(?<!-|\w)position\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: position',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?(?<!-|\w)quotes\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: quotes',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?(?<!-|\w)richness\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: richness',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?(?<!-|\w)right\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: right',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?speak\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: speak',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?speak-header\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: speak-header',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?speak-numeral\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: speak-numeral',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?speak-punctuation\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: speak-punctuation',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?speech-rate\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: speech-rate',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?stress\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: stress',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?table-layout\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: table-layout',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?text-shadow\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: text-shadow',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?text-transform\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: text-transform',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?(?<!-|\w)top\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: top',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?unicode-bidi\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: unicode-bidi',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?visibility\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: visibility',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?voice-family\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: voice-family',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?volume\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: volume',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?(?<!-|\w)width\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: width',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?windows\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: windows',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*?word-spacing\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: word-spacing',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*z-index\s*?:.*?)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t show the CSS style: z-index',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$5',
		'match_offset' => 2
	),
	array(
		'regular_expression' => '%(style\s*?=\s*?)(["\'])(.*?)(\s*url=*([\'"]*).*?[^\5>\s]+)(\2|;)%',
		'description' => 'Outlook 2007 doesn\'t support background urls in css',
		'use_preg_replace' => 1,
		'replacement' => '$1$2$3$6',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%<bdo.*?>(.*?)</bdo>%',
		'description' => 'Outlook 2007 doesn\'t show the HTML element: bdo',
		'replacement' => '$1',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%<button.*?>(.*?)</button>%',
		'description' => 'Outlook 2007 doesn\'t show the HTML element: button',
		'replacement' => '$1',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%<input.*?>%',
		'description' => 'Outlook 2007 doesn\'t show the HTML element: input. This includes all types of form fields (radio buttons, checkboxes and text fields).',
		'replacement' => '[ Input Area ]',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%<textarea.*?>(.*?)</textarea>%',
		'description' => 'Outlook 2007 doesn\'t support textareas',
		'replacement' => '$1',
		'match_offset' => 0,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%<form.*?>(.*?)</form>%',
		'description' => 'Outlook 2007 doesn\'t show the HTML element: form',
		'replacement' => '$1',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%<iframe.*?(</iframe>|/>)%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: iframe',
		'replacement' => '',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%<isindex.*?>%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: isindex',
		'replacement' => '[ Input Area ]',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%<menu>(.*?)</menu>%',
		'description' => 'Outlook 2007 doesn\'t show the HTML element: menu',
		'replacement' => '$1',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%<noframes.*?</noframes>%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: noframes',
		'replacement' => '',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%<noscript.*?</noscript>%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: noscript',
		'replacement' => '',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%<select.*?</select>%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: select',
		'replacement' => '[ Option ]',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%<object.*?</object>%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: object',
		'replacement' => '',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%<applet.*?</applet>%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: applet',
		'replacement' => '',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%<param.*?(>|/>)%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: param',
		'replacement' => '',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%<q.*?>(.*?)</q>%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: q',
		'replacement' => '$1',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%<script.*?</script>%',
		'description' => 'Outlook 2007 doesn\'t support any scripting language (including javascript and vbscript)',
		'replacement' => '',
		'match_offset' => 0
	),
	array(
		'regular_expression' => '%(?<=<a)([^>]*?)accesskey\s*=\s*([\'|"]).\2%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element property: accesskey',
		'replacement' => '$1',
		'match_offset' => 0,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%(<\w*?\b)([^>]*?)(\s*?background\s*?=\s*?((\'|").*?\5|.*?))(\s|/>|>)%',
		'description' => 'Outlook 2007 doesn\'t support background urls html elements',
		'replacement' => '$1$2$6',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%(<frame[^>]*?)\s*?noresize(\s*?=\s*?.*?){0,1}(\s|/>|>)%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: noresize',
		'replacement' => '$1$3',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%(<frame[^>]*?)\s*?frameborder(\s*?=\s*?(\'|")?0\3?)?(\s|/>|>)%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: frameborder=0',
		'replacement' => '$1$4',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%(<frame[^>]*?)\s*?scrolling(\s*?=\s*?.*?){0,1}(\s|/>|>)%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: scrolling',
		'replacement' => '$1$3',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%(<img|<frame)([^>]*?)(\s*?longdesc\s*?=\s*?.*?)(\s|/>|>)%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: longdesc',
		'replacement' => '$1$2$4',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
	/**
	 * I can't get this to work unless I break them up into different rules which will further compound the processing time it took to process Outlook 2007 rulese.
	 * For the moment, it will REMOVE all properties of the element after the javascript has been found...
	 * There are also other limitations that this query cannot handle (ie. the javascript contains ">" or "/>" in their text
	 */
	array(
		'regular_expression' => '%(?<=<)([^>]*?)(\s*?(onblur|onchange|onclick|ondblclick|onfocus|onkeydown|onkeypress|onkeyup|onload|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|onreset|onselect|onsubmit|onunload)\s*?=\s*?("|\').*?\4).*?(>|/>)%',
		'description' => 'Outlook 2007 doesn\'t support javascript elements like onblur, onmouseover, onchange.',
		'replacement' => '$1$5',
		'match_offset' => 0,
		'use_preg_replace' => 1
	),
	/**
	 * -----
	 */
	array(
		'regular_expression' => '%(<a|<area)([^>]*?)(\s*?tabindex\s*?=\s*?((\'|").*?\5|.*?))(\s|/>|>)%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: tabindex',
		'replacement' => '$1$2$6',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%(<\w*?\b)([^>]*?)(\s*?title\s*?=\s*?((\'|").*?\5|.*?))(\s|/>|>)%',
		'description' => 'Outlook 2007 doesn\'t support title tags for any html elements',
		'replacement' => '$1$2$6',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%(<img)([^>]*?)(\s*?alt\s*?=\s*?((\'|").*?\5|.*?))(\s|/>|>)%',
		'description' => 'Outlook 2007 doesn\'t support alt tags for images',
		'replacement' => '$1$2$6',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%(<td|<th|<tr)([^>]*?)(\s*?colspan\s*?=\s*?((\'|")0\5|0))(\s|/>|>)%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: colspan=0',
		'replacement' => '$1$2$6',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
	array(
		'regular_expression' => '%(<td|<th|<tr)([^>]*?)(\s*?rowspan\s*?=\s*?((\'|")0\5|0))(\s|/>|>)%',
		'description' => 'Outlook 2007 doesn\'t support the HTML element: rowspan=0',
		'replacement' => '$1$2$6',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),
);
