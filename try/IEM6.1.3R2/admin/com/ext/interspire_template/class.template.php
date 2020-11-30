<?php
/**
 * This file contains the Interspire Template Engine
 *
 * This file contains the main InterspireTemplate class and also a few helper functions
 *
 * @author Jordie <jordie+code@interspire.com>
 *
 * @package Library
 * @subpackage InterspireTemplate
 */

/**
 * InterspireTemplate class
 *
 * InterspireTemplate is a cross application template engine. It takes in a template file, parses it and stores a cached PHP version.
 * When ever a template needs to be run again, the cached PHP version of the file is used instead, meaning a large performance increase
 * over parsing the template on each load. If a template file is modified, the template engine notices this with the filemtime() function
 * and clears the cached file and writes a new one.
 *
 * The syntax of the 'template code' is similar to 'smarty' (see: smarty.php.net) for familiarity, though no smarty PHP code is actually used.
 * Numerous smarty functions and features are not supported by this class.
 *
 * @package Library
 * @subpackage InterspireTemplate
 */

class InterspireTemplate
{
	/*
	 TODO:
		- Structures still to add:
			- {php}{/php} Allows inline PHP execution. Also add a toggle to allow/disable this.
	**/

	/**
	* Variables
	* This holds all template variables set via Assign(). The Get() function retrieves the values.
	*
	* @var String $Variables
	*
	* @see Assign
	* @see Get
	*/

	public $Variables;

	/**
	* TemplateData
	* This holds the current template data being parsed.
	*
	* @var String $TemplateData
	*
	* @see LoadTemplateFile
	* @see ParseTemplate
	* @see ParseSet
	* @see ParseVariables
	*/

	public $TemplateData;

	/**
	 * This allows for adding additional arguments to the ParseTemplate function call used when {template=""} is processed
	 *
	 * @see ParseIncludes
	 */
	public $appendToParseTemplateForIncluded = '';

	/**
	* TemplateFile
	* This holds the name of the template file to parse. It shouldn't have the file extension.
	*
	* @var String $TemplateFile
	*
	* @see LoadTemplateFile
	* @see GetTemplate
	* @see SetTemplate
	*/

	protected $TemplateFile;

	/**
	* TemplateFileExtension
	* This holds the file extension to be used. It is usually .html or .tpl
	*
	* @var String $TemplateFileExtension
	*
	* @see LoadTemplateFile
	* @see SetTemplatePath
	*/

	protected $TemplateFileExtension = 'html';

	/**
	* TemplatePath
	* This is the path to the directory where the template files reside. It should have a trailing slash.
	*
	* @var String $TemplatePath
	*
	* @see __construct
	* @see GetTemplate
	* @see SetTemplate
	*/

	protected $TemplatePath;

	/**
	* DisableEvents
	* Setting this to true will cause it to stop any events being triggered in the class
	*
	* @var Boolean $DisableEvents
	*
	* @see EventsAvailable
	*/
	public $DisableEvents = false;

	/**
	* AllowedFunctions
	* This is the array of function names that can be used in variable modifiers. e.g. {$var|functionName} where functionName is a valid
	* function in this array.
	*
	* @var Array $AllowedFunctions
	*
	* @see ParseVariables
	*/

	protected $AllowedFunctions = array(
									'addslashes',
									'stripslashes',
									'number_format',
									'dateformat',
									'errorimg',
									'sprintf',
									'round',
									'math',
									'ceil',
									'is_checked',
									'is_selected',
									'strtoupper',
									'strtolower',
									'strlen',
									'ucfirst',
									'stripspaces',
									'nl2br',
									'count',
								);

	/**
	 * Instance
	 * This static variable holds the current instance of this object being loaded.
	 * So using the getInstance function anywhere will return the very same instance.
	 *
	 * @var object Instance
	 */

	public static $Instance;

	/**
	 * The character set used for any escaping
	 *
	 * @var string
	 **/
	public $CharacterSet = 'UTF-8';

	/**
	* If this is true, whitespace between PHP tags will be removed from the parsed templates, otherwise it will not be removed.
	*
	* @var boolean
	*/
	protected $CleanWhiteSpace = true;

	/**
	 * The name of the function to call to get language values
	 *
	 * @var string
	 *
	 * @see ParseLanguageVariables
	 */
	protected $GetLangFunction = 'GetLang';

	/**
	 * The name of the function to call to get config values
	 *
	 * @var string
	 *
	 * @see ParseConfig
	 */
	protected $GetConfigFunction = 'GetConfig';

	/**
	 * The name of the function to call to get a helptip placeholder
	 * This overrides any {$help.foo} calls. To ignore this, set it to null.
	 *
	 * @var string
	 *
	 * @see Get
	 */
	protected $GetHelptipFunction = null;

	/**
	 * This changes whether or not values passed to the Assign() function are html escaped.
	 * We normally want this to be true for fornt-end templtes, but false for backend templates.
	 *
	 * @var string
	 *
	 * @see Assign
	 */
	public $DefaultHtmlEscape = true;

	/**
	 * When objects are called in the template using the {foreach} loop, you can require that a prefix is added to their name.
	 *
	 * @var string
	 *
	 * @see ParseForeach
	 */
	protected $ObjectPrefix = '';

	/**
	 * This is the path used when {include='file.php'} or {include_php='file.php'} is called
	 *
	 * @var string
	 *
	 * @see ParseIncludes
	 */
	protected $IncludePath = '';

	/**
	 * This is the path used to store the PHP cache files
	 *
	 * @var string
	 *
	 * @see ParseTemplate
	 */
	protected $CachePath ='';

	/**
	 * getInstance
	 * This is a static function that sets up the class instance and stores it to the static variable. It will then return that instantiation in the future.
	 *
	 * @return object Returns the instantiated object
	 */

	public static function getInstance()
	{
		if(!isset(self::$Instance)) {
			self::$Instance = new self();
		}
		return self::$Instance;
	}

	/**
	 * __construct
	 * This is the class constructor. By default it checks if its being run in IWP and if so set the default template path
	 *
	 * @return void Doesn't return anything.
	 */
	public function __construct(){
	}

	/**
	 * GetLangFunction
	 * Gets the name of the function that is currently set to be called when parsing the language variables
	 *
	 * @return void Doesn't return anything.
	 */
	public function GetLangFunction(){
		return $this->GetLangFunction;
	}

	/**
	 * SetLangFunction
	 * Sets the name of the function to call when parsing the language variables
	 *
	 * @return void Doesn't return anything.
	 */
	public function SetLangFunction($name){
		$this->GetLangFunction = $name;
	}

	/**
	* GetCleanWhiteSpace
	* Gets the current preference for removing whitespace between PHP tags in parsed templates
	*
	* @return boolean Returns true if white spaces will be removed from templates, otherwise false
	*/
	public function GetCleanWhiteSpace ()
	{
		return $this->CleanWhiteSpace;
	}

	/**
	* SetCleanWhiteSpace
	* Sets the current preference for removing whitespace between PHP tags in parsed templates
	*
	* @param boolean $value The preference for removing whitespace as a boolean. Set this to true to remove whitespaces between PHP tags in parsed templates.
	*/
	public function SetCleanWhiteSpace ($value)
	{
		if (is_bool($value)) {
			$this->CleanWhiteSpace = $value;
		}
	}

	/**
	 * SetTemplatePath
	 * This sets the directory to use when reading in template files. It sets the $TemplatePath variable with the path.
	 *
	 * @return void Doesn't return anything.
	 *
	 * @see TemplatePath
	 * @see GetTemplatePath
	 * @see GetExtension
	 */
	public function SetTemplatePath($dir, $ext=null){

		if(!is_null($ext)){
			$this->TemplateFileExtension = $ext;
		}
		if(lastchar($dir) !== '/'){
			$dir = $dir . '/';
		}
		$this->TemplatePath = $dir;
	}

