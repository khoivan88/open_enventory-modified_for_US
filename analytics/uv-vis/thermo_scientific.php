<?php
$GLOBALS["type_code"]="uv-vis";
$GLOBALS["device_driver"]="thermo_scientific";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(".csv"),
		"optionalFiles" => array());
/*
 * Reads and converts a *.csv file to sketchable graphdata
 */

class thermo_scientific extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.csv'][$this->fileNumber];	// puts all the data into a string
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['x'] = 4;
			$this->config['precision']['y'] = 4;
			$this->config['peaks']['range'] = 25;
			$this->config['yUnitOffset'] = 110;
			
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
	 * converts an uv-vis/*.csv file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		$lines = explode("\n", $this->data);
		$this->graphData['extrema']['maxima']['x'] = -PHP_INT_MAX;
		$this->graphData['extrema']['minima']['x'] = PHP_INT_MAX;
		$this->graphData['extrema']['maxima']['y'] = -PHP_INT_MAX;
		$this->graphData['extrema']['minima']['y'] = PHP_INT_MAX;
		
		// gets xy data and gets extrema
		for($i=0; $i<count($lines)-1; $i++) {
			if(is_numeric(str_replace(',', '.', str_split($lines[$i], strpos($lines[$i], ';'))[0]))) {
				$this->graphData['graphs'][0]['points'][$i]['x'] = floatval(str_replace(',', '.', str_split($lines[$i], strpos($lines[$i], ';'))[0]));
				$this->graphData['graphs'][0]['points'][$i]['y'] = floatval(str_replace(',', '.', str_split($lines[$i], strpos($lines[$i], ';')+1)[1]));
				if($this->graphData['graphs'][0]['points'][$i]['y'] < 0) {
					$this->graphData['graphs'][0]['points'][$i]['y'] = 0;
				}
				if($this->graphData['graphs'][0]['points'][$i]['x'] > $this->graphData['extrema']['maxima']['x']) {
					$this->graphData['extrema']['maxima']['x'] = $this->graphData['graphs'][0]['points'][$i]['x'];
				}
				if($this->graphData['graphs'][0]['points'][$i]['y'] > $this->graphData['extrema']['maxima']['y']) {
					$this->graphData['extrema']['maxima']['y'] = $this->graphData['graphs'][0]['points'][$i]['y'];
				}
				if($this->graphData['graphs'][0]['points'][$i]['x'] < $this->graphData['extrema']['minima']['x']) {
					$this->graphData['extrema']['minima']['x'] = $this->graphData['graphs'][0]['points'][$i]['x'];
				}
				if($this->graphData['graphs'][0]['points'][$i]['y'] < $this->graphData['extrema']['minima']['y']) {
					$this->graphData['extrema']['minima']['y'] = $this->graphData['graphs'][0]['points'][$i]['y'];
				}
			}
		}
		
		// sets to graphdata
		$this->graphData['graphs'][0]['points'] = array_values($this->graphData['graphs'][0]['points']);
		$this->graphData['units']['x'] = "Wellenlaenge (nm)";
		$this->graphData['units']['y'] = "Extinktion";
	}
	
	/*
	 * checks if the signature of the file fits the signature of the converter
	 * it returns 0, if it fits, else 1. if there is none, return 2
	 */
	public function verifyFileSignature($file_contents) {
		$this->fileNumber = 0;
		return 2;
	}
}
?>