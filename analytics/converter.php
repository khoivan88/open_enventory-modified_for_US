<?php

abstract class converter {
	protected $cursorPos;		// cursor position
	protected $data;		// the datastring
	protected $graphData;		// the converted graphData
	protected $config;		// gets the default configuration of the converter
	protected $image;		// the image resource
	protected $report;		// the report of the spectrum
	protected $fileNumber; 		// the number of the file in the file_contents array, which should be read
	protected $isNegative;		// helper variable to avoid unsigned long integer bug in php
	
	/*
	 * Constructor
	 */
	function __construct() {
		$this->isNegative = 0;
		// the sketchable graphData, initialized with default values
		$this->graphData = array(
				'units' => array(
						'x' => 'X', 						// default x-unit
						'y' => 'Y',							// default y-unit
						'y2' => 'Z'), 						// default z-unit
				'extrema' => array(
						'minima' => array(
								'x' => 0, 					// default x-minimum
								'y' => 0), 					// default y-minimum
						'maxima' => array(
								'x' => 100, 				// default x-maximum	
								'y' => 100)),  				// default y-maximum
				'tickScale' => array(
						'x' => 100,							// default tickScale in x-direction
						'y' => 100,							// default tickScale in y-direction
						),
				'tickDistance' => array(
						'x' => 50,							// default tickDistance in x-direction
						'y' => 50),							// default tickDistance in y-direction
				'drawingStyle' => 0,						// default drawing style; 0=LineGraph, 1=CandleSticks, 2=nmr
				'graphNames' => array(0 => ''),			// default name of the graph
				// block of data, determined by the specific converter, or no need to be changed
				'image' => NULL,
				'imageMime' => 'image/png',
				'graphs' => array(),
				'interpretation' => "",
				'method' => "",
				'csvDataString' => "");
						
		// the configuration variables, initialized with default values
		$this->config = array(
				'dimensions' => array(
						'width' => 800, 					// default image width
						'height' => 600),					// default image height 
				'axisOffset' => array(
						'x' => 30, 							// default x-axis offset
						'y' => 45), 						// default y-axis offset
				'legendOffset' => 60,						// default offset of the legend
				'precision' => array(
						'x' => 0,							// default precision for rounding x-values
						'y' => 0),							// default precision for rounding y-values
				'textAttributes' => array(
						'font' => 'analytics/tnr.ttf',		// default text font
						'size' => 11),						// default text size
				'yUnitOffset' => 15,						// default offset of the y unit
				'transparent' => true,						// default value for transparency, default: on
				'peaks' => array(
						'computePeaks' => true,				// default if peaks should be computed
						'range' => 50,						// default range for searching local extrema
						'significanceLevel' => 1.08,		// default level of significance
						'minimum' => false,					// default kind of peak: if true-> minimum, else->maximum
						'tickLength' => 13,					// default length of peak ticks
						'maxPeaks' => 0,					// default maximum amount of peaks, if 0, then ignore
						'relativePeak' => 0.07),			// default minima for peak/maxpeak
				'thickness' => 1,							// default thickness of all lines
				'tickSize' => array(
						'small' => 6,						// default size of small ticks
						'big' => 8							// default size of big ticks
				),
				'arrowSize' => 8,							// default size of the the arrows at the ends of x- and y-axis
				'margin' => array(
						'top' => 30,						// default top margin
						'right' => 30						// default right margin
				),
				'2ndYAxis' => false,						// default for 2nd y-axis (off)
				'colors' => array()							// needs to be set at graph.php !!
		);
	}
	
	public function __destruct() {
		// nothing happens
	}
	
	/*
	 * needs to be implemented
	 * supposed to do the convertation
	 */
	public abstract function convertFileToGraphData();
	
	/*
	 * produces the interpretation string, empty by default
	 */
	public function produceInterpretation() {
		
	}
	
