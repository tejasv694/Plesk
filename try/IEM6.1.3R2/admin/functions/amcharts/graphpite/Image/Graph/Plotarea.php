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
 * @package plotarea
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Plotarea.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Graph/Layout.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Layout.php");

/**
 * Plot area used for drawing plots.
 * The plotarea consists of an x-axis and an y-axis, the plotarea can plot multiple
 * charts within one plotares, by simply adding them (the axis' will scale to the
 * plots automatically). A graph can consist of more plotareas
 */
class Image_Graph_Plotarea extends Image_Graph_Layout 
{

    /**
     * The left most pixel of the "real" plot area on the canvas
     * @var int
     * @access private
     */
    var $_plotLeft = 0;

    /**
     * The top most pixel of the "real" plot area on the canvas
     * @var int
     * @access private
     */
    var $_plotTop = 0;

    /**
     * The right most pixel of the "real" plot area on the canvas
     * @var int
     * @access private
     */
    var $_plotRight = 0;

    /**
     * The bottom most pixel of the "real" plot area on the canvas
     * @var int
     * @access private
     */
    var $_plotBottom = 0;

    /**
     * The X axis
     * @var Axis
     * @access private
     */
    var $_axisX = null;

    /**
     * The Y axis
     * @var Axis
     * @access private
     */
    var $_axisY = null;

    /**
     * The border style of the "real" plot area
     * @var LineStyle
     * @access private
     */
    var $_plotBorderStyle = null;

    /**
     * Image_Graph_Plotarea [Constructor]
     * @param Image_Graph_Axis $axisX The X axis (if false or omitted a std. axis is created)
     * @param Image_Graph_Axis $axisY The Y axis (if false or omitted a std. axis is created)
     */
    function &Image_Graph_Plotarea($axisX = false, $axisY = false)
    {
        parent::__construct();       
        
        $this->_padding = 10;
    
        if (($axisX == null) and ($axisX !== false)) {
            $this->_axisX = null;
        }
        elseif ($axisX !== false) {
            $this->_axisX = & $axisX;
        } else {
            $this->_axisX = & new Image_Graph_Axis(IMAGE_GRAPH_AXIS_X);
        }
        if (is_object($this->_axisX)) {
            $this->_axisX->_setParent($this);
        }

        if (($axisY == null) and ($axisY !== false)) {
            $this->_axisY = null;
        } 
        elseif ($axisY !== false) {
            $this->_axisY = & $axisY;
        } else {
            $this->_axisY = & new Image_Graph_Axis(IMAGE_GRAPH_AXIS_Y);
        }
        if (is_object($this->_axisY)) {
            $this->_axisY->_setParent($this);
            $this->_axisY->_setMinimum(0);
        }
    }

    /**
     * Sets the plot border line style of the element	 
     * @param Image_Graph_Line $lineStyle The line style of the border 
     */
    function setPlotBorderStyle(& $plotBorderStyle)
    {
        $this->_plotBorderStyle = & $plotBorderStyle;
        $this->add($plotBorderStyle);
    }

    /**
     * Add a plot to the plotarea    
     * @param Image_Graph_Plot $plotType The plot to add
     * @return Image_Graph_Plot The addded plottype 
     * @see Image_Graph_Common::add() 
     */
    function &addPlot(& $plotType)
    {
        $this->add($plotType);
        $this->_setExtrema($plotType->_minimumX(), $plotType->_maximumX(), $plotType->_minimumY(), $plotType->_maximumY());
        return $plotType;
    }

    /**
     * Add a X-axis grid to the plotarea	 
     * @param Grid $grid The grid to add
     * @see Image_Graph_Common::add() 
     */
    function &addGridX(& $grid)
    {
        if ($this->_axisX != null) {
            $this->add($grid);
	        $grid->_setPrimaryAxis($this->_axisX);
            if ($this->_axisY != null) { 
                $grid->_setSecondaryAxis($this->_axisY);
            }

            return $grid;
        }
    }

    /**
     * Add a Y-axis grid to the plotarea	 
     * @param Grid $grid The grid to add
     * @see Image_Graph_Common::add() 
     */
    function &addGridY(& $grid)
    {
        if ($this->_axisY != null) {
            $this->add($grid);
            $grid->_setPrimaryAxis($this->_axisY);
            if ($this->_axisX != null) { 
                $grid->_setSecondaryAxis($this->_axisX);
            }            
            return $grid;
        }
    }