	/**
	 * GetExtension
	 * This gets the extension for the current template file.
	 *
	 * @return string The extension for the template file.
	 *
	 * @see TemplateFileExtension
	 * @see SetTemplatePath
	 */
	public function GetExtension(){
		return $this->TemplateFileExtension;
	}

	/**
	 * GetCachePath
	 * This returns the current Cache Path
	 *
	 * @see IncludePath
	 * @see SetIncludePath
	 */
	public function GetCachePath(){
		return $this->CachePath;
	}

	/**
	 * SetCachePath
	 * This sets the current Cache Path
	 *
	 * @return string The path to use for the cache PHP files
	 *
	 * @see IncludePath
	 * @see GetIncludePath
	 */
	public function SetCachePath($path){
		$this->CachePath = $path;
	}


	/**
	 * GetIncludePath
	 * This returns the current Include Path
	 *
	 * @see IncludePath
	 * @see SetIncludePath
	 */
	public function GetIncludePath(){
		return $this->IncludePath;
	}

	/**
	 * SetIncludePath
	 * This sets the Include Path
	 *
	 * @return string The path to use for the {include} and {include_php} calls
	 *
	 * @see IncludePath
	 * @see GetIncludePath
	 */
	public function SetIncludePath($path){
		$this->IncludePath = $path;
	}

	/**
	 * AppendTemplatePath
	 * Adds a path as last priority to the list of template folders which are searched for template files.
	 *
	 * @see TemplatePath
	 * @see SetTemplatePath
	 * @see PrependTemplatePath
	 */
	public function AppendTemplatePath($path)
	{
		if (!empty($this->TemplatePath)) {
			$this->TemplatePath = $this->TemplatePath.';'.$path;
		} else {
			$this->SetTemplatePath($path);
		}
	}

	/**
	 * PrependTemplatePath
	 * Adds a path as first priority to the list of template folders which are searched for template files.
	 *
	 * @see TemplatePath
	 * @see SetTemplatePath
	 * @see AppendTemplatePath
	 */
	public function PrependTemplatePath($path)
	{
		if (!empty($this->TemplatePath)) {
			$this->TemplatePath = $path.';'.$this->TemplatePath;
		} else {
			$this->SetTemplatePath($path);
		}
	}

	/**
	 * GetTemplatePath
	 * This returns the TemplatePath variable
	 *
	 * @return string The path to the current template directory
	 *
	 * @see TemplatePath
	 * @see SetTemplatePath
	 */
	public function GetTemplatePath(){
		return $this->TemplatePath;
	}

	/**
	 * GetTemplate
	 * This returns the current TemplateFile value.
	 *
	 * @return String Returns the value of $TemplateFile
	 *
	 * @see TemplateFile
	 */
	public function GetTemplate(){
		return $this->TemplateFile;
	}

	/**
	 * CleanPreviousCache
	 * Removes any previosu cache files
	 *
	 * @return void
	 *
	 * @see ParseTemplate
	 */
	public function CleanPreviousCache($current){
		$regex = '#' .preg_quote($this->TemplateFile.'_', '#').'([0-9]*)'.preg_quote('_'.$this->TemplateFileExtension.'.html', '#') .'#i';
		$currentFile = $this->TemplateFile.'_'.$current.'_'.$this->TemplateFileExtension.'.html';
		if ($handle = opendir($this->CachePath)) {
			/* This is the correct way to loop over the directory. */
			while (false !== ($file = readdir($handle))) {

				if(preg_match($regex, $file) && $file != $currentFile){
					unlink($this->CachePath .'/'.$file);
				}
			}
			closedir($handle);
		}
	}

	/**
	 * ParseTemplate
	 * This is the master 'parsing' function. It reads in the template file and runs through all the parsing functions in order.
	 *
	 * @param String $name Template to be parsed
	 * @param Boolean $return Toggles whether the parsed template code will be echo'ed out directly or returned by the function.
	 *
	 * @return Mixed Can be either void or a String
	 *
	 * @see LoadTemplateFile
	 * @see ParseForeach
	 * @see ParseSet
	 * @see ParseIf
	 * @see CleanPreviousCache
	 * @see ParseVariables
	 * @see TemplateData
	 */

	public function ParseTemplate($name = null, $return = false, $input = null){

		if(!is_null($name)){
			$this->SetTemplate($name);
			$templateFile = $this->GetTemplateFilename();
		}else{
			$templateFile = false;
		}

		// $tpl is used in the template PHP code, we want to let it use this current template class
		$tpl = &$this;

		// Check that all the paths are set up correctly
		if(empty($this->CachePath) || is_null($this->CachePath)) {
			throw new InterspireTemplateException('Invalid cache path. A cache path must be specified before a template can be parsed.');
		}

		if(empty($this->TemplatePath) || is_null($this->TemplatePath)) {
			throw new InterspireTemplateException('Invalid template path. A valid template path must be specified pointing to the directory of the template files.');
		}

		// check the there is something to parse, either direct input or a template file.
		if (is_null($input) && !$templateFile) {
			throw new InterspireTemplateException('Nothing to parse. Either direct input is required or a valid template file must be specified.');
			return false;
		}

		$parseTemplateFile = true;

		if (is_null($input)) {
			$filemtime = filemtime($templateFile);
			$cacheFile = $this->CachePath . DIRECTORY_SEPARATOR . $name . '_' . $filemtime . '_' . $this->TemplateFileExtension . '.html';

			// if there is a cache file that exists we don't need to parse the original template file
			$parseTemplateFile = !file_exists($cacheFile);

		} else {
			$cacheFile = $this->CachePath . '/tmp' . rand(3000,90000000) . '.html';
		}

		if($parseTemplateFile){
			if ($this->EventBeforeUncachedTemplateParsed()) {
				if(is_null($input)) {
					$this->CleanPreviousCache($filemtime);
					$this->LoadTemplateFile();
				} else {
					$this->TemplateData = $input;
				}

				$this->StripTemplateComments();

				// run through all the template parsing functions
				$this->TemplateData = $this->ParseForeach($this->TemplateData); // foreach is recursive, so we need to use this method of calling it
				$this->ParseAlias();
				$this->ParseCapture();
				$this->ParseCycle();
				$this->ParseIncludes();
				$this->ParseSet();
				$this->ParseIf();
				$this->ParseConfig();
				$this->ParseLanguageVariables();
				$this->ParseVariables();

				if ($this->CleanWhiteSpace) {
					// this cleans up the code a bit, if there is a closing PHP tag and only whitespace between it and another opening PHP tag,
					// get rid of both of them and let the PHP 'continue'
					$this->TemplateData = preg_replace('#\? >[\n\s\t]*<\?php#sm', '', $this->TemplateData);
				}
			}

			$this->EventAfterUncachedTemplateParsed();

			if ($this->EventBeforeTemplateCached($cacheFile)) {
				file_put_contents($cacheFile, $this->TemplateData);
			}

			$this->EventAfterTemplateCached($cacheFile);
		}

		ob_start();

		if ($this->EventBeforeTemplateIncluded($cacheFile)) {
			include($cacheFile);
		}

		$this->EventAfterTemplateIncluded();
		$this->TemplateData = ob_get_contents();
		ob_end_clean();
		$this->EventTemplateCaptured();

		if (!is_null($input)) {
			@unlink($cacheFile);
		}

		if ($this->CleanWhiteSpace) {
			$this->TemplateData = preg_replace('#^\s+#m', '', $this->TemplateData);
			$this->TemplateData = preg_replace('#\s+$#m', '', $this->TemplateData);
			$this->TemplateData = preg_replace("#[ \t]+#", ' ', $this->TemplateData);
		}

		if($return){
			return $this->TemplateData;
		}else{
			echo $this->TemplateData;
		}
	}

