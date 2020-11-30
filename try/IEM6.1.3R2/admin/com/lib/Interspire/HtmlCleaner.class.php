<?php
/**
 * This file contains classes which provide a robust HTML sanitising
 * functionality which can be used to clean up any untrusted or user-
 * supplied HTML to suit the website's policies for safe content.
 *
 * @author Gwilym Evans <gwilym.evans@interspire.com>
 * @package Library
 * @subpackage Interspire_HtmlCleaner
 */

/**
 * Interspire_HtmlCleaner Class
 *
 * This class provides robust HTML sanitising functionality which can be used
 * to clean up any untrusted or user-supplied HTML to suit the website's
 * policies for safe content.
 *
 * Example Uses:
 * <code>
 * //	clean HTML using all default settings
 * $output = Interspire_HtmlCleaner::CleanHTMLStatic($input);
 * </code>
 *
 * <code>
 * //	clean HTML with some tags added to the default whitelist
 * //	the same methodology can also be used to define custom URI protocol and global-attribute whitelists
 * $tagAttributeWhitelist = Interspire_HtmlCleaner::$DefaultTagAttributeWhitelist + array(
 * 	'ul' => false,
 * 	'li' => false,
 * );
 *
 * $cleaner = new Interspire_HtmlCleaner();
 * $cleaner->SetTagAttributeWhitelist($tagAttributeWhitelist);
 * $output = $cleaner->CleanHTML($input);
 * </code>
 *
 * Note that the resulting strings will not have newlines converted to BR tags
 * as this will not always be desired by the website. A simple call to either:
 * $output = str_replace("\n", "<br />", $output);	// replace "\n" with "<br />"
 * ...or
 * $output = nl2br($output);	// replace "\n" with "<br />\n"
 * ...will complete the process
 *
 * @author Gwilym Evans <gwilym.evans@interspire.com>
 * @package Library
 * @subpackage Interspire_HtmlCleaner
 */
class Interspire_HtmlCleaner
{
	/**
	 * This array contains a standard list of known protocols which are deemed
	 * (relatively) safe to allow in untrusted URIs.
	 *
	 * @var array
	 */
	public static $DefaultProtocolWhitelist = array(
		'http',
		'https',
		'irc',
		'ftp',
		'sftp',
		'ftps',
		'news',
		'mailto',
		'nntp',
		'gopher',
		'feed',
		'itms',
		'itpc',
	);

	/**
	 * This array contains a standard list of known attributes whic are deemed
	 * (relatively) safe to allow in untrusted HTML content. This list is
	 * applied to all tags.
	 *
	 * Generally this is left empty in favour of using tags with CSS definitions
	 * to format any supplied HTML.
	 *
	 * @var array
	 */
	public static $DefaultAttributeWhitelist = array();

	/**
	 * This array contains a standard list of known tags which are deemed
	 * (relatively) safe to allow in untrusted HTML content.
	 *
	 * Each item in the array also contains a child array which notes any
	 * attributes which are whitelisted specifically for that tag.
	 *
	 * @var array
	 */
	public static $DefaultTagAttributeWhitelist = array(
		'a'				=> array('href'),
		'br'			=> false,
		'b'				=> false,
		'u'				=> false,
		'i'				=> false,
		'code'			=> false,
		'pre'			=> false,
		'strong'		=> false,
		'em'			=> false,
		'blockquote'	=> false,
		'p'				=> array('align'),
		'cite'			=> false,
		'var'			=> false,
		'abbr'			=> false,
		'q'				=> false,
		'dfn'			=> false,
		'sub'			=> false,
		'sup'			=> false,
		'samp'			=> false,
		'kbd'			=> false,
		'acronym'		=> false,
	);

