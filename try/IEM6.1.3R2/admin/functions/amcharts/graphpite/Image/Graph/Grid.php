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
 * @package grid
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Grid.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Graph/Element.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Element.php");

/**
 * A grid displayed on the plotarea.
 * A grid is associated with a primary and a secondary axis. The grid is displayed in
 * context of the primary axis' label interval - meaning that a grid for an Y-axis displays
 * a grid for every label on the y-axis (fx. a {@see Image_Graph_Grid_Lines}, which displays horizontal
 * lines for every label on the y-axis from the x-axis minimum to the x-axis maximum).
 * You should always add the grid as one of the first elements to the plotarea. This is
 * due to the fact that elements are "outputted" in the order they are added, i.e. if
 * an grid is added *after* a chart, the grid will be displayed on top of the chart which is
 * (probably) not desired. 
 * @abstract
 */
class Image_Graph_Grid extends Image_Graph_Element 
{

    /**
     * The primary axis: the grid "refers" to
     * @var Axis
     * @access private
     */
    var $_primaryAxis = null;

    /**
     * The secondary axis
     * @var Axis
     * @access private
     */
    var $_secondaryAxis = null;

    /**
     * Set the primary axis: the grid should "refer" to
     * @param Image_Graph_Axis $axis The axis 
     * @access private 
     */
    function _setPrimaryAxis(& $axis)
    {
        $this->_primaryAxis = & $axis;
    }

    /**
     * Set the secondary axis
     * @param Image_Graph_Axis $axis The axis 
     * @access private 
     */
    function _setSecondaryAxis(& $axis)
    {
        $this->_secondaryAxis = & $axis;
    }

    /**
     * Get the points on the secondary axis that the grid should "connect"
     * @return Array The secondary data values that should mark the grid "end points"
     * @access private	 
     */
    function _getSecondaryAxisPoints()
    {
        if (is_a($this->_secondaryAxis, "Image_Graph_Axis_Multidimensional")) {

            $secondaryValue = $this->_secondaryAxis->_getNextLabel();

            while ($secondaryValue <= $this->_secondaryAxis->_getMaximum()) {
                $secondaryAxisPoints[] = $secondaryValue;
                $secondaryValue = $this->_primaryAxis->_getNextLabel($secondaryValue);
            }       
        }
        elseif (is_a($this->_secondaryAxis, "Image_Graph_Axis_Sequential")) {
            $secondaryAxisPoints = array ($this->_secondaryAxis->_getMinimum() - 0.5, $this->_secondaryAxis->_getMaximum() - 0.5);
        } else {
            $secondaryAxisPoints = array ($this->_secondaryAxis->_getMinimum(), $this->_secondaryAxis->_getMaximum());
        }
        return $secondaryAxisPoints;
    }

    /**
     * Causes the object to update all sub elements coordinates (Image_Graph_Common, does not itself have coordinates, this is basically an abstract method)
     * @access private
     */
    function _updateCoords()
    {
        $this->_setCoords($this->_parent->_plotLeft, $this->_parent->_plotTop, $this->_parent->_plotRight, $this->_parent->_plotBottom);
        parent::_updateCoords();
    }

}

?>