	/*
	 * needs to be implemented
	 * supposed to return 1 if signature of the files fits with the specific converter, 0 if not or 2 if there is none
	 */
	public abstract function verifyFileSignature($file_contents);
	
	/*
	 * reads and decodes a sequenz of binary data
	 * returns unpackedData
	 */
	public function readData($rawData, $code, $length, $cursorPos) {
		// gets the next segment of binary data to unpack
		$data = substr($rawData, $cursorPos, $length);
	
		// unpacks binary data segment
		$unpackedData = unpack($code.'i',$data)['i'];
	
		return $unpackedData;
	}
	
	/*
	 * converts to the specific coordinates of the various pixels
	 * returns converted graphData
	 */
	public function convertPointsToPixelCoordinates($graphData, $config) {
		for($j = 0; $j<count($graphData['graphs']); $j++) {
			// converts the points
			for($i=0; $i<count($graphData['graphs'][$j]['points']); $i++) {
				$graphData['graphs'][$j]['points'][$i]['x'] = ($graphData['graphs'][$j]['points'][$i]['x']-$graphData['extrema']['minima']['x'])/($graphData['extrema']['minima']['x']+(ceil(($config['dimensions']['width']-($config['axisOffset']['y']+$config['margin']['right']))/$graphData['tickDistance']['x'])-1)*$graphData['tickScale']['x']-$graphData['extrema']['minima']['x'])*$graphData['tickDistance']['x']*(ceil(($config['dimensions']['width']-($config['axisOffset']['y']+$config['margin']['right']))/$graphData['tickDistance']['x'])-1);
				$graphData['graphs'][$j]['points'][$i]['y'] = ($graphData['graphs'][$j]['points'][$i]['y']-$graphData['extrema']['minima']['y'])/($graphData['extrema']['minima']['y']+(ceil(($config['dimensions']['height']-($config['axisOffset']['x']+$config['margin']['top']))/$graphData['tickDistance']['y'])-1)*$graphData['tickScale']['y']-$graphData['extrema']['minima']['y'])*$graphData['tickDistance']['y']*(ceil(($config['dimensions']['height']-($config['axisOffset']['x']+$config['margin']['top']))/$graphData['tickDistance']['y'])-1);
			}
			// converts the peaks
			for($i=0; $i<count($graphData['graphs'][$j]['peaks']); $i++) {
				$graphData['graphs'][$j]['peaks'][$i]['xOld'] = $graphData['graphs'][$j]['peaks'][$i]['x'];
				$graphData['graphs'][$j]['peaks'][$i]['x'] = ($graphData['graphs'][$j]['peaks'][$i]['x']-$graphData['extrema']['minima']['x'])/($graphData['extrema']['minima']['x']+(ceil(($config['dimensions']['width']-($config['axisOffset']['y']+$config['margin']['right']))/$graphData['tickDistance']['x'])-1)*$graphData['tickScale']['x']-$graphData['extrema']['minima']['x'])*$graphData['tickDistance']['x']*(ceil(($config['dimensions']['width']-($config['axisOffset']['y']+$config['margin']['right']))/$graphData['tickDistance']['x'])-1);
				$graphData['graphs'][$j]['peaks'][$i]['y'] = ($graphData['graphs'][$j]['peaks'][$i]['y']-$graphData['extrema']['minima']['y'])/($graphData['extrema']['minima']['y']+(ceil(($config['dimensions']['height']-($config['axisOffset']['x']+$config['margin']['top']))/$graphData['tickDistance']['y'])-1)*$graphData['tickScale']['y']-$graphData['extrema']['minima']['y'])*$graphData['tickDistance']['y']*(ceil(($config['dimensions']['height']-($config['axisOffset']['x']+$config['margin']['top']))/$graphData['tickDistance']['y'])-1);
			}
			// converts points for an optional second y axis
			if($config['2ndYAxis'] == true) {
				for($i=0; $i<count($graphData['graphs'][$j]['points']); $i++) {
					if($graphData['graphs'][$j]['points'][$i]['y2']!=null) {
						$graphData['graphs'][$j]['points'][$i]['y'] = ($graphData['graphs'][$j]['points'][$i]['y2']-$graphData['extrema']['minima']['y2'])/($graphData['extrema']['minima']['y2']+(ceil(($config['dimensions']['height']-($config['axisOffset']['x']+$config['margin']['top']))/$graphData['tickDistance']['y2'])-1)*$graphData['tickScale']['y2']-$graphData['extrema']['minima']['y2'])*$graphData['tickDistance']['y2']*(ceil(($config['dimensions']['height']-($config['axisOffset']['x']+$config['margin']['top']))/$graphData['tickDistance']['y2'])-1);
					}
				}
			}
		}
		return $graphData;
	}
	
