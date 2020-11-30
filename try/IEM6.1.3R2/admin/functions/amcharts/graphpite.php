<?php
require_once(dirname(__FILE__) . '/common.php');
require_once(dirname(__FILE__) . "/graphpite/Image/Graph.php");

class Chart_Image {
	/**
	* Graph
	* The GraphPHPite object
	*
	* @var Image_Graph
	*/
	var $graph;

	/**
	* Width
	* The width of the chart
	*
	* @var Integer
	*/
	var $width;

	/**
	* Height
	* The height of the chart
	*
	* @var Integer
	*/
	var $height;

	/**
	* Font
	* Path to a TrueType font for use with ttf functions
	*
	* @var Integer
	*/
	var $font;

	/**
	* Colors
	* Array of colors used for different datasets
	*
	* @var Array
	*/
	var $colors = array("F98F25", "FFBE21", "84B221", "6379AD", "F74F25", "2579F7", "A925F5", "E54572", "429CBD", "9CBD42");

	/**
	* Title
	* Title of the chart
	*
	* @var String
	*/
	var $title;

	/**
	* Canvas
	* Reference to the image resource holding the finished chart image
	*
	* @var Resource
	*/
	var $canvas;

	/**
	* Chart_Image
	* Creates the Image_Graph control and sets configuration values
	*
	* @return Void Doesn't return anything.
	*/
	function Chart_Image($width,$height,$title) {
		$this->graph =& new Image_Graph($width,$height);
		$this->width = $width;
		$this->height = $height;
		$this->font = dirname(__FILE__) . "/graphpite/Image/Graph/Fonts/arial.ttf";
		$this->title = $title;
	}

	/**
	* Generate
	* Generates the charts from the data passed into it
	*
	* @param chart Array of data and labels
	*
	* @return Void Doesn't return anything.
	*/
	function Generate($chart) {

		$this->graph->add(
				$PlotArea =& new Image_Graph_Plotarea()
		);

		$this->graph->_outputImage = false;

		switch ($chart['chart_type']) {
			case 'column':
				// Add grid
				$PlotArea->addGridY(new Image_Graph_Grid_Lines());
				$PlotArea->addGridX(new Image_Graph_Grid_Lines());

				$labels = array_shift($chart['chart_data']);
				$legend_labels = array();
				$DataSets = array();

				foreach ($chart['chart_data'] as $data) {
					$Dataset =& new Image_Graph_Dataset_Trivial();
					foreach ($data as $key => $value) {
						if (!is_numeric($value)) {
							if ($key == 0) {
								$legend_labels[] = $value;
							}
							continue;
						}

						$Dataset->addPoint($key, $value);
					}
					$DataSets[] =& $Dataset;
				}

				$PlotMultiple =& new Image_Graph_Plot_Bar_Multiple($DataSets);
				$PlotMultiple->spacing(3);
				$PlotMultiple->setXValueWidth(1);
				$Plot1 =& $PlotArea->addPlot($PlotMultiple);

				$noBorder =& new Image_Graph_Line_Solid();
				$noBorder->setThickness(0);

				// create a Y data value marker
				$Marker =& $Plot1->add(new Image_Graph_Marker_Value(IMAGE_GRAPH_VALUE_Y));
				//$Marker->setFillColor(0xFFFFFF);
				$Marker->setBorderColor(0xFFFFFF);

				$PointingMarker =& $Plot1->add(new Image_Graph_Marker_Pointing(0,-10, $Marker));
				$PointingMarker->setLineStyle($noBorder);
				$Plot1->setMarker($PointingMarker);

				$FillArray =& new Image_Graph_Fill_Array();
				foreach ($this->colors as $color) {
					$FillArray->add(new Image_Graph_Fill_Gradient(IMAGE_GRAPH_GRAD_RADIAL, eval("return 0x{$color};"), eval("return 0x{$color};"), 200));

				}
				$Plot1->setFillStyle($FillArray);

				$AxisX =& $PlotArea->getAxis(IMAGE_GRAPH_AXIS_X);
				$AxisX->setDataPreprocessor(
					new Image_Graph_DataPreprocessor_Array(
						$labels
					)
				);

				$thickAxis =& new Image_Graph_Line_Solid();
				$thickAxis->setThickness(2);

				$AxisX->setLineStyle($thickAxis);
				$AxisX->setLabelInterval(1);

				$AxisY =& $PlotArea->getAxis(IMAGE_GRAPH_AXIS_Y);
				$AxisY->setLineStyle($thickAxis);
				$AxisY->setDataPreprocessor(new Image_Graph_DataPreprocessor_Function("FormatNumber"));

				$this->graph->done();

				$canvas =& $this->graph->_canvas();

				$canvas =& $this->DrawTitle($canvas,$this->title,true);

				$this->DrawLegend($canvas,$legend_labels,'column');
			break; // case column

			case 'pie':
				$DataSet =& new Image_Graph_Dataset_Trivial();
				$Plot1 =& $PlotArea->addPlot(new Image_Graph_Plot_Pie($DataSet));
				$points = $chart['chart_data'][1];
				$labels = $chart['chart_data'][0];

				foreach ($points as $key => $value) {
					if (!is_numeric($value)) { continue; }

					$DataSet->addPoint($labels[$key], $value);
				}
				$PlotArea->hideAxis();

				// create a Y data value marker
				$Marker =& $Plot1->add(new Image_Graph_Marker_Value(IMAGE_GRAPH_VALUE_X));
				$Marker->setFillColor(0xFFFFFF);
				$Marker->setBorderColor(0xFFFFFF);
				$PointingMarker =& $Plot1->add(new Image_Graph_Marker_Pointing_Angular(40, $Marker));
				$Plot1->setMarker($PointingMarker);

				$FillArray =& new Image_Graph_Fill_Array();

				foreach ($this->colors as $color) {
					$FillArray->add(new Image_Graph_Fill_Gradient(IMAGE_GRAPH_GRAD_RADIAL, eval("return 0x{$color};"), eval("return 0x{$color};"), 200));
				}

				$Plot1->setFillStyle($FillArray);
				$Plot1->Radius = 80;

				$this->graph->done();

				$canvas =& $this->graph->_canvas();

				$this->DrawLegend($canvas,$labels,'pie');
				$canvas =& $this->DrawTitle($canvas,$this->title);
			break; // case pie
		}

		$this->canvas =& $canvas;
	}

