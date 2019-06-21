<?php
$GLOBALS["type_code"]="ir";
$GLOBALS["device_driver"]="irf";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(".irf"),
		"optionalFiles" => array());
/*
 * Reads and converts an *.irf file to sketchable graphdata
 */

class irf extends IRconverter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.irf'][$this->fileNumber];	// puts all the data into a string
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['x'] = 0;
			$this->config['precision']['y'] = 1;
			$this->config['peaks']['range'] = 50;
			$this->config['peaks']['minimum'] = true;
			
			// does the converting
			$this->convertFileToGraphData();
			
			// gets the peaks
			$this->graphData = $this->getPeaks($this->graphData, $this->config);
			
			// produces interpretation
			$this->produceInterpretation();
			
			// gets the best considered fitting tickScales and its proper tickDistances
			$tickDistancesAndTickScales = $this->getBestTickDistance($this->graphData, $this->config);
			$this->graphData['tickDistance'] = $tickDistancesAndTickScales['tickDistance'];
			$this->graphData['tickScale'] = $tickDistancesAndTickScales['tickScale'];
			
			// produces csvDataString
			$this->graphData['csvDataString'][0] = $this->produceCsvDataString($this->graphData);
			
			// converts to the specific coordinates of the various pixels
			$this->graphData = $this->convertPointsToPixelCoordinates($this->graphData, $this->config);
		}
	}
	
	/*
	 * converts an ir/*.irf file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		// number of points @0x6c 16bit little endian
		$nrOfPoints = $this->readData($this->data, 'v', 2, 108);
		
		// Absorption (321) or Transmission (322) @0x95 16bit little endian
		$mode = $this->readData($this->data, 'v', 2, 149); // not sure
		if ($mode==321) {
			$this->graphData['units']['y'] = "Absorbance";
			$this->config['peaks']['minimum'] = false;
		}
		else {
			$this->graphData['drawingStyle'] = 2; // sets drawingStyle to y axis right
			$this->graphData['units']['y'] = "%T";
			$this->config['peaks']['relativePeak'] = 0.0;
		}
		
		// max x @0x54
		$this->graphData['extrema']['maxima']['x'] = round($this->readData($this->data, 'd', 8, 84), $this->config['precision']['x']-1);
		
		// min x @0x5c
		$this->graphData['extrema']['minima']['x'] = round($this->readData($this->data, 'd', 8, 92), $this->config['precision']['x']-1);
		$this->graphData['extrema']['maxima']['y'] = 100;
		$this->graphData['extrema']['minima']['y'] = 0;
		$yMax=-PHP_INT_MAX;
		$yMin=PHP_INT_MAX;
		
		// graph at 0x200 32 bit little endian
		$offset=512;
		$blocks=str_split(substr($this->data,$offset,$nrOfPoints*4),4);
		for($i=0; $i<count($blocks)-1; $i++) {
			if($mode == 321) {
				$factor = $i;
			}
			else {
				$factor = (count($blocks)-$i);
			}
			$this->graphData['graphs'][0]['points'][$i]['x'] = $this->graphData['extrema']['minima']['x']+($this->graphData['extrema']['maxima']['x']-$this->graphData['extrema']['minima']['x'])/count($blocks)*$factor;
			$this->graphData['graphs'][0]['points'][$i]['y'] = $this->readData($blocks[$i], 'l', strlen($blocks[$i]), 0); // best would be w
			$this->graphData['graphs'][0]['points'][$i]['y'] = $this->fixLongInt($this->graphData['graphs'][0]['points'][$i]['y'], $this->graphData['graphs'][0]['points'][$i-1]['y'], 3000000000);
			if($this->graphData['graphs'][0]['points'][$i]['y']>$yMax) {
				$yMax = $this->graphData['graphs'][0]['points'][$i]['y'];
			}
			if($this->graphData['graphs'][0]['points'][$i]['y']<$yMin) {
				$yMin = $this->graphData['graphs'][0]['points'][$i]['y'];
			}
		}
		// normalisation
		for($i=0; $i<count($this->graphData['graphs'][0]['points']); $i++) {
			if ($mode==321) {
				$this->graphData['graphs'][0]['points'][$i]['y'] = 100/($yMax-$yMin)*($this->graphData['graphs'][0]['points'][$i]['y']-$yMin);
			}
			else {
				$this->graphData['graphs'][0]['points'][$i]['y'] = 100.01-100/($yMax-$yMin)*($this->graphData['graphs'][0]['points'][$i]['y']-$yMin);
			}
		}
		$this->graphData['units']['x'] = "cm-1";
	}
	
	/*
	 * checks if the signature of the file fits the signature of the converter
	 * it returns 0, if it fits, else 1. if there is none, return 2
	 */
	public function verifyFileSignature($file_contents) {
		$isCorrectConverter=0;
		for($i=0; $i<count($file_contents); $i++) {
			for($j=0; $j<count($file_contents[array_keys($file_contents)[$i]]); $j++) {
				if(substr($file_contents[array_keys($file_contents)[$i]][$j], 0, 4) == "FT7\0") {
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>