	/**
	 * Prepend
	 * Shortcut function for calling Assign with the prepend method.
	 *
	 * @see iwp_template::Assign
	 *
	 * @param mixed $name
	 * @param mixed $value
	 * @param boolean $htmlescape
	 */
	public function Prepend ($name, $value, $htmlescape = null)
	{
		$this->Assign($name, $value, $htmlescape, -1);
	}

	/**
	 * Append
	 * Shortcut function for calling Assign with the append method.
	 *
	 * @see iwp_template::Assign
	 *
	 * @param mixed $name
	 * @param mixed $value
	 * @param boolean $htmlescape
	 */
	public function Append ($name, $value, $htmlescape = null)
	{
		$this->Assign($name, $value, $htmlescape, 1);
	}

	/**
	 * Assign
	 * This sets variables in the $Variables for use in the template files.
	 *
	 * <b>Example</b>
	 * $tpl->Assign('foo', 'value'); Sets: $Variables['foo'] = 'value'; Accessed by: {$foo}
	 * $tpl->Assign(array('foo', 'bar'), 'value'); Sets: $Variables['foo']['bar'] = 'value'; Accessed by: {$foo.bar}
	 * $tpl->Assign('foo', array('sub' => 'value')); Sets: $Variables['foo']['sub'] = 'value';  Accessed by: {$foo.sub}
	 * $tpl->Assign(array('foo', 'bar'), array('sub' => 'value')); Sets: $Variables['foo']['bar']['sub'] = 'value'; {$foo.bar.sub}
	 *
	 * @param $name Mixed The name of the variable to set. If it is an array, it will be used to detemine the depth.
	 * @param $value Mixed The value of the variable to set. Can be any standard PHP variable value (i.e. string, boolean, integer, array, object)
	 * @param Boolean $htmlescape Specify whether or not the templating system needs to escape the variable (Default to InterspireTemplate::$DefaultHtmlEscape)
	 * @param Integer $prependAppendReplace Specify if this assign call should replace, or prepend or append to the existing value(s). Default is 0 which replaces the existing value, specify < 0 to prepend to the existing value, specify > 0 to append to the existing value.
	 *
	 * @return Void Doesn't return anything
	 *
	 * @see Variables
	 * @uses InterspireTemplate::$DefaultHtmlEscape
	 */

	public function Assign ($name, $value, $htmlescape=null, $prependAppendReplace=0) {
		if ($htmlescape === null) {
			$htmlescape = $this->DefaultHtmlEscape;
		}

		if ($htmlescape === true) {
			if (is_array($value)) {
				$value = $this->htmlspecialchars_deep($value, ENT_QUOTES, $this->CharacterSet);
			} elseif (is_object($value)) {
				$value = $value;
			} else {
				$value = htmlspecialchars($value, ENT_QUOTES, $this->CharacterSet);
			}
		}

		if (!is_array($name)) {
			if($prependAppendReplace === 0){
				$this->Variables[$name] = $value;
				return;
			}

			$setme = &$this->Variables[$name];
		} else {
			$vars = &$this->Variables;
			foreach ($name as $k=>$v) {
				if (!isset($vars[$v])) {
					$vars[$v] = array();
				}

				if (is_array($vars)) {
					$vars = &$vars[$v];
				}
			}
			$setme = &$vars;
		}

		if($prependAppendReplace === 0){
			$setme = $value;			//	replace
		} else if ($prependAppendReplace < 0) {
			$setme = $value . $setme;	//	prepend
		} else if ($prependAppendReplace > 0) {
			$setme = $setme . $value;	//	append
		}
	}

	/**
	 * Get
	 * This retrieves the request variable from the $Variables.
	 *
	 * <b>Example</b>
	 * $tpl->Get('foo'); Gets: $Variables['foo']
	 * $tpl->Get('foo', 'bar'); Gets: $Variables['foo']['bar']
	 *
	 * @param String $args This function takes an unlimited number of arguements which specify the depth of the associative array.
	 *
	 * @return Mixed Returns the value of the variable which can be anything
	 *
	 * @see Variables
	 */
	public function Get(){
		$numargs = func_num_args();
		$args = func_get_args();

		if($numargs === 1){
			if($args[0] === "newLine"){
				return "\n";
			}

			if(isset($this->Variables[$args[0]])){
				return $this->Variables[$args[0]];
			}else{
				return false;
			}
		}else{

			// predefined special variables
			if($args[0] === "config" && !is_null($this->GetConfigFunction) && function_exists($this->GetConfigFunction)){
				return call_user_func($this->GetConfigFunction, $args[1]);
			}

			if($args[0] === "get"){
				// we're forcefully overridding the get value with the real get values to make
				// sure it wasn't altered elsewhere
				$this->Assign('get', $this->htmlspecialchars_deep($_GET));
			}

			if($args[0] === "tpl"){
				if(!isset($args[2])){
					return $this->$args[1];
				}else{
					$tmp = &$this->$args[1];
					if(firstchar($args[2]) === '$'){
						$args[2] = substr($args[2], 1);
						$sub = $this->Get($args[2]);
						return $tmp[$sub];
					}else{
						return $tmp[$args[2]];
					}
				}
			}

			if($args[0] === "help" && !is_null($this->GetHelptipFunction) && function_exists($this->GetHelptipFunction)){
				return call_user_func($this->GetHelptipFunction, $args[1], $this);
			}

			$vars = &$this->Variables;
			foreach($args as $k=>$v){
				if(!isset($vars[$v])){
					return false;
				}else{
					if (is_array($vars)){
						$vars = &$vars[$v];
					} elseif (is_object($vars)) {
						$vars = &$vars->$v;
					} else {
						return false;
					}
				}
			}
			return $vars;
		}
	}

	/**
	 * LoadTemplateFile
	 * This reads in the template file into the $TemplateData variable ready for parsing. It does not take any arguements, but instead
	 * used the TemplatePath and TemplateFile variables, so these must both be set before use.
	 *
	 * @return Void Doesn't return anything
	 *
	 * @see TemplateData
	 * @see TemplatePath
	 * @see TemplateFile
	 */
	public function LoadTemplateFile() {
		$fileName = $this->GetTemplateFilename();

		if ($fileName === false) {
			$this->TemplateData = '';
			return;
		}

		$this->TemplateData = file_get_contents($fileName);
	}

	/**
	 * GetTemplateFilename
	 * This searches through all the specified template folders in order of preference to find the template file. It will return the full path for the first match that it finds, false otherwise.
	 *
	 * @return mixed String if successful, false otherwise.
	 *
	 * @see TemplatePath
	 * @see TemplateFile
	 * @see TemplateFileExtension
	 * @see SetTemplatePath
	 * @see LoadTemplateFile
	 * @see ParseTemplate
	 */
	protected function GetTemplateFilename()
	{
		$paths = explode(';', $this->TemplatePath);
		foreach ($paths as $path) {
			if (file_exists($path . $this->TemplateFile . '.' . $this->TemplateFileExtension)) {
				return $path . $this->TemplateFile . '.' . $this->TemplateFileExtension;
			}
		}
		return false;
	}

	/**
	 * StripTemplateComments
	 * Removes all the comments from the template file. Comments look like:
	 * {* this is my comment *)
	 *
	 * @return Void Doesn't return anything
	 *
	 * @see TemplateData
	 */
	public function StripTemplateComments() {
		$this->TemplateData = preg_replace("#\{\*.*\*\}#iUsm","", $this->TemplateData);
	}

