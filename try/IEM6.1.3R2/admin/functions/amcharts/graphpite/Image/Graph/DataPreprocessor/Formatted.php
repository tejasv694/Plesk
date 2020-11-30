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
 * @package datapreprocessor
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Formatted.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Include file Graph/DataPreprocessor.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/DataPreprocessor.php");

/**
 * Format data using a (s)printf pattern.
 * This method is useful when data must displayed using a simple (s)printf pattern as
 * described in the {@link http://www.php.net/manual/en/function.sprintf.php PHP manual}
 */
class Image_Graph_DataPreprocessor_Formatted extends Image_Graph_DataPreprocessor 
{

    /**
     * A (s)printf format string.
     * See {@link http://www.php.net/manual/en/function.sprintf.php PHP Manual} for a description
     * @var string
     * @access private
     */
    var $_format;

    /**
     * Create a (s)printf format data preprocessor
     * @param string $format See <a href = "http://www.php.net/manual/en/function.sprintf.php"> PHP Manual</a> for a description
     */
    function &Image_Graph_DataPreprocessor_Formatted($format)
    {
        parent::__construct();
        $this->_format = $format;
    }

    /**
     * Process the value
     * @param var $value The value to process/format
     * @return string The processed value
     * @access private
     */
    function _process($value)
    {
        return sprintf($this->_format, $value);
    }

}

?>