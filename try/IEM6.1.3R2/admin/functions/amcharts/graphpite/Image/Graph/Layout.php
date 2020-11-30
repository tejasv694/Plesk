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
 * @package layout
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Layout.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Graph/Element.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Element.php");

/**
 * Defines an area of the graph that can be layout'ed.
 * Any class that extends this abstract class can be used within a layout on the canvas.
 * @abstract
 */
class Image_Graph_Layout extends Image_Graph_Element 
{

    /**
     * Alignment of the area for each vertice (left, top, right, bottom)
     * @var array
     * @access private
     */
    var $_alignSize = array (IMAGE_GRAPH_AREA_LEFT => 0, IMAGE_GRAPH_AREA_TOP => 0, IMAGE_GRAPH_AREA_RIGHT => 0, IMAGE_GRAPH_AREA_BOTTOM => 0);

    /**
     * Image_Graph_Layout [Constructor]
     * @access private
     */
    function &_image_GraPHPite_Layout()
    {
        parent::__construct();
        $this->_padding = 2;
    }
    
    /**
     * Calculate the edges
     * @access private
     */
    function _calcEdges()
    {
        if (is_array($this->_alignSize)) {
            $left = $this->_parent->_fillLeft() + ($this->_alignSize[IMAGE_GRAPH_AREA_LEFT] <= 1 ? $this->_parent->_fillWidth() * $this->_alignSize[IMAGE_GRAPH_AREA_LEFT] : $this->_alignSize[IMAGE_GRAPH_AREA_LEFT]);
            $top = $this->_parent->_fillTop() + ($this->_alignSize[IMAGE_GRAPH_AREA_TOP] <= 1 ? $this->_parent->_fillHeight() * $this->_alignSize[IMAGE_GRAPH_AREA_TOP] : $this->_alignSize[IMAGE_GRAPH_AREA_TOP]);
            $right = $this->_parent->_fillRight() - ($this->_alignSize[IMAGE_GRAPH_AREA_RIGHT] <= 1 ? $this->_parent->_fillWidth() * $this->_alignSize[IMAGE_GRAPH_AREA_RIGHT] : $this->_alignSize[IMAGE_GRAPH_AREA_RIGHT]);
            $bottom = $this->_parent->_fillBottom() - ($this->_alignSize[IMAGE_GRAPH_AREA_BOTTOM] <= 1 ? $this->_parent->_fillHeight() * $this->_alignSize[IMAGE_GRAPH_AREA_BOTTOM] : $this->_alignSize[IMAGE_GRAPH_AREA_BOTTOM]);
                        
            $this->_setCoords($left + $this->_padding, $top + $this->_padding, $right - $this->_padding, $bottom - $this->_padding);
        }
    }

    /**
     * Update coordinates
     * @access private
     */
    function _updateCoords()
    {
        $this->_calcEdges();
        parent::_updateCoords();
    }

    /**
     * Pushes an edge of area a specific distance "into" the canvas
     * @param int $edge The edge of the canvas to align relative to
     * @param int $size The number of pixels or the percentage of the canvas total size to occupy relative to the selected alignment edge
     * @access private
     */
    function _push($edge, $size = "100%")
    {
        if (($edge == IMAGE_GRAPH_AREA_MAX) or (!is_array($this->_alignSize))) {
            $this->_alignSize = array (IMAGE_GRAPH_AREA_LEFT => 0, IMAGE_GRAPH_AREA_TOP => 0, IMAGE_GRAPH_AREA_RIGHT => 0, IMAGE_GRAPH_AREA_BOTTOM => 0);
        } else {
            $this->_alignSize[$edge] = (ereg("([0-9]*)\%", $size, $result) ? min(100, max(0, $result[1] / 100)) : $size);
        }
    }

    /**
     * Sets the coordinates of the element   
     * @param int $left The leftmost pixel of the element on the canvas 
     * @param int $top The topmost pixel of the element on the canvas 
     * @param int $right The rightmost pixel of the element on the canvas 
     * @param int $bottom The bottommost pixel of the element on the canvas 
     * @access private
     */
    function _setCoords($left, $top, $right, $bottom)
    {
        parent::_setCoords($left, $top, $right, $bottom);
        $this->_alignSize = false;
    }

}

?>