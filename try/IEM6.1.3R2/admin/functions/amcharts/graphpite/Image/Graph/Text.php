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
 * @version $Id: Text.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Graph/Element.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Element.php");

/**
 * Textual element.
 */
class Image_Graph_Text extends Image_Graph_Element 
{

    /**
     * The text to print
     * @var string
     * @access private
     */
    var $_text;

    /**
     * Alignment of the text
     * @var int
     * @access private
     */
    var $_alignment;

    /**
     * The font to use
     * @var Font
     * @access private
     */
    var $_font;

    /**
     * Create the text
     * @param int $x The X position (horizontal) position of the text (center point) on the canvas
     * @param int $y The Y position (vertical) position of the text (center point) on the canvas
     * @param string $text The text to display
     * @param Font $font The font to use in printing
     */
    function &Image_Graph_Text($x, $y, $text, & $font)
    {
        parent::__construct();
        $this->_alignment = IMAGE_GRAPH_ALIGN_LEFT + IMAGE_GRAPH_ALIGN_TOP;
        $this->_font = & $font;
        $this->_setCoords($x, $y, $x, $y);
        $this->setText($text);
    }

    /**
     * Set the text
     * @param string $text The text to display
     */
    function setText($text)
    {
        $this->_text = $text;
    }

    /**
     * Set the alignment of the text
     * @param int $alignment The alignment
     */
    function setAlignment($alignment)
    {
        $this->_alignment = $alignment;
    }

    /**
     * The width of the text on the canvas 
     * @return int Number of pixels representing the width of the text
     */
    function width()
    {
        $this->_font->width($this->_text);
    }

    /**
     * The height of the text on the canvas 
     * @return int Number of pixels representing the height of the text
     */
    function height()
    {
        return $this->_font->height($this->_text);
    }

    /**
     * Left boundary of the background fill area 
     * @return int Leftmost position on the canvas
     * @access private
     */
    function _fillLeft()
    {
        if ($this->_alignment & IMAGE_GRAPH_ALIGN_RIGHT) {
            return $this->_left - $this->_font->width($this->_text);
        }
        if ($this->_alignment & IMAGE_GRAPH_ALIGN_CENTER_X) {
            return $this->_left - $this->_font->_centerWidth($this->_text);
        } else {
            return $this->_left;
        }
    }

    /**
     * Top boundary of the background fill area 
     * @return int Topmost position on the canvas
     * @access private
     */
    function _fillTop()
    {
        if ($this->_alignment & IMAGE_GRAPH_ALIGN_BOTTOM) {
            return $this->_top - $this->_font->height($this->_text);
        }
        if ($this->_alignment & IMAGE_GRAPH_ALIGN_CENTER_Y) {
            return $this->_top - $this->_font->_centerHeight($this->_text);
        } else {
            return $this->_top;
        }
    }

    /**
    * Right boundary of the background fill area 
    * @return int Rightmost position on the canvas
     * @access private
    */
    function _fillRight()
    {
        if ($this->_alignment & IMAGE_GRAPH_ALIGN_RIGHT) {
            return $this->_left;
        }
        if ($this->_alignment & IMAGE_GRAPH_ALIGN_CENTER_X) {
            return $this->_left + $this->_font->_centerWidth($this->_text);
        } else {
            return $this->_left + $this->_font->width($this->_text);
        }
    }

    /**
     * Bottom boundary of the background fill area 
     * @return int Bottommost position on the canvas
     * @access private
     */
    function _fillBottom()
    {
        if ($this->_alignment & IMAGE_GRAPH_ALIGN_BOTTOM) {
            return $this->_top;
        }
        if ($this->_alignment & IMAGE_GRAPH_ALIGN_CENTER_Y) {
            return $this->_top + $this->_font->_centerHeight($this->_text);
        } else {
            return $this->_top + $this->_font->height($this->_text);
        }
    }

    /**
     * Output the text 
     * @access private
     */
    function _done()
    {
        if (!$this->_font) {
            return false;
        }
        parent::_done();
        $this->_font->_write($this->_fillLeft(), $this->_fillTop(), $this->_text);
    }

}

?>