	/**
	 * This array contains a list of tags (by name) which are known to be
	 * inline-level tags. Situations where the site redefines a tag's display
	 * mode to be non-inline are beyond the scope of this list.
	 *
	 * Technically BR is an inline-level tag, but it should not be included
	 * on this list as it will mess up situations like "test\n<br />\ntest\n"
	 * where you expect the end result to be "test<br /><br />test<br />"
	 *
	 * @var array
	 */
	public static $inlineTags = array(
		'a',
		'abbr',
		'acronym',
		'b',
		'basefont',
		'bdo',
		'big',
		'cite',
		'code',
		'dfn',
		'em',
		'font',
		'i',
		'img',
		'input',
		'kbd',
		'label',
		'q',
		's',
		'samp',
		'select',
		'small',
		'span',
		'strike',
		'strong',
		'sub',
		'sup',
		'textarea',
		'tt',
		'u',
		'var',
	);

	/**
	 * The input HTML string.
	 *
	 * @var string
	 */
	private $_input = null;

	/**
	 * An instance of DOMDocument used by the cleaning functions.
	 *
	 * @var DOMDocument
	 */
	private $_document = null;

	/**
	 * Returns the private _document property. This will be null unless LoadDocument has been called.
	 *
	 * @return DOMDocument
	 */
	public function GetDocument ()
	{
		return $this->_document;
	}

	/**
	 * Sets the DOMDocument instance to be used by the cleaning functions. Useful if you've created a document already and don't want to use LoadHTMLAsDocument
	 *
	 * @param DOMDocument $document
	 * @return void
	 */
	public function SetDocument ($document)
	{
		$this->_document = $document;
	}

	/**
	 * An instance of DOMElement representing the <body> tag of the DOMDocument
	 * being used by the cleaning functions.
	 *
	 * @var DOMElement
	 */
	private $_body = null;

	/**
	 * An instance of DOMXPath being used by the cleaning functions.
	 *
	 * @var DOMXPath
	 */
	private $_xpath = null;

	/**
	 * Returns the instance of DOMXPath being used by the cleaning functions, or null if it has not been created yet.
	 *
	 * @return DOMXPath
	 */
	public function GetXPath ()
	{
		return $this->_xpath;
	}

	/**
	 * Private storage for the custom global attribute whitelist being used by this instance of Interspire_HtmlCleaner.
	 *
	 * @var array
	 */
	private $_attributeWhitelist = null;

	/**
	 * Sets the custom global attribute whitelist to be used for this instance of Interspire_HtmlCleaner.
	 *
	 * @param array $list An array of attribute names to use as the custom global attribute whitelist, or provide null to set this instance of Interspire_HtmlCleaner back to using the default whitelist.
	 */
	public function SetAttributeWhitelist ($list)
	{
		$this->_attributeWhitelist = $list;
	}

	/**
	 * Returns the custom global attribute whitelist which has been set for this instance of Interspire_HtmlCleaner, or returns the default whitelist if no custom list has been set.
	 *
	 * @return array
	 */
	public function GetAttributeWhitelist ()
	{
		if (is_null($this->_attributeWhitelist)) {
			return Interspire_HtmlCleaner::$DefaultAttributeWhitelist;
		} else {
			return $this->_attributeWhitelist;
		}
	}

	/**
	 * Private storage for the custom tag/attribute whitelist being used by this instance of Interspire_HtmlCleaner.
	 *
	 * @var array
	 */
	private $_tagAttributeWhitelist = null;

	/**
	 * Sets the custom tag/attribute whitelist to be used for this instance of Interspire_HtmlCleaner.
	 *
	 * @param array $list An array of attribute name => array of attributes to use as the custom tag/attribute whitelist, or provide null to set this instance of Interspire_HtmlCleaner back to using the default whitelist.
	 */
	public function SetTagAttributeWhitelist ($list)
	{
		$this->_tagAttributeWhitelist = $list;
	}

	/**
	 * Returns the custom tag/attribute whitelist which has been set for this instance of Interspire_HtmlCleaner, or returns the default whitelist if no custom list has been set.
	 *
	 * @return array
	 */
	public function GetTagAttributeWhitelist ()
	{
		if (is_null($this->_tagAttributeWhitelist)) {
			return self::$DefaultTagAttributeWhitelist;
		} else {
			return $this->_tagAttributeWhitelist;
		}
	}

	/**
	 * Private storage for the custom URI protocol whitelist being used by this instance of Interspire_HtmlCleaner.
	 *
	 * @var array
	 */
	private $_protocolWhitelist = null;