	/*
	 * produces accessible reading a csv datastring
	 * returns csv data string
	 */
	public function produceCsvDataString($graphData) {
		/* next lines produces options and the csv data for the graphViewer */
		//  produces csv string
		$data = "\"";
		for($i=0; $i<count($graphData['graphs'][0]['points']); $i++) {
			if($graphData['drawingStyle']!=2) {
				$pointNumber = $i;
			}
			else {
				$pointNumber = count($graphData['graphs'][0]['points'])-$i-1;
			}
			$data .= $graphData['graphs'][0]['points'][$pointNumber]['x'].", ";
			for($j=0; $j<count($graphData['graphs']); $j++) {
				if($j<count($graphData['graphs'])-1) {
					if($graphData['graphs'][$j]['points'][$pointNumber]['y'] == NULL) {
						$data .= $graphData['graphs'][$j]['points'][$pointNumber]['y2'].", ";
					}
					else {
						$data .= $graphData['graphs'][$j]['points'][$pointNumber]['y'].", ";
					}
				}
				else {
					if($graphData['graphs'][$j]['points'][$pointNumber]['y'] == NULL) {
						$data .= $graphData['graphs'][$j]['points'][$pointNumber]['y2']."\\n";
					}
					else {
						$data .= $graphData['graphs'][$j]['points'][$pointNumber]['y']."\\n";
					}
				}
			}
		}
		$data .= "\",\n{\n";
		
		// adds the needed options for each plot
		if($graphData['drawingStyle']==1) {
			$data .= "plotter: barChartPlotter,\n";
		}
		
		$data .= "labels: ['".$graphData['units']['x']."',";
		for($j=0; $j<count($graphData['graphs']); $j++) {
			if($j<count($graphData['graphs'])-1) {
				if(strlen($graphData['graphNames'][$j])<20) {
					$data .= "'".$graphData['graphNames'][$j]."', ";
				}
				else {
					$data .= "'Graph ".($j+1)."', ";
				}
			}
			else {
				if(strlen($graphData['graphNames'][$j])<20) {
					$data .= "'".$graphData['graphNames'][$j]."'],\n";
				}
				else {
					$data .= "'Graph ".($j+1)."'],\n";
				}
			}
		}

		if($graphData['units']['y2']!=NULL) {
			$data .= "y2label: '".$graphData['units']['y2']."',\n";
			$data .= "series: {\n";
			$data .= "'".$graphData['units']['y2']."': { axis: 'y2' }\n";
			$data .= "},\n";
			$data .= "axes: {\n";
			$data .= "y2: {\n";
			$data .= "labelsKMB: true\n";
			$data .= "}\n";
			$data .= "},\n";
		}
		$data .= "legend: 'always',\n";
		$data .= "colors: [\"rgb(0, 0, 0)\",\n\"rgb(96, 186, 90)\",\n\"rgb(80, 192, 220)\",\n\"rgb(243, 34, 76)\",\n\"rgb(176, 101, 240)\"],\n";
		$data .= "xlabel: '".$graphData['units']['x']."',\n";
		$data .= "ylabel: '".$graphData['units']['y']."'\n";
		
		$data .= "}";
		return $data;
	}
	