    /**
     * Get the width of the "real" plotarea	 
     * @return int The width of the "real" plotarea, ie not including space occupied by padding and axis 
     * @access private
     */
    function _plotWidth()
    {
        return abs($this->_plotRight - $this->_plotLeft);
    }

    /**
     * Get the height of the "real" plotarea	 
     * @return int The height of the "real" plotarea, ie not including space occupied by padding and axis 
     * @access private
     */
    function _plotHeight()
    {
        return abs($this->_plotBottom - $this->_plotTop);
    }

    /**
     * Set the extrema of the axis	 
     * @param double MinimumX The minimum X value 
     * @param double MaximumX The maximum X value 
     * @param double MinimumY The minimum Y value 
     * @param double MaximumY The maximum Y value 
     * @access private
     */
    function _setExtrema($minimumX, $maximumX, $minimumY = 0, $maximumY = 0)
    {
        if ($this->_axisX != null) {
            $this->_axisX->_setMinimum($minimumX);
            $this->_axisX->_setMaximum($maximumX);
        }
        if ($this->_axisY != null) {
            $this->_axisY->_setMinimum($minimumY);
            $this->_axisY->_setMaximum($maximumY);
        }
    }

    /**
     * Left boundary of the background fill area 
     * @return int Leftmost position on the canvas
     * @access private
     */
    function _fillLeft()
    {
        return $this->_plotLeft;
    }

    /**
     * Top boundary of the background fill area 
     * @return int Topmost position on the canvas
     * @access private
     */
    function _fillTop()
    {
        return $this->_plotTop;
    }

    /**
     * Right boundary of the background fill area 
     * @return int Rightmost position on the canvas
     * @access private
     */
    function _fillRight()
    {
        return $this->_plotRight;
    }

    /**
     * Bottom boundary of the background fill area 
     * @return int Bottommost position on the canvas
     * @access private
     */
    function _fillBottom()
    {
        return $this->_plotBottom;
    }

    /**
     * Get the X pixel position represented by a value
     * @param double Value the value to get the pixel-point for	 
     * @return double The pixel position along the axis
     * @access private
     */
    function _pointX($value)
    {
        if (is_array($value)) {
            $value = $value['X'];
        }
        if ($this->_axisX == null) {
            return false;
        }
        return max($this->_plotLeft, min($this->_plotRight, $this->_axisX->_point($value)));
    }

    /**
     * Get the Y pixel position represented by a value
     * @param double Value the value to get the pixel-point for	 
     * @return double The pixel position along the axis
     * @access private
     */
    function _pointY($value)
    {
        if (is_array($value)) {
            $value = $value['Y'];
        }
        if ($this->_axisY == null) {
            return false;
        }
        return max($this->_plotTop, min($this->_plotBottom, $this->_axisY->_point($value)));
    }

    /** 
     * Hides the axis
     */
    function hideAxis()
    {
        $this->_axisX = $this->_axisY = null;
    }

    /** 
     * Get axis
     * @param int $Axis The axis to return
     * @return Image_Graph_Axis The axis
     */
    function &getAxis($Axis = IMAGE_GRAPH_AXIS_X)
    {
        switch ($Axis) {
            case IMAGE_GRAPH_AXIS_X: return $this->_axisX; break;
            case IMAGE_GRAPH_AXIS_Y: return $this->_axisY; break;
        }
    }

