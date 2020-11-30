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
 * @package logo
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Logo.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Graph/Element.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Element.php");

/**
 * Displays a logo on the canvas.
 * By default the GraPHPite logo is displayed in the top-right corner of the canvas. 
 */
class Image_Graph_Logo extends Image_Graph_Element 
{

    /**
     * The file name
     * @var stirng
     * @access private
     */
    var $_fileName;

    /**
     * The GD Image resource
     * @var resource
     * @access private
     */
    var $_image;

    /**
     * Alignment of the logo
     * @var int
     * @access private
     */
    var $_alignment;

    /**
     * Logo [Constructor]
     * @param string $filename The filename and path of the image to use for logo 
     */
    function &Image_Graph_Logo($fileName, $alignment = IMAGE_GRAPH_ALIGN_TOP_RIGHT)
    {
        parent::__construct();
        if (file_exists($fileName)) {
            if (strtolower(substr($fileName, -4)) == ".png") {
                $this->_image = ImageCreateFromPNG($this->_fileName = $fileName);
            } else {
                $this->_image = ImageCreateFromJPEG($this->_fileName = $fileName);
            }
        } else {
            $this->_image = false;
        }
        $this->_alignment = $alignment;
    }

    /**
     * Sets the parent. The parent chain should ultimately be a GraPHP object
     * @see Image_Graph
     * @param Image_Graph_Common $parent The parent 
     * @access private
     */
    function _setParent(& $parent)
    {
        parent::_setParent($parent);
        $this->_setCoords($this->_parent->_left, $this->_parent->_top, $this->_parent->_right, $this->_parent->_bottom);
    }

    /**
     * Output the logo
     * @access private
     */
    function _done()
    {
        parent::_done();
        if (!$this->_image) {
            return false;
        }
        $logoWidth = ImageSX($this->_image);
        $logoHeight = ImageSY($this->_image);
        if ($this->_alignment & IMAGE_GRAPH_ALIGN_LEFT) {
            $x = $this->_parent->_left + 2;
        }
        elseif ($this->_alignment & IMAGE_GRAPH_ALIGN_RIGHT) {
            $x = $this->_parent->_right - $logoWidth - 2;
        } else {
            $x = $this->_parent->_left + ($this->_parent->width() - $logoWidth) / 2;
        }

        if ($this->_alignment & IMAGE_GRAPH_ALIGN_TOP) {
            $y = $this->_parent->_top + 2;
        }
        elseif ($this->_alignment & IMAGE_GRAPH_ALIGN_BOTTOM) {
            $y = $this->_parent->_bottom - $logoHeight - 2;
        } else {
            $y = $this->_parent->_top + ($this->_parent->height() - $logoHeight) / 2;
        }

        ImageCopy($this->_canvas(), $this->_image, $x, $y, 0, 0, $logoWidth, $logoHeight);
    }

}

?>