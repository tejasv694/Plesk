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
 * @version $Id: Plot.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Graph/Element.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Element.php");

/**
 * Framework for a chart
 * @abstract 
 */
class Image_Graph_Plot extends Image_Graph_Element 
{

    /**    
     * The dataset to plot
     * @var Dataset
     * @access private
     */
    var $_dataset;

    /**
     * The marker to plot the data set as
     * @var Marker
     * @access private
     */
    var $_marker = null;

    /**
     * The dataselector to use for data marking
     * @var DataSelector
     * @access private
     */
    var $_dataSelector = null;

    /**
     * The title of the plot, used for lefend
     * @var string
     * @access private
     */
    var $_title = "";

    /**
     * PlotType [Constructor]
     * @param Dataset $dataset The data set (value containter) to plot
     * @param string $title The title of the plot (used for legends, {@see Image_Graph_Legend})
     */
    function &Image_Graph_Plot(& $dataset, $title = "")
    {
        parent::__construct();
        if ($dataset) {
            $this->_dataset = & $dataset;            
            if ($title) {
                $this->_title = $title;
            }
        }
    }

    /**
     * Sets the title of the plot, used for legend
     * @param string $title The title of the plot
     */
    function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * Get the point for the X,Y pa
     * @param array $point The data value point to get pixel point for
     * @return array The pixel-point for the X,Y values
     * @access private
     */
    function _pointXY($point)
    {
        return array ('X' => $this->_parent->_pointX($point), 'Y' => $this->_parent->_pointY($point));
    }

    /**
     * Sets the marker to "display" data points on the graph
     * @param Marker $marker The marker
     */
    function &setMarker(& $marker)
    {
        $this->add($marker);
        $this->_marker = & $marker;
        return $marker;
    }

    /**
     * Sets the dataselector to specify which data should be displayed on the plot as markers and which are not
     * @param DataSelector $dataSelector The dataselector
     */
    function setDataSelector(& $dataSelector)
    {
        $this->_dataSelector = & $dataSelector;
    }

    /**
     * Calculate marker point data
     * @param Array Point The point to calculate data for
     * @param Array NextPoint The next point
     * @param Array PrevPoint The previous point
     * @param Array Totals The pre-calculated totals, if needed
     * @return Array An array containing marker point data
     * @access private
     */
    function _getMarkerData($point, $nextPoint, $prevPoint, & $totals)
    {
        if (!$prevPoint) {
            $point['AX'] = -5;
            $point['AY'] = 5;
            $point['PPX'] = 0;
            $point['PPY'] = 0;
            $point['NPX'] = $nextPoint['X'];
            $point['NPY'] = $nextPoint['Y'];
        }
        elseif (!$nextPoint) {
            $point['AX'] = 5;
            $point['AY'] = 5;
            $point['PPX'] = $prevPoint['X'];
            $point['PPY'] = $prevPoint['Y'];
            $point['NPX'] = 0;
            $point['NPY'] = 0;
        } else {
            $point['AX'] = $this->_parent->_pointY($prevPoint) - $this->_parent->_pointY($nextPoint);
            $point['AY'] = $this->_parent->_pointX($nextPoint) - $this->_parent->_pointX($prevPoint);
            $point['PPX'] = $prevPoint['X'];
            $point['PPY'] = $prevPoint['Y'];
            $point['NPX'] = $nextPoint['X'];
            $point['NPY'] = $nextPoint['Y'];
        }

        $point['APX'] = $point['X'];
        $point['APY'] = $point['Y'];

        $point['LENGTH'] = sqrt($point['AX'] * $point['AX'] + $point['AY'] * $point['AY']);
        if ((isset($point['LENGTH'])) and ($point['LENGTH'] != 0)) {
            $point['ANGLE'] = asin($point['AY'] / $point['LENGTH']);
        }

        if ((isset($point['AX'])) and ($point['AX'] > 0)) {
            $point['ANGLE'] = pi() - $point['ANGLE'];
        }

        if ($this->_dataset->minimumX() != 0) {
            $point['PCT_MIN_X'] = 100 * $point['X'] / $this->_dataset->minimumX();
        }

        if ($this->_dataset->maximumX() != 0) {
            $point['PCT_MAX_X'] = 100 * $point['X'] / $this->_dataset->maximumX();
        }

        if ($this->_dataset->minimumY() != 0) {
            $point['PCT_MIN_Y'] = 100 * $point['Y'] / $this->_dataset->minimumY();
        }

        if ($this->_dataset->maximumY() != 0) {
            $point['PCT_MAX_Y'] = 100 * $point['Y'] / $this->_dataset->maximumY();
        }

        $point['AVERAGE_Y'] = $this->_dataset->_averageY();

        $point['MARKER_X'] = $this->_parent->_pointX($point);
        $point['MARKER_Y'] = $this->_parent->_pointY($point);

        return $point;
    }