	/**
	 * Tpl2PhpVars
	 * This takes a template variable and returns the result. It checks if its a plain variable, an array or an object and
	 * returns the corresponding PHP code
	 *
	 * @return String The PHP version of the template variable
	 *
	 * @see AllowedFunctions
	 * @see ParseGetArray
	 */
	public function Tpl2PhpVars($value, $front='', $back=''){
		$arrBreakdown = explode('|', $value);
		$Begin = $End = '';
		$arrVar = explode('.', $arrBreakdown[0]);
		if(isset($arrBreakdown[1])) {

			// sorry, couldn't figure out the regex to do this
			// we need to split the functions by | but make sure we don't split on
			// |'s that are inside strings
			$newStr = '';
			$arrFunctions = array();
			$inQuotes = false;
			$currentQuote = '';
			$functionsPart = substr($value, strlen($arrBreakdown[0])+1);

			for ($i=0; $i<strlen($functionsPart); ++$i) {

				// are we starting a new string? If so, set the flag
				if($inQuotes === false && in_array($functionsPart{$i}, array("'", '"'))) {
					$currentQuote = $functionsPart{$i};
					$inQuotes = true;

				// are we closing a string?
				} elseif($inQuotes === true && @$functionsPart{$i-1} != '\\' && $functionsPart{$i} === $currentQuote) {
					$inQuotes = false;
				}

				// check to make sure its a valid function splitter thats not in quotes
				if($functionsPart{$i} === '|' && !$inQuotes) {
					$arrFunctions[] = $newStr;
					$newStr = '';
					continue;
				}

				$newStr .= $functionsPart{$i};
			}

			// add newStr as the last function, as normally an array entry is only inserted at |
			$arrFunctions[] = $newStr;

			foreach($arrFunctions as $key2=>$value2) {

				// get array of values to pass into the function
				$arrFunc = preg_split('#,\s*#', $value2, -1, PREG_SPLIT_NO_EMPTY);
				$funcName = $arrFunc[0];

				// if its not in the function whitelist, skip it
				if(!in_array(strtolower($funcName), $this->AllowedFunctions)){
					continue;
				}

				$Begin = $funcName.'('.$Begin;
				if(isset($arrFunc[1])){ // if there are any additional arguments to pass in
					array_shift($arrFunc);
					$args = '';
					foreach($arrFunc as $k=>$arg){
						if(firstchar($arg) === '$'){
							$arg = substr($arg,1);
							$arg = $this->ParseGetArray($arg);
							$args .= ', $tpl->Get('.$arg.')';
						}else{
							$args .= ', '.$arg;
						}
					}
					$End .= $args.')';
				}else{
					$End .= ')';
				}
			}
		}

		if(firstchar($arrBreakdown[0]) === '$'){
			// its a variable or array
			$arrBreakdown[0] = substr($arrBreakdown[0], 1);
			$value = $this->ParseGetArray($arrBreakdown[0]);
			$value = "\$tpl->Get(".$value.')';

		}elseif(firstchar($arrBreakdown[0]) === '%'){
			// its an object
			$arrBreakdown[0] = substr($arrBreakdown[0], 1);
			$arrObjBits = explode(':', $arrBreakdown[0]);
			$arrObj = explode('.', $arrObjBits[0]);
			$objName = $arrObj[0];
			$objFunc = $arrObj[1];
			$parseInValues = '';

			if(isset($arrObjBits[1])){
				$arrObjArgs = explode(',', $arrObjBits[1]);
				$valuelist = array();
				foreach ($arrObjArgs as $args_k=>$args_v){
					if(firstchar($args_v) === '$'){
						$args_v = $this->ParseGetArray(substr($args_v, 1));
					}
					$valuelist[] .= $args_v;
				}
				$parseInValues = '$tpl->Get(' . implode(',', $valuelist). ')';
			}

			$value = $this->ObjectPrefix . $objName .'::getInstance()->'.$objFunc.'('.$parseInValues.')';

		}elseif(firstchar($arrBreakdown[0]) === '@'){
			// its an object
			$arrBreakdown[0] = substr($arrBreakdown[0], 1);
			$arrObj = explode('.', $arrBreakdown[0]);
			$objName = $arrObj[0];
			$parseInValues = '';

			if(isset($arrBreakdown[1])) {
				$parseInValues = $arrBreakdown[1];
			}

			$value = $objName.'('.$parseInValues.')';

		}else{
			$value = "'" . $arrBreakdown[0] ."'";
		}

		return $Begin.$front.$value.$back.$End;
	}

	/**
	 * ParseForeach
	 * This parses the argument $TplData variable for any of the foreach loops. It then translates it into PHP code and replaces the original value.
	 * Other parse functions in this class directly modify the $TemplateData variable. This one doesn't due to the possibility of recursion.
	 *
	 * @param String $TplData The is the template code to parse for foreach loops
	 *
	 * @return String The code with the PHP foreach loops replacing the original code foreach's
	 *
	 * @see GetAttributes
	 */

