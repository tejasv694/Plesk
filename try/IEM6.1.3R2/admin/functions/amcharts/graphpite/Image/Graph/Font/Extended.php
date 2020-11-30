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
 * @version $Id: Extended.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Graph/Font.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Font.php");

/**
 * A font with extended functionality.
 * @abstract
 */
class Image_Graph_Font_Extended extends Image_Graph_Font 
{

    /**
     * The angle of the output
     * @var int
     * @access private
     */
    var $_angle = false;

    /**
     * The size of the font
     * @var int
     * @access private
     */
    var $_size = 11;

    /**
     * Set the angle slope of the output font (0 = normal, 90 = bottom and up, 180 = upside down, 270 = top and down)
     * @param int $angle The angle in degrees to slope the text 
     */
    function setAngle($angle)
    {
        $this->_angle = $angle;
    }

    /**
     * Set the size of the font
     * @param int $size The size in pixels of the font 
     */
    function setSize($size)
    {
        $this->_size = $size;
    }

}

?>