	/*
	 * returns graphData with peaks
	 */
	public function getPeaks($graphData, $config) {
		// simply do the calculating if it is neccessary
		if($config['peaks']['computePeaks']) {
			if($graphData['drawingStyle'] == 1) {
				uasort($graphData['graphs'][0]['points'], function($a, $b) {return $b['y'] - $a['y'];});	// sorts all values of the ms by y
				$msPeaks=array_slice($graphData['graphs'][0]['points'], 0, $config['peaks']['maxPeaks']);	// select the extreme ones
				usort($msPeaks, function($a, $b) {return $b['x'] - $a['x'];});
				$graphData['graphs'][0]['peaks'] = $msPeaks;
			}
			else {
				for($j=0; $j<count($graphData['graphs']); $j++) {
					if($config['peaks']['minimum'] == true) {
						$peakCandidates=array();
						$minPeak=PHP_INT_MAX;
						// gets the peak candidates: checks if the neighboring points are smaller
						for($i=1; $i<count($graphData['graphs'][$j]['points'])-2; $i++) {
							if($graphData['graphs'][$j]['points'][$i-1]['y']>$graphData['graphs'][$j]['points'][$i]['y'] && $graphData['graphs'][$j]['points'][$i+1]['y']>$graphData['graphs'][$j]['points'][$i]['y']) {
								$peakCandidates[$i] = $graphData['graphs'][$j]['points'][$i];
								// gets the highest peak
								if($peakCandidates[$i]['y']<$maxPeak) {
									$maxPeak = $peakCandidates[$i]['y'];
								}
							}
						}
						// goes through all peakcandidates and eliminates the undersized ones
						foreach($peakCandidates as $i => $peakCandidate) {
							// undersized in comparison with the extreme ones
							if($minPeak/$peakCandidate['y']<$config['peaks']['relativePeak']) {
								unset($peakCandidates[$i]);
								continue;
							}
							$sum = 0;
							$average = 0;
							// undersized in comparison with the other peaks in range
							for($k=-$config['peaks']['range']/2; $k<$config['peaks']['range']/2; $k++) {
								if($peakCandidates[$k] != NULL && $peakCandidates[$k]['y']>$peakCandidate['y']) {
									unset($peakCandidates[$i]);
								}
								elseif($peakCandidates[$k] != NULL && $peakCandidates[$k]['y']<$peakCandidate['y']) {
									unset($peakCandidates[$k]);
								}
								$sum += $graphData['graphs'][$j]['points'][$i+$k]['y'];
							}
							$average = $sum/$config['peaks']['range'];
							$significanceLevel = $average/$peakCandidate['y'];
							// undersized in comparison with the average of the block
							if($significanceLevel<$config['peaks']['significanceLevel']) {
								unset($peakCandidates[$i]);
								continue;
							}
						}
					}
					else {
						$peakCandidates=array();
						$maxPeak=-PHP_INT_MAX;
						// gets the peak candidates: checks if the neighboring points are smaller
						for($i=1; $i<count($graphData['graphs'][$j]['points'])-2; $i++) {
							if($graphData['graphs'][$j]['points'][$i-1]['y']<$graphData['graphs'][$j]['points'][$i]['y'] && $graphData['graphs'][$j]['points'][$i+1]['y']<$graphData['graphs'][$j]['points'][$i]['y']) {
								$peakCandidates[$i] = $graphData['graphs'][$j]['points'][$i];
								// gets the highest peak
								if($peakCandidates[$i]['y']>$maxPeak) {
									$maxPeak = $peakCandidates[$i]['y'];
								}
							}
						}
						// goes through all peakcandidates and eliminates the undersized ones
						foreach($peakCandidates as $i => $peakCandidate) {
							// undersized in comparison with the extreme ones
							if($peakCandidate['y']/$maxPeak<$config['peaks']['relativePeak']) {
								unset($peakCandidates[$i]);
								continue;
							}
							$sum = 0;
							$average = 0;
							// undersized in comparison with the other peaks in range
							for($k=-$config['peaks']['range']/2; $k<$config['peaks']['range']/2; $k++) {
								if($peakCandidates[$k] != NULL && $peakCandidates[$k]['y']>$peakCandidate['y']) {
									unset($peakCandidates[$i]);
								}
								elseif($peakCandidates[$k] != NULL && $peakCandidates[$k]['y']<$peakCandidate['y']) {
									unset($peakCandidates[$k]);
								}
								$sum += $graphData['graphs'][$j]['points'][$i+$k]['y'];
							}
							$average = $sum/$config['peaks']['range'];
							$significanceLevel = $peakCandidate['y']/$average;
							// undersized in comparison with the average of the block
							if($significanceLevel<$config['peaks']['significanceLevel']) {
								unset($peakCandidates[$i]);
								continue;
							}
						}
					}
					// eleminates peaks nearby or identical to another
					$graphData['graphs'][$j]['peaks'] = array_values($peakCandidates);
					$tempPeaks=array();
					$tempPeaks[] = reset($graphData['graphs'][$j]['peaks']);
					$tempPeaks[] = end($graphData['graphs'][$j]['peaks']);
					for($l<0; $l<count($graphData['graphs'][$j]['peaks']); $l++) {
						$tempPoint=array();
						for($m=$l+1; $m<count($graphData['graphs'][$j]['peaks']); $m++) {
							if(round($graphData['graphs'][$j]['peaks'][$l]['x'], $config['precision']['x'])==round($graphData['graphs'][$j]['peaks'][$m]['x'], $config['precision']['x'])) {
								if($graphData['graphs'][$j]['peaks'][$l]['y']<$graphData['graphs'][$j]['peaks'][$m]['y']) {
									$tempPoint = $graphData['graphs'][$j]['peaks'][$m];
								}
								else {
									$tempPoint = $graphData['graphs'][$j]['peaks'][$l];
								}
							}
							elseif($tempPoint==array()) {
								$tempPeaks[] = $graphData['graphs'][$j]['peaks'][$l];
								break;
							}
							else {
								$tempPeaks[] = $tempPoint;
								$l=$m;
								break;
							}
						}
					}
					usort($tempPeaks, function($a, $b) {return $a['x']*100000 - $b['x']*100000;});
					$graphData['graphs'][$j]['peaks'] = array_values(array_diff($tempPeaks, array_filter($tempPeaks, 'is_null')));
					// selects only the heighest peaks if it is turned on
					if($config['peaks']['maxPeaks']>0) {
						uasort($graphData['graphs'][$j]['peaks'], function($a, $b) {return $b['y']*100000 - $a['y']*100000;});	// sorts all values of the peaks by y
						$tempPeaks=array_slice($graphData['graphs'][$j]['peaks'], 0, $config['peaks']['maxPeaks']);	// gets the extreme ones
						usort($tempPeaks, function($a, $b) {return $b['x']*100000 - $a['x']*100000;});
						$graphData['graphs'][$j]['peaks']=$tempPeaks; // sets these as peaks
					}
				}
			}
		}
		return $graphData;
	}
	