	public function ParseForeach($TplData){
		$exit = false;
		$inString = false;
		$stringChar = '';

		while(strpos($TplData, '{foreach ') !== false && strpos($TplData, '{foreach ') < strpos($TplData, '{/foreach')) {

			$foreachStart = strpos($TplData, '{foreach ');
			$i = $foreachStart + strlen('{foreach ');

			$exit = false;
			$inString = false;
			$hasFirst = $hasRecursion = $hasTotal = $hasLast = $hasIteration = false;
			$stringChar = '';

			// get the string until the closing }, skipping the ones in strings/quotes: "foo}bar"
			while(!$exit){
				if(!$inString){
					// we're not in a quoted string, so any braces are considered 'real'
					if(substr($TplData, $i, 1) === '}'){
						// we found our real closing brace
						$exit = true;
						break;
					}
				}else{
					// we're in a string so don't even test for a brace
					if(substr($TplData, $i, 1) === $stringChar && substr($TplData, $i-1, 1) !== '\\') {
						// we've come out of our string
						$inString = false;
					}
				}
				++$i;
			}

			// the position of the begining of the code in the foreach
			$bodyStart = $i+1;

			// this is the entire starting foreach line, we want to replace this later
			$startingLineFull = substr($TplData, $foreachStart, $bodyStart-$foreachStart);

			// we want to get all the attributes for this foreach, so remove the braces and foreach word so we can grab them
			$startingLineAttributes = substr($startingLineFull,0, -1);
			$startingLineAttributes = substr($startingLineAttributes,strlen('{foreach '));
			$attributes = $this->GetAttributes($startingLineAttributes);

			// Get the PHP code for the from attribute's value. This is what we loop on.
			$ForeachFunctionVar = $this->Tpl2PhpVars($attributes['from']);

			$BodyStr = substr($TplData, $bodyStart);

			// do any recursive foreachs
			$parsedBodyStr = $this->ParseForeach($BodyStr);

			$inForeach = 0;
			// we now need to find the correct closign foreach for this loop, not any child loops
			for($x=$bodyStart;$x<strlen($TplData); ++$x){
				if(substr($TplData,$x,strlen('{foreach ')) === '{foreach '){
					++$inForeach;
				}
				if(substr($TplData,$x,strlen('{/foreach}')) === '{/foreach}'){
					if($inForeach > 0){
						--$inForeach;
					}else{
						// we found our closing foreach!!! =D
						break;
					}
				}
			}

			$endBodyPos = $x -0 +strlen('{/foreach}');
			$endBody = strpos($parsedBodyStr, '{/foreach}');
			$BodyStr = substr($parsedBodyStr, 0, $endBody);

			if(isset($attributes['id'])){
				$hasId = true;
				$loopID = $attributes['id'];
			}else{
				// the loop still need an id, so we give it something random
				$hasId = false;
				$loopID = mt_rand(23000,1000000);
				$loopID = "loop_".$loopID;
			}

			// because the foreach is a function, we need to make the function name unique
			$ForeachIDNumber = mt_rand(2386, 230686);

			// start our foreach stuff, set up the default vars like iteration, first, last etc. before the loop

			// now parse recursion, i.e. a function call to the same loop but with different arguements and output
			// whereever the {recursion} token is
			if(strpos($BodyStr, '{recursion') !== false){
				$hasRecursion = true;
				preg_match_all("#\{recursion=([a-zA-Z0-9|_\-:,\.\$\%]*)\}#is", $BodyStr, $matches);
				foreach($matches[0] as $recursion_key=>$recursion_val){
					$from = $attributes['from'];
					$arrFrom = explode(':', $from);
					$from = $arrFrom[0] .':'.$matches[1][$recursion_key];
					$BodyStr = str_replace($recursion_val, '<?php foreach_'.$ForeachIDNumber.'($tpl, '.$this->Tpl2PhpVars($from).'); ?>', $BodyStr);
				}
			}

			if($hasRecursion || $hasId){
				$phpCode = '<?php if(!function_exists("foreach_'.$ForeachIDNumber.'")){ function foreach_'.$ForeachIDNumber.'(&$tpl, $array){ ';
			}else{
				$phpCode = '<?php $array = '.$ForeachFunctionVar.'; ';
			}

			$hasIteration = (bool)( strpos($BodyStr, $loopID.'.iteration') !== false);
			$hasLast = (bool)( strpos($BodyStr, $loopID.'.last') !== false);
			$hasFirst = (bool)( strpos($BodyStr,$loopID.'.first') !== false);
			$hasTotal = (bool)( strpos($BodyStr,$loopID.'.total') !== false);

			if($hasIteration || $hasLast) {
				$phpCode .= '$tpl->Assign(array(\''.$loopID.'\', \'iteration\'), 0); ';
			}

			if($hasFirst) {
				$phpCode .= '$tpl->Assign(array(\''.$loopID.'\', \'first\'), true); ';
			}

			if($hasLast) {
				$phpCode .= '$tpl->Assign(array(\''.$loopID.'\', \'last\'), false); ';
			}

			if($hasTotal || $hasLast) {
				$phpCode .= '$tpl->Assign(array(\''.$loopID.'\', \'total\'), sizeof($array)); ';
			}

			$phpCode .= 'if(is_array($array) || is_object($array)): ';

			// while achieving the same thing
			$phpCode .= 'foreach($array as ';
			if(isset($attributes['key'])){
				$phpCode .= '$'.$attributes['key'].'=>$'.$attributes['item'].'';
			}else{
				$attributes['key'] = '__key';
				$phpCode .= '$__key=>$'.$attributes['item'];
			}
			$phpCode .= '): ';

			// other variables that are set inside the loop
			$phpCode .= '$tpl->Assign(\''.$attributes['key'].'\', $'.$attributes['key'].', false); ';
			$phpCode .= '$tpl->Assign(\''.$attributes['item'].'\', $'.$attributes['item'].', false); ';

			if($hasIteration || $hasLast){
				$phpCode .= '$tpl->Assign(array(\''.$loopID.'\', \'iteration\'), $tpl->Get(\''.$loopID.'\', \'iteration\')+1);';
			}

			if($hasLast){
				$phpCode .= 'if( $tpl->Get(\''.$loopID.'\',\'total\') ===  $tpl->Get(\''.$loopID.'\',\'iteration\')){ ';
				$phpCode .= '$tpl->Assign(array(\''.$loopID.'\',\'last\'), true);';
				$phpCode .= '}';
			}

			$phpCode .= ' ?>';

			// foreach else is run if the array used for the foreach is empty
			if(strpos($BodyStr, '{foreachelse}') !== false){
				$arrBody = explode('{foreachelse}', $BodyStr);
				$BodyStr = $arrBody[0];
				$BodyElseStr = $arrBody[1];
				$newPhpCode = '<?php ';

				if($hasTotal || $hasLast){
					$newPhpCode .= '$tpl->Assign(array(\''.$loopID.'\', \'total\'), @sizeof('.$ForeachFunctionVar.'));';
				}

				$newPhpCode .=  'if(!is_array('.$ForeachFunctionVar.') || @sizeof('.$ForeachFunctionVar.') === 0 ){
								?> '. $BodyElseStr. ' <?php
							}else{  ?>'. $phpCode . $BodyStr . ' <?php ';
				$phpCode = $newPhpCode;
				if($hasFirst){
					$phpCode .= '$tpl->Assign(array(\''.$loopID.'\', \'first\'), false);';
				}

				if($hasRecursion || $hasId){
					$phpCode .= 'endforeach; endif;  }} foreach_'.$ForeachIDNumber.'($tpl, '.$ForeachFunctionVar.'); } ?> ';
				}else{
					$phpCode .= 'endforeach; endif; } ?> ';
				}
			}else{
				$phpCode .= $BodyStr . ' <?php ';
				if($hasFirst){
					$phpCode .= '$tpl->Assign(array(\''.$loopID.'\', \'first\'), false);';
				}

				if($hasRecursion || $hasId){
					$phpCode .= 'endforeach; endif;}} foreach_'.$ForeachIDNumber.'($tpl, '.$ForeachFunctionVar.'); ?>';
				}else{
					$phpCode .= 'endforeach; endif; ?>';
				}
			}

			// done output the PHP code instead of the template loop
			$TplData = substr($TplData,0,$foreachStart). $phpCode . substr($TplData,$endBodyPos);
		}

		$TplData = str_replace('{breakForeach}', '<?php break; ?>', $TplData);
		$TplData = str_replace('{continueForeach}', '<?php continue; ?>', $TplData);

		return $TplData;
	}

	/**
	 * ParseSet
	 * This parses the $TemplateData variable for any possible variable setters in the template code. i.e. {assign varName="value"}
	 * It then generates the PHP which uses the Assign function to set the variables for use in the template.
	 *
	 * @return Void Doesn't return anything
	 *
	 * @see TemplateData
	 * @see Assign
	 */

	public function ParseSet(){
		preg_match_all('#\{assign ([a-zA-Z0-9]+)=(true|false|["\'].+["\'])}#isU', $this->TemplateData, $matches);

		foreach($matches[0] as $k=>$set){
			$setName = $matches[1][$k];
			$setValue = $matches[2][$k];
			$setPHP = '';
			if(firstchar($setValue) === lastchar($setValue) && in_array(lastchar($setValue), array("'", '"'))){
				$quote = firstchar($setValue);
				$newSetValue = substr($setValue, 1);
				$newSetValue = substr($newSetValue, 0, -1);
				$Begin = '';
				$End = '';

				if(firstchar($newSetValue) === '$'){
					$newSetValue = substr($newSetValue, 1);
					$arrBreakdown = explode('|', $newSetValue);

					$arrVar = explode('.', $arrBreakdown[0]);
					if(isset($arrBreakdown[1])){
						$arrFunctions = explode(':', $arrBreakdown[1]);
						foreach($arrFunctions as $key2=>$value2){
							$arrFunc = preg_split('#,\s*#', $value2, -1, PREG_SPLIT_NO_EMPTY);

							$funcName = $arrFunc[0];
							if(!in_array(strtolower($funcName), $this->AllowedFunctions)){
								continue;
							}
							$Begin = $funcName.'('.$Begin;

							if(isset($arrFunc[1])){
								array_shift($arrFunc);
								$args = '';
								foreach($arrFunc as $k=>$arg){
									$args .= ', '.$arg;
								}
								$End .= $args.')';
							}else{
								$End .= ')';
							}
						}
					}
					$newSetValue = implode("','", $arrVar);
					$newSetValue = $Begin."\$tpl->Get('".$newSetValue."')".$End;
				}else{
					$newSetValue = $quote.$newSetValue.$quote;
				}
				$setPHP = '<?php $tpl->Assign(\''.$setName.'\', '.$newSetValue.'); ?>';
			}else{
				if($setValue !== "true" && $setValue !== "false"){
					$setPHP = '';
				}else{
					if($setValue === "true"){
						$setPHP = '<?php $tpl->Assign(\''.$setName.'\', true); ?>';
					}else{
						$setPHP = '<?php $tpl->Assign(\''.$setName.'\', false); ?>';
					}
				}
			}
			$this->TemplateData = str_replace($set, $setPHP, $this->TemplateData);
		}
	}

