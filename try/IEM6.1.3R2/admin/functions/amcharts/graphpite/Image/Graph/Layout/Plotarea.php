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
 * @version $Id: Plotarea.php,v 1.1 2008/01/24 01:06:07 tye Exp $
 */ 

/**
 * Include file Graph/Layout.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Layout.php");
/**
 * Include file Graph/Layout/Horizontal.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Layout/Horizontal.php");
/**
 * Include file Graph/Layout/Vertical.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Layout/Vertical.php");
/**
 * Include file Graph/Plotarea.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Plotarea.php");
/**
 * Include file Graph/Title.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Title.php");

/**
 * A default/standard plotarea layout.
 * Image_Graph_PlotareaLayout creates a Image_Graph_Plotarea with assoiated layout's for a title, an X-axis 
 * title and a Y-axis title. 
 */
class Image_Graph_Layout_Plotarea extends Image_Graph_Layout 
{
    
    /**
     * The plotarea
     * @var Image_Graph_Plotarea
     * @access private
     */
    var $_plotarea;

    /**
     * PlotareaLayout [Constructor]
     * @param string $title The plotarea title
     * @param string $axisXTitle The title displayed on the X-axis (i.e. at the bottom)
     * @param string $axisYTitle The title displayed on the Y-axis (i.e. on the left - vertically)
     */
    function &Image_Graph_Layout_Plotarea($title, $axisXTitle, $axisYTitle)
    {
        parent::__construct();
        
        $this->_plotarea = & new Image_Graph_Plotarea();
        
        return
            new Image_Graph_Layout_Horizontal(
                new Image_Graph_Title($title, $GLOBALS['_Image_Graph_font']),        
                new Image_Graph_Layout_Vertical(
                    new Image_Graph_Title($axisYTitle, $GLOBALS['_Image_Graph_vertical_font']),
                    new Image_Graph_Layout_Horizontal(
                        $this->_plotarea,
                        new Image_Graph_Title($axisXTitle, $GLOBALS['_Image_Graph_font']),
                        95
                    ),
                    5
                ),
                10
            );
    }
    
    /**
     * Get the plotarea
     * @return Image_Graph_Plotarea The plotarea 
     */
    function &getPlotarea() {
        return $this->_plotarea;     
    }

}

?>