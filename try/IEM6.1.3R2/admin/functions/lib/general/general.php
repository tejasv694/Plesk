<?php
/**
* This file has some very basic functions in it. Generic functions are in here and should be useful with any product.
* This includes debug functions, file handling and so on.
*
* @version     $Id: general.php,v 1.17 2007/08/28 00:48:00 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package Library
* @subpackage General
*/

/**
* EasySize
* Turns a size into an appropriate unit. Eg bytes, Kb, Mb, Gb etc.
*
* @param Int $size Size to convert
* @param Int $decimals Number of decimal places to round to. Defaults to 2.
*
* @return String The size in the appropriate unit (with unit attached).
*/
function EasySize($size=0, $decimals=2)
{
	if ($size < 1024) {
		return $size . ' b';
	}

	if ($size >= 1024 && $size < (1024*1024)) {
		return number_format(($size/1024), $decimals) . ' Kb';
	}

	if ($size >= (1024*1024) && $size < (1024*1024*1024)) {
		return number_format(($size/1024/1024), $decimals) . ' Mb';
	}

	if ($size >= (1024*1024*1024)) {
		return number_format(($size/1024/1024/1024), $decimals) . ' Gb';
	}
}

/**
* remove_directory
* Will recursively remove directories and clean up files in each directory.
*
* @param String $directory Name of directory to clean up and clear.
*
* @return Boolean Returns false if it can't remove a directory or a file. Returns true if it all worked ok.
*/
function remove_directory($directory='')
{
	$safe_mode = ini_get('safe_mode');

	if (!is_dir($directory)) {
		return true;
	}

	// need to use the '@' in case we can't open the directory
	// otherwise it still throws a notice.
	if (!$handle = @opendir($directory)) {
		return false;
	}

	while (($file = readdir($handle)) !== false) {
		if ($file == '.' || $file == '..') {
			continue;
		}

		$f = $directory . '/' . $file;
		if (is_dir($f)) {
			remove_directory($f);
			continue;
		}

		if (is_file($f)) {
			if (!unlink($f)) {
				closedir($handle);
				return false;
			}
		}
	}
	closedir($handle);

	if ($safe_mode) {
		$status = true;
	} else {
		$status = rmdir($directory);
	}
	return $status;
}

/**
* CreateDirectory
* Creates a full path to a directory. If any part breaks (permissions), then it dies and returns false.
*
* @param String $dirname Full path to directory to make.
* @param String $checkbase The base path to check from. This will stop issues with open_basedir restrictions so it doesn't go back and check '/' etc.
*
* @return Boolean Returns true if the directory exists or if it's able to create it properly. Returns false if it can't create the directory or it's an invalid directory name passed in.
*/
function CreateDirectory($dirname=false, $checkbase=TEMP_DIRECTORY, $permission=0755)
{
	if (!$dirname) {
		return false;
	}

	if (is_dir($dirname)) {
		return true;
	}

	$dirname = str_replace($checkbase, '', $dirname);

	$parts = explode('/', $dirname);
	$result = false;
	$size = count($parts);
	$base = $checkbase;
	for ($i = 0; $i < $size; $i++) {
		if ($parts[$i] == '') {
			continue;
		}
		$base .= '/' . $parts[$i];
		if (!is_dir($base)) {
			$result = mkdir($base, $permission);
			if (!$result) {
				return false;
			}
			chmod($base, $permission);
		}
	}
	return true;
}

