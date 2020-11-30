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
 * @package color
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Color.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Graph/Common.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Common.php");

/**
 * RGB Color representation used for advanced manipulation, such as setting alpha channel
 * and/or calculating HSB values. 
 * It is not necesary to use the Color class 
 * for every representable colors. Named colors are simple RGB contant values and can
 * be used for creating Color objects. This is to avoid a large overhead by creating numerous
 * unneeded objects (an object for every named color).  
 */
class Image_Graph_Color extends Image_Graph_Common 
{

    /**
     * The red part of the RGB code
     * @var int
     * @access private
     */
    var $_red;

    /**
     * The green part of the RGB code
     * @var int
     * @access private
     */
    var $_green;

    /**
     * The blue part of the RGB code
     * @var int
     * @access private
     */
    var $_blue;

    /**
     * The hue of the color
     * @var int
     * @access private
     */
    var $_hue = 0;

    /**
     * The saturation of the color
     * @var int
     * @access private
     */
    var $_saturation = 0;

    /**
     * The brightness of the color
     * @var int
     * @access private
     */
    var $_brightness = 0;

    /**
     * The allocated index on the GD image
     * @var int
     * @access private
     */
    var $_index;

    /**
     * The alpha blending value, default: 0, between 0 (full color) and 255 (transparent)
     * @var int
     * @access private
     */
    var $_alpha = 0;

    /**
     * Allocate the color
     * @param int $red The red part of the RGB code
     * @param int $green The green part of the RGB code
     * @param int $blue The blue part of the RGB code	 
     */
    function &Image_Graph_Color($red, $green = false, $blue = false)
    {
        parent::__construct();
        if ($green === false) {            
            $this->_red = ($red >> 16) & 0xff;
            $this->_green = ($red >> 8) & 0xff;
            $this->_blue = $red & 0xff;
        } else {            
            $this->_red = $red;        
            $this->_green = $green;
            $this->_blue = $blue;
        }
    }

    /**
     * Set the alpha (opacity) of the color
     * @param int $alpha The alpha blending value, between 0 (full color) and 255 (transparent)
     */
    function setAlpha($alpha)
    {
        $this->_alpha = $alpha;
        $this->_setParent($this->_parent);
    }

    /**
     * Return the Red of the RGB value
     * @return int The Red part
     */
    function red()
    {
        return $this->_red;
    }

    /**
     * Return the Green of the RGB value
     * @return int The Green part
     */
    function green()
    {
        return $this->_green;
    }

    /**
     * Return the Blue of the RGB value
     * @return int The Blue part
     */
    function blue()
    {
        return $this->_blue;
    }

    /**
     * Return the hue of the color.
     * Hue is the color reflected from or transmitted through an object. It is measured as a location on the standard color wheel, expressed as a degree between 0° and 360°. In common use, hue is identified by the name of the color such as red, orange, or green.
     * @return int The hue of the color in degrees
     */
    function hue()
    {
        $this->_hSB();
        return round($this->_hue * 360 / 255);
    }

    /**
     * Return the saturation of the color
     * Saturation, sometimes called chroma, is the strength or purity of the color. Saturation represents the amount of gray in proportion to the hue, measured as a percentage from 0% (gray) to 100% (fully saturated). On the standard color wheel, saturation increases from the center to the edge.
     * @return int The saturation of the color in percent
     */
    function saturation()
    {
        $this->_hSB();
        return round($this->_saturation * 100);
    }

    /**
     * Return the brightness of the color
     * Brightness is the relative lightness or darkness of the color, usually measured as a percentage from 0% (black) to 100% (white).
     * @return int The brightness of the color in percent
     */
    function brightness()
    {
        $this->_hSB();
        return round($this->_brightness * 100);
    }

    /**
     * Calculate HSB (Hue, Saturation and Brightness) of the RGB value
     * @access private
     */
    function _hSB()
    {
        $this->_hue = $this->_saturation = $this->_brightness = 0;

        $max = max($this->_red, max($this->_green, $this->_blue));
        $min = min($this->_red, min($this->_green, $this->_blue));
        $rGB = array ($this->_red, $this->_green, $this->_blue);

        $this->_brightness = $max / 255;
        if ($this->_brightness == 0) {
            return;
        }

        $this->_saturation = ($max - $min) / $max;
        if ($this->_saturation == 0) {
            return;
        }

        for ($i = 0; $i < 3; $i ++) {
            $rGB[$i] = round(255 - (255 - $rGB[$i] * (1 / $this->_brightness)) * (1 / $this->_saturation));
            $tempRange += $rGB[$i];
        }
        $tempRange -= 255;

        if ($tempRange == 0) {
            if ($rGB[0] = 255) {
                $phase = 0;
            }
            elseif ($rGB[1] = 255) {
                $phase = 2;
            }
            elseif ($rGB[2] = 255) {
                $phase = 4;
            }
        }
        elseif ($tempRange == 255) {
            if ($rGB[0] = 0) {
                $phase = 3;
            }
            elseif ($rGB[1] = 0) {
                $phase = 5;
            }
            elseif ($rGB[2] = 0) {
                $phase = 1;
            }
        } else {
            for ($i = 0; $i < 3; $i ++) {
                if (($rGB[$i] > $rGB[($i +1) % 3]) && ($rGB[($i +1) % 3] > $rGB[($i +2) % 3])) {
                    $phase = $i * 2;
                    break;
                }
                elseif (($rGB[$i] > $rGB[($i +2) % 3]) && ($rGB[($i +2) % 3] > $rGB[($i +1) % 3])) {
                    $phase = ($i * 2 + 5) % 6;
                    break;
                }
            }
        }

        if ($phase % 2 == 0) {
            $range = $tempRange;
        } else {
            $range = 255 - $tempRange;
        }

        $this->_hue = (($phase * 255 + $range) / 6);
    }

    /**
     * Calculate RGB from the HSB (Hue, Saturation and Brightness)
     * @access private
     */
    function _rGB()
    {
        $range = (6 * $this->_hue) % 255;
        $phase = floor(6 * $this->_hue / 255);

        $mid = (7 - $phase) % 3;

        $tmp[floor(($phase +7) / 2) % 3] = 255;
        $tmp[(floor($phase / 2) + 5) % 3] = 0;

        if (($phase % 2) == 1) {
            $tmp[$mid] = 255 - $range;
        } else {
            $tmp[$mid] = $range;
        }

        for ($i = 0; $i < 3; $i ++) {
            $rGB[$i] = round($this->_brightness * (255 - ((255 - $tmp[$i]) * $this->_saturation)));
        }

        $this->_red = $rGB[0];
        $this->_green = $rGB[1];
        $this->_blue = $rGB[2];
    }

    /**
     * Sets the parent. The parent chain should ultimately be a GraPHP object
     * @see Image_Graph_Common
     * @param Image_Graph_Common Parent The parent 
     * @access private
     */
    function _setParent(& $parent)
    {
        parent::_setParent($parent);
        if ((!$this->_alpha) or (!$GLOBALS['_Image_Graph_gd2'])) {
            $this->_index = ImageColorAllocate($this->_canvas(), $this->_red, $this->_green, $this->_blue);
        } else {
            $this->_index = ImageColorResolveAlpha($this->_canvas(), $this->_red, $this->_green, $this->_blue, $this->_alpha);
        }
    }

}

?>