	/**
	 * Sets the custom URI protocol whitelist to be used for this instance of Interspire_HtmlCleaner.
	 *
	 * @param array $list An array of protocol names to use as the custom URI protocol whitelist, or provide null to set this instance of Interspire_HtmlCleaner back to using the default whitelist.
	 */
	public function SetProtocolWhitelist ($list)
	{
		$this->_protocolWhitelist = $list;
	}

	/**
	 * Returns the custom URI protocol whitelist which has been set for this instance of Interspire_HtmlCleaner, or returns the default whitelist if no custom list has been set.
	 *
	 * @return array
	 */
	public function GetProtocolWhitelist ()
	{
		if (is_null($this->_protocolWhitelist)) {
			return Interspire_HtmlCleaner::$DefaultProtocolWhitelist;
		} else {
			return $this->_protocolWhitelist;
		}
	}

	/**
	 * Sets the input string to be used by cleaning functionality.
	 *
	 * @param string $input Input HTML string to set.
	 * @return void
	 */
	public function SetInput ($input)
	{
		$this->_input = $input;
	}

	/**
	 * Returns the unmodified input string.
	 *
	 * @return string The unmodified input string currently being used by the cleaning functionality.
	 */
	public function GetInput ()
	{
		return $this->_input;
	}

	/**
	 * Private storage for the preference of adding rel=nofollow attributes to A tags.
	 *
	 * @var boolean
	 */
	private $_relnofollow = true;

	/**
	 * Sets the preference for adding rel=nofollow attributes to all A tags during the cleaning process.
	 *
	 * @param boolean $value Provide true to activate this functionality, or false to deactivate.
	 */
	public function SetRelNoFollow ($value)
	{
		$this->_relnofollow = $value;
	}

	/**
	 * Returns the preference for adding rel=nofollow attributes to all A tags during the cleaning process.
	 *
	 * @return boolean Returns true if this functionality is active, otherwise false.
	 */
	public function GetRelNoFollow ()
	{
		return $this->_relnofollow;
	}

	/**
	 * Constructor.
	 *
	 * @param string $input Optional. Input string to set by default.
	 * @param array $attributeWhitelist Optional. Custom global attribute whitelist to use for this instance of Interspire_HtmlCleaner.
	 * @param array $tagAttributeWhitelist Optional. Custom tag/attribute whitelist to use for this instance of Interspire_HtmlCleaner.
	 * @param array $protocolWhitelist Optional. Custom URI protocol whitelist to use for this instance of Interspire_HtmlCleaner.
	 */
	public function __construct ($input = null, $attributeWhitelist = null, $tagAttributeWhitelist = null, $protocolWhitelist = null)
	{
		if (!is_null($input)) {
			$this->SetInput($input);
		}

		if (!is_null($attributeWhitelist)) {
			$this->SetAttributeWhitelist($attributeWhitelist);
		}

		if (!is_null($tagAttributeWhitelist)) {
			$this->SetTagAttributeWhitelist($tagAttributeWhitelist);
		}

		if (!is_null($protocolWhitelist)) {
			$this->SetProtocolWhitelist($protocolWhitelist);
		}
	}

	/**
	 * CleanHTMLStatic
	 * A static method which can be used by an application which does not
	 * modify any of the default settings. Named as such due to PHP not
	 * allowing static methods to share names with regular methods.
	 *
	 * This is a shorthand call to the following:
	 *
	 * $cleaner = new Interspire_HtmlCleaner();
	 * $html = $cleaner->CleanHTML($html);
	 *
	 * @param string $html Input HTML string.
	 * @return string The results of the HTML cleaning process.
	 */
	public static function CleanHTMLStatic ($html)
	{
		$cleaner = new self($html);
		return $cleaner->CleanHTML();
	}

