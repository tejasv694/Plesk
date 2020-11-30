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
 * @version $Id: Multiple.php,v 1.1 2008/01/24 01:06:07 tye Exp $
 */ 

/**
 * Include file Graph/Plot/MultipleData.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Plot/MultipleData.php");

/**
 * Multiple barchart.   
 * This is used to display multiple barcharts withing the same chart (i.e. the bars for
 * same X-value are displayed directly next to one another).
 */
class Image_Graph_Plot_Bar_Multiple extends Image_Graph_Plot_MultipleData 
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
     * Output the plot
     * @access private
     */
    function _done()
    {
        parent::_done();

        if (is_array($this->_datasets)) {
            //reset($this->_datasets);

            if (!$this->_xValueWidth) {
                $width = $this->width() / ($this->_maximumX() + 2) / 2;
            }

            $keys = array_keys($this->_datasets);
            $number = 0;
            while (list ($ID, $key) = each($keys)) {
                $dataset = & $this->_datasets[$key];
                $dataset->_reset();
                while ($point = $dataset->_next()) {
                    if (!$this->_xValueWidth) {
                        $x1 = $this->_parent->_pointX($point) - $width + $this->_space;
                        $x2 = $this->_parent->_pointX($point) + $width - $this->_space;
                    } else {
                        $x1 = $this->_parent->_pointX($point['X'] - $this->_xValueWidth / 2) + $this->_space;
                        $x2 = $this->_parent->_pointX($point['X'] + $this->_xValueWidth / 2) - $this->_space;
                    }
                    $w = abs($x2 - $x1) / count($this->_datasets);
                    $x2 = ($x1 = $x1 + $number * $w) + $w;
                    $y1 = $this->_parent->_pointY(0);
                    $y2 = $this->_parent->_pointY($point);
                    ImageFilledRectangle($this->_canvas(), min($x1, $x2), min($y1, $y2), max($x1, $x2), max($y1, $y2), $this->_getFillStyle($key));
                    // Modified: Don't draw border
                    //ImageRectangle($this->_canvas(), min($x1, $x2), min($y1, $y2), max($x1, $x2), max($y1, $y2), $this->_getLineStyle());
                }
                $number ++;
            }
            $this->_drawMarker();
        }
    }
}

?>