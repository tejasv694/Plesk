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
 * @version $Id: Constants.php,v 1.1 2008/01/24 01:06:07 tye Exp $
 */ 

/**
 * Align text left
 */
define("IMAGE_GRAPH_ALIGN_LEFT", 0x1);

/**
 * Align text right
 */
define("IMAGE_GRAPH_ALIGN_RIGHT", 0x2);

/**
 * Align text center x (horizontal) 
 */
define("IMAGE_GRAPH_ALIGN_CENTER_X", 0x4);

/**
 * Align text top
 */
define("IMAGE_GRAPH_ALIGN_TOP", 0x8);

/**
 * Align text bottom
 */
define("IMAGE_GRAPH_ALIGN_BOTTOM", 0x10);

/**
 * Align text center y (vertical)
 */
define("IMAGE_GRAPH_ALIGN_CENTER_Y", 0x20);

/**
 * Align text center (both x and y)
 */
define("IMAGE_GRAPH_ALIGN_CENTER", IMAGE_GRAPH_ALIGN_CENTER_X + IMAGE_GRAPH_ALIGN_CENTER_Y);

/**
 * Align text top left
 */
define("IMAGE_GRAPH_ALIGN_TOP_LEFT", IMAGE_GRAPH_ALIGN_TOP + IMAGE_GRAPH_ALIGN_LEFT);

/**
 * Align text top right
 */
define("IMAGE_GRAPH_ALIGN_TOP_RIGHT", IMAGE_GRAPH_ALIGN_TOP + IMAGE_GRAPH_ALIGN_RIGHT);

/**
 * Align text bottom left
 */
define("IMAGE_GRAPH_ALIGN_BOTTOM_LEFT", IMAGE_GRAPH_ALIGN_BOTTOM + IMAGE_GRAPH_ALIGN_LEFT);

/**
 * Align text bottom right
 */
define("IMAGE_GRAPH_ALIGN_BOTTOM_RIGHT", IMAGE_GRAPH_ALIGN_BOTTOM + IMAGE_GRAPH_ALIGN_RIGHT);

/**
 * Align vertical
 */
define("IMAGE_GRAPH_ALIGN_VERTICAL", IMAGE_GRAPH_ALIGN_TOP);

/**
 * Align horizontal
 */
define("IMAGE_GRAPH_ALIGN_HORIZONTAL", IMAGE_GRAPH_ALIGN_LEFT);

/**
 * Text direction vertical (up->down)
 */
define("IMAGE_GRAPH_TEXT_DIR_VERTICAL", 270);

/**
 * Text direction vertical (down->up)
 */
define("IMAGE_GRAPH_TEXT_DIR_VERTICAL_UP", 90);

/**
 * Text direction normal (left->right)
 */
define("IMAGE_GRAPH_TEXT_DIR_NORMAL", 0);

/**
 * Text direction upside down (right->left)
 */
define("IMAGE_GRAPH_TEXT_DIR_UPSIDE_DOWN", 180);

/**
 * Text direction horizontal slant (315 degrees)
 */
define("IMAGE_GRAPH_TEXT_DIR_X_AXIS_SLANT", 315);

?>