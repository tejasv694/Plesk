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
 * @version $Id: DataPreprocessor.php,v 1.1 2008/01/24 01:06:06 tye Exp $
 */ 

/**
 * Data preprocessor used for preformatting a data.
 * A data preprocessor is used in cases where a value from a dataset or label must be
 * displayed in another format or way than entered. This could for example be the need
 * to display X-values as a date instead of 1, 2, 3, .. or even worse unix-timestamps. 
 * It could also be when a {@see Image_Graph_Marker_Value} needs to display values as percentages
 * with 1 decimal digit instead of the default formatting (fx. 12.01271 -> 12.0%).
 * @abstract
 */
class Image_Graph_DataPreprocessor 
{

    /**
     * Image_Graph_DataPreprocessor [Constructor]. 
	 */
    function &Image_Graph_DataPreprocessor()
    {
    }

    /**
     * Process the value
     * @param var $value The value to process/format
     * @return string The processed value
     * @access private
     */
    function _process($value)
    {
        return $value;
    }

}

?>