	/**
	 * This calls each step of the HTML cleaning process in the correct order.
	 * Internally, each step of the process is a method which can be called
	 * individually if an application wants to execute only certain parts of
	 * the process.
	 *
	 * @param string $input Optional. Input HTML string to process (will internally call SetInput());
	 * @return string The processing result.
	 */
	public function CleanHTML ($input = null)
	{
		if (!is_null($input)) {
			$this->SetInput($input);
		}

		$str = $this->GetInput();

		$str = self::StripNull($str);
		$str = $this->NormaliseNewlines($str);
		$str = $this->LessThanToEntities($str);
		$str = $this->StripTags($str);
		$this->LoadHTMLAsDocument($str);
		$document = $this->GetDocument();
		$xpath = $this->LoadXPath($document);
		$body = $this->GetBodyElement($document);

		$attributeWhitelist = $this->GetAttributeWhitelist();
		$tagAttributeWhitelist = $this->GetTagAttributeWhitelist();
		$this->StripAttributes($xpath, $body, $attributeWhitelist, $tagAttributeWhitelist);

		$this->RemoveRedundantWhitespace($xpath, $body);

		if ($this->HrefAllowed($attributeWhitelist, $tagAttributeWhitelist)) {
			//	apply the protocol whitelist if the attribute whitelists allow for HREF
			$this->SanitiseHrefAttributes($xpath, $body, $this->GetProtocolWhitelist());
		}

		if ($this->GetRelNoFollow()) {
			$this->AddRelNoFollow($xpath, $body);
		}

		return $this->GetBodyContent($body);
	}

	/**
	 * Returns the first BODY element of the given DOMDocument.
	 *
	 * @param DOMDocument $document The DOMDocument containing a body tag
	 * @return DOMElement A DOMElement representing the first body tag of the provided DOMDocument
	 */
	public function GetBodyElement ($document)
	{
		return $document->getElementsByTagName('body')->item(0);
	}

	/**
	 * Returns the XHTML content between a given body tag as a string.
	 *
	 * @param DOMElement $body An instance of DOMElement representing the body tag.
	 * @return string The 'innerHTML' content of the body tag.
	 */
	public function GetBodyContent ($body)
	{
		$document = $body->ownerDocument;

		$xpath = $this->LoadXPath($document);

		//	saveXML will output script and style tags with a cdata block which causes errors when loaded as xhtml in a browser, correct this...
		$blocks = $xpath->query('//script|//style');
		if (is_object($blocks)) {
			foreach ($blocks as $block) {
				if (!$block->hasChildNodes()) {
					//	don't process empty nodes
					continue;
				}

				$source = $block->firstChild->data;

				//	strip any cdata delimiters inside the cdata block; these are not picked up by libxml as the beginning of the real cdata block because they will start with a // or /*
				$source = str_replace('<![CDATA[', '', $source);
				$source = str_replace(']]>', '', $source);

				//	remove any html comments from the script source
				$source = str_replace('<!--', '', $source);
				$source = str_replace('-->', '', $source);

				$source = trim($source);

				//	remove any blank comments
				$source = preg_replace('#//\s*$#m', '', $source);
				$source = preg_replace('#/\*\s*\*/#', '', $source);

				//	place one newline either side of all script source for readability and easy cdata replacement later
				$source = "\n". trim($source) ."\n";

				$block->firstChild->data = $source;
			}
		}

		//	produce xhtml output
		$str = $document->saveXML($body->firstChild);

		//	strip off the outside div which was added during the load process if it still exists after whitelist filtering
		$str = preg_replace('#^<div>(.*)</div>$#s', '$1', $str);

		//	script tags will be cdata encoded but tinymce does not like this so change them to be enclosed in html comments instead
		$str = preg_replace('#(<script[^>]*>)<!\[CDATA\[#i', '$1//<!--', $str);
		$str = preg_replace('#]]>(</script>)#i', '//-->$1', $str);

		//	script tags will be cdata encoded but tinymce does not like this so change them to be enclosed in html comments instead
		$str = preg_replace('#(<style[^>]*>)<!\[CDATA\[#i', '$1/*<!--*/', $str);
		$str = preg_replace('#]]>(</style>)#i', '/*-->*/$1', $str);

		//	decode any cdata blocks which were encoded as text content
		//$str = str_replace('&lt;![CDATA[', '<![CDATA[', $str);
		//$str = str_replace(']]&gt;', ']]>', $str);

		//	decode any comment blocks which were encoded as text content
		//$str = str_replace('&lt;!--', '<!--', $str);
		//$str = str_replace('--&gt;', '-->', $str);

		return $str;
	}