	/**
	 * ParseGetArray
	 * This takes an arugment that should be a plan variable/array and output the resulting PHP for it
	 * @param string $vars Takes a string of a template variable
	 *
	 * @return string Doesn't return anything
	 */
	public function ParseGetArray($vars){
		$arrVars = explode('.', $vars);
		$arrVars = CleanArray($arrVars);
		$newVars = array();
		foreach($arrVars as $key=>$value){
			if(firstchar($value) === '$'){
				$value = substr($value, 1);
				$newVars[] = '$tpl->Get("'.$value.'")';
			}else{
				$newVars[] = "'".$value."'";
			}
		}
		return implode(",", $newVars);
	}

	/**
	 * ParseIf
	 * This parses the $TemplateData variable for any possible 'if' constructs.
	 * It also looks for elseif, else and if and replaces with the corresponding PHP code.
	 *
	 * @return Void Doesn't return anything
	 *
	 * @see TemplateData
	 */

	public function ParseIf(){
		preg_match_all('#\{if ([^}]*)}#is', $this->TemplateData, $matches);
		foreach($matches[1] as $k=>$if){
			preg_match_all('#[\$%]([a-zA-Z0-9\$_\.]+)#is', $if, $vars);
			foreach($vars[0] as $k2=>$varName){
				$if = str_replace($vars[0][$k2], $this->Tpl2PhpVars($varName), $if);
			}
			$this->TemplateData = str_replace($matches[0][$k], '<?php if('.$if.'): ?>', $this->TemplateData);
		}

		preg_match_all('#\{elseif ([^}]*)}#is', $this->TemplateData, $matches);
		foreach($matches[1] as $k=>$if){
			preg_match_all('#\$([a-zA-Z0-9\$_\.]+)#is', $if, $vars);
			foreach($vars[1] as $k2=>$varName){
				$if = str_replace($vars[0][$k2], '$tpl->Get('.$this->ParseGetArray($varName).')', $if);
			}
			$this->TemplateData = str_replace($matches[0][$k], '<?php elseif('.$if.'): ?>', $this->TemplateData);
		}

		$this->TemplateData = str_replace('{else}', '<?php else: ?>', $this->TemplateData);
		$this->TemplateData = str_replace('{/if}', '<?php endif; ?>', $this->TemplateData);
	}

	/**
	 * ParseConfig
	 * This parses the $TemplateData for any language variables and calls the callback function specified in GetConfigFunction
	 *
	 * @return Void Doesn't return anything
	 *
	 * @see TemplateData
	 * @see AllowedFunctions
	 */

	public function ParseConfig(){
		preg_match_all('#\{\$config\.([^}]*)\}#is', $this->TemplateData, $matches);

		foreach($matches[1] as $regexKey=>$value){
			$this->TemplateData = str_replace($matches[0][$regexKey], '<?php echo '.$this->GetConfigFunction.'(\''.$value.'\'); ?>', $this->TemplateData);
		}
	}

	/**
	 * ParseIncludes
	 * This parses the $TemplateData variable for any possible {template=""} or {include=""} or {include_php=""}
	 *
	 * @return Void Doesn't return anything
	 *
	 * @see TemplateData
	 */

	public function ParseIncludes(){

		preg_match_all('#\{template="([^"]*)"}#is', $this->TemplateData, $matches);
		foreach($matches[1] as $k=>$tplfile){

			if(firstchar($tplfile) === '$'){
				$tplfile = $this->ParseGetArray($tplfile);
			}else{
				$tplfile = '"'.$tplfile.'"';
			}
			$replace = '<?php $tmpTplFile = $tpl->GetTemplate();
			$tmpTplData = $tpl->TemplateData;
			$tpl->ParseTemplate(' . $tplfile . $this->appendToParseTemplateForIncluded . ');
			$tpl->SetTemplate($tmpTplFile);
			$tpl->TemplateData = $tmpTplData; ?>';
			$this->TemplateData = str_replace($matches[0][$k], $replace, $this->TemplateData);
		}

		preg_match_all('#\{include="([^"]*)"}#is', $this->TemplateData, $matches);
		foreach($matches[1] as $k=>$tplfile){
			if(empty($this->IncludePath) || is_null($this->IncludePath)) {
				throw new InterspireTemplateException('Invalid include path. An include path must be set before {include} tokens can be used.');
			}
			if(firstchar($tplfile) === "/"){
				$data = file_get_contents($tplfile);
			}elseif(strpos($tplfile, "/") !== false) {
				$data = file_get_contents($this->IncludePath . '/'. $tplfile);
			}else{
				$data = file_get_contents($this->TemplatePath . '/'. $tplfile);
			}

			$data = str_replace('<?', '&lt;?', $data);
			$data = str_replace('{', '&#123;', $data);
			$data = str_replace('}', '&#125;', $data);
			$this->TemplateData = str_replace($matches[0][$k], $data, $this->TemplateData);
		}

		preg_match_all('#\{include_php="([^"]*)"}#is', $this->TemplateData, $matches);
		foreach($matches[1] as $k=>$tplfile){
			if(firstchar($tplfile) === "/"){
				$path = $tplfile;
			}elseif(strpos($tplfile, "/") === false) {
				$path = $this->IncludePath . '/'. $tplfile;
			}else{
				$path = $this->TemplatePath . '/'. $tplfile;
			}

			$this->TemplateData = str_replace($matches[0][$k], '<?php include(\''.$path.'\'); ?>', $this->TemplateData);
		}

	}

	/**
	 * ParseLanguageVariables
	 * This parses the $TemplateData for any language variables and calls the callback function specified in GetLangFunction
	 *
	 * @return Void Doesn't return anything
	 *
	 * @see TemplateData
	 * @see AllowedFunctions
	 */

	public function ParseLanguageVariables(){
		preg_match_all('#\{\$lang\.([^}]*)\}#is', $this->TemplateData, $matches);

		foreach($matches[1] as $regexKey=>$value){
			$value = $this->Tpl2PhpVars($value, $this->GetLangFunction.'(', ')');
			$this->TemplateData = str_replace($matches[0][$regexKey], '<?php echo '.$value.'; ?>', $this->TemplateData);
		}
	}

	/**
	 * ParseVariables
	 * This parses the $TemplateData for any variables that need to be echo'ed out.
	 * It changes a {$var} to <?php echo $tpl->Get('var'); ? >
	 * It then looks for any modifier functions that are allowed by $AllowedFunctions and applies then to the variable value
	 *
	 * @return Void Doesn't return anything
	 *
	 * @see TemplateData
	 * @see AllowedFunctions
	 */
	public function ParseVariables(){
		preg_match_all('#\{([\$%@][^}]*)\}#is', $this->TemplateData, $matches);

		foreach($matches[1] as $regexKey=>$value){
			$varReturn = $this->Tpl2PhpVars($value);
			$this->TemplateData = str_replace($matches[0][$regexKey], '<?php echo '.$varReturn.'; ?>', $this->TemplateData);
		}
	}

	/**
	 * ParseAlias
	 * This parses the $TemplateData for any {alias ...} directives.
	 * It can be used to shorten a long variable down to a shorter one, e.g. {alias name=newvar from=$large.number.of.nested.arrays}
	 *
	 * @return Void Doesn't return anything.
	 *
	 * @see TemplateData
	 */
	public function ParseAlias(){
		preg_match_all('#\{alias\s([^}]*)\}#is', $this->TemplateData, $matches);
		foreach($matches[1] as $regexKey=>$value) {
			$attributes = $this->GetAttributes($value);
			$varReturn = $this->Tpl2PhpVars($attributes['from']);
			$this->TemplateData = str_replace($matches[0][$regexKey], '<?php $tpl->Assign("' . $attributes['name'] . '", ' . $varReturn . '); ?>', $this->TemplateData);
		}
	}

