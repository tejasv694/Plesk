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
 * @package marker
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Value.php,v 1.1 2008/01/24 01:06:07 tye Exp $
 */ 

/**
 * Include file Graph/Marker.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Marker.php");

/**
 * A marker showing the data value. 
 */
class Image_Graph_Marker_Value extends Image_Graph_Marker 
{

    /**
     * Datapreproccesor to format the value
     * @var DataPreprocessor
     * @access private
     */
    var $_dataPreprocessor = null;

    /**
     * Which value to use from the data set, ie the X or Y value
     * @var int
     * @access private
     */
    var $_useValue;

    /**
     * Internal text object, TOTALLY private :)
     * @var Text
     * @access private
     */
    var $_text;

    /**
     * Create a value marker, ie a box containing the value of the "pointing data"
     * @param int $useValue Defines which value to use from the dataset, ie the X or Y value 
     */
    function &Image_Graph_Marker_Value($useValue = IMAGE_GRAPH_VALUE_X)
    {
        parent::__construct();
        $this->_padding = 2;
        $this->_useValue = $useValue;
        $this->setFont($GLOBALS['_Image_Graph_font']);
        $this->_fillStyle = IMAGE_GRAPH_WHITE;
        $this->_borderStyle = IMAGE_GRAPH_BLACK;
    }

    /**
     * Sets the background fill style of the element	 
     * @param Image_Graph_Fill $background The background 
     * @see Image_Graph_Fill
     */
    function setBackground(& $background)
    {
        $this->setFillStyle($background);
    }

    /**
     * Sets the background color of the element    
     * @param int $red The red part (or the whole)
     * @param int $green The green part (if omitted the $red part must contain the whole 24-bit color)
     * @param int $blue The blue part (if omitted the $red part must contain the whole 24-bit color)
     */
    function setBackgroundColor($red, $green = false, $blue = false)
    {
        $this->setFillColor($red, $green, $blue);
    }

    /**
     * Sets a data preprocessor for formatting the values
     * @param DataPreprocessor $dataPreprocessor The data preprocessor
     * @return Image_Graph_DataPreprocessor The data preprocessor
     */
    function &setDataPreprocessor(& $dataPreprocessor)
    {
        $this->_dataPreprocessor = & $dataPreprocessor;
        return $dataPreprocessor;
    }

    /**
     * Left boundary of the background fill area 
     * @return int Leftmost position on the canvas
     * @access private
     */
    function _fillLeft()
    {
        return $this->_text->_fillLeft() - $this->_padding;
    }

    /**
     * Top boundary of the background fill area 
     * @return int Topmost position on the canvas
     * @access private
     */
    function _fillTop()
    {
        return $this->_text->_fillTop() - $this->_padding;
    }

    /**
     * Right boundary of the background fill area 
     * @return int Rightmost position on the canvas
     * @access private
     */
    function _fillRight()
    {
        return $this->_text->_fillRight() + $this->_padding;
    }

    /**
     * Bottom boundary of the background fill area 
     * @return int Bottommost position on the canvas
     * @access private
     */
    function _fillBottom()
    {
        return $this->_text->_fillBottom() + $this->_padding;
    }

    /**
     * Get the value to display
     * @param array $values The values representing the data the marker "points" to
     * @return string The display value, this is the pre-preprocessor value, to support for customized with multiple values. i.e show "x = y" or "(x, y)"
     * @access private
     */
    function _getDisplayValue($values)
    {
        switch ($this->_useValue) {
            case IMAGE_GRAPH_VALUE_X :
                $value = $values['X'];
                break;

            case IMAGE_GRAPH_PCT_X_MIN :
                $value = $values['PCT_MIN_X'];
                break;

            case IMAGE_GRAPH_PCT_X_MAX :
                $value = $values['PCT_MAX_X'];
                break;

            case IMAGE_GRAPH_PCT_Y_MIN :
                $value = $values['PCT_MIN_Y'];
                break;

            case IMAGE_GRAPH_PCT_Y_MAX :
                $value = $values['PCT_MAX_Y'];
                break;

            case IMAGE_GRAPH_POINT_ID :
                $value = $values['ID'];
                break;

            default :
                $value = $values['Y'];
        }
        return $value;
    }

    /**
     * Draw the marker on the canvas
     * @param int $x The X (horizontal) position (in pixels) of the marker on the canvas 
     * @param int $y The Y (vertical) position (in pixels) of the marker on the canvas 
     * @param array $values The values representing the data the marker "points" to 
     * @access private
     */
    function _drawMarker($x, $y, $values = false)
    {
        parent::_drawMarker($x, $y, $values);

        $value = $this->_getDisplayValue($values);

        if ($this->_dataPreprocessor) {
            $value = $this->_dataPreprocessor->_process($value);
        }

        $this->_text = new Image_Graph_Text($x, $y, $value, $this->_font);
        $this->_text->setAlignment(IMAGE_GRAPH_ALIGN_CENTER);
        $this->add($this->_text);

        if ($this->_fillStyle) {
        	// Modified: Don't draw background
            //ImageFilledRectangle($this->_canvas(), $this->_fillLeft(), $this->_fillTop(), $this->_fillRight(), $this->_fillBottom(), $this->_getFillStyle());
        }

        if (isset($this->_borderStyle)) {            
        	// Modified: Don't draw a border
            //ImageRectangle($this->_canvas(), $this->_fillLeft(), $this->_fillTop(), $this->_fillRight(), $this->_fillBottom(), $this->_getBorderStyle());
        }

        $this->_text->_done();
    }

}

?>