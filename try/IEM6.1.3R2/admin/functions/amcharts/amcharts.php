<?php

require_once(dirname(__FILE__) . "/class.xml.php");

function SendChartData($chart) {
	$xml = new xml();

	$colors = array("F98F25", "FFBE21", "84B221", "6379AD", "F74F25", "2579F7", "A925F5", "E54572", "429CBD", "9CBD42");

	switch ($chart['chart_type']) {
		case 'pie':
			$radius = 5;
			$xml->OpenXMLTag('pie');
				foreach ($chart['chart_data'][0] as $key => $val) {
					if ($val != '') {
						$xml->AddXMLTag('slice',$chart['chart_data'][1][$key],array('title' => $val, 'label_radius' => $radius));
					}
					$radius += 5;
				}

			$xml->CloseXMLTag(); // pie
		break; // pie
		case 'column':
			$xml->OpenXMLTag('chart');

				// x labels
				$xml->OpenXMLTag('series');
					$x_labels = array_shift($chart['chart_data']);
					$label = array_shift($x_labels);
					foreach ($x_labels as $key => $label) {
						if ($label != '') $xml->AddXMLTag('value',"$label",array('xid' => $key));
					}
				$xml->CloseXMLTag(); // series

				// x values
				$xml->OpenXMLTag('graphs');

				foreach ($chart['chart_data'] as $key => $graph) {
					$title = array_shift($graph);
					list(,$color) = each($colors);
					$xml->OpenXMLTag('graph',array('gid' => $key,'title' => "$title", 'color' => "#$color"));

						$xml->AddXMLTag('type','column');
						foreach ($graph as $key => $value) {
							if ($value !== '') $xml->AddXMLTag('value',$value,array('xid' => $key));
						}

					$xml->CloseXMLTag(); // graph
				}
				$xml->CloseXMLTag(); // graphs

			$xml->CloseXMLTag(); // chart
		break;
		default:
			$xml->OpenXMLTag('pie');
				$xml->AddXMLTag('slice',1,array('title' => 'slice'));
			$xml->CloseXMLTag();

	}

	$xml->SendXMLHeader();
	echo $xml->GetXML();
}


/**
  * InsertChart
  *
  *  returns the markup suitable for producing a flash chart and inserting into a HTML template page
  *
  * @param type String the type of plot to produce (column | pie)
  * @param data_url String the values used for X Axis vectors
  * @param override Booelan extra setting for chart display
  * @param transparent Boolean reserved for future use
  * @param base_url String a base URL used to preprend to URLs (leave as '' if call is made from app base directory)
  *
  * @return return String markup suitable for including a FLash chart into a page
  **/
function InsertChart($type, $data_url, $override=null, $transparent=true, $base_url='') {
	$base_url = preg_replace('~^https{0,1}:\/\/.*?/~', '/', $base_url);

	$settings = "escape(\"{$base_url}functions/amcharts/{$type}_defaults.php";
	if ($override != null) {
		if (is_array($override)) {
			$parameters = array();
			$settings .= '?';
			foreach ($override as $key => $val) {
				$parameters[] = urlencode($key) . "=" . urlencode($val);
			}
			$settings .= implode('&',$parameters);
		} else {
			$settings .= "$override";
		}
	}
	$settings .= '")';

	$id = md5(uniqid('_'));

	$data_url .= "&" . rand(100000,999999);

	$return = <<<EOF
	<span class="statistics_chart">
	<script type="text/javascript" src="{$base_url}functions/amcharts/am{$type}/swfobject.js"></script>
		<div id="flashcontent{$id}">
			<p class="Text">You need to upgrade your Flash Player to view charts. <a href="http://www.adobe.com/go/getflash/">Click here to upgrade</a>.</p>
		</div>

		<script type="text/javascript">
			// <![CDATA[
			var so = new SWFObject("{$base_url}functions/amcharts/am{$type}/am{$type}.swf", "am{$type}{$id}", "100%", "300", "8", "#FFFFFF");
			so.addVariable('chart_id','{$id}');
			so.addParam("wmode", "transparent");
			so.addVariable("path", "{$base_url}functions/amcharts/");
			so.addVariable("settings_file", $settings );
			so.addVariable("data_file", escape("$data_url"));
			so.addVariable("preloader_color", "#999999");
			so.write("flashcontent{$id}");
			// ]]>
		</script>
	</span>
EOF;

	return $return;
}
