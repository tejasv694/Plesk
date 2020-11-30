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
 * @package axis
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Constants.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Defines an X (horizontal) axis
 */
define("IMAGE_GRAPH_AXIS_X", 1);

/**
 * Defines an Y (vertical) axis
 */
define("IMAGE_GRAPH_AXIS_Y", 2);

/**
 * Defines an horizontal (X) axis
 */
define("IMAGE_GRAPH_AXIS_HORIZONTAL", 1);

/**
 * Defines an vertical (Y) axis
 */
define("IMAGE_GRAPH_AXIS_VERTICAL", 2);

/**
 * Defines an automatic axis interval
 */
define("IMAGE_GRAPH_AXIS_INTERVAL_AUTO", 0);

/**
 * Define if label should be shown for axis minimum value
 */
define("IMAGE_GRAPH_LABEL_MINIMUM", 1);

/**
 * Define if label should be shown for axis 0 (zero) value
 */
define("IMAGE_GRAPH_LABEL_ZERO", 2);

/**
 * Define if label should be shown for axis maximum value
 */
define("IMAGE_GRAPH_LABEL_MAXIMUM", 4);

?>