/**
* list_files
* Lists files in a directory. Can also skip particular types of files.
*
* @param String $dir Name of directory to list files for.
* @param Array $skip_files Filenames to skip. Can be a single file or an array of filenames.
* @param Boolean $recursive Whether to recursively list files or not. Default is no.
* @param Boolean $only_directories Whether to only include directories in the file list or not. Default is no (ie include all files/directories).
*
* @return Mixed Returns false if it can't open a directory, else it returns a multi-dimensional array.
*/
function list_files($dir='', $skip_files = null, $recursive=false, $only_directories=false)
{
	if (empty($dir) || !is_dir($dir)) {
		return false;
	}

	$file_list = array();

	// need to use the '@' in case we can't open the directory
	// otherwise it still throws a notice.
	if (!$handle = @opendir($dir)) {
		return false;
	}

	while (($file = readdir($handle)) !== false) {
		if ($file == '.' || $file == '..') {
			continue;
		}
		if (is_file($dir.'/'.$file)) {
			if ($only_directories) {
				continue;
			}
			if (empty($skip_files)) {
				$file_list[] = $file;
				continue;
			}
			if (!empty($skip_files)) {
				if (is_array($skip_files) && !in_array($file, $skip_files)) {
					$file_list[] = $file;
				}
				if (!is_array($skip_files) && $file != $skip_files) {
					$file_list[] = $file;
				}
			}
			continue;
		}

		if (is_dir($dir.'/'.$file) && !isset($file_list[$file])) {
			if ($recursive) {
				$file_list[$file] = list_files($dir.'/'.$file, $skip_files, $recursive, $only_directories);
			}
		}
	}
	closedir($handle);
	if (!$recursive) {
		natcasesort($file_list);
	}

	return $file_list;
}

/**
* list_directories
* Lists directories under a particular tree. Can also skip particular directory names of files.
*
* @param String $dir Name of directory to list directories for.
* @param Array $skip_dirs Directory names to skip. Can be a single name or an array.
* @param Boolean $recursive Whether to recursively list directories or not. Default is no.
*
* @return Mixed Returns false if it can't open a directory, else it returns a multi-dimensional array.
*/
function list_directories($dir='', $skip_dirs = null, $recursive=false)
{
	if (empty($dir) || !is_dir($dir)) {
		return false;
	}

	$file_list = array();

	if (substr($dir, -1) == '/') {
		$dir = substr($dir, 0, -1);
	}

	// need to use the '@' in case we can't open the directory
	// otherwise it still throws a notice.
	if (!$handle = @opendir($dir)) {
		return false;
	}

	while (($file = readdir($handle)) !== false) {
		if ($file == '.' || $file == '..') {
			continue;
		}

		if (is_dir($dir.'/'.$file) && !isset($file_list[$file])) {
			$file_list[] = $dir . '/' . $file;
			if ($recursive) {
				$subdir = list_directories($dir.'/'.$file, $skip_dirs, $recursive);
				if (!empty($subdir)) {
					foreach ($subdir as $p => $subdirname) {
						$file_list[] = $subdirname;
					}
				}
			}
		}
	}
	closedir($handle);
	if (!$recursive) {
		natcasesort($file_list);
	}

	return $file_list;
}

/**
* CopyDirectory
* Copies an entire directory structure from source to destination. Works recursively.
*
* @param String $source Source directory to copy.
* @param String $destination Destination directory to create and copy to.
*
* @return Boolean Returns true if all files were worked ok, otherwise false.
*/
function CopyDirectory($source='', $destination='')
{
	if (!$source || !$destination) {
		return false;
	}

	if (!is_dir($source)) {
		return false;
	}

	if (!CreateDirectory($destination)) {
		return false;
	}

	$files_to_copy = list_files($source, null, true);

	$status = true;

	if (is_array($files_to_copy)) {
		foreach ($files_to_copy as $pos => $name) {
			if (is_array($name)) {
				$dir = $pos;
				$status = CopyDirectory($source . '/' . $dir, $destination . '/' . $dir);
			}

			if (!is_array($name)) {
				$copystatus = copy($source . '/' . $name, $destination . '/' . $name);
				if ($copystatus) {
					chmod($destination . '/' . $name, 0644);
				}
				$status = $copystatus;
			}
		}
		return $status;
	}
	return false;
}

