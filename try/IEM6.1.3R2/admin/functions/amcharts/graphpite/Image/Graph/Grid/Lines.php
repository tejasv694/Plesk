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
 * @version $Id: Lines.php,v 1.1 2008/01/24 01:06:07 tye Exp $
 */ 

/**
 * Include file Graph/Grid.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Grid.php");

/**
 * Display a line grid on the plotarea.
 * {@see Image_Graph_Grid} 
 */
class Image_Graph_Grid_Lines extends Image_Graph_Grid 
{

    /**
     * GridLines [Constructor]
     */
    function &Image_Graph_Grid_Lines()
    {
        parent::__construct();
        $this->_lineStyle = IMAGE_GRAPH_LIGHTGRAY;
    }

    /**
     * Output the grid
     * @access private
     */
    function _done()
    {
        parent::_done();

        if (!$this->_primaryAxis) {
            return false;
        }

        $value = $this->_primaryAxis->_getNextLabel();

        $secondaryPoints = $this->_getSecondaryAxisPoints();

        while ($value <= $this->_primaryAxis->_getMaximum()) {
            if ($value > $this->_primaryAxis->_getMinimum()) {
                reset($secondaryPoints);
                list ($id, $previousSecondaryValue) = each($secondaryPoints);
                while (list ($id, $secondaryValue) = each($secondaryPoints)) {
                    if ($this->_primaryAxis->_type == IMAGE_GRAPH_AXIS_Y) {
                        $p1 = array ('X' => $secondaryValue, 'Y' => $value);
                        $p2 = array ('X' => $previousSecondaryValue, 'Y' => $value);
                    } else {
                        $p1 = array ('X' => $value, 'Y' => $secondaryValue);
                        $p2 = array ('X' => $value, 'Y' => $previousSecondaryValue);
                    }

                    $x1 = $this->_parent->_pointX($p1);
                    $y1 = $this->_parent->_pointY($p1);
                    $x2 = $this->_parent->_pointX($p2);
                    $y2 = $this->_parent->_pointY($p2);

                    $previousSecondaryValue = $secondaryValue;

                    ImageLine($this->_canvas(), $x1, $y1, $x2, $y2, $this->_getLineStyle());
                }
            }
            $value = $this->_primaryAxis->_getNextLabel($value);
        }
    }

}
?>