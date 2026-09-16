<?php
$GLOBALS["type_code"]="xray_powder";
$GLOBALS["device_driver"]="stoe_raw";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(".raw"),
		"optionalFiles" => array());
/*
 * Reads and converts a *.raw file to sketchable graphdata
 */

class stoe_raw extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 2048;	// important data starts at cursor position 2048
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.raw'][$this->fileNumber];	// puts all the data into a string
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['x'] = 1;
			$this->config['precision']['y'] = 1;
			$this->config['peaks']['range'] = 100;
			$this->config['peaks']['significanceLevel'] = 1.4;
			$this->config['margin']['right'] = 40;
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
		$blockSizes = unpack('C*', substr($this->data, 496, 15));
		for($i=0; $i<count($blockSizes); $i++) {
			if($blockSizes[$i]<1) {
				unset($blockSizes[$i]);
			}
		}
		$blockSizes = array_values($blockSizes);
		
		$this->graphData['extrema']['minima']['x'] = round($this->readData($this->data, 'f', 4, $this->cursorPos+44));
		$this->graphData['extrema']['maxima']['x'] = round($this->readData($this->data, 'f', 4, $this->cursorPos+52));
		$xStep = $this->readData($this->data, 'f', 4, $this->cursorPos+60);
		
		for($i=0; $i<count($blockSizes); $i++) {
			if($blockSizes[$i]<1) {
				break;
			}
			$this->graphData['graphNames'][$i] = "Graph".$i." "; 
			if($i>0) {
				$this->config['peaks']['computePeaks'] = false;
			}
			$this->cursorPos += 512;
			$nrOfPoints = ($this->graphData['extrema']['maxima']['x']-$this->graphData['extrema']['minima']['x'])/$xStep;
			$pointLength = 4;
			$code = "I";
			if($nrOfPoints > $blockSizes[$i]*512/$pointLength) {
				$pointLength = 2;
				$code = 's';
			}
			$binEnd = $this->cursorPos + $nrOfPoints*$pointLength;
			if($binEnd>$rawSize) {
				break;
			}
			$yMax = -PHP_INT_MAX;
			// gets xy data
			for($j=0; $j<$nrOfPoints; $j++) {
				$this->graphData['graphs'][$i]['points'][$j]['x'] = $this->graphData['extrema']['minima']['x']+$j*$xStep;
				$this->graphData['graphs'][$i]['points'][$j]['y'] = $this->readData($this->data, $code, $pointLength, $this->cursorPos+$j*$pointLength);
				if($yMax < $this->graphData['graphs'][$i]['points'][$j]['y']) {
					$yMax = $this->graphData['graphs'][$i]['points'][$j]['y'];
				}
				if($this->graphData['graphs'][$i]['points'][$j]['y']<0) {
					$this->graphData['graphs'][$i]['points'][$j]['y'] = 0;
				}
			}
			$this->graphData['extrema']['maxima']['y'] = 100;
			$this->graphData['extrema']['minima']['y'] = 0;
			
			// normalisation
			for($j=0; $j<count($this->graphData['graphs'][$i]['points']); $j++) {
				$this->graphData['graphs'][$i]['points'][$j]['y'] = 100/$yMax*$this->graphData['graphs'][$i]['points'][$j]['y'];
			}
			if($this->graphData['graphs'][$i]['points'][$nrOfPoints-1]['x'] > $this->graphData['extrema']['maxima']['x']) {
				$this->graphData['extrema']['maxima']['x'] = round($this->graphData['graphs'][$i]['points'][$nrOfPoints-1]['x']);
			}
			$this->graphData['units']['x'] = "2 theta in °";
			$this->graphData['units']['y'] = "%";
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
				if(substr($file_contents[array_keys($file_contents)[$i]][$j], 0, 4) == "RAW_") {
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>