/**
* See if the file_get_contents function is available.
*/
if (!function_exists('file_get_contents')) {
	/**
	* file_get_contents
	* If there is no file_get_contents function, then recreate it.
	*
	* @param String $filename Filename to read (full path).
	*
	* @return Mixed Returns false if the file doesn't exist or isn't readable. If it is, then it reads the file and returns it's contents.
	*/
	function file_get_contents($filename=false)
	{
		if (!is_file($filename) || !is_readable($filename)) {
			return false;
		}
		$handle = fopen($filename, "r");
		$contents = fread($handle, filesize($filename));
		fclose($handle);
		return $contents;
	}
}

/**
* Timedifference
* Returns the time difference in an easy format / unit system (eg how many seconds, minutes, hours etc).
*
* @param Int $timedifference Time difference as an integer to transform.
*
* @return String Time difference plus units.
*/
function timedifference($timedifference=0)
{
	if ($timedifference < 60) {
		$timechange = number_format($timedifference, 0) . ' second';
		if ($timedifference > 1) {
			$timechange .= 's';
		}
	}

	if ($timedifference >= 60 && $timedifference < 3600) {
		$num_mins = floor($timedifference / 60);
		$timechange = number_format($num_mins, 0) . ' minute';
		if ($num_mins > 1) {
			$timechange .= 's';
		}
	}

	if ($timedifference >= 3600) {
		$hours = floor($timedifference/3600);
		$mins = floor($timedifference % 3600) / 60;

		$timechange = number_format($hours, 0) . ' hour';
		if ($hours > 1) {
			$timechange .= 's';
		}

		$timechange .= ' and ' . number_format($mins, 0) . ' minute';
		if ($mins > 1) {
			$timechange .= 's';
		}
	}
	return $timechange;
}


/**
* array_contents
* Recursively prints an array. Works well with associative arrays and objects.
*
* @see bam
*
* @param Array $array Array or object to print
* @param Int $max_depth Maximum depth to print
* @param Int $depth Used internally to make sure the array doesn't go past max_depth.
* @param Boolean $ignore_ints So it doesn't show numbers only.
*
* @return String The contents of the array / object is returned as a string.
*/
function array_contents(&$array, $max_depth=0, $depth=0, $ignore_ints=false)
{
	$string = $indent = "";
	for ($i = 0; $i < $depth; $i++) {
		$indent .= "\t";
	}
	if (!empty($max_depth) && $depth >= $max_depth) {
		return $indent."[Max Depth Reached]\n";
	}
	if (empty($array)) {
		return $indent."[Empty]\n";
	}
	reset($array);
	while ( list($key,$value) = each($array) ) {
		$print_key = str_replace("\n","\\n",str_replace("\r","\\r",str_replace("\t","\\t",addslashes($key))));
		if ($ignore_ints && gettype($key) == "integer") {
			continue;
		}
		$type = gettype($value);
		if ($type == "array" || $type == "object") {
			$string .= $indent
					.  ((is_string($key)) ? "\"$print_key\"": $key) . " => "
					.  (($type == "array")?"array (\n":"")
					.  (($type == "object")?"new ".get_class($value)." Object (\n":"");
			$string .= array_contents($value, $max_depth, $depth + 1,  $ignore_ints);
			$string .= $indent . "),\n";
		} else {
			if (is_string($value)) {
				$value = str_replace("\n","\\n",str_replace("\r","\\r",str_replace("\t","\\t",addslashes($value))));
			}
			$string .= $indent
					.  ((is_string($key)) ? "\"$print_key\"": $key) . " => "
					.  ((is_string($value)) ? "\"$value\"": $value) . ",\n";
		}
	}
	$string[ strlen($string) - 2 ] = " ";
	return $string;
}

/**
* bam
* Prints out a variable, possibly recursively if the variable is an array or object.
*
* @see array_contents
*
* @param String $x Message to print out.
* @param Int $max_depth Maximum depth to print out of the variable if it's an object / array.
* @param String $style Stylesheet to apply.
*
* @return Void Doesn't return anything.
*/
function bam($x='BAM!', $max_depth=0, $style='')
{
?>
	<div align="left"><pre style="<?php echo $style; ?>font-family: courier, monospace;"><?php
	$type = gettype($x);
	if ($type == "object" && !$max_depth) {
		print_r($x);
	} else {
		if ($type == "object" || $type == "array") {
			# get the contents, then
			if (!$max_depth) {
				$max_depth = 10;
			}
			$x = array_contents($x, $max_depth);
		}
		echo htmlspecialchars(ereg_replace("\t", str_repeat (" ", 4), $x));
	}#end switch
	?></pre></div>
<?php
}

