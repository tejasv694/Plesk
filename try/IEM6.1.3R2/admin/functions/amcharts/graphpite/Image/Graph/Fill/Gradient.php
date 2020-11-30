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
 * @version $Id: Gradient.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Graph/Fill/Image.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Fill/Image.php");

/**
 * Fill using a gradient color.
 * This creates a scaled fillstyle with colors flowing gradiently between 2 specified 
 * RGB values. Several directions are supported:
 * 0. Vertically (IMAGE_GRAPH_GRAD_VERTICAL)
 * 1. Horizontally (IMAGE_GRAPH_GRAD_HORIZONTAL)
 * 2. Mirrored vertically (the color grades from a-b-a vertically) (IMAGE_GRAPH_GRAD_VERTICAL_MIRRORED) 
 * 3. Mirrored horizontally (the color grades from a-b-a horizontally) IMAGE_GRAPH_GRAD_HORIZONTAL_MIRRORED 
 * 4. Diagonally from top-left to right-bottom (IMAGE_GRAPH_GRAD_DIAGONALLY_TL_BR) 
 * 5. Diagonally from bottom-left to top-right (IMAGE_GRAPH_GRAD_DIAGONALLY_BL_TR) 
 * 6. Radially (concentric circles in the center) (IMAGE_GRAPH_GRAD_RADIAL)
 */
class Image_Graph_Fill_Gradient extends Image_Graph_Fill_Image 
{

    /**
     * The direction of the gradient
     * @var int
     * @access private
     */
    var $_direction;

    /**
     * The first color to gradient in RGB format
     * @var bool
     * @access private
     */
    var $_startColor;

    /**
     * The last color to gradient in RGB format
     * @var bool
     * @access private
     */
    var $_endColor;

    /**
     * The number of colors to use in the gradient
     * @var bool
     * @access private
     */
    var $_count;

    /**
     * Image_Graph_GradientFill [Constructor]
     * @param int $direction The direction of the gradient
     * @param int $startColor The RGB value of the starting color
     * @param int $endColor The RGB value of the ending color
     * @param int $count The number of steps to be made between the 2 colors (the more the more smooth, but more ressources are required). 100 is default.
     * @param int $alpha The alpha-blend (not supported yet)
     */
    function &Image_Graph_Fill_Gradient($direction, $startColor, $endColor, $count = 100, $alpha = 0)
    {
        parent::__construct();
        $this->_direction = $direction;
        $this->_startColor['RED'] = ($startColor >> 16) & 0xff;
        $this->_startColor['GREEN'] = ($startColor >> 8) & 0xff;
        $this->_startColor['BLUE'] = $startColor & 0xff;

        $this->_endColor['RED'] = ($endColor >> 16) & 0xff;
        $this->_endColor['GREEN'] = ($endColor >> 8) & 0xff;
        $this->_endColor['BLUE'] = $endColor & 0xff;

        $this->_count = $count;

        switch ($this->_direction) {
            case IMAGE_GRAPH_GRAD_HORIZONTAL :
                $width = $this->_count;
                $height = 1;
                break;

            case IMAGE_GRAPH_GRAD_VERTICAL :
                $width = 1;
                $height = $this->_count;
                break;

            case IMAGE_GRAPH_GRAD_HORIZONTAL_MIRRORED :
                $width = 2 * $this->_count;
                $height = 1;
                break;

            case IMAGE_GRAPH_GRAD_VERTICAL_MIRRORED :
                $width = 1;
                $height = 2 * $this->_count;
                break;

            case IMAGE_GRAPH_GRAD_DIAGONALLY_TL_BR :
            case IMAGE_GRAPH_GRAD_DIAGONALLY_BL_TR :
                $width = $height = $this->_count / 2;
                break;

            case IMAGE_GRAPH_GRAD_RADIAL :
                $width = $height = sqrt($this->_count * $this->_count / 2);
                break;
        }

        if (isset($GLOBALS['_Image_Graph_gd2'])) {
            $this->_image = ImageCreateTrueColor($width, $height);
        } else {
            $this->_image = ImageCreate($width, $height);
        }

        $redIncrement = ($this->_endColor['RED'] - $this->_startColor['RED']) / $this->_count;
        $greenIncrement = ($this->_endColor['GREEN'] - $this->_startColor['GREEN']) / $this->_count;
        $blueIncrement = ($this->_endColor['BLUE'] - $this->_startColor['BLUE']) / $this->_count;

        for ($i = 0; $i <= $this->_count; $i ++) {
            if ($i == 0) {
                $red = $this->_startColor['RED'];
                $green = $this->_startColor['GREEN'];
                $blue = $this->_startColor['BLUE'];
            } else {
                $red = round(($redIncrement * $i) + $redIncrement + $this->_startColor['RED']);
                $green = round(($greenIncrement * $i) + $greenIncrement + $this->_startColor['GREEN']);
                $blue = round(($blueIncrement * $i) + $blueIncrement + $this->_startColor['BLUE']);
            }
            $color = ImageColorAllocate($this->_image, $red, $green, $blue);

            switch ($this->_direction) {
                case IMAGE_GRAPH_GRAD_HORIZONTAL :
                    ImageSetPixel($this->_image, $i, 0, $color);
                    break;

                case IMAGE_GRAPH_GRAD_VERTICAL :
                    ImageSetPixel($this->_image, 0, $height - $i, $color);
                    break;

                case IMAGE_GRAPH_GRAD_HORIZONTAL_MIRRORED :
                    ImageSetPixel($this->_image, $i, 0, $color);
                    ImageSetPixel($this->_image, $width - $i, 0, $color);
                    break;

                case IMAGE_GRAPH_GRAD_VERTICAL_MIRRORED :
                    ImageSetPixel($this->_image, 0, $i, $color);
                    ImageSetPixel($this->_image, 0, $height - $i, $color);
                    break;

                case IMAGE_GRAPH_GRAD_DIAGONALLY_TL_BR :
                    if ($i > $width) {
                        $polygon = array ($width, $i - $width, $width, $height, $i - $width, $height);
                    } else {
                        $polygon = array (0, $i, 0, $height, $width, $height, $width, 0, $i, 0);
                    }
                    ImageFilledPolygon($this->_image, $polygon, count($polygon) / 2, $color);
                    break;

                case IMAGE_GRAPH_GRAD_DIAGONALLY_BL_TR :
                    if ($i > $height) {
                        $polygon = array ($i - $height, 0, $width, 0, $width, 2 * $height - $i);
                    } else {
                        $polygon = array (0, $height - $i, 0, 0, $width, 0, $width, $height, $i, $height);
                    }
                    ImageFilledPolygon($this->_image, $polygon, count($polygon) / 2, $color);
                    break;

                case IMAGE_GRAPH_GRAD_RADIAL :
                    if (($GLOBALS['_Image_Graph_gd2']) and ($i < $this->_count)) {
                        ImageFilledEllipse($this->_image, $width / 2, $height / 2, $this->_count - $i, $this->_count - $i, $color);
                    }
                    break;
            }
        }
    }
}

?>