    /**
     * Draws markers on the canvas
     * @access private
     */
    function _drawMarker()
    {
        if (($this->_marker) and ($this->_dataset)) {
            $this->_dataset->_reset();
            while ($point = $this->_dataset->_next()) {
                $prevPoint = $this->_dataset->_nearby(-2);
                $nextPoint = $this->_dataset->_nearby();

                if ((!is_object($this->_dataSelector)) or ($this->_dataSelector->_select($point))) {
                    $point = $this->_getMarkerData($point, $nextPoint, $prevPoint, $i);
                    if (is_array($point)) {
                        $this->_marker->_drawMarker($point['MARKER_X'], $point['MARKER_Y'], $point);
                    }
                }
            }
        }
    }

    /**
     * Get the minimum X value from the dataset
     * @return double The minimum X value
     * @access private
     */
    function _minimumX()
    {
        if ($this->_dataset) {
            return $this->_dataset->minimumX();
        }
    }

    /**
     * Get the maximum X value from the dataset
     * @return double The maximum X value
     * @access private
     */
    function _maximumX()
    {
        if ($this->_dataset) {
            return $this->_dataset->maximumX();
        }
    }

    /**
     * Get the minimum Y value from the dataset
     * @return double The minimum Y value
     * @access private
     */
    function _minimumY()
    {
        if ($this->_dataset) {
            return $this->_dataset->minimumY();
        }
    }

    /**
     * Get the maximum Y value from the dataset
     * @return double The maximum Y value
     * @access private
     */
    function _maximumY()
    {
        if ($this->_dataset) {
            return $this->_dataset->maximumY();
        }
    }

    /**
     * Update coordinates
     * @access private
     */
    function _updateCoords()
    {
        $this->_setCoords($this->_parent->_plotLeft, $this->_parent->_plotTop, $this->_parent->_plotRight, $this->_parent->_plotBottom);
        parent::_updateCoords();
    }

    /**
      * Draw a sample for use with legend
      * @param int $x The x coordinate to draw the sample at
      * @param int $y The y coordinate to draw the sample at
      * @access private
      */
    function _legendSample($x, $y, &$font)
    {
        if (is_a($this->_fillStyle, "Image_Graph_Fill")) {
            $fillStyle = $this->_fillStyle->_getFillStyleAt($x -5, $y -5, 10, 10);
        } else {
            $fillStyle = $this->_getFillStyle();
        }
        if ($fillStyle != IMG_COLOR_TRANSPARENT) {
            ImageFilledRectangle($this->_canvas(), $x -5, $y -5, $x +5, $y +5, $fillStyle);
            ImageRectangle($this->_canvas(), $x -5, $y -5, $x +5, $y +5, $this->_getLineStyle());
        } else {
            ImageLine($this->_canvas(), $x -7, $y, $x +7, $y, $this->_getLineStyle());
        }

        if (($this->_marker) and ($this->_dataset)) {
            $this->_dataset->_reset();
            $point = $this->_dataset->_next();
            $prevPoint = $this->_dataset->_nearby(-2);
            $nextPoint = $this->_dataset->_nearby();

            $point = $this->_getMarkerData($point, $nextPoint, $prevPoint, $i);
            if (is_array($point)) {
                $point['MARKER_X'] = $x;
                $point['MARKER_Y'] = $y;
                unset ($point['AVERAGE_Y']);
                $this->_marker->_drawMarker($point['MARKER_X'], $point['MARKER_Y'], $point);
            }
        }

        $text = new Image_Graph_Text($x + 20, $y, $this->_title, $font);
        $text->setAlignment(IMAGE_GRAPH_ALIGN_CENTER_Y | IMAGE_GRAPH_ALIGN_LEFT);
        $this->add($text);
        $text->_done();

        return array('Width' => 20+$font->width($this->_title), 'Height' => max(10, $font->height($this->_title)));
    }

}

?>