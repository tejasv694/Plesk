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
 * @version $Id: Horizontal.php,v 1.1 2008/01/24 01:06:07 tye Exp $
 */ 

/**
 * Include file Graph/Layout.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Layout.php");

/**
 * Layout for displaying two elements side by side.
 * This splits the area contained by this element in two, side by side by a specified percentage
 * (relative to the left side). A layout can be nested. Fx. a HorizontalLayout can 
 * layout two {@see Image_Graph_Layout_Vertical}s to make a 2 by 2 matrix of "element-areas". 
 */
class Image_Graph_Layout_Horizontal extends Image_Graph_Layout 
{

    /**
     * Part1 of the layout
     * @var GraPHPElemnt
     * @access private
     */
    var $_part1;

    /**
     * Part2 of the layout
     * @var GraPHPElemnt
     * @access private
     */
    var $_part2;

    /**
     * The percentage of the graph where the split occurs
     * @var int
     * @access private
     */
    var $_percentage;

    /**
     * HorizontalLayout [Constructor]
     * @param Image_Graph_Element $part1 The 1st part of the layout
     * @param Image_Graph_Element $part2 The 2nd part of the layout
     * @param int $percentage The percentage of the layout to split at
     */
    function &Image_Graph_Layout_Horizontal(& $part1, & $part2, $percentage = 50)
    {
        parent::__construct();
        $this->_part1 = & $part1;
        $this->_part2 = & $part2;
        $this->add($this->_part1);
        $this->add($this->_part2);
        $this->_percentage = max(0, min(100, $percentage));
        $this->_split();
        $this->_padding = 0;
    }

    /**
     * Splits the layout between the parts, by the specified percentage
     * @access private
     */
    function _split()
    {
        $split1 = 100 - $this->_percentage;
        $split2 = $this->_percentage;
        $this->_part1->_push(IMAGE_GRAPH_AREA_RIGHT, "$split1%");
        $this->_part2->_push(IMAGE_GRAPH_AREA_LEFT, "$split2%");
    }

    /**
     * Output the layout to the canvas
     * @access private
     */
    function _done()
    {
        $this->_part1->_done();
        $this->_part2->_done();
    }

}

?>