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
 * @package fillstyle
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Array.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Graph/Fill.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Fill.php");

/**
 * A sequential array of fillstyles.
 * This is used for filling multiple objects within the same element with different styles.
 * This is done by adding multiple fillstyles to a FillArrray structure. The fillarray 
 * will then when requested return the "next" fillstyle in sequential order. It is possible
 * to specify ID tags to each fillstyle, which is used to make sure some data uses a 
 * specific fillstyle (i.e. in a multiple-/stackedbarchart you name the {@see Image_Graph_Dataset}s and
 * uses this name as ID tag when adding the dataset's associated fillstyle to the fillarray.
 */
class Image_Graph_Fill_Array extends Image_Graph_Fill 
{

    /**
     * The fill array
     * @var array
     * @access private
     */
    var $_fillStyles = array ();

    /**
     * Resets the fillstyle
     * @access private 
     */
    function _reset()
    {
        reset($this->_fillStyles);        
    }

    /**
     * Add a fill style to the array
     * @param Image_Graph_Fill $style The style to add
     * @param string $id The id or name of the style
     */
    function &add(& $style, $id = "")
    {
        if (is_a($style, "Image_Graph_Element")) {
            parent::add($style);
        }
        if ($id == "") {
            $this->_fillStyles[] = & $style;
        } else {
            $this->_fillStyles[$id] = & $style;
        }
        reset($this->_fillStyles);
    }

    /**
     * Add a color to the array    
     * @param int $red The red part or the whole part
     * @param int $green The green part (or nothing), or the alpha channel
     * @param int $blue The blue part (or nothing)
     * @param string $id The id or name of the style
     */
    function addColor($red, $green = false, $blue = false, $id = false)
    {        
        if ($green === false) {
             $color = $red;
             $id = $green;            
        } else {
            $color = (($red << 16) + ($green << 8) + $blue);
        }
        if ($id !== false) {
            $this->_fillStyles[$id] = $color;
        } else {
            $this->_fillStyles[] = $color;
        }
    }

    /**
     * Return the fillstyle
     * @return int A GD fillstyle 
     * @access private 
     */
    function _getFillStyle($ID = false)
    {
        if (($ID === false) and (!(list ($ID, $fillStyle) = each($this->_fillStyles)))) {
            reset($this->_fillStyles);
            list ($ID, $fillStyle) = each($this->_fillStyles);
        }
        $fillStyle = & $this->_fillStyles[$ID];

        if ($fillStyle != null) {
            if (is_numeric($fillStyle)) {
                return $this->_color($fillStyle);
            }
            elseif (is_a($fillStyle, "Image_Graph_Color")) {
                return $fillStyle->_index;
            } else {
                return $fillStyle->_getFillStyle();
            }
        } else {
            return parent::_getFillStyle($ID);
        }
    }

    /**
    * Return the fillstyle at positions X, Y 
    * @param int $x The X position
    * @param int $y The Y position
    * @param int $w The Width
    * @param int $h The Height
    * @return int A GD fillstyle 
    * @access private
    */
    function _getFillStyleAt($x, $y, $w, $h, $ID = false)
    {
        if (($ID === false) and (!(list ($ID, $fillStyle) = each($this->_fillStyles)))) {
            reset($this->_fillStyles);
            list ($ID, $fillStyle) = each($this->_fillStyles);
        }
        $fillStyle = & $this->_fillStyles[$ID];

        if ($fillStyle != null) {
            if (is_numeric($fillStyle)) {
                return $this->_color($fillStyle);
            }
            elseif (is_a($fillStyle, "Image_Graph_Color")) {
                return $fillStyle->_index;
            } else {
                return $fillStyle->_getFillStyleAt($x, $y, $w, $h, $ID);
            }
        } else {
            return parent::_getFillStyle($ID);
        }
    }

}

?>