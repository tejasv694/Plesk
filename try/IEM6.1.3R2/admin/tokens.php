<?php
/**
 * Server-side file used to retrieve arbitrary language tokens through AJAX.
 *
 * @see Application.Modules.Language (JavaScript)
 *
 * @package interspire.iem
 */

// Make sure that the IEM controller does NOT redirect request.
define('IEM_NO_CONTROLLER', true);

// Include the index file
require_once dirname(__FILE__) . '/index.php';

/**
 * TokenFactory
 * This class manages the retrieval of language tokens from nominated files.
 *
 * @package SendStudio
 */
class TokenFactory
{
	/**
	 * The path to look for language token files.
	 */
	private $base_path;

	/**
	 * __construct
	 * Sets the base path where to look for language token files.
	 *
	 * @return Void Does not return anything.
	 */
	public function __construct()
	{
		$lang_folder = IEM_PATH . '/language';
		$user_lang_folder = 'default';

		// ----- Get user language preference
			$user = GetUser();
			$temp = $user->user_language;

			if (!empty($temp) && is_dir("{$lang_folder}/{$user_lang_folder}")) {
				$user_lang_folder = $temp;
			}

			unset($temp);
			unset($user);
		// -----

		$this->base_path = "{$lang_folder}/{$user_lang_folder}";
	}

	/**
	 * LookIn
	 * Calls require to load the langauge files to look for language tokens in.
	 */
	public function LookIn($files)
	{
		if (!is_array($files)) {
			return false;
		}
		$files = array_unique($files);
		foreach ($files as $filename) {
			if ($this->_Valid($filename)) {
				require_once($this->base_path . '/' . $filename);
			}
		}
		return true;
	}

	/**
	 * GetTokens
	 * Returns the language token values. Invalid tokens will have the value of
	 * the empty string.
	 *
	 * @param Array $tokens The list of language tokens without the preceing 'LNG_'
	 *
	 * @return Array The list of language token values in the form array($token => $value);
	 */
	public function GetTokens($tokens)
	{
		if (!is_array($tokens)) {
			return false;
		}
		$token_values = array();
		foreach ($tokens as $token) {
			$val = GetLang($token, '');
			$token_values[$token] = $val;
		}
		return $token_values;
	}

	/**
	 * _Valid
	 * Checks to see if the given file name is a valid language file to
	 * include.
	 *
	 * @param String $filename The file name to test
	 */
	private function _Valid($filename)
	{
		if (strpos($filename, '..') !== false) {
			return false;
		}
		return (is_file($this->base_path . '/' . $filename));
	}
}

header('Content-type: application/json');

$factory = new TokenFactory();
if ($factory->LookIn($_POST['files'])) {
	$tokens = $factory->GetTokens($_POST['tokens']);
	$response = array(
		'status' => 'OK',
		'tokens' => GetJSON($tokens)
		);
	echo GetJSON($response);
} else {
	echo GetJSON(array('status' => 'Failed'));
}
