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
 * @package plottype
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Bar.php,v 1.1 2008/01/24 01:06:07 tye Exp $
 */ 

/**
 * Include file Graph/Plot.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Plot.php");

/**
 * A bar chart. 
 */
class Image_Graph_Plot_Bar extends Image_Graph_Plot 
{

    /**
     * The space between 2 bars (should be a multipla of 2)
     * @var int
     * @access private
     */
    var $_space = 2;

    /**
     * The width of a single bar in terms on an "X" value
     * @var double
     * @access private
     */
    var $_xValueWidth = 0;

    /**
     * Set the spacing between 2 neighbouring bars
     * @param int $space The number of pixels between 2 bars, should be a multipla of 2 (ie an even number)
     */
    function spacing($space)
    {
        $this->_space = (int) ($space / 2);
    }

    /**
     * Set the width of a single bar in terms on an "X" value
     * @param double $xValueWidth The width of a single bar in terms on an "X" value 
     */
    function setXValueWidth($xValueWidth)
    {
        $this->_xValueWidth = $xValueWidth;
    }

    /**
     * Get the minimum X value from the dataset
     * @return double The minimum X value
     * @access private
     */
    function _minimumX()
    {
        return 0;
    }

    /**
     * Get the maximum X value from the dataset
     * @return double The maximum X value
     * @access private
     */
    function _maximumX()
    {
        if ($this->_dataset) {
            return $this->_dataset->count() + 1;
        }
    }

    /**
     * Output the plot
     * @access private
     */
    function _done()
    {
        parent::_done();
        if ($this->_dataset) {
            if (!$this->_xValueWidth) {
                $width = ($this->width() / ($this->_dataset->count() + 2)) / 2;
            }

            $this->_dataset->_reset();
            while ($point = $this->_dataset->_next()) {
                if (!$this->_xValueWidth) {
                    $x1 = $this->_parent->_pointX($point) - $width + $this->_space;
                    $x2 = $this->_parent->_pointX($point) + $width - $this->_space;
                } else {
                    $x1 = $this->_parent->_pointX($point['X'] - $this->_xValueWidth / 2) + $this->_space;
                    $x2 = $this->_parent->_pointX($point['X'] + $this->_xValueWidth / 2) - $this->_space;
                }
                $y1 = $this->_parent->_pointY(0);
                $y2 = $this->_parent->_pointY($point);
                ImageFilledRectangle($this->_canvas(), min($x1, $x2), min($y1, $y2), max($x1, $x2), max($y1, $y2), $this->_getFillStyle());
                ImageRectangle($this->_canvas(), min($x1, $x2), min($y1, $y2), max($x1, $x2), max($y1, $y2), $this->_getLineStyle());
            }
            $this->_drawMarker();
        }
    }
}

?>