	/**
	 * Determines if the HREF attribute is allowed anywhere in the document based on the attribute whitelists.
	 *
	 * @param array $globalWhitelist By reference. Array of attribute whitelist for all tags.
	 * @param array $tagWhitelist By reference. Array of tag/attribute whitelist data. As described by Interspire_HtmlCleaner::$DefaultTagAttributeWhitelist.
	 * @return boolean Returns true if the HREF attribute is specified anywhere in the whitelists, otherwise false.
	 */
	public function HrefAllowed (&$globalWhitelist, &$tagWhitelist)
	{
		if (is_array($globalWhitelist) && !empty($globalWhitelist) && in_array('href', $globalWhitelist)) {
			//	the global whitelist is a non-empty array and contains 'href'
			return true;
		}

		if (is_array($tagWhitelist) && !empty($tagWhitelist)) {
			foreach ($tagWhitelist as $tag => &$whitelist) {
				if (is_array($whitelist) && !empty($whitelist) && in_array('href', $whitelist)) {
					//	this tag's whitelist is a non-empty array and contains 'href'
					return true;
				}
			}
		}

		//	neither condition satisfied, return false
		return false;
	}

	/**
	 * Strips NULL characters from the given string.
	 *
	 * @param string $str The input string to process.
	 * @return string The resulting string.
	 */
	public static function StripNull ($str)
	{
		return str_replace("\0", "", $str);
	}

	/**
	 * Returns the given string with newlines normalised from most operating
	 * systems back to UNIX (LF) newlines. Allows for easier regex and nl2br-
	 * like processing.
	 *
	 * @param string $str The input string to process.
	 * @return string The resulting string.
	 */
	public function NormaliseNewlines ($str)
	{
		//	Change DOS/Windows line endings to UNIX
		$str = str_replace("\r\n", "\n", $str);

		//	Change any leftover Mac OS < 10, OS-9 etc. endings to UNIX
		return str_replace("\r", "\n", $str);
	}

	/**
	 * Returns the given string with most solitary '<' (less-than) characters
	 * encoded into &lt; entities.
	 *
	 * This will handle the following situations:
	 * "< "
	 * "<3"
	 * "< 3"
	 * "<3 "
	 * "</ "
	 * "</3 "
	 * plus some others
	 *
	 * However, situations like:
	 * "<b>this<that</b>"
	 * cannot yet handled cleanly by this function.
	 *
	 * This function exists so that some obviously harmless uses of '<' aren't
	 * dropped by the DOMDocument parsing engine, resulting in vast portions of
	 * the provided text being wiped out.
	 *
	 * @param string $str The input string to process.
	 * @return string The resulting string.
	 */
	public function LessThanToEntities ($str)
	{
		//	add one space to the end of the string so that we do not have to
		//	have extra end-of-line regex tests
		$str  = $str .' ';

		//	handle some innocent uses of the < character with numbers or some
		//	cases like "</test "... unfortunately "<b>this<that</b>" cannot be
		//	handled cleanly as 'that' will always be mistaken for an html tag
		//	the result will be "<b>this</b>"
		$str = preg_replace('/<([^a-zA-Z\s\/]+|\s)/', '&lt;$1', $str);	//	handles "4<5" "4 < 5"
		$str = preg_replace('/<\/([^\s>]*)(\s)/', '&lt;/$1$2', $str);	//	handles "</3 "

		//	strip the padding we added and return
		return substr($str, 0, strlen($str) - 1);
	}

	/**
	 * Calls PHP's internal strip_tags function based on the internal tag
	 * whitelist. This results in returning an HTML string with undesired
	 * tags stripped out, leaving their text content behind.
	 *
	 * Attributes of whitelisted tags are not affected by this function.
	 *
	 * @param string $str The input string to process.
	 * @return string The resulting string.
	 */
	public function StripTags ($str)
	{
		//	get the current tag whitelist
		$whitelist = $this->GetTagAttributeWhitelist();

		//	convert it to a string usable by strip_tags
		$whitelist = '<' . implode('><', array_keys($whitelist)) . '>';

		//	strip unwanted tags leaving their content behind
		//	we use this because removing elements in the dom is much more
		//	involved and strip_tags does a nice job of cleaning up some broken
		//	html
		return strip_tags($str, $whitelist);
	}