    /**
     * Update coordinates
     * @access private
     */
    function _updateCoords()
    {
        $this->_debug("Calculating and setting edges");
        $this->_calcEdges();

        $pctWidth = (int) ($this->width() * 0.05);
        $pctHeight = (int) ($this->height() * 0.05);
       
        $this->_debug("Adjusting axis");
        if (($this->_axisX != null) and ($this->_axisY != null)) {
            if (($this->_axisX->_minimum >= 0) and ($this->_axisY->_minimum >= 0)) {
                $this->_debug("Fairly standard situation (MinX>= 0, MinY>= 0), starting X axis");
                $this->_axisX->_setCoords(
                    $this->_left + $this->_axisY->_size() + $this->_padding, 
                    $this->_bottom - $this->_axisX->_size() - $this->_padding, 
                    $this->_right - $this->_padding, 
                    $this->_bottom - $this->_padding
                );                   
                $this->_debug("Done x axis, starting y axis");
                $this->_axisY->_setCoords(
                    $this->_left + $this->_padding, 
                    $this->_top + $this->_padding, 
                    $this->_left + $this->_axisY->_size() + $this->_padding, 
                    $this->_bottom - $this->_axisX->_size() - $this->_padding);
                $this->_debug("Done y axis");
            }
            elseif ($this->_axisX->_minimum >= 0) {
                $this->_axisY->_setCoords(
                    $this->_left, 
                    $this->_top, 
                    $this->_left + $this->_axisY->_size(), 
                    $this->_bottom
                );
                $this->_axisX->_setCoords(
                    $this->_axisY->_right, 
                    $this->_axisY->_point(0), 
                    $this->_right, 
                    $this->_axisY->_point(0) + $this->_axisX->_size()
                );
            }
            elseif ($this->_axisY->_minimum >= 0) {
                $this->_axisX->_setCoords(
                    $this->_left, 
                    $this->_bottom - $this->_axisX->_size(), 
                    $this->_right, 
                    $this->_bottom
                );
                $this->_axisY->_setCoords(
                    $this->_axisX->_point(0) - $this->_axisY->_size(), 
                    $this->_top, 
                    $this->_axisX->_point(0), 
                    $this->_axisX->_top
                );
            } else {
                $this->_axisY->_setCoords(
                    $this->_left + $this->_padding, 
                    $this->_top + $this->_padding, 
                    $this->_right - $this->_padding, 
                    $this->_bottom - $this->_padding
                );
                $this->_axisX->_setCoords(
                    $this->_left + $this->_padding, 
                    $this->_axisY->_point(0), 
                    $this->_right - $this->_padding, 
                    $this->_axisY->_point(0) + $this->_axisX->_size()
                );
                $this->_axisY->_setCoords(
                    $this->_axisX->_point(0) - $this->_axisY->_size(), 
                    $this->_top + $this->_padding, 
                    $this->_axisX->_point(0), 
                    $this->_bottom - $this->_padding);
            }

            //$this->_axisX->shrink($indent, $indent, $indent, $indent);
            //$this->_axisY->shrink($indent, $indent, $indent, $indent);

            $this->_plotLeft = $this->_axisX->_left;
            $this->_plotTop = $this->_axisY->_top;
            $this->_plotRight = $this->_axisX->_right;
            $this->_plotBottom = $this->_axisY->_bottom;
        } else {
            $this->_plotLeft = $this->_left;
            $this->_plotTop = $this->_top;
            $this->_plotRight = $this->_right;
            $this->_plotBottom = $this->_bottom;
        }

        $this->_debug("Updating child elements");
        Image_Graph_Element::_updateCoords();
    }

    /**
     * Output the plotarea to the canvas
     * @access private
     */
    function _done()
    {
        if ($this->_axisX != null) {
            $this->add($this->_axisX);
        }
        if ($this->_axisY != null) {
            $this->add($this->_axisY);
        }

        if ($this->_identify) {
            $red = rand(0, 255);
            $green = rand(0, 255);
            $blue = rand(0, 255);
            $color = ImageColorAllocate($this->_canvas(), $red, $green, $blue);

            if (isset($GLOBALS['_Image_Graph_gd2'])) {
                $alphaColor = ImageColorResolveAlpha($this->_canvas(), $red, $green, $blue, 200);
            } else {
                $alphaColor = $color;
            }

            ImageRectangle($this->_canvas(), $this->_plotLeft, $this->_plotTop, $this->_plotRight, $this->_plotBottom, $color);
            ImageFilledRectangle($this->_canvas(), $this->_plotLeft, $this->_plotTop, $this->_plotRight, $this->_plotBottom, $alphaColor);
        }

        if ($this->_fillStyle) {
            ImageFilledRectangle($this->_canvas(), $this->_plotLeft, $this->_plotTop, $this->_plotRight, $this->_plotBottom, $this->_getFillStyle());
        }

        parent::_done();

        if ($this->_plotBorderStyle) {
            ImageRectangle($this->_canvas(), $this->_plotLeft, $this->_plotTop, $this->_plotRight, $this->_plotBottom, $this->_plotBorderStyle->_getLineStyle());
        }
    }

}

?>