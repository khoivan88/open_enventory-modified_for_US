<?php
$GLOBALS["type_code"]="xray_powder";
$GLOBALS["device_driver"]="bruker_raw";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(".raw"),
		"optionalFiles" => array());
/*
 * Reads and converts a *.raw file to sketchable graphdata
 */

class bruker_raw extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 1016;	// important data starts at cursor position 1016
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.raw'][$this->fileNumber];	// puts all the data into a string
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['x'] = 1;
			$this->config['precision']['y'] = 1;
			$this->config['peaks']['range'] = 100;
			$this->config['margin']['right'] = 50;
			$this->config['peaks']['significanceLevel'] = 1.4;
			$this->config['peaks']['relativePeak'] = 0.15;
			$this->config['yUnitOffset'] = 30;
			
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
	 * converts a xray_powder/*.raw file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		// does some preparations and gets extrema
		$rawSize = strlen($this->data);
		$xStep = $this->readData($this->data, 'd', 8, 888);
		$nrOfPoints = $this->readData($this->data, 'I', 4, 716);
		$this->graphData['extrema']['maxima']['x'] = round($this->graphData['extrema']['minima']['x'] + $xStep * $nrOfPoints);
		$this->graphData['extrema']['minima']['x'] = round($this->readData($this->data, 'd', 8, 728));
		$this->graphData['extrema']['maxima']['y'] = 100;
		$this->graphData['extrema']['minima']['y'] = 0;
		$this->graphData['units']['x'] = "2 theta in °";
		$this->graphData['units']['y'] = "%";
		
		
		// gets xy data
		$yMax=-PHP_INT_MAX;
		for($i=0; $i<$nrOfPoints; $i++) {
			$this->graphData['graphs'][0]['points'][$i]['x'] = $this->graphData['extrema']['minima']['x'] + $xStep*$i;
			$this->graphData['graphs'][0]['points'][$i]['y'] = $this->readData($this->data, 'f', 4, $this->cursorPos);
			if($this->graphData['graphs'][0]['points'][$i]['y'] < 0) {
				$this->graphData['graphs'][0]['points'][$i]['y'] = 0;
			}
			if($this->graphData['graphs'][0]['points'][$i]['y'] > $yMax) {
				$yMax = $this->graphData['graphs'][0]['points'][$i]['y'];
			}
			$this->cursorPos += 4;
		}
		if($this->graphData['graphs'][0]['points'][$nrOfPoints-1]['x'] > $this->graphData['extrema']['maxima']['x']) {
			$this->graphData['extrema']['maxima']['x'] = round($this->graphData['graphs'][0]['points'][$nrOfPoints-1]['x']);
		}
		
		// normalisation
		for($i=0; $i<count($this->graphData['graphs'][0]['points']); $i++) {
			$this->graphData['graphs'][0]['points'][$i]['y'] = 100/$yMax*$this->graphData['graphs'][0]['points'][$i]['y'];
		}
	}
	
	/*
	 * produces the interpretation string
	 */
	public function produceInterpretation() {
		$this->graphData['interpretation'] = "2 \xCE\xB8 = ";
		for($i=0; $i<count($this->graphData['graphs'][0]['peaks'])-2; $i++) {
			$this->graphData['interpretation'] .= round($this->graphData['graphs'][0]['peaks'][$i]['x'], 2)."° (".round($this->graphData['graphs'][0]['peaks'][$i]['y'])."), ";
		}
		$this->graphData['interpretation'] .= round($this->graphData['graphs'][0]['peaks'][count($this->graphData['graphs'][0]['peaks'])-1]['x'], 2)."° (".round($this->graphData['graphs'][0]['peaks'][count($this->graphData['graphs'][0]['peaks'])-1]['y']).")";
	}
	
	/*
	 * checks if the signature of the file fits the signature of the converter
	 * it returns 0, if it fits, else 1. if there is none, return 2
	 */
	public function verifyFileSignature($file_contents) {
		$isCorrectConverter=0;
		for($i=0; $i<count($file_contents); $i++) {
			for($j=0; $j<count($file_contents[array_keys($file_contents)[$i]]); $j++) {
				if(substr($file_contents[array_keys($file_contents)[$i]][$j], 0, 3) == "RAW" && substr($file_contents[array_keys($file_contents)[$i]][$j], 7, 1) == "\0") {
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>