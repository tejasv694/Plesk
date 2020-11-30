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
 * @package dataset
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Trivial.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Graph/Dataset.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Dataset.php");

/**
 * Trivial data set, simply add points (x, y) 1 by 1 
 */
class Image_Graph_Dataset_Trivial extends Image_Graph_Dataset 
{

    /**
     * Data storage
     * @var array
     * @access private
     */
    var $_data;

    /**
     * Image_Graph_Dataset_Trivial [Constructor]
     */
    function &Image_Graph_Dataset_Trivial()
    {
        parent::__construct();
        $this->_data = array ();
    }

    /**
     * Add a point to the dataset
     * @param int $x The X value to add
     * @param int $y The Y value to add, can be omited
     * @param var $ID The ID of the point
	 */
    function addPoint($x, $y = false, $ID = false)
    {
        parent::addPoint($x, $y, $ID);
        $this->_data[] = array ('X' => $x, 'Y' => $y, 'ID' => $ID);
    }

    /**
     * Gets a X point from the dataset
     * @param var $x The variable to return an X value from, fx in a vector function data set
     * @return var The X value of the variable
     * @access private
	 */
    function _getPointX($x)
    {
        if (isset ($this->_data[$x]['X'])) {
            return $this->_data[$x]['X'];
        } else {
            return false;
        }
    }

    /**
     * Gets a Y point from the dataset
     * @param var $x The variable to return an Y value from, fx in a vector function data set
     * @return var The Y value of the variable
     * @access private
	 */
    function _getPointY($x)
    {
        if (isset ($this->_data[$x]['Y'])) {
            return $this->_data[$x]['Y'];
        } else {
            return false;
        }
    }

    /**
     * Gets a ID from the dataset
     * @param var $x The variable to return an Y value from, fx in a vector function data set
     * @return var The ID value of the variable
     * @access private
	 */
    function _getPointID($x)
    {
        if (isset ($this->_data[$x]['ID'])) {
            return $this->_data[$x]['ID'];
        } else {
            return false;
        }
    }

    /**
     * The number of values in the dataset
     * @return int The number of values in the dataset
	 */
    function count()
    {
        return count($this->_data);
    }

    /**
     * Reset the intertal dataset pointer
     * @return var The first X value
     * @access private
	 */
    function _reset()
    {
        $this->_posX = 0;
        return $this->_posX;
    }

    /**
     * Get the next point the internal pointer refers to and advance the pointer
     * @return array The next point
     * @access private
	 */
    function _next()
    {
        if ($this->_posX >= $this->count()) {
            return false;
        }
        $x = $this->_getPointX($this->_posX);
        $y = $this->_getPointY($this->_posX);
        $ID = $this->_getPointID($this->_posX);
        $this->_posX += $this->_stepX();

        return array ('X' => $x, 'Y' => $y, 'ID' => $ID);
    }

}

?>