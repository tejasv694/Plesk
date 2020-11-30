<?php
// +--------------------------------------------------------------------------+
// | Image_Graph aka GraPHPite                                                |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2003, 2004 Jesper Veggerby Hansen                          |
// | Email         pear.nosey@veggerby.dk                                |
// | Web           http://graphpite.sourceforge.net                           |
// | PEAR          http://pear.php.net/pepr/pepr-proposal-show.php?id=145     |
// +--------------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or            |
// | modify it under the terms of the GNU Lesser General Public               |
// | License as published by the Free Software Foundation; either             |
// | version 2.1 of the License, or (at your option) any later version.       |
// |                                                                          |
// | This library is distributed in the hope that it will be useful,          |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU        |
// | Lesser General Public License for more details.                          |
// |                                                                          |
// | You should have received a copy of the GNU Lesser General Public         |
// | License along with this library; if not, write to the Free Software      |
// | Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA |
// +--------------------------------------------------------------------------+

/**
 * Image_Graph aka GraPHPite - PEAR PHP OO Graph Rendering Utility.
 * @package common
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Common.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

if (!function_exists('is_a')) {

    /**
     * Check if an object is of a given class, this function is available as of PHP 4.2.0, so if it exist it will not be declared
     * @link http://www.php.net/manual/en/function.is-a.php PHP.net Online Manual for function is_a()
     * @param object $object The object to check class for
     * @param string $class_name The name of the class to check the object for
     * @return bool Returns TRUE if the object is of this class or has this class as one of its parents 
     */
    function is_a($object, $class_name)
    {
        if (empty ($object)) {
            return false;
        }
        $object = is_object($object) ? get_class($object) : $object;
        if (strtolower($object) == strtolower($class_name)) {
            return true;
        }
        return is_a(get_parent_class($object), $class_name);
    }
}

/**
 * Check which version of GD is installed
 * @return int 0 if GD isn't installed, 1 if GD 1.x is installed and 2 if GD 2.x is installed
 */
function Image_Graph_gd_version()
{
    if (function_exists("gd_info")) {
        $info = gd_info();
        $version = $info['GD Version'];
    } else {    
        ob_start();
        phpinfo(8);
        $php_info = ob_get_contents();
        ob_end_clean();
         
        if (ereg("<td[^>]*>GD Version *<\/td><td[^>]*>([^<]*)<\/td>", $php_info, $result)) {
            $version = $result[1];
        }
    }
    
    if ($version) {
        //define("GD_VERSION", $version);
    }
    
    if (ereg("1\.[0-9]{1,2}", $version)) {
        return 1;
    }
    elseif (ereg("2\.[0-9]{1,2}", $version)) {
        return 2;
    } else {
        return 0;
    }            
}

/**
 * The ultimate ancestor of all GraPHPite classes.
 * This class contains common functionality needed by all GraPHPite classes.
 * @abstract 
 */
class Image_Graph_Common 
{

    /** The parent container of the current GraPHPite object
     * @var Image_Graph_Common
     * @access private
     */
    var $_parent = null;

    /** The sub-elements of the current GraPHPite container object
     * @var array
     * @access private
     */
    var $_elements;

    /** Specifies if the object should identify itself in the output.
     * Default value is false.<br>
     * This is for debugging purposes only
     * @var bool
     * @access private
     */
    var $_identify = false;

    /** Specifies if the object should identify itself in the output with a descriptive text.
     * Default value is false.<br>
     * This is for debugging purposes only
     * @var bool
     * @access private
     */
    var $_identifyText = false;

    /** Name the specific instance of the class
     * Default value is "".<br>
     * @var string
     * @access private
     */
    var $_name = "";

    /** Identification number of the object 
     * Default value is a random between 0 and 10000.<br>
     * This is for debugging purposes only
     * @var int
     * @see Image_Graph_Common::_identify()
     * @access private
     */
    var $_iD;

    /**
     * Creates an a instance of Image_Graph_Common.
     * Note: Image_Graph_Common is implicitely an abstract class 
     */
    function Image_Graph_Common()
    {
        $this->_iD = rand(0, 10000);
    }

    /**
     * Sets the name
     * @param string $name The new Image_Graph_name 
     */
    function setName($name)
    {
        $this->_debug("Setting name \"$name\"");
        $this->_name = $name;
    }

    /**
     * Sets the parent. The parent chain should ultimately be a GraPHP object
     * @see Image_Graph_Common
     * @param Image_Graph_Common $parent The parent 
     * @access private
     */
    function _setParent(& $parent)
    {
        $this->_debug("Setting parent \"".$parent->_identification()."\"");
        $this->_parent = & $parent;
    }

    /**
     * Adds an element to the objects element list, the new Image_Graph_elements parent is automatically set	 
     * @param Image_Graph_Common $element The new Image_Graph_element
     * @return Image_Graph_Common The new Image_Graph_element 
     */
    function &add(& $element)
    {
        $this->_debug("Adding element \"".$element->_identification());
        $this->_elements[] = &$element;
        $element->_setParent($this);
        return $element;
    }

    /**
     * Gets the parent chain path	 
     * @return string A textual representation of the parent chain 
     * @access private
     */
    function _parentPath()
    {
        if ($this->_parent) {
            return $this->_parent->_parentPath()." -> ".get_class($this)." [$this->_iD]";
        } else {
            return "[chain broken] -> ".get_class($this)." [$this->_iD]";
    }
        }

