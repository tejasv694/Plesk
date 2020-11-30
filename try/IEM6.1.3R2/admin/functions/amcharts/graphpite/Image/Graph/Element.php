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
 * @package common
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Element.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Common.php
 */
require_once("Common.php");

/**
 * Representation of a element in GraPHPite.
 * The Image_Graph_Element can be drawn on the canvas, ie it has coordinates, {@see Image_Graph_Line},
 * {@see Image_Graph_Fill}, border and background - although not all of these may apply to all
 * children.
 * @abstract
 */
class Image_Graph_Element extends Image_Graph_Common 
{

    /** The leftmost pixel of the element on the canvas
     * @var int
     * @access private
     */
    var $_left = 0;

    /** The topmost pixel of the element on the canvas
     * @var int
     * @access private
     */
    var $_top = 0;

    /** The rightmost pixel of the element on the canvas
     * @var int
     * @access private
     */
    var $_right = 0;

    /** The bottommost pixel of the element on the canvas
     * @var int
     * @access private
     */
    var $_bottom = 0;

    /** Background of the element. Default: None
     * @var FillStyle
     * @access private
     */
    var $_background = null;

    /** Borderstyle of the element. Default: None
     * @var LineStyle
     * @access private
     */
    var $_borderStyle = null;

    /** Line style of the element. Default: None
     * @var LineStyle
     * @access private
     */
    var $_lineStyle = 0x000000;

    /** Fill style of the element. Default: None
     * @var FillStyle
     * @access private
     */
    var $_fillStyle = 0xffffff;

    /** Font of the element. Default: Standard font - FONT
     * @var Font
     * @access private
     * @see $IMAGE_GRAPH_FONT
     */
    var $_font = null;

    /** Enable shadows on the element
     * @var bool
     * @access private
     */
    var $_shadow = false;
    
    /** The padding displayed on the element
     * @var int
     * @access private
     */   
    var $_padding = 0;
    
    /**
     * Gets an identification text
     * @return string A textual identification of the object 
     * @access private
     */
    function _identification()
    {
        return parent::_identification()." ($this->_left, $this->_top, $this->_right, $this->_bottom)";
    }

    /**
     * Gets the parent chain path	 
     * @return string A textual representation of the parent chain 
     * @access private
     */
    function _parentPath()
    {
        return parent::_parentPath()."($this->_left, $this->_top, $this->_right, $this->_bottom)";
    }
    
    /**
     * Sets the background fill style of the element     
     * @param Image_Graph_Fill $background The background 
     * @see Image_Graph_Fill
     */
    function setBackground(& $background)
    {
        $this->_debug("Setting background");
        $this->_background = & $background;
        $this->add($background);
    }

    /**
     * Shows shadow on the element     
     */
    function showShadow()
    {
        $this->_shadow = true;
    }

    /**
     * Sets the background color of the element    
     * @param int $red The red part (or the whole)
     * @param int $green The green part (if omitted the $red part must contain the whole 24-bit color)
     * @param int $blue The blue part (if omitted the $red part must contain the whole 24-bit color)
     */
    function setBackgroundColor($red, $green = false, $blue = false)
    {
        $this->_debug("Setting line color");
        if ($green === false) {
            $this->_background = $red;
        } else {
            $this->_background = ($red << 16) + ($green << 8) + $blue;
        }
    }
    
     /**
     * Gets the background fill style of the element     
     * @return int A GD fillstyle representing the background style 
     * @see Image_Graph_Fill
     * @access private
     */
    function _getBackground()
    {
        $this->_debug("Getting background");
        if (is_numeric($this->_background)) {
            return $this->_color($this->_background);
        }
        elseif ($this->_background != null) {
            if (is_a($this->_background, "Image_Graph_Color")) {
                return $this->_background->_index;
            } else {
                return $this->_background->_getFillStyle();
            }
        } else {
            return $this->_color(IMAGE_GRAPH_TRANSPARENT);
        }
    }

