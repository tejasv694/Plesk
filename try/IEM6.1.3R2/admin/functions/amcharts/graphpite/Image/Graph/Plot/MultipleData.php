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
 * @version $Id: MultipleData.php,v 1.1 2008/01/24 01:06:07 tye Exp $
 */ 

/**
 * Include file Graph/Plot.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Plot.php");

/**
 * Plot having multiple data sets
 * @abstract
 */
class Image_Graph_Plot_MultipleData extends Image_Graph_Plot 
{

    /**
     * The datasets to plot
     * @var Datasets
     * @access private
     */
    var $_datasets;

    /**
     * PlotTypeMultipleData [Constructor]
     * @param array $datasets The datasets to plot
     */
    function &Image_Graph_Plot_MultipleData($datasets)
    {
        parent::__construct();
        $this->_datasets = $datasets;
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
        $point['APX'] = $point['X'];
        $point['APY'] = $point['Y'];

        if ($totals['MINIMUM_X'] != 0) {
            $point['PCT_MIN_X'] = 100 * $point['X'] / $totals['MINIMUM_X'];
        }
        if ($totals['MAXIMUM_X'] != 0) {
            $point['PCT_MAX_X'] = 100 * $point['X'] / $totals['MAXIMUM_X'];
        }

        if ($totals['MINIMUM_Y'] != 0) {
            $point['PCT_MIN_Y'] = 100 * $point['Y'] / $totals['MINIMUM_Y'];
        }
        if ($totals['MAXIMUM_Y'] != 0) {
            $point['PCT_MAX_Y'] = 100 * $point['Y'] / $totals['MAXIMUM_Y'];
        }

        if (!$this->_xValueWidth) {
            $point['MARKER_X1'] = $this->_parent->_pointX($point) - $totals['WIDTH'] + $this->_space;
            $point['MARKER_X2'] = $this->_parent->_pointX($point) + $totals['WIDTH'] - $this->_space;
        } else {
            $point['MARKER_X1'] = $this->_parent->_pointX($point['X'] - $this->_xValueWidth / 2) + $this->_space;
            $point['MARKER_X2'] = $this->_parent->_pointX($point['X'] + $this->_xValueWidth / 2) - $this->_space;
        }
        $point['COLUMN_WIDTH'] = abs($point['MARKER_X2'] - $point['MARKER_X1']) / count($this->_datasets);
        $point['MARKER_X'] = $point['MARKER_X1'] + ($totals['NUMBER'] + 0.5) * $point['COLUMN_WIDTH'];
        $point['MARKER_Y'] = $this->_parent->_pointY($point);

        return $point;
    }

    /**
     * Draws markers on the canvas
     * @access private
     */
    function _drawMarker()
    {

        if (($this->_marker) and (is_array($this->_datasets))) {
            reset($this->_datasets);

            $totals['WIDTH'] = $this->width();
            $totals['TOTAL_Y'] = array();
            $keys = array_keys($this->_datasets);
            if (!$this->_xValueWidth) {
                while (list ($ID, $key) = each($keys)) {
                    $dataset = & $this->_datasets[$key];
                    $dataset->_reset();
                    while ($point = $dataset->_next()) {
                        $x = $point['X'];
                        if (isset($totals['TOTAL_Y'][$x])) {
                            $totals['TOTAL_Y'][$x] += $point['Y'];
                        } else {
                            $totals['TOTAL_Y'][$x] = $point['Y'];
                        }
                    }
                }
            }
            $totals['WIDTH'] = $this->width() / ($this->_maximumX() + 2) / 2;

            reset($keys);
            $number = 0;
            while (list ($ID, $key) = each($keys)) {
                $dataset = & $this->_datasets[$key];
                $totals['MINIMUM_X'] = $dataset->minimumX();
                $totals['MAXIMUM_X'] = $dataset->maximumX();
                $totals['MINIMUM_Y'] = $dataset->minimumY();
                $totals['MAXIMUM_Y'] = $dataset->maximumY();
                $totals['NUMBER'] = $number ++;
                $dataset->_reset();
                while ($point = $dataset->_next()) {
                    $x = $point['X'];
                    $y = $point['Y'];
                    if ((!is_object($this->_dataSelector)) or ($this->_dataSelector->select($point))) {
                        $point = $this->_getMarkerData($point, false, false, $totals);
                        if (is_array($point)) {
                            $this->_marker->_drawMarker($point['MARKER_X'], $point['MARKER_Y'], $point);
                        }
                    }
                    if (!isset($totals['SUM_Y'])) {
                        $totals['SUM_Y'] = array();
                    }
                    if (isset($totals['SUM_Y'][$x])) {
                        $totals['SUM_Y'][$x] += $y;
                    } else {
                        $totals['SUM_Y'][$x] = $y;
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
        return 0;
    }

    /**
     * Get the maximum X value from the dataset
     * @return double The maximum X value
     * @access private
     */
    function _maximumX()
    {
        $max = 0;
        if (is_array($this->_datasets)) {
            //reset($this->_datasets);

            $keys = array_keys($this->_datasets);
            while (list ($ID, $key) = each($keys)) {
                $max = max($max, $this->_datasets[$key]->maximumX() + 1);
            }
        }
        return $max;
    }

    /**
     * Get the minimum Y value from the dataset
     * @return double The minimum Y value
     * @access private
     */
    function _minimumY()
    {
        return 0;
    }

    /**
     * Get the maximum Y value from the dataset
     * @return double The maximum Y value
     * @access private
     */
    function _maximumY()
    {
        $maxY = 0;
        if (is_array($this->_datasets)) {
            //reset($this->_datasets);

            $keys = array_keys($this->_datasets);
            while (list ($ID, $key) = each($keys)) {
                $dataset = & $this->_datasets[$key];

                $dataset->_reset();
                while ($point = $dataset->_next()) {
                    $maxY = max($maxY, $point['Y']);
                }
            }
        }
        return $maxY;
    }

    /**
      * Draw a sample for use with legend
      * @param int $x The x coordinate to draw the sample at
      * @param int $y The y coordinate to draw the sample at
      * @access private
      */
    function _legendSample($x, $y, &$font)
    {
        $size['Height'] = 0;
        $size['Width'] = $x;        
        if (is_array($this->_datasets)) {
            if (is_a($this->_fillStyle, "Image_Graph_Fill")) {
                $this->_fillStyle->_reset();
            }

            $count = 0;
            $keys = array_keys($this->_datasets);
            while (list ($ID, $key) = each($keys)) {
                $count++;
                if (is_a($this->_fillStyle, "Image_Graph_Fill")) {
                    $fillStyle = $this->_fillStyle->_getFillStyleAt($x -5, $y -5, 10, 10, $key);
                } else {
                    $fillStyle = $this->_getFillStyle($key);
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

                $text = new Image_Graph_Text($x + 20, $y, $key, $font);
                $text->setAlignment(IMAGE_GRAPH_ALIGN_CENTER_Y | IMAGE_GRAPH_ALIGN_LEFT);
                $this->add($text);
                $text->_done();

                $x += 40+$font->width($key);
                $size['Height'] = max($size['Height'], 10, $font->height($key));                
            }
        }
        $size['Width'] = $x-$size['Width'];        
        return $size;
    }
}

?>