    /**
     * Gets an identification text
     * @return string A textual identification of the object 
     * @access private
     */
    function _identification()
    {
        return "".get_class($this)." [". ($this->_name ? "$this->_name " : "")."$this->_iD] (".count($this->_elements)." {".$this->_parentPath()."}";
    }

    /**
     * Debugs the graphs output
     * @param string $text The debug text
     * @access private
     */
    function _debug($text, $close = false)
    {
        if (IMAGE_GRAPH_DEBUG) {
            if (!$GLOBALS['IMAGE_GRAPH_LOG']) {
                $GLOBALS['IMAGE_GRAPH_LOG'] = true;
                print "<table><tr><th>Time</th><th>Object ID</th><th>Text</th></tr>\n";
            }

            print "<tr><td>".date("H:i:s d/m/Y")."</td>" . 
                  "<td>". $this->_identification()."</td>" . 
                  "<td>$text</td></tr>\n";

            if ($close) {
                print "</table>\n";
            }
        }
    }

    /**
     * Returns the graph's canvas. Penultimately it should call Canvas() from the GraPHP object
     * @see Image_Graph
     * @return resource A GD image representing the graph's canvas 
     * @access private
     */
    function _canvas()
    {
        if ($this->_parent) {
            return $this->_parent->_canvas();
        } else {
            return false;
        }
    }

    /**
     * Returns the total width of the graph's canvas
     * @see Image_Graph
     * @return int The width of the canvas 
     * @access private
     */
    function _graphWidth()
    {
        $this->_debug("Getting graph width");
        if ($this->_parent) {
            return $this->_parent->_graphWidth();
        } else {
            return 0;
        }
    }

    /**
     * Returns the total height of the graph's canvas
     * @see Image_Graph
     * @return int The height of the canvas 
     * @access private
     */
    function _graphHeight()
    {
        $this->_debug("Getting graph height");
        if ($this->_parent) {
            return $this->_parent->_graphHeight();
        } else {
            return 0;
        }
    }

    /**
     * Add a color. Ultimately it should call addColor() from the Image_Graph object
     * @see Image_Graph
     * @param Color $color A representation of the color
     */
    function &addColor(& $color)
    {
        $this->_debug("Adding color ".$color->_index);
        if ($this->_parent) {
            return $this->_parent->addColor($color);
        }
    }

    /**
     * Create a new Image_Graph_color. 
     * @param int $red The red part or the whole part
     * @param int $green The green part (or nothing), or the alpha channel
     * @param int $blue The blue part (or nothing)
     * @param int $alpha The alpha channel (or nothing)
     */
    function newColor($red, $green = false, $blue = false, $alpha = false)
    {
        $this->_debug("Creating color Red = $red, Green/Alpha = $green, Blue = $blue, Alpha = $alpha");
        if ($this->_parent) {
            $this->_parent->newColor($red, $green, $blue, $alpha);
        }
    }

    /**
     * Get the color index for the RGB color
     * @param int $colorRGB The RGB value of the color
     * @return int The GD image index of the color
     * @access private
     */     
    function _color($colorRGB)
    {
        if ($colorRGB == IMAGE_GRAPH_TRANSPARENT) {
            return ImageColorTransparent($this->_canvas());
        } 
        else {
            return ImageColorAllocate($this->_canvas(), ($colorRGB >> 16) & 0xff, ($colorRGB >> 8) & 0xff, $colorRGB & 0xff);
        }
    }

    /**
     * Specifies whether the object should identify itself on the canvas or not
     * @param bool $identifyText Specifies whether idenfification should include a text (can be omitted), default: false 
     * @access private
     */
    function _identify($identifyText = false)
    {
        $this->_identify = true;
        $this->_identifyText = $identifyText;
    }

    /**
     * Causes the object to update all sub elements coordinates (Image_Graph_Common, does not itself have coordinates, this is basically an abstract method)
     * @access private
     */
    function _updateCoords()
    {
        $this->_debug("Updating coordinates");
        if (is_array($this->_elements)) {
            reset($this->_elements);

            $keys = array_keys($this->_elements);
            while (list ($ID, $key) = each($keys)) {
                $this->_elements[$key]->_updateCoords();
            }
        }
    }

    /**
     * Shrink the element. Negative values will cause the size to grow! 
     * @param int $$left Number of pixels to shrink in the left side
     * @param int $$top Number of pixels to shrink in the top
     * @param int $$right Number of pixels to shrink in the right side
     * @param int $bottom Number of pixels to shrink in the bottom
     * @access private
     */
    function _shrink($left, $top, $right, $bottom)
    {
        $this->_debug("Shrinking ($left, $top, $right, $bottom)");
    }

    /**
     * The last method to call. Calling Done causes output to the canvas. All sub elements done() method
     * will be invoked 
     * @access private
     */
    function _done()
    {
        $this->_debug("Done start...");

        if (is_array($this->_elements)) {
            reset($this->_elements);

            $keys = array_keys($this->_elements);
            while (list ($ID, $key) = each($keys)) {
                if ($this->_identify)
                    $this->_elements[$key]->_identify($this->_identifyText);
                $this->_elements[$key]->_done();
            }
        }
    }

}

?>