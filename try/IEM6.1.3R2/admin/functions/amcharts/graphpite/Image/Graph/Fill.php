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
 * @version $Id: Fill.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Graph/Element.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Element.php");

/**
 * Style used for filling elements. 
 * @abstract
 */
class Image_Graph_Fill extends Image_Graph_Element 
{

    /**
     * Resets the fillstyle
     * @access private 
     */
    function _reset()
    {
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
        return $this->_getFillStyle($ID);
    }

    /**
     * Causes the object to update all sub elements coordinates (Image_Graph_Common, does not itself have coordinates, this is basically an abstract method)
     * @access private
     */
    function _updateCoords()
    {
        $this->_setCoords($this->_parent->_fillLeft()-1, $this->_parent->_fillTop()-1, $this->_parent->_fillRight()+1, $this->_parent->_fillBottom()+1);
        parent::_updateCoords();
    }

}

?>