	/**
	* DrawTitle
	* Draws the title onto the chart. This function also resizes the canvas to fit the title
	* and the legend for column charts
	*
	* @param canvas Resource to draw onto
	* @param title Text of the title
	* @param legendspace True/False, whether or not to make space for a legend when resizing the canvas
	*
	* @return Resource
	*/
	function &DrawTitle(&$canvas,$title,$legendspace = false) {
		// Height of the space to add
		$height = 20;
		$new_canvas = imagecreate($this->width,$this->height + $height * ($legendspace ? 2 : 1));

		imagecopy($new_canvas,$canvas,0,$height,0,0,$this->width,$this->height);

		$blackindex = imagecolorexact($new_canvas,0,0,0);

		//if (function_exists('imagettftext')) {
		//} else {
			imagestring($new_canvas,5,$this->width / 2 - 100,5,$title,$blackindex);
		//}
		return $new_canvas;
	}

	/**
	* DrawLegend
	* Draws the legend onto the chart. The GraPHPite legend feature doesn't work well.
	*
	* @param canvas Resource to draw onto
	* @param labels Array of labels
	* @param type pie or column, pie legends are drawn top right and laid out vertically, column legends are drawn bottom right laid out horizontally
	*
	* @return Void
	*/
	function DrawLegend(&$canvas,$labels,$type) {
		// Starting position
		$y = 10;
		$x = 10;

		if ($type == 'column') {
			$y = $this->height;
		}

		// Size of the legend color squares
		$square = 18;

		// GD font number
		$font = 2;

		reset($this->colors);

		$blackindex = imagecolorexact($canvas,0,0,0);
		reset($this->colors);

		foreach ($labels as $label) {
			if ($label != '') {
				list($key,$color) = each($this->colors);

				$colorindex = imagecolorexact($canvas,
					(eval("return 0x{$color};") & 0xFF0000) >> 16,
					(eval("return 0x{$color};") & 0x00FF00) >> 8,
					(eval("return 0x{$color};") & 0x0000FF)
				);
				if ($colorindex == -1) {
					$colorindex = imagecolorallocate($canvas,
						(eval("return 0x{$color};") & 0xFF0000) >> 16,
						(eval("return 0x{$color};") & 0x00FF00) >> 8,
						(eval("return 0x{$color};") & 0x0000FF)
					);
				}

				imagefilledrectangle($canvas,$x,$y,$x + $square,$y + $square,$colorindex);
				//if (function_exists('imagettftext')) {
				//	imagettftext($canvas,12,0,35,$y + 10,$this->font,$label);
				//} else {
					imagestring($canvas,$font,$x + $square + 5,$y + 6,$label,$blackindex);
				//}
				if ($type == 'pie') $y += 18 + 5;
				if ($type == 'column') $x += $square + 5 + 5 + imagefontwidth($font) * strlen($label);
			}
		}

	}

	/**
	* PrintImage
	* Prints the image to the output. Generate() must be called first.
	*
	* @return Void
	*/
	function PrintImage() {
		if (function_exists('imagegif')) {
			header("Content-type: image/gif");
			imagegif($this->canvas);
		} else {
			header("Content-type: image/png");
			imagejpeg($this->canvas,NULL,100);
		}
	}
}

/**
* FormatNumber
* Alias for SendStudio_Functions::FormatNumber, GraPHPite requires a global function for formatting
*
* @return String Formatted number
*/

function FormatNumber($number) {
	static $SendStudio_Functions;

	if (gettype($SendStudio_Functions) != 'object') {
		$SendStudio_Functions = new SendStudio_Functions();
	}
	return $SendStudio_Functions->FormatNumber($number);
}
