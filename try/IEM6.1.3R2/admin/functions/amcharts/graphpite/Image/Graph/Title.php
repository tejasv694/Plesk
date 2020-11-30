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
 * @version $Id: Title.php,v 1.1 2008/01/24 01:31:36 tye Exp $
 */ 

/**
 * Include file Graph/Layout.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Layout.php");

/**
 * Title
 */
class Image_Graph_Title extends Image_Graph_Layout 
{

    /**
     * The text to print
     * @var string
     * @access private
     */
    var $_text;

    /**
     * The font to use
     * @var Font
     * @access private
     */
    var $_font;

    /**
     * Create the title
     * @param sting $text The text to represent the title
     * @param Font $font The font to use in the title
     */
    function &Image_Graph_Title($text, & $font)
    {
        parent::__construct();
        $this->_font = & $font;
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
     * Output the text 
     * @access private
     */
    function _done()
    {
        if (!$this->_font) {
            return false;
        }
        
        if (!is_a($this->_parent, "Image_Graph_Layout")) {
            $this->_setCoords(
                $this->_parent->_fillLeft(),
                $this->_parent->_fillTop(),
                $this->_parent->_fillRight(),
                $this->_parent->_fillTop() + $this->_font->height($this->_text)
            );
        }               
        
        parent::_done();

        $this->_font->_write(
            ($this->_left + $this->_right - $this->_font->width($this->_text)) / 2, 
            ($this->_top + $this->_bottom - $this->_font->height($this->_text)) / 2, 
            $this->_text
        );
    }

}

?>