    /**
     * Sets the border line style of the element	 
     * @param Image_Graph_Line $borderStyle The line style of the border 
     * @see Image_Graph_Line
     */
    function setBorderStyle(& $borderStyle)
    {
        $this->_debug("Setting border");
        $this->_borderStyle = & $borderStyle;
        $this->add($borderStyle);
    }

    /**
     * Sets the border color of the element    
     * @param int $red The red part (or the whole)
     * @param int $green The green part (if omitted the $red part must contain the whole 24-bit color)
     * @param int $blue The blue part (if omitted the $red part must contain the whole 24-bit color)
     */
    function setBorderColor($red, $green = false, $blue = false)
    {
        $this->_debug("Setting border color");
        if ($green === false) {
            $this->_borderStyle = $red;
        } else {
            $this->_borderStyle = ($red << 16) + ($green << 8) + $blue;
        }
    }

    /**
     * Gets the border line style of the element	 
     * @return int A GD linestyle representing the borders line style 
     * @see Image_Graph_Line
     * @access private
     */
    function _getBorderStyle()
    {
        $this->_debug("Getting border");
        if (is_numeric($this->_borderStyle)) {
            if (isset($GLOBALS['_Image_Graph_gd2'])) {
                ImageSetThickness($this->_canvas(), 1);
            }
            return $this->_color($this->_borderStyle);
        }
        elseif ($this->_borderStyle != null) {
            if (is_a($this->_borderStyle, "Image_Graph_Color")) {
                return $this->_borderStyle->_index;
            } else {
                return $this->_borderStyle->_getLineStyle();
            }
        } else {
            if (isset($GLOBALS['_Image_Graph_gd2'])) {
                ImageSetThickness($this->_canvas(), 1);
            }
            return $this->_color(IMAGE_GRAPH_TRANSPARENT);
        }
    }

    /**
     * Sets the line style of the element    
     * @param Image_Graph_Line $lineStyle The line style of the element 
     * @see Image_Graph_Line
     */
    function setLineStyle(& $lineStyle)
    {
        $this->_debug("Setting line style");
        $this->_lineStyle = & $lineStyle;
        $this->add($lineStyle);
    }
    
    /**
     * Sets the line color of the element    
     * @param int $red The red part (or the whole)
     * @param int $green The green part (if omitted the $red part must contain the whole 24-bit color)
     * @param int $blue The blue part (if omitted the $red part must contain the whole 24-bit color)
     */
    function setLineColor($red, $green = false, $blue = false)
    {
        $this->_debug("Setting line color");
        if ($green === false) {
            $this->_lineStyle = $red;
        } else {
            $this->_lineStyle = ($red << 16) + ($green << 8) + $blue;
        }
    }

    /**
     * Gets the line style of the element	 
     * @return int A GD linestyle representing the line style 
     * @see Image_Graph_Line
     * @access private
     */
    function _getLineStyle()
    {
        $this->_debug("Getting line style");
        if (is_numeric($this->_lineStyle)) {
            if (isset($GLOBALS['_Image_Graph_gd2'])) {
                ImageSetThickness($this->_canvas(), 1);
            }
            return $this->_color($this->_lineStyle);
        }
        elseif ($this->_lineStyle != null) {
            if (is_a($this->_lineStyle, "Image_Graph_Color")) {
                ImageSetThickness($this->_canvas(), 1);
                return $this->_lineStyle->_index;
            } else {
                return $this->_lineStyle->_getLineStyle();
            }
        } else {
            if (isset($GLOBALS['_Image_Graph_gd2'])) {
                ImageSetThickness($this->_canvas(), 1);
            }
            return $this->_color(IMAGE_GRAPH_BLACK);
        }
    }

    /**
     * Sets the fill style of the element	 
     * @param Image_Graph_Fill $fillStyle The fill style of the element 
     * @see Image_Graph_Fill
     */
    function setFillStyle(& $fillStyle)
    {
        $this->_debug("Setting fill style");
        $this->_fillStyle = & $fillStyle;
        $this->add($fillStyle);
    }

