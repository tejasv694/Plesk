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
 * @package common
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Include.php,v 1.1 2008/01/24 01:27:13 tye Exp $
 */ 

/**
 * Include file Graph/Config.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Config.php");

/**
 * Include file Graph/Common.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Common.php");
/**
 * Include file Graph/Element.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Element.php");

/**
 * Include file Graph/Dataset.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Dataset.php");
/**
 * Include file Graph/Dataset/Trivial.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Dataset/Trivial.php");
/**
 * Include file Graph/Dataset/Sequential.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Dataset/Sequential.php");
/**
 * Include file Graph/Dataset/Random.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Dataset/Random.php");
/**
 * Include file Graph/Dataset/Function.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Dataset/Function.php");
/**
 * Include file Graph/Dataset/VectorFunction.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Dataset/VectorFunction.php");

/**
 * Include file Color.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Color.php");
/**
 * Include file Graph/Color/HSB.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Color/HSB.php");

/**
 * Include the Linestyles
/**
 * Include file Graph/Line/Solid.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Line/Solid.php");
/**
 * Include file Graph/Line/Formatted.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Line/Formatted.php");
/**
 * Include file Graph/Line/Dotted.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Line/Dotted.php");
/**
 * Include file Graph/Line/Dashed.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Line/Dashed.php");
/**
 * Include file Graph/Line/Array.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Line/Array.php");

/**
 * Include file Graph/Fill/Constants.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Fill/Constants.php");
/**
 * Include file Graph/Fill.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Fill.php");
/**
 * Include file Graph/Fill/Solid.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Fill/Solid.php");
/**
 * Include file Graph/Fill/Image.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Fill/Image.php");
/**
 * Include file Graph/Fill/Gradient.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Fill/Gradient.php");
/**
 * Include file Graph/Fill/Array.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Fill/Array.php");
/**
 * Include file Graph/Fill/StandardColors.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Fill/StandardColors.php");

/**
 * Include file Graph/Font/Constants.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Font/Constants.php");
/**
 * Include file Graph/Font.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Font.php");
/**
 * Include file Graph/Font/Vertical.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Font/Vertical.php");
/**
 * Include file Graph/Font/Extended.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Font/Extended.php");
/**
 * Include file Graph/Font/TTF.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Font/TTF.php");

/**
 * Include file Graph/Layout/Constants.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Layout/Constants.php");
/**
 * Include file Graph/Layout.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Layout.php");
/**
 * Include file Graph/Layout/Horizontal.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Layout/Horizontal.php");
/**
 * Include file Graph/Layout/Vertical.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Layout/Vertical.php");
/**
 * Include file Graph/Layout/Plotarea.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Layout/Plotarea.php");
/**
 * Include file Graph/Layout/Matrix.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Layout/Matrix.php");

/**
 * Include file Graph/Text/Constants.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Text/Constants.php");
/**
 * Include file Graph/Text.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Text.php");
/**
 * Include file Graph/Title.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Title.php");

/**
 * Include file Graph/Axis/Constants.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Axis/Constants.php");
/**
 * Include file Graph/Axis.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Axis.php");
/**
 * Include file Graph/Axis/Sequential.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Axis/Sequential.php");
/**
 * Include file Graph/Axis/Multidimensional.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Axis/Multidimensional.php");
/**
 * Include file Graph/Axis/Radar.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Axis/Radar.php");
/**
 * Include file Graph/Axis/Logarithmic.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Axis/Logarithmic.php");

/**
 * Include file Graph/Plotarea.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Plotarea.php");
/**
 * Include file Graph/Plotarea/Radar.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Plotarea/Radar.php");
/**
 * Include file Graph/Plotarea/Map.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Plotarea/Map.php");

/**
 * Include file Graph/Grid.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Grid.php");
/**
 * Include file Graph/Grid/Lines.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Grid/Lines.php");
/**
 * Include file Graph/Grid/Bars.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Grid/Bars.php");

/**
 * Include file Graph/Legend.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Legend.php");

/**
 * Include file Graph/Plot.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Plot.php");
/**
 * Include file Graph/Plot/MultipleData.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/MultipleData.php");
/**
 * Include file Graph/Plot/Line.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Line.php");
/**
 * Include file Graph/Plot/Area.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Area.php");
/**
 * Include file Graph/Plot/Bar.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Bar.php");
/**
 * Include file Graph/Plot/Bar/Multiple.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Bar/Multiple.php");
/**
 * Include file Graph/Plot/Bar/Horizontal.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Bar/Horizontal.php");
/**
 * Include file Graph/Plot/Stacked/Bar.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Stacked/Bar.php");
/**
 * Include file Graph/Plot/Stacked/Bar100Pct.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Stacked/Bar100Pct.php");
/**
 * Include file Graph/Plot/Stacked/Area.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Stacked/Area.php");
/**
 * Include file Graph/Plot/Step.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Step.php");
/**
 * Include file Graph/Plot/Impulse.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Impulse.php");
/**
 * Include file Graph/Plot/Dot.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Dot.php");

/**
 * Include file Graph/Plot/Pie.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Pie.php");
/**
 * Include file Graph/Plot/Radar.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Radar.php");

/**
 * Include file Graph/Plot/Smoothed/Bezier.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Smoothed/Bezier.php");
/**
 * Include file Graph/Plot/Smoothed/Line.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Smoothed/Line.php");
/**
 * Include file Graph/Plot/Smoothed/Area.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Plot/Smoothed/Area.php");

/**
 * Include file Graph/Logo.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Logo.php");

/**
 * Include file Graph/DataPreprocessor.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/DataPreprocessor.php");
/**
 * Include file Graph/DataPreprocessor/Function.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/DataPreprocessor/Function.php");
/**
 * Include file Graph/DataPreprocessor/Array.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/DataPreprocessor/Array.php");
/**
 * Include file Graph/DataPreprocessor/Sequential.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/DataPreprocessor/Sequential.php");
/**
 * Include file Graph/DataPreprocessor/Formatted.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/DataPreprocessor/Formatted.php");
/**
 * Include file Graph/DataPreprocessor/Date.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/DataPreprocessor/Date.php");
/**
 * Include file Graph/DataPreprocessor/Currency.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/DataPreprocessor/Currency.php");
/**
 * Include file Graph/DataPreprocessor/RomanNumerals.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/DataPreprocessor/RomanNumerals.php");
/**
 * Include file Graph/DataPreprocessor/NumberText.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/DataPreprocessor/NumberText.php");

/**
 * Include file Graph/DataSelector.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/DataSelector.php");
/**
 * Include file Graph/DataSelector/EveryNthPoint.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/DataSelector/EveryNthPoint.php");
/**
 * Include file Graph/DataSelector/NoZeros.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/DataSelector/NoZeros.php");

/**
 * Include file Graph/Marker/Constants.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Constants.php");
/**
 * Include file Graph/Marker.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Marker.php");
/**
 * Include file Graph/Marker/Array.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Array.php");
/**
 * Include file Graph/Marker/Box.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Box.php");
/**
 * Include file Graph/Marker/Cross.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Cross.php");
/**
 * Include file Graph/Marker/Circle.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Circle.php");
/**
 * Include file Graph/Marker/Diamond.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Diamond.php");
/**
 * Include file Graph/Marker/Asterisk.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Asterisk.php");
/**
 * Include file Graph/Marker/Plus.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Plus.php");
/**
 * Include file Graph/Marker/Triangle.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Triangle.php");

/**
 * Include file Graph/Marker/Pointing.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Pointing.php");
/**
 * Include file Graph/Marker/Pointing/Radial.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Pointing/Radial.php");
/**
 * Include file Graph/Marker/Pointing/Angular.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Pointing/Angular.php");

/**
 * Include file Graph/Marker/Average.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Average.php");
/**
 * Include file Graph/Marker/Icon.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Icon.php");
/**
 * Include file Graph/Marker/Pinpoint.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Pinpoint.php");
/**
 * Include file Graph/Marker/ReversePinpoint.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/ReversePinpoint.php");
/**
 * Include file Graph/Marker/Value.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Value.php");
/**
 * Include file Graph/Marker/PercentageCircle.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/Bubble.php");
/**
 * Include file Graph/Marker/FloodFill.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Marker/FloodFill.php");

/**
 * Include file Graph/Figure/Rectangle.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Figure/Rectangle.php");
/**
 * Include file Graph/Figure/Ellipse.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Figure/Ellipse.php");
/**
 * Include file Graph/Figure/Circle.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Figure/Circle.php");
/**
 * Include file Graph/Figure/Polygon.php
 */
#require_once (IMAGE_GRAPH_PATH . "/Graph/Figure/Polygon.php");

/**
 * Include file Graph/Constants.php
 */
require_once (IMAGE_GRAPH_PATH . "/Graph/Constants.php");

?>