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
 * @package layout
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Constants.php,v 1.1 2008/01/24 01:06:07 tye Exp $
 */ 

/**
 * Define the area to be maximized
 */
define("IMAGE_GRAPH_AREA_MAX", 0);

/**
 * Define the area to be left aligned
 */
define("IMAGE_GRAPH_AREA_LEFT", 1);

/**
 * Define the area to be top aligned
 */
define("IMAGE_GRAPH_AREA_TOP", 2);

/**
 * Define the area to be right aligned
 */
define("IMAGE_GRAPH_AREA_RIGHT", 3);

/**
 * Define the area to be bottom aligned
 */
define("IMAGE_GRAPH_AREA_BOTTOM", 4);

/**
 * Define the area to have an absoute position
 */
define("IMAGE_GRAPH_AREA_ABSOLUTE", 5);

/**
 * Define an area size to be absolute, ie. exact pixel size
 */
define("IMAGE_GRAPH_POS_ABSOLUTE", 0);

/**
 * Define an area size to be relative, ie. percentage of "total size"
 */
define("IMAGE_GRAPH_POS_RELATIVE", 1);

?>