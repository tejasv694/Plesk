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
 * @package marker
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Marker.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Graph/Element.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Element.php");

/**
 * Data point marker.
 * The data point marker is used for marking the datapoints on a graph with some 
 * visual label, fx. a cross, a text box with the value or an icon.
 * @abstract
 */
class Image_Graph_Marker extends Image_Graph_Element 
{

    /**
     * Secondary marker
     * @var Marker
     * @access private
     */
    var $_secondaryMarker = false;

    /**
     * The "size" of the marker, the meaning depends on the specific Marker implementation
     * @var int
     * @access private
     */
    var $_size = 6;

    /**
     * Set the "size" of the marker
     * @param int $size The "size" of the marker, the meaning depends on the specific Marker implementation 
     */
    function setSize($size)
    {
        $this->_size = $size;
    }
    
    /**
     * Set the secondary marker
     * @param Marker $secondaryMarker The secondary marker 
     */
    function setSecondaryMarker(& $secondaryMarker)
    {
        $this->_secondaryMarker = & $secondaryMarker;
        $this->_secondaryMarker->_setParent($this);
    }

    /**
     * Draw the marker on the canvas
     * @param int $x The X (horizontal) position (in pixels) of the marker on the canvas 
     * @param int $y The Y (vertical) position (in pixels) of the marker on the canvas 
     * @param array $values The values representing the data the marker "points" to 
     * @access private
     */
    function _drawMarker($x, $y, $values = false)
    {
        if (is_a($this->_secondaryMarker, "Image_Graph_Marker")) {
            $this->_secondaryMarker->_drawMarker($x, $y, $values);
        }
    }

    /**
     * Output to the canvas
     * @access private
     */
    function _done()
    {
    }

}

?>