	/**
	 * Loads the given HTML string into an instance of DOMDocument, populating
	 * the private _document property.
	 *
	 * This function will add XHTML headers (unless $addHeaders is false) to
	 * the given partial HTML string to force DOMDocument to handle the
	 * document as:
	 * - XHTML 1.0 Transitional
	 * - UTF-8
	 *
	 * The resulting instance of DOMDocument can be retrieved using the
	 * GetDocument() method.
	 *
	 * @param string $str The input string to process.
	 * @param boolean $addHeaders Optional. Default true. With this set to true, this function will add XHTML/UTF-8 handling tags to the string before passing it to DOMDocument. If this is false, the input string should contain all necessary headers to generate a predictable DOMDocument, otherwise the processing results may be unpredictable.
	 * @return boolean Returns true if the parsing was successful, otherwise false. This is determined by the result of DOMDocument::loadHTML.
	 */
	public function LoadHTMLAsDocument ($str, $addHeaders = true)
	{
		if (is_null($this->_document)) {
			$this->_document = new DOMDocument();
		}

		if ($addHeaders) {
			$str = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head><meta http-equiv="content-type" content="text/html; charset=utf-8" /></head><body><div>'. $str .'</div></body></html>';
		}

		return @$this->_document->loadHTML($str);
	}

	/**
	 * Creates an instance of DOMXPath based on the given DOMDocument and
	 * stores it in the private _xpath property.
	 *
	 * The resulting DOMXPath instance can also be retrieved using the
	 * GetXPath method.
	 *
	 * @param DOMDocument $document The DOMDocument to use with the DOMXPath object
	 * @return DOMXPath Returns the generated DOMXPath object.
	 */
	public function LoadXPath ($document)
	{
		//	create an xpath query object for our usage
		$this->_xpath = new DOMXPath($document);
		return $this->GetXPath();
	}

	/**
	 * Modifies the provided DOMElement instance to strip all attributes which are not in the provided whitelists.
	 *
	 * @param DOMXPath $xpath An instance of DOMXPath.
	 * @param DOMElement $element An instance of DOMElement to set the context for replacement (usually set to the body tag of the document).
	 * @param array $globalWhitelist By reference. Array of attribute whitelist for all tags.
	 * @param array $tagWhitelist By reference. Array of tag/attribute whitelist data. As described by Interspire_HtmlCleaner::$DefaultTagAttributeWhitelist.
	 * @return void
	 */
	public function StripAttributes ($xpath, $element, &$globalWhitelist, &$tagWhitelist)
	{
		//	find all attributes in the html content
		$blocks = $xpath->query('//@*', $element);

		$checkglobal = (is_array($globalWhitelist) && !empty($globalWhitelist));

		foreach ($blocks as $block) {
			$attributeName = $block->nodeName;

			if ($checkglobal && in_array($attributeName, $globalWhitelist)) {
				//	skip (allow) if attribute is in global whitelist
				continue;
			}

			$tagName = $block->parentNode->nodeName;
			if (isset($tagWhitelist[$tagName])) {
				$whitelist = &$tagWhitelist[$tagName];
				if (is_array($whitelist) && !empty($whitelist) && in_array($attributeName, $whitelist)) {
					//	skip (allow) if attribute is in tag whitelist
					continue;
				}
			}

			//	remove this attribute if it is not in the whitelists
			$block->parentNode->removeAttributeNode($block);
		}
	}

