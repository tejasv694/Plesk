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
 * @package text
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Font.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file ../Common.php
 */
//require_once("../Common.php");

/**
 * A font.
 * @see Image_Graph_Text
 */
class Image_Graph_Font extends Image_Graph_Common 
{

    /**
     * The color of the font
     * @var Color
     * @access private
     */
    var $_color;

    /**
     * Image_Graph_Font [Constructor]
     */
    function Image_Graph_Font()
    {
        parent::__construct();
    }

    /**
     * Set the color of the font
     * @param Color $color The color object of the Font 
     */
    function setColor(& $color)
    {
        $this->_color = $color;
    }

    /**
     * Get the color of the font
     * @return Color The color object of the Font 
     * @access private 
     */
    function _getColor()
    {
        if ($this->_color != null) {
            return $this->_color->_index;
        } else {
            return $this->_color(IMAGE_GRAPH_BLACK);
        }
    }

    /**
     * Get the width of the text specified in pixels
     * @param string $text The text to calc the width for 
     * @return int The width of the text using the specified font 
     */
    function width($text)
    {
        return ImageFontWidth(IMAGE_GRAPH_FONT) * strlen($text);
    }

    /**
     * Get the height of the text specified in pixels
     * @param string $text The text to calc the height for 
     * @return int The height of the text using the specified font 
     */
    function height($text)
    {
        return ImageFontHeight(IMAGE_GRAPH_FONT);
    }

    /**
     * Get the center width of the text specified in pixels
     * @param string $text The text to calc the width for 
     * @return int The center width of the text using the specified font 
     * @access private 
     */
    function _centerWidth($text)
    {
        return (int) ($this->width($text) / 2);
    }

    /**
     * Get the center height of the text specified in pixels
     * @param string $text The text to calc the height for 
     * @return int The center height of the text using the specified font
     * @access private 
     */
    function _centerHeight($text)
    {
        return (int) ($this->height($text) / 2);
    }

    /**
     * Write a text on the canvas
     * @param int $x The X (horizontal) position of the text 
     * @param int $y The Y (vertical) position of the text 
     * @param string $text The text to write on the canvas 
     * @access private 
     */
    function _write($x, $y, $text)
    {
        ImageString($this->_canvas(), IMAGE_GRAPH_FONT, $x, $y, $text, $this->_getColor());
    }

}

?>