	/**
	 * ParseCapture
	 * This looks for the capture tags and it takes the output of what is between it and assigns it to a variable.
	 * If the trim value is passed in as true then the value is trimmed allowing for neat coding in the template.
	 *
	 * @return Void Doesn't return anything
	 *
	 * @see TemplateData
	 */
	public function ParseCapture(){
		preg_match_all('#\{capture name=([^ ]*)([ ]*trim=(true|false))?([ ]*append=(true|false))?\}(.*)\{/capture\}#ismU', $this->TemplateData, $matches);
		foreach($matches[0] as $key=>$value){
			if($matches[3][$key] === "true" || $matches[3][$key] === true){
				// trim = true
				$new = '<?php ob_start(); ?>'.$matches[6][$key].'<?php $tpl->Assign("'.$matches[1][$key].'", ';

				if($matches[5][$key] === 'true' || $matches[5][$key] === true){
					// append = true
					$new .= '$tpl->Get("'.$matches[1][$key].'") . ';
				}
				$new .= 'trim(ob_get_contents())); ob_end_clean(); ?>';
			}else{
				$new = '<?php ob_start(); ?>'.$matches[6][$key].'<?php $tpl->Assign("'.$matches[1][$key].'", ';
				if($matches[5][$key] === 'true' || $matches[5][$key] === true){
					// append = true
					$new .= '$tpl->Get("'.$matches[1][$key].'") . ';
				}
				$new .= 'ob_get_contents()); ob_end_clean(); ?>';
			}
			$this->TemplateData = str_replace($value, $new , $this->TemplateData);
		}
	}

	/**
	 * ParseCycle
	 * Looks for and replaces cycle with PHP code.
	 * Cycle is used only in foreach loops and is used to alternate between values passed in. e.g. alternating table row colors/classes
	 *
	 * @return Void Doesn't return anything
	 *
	 * @see TemplateData
	 */
	public function ParseCycle(){
		preg_match_all('#\{cycle([^}]*)}#ismU', $this->TemplateData, $matches);
		foreach($matches[0] as $key=>$value){
			$attr = $this->GetAttributes($matches[1][$key]);
			if(substr($attr['values'],0,1) === substr($attr['values'],-1) && in_array(substr($attr['values'],-1), array('"', "'"))){
				$attr['values'] = substr($attr['values'], 1);
				$attr['values'] = substr($attr['values'], 0, -1);
			}
			$cycleValues = explode(',', $attr['values']);
			$rand = mt_rand();
			$arrName = "GLOBALS['cycle".$rand."']";
			$counterName = "GLOBALS['counter".$rand."']";
			$code = '<?php if(!isset($'.$arrName.') || !is_array($'.$arrName.')){ $'.$arrName.' = array(\'';
			$code .= implode("', '", $cycleValues);
			$code .= '\');';
			$code .= ' $'.$counterName.' = 0;} ';
			$code .= ' echo $'.$arrName.'[$'.$counterName.']; ++$'.$counterName.';';
			$code .= ' if(sizeof($'.$arrName.')  === $'.$counterName.') { $'.$counterName.' = 0; } ?>';

			$this->TemplateData = str_replace($value, $code , $this->TemplateData);
		}
	}

	/**
	 * GetAttributes
	 * This takes a line that is from a foreach or other line that has attributes with name value pairs and translates it into an array
	 * The string passed in must only include the name vlaue pairs, the {foreach and } etc. must be stripped out beforehand
	 * e.g. takes in: id="myLoop" iteration=5 foo=bar and returns array('id'=>'myLoop', 'iteration'=>5, 'foo'=>'bar');
	 *
	 * @return Array Returns the array of attributes with the names as the key's
	 */

	public function GetAttributes($conditionLine){
		$conditionLine = trim($conditionLine);
		$tmp = explode(' ', $conditionLine);
		$tmp = CleanArray($tmp);
		foreach($tmp as $key=>$value){
			$tmp2 = explode('=', trim($value));
			$tmp2[0] = trim($tmp2[0]);
			$tmp2[1] = trim($tmp2[1]);
			$attributes[$tmp2[0]] = $tmp2[1];
		}
		return $attributes;
	}

	/**
	 * SetTemplate
	 * This takes a string that is a filename and sets the $TemplateFile class variable with that value.
	 *
	 * @return Void Doesn't return anything
	 *
	 * @see TemplateFile
	 */

	public function SetTemplate($tplfile){
		if(strlen($tplfile) > 0){
			$this->TemplateFile = $tplfile;
		}
	}

	public function htmlspecialchars_deep($vals, $quotes, $charset){
		foreach($vals as $k=>$v){
			if(is_array($v)){
				$vals[$k] = $this->htmlspecialchars_deep($v, $quotes, $charset);
			}else{
				$vals[$k] = htmlspecialchars($v, $quotes, $charset);
			}
		}
		return $vals;
	}

	/**
	 * Cache variable for an otherwise (potentially) expensive class_exists call. Do not access directly, rather call EventsAvailable() instead.
	 *
	 * @var Boolean
	 */
	protected $InterspireEventClassExists = null;

	/**
	 * Returns TRUE if the InterspireEvent classes are available to use.
	 *
	 * @return Boolean
	 */
	protected function EventsAvailable ()
	{
		if ($this->DisableEvents === true) {
			return false;
		}

		if ($this->InterspireEventClassExists === null) {
			$this->InterspireEventClassExists = class_exists('InterspireEvent') && class_exists('InterspireEventData');
		}

		return $this->InterspireEventClassExists;
	}

	/**
	 * Triggers the BeforeUncachedTemplateParsed event.
	 *
	 * @return Boolean If the event was cancelled (assuming the event is cancellable).
	 */
	protected function EventBeforeUncachedTemplateParsed ()
	{
		if (!$this->EventsAvailable()) {
			return true;
		}

		return InterspireEvent::trigger('InterspireTemplate_BeforeUncachedTemplateParsed', new InterspireTemplateEventBeforeUncachedTemplateParsed($this));
	}

	/**
	 * Triggers the AfterUncachedTemplateParsed event.
	 *
	 * @return Boolean If the event was cancelled (assuming the event is cancellable).
	 */
	protected function EventAfterUncachedTemplateParsed ()
	{
		if (!$this->EventsAvailable()) {
			return true;
		}

		return InterspireEvent::trigger('InterspireTemplate_AfterUncachedTemplateParsed', new InterspireTemplateEventAfterUncachedTemplateParsed($this));
	}

	/**
	 * Triggers the BeforeTemplateCached event.
	 *
	 * @param String $cacheFile Name of the cache file which will be cached, passed by reference.
	 * @return Boolean If the event was cancelled (assuming the event is cancellable).
	 */
	protected function EventBeforeTemplateCached (&$cacheFile)
	{
		if (!$this->EventsAvailable()) {
			return true;
		}

		return InterspireEvent::trigger('InterspireTemplate_BeforeTemplateCached', new InterspireTemplateEventBeforeTemplateCached($this, $cacheFile));
	}

	/**
	 * Triggers the AfterTemplateCached event.
	 *
	 * @param String $cacheFile Name of the cache file which was cached.
	 * @return Boolean If the event was cancelled (assuming the event is cancellable).
	 */
	protected function EventAfterTemplateCached ($cacheFile)
	{
		if (!$this->EventsAvailable()) {
			return true;
		}

		return InterspireEvent::trigger('InterspireTemplate_AfterTemplateCached', new InterspireTemplateEventAfterTemplateCached($this, $cacheFile));
	}

	/**
	 * Triggers the BeforeTemplateIncluded event.
	 *
	 * @param String $cacheFile Name of the cache file to be included, passed by reference.
	 * @return Boolean If the event was cancelled (assuming the event is cancellable).
	 */
	protected function EventBeforeTemplateIncluded (&$cacheFile)
	{
		if (!$this->EventsAvailable()) {
			return true;
		}

		return InterspireEvent::trigger('InterspireTemplate_BeforeTemplateIncluded', new InterspireTemplateEventBeforeTemplateIncluded($this, $cacheFile));
	}