    /**
     * Sets the fill color of the element    
     * @param int $red The red part (or the whole)
     * @param int $green The green part (if omitted the $red part must contain the whole 24-bit color)
     * @param int $blue The blue part (if omitted the $red part must contain the whole 24-bit color)
     */
    function setFillColor($red, $green = false, $blue = false)
    {
        $this->_debug("Setting fill color");
        if ($green === false) {
            $this->_fillStyle = $red;
        } else {
            $this->_fillStyle = ($red << 16) + ($green << 8) + $blue;
        }
    }
    

    /**
     * Gets the fill style of the element	 
     * @return int A GD filestyle representing the fill style 
     * @see Image_Graph_Fill
     * @access private
     */
    function _getFillStyle($ID = false)
    {
        $this->_debug("Getting fill style");
        if ($this->_fillStyle != null) {
        	if (is_numeric($this->_fillStyle)) {
                return $this->_color($this->_fillStyle);
            }
            elseif (is_a($this->_fillStyle, "Image_Graph_Color")) {
                return $this->_fillStyle->_index;
            } else {
                return $this->_fillStyle->_getFillStyle($ID);
            }
        } else {
            return $this->_color(IMAGE_GRAPH_TRANSPARENT);
        }
    }

    /**
     * Sets the font of the element	 
     * @param Font $font The font of the element 
     * @see Image_Graph_Font
     */
    function setFont(& $font)
    {
        $this->_debug("Setting font");
        $this->_font = & $font;
        $this->add($font);
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
        $this->_debug("Setting coordinates ($left, $top, $right, $bottom)");
        $this->_left = min($left, $right);
        $this->_top = min($top, $bottom);
        $this->_right = max($left, $right);
        $this->_bottom = max($top, $bottom);
    }

    /**
     * Moves the element	 
     * @param int $deltaX Number of pixels to move the element to the right (negative values move to the left) 
     * @param int $deltaY Number of pixels to move the element downwards (negative values move upwards) 
     * @access private
     */
    function _move($deltaX, $deltaY)
    {
        $this->_debug("Moving ($deltaX, $deltaY)");
        $this->_left += $deltaX;
        $this->_right += $deltaX;
        $this->_top += $deltaY;
        $this->_bottom += $deltaY;
    }

    /**
     * Sets the width of the element relative to the left side	 
     * @param int $width Number of pixels the element should be in width  
     * @access private
     */
    function _setWidth($width)
    {
        $this->_debug("Setting Width ($width) -> expanding right border");
        $this->_right = $this->_left + $width;
    }

    /**
     * Sets the height of the element relative to the top    
     * @param int $width Number of pixels the element should be in height  
     * @access private
     */
    function _setHeight($height)
    {
        $this->_debug("Setting Height ($height) -> expanding bottom border");
        $this->_bottom = $this->_top + $height;
    }
    
    /**
     * Sets padding of the element    
     * @param int $padding Number of pixels the element should be padded with  
     */
    function setPadding($padding)
    {
        $this->_padding = $padding;
    }

    /**
     * Shrink the element. Negative values will cause the size to grow! 
     * @param int $left Number of pixels to shrink in the left side
     * @param int $top Number of pixels to shrink in the top
     * @param int $right Number of pixels to shrink in the right side
     * @param int $bottom Number of pixels to shrink in the bottom
     * @see Image_Graph_Common
     * @access private
     */
    function _shrink($left, $top, $right, $bottom)
    {
        $this->_left += $left;
        $this->_top += $top;
        $this->_right -= $right;
        $this->_bottom -= $bottom;
    }

    /**
     * Indents the element, sub elements are shrunk accordingly 
     * @param int $left Number of pixels to indent on the left side
     * @param int $top Number of pixels to indent in the top
     * @param int $right Number of pixels to indent on the right side
     * @param int $bottom Number of pixels to indent in the bottom
     * @access private
     */
    function _indent($left, $top, $right, $bottom)
    {
        $this->_debug("Indents elements ($left, $right, $top, $bottom)");
        if (is_array($this->_elements)) {
            reset($this->_elements);

            $keys = array_keys($this->_elements);
            while (list ($ID, $key) = each($keys)) {
                $this->_elements[$key]->shrink($left, $top, $right, $bottom);
            }
        }
    }

