How to create your own design rules.
------------------------------------
***********************
* Please note - if you are not highly proficient with regular expressions, do not modify the files in this directory!
* They rely heavily on regular expressions to find the design-rule that is going to be broken and what should be put in the content instead.
*
* If you decide to continue, back up the file(s) before you start.
***********************

The files in this folder are used when you 'View' an Email Campaign, Autoresponder or Template to show how your content will show in a particular email program. You will get a list of email programs and you will be able to see how each email program will display your content.

Each file has a particular format you will need to follow for SendStudio to be able to process it.

Taken from aol.php:

$GLOBALS['Design_Rules']['AOL'] = array (

SendStudio looks at the whole $GLOBALS['Design_Rules'] array. The name in the associative array tells SendStudio which email client it's going to be - this is exactly how it will appear inside the application so make sure it's descriptive. This can contain spaces (eg 'Outlook 2007') or other information so make it as descriptive as possible.


	array(
		'regular_expression' => '%style\s*=(["\']*)\s*.*?(background-spacing:.*?[^\;|\1][;|\1])\1%',
		'description' => 'AOL doesn\'t show the CSS style: background-spacing',
		'replacement' => '',
		'match_offset' => 2
	),

Each array element contains 4 pieces of information that sendstudio uses to work out what to do.

The regular expression is what SendStudio uses to find the elements that will break in that particular email client.

The description is what SendStudio will show as an error message if the regular expression matches any part of your content.

The replacement is what SendStudio will put in place for a particular element.

The match_offset is the part that SendStudio looks through to do the replacements.

In this case, an offset of '1' gives the quotes (if any) around the style tag. This is then used at the end of the regular expression to ensure we find a matching quote.

style="... ( background-spacing: ..;)..."

An offset of 2 gives us the whole background-spacing element and it's setting - this is the part we want to remove so we set the offset to 2.


As another example, in the gmail.php file we have:

	array(
		'regular_expression' => '%(.*?)<body%',
		'description' => 'Gmail removes anything before the body tags',
		'replacement' => '<body',
		'match_offset' => 0
	),

In this case, we want to remove anything before the start <body> tag.

We want to replace the whole match with '<body' so we keep the starting <body> tag and any elements it has (eg an inline style).

The offset is '0' because we want to match the whole thing and remove it all.

If you need to replace content with part of the regular expression match, set the flag 'use_preg_replace'.

That is:

	array(
		'regular_expression' => '%(<q>(.*[^</q>])</q>)%',
		'description' => 'Outlook 2007 does not support the HTML element: q',
		'replacement' => '${2}',
		'match_offset' => 1,
		'use_preg_replace' => 1
	),

This tells SendStudio to do the replacement in a different fashion which (in this example) grabs the content from the middle of the element and puts it back in.