/**
* FloatTime
* Returns seconds and microtime. Used to check performance.
*
* @return Float Returns a floating point number of seconds and microseconds.
*/
function floattime()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

/**
* MemoryUsage
* Returns memory usage in KB's to 4 decimal places.
* Used for debug purposes only.
*/
function MemoryUsage()
{
	$mem = memory_get_usage();
	return number_format(($mem / 1024), 4);
}

/**
 * GetJSON
 * Get JSON representation of specified data.
 * This is just an interface to choose between using PHP's own json_encode if available (ie. using PHP5 or above)
 * or appropriate (json_encode does not encode charactersets other than UTF-8), otherwise
 * it will emulate what json_encode does.
 *
 * @param Mixed $data Data to be encoded to JSON format
 *
 * @return String Returns JSON formatted representation of the data
 */
function GetJSON($data)
{
	if (strtolower(SENDSTUDIO_CHARSET) == 'utf-8' && function_exists('json_encode')) {
		return json_encode($data);
	} else {
		if (is_null($data)) {
			return 'null';
		} elseif ($data === true) {
			return 'true';
		} elseif ($data === false) {
			return 'false';
		} elseif (is_float($data)) {
			return str_replace(",", ".", strval($data));
		} elseif (is_numeric($data)) {
			return intval($data);
		} elseif (is_scalar($data)) {
			return '"' . addcslashes(strval($data), "\\\n\r\t\/\x0B\x0C\"\'") . '"';
		} else {
			$tempIsArray = true;

			for ($i = 0, $j = count($data), reset($data); $i < $j; $i++, next($data)) {
				if (key($data) !== $i) {
					$tempIsArray = false;
					break;
				}
			}

			$output = array();
			if ($tempIsArray) {
				foreach ($data as $value) {
					array_push($output, GetJSON($value));
				}

				return '[' . implode(',',$output) . ']';
			} else {
				foreach ($data as $key => $value) {
					array_push($output, GetJSON($key) . ':' . GetJSON($value));
				}

				return '{' . implode(',',$output) . '}';
			}
		}
	}
}

if (!function_exists('json_encode'))
{
  function json_encode($a=false)
  {
    if (is_null($a)) return 'null';
    if ($a === false) return 'false';
    if ($a === true) return 'true';
    if (is_scalar($a))
    {
      if (is_float($a))
      {
        // Always use "." for floats.
        return floatval(str_replace(",", ".", strval($a)));
      }

      if (is_string($a))
      {
        static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
        return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
      }
      else
        return $a;
    }
    $isList = true;
    for ($i = 0, reset($a); $i < count($a); $i++, next($a))
    {
      if (key($a) !== $i)
      {
        $isList = false;
        break;
      }
    }
    $result = array();
    if ($isList)
    {
      foreach ($a as $v) $result[] = json_encode($v);
      return '[' . join(',', $result) . ']';
    }
    else
    {
      foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
      return '{' . join(',', $result) . '}';
    }
  }
}

