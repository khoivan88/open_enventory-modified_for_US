<?php
$GLOBALS["type_code"]="cv";
$GLOBALS["device_driver"]="cyclovoltammetry";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(".txt"),
		"optionalFiles" => array());
/*
 * Reads and converts a *.txt file into sketchable graphdata
 */

class cyclovoltammetry extends converter {
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.txt'][$this->fileNumber];	// puts all the data into a string
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['x'] = 3;
			$this->config['precision']['y'] = 6;
			$this->config['peaks']['computePeaks'] = false;
			$this->config['axisOffset']['y'] = 60;
			
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
	 * converts a cv/*.txt file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		$splitWord = "Potential"; // separator between header and xydata
		$this->graphData['interpretation'] = str_split($this->data, strpos($this->data, $splitWord)-1)[0]; // executes separation
		$xyData = explode("\n", substr($this->data, strpos($this->data, $splitWord), strlen($this->data)));
		$lines = explode("\n", $this->graphData['interpretation']);
		$trace_name = trim($lines[0], " \r");
		
		# creates graph
		// gets some properties
		for($i=0; $i<count($lines)-1; $i++) {
			if(startswith($lines[$i], "Instrument Model:")==true) {
				$instrument = trim(substr($lines[$i], strpos($lines[$i], ":")+1), " \r");
			}
			if(startswith($lines[$i], "Scan Rate")==true) {
				$scanRate = trim(substr($lines[$i], strpos($lines[$i], "=")+1), " \r");
			}
		}
		
		// converts xy data and determines exrtema
		$this->graphData['extrema']['maxima']['x'] = -PHP_INT_MAX;
		$this->graphData['extrema']['minima']['x'] = PHP_INT_MAX;
		$this->graphData['extrema']['maxima']['y'] = -PHP_INT_MAX;
		$this->graphData['extrema']['minima']['y'] = PHP_INT_MAX;
		$graphNumber = -1;
		for($i=2; $i<count($xyData)-1; $i++) {
			if($xyData[$i]!="") {
				$point['x'] = floatval(str_split($xyData[$i], strpos($xyData[$i], ","))[0]);
				$point['y'] = floatval(substr($xyData[$i], strpos($xyData[$i], ",")+2))*1000;
				if($point['x'] == 0) {
					$graphNumber++;
					$this->graphData['graphNames'][$graphNumber] = "Round ".($graphNumber+1);
				}
				$this->graphData['graphs'][$graphNumber]['points'][] = $point;
				if($point['x'] > $this->graphData['extrema']['maxima']['x']) {
					$this->graphData['extrema']['maxima']['x'] = $point['x'];
				}
				if($point['y'] > $this->graphData['extrema']['maxima']['y']) {
					$this->graphData['extrema']['maxima']['y'] = round($point['y'], 4);
				}
				if($point['x'] < $this->graphData['extrema']['minima']['x']) {
					$this->graphData['extrema']['minima']['x'] = $point['x'];
				}
				if($point['y'] < $this->graphData['extrema']['minima']['y']) {
					$this->graphData['extrema']['minima']['y'] = round($point['y'], 4);
				}
			}
		}
		// sets units and properties
		$this->graphData['units']['x'] = "V";
		$this->graphData['units']['y'] = "mA";
		$this->graphData['analytical_data_properties']['measurement_info'] = $trace_name;
		$this->graphData['analytical_data_properties']['instrument'] = $instrument;
		$this->graphData['analytical_data_properties']['scanrate'] = $scanRate;
	}
	
	/*
	 * checks if the signature of the file fits the signature of the converter
	 * it returns 0, if it fits, else 1. if there is none, return 2
	 */
	public function verifyFileSignature($file_contents) {
		$isCorrectConverter=0;
		for($i=0; $i<count($file_contents); $i++) {
			for($j=0; $j<count($file_contents[array_keys($file_contents)[$i]]); $j++) {
				if(trim(explode("\n", $file_contents[array_keys($file_contents)[$i]][$j])[1])=="Cyclic Voltammetry") {
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>