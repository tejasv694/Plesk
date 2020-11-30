How to create your own spam rules.
------------------------------------
***********************
* Please note - if you are not highly proficient with regular expressions, do not modify the files in this directory!
* They rely heavily on regular expressions to find spam keywords or phrases.
*
* If you decide to continue, back up the file(s) before you start.
***********************

Spam ratings in SendStudio are based on a scale of 0 (not spam) to 5 (almost guaranteed to be spam).

The files in this folder are used when you are editing an Email Campaign or Autoresponder and you check for spam keywords.

Each file has a particular format you will need to follow for SendStudio to be able to process it.

$GLOBALS['Spam_Rules']['interspire'] = array (
	'body' => array (

SendStudio looks at the whole $GLOBALS['Spam_Rules'] array. The name in the associative array has to be unique. It is not displayed anywhere at all so it does not matter what you put in there. We suggest you use the same name as the filename.

	array('%\bfreepic\b%i','Contains the word \'freepic\'','0.4'),

Each array element contains 3 pieces of information that sendstudio uses to work out what to do.

The first part is a regular expression which finds the word or phrase that is triggering the spam filter.

The second part is the description that is shown if the word or phrase is found in your content.

The third part is the 'score' for that particular phrase or keyword. This should range from 0 to 5.

The scores are accumulative across all files. That is if you trigger a keyword or phrase in file '1', a separate check for this in file '2' will show up again and be scored again.