	/**
	 * Triggers the AfterTemplateIncluded event.
	 *
	 * @return Boolean If the event was cancelled (assuming the event is cancellable).
	 */
	protected function EventAfterTemplateIncluded ()
	{
		if (!$this->EventsAvailable()) {
			return true;
		}

		return InterspireEvent::trigger('InterspireTemplate_AfterTemplateIncluded', new InterspireTemplateEventAfterTemplateIncluded($this));
	}

	/**
	 * Triggers the TemplateCaptured event.
	 *
	 * @return Boolean If the event was cancelled (assuming the event is cancellable).
	 */
	protected function EventTemplateCaptured ()
	{
		if (!$this->EventsAvailable()) {
			return true;
		}

		return InterspireEvent::trigger('InterspireTemplate_TemplateCaptured', new InterspireTemplateEventTemplateCaptured($this));
	}
}

/*
 These are helper functions that are declared only if they're not within the application itself already
 */

if(!function_exists('is_selected')){
	/**
	 * is_selected
	 * This takes two arguements and outputs blank or selected.
	 * One argument should be the value of the field and the other is the value to test against (e.g. Input or DB info) to
	 * determine if the item is selected or not.
	 * The third argument inverts the result.
	 *
	 * @param mixed $one Any value to compare against $two
	 * @param mixed $two Any value to compare against $one
	 * @param boolean $not Inverts the return result
	 *
	 * @return string Can be 'selected' or blank
	 */
	function is_selected($one, $two, $not=false){
		if(trim($one) == ''){
			$one = null;
		}
		if(trim($two) == ''){
			$two = null;
		}
		$return = null;
		if($one == $two){
			$return = true;
		}else{
			$return = false;
		}

		if($not === true){
			$return = !$return;
		}

		if($return === true){
			return 'selected';
		}else{
			return '';
		}
	}
}

if(!function_exists('is_checked')){
	/**
	 * is_checked
	 * This takes two arguements and outputs blank or 'checked'.
	 * One argument should be the value of the field and the other is the value to test against (e.g. Input or DB info) to
	 * determine if the item is checked or not.
	 * The third argument inverts the result.
	 *
	 * @param mixed $one Any value to compare against $two
	 * @param mixed $two Any value to compare against $one
	 * @param boolean $not Inverts the return result
	 *
	 * @return string Can be 'checked' or blank
	 */
	function is_checked($one, $two, $not=false){
		if(trim($one) == ''){
			$one = null;
		}
		if(trim($two) == ''){
			$two = null;
		}

		$return = null;
		if($one == $two){
			$return = true;
		}else{
			$return = false;
		}

		if($not === true){
			$return = !$return;
		}

		if($return === true){
			return 'checked="checked"';
		}else{
			return '';
		}
	}
}


if(!function_exists('firstchar')){
	/**
	 * Returns the first character of a string
	 *
	 * @param string $str The string to get the first character from
	 * @return string A one character string
	 */
	function firstchar($str){
		return substr($str, 0, 1);
	}
}

if(!function_exists('lastchar')){
	/**
	 * Returns the last character of a string
	 *
	 * @param string $str The string to get the last character from
	 * @return string A one character string
	 */
	function lastchar($str){
		return substr($str, -1, 1);
	}
}

if(!function_exists('strreplace')){

	/**
	 * An alias for str_replace but with the arguements reordered for use in the template class
	 * The template class passes the value in as the first field to any function call.
	 *
	 * @param string $str The string to work with
	 * @param string $from The value to be replaced
	 * @param string $to The value to replace $from with
	 * @return string The resulting string with any replacements
	 */
	function strreplace($str, $from, $to){
		return str_replace($from, $to, $str);
	}
}

if(!function_exists('dateformat')){
	/**
	 * An alias for the date() function but with the arguements in reverse order for use in the template class.
	 * The template class passes the value in as the first field to any function call.
	 *
	 * @param string $date The date to be used, or if blank the current date
	 * @param string $format The format to be used when outputting, uses the standard date function formatting, www.php.net/date
	 * @return string A formatted date/time string
	 */
	function dateformat($date=false, $format){
		if($date !== false){
			return date($format, $date);
		}else{
			return date($format);
		}
	}
}

if(!function_exists('CleanArray')){
	/**
	 * Loops over the passed in array and removes any elements that are blank returning an array with only real elements.
	 *
	 * @param array $arr The array to clean out empty elements
	 * @return array A clean array without any empty elements
	 */
	function CleanArray($arr){
		$new = array();
		foreach($arr as $key=>$val){
			if($val != ''){
				$new[$key] = $val;
			}
		}
		return $new;
	}
}
if(!function_exists('ErrorImg')){
	/**
	 * A function to determine what image to show based on the passed in error code
	 *
	 * @param integer $type The error code to check with
	 * @return string The image that corresponds to the error code
	 */
	function ErrorImg($type){
		switch ($type) {
		case MSG_ERROR:
			return "images/error.gif";
			break;
		case MSG_SUCCESS:
			return "images/success.gif";
			break;
		case MSG_INFO:
			return "images/info.gif";
			break;
		case MSG_WARNING:
		default:
			return "images/warning.gif";
		}
	}
}

if(!function_exists('stripspaces')){
	/**
	 * Strips any and all spaces from the string passed in
	 *
	 * @param string $str String to have spaces removed from.
	 * @return string A string without any spaces
	 */
	function stripspaces($str){
		return str_replace(' ', '', $str);
	}
}

if(!function_exists('math')){
	/**
	 * This function allows mathematical calculations to be done in the template code.
	 *
	 * @param float $first The number to be used inside the calculation
	 * @param string $function The mathematical equation to run with using $first in the place of a placeholder %s
	 * @return mixed The result of the equation being run.
	 */
	function math($first , $function){
		$return = '';
		$function = preg_replace('#[^0-9 \(\)\.,\+/\*s\%\-]*#s', '', $function);
		$math = sprintf($function, $first);
		eval('$return = '.$math.';');
		return $return;
	}
}

if (class_exists('InterspireEvent') && class_exists('InterspireEventData')) {

	class InterspireTemplateEventBeforeUncachedTemplateParsed extends InterspireEventData
	{
		public $template;

		public function __construct (&$template)
		{
			$this->template = &$template;
			parent::__construct();
		}
	}

	class InterspireTemplateEventAfterUncachedTemplateParsed extends InterspireEventData
	{
		public $template;

		public function __construct (&$template)
		{
			$this->template = &$template;
			parent::__construct(false);
		}
	}

	class InterspireTemplateEventBeforeTemplateCached extends InterspireEventData
	{
		public $template;

		public function __construct (&$template)
		{
			$this->template = &$template;
			parent::__construct();
		}
	}

	class InterspireTemplateEventAfterTemplateCached extends InterspireEventData
	{
		public $template;
		public $cacheFile;

		public function __construct (&$template, &$cacheFile)
		{
			$this->template = &$template;
			$this->cacheFile = &$cacheFile;
			parent::__construct(false);
		}
	}

	class InterspireTemplateEventBeforeTemplateIncluded extends InterspireEventData
	{
		public $template;
		public $cacheFile;

		public function __construct (&$template, &$cacheFile)
		{
			$this->template = &$template;
			$this->cacheFile = &$cacheFile;
			parent::__construct();
		}
	}

	class InterspireTemplateEventAfterTemplateIncluded extends InterspireEventData
	{
		public $template;

		public function __construct (&$template)
		{
			$this->template = &$template;
			parent::__construct(false);
		}
	}

	class InterspireTemplateEventTemplateCaptured extends InterspireEventData
	{
		public $template;

		public function __construct (&$template)
		{
			$this->template = &$template;
			parent::__construct(false);
		}
	}

}

/**
 * This is the excpetion that is called when there is an error in the template class
 *
 * @see InterspireTemplate
 */

class InterspireTemplateException extends Exception { }