	/**
	 * Modifies the provided DOMElement instance to remove all redundant
	 * whitespace/newline text nodes, allowing the nl2br function to provide a
	 * result which is more in-line with user expectations when the content
	 * also contains HTML formatting.
	 *
	 * Removes whitespaces/newlines between:
	 * - Two block elements like "<table>\n<tr>"
	 * - One newline immediately after a block element like "</table>\ntest"
	 * - One newline immediately before a block element like "test\n<table>", unless the element is a <br />
	 *
	 * @param DOMXPath $xpath An instance of DOMXPath.
	 * @param DOMElement $element An instance of DOMElement to set the context for replacement (usually set to the body tag of the document).
	 */
	public function RemoveRedundantWhitespace ($xpath, $element)
	{
		//	remove all textnodes which are whitespace only, allowing nl2br to work without breaking block-level elements
		$blocks = $xpath->query("//text()", $element);
		foreach ($blocks as $block) {
			$previousInline = $block->previousSibling ? in_array($block->previousSibling->nodeName, Interspire_HtmlCleaner::$inlineTags) : false;
			$nextInline = $block->nextSibling ? in_array($block->nextSibling->nodeName, Interspire_HtmlCleaner::$inlineTags) : false;

			if ($block->isWhitespaceInElementContent()) {
				//	remove empty text nodes between block level elements
				if (!($previousInline || $nextInline)) {
					//	remove whitespace between two block-level elements
					$block->parentNode->removeChild($block);
					continue;
				}
			}

			$text = $block->textContent;

			if (!$previousInline && substr($text, 0, 1) == "\n") {
				//	remove 1 left-trailing newline if the previous element was block-level
				$text = substr($text, 1);
			}

			$textlen = strlen($text);
			if (!$nextInline && $block->nextSibling && $block->nextSibling->nodeName != 'br' && substr($text, $textlen - 1, 1) == "\n") {
				//	remove 1 right-trailing newline if the next element is block-level (except for br tags)
				$text = substr($text, 0, $textlen - 1);
			}
			if((int)$block->length > 0){
				$block->replaceData(0, $block->length, $text);
			}
		}
	}

	/**
	 * Modifies all HREF attributes in the child nodes of the provided
	 * DOMElement $element, removing any URIs which do not match the given
	 * protocol whitelist.
	 *
	 * @param DOMXPath $xpath An instance of DOMXPath.
	 * @param DOMElement $element An instance of DOMElement to set the context for replacement (usually set to the body tag of the document).
	 * @param array $protocolWhitelist
	 * @param string $replacement Optional. Default 'javascript:;'. Invalid URIs will be replaced with this string.
	 */
	public function SanitiseHrefAttributes ($xpath, $element, &$protocolWhitelist, $replacement = 'javascript:;')
	{
		$nodes = $xpath->query("//@href", $element);
		foreach ($nodes as $node) {
			//	trim the href
			$href = ltrim($node->textContent);

			//	check the protocol blacklist
			if (preg_match('/^([\x00-\x20a-zA-Z0-9\.]*?):/is', $href, $matches)) {	//	match letters/numbers/whitespaces up to first :
				$protocol = preg_replace('/[^a-zA-Z0-9\.]/', '', $matches[1]);
				if (!in_array($protocol, $protocolWhitelist)) {
					$node->parentNode->setAttribute($node->name, 'javascript:;');
					continue;
				}
			}

			$node->parentNode->setAttribute($node->name, $href);
		}
	}

	/**
	 * Modifies all A tags in the child nodes of the given DOMElement $element
	 * to include the rel="nofollow" attribute.
	 *
	 * @param DOMXPath $xpath An instance of DOMXPath.
	 * @param DOMElement $element An instance of DOMElement to set the context for replacement (usually set to the body tag of the document).
	 */
	public function AddRelNoFollow ($xpath, $element)
	{
		$this->SetAttributeOnMatches($xpath, $element, '//a', 'rel', 'nofollow');
	}

	/**
	 * A generic function for querying the DOM using XPath and setting an attribute on all matching blocks.
	 *
	 * @param DOMXPath $xpath An instance of DOMXPath.
	 * @param DOMElement $element An instance of DOMElement to set the context for replacement.
	 * @param string $xpathQuery A query string to pass to DOMXPath::query
	 * @param string $attributeName The name of the attribute to set.
	 * @param string $attributeValue The value of which to set the attribute to.
	 */
	public function SetAttributeOnMatches ($xpath, $element, $xpathQuery, $attributeName, $attributeValue)
	{
		$blocks = $xpath->query($xpathQuery, $element);
		foreach ($blocks as $block) {
			$block->setAttribute($attributeName, $attributeValue);
		}
	}
}