    /**
     * The width of the element on the canvas 
     * @return int Number of pixels representing the width of the element
     */
    function width()
    {
        $this->_debug("Get Width");
        return abs($this->_right - $this->_left) + 1;
    }

    /**
     * The height of the element on the canvas 
     * @return int Number of pixels representing the height of the element
     */
    function height()
    {
        $this->_debug("Get Height");
        return abs($this->_bottom - $this->_top) + 1;
    }

    /**
     * Left boundary of the background fill area 
     * @return int Leftmost position on the canvas
     * @access private
     */
    function _fillLeft()
    {
        return $this->_left + $this->_padding;
    }

    /**
     * Top boundary of the background fill area 
     * @return int Topmost position on the canvas
     * @access private
     */
    function _fillTop()
    {
        return $this->_top + $this->_padding;
    }

    /**
     * Right boundary of the background fill area 
     * @return int Rightmost position on the canvas
     * @access private
     */
    function _fillRight()
    {
        return $this->_right - $this->_padding;
    }

    /**
     * Bottom boundary of the background fill area 
     * @return int Bottommost position on the canvas
     * @access private
     */
    function _fillBottom()
    {
        return $this->_bottom - $this->_padding;
    }
    
    /**
     * Returns the filling width of the element on the canvas 
     * @return int Filling width
     * @access private
     */
    function _fillWidth()
    {
        return $this->_fillRight() - $this->_fillLeft() + 1;
    }    
    
    /**
     * Returns the filling height of the element on the canvas 
     * @return int Filling height
     * @access private
     */
    function _fillHeight()
    {
        return $this->_fillBottom() - $this->_fillTop() + 1;
    }    
    
    /**
     * Draws a shadow "around" the element
     * @access private 
     */
    function _displayShadow()
    {
                
        $shadows['TR'] = ImageCreateFromPNG(dirname(__FILE__)."/Images/Shadows/tr.png");
        $shadows['R'] = ImageCreateFromPNG(dirname(__FILE__)."/Images/Shadows/r.png");
        $shadows['BL'] = ImageCreateFromPNG(dirname(__FILE__)."/Images/Shadows/bl.png");
        $shadows['B'] = ImageCreateFromPNG(dirname(__FILE__)."/Images/Shadows/b.png");
        $shadows['BR'] = ImageCreateFromPNG(dirname(__FILE__)."/Images/Shadows/br.png");                        
        
        $tR['X'] = floor($this->_right+1);
        $tR['Y'] = floor($this->_top);
        $tR['W'] = ImageSX($shadows['TR']);
        $tR['H'] = ImageSY($shadows['TR']);

        $r['X'] = $tR['X'];
        $r['Y'] = $tR['Y'] + $tR['H'];
        $r['W'] = ImageSX($shadows['R']);
        $r['H'] = floor($this->_bottom - $r['Y'] + 1);
        
        $bR['X'] = $tR['X'];
        $bR['Y'] = $r['Y'] + $r['H'];       
        $bR['W'] = ImageSX($shadows['BR']);
        $bR['H'] = ImageSY($shadows['BR']);       

        $bL['X'] = floor($this->_left);
        $bL['Y'] = $bR['Y'];       
        $bL['W'] = ImageSX($shadows['BL']);
        $bL['H'] = ImageSY($shadows['BL']);       

        $b['X'] = $bL['X'] + $bL['W'];
        $b['Y'] = $bL['Y'];
        $b['W'] = floor($bR['X']-$bL['X']-$bL['W']);
        $b['H'] = ImageSY($shadows['B']);
                      
        
        ImageCopyResampled($this->_canvas(), $shadows['TR'], $tR['X'], $tR['Y'], 0, 0, $tR['W'], $tR['H'], $tR['W'], $tR['H']);                                               
        ImageCopyResampled($this->_canvas(), $shadows['BR'], $bR['X'], $bR['Y'], 0, 0, $bR['W'], $bR['H'], $bR['W'], $bR['H']);                                               
        ImageCopyResampled($this->_canvas(), $shadows['BL'], $bL['X'], $bL['Y'], 0, 0, $bL['W'], $bL['H'], $bL['W'], $bL['H']);                                               
       
        ImageCopyResampled($this->_canvas(), $shadows['R'], $r['X'], $r['Y'], 0, 0, $r['W'], $r['H'], $r['W'], ImageSY($shadows['R']));                                               
        ImageCopyResampled($this->_canvas(), $shadows['B'], $b['X'], $b['Y'], 0, 0, $b['W'], $b['H'], ImageSX($shadows['B']), $b['H']);                                               
        
        ImageDestroy($shadows['TR']);                                              
        ImageDestroy($shadows['R']);                                              
        ImageDestroy($shadows['BL']);                                              
        ImageDestroy($shadows['B']);                                              
        ImageDestroy($shadows['BR']);                                              
    }
    

