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
 * @package plottype
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Line.php,v 1.1 2008/01/24 01:32:27 tye Exp $
 */ 

/**
 * Include file Graph/Plot.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Plot.php");

/**
 * Linechart
 */
class Image_Graph_Plot_Line extends Image_Graph_Plot 
{

    /**
     * Gets the fill style of the element    
     * @return int A GD filestyle representing the fill style 
     * @see Image_Graph_Fill
     * @access private
     */
    function _getFillStyle($ID = false)
    {
        return IMG_COLOR_TRANSPARENT;
    }
    
    /**
     * Output the plot
     * @access private
     */
    function _done()
    {
        parent::_done();

        $p1 = false;
        $this->_dataset->_reset();
        while ($point = $this->_dataset->_next()) {
            $p2['X'] = $this->_parent->_pointX($point);
            $p2['Y'] = $this->_parent->_pointY($point);
            if ($p1) {
                ImageLine($this->_canvas(), $p1['X'], $p1['Y'], $p2['X'], $p2['Y'], $this->_getLineStyle());
            }
            $p1 = $p2;
        }
        $this->_drawMarker();
    }

}

?>