if ( !function_exists('json_decode') ){
   /**
    * convert a string from one UTF-16 char to one UTF-8 char
    *
    * Normally should be handled by mb_convert_encoding, but
    * provides a slower PHP-only method for installations
    * that lack the multibye string extension.
    *
    * @param    string  $utf16  UTF-16 character
    * @return   string  UTF-8 character
    * @access   private
    */
    function utf162utf8($utf16)
    {
        if(function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
        }

        $bytes = (ord($utf16{0}) << 8) | ord($utf16{1});

        switch(true) {
            case ((0x7F & $bytes) == $bytes):
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x7F & $bytes);

            case (0x07FF & $bytes) == $bytes:
                // return a 2-byte UTF-8 character
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xC0 | (($bytes >> 6) & 0x1F))
                     . chr(0x80 | ($bytes & 0x3F));

            case (0xFFFF & $bytes) == $bytes:
                // return a 3-byte UTF-8 character
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xE0 | (($bytes >> 12) & 0x0F))
                     . chr(0x80 | (($bytes >> 6) & 0x3F))
                     . chr(0x80 | ($bytes & 0x3F));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }

   /**
    * reduce a string by removing leading and trailing comments and whitespace
    *
    * @param    $str    string      string value to strip of comments and whitespace
    *
    * @return   string  string value stripped of comments and whitespace
    * @access   private
    */
    function reduce_string($str)
    {
        $str = preg_replace(array(

                // eliminate single line comments in '// ...' form
                '#^\s*//(.+)$#m',

                // eliminate multi-line comments in '/* ... */' form, at start of string
                '#^\s*/\*(.+)\*/#Us',

                // eliminate multi-line comments in '/* ... */' form, at end of string
                '#/\*(.+)\*/\s*$#Us'

            ), '', $str);

        // eliminate extraneous space
        return trim($str);
    }

   /**
    * decodes a JSON string into appropriate variable
    *
    * @param    string  $str    JSON-formatted string
    *
    * @return   mixed   number, boolean, string, array, or object
    *                   corresponding to given JSON input string.
    *                   See argument 1 to Services_JSON() above for object-output behavior.
    *                   Note that decode() always returns strings
    *                   in ASCII or UTF-8 format!
    * @access   public
    */
    function json_decode($str)
    {
        $use = 1;
        $str = reduce_string($str);

        switch (strtolower($str)) {
            case 'true':
                return true;

            case 'false':
                return false;

            case 'null':
                return null;

            default:
                $m = array();

                if (is_numeric($str)) {
                    // Lookie-loo, it's a number

                    // This would work on its own, but I'm trying to be
                    // good about returning integers where appropriate:
                    // return (float)$str;

                    // Return float or int, as appropriate
                    return ((float)$str == (integer)$str)
                        ? (integer)$str
                        : (float)$str;

                } elseif (preg_match('/^("|\').*(\1)$/s', $str, $m) && $m[1] == $m[2]) {
                    // STRINGS RETURNED IN UTF-8 FORMAT
                    $delim = substr($str, 0, 1);
                    $chrs = substr($str, 1, -1);
                    $utf8 = '';
                    $strlen_chrs = strlen($chrs);

                    for ($c = 0; $c < $strlen_chrs; ++$c) {

                        $substr_chrs_c_2 = substr($chrs, $c, 2);
                        $ord_chrs_c = ord($chrs{$c});

                        switch (true) {
                            case $substr_chrs_c_2 == '\b':
                                $utf8 .= chr(0x08);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\t':
                                $utf8 .= chr(0x09);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\n':
                                $utf8 .= chr(0x0A);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\f':
                                $utf8 .= chr(0x0C);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\r':
                                $utf8 .= chr(0x0D);
                                ++$c;
                                break;

                            case $substr_chrs_c_2 == '\\"':
                            case $substr_chrs_c_2 == '\\\'':
                            case $substr_chrs_c_2 == '\\\\':
                            case $substr_chrs_c_2 == '\\/':
                                if (($delim == '"' && $substr_chrs_c_2 != '\\\'') ||
                                   ($delim == "'" && $substr_chrs_c_2 != '\\"')) {
                                    $utf8 .= $chrs{++$c};
                                }
                                break;

                            case preg_match('/\\\u[0-9A-F]{4}/i', substr($chrs, $c, 6)):
                                // single, escaped unicode character
                                $utf16 = chr(hexdec(substr($chrs, ($c + 2), 2)))
                                       . chr(hexdec(substr($chrs, ($c + 4), 2)));
                                $utf8 .= utf162utf8($utf16);
                                $c += 5;
                                break;

                            case ($ord_chrs_c >= 0x20) && ($ord_chrs_c <= 0x7F):
                                $utf8 .= $chrs{$c};
                                break;

                            case ($ord_chrs_c & 0xE0) == 0xC0:
                                // characters U-00000080 - U-000007FF, mask 110XXXXX
                                //see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 2);
                                ++$c;
                                break;

                            case ($ord_chrs_c & 0xF0) == 0xE0:
                                // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 3);
                                $c += 2;
                                break;

                            case ($ord_chrs_c & 0xF8) == 0xF0:
                                // characters U-00010000 - U-001FFFFF, mask 11110XXX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 4);
                                $c += 3;
                                break;

                            case ($ord_chrs_c & 0xFC) == 0xF8:
                                // characters U-00200000 - U-03FFFFFF, mask 111110XX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 5);
                                $c += 4;
                                break;

                            case ($ord_chrs_c & 0xFE) == 0xFC:
                                // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 6);
                                $c += 5;
                                break;

                        }

                    }

                    return $utf8;

                } elseif (preg_match('/^\[.*\]$/s', $str) || preg_match('/^\{.*\}$/s', $str)) {
                    // array, or object notation

                    if ($str{0} == '[') {
                        $stk = array(3);
                        $arr = array();
                    } else {
                        if ($use & 16) {
                            $stk = array(4);
                            $obj = array();
                        } else {
                            $stk = array(4);
                            $obj = new stdClass();
                        }
                    }

                    array_push($stk, array('what'  => 1,
                                           'where' => 0,
                                           'delim' => false));

                    $chrs = substr($str, 1, -1);
                    $chrs = reduce_string($chrs);

                    if ($chrs == '') {
                        if (reset($stk) == 3) {
                            return $arr;

                        } else {
                            return $obj;

                        }
                    }

                    $strlen_chrs = strlen($chrs);

                    for ($c = 0; $c <= $strlen_chrs; ++$c) {

                        $top = end($stk);
                        $substr_chrs_c_2 = substr($chrs, $c, 2);

                        if (($c == $strlen_chrs) || (($chrs{$c} == ',') && ($top['what'] == 1))) {
                            // found a comma that is not inside a string, array, etc.,
                            // OR we've reached the end of the character list
                            $slice = substr($chrs, $top['where'], ($c - $top['where']));
                            array_push($stk, array('what' => 1, 'where' => ($c + 1), 'delim' => false));
                            //print("Found split at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                            if (reset($stk) == 3) {
                                // we are in an array, so just push an element onto the stack
                                array_push($arr, json_decode($slice));

                            } elseif (reset($stk) == 4) {
                                // we are in an object, so figure
                                // out the property name and set an
                                // element in an associative array,
                                // for now
                                $parts = array();

                                if (preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                                    // "name":value pair
                                    $key = json_decode($parts[1]);
                                    $val = json_decode($parts[2]);

                                    if ($use & 16) {
                                        $obj[$key] = $val;
                                    } else {
                                        $obj->$key = $val;
                                    }
                                } elseif (preg_match('/^\s*(\w+)\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                                    // name:value pair, where name is unquoted
                                    $key = $parts[1];
                                    $val = json_decode($parts[2]);

                                    if ($use & 16) {
                                        $obj[$key] = $val;
                                    } else {
                                        $obj->$key = $val;
                                    }
                                }

                            }

                        } elseif ((($chrs{$c} == '"') || ($chrs{$c} == "'")) && ($top['what'] != 2)) {
                            // found a quote, and we are not inside a string
                            array_push($stk, array('what' => 2, 'where' => $c, 'delim' => $chrs{$c}));
                            //print("Found start of string at {$c}\n");

                        } elseif (($chrs{$c} == $top['delim']) &&
                                 ($top['what'] == 2) &&
                                 ((strlen(substr($chrs, 0, $c)) - strlen(rtrim(substr($chrs, 0, $c), '\\'))) % 2 != 1)) {
                            // found a quote, we're in a string, and it's not escaped
                            // we know that it's not escaped becase there is _not_ an
                            // odd number of backslashes at the end of the string so far
                            array_pop($stk);
                            //print("Found end of string at {$c}: ".substr($chrs, $top['where'], (1 + 1 + $c - $top['where']))."\n");

                        } elseif (($chrs{$c} == '[') &&
                                 in_array($top['what'], array(1, 3, 4))) {
                            // found a left-bracket, and we are in an array, object, or slice
                            array_push($stk, array('what' => 3, 'where' => $c, 'delim' => false));
                            //print("Found start of array at {$c}\n");

                        } elseif (($chrs{$c} == ']') && ($top['what'] == 3)) {
                            // found a right-bracket, and we're in an array
                            array_pop($stk);
                            //print("Found end of array at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        } elseif (($chrs{$c} == '{') &&
                                 in_array($top['what'], array(1, 3, 4))) {
                            // found a left-brace, and we are in an array, object, or slice
                            array_push($stk, array('what' => 4, 'where' => $c, 'delim' => false));
                            //print("Found start of object at {$c}\n");

                        } elseif (($chrs{$c} == '}') && ($top['what'] == 4)) {
                            // found a right-brace, and we're in an object
                            array_pop($stk);
                            //print("Found end of object at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        } elseif (($substr_chrs_c_2 == '/*') &&
                                 in_array($top['what'], array(1, 3, 4))) {
                            // found a comment start, and we are in an array, object, or slice
                            array_push($stk, array('what' => 5, 'where' => $c, 'delim' => false));
                            $c++;
                            //print("Found start of comment at {$c}\n");

                        } elseif (($substr_chrs_c_2 == '*/') && ($top['what'] == 5)) {
                            // found a comment end, and we're in one now
                            array_pop($stk);
                            $c++;

                            for ($i = $top['where']; $i <= $c; ++$i)
                                $chrs = substr_replace($chrs, ' ', $i, 1);
                        }

                    }

                    if (reset($stk) == 3) {
                        return $arr;

                    } elseif (reset($stk) == 4) {
                        return $obj;

                    }

                }
        }
    }
}