	/*
	 * returns an appropriate tickScale
	 */
	public function getTickScale($delta,$no_ticks) {
		$tick_scale=pow(10,floor(log(2.5*$delta/$no_ticks,10))); // increment between ticks, either 10.. or 20.. or 50..
		for ($a=0;$a<2;$a++) {
			if ($delta>$tick_scale*$no_ticks) {
				$tick_scale*=2;
			}
		}
		return $tick_scale;
	}
	
	/*
	 * returns the best considered fitting tickScales and its proper tickDistances
	 */
	public function getBestTickDistance($graphData, $config) {
		$res = array();
		$min = PHP_INT_MAX;
		$distance = 0;
		$bestTickScale = 0;
		// tests for what distance of the ticks in between 40 and 60 px the difference between maximum tick value and maximum x value is the smallest
		// first in the direction of x, then y-direction
		for($i=40; $i<=60; $i++) {
			$a = $this->getTickScale($graphData['extrema']['maxima']['x']-$graphData['extrema']['minima']['x'], ceil(($config['dimensions']['width']-($config['axisOffset']['y']+$config['margin']['right']))/$i)-1);
			$tickcount = ceil(($config['dimensions']['width']-($config['axisOffset']['y']+$config['margin']['right']))/$i);
			if($min > $graphData['extrema']['minima']['x']+($tickcount-1)*$a-$graphData['extrema']['maxima']['x']) {
				$min = $graphData['extrema']['minima']['x']+($tickcount-1)*$a-$graphData['extrema']['maxima']['x'];
				$distance = $i;
				$bestTickScale = $a;
			}
		}
		$res['tickDistance']['x'] = $distance;
		$res['tickScale']['x'] = $bestTickScale;
		$min = PHP_INT_MAX;
		$distance = 0;
		$bestTickScale = 0;
		for($i=40; $i<=60; $i++) {
			$a = $this->getTickScale($graphData['extrema']['maxima']['y']-$graphData['extrema']['minima']['y'], ceil(($config['dimensions']['height']-($config['axisOffset']['x']+$config['margin']['top']))/$i)-1);
			$tickcount = ceil(($config['dimensions']['height']-($config['axisOffset']['x']+$config['margin']['top']))/$i);
			if($min > $graphData['extrema']['minima']['y']+($tickcount-1)*$a-$graphData['extrema']['maxima']['y']) {
					$min = $graphData['extrema']['minima']['y']+($tickcount-1)*$a-$graphData['extrema']['maxima']['y'];
					$distance = $i;
					$bestTickScale = $a;
				}
		}
		$res['tickDistance']['y'] = $distance;
		$res['tickScale']['y'] = $bestTickScale;
		if($config['2ndYAxis'] == true) {
			$min = PHP_INT_MAX;
			$distance = 0;
			$bestTickScale = 0;
			for($i=40; $i<=60; $i++) {
				$a = $this->getTickScale($graphData['extrema']['maxima']['y2']-$graphData['extrema']['minima']['y2'], ceil(($config['dimensions']['height']-($config['axisOffset']['x']+$config['margin']['top']))/$i)-1);
				$tickcount = ceil(($config['dimensions']['height']-($config['axisOffset']['x']+$config['margin']['top']))/$i);
				if($min > $graphData['extrema']['minima']['y2']+($tickcount-1)*$a-$graphData['extrema']['maxima']['y2']) {
					$min = $graphData['extrema']['minima']['y2']+($tickcount-1)*$a-$graphData['extrema']['maxima']['y2'];
					$distance = $i;
					$bestTickScale = $a;
				}
			}
			$res['tickDistance']['y2'] = $distance;
			$res['tickScale']['y2'] = $bestTickScale;
		}
		return $res;
	}
	
	/*
	 * php bugfix for unsigned long int
	 * returns the correct value
	 */
	protected function fixLongInt($new, $old, $threshold) {
		if($old!=NULL && abs($new-$old) > $threshold) { // if the difference between new and old is > threshold, change sign
			if($this->isNegative==0) {
				$this->isNegative = 1;
			}
			else {
				$this->isNegative = 0;
			}
		}
		$new = $new - $this->isNegative*4294967296;
		return $new;
	}
	
	/*
	 * returns the configuration of the converter
	 */
	public function getConfig() {
		return $this->config;
	}
	
	/*
	 * returns sketchable graphData
	 */
	public function getGraphData() {
		return $this->graphData;
	}
}
?>