    /**
     * Output the element to the canvas
     * @see Image_Graph_Common 
     * @access private
     */
    function _done()
    {
        if (is_a($this->_fillStyle, "Image_Graph_Fill")) {
            $this->_fillStyle->_reset();
        }
                
        if ($this->_background != null) {
            $this->_debug("Drawing background");
            ImageFilledRectangle($this->_canvas(), $this->_left, $this->_top, $this->_right, $this->_bottom, $this->_getBackground());
        }

        if ($this->_identify) {
            $this->_debug("Identifying");
            $red = rand(0, 255);
            $green = rand(0, 255);
            $blue = rand(0, 255);
            $color = ImageColorAllocate($this->_canvas(), $red, $green, $blue);
            if (isset($GLOBALS['_Image_Graph_gd2'])) {
                $alphaColor = ImageColorResolveAlpha($this->_canvas(), $red, $green, $blue, 200);
            } else {
                $alphaColor = $color;
            }

            ImageRectangle($this->_canvas(), $this->_left, $this->_top, $this->_right, $this->_bottom, $color);
            ImageFilledRectangle($this->_canvas(), $this->_left, $this->_top, $this->_right, $this->_bottom, $alphaColor);

            if ($this->_identifyText) {
                $text = eregi_replace("<[^>]*>([^<]*)", "\\1", $this->_identification());
                if (ImageFontWidth(IMAGE_GRAPH_FONT) * strlen($text) > $this->width()) {
                    $x = max($this->_left, min($this->_right, $this->_left + ($this->width() - ImageFontHeight(IMAGE_GRAPH_FONT)) / 2));
                    $y = max($this->_top, min($this->_bottom, $this->_bottom - ($this->height() - ImageFontWidth(IMAGE_GRAPH_FONT) * strlen($text)) / 2));
                    ImageStringUp($this->_canvas(), FONT, $x, $y, $text, $color);
                } else {
                    $x = max($this->_left, min($this->_right, $this->_left + ($this->width() - ImageFontWidth(IMAGE_GRAPH_FONT) * strlen($text)) / 2));
                    $y = max($this->_top, min($this->_bottom, $this->_top + ($this->height() - ImageFontHeight(IMAGE_GRAPH_FONT)) / 2));
                    ImageString($this->_canvas(), FONT, $x, $y, $text, $color);
                }
            }
        }

        if ($this->_borderStyle != null) {
            $this->_debug("Drawing border");
            ImageRectangle($this->_canvas(), $this->_left, $this->_top, $this->_right, $this->_bottom, ((is_a($this->_borderStyle, "Image_Graph_Color")) ? $this->_borderStyle->_index : $this->_borderStyle->_getLineStyle()));
        }
        parent::_done();
        
        if ($this->_shadow) {
            $this->_displayShadow();
        }
    }

}
?>