/**
 * GetRealIp
 * Gets the IP from the users web browser. It checks if there is a proxy etc in front of the browser.
 *
 * NOTE: This will return the connection IP address rather than the real address behind a proxy.
 * The reason for the change is that getting client's IP address VIA proxy header is NOT reliable enough.
 * At least this way we have a record of the connection IP address instead of a possible bogus IP.
 *
 * @param Boolean $override_settings If this is passed in and true, this will skip the check for ip tracking being enabled. Currently this is only used by the user functions to always grab a users ip address when they generate a new xml api token.
 *
 * @return String The IP address of the user.
 *
 * @todo refactor this
 */
function GetRealIp($override_settings=false)
{
		$iptracking = true;
		if (defined('SENDSTUDIO_IPTRACKING') && !SENDSTUDIO_IPTRACKING) {
			$iptracking = false;
		}


		if (!$override_settings && !$iptracking) {
			return null;
		}

		$ip = IEM::ifsetor($_SERVER['REMOTE_ADDR'], false);
		if (!$ip) {
			return null;
		}

		// Handle IPv6.
		if (strpos($ip, ':') !== false) {
			// IPv6's deprecated IPv4 compatibility mode.
			// See http://www.mail-archive.com/swinog@lists.swinog.ch/msg03443.html.
			if (!preg_match('/\:\:ffff\:([\d\.]+)/i', $ip, $matches)) {
				return $ip;
			}
			$ip = $matches[1]; // Continue checking.
		}

		// ----- Make sure that this is a valid IP
			$ip = ip2long($ip);
			if ($ip !== false && $ip !== -1 && $ip !== 0) {
				$ip = long2ip($ip);
			} else {
				$ip = '';
			}
		// -----

		return $ip;
}
