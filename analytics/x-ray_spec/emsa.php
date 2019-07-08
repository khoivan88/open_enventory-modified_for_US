<?php
$GLOBALS["type_code"]="x-ray_spec";
$GLOBALS["device_driver"]="emsa";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(".ems"),
		"optionalFiles" => array());
/*
 * Reads and converts an *.emsa file to sketchable graphdata
 */

class emsa extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.ems'][$this->fileNumber];	// puts all the data into a string
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['x'] = 0;
			$this->config['precision']['y'] = 0;
			$this->config['peaks']['range'] = 25;
			$this->config['axisOffset']['y'] = 50;
			$this->config['yUnitOffset'] = 95;
			$this->config['peaks']['computePeaks'] = true;
			$this->config['peaks']['significanceLevel'] = 1.2;
			
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
	 * converts a x-ray_spec/*.emsa file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		// some preparations
		$lines = explode("\n", $this->data);
		$emptyLines = 0;
		$phase = 0; # 0: header, 1: graph
		$xColumns = 1;
		$yColumns = 1;
		for($i=0; $i<count($lines); $i++) {
			if(trim($lines[$i])=="") {
				continue;
			}
			if(startswith(strtoupper(trim($lines[$i])), "#ENDOFDATA")==true) {
				$phase = 0;
			}
			elseif(startswith(strtoupper(trim($lines[$i])), "#")==true && strpos(strtoupper(trim($lines[$i])), ':')>0) {
				$name = trim(substr(strtoupper(trim($lines[$i])), 1, strpos(strtoupper(trim($lines[$i])), ':')-1));
				$value = trim(substr(trim($lines[$i]), strpos(strtoupper(trim($lines[$i])), ':')+1, strlen(trim($lines[$i]))));
				if($name == "XUNITS") {
					$this->graphData['units']['x'] = $value;
				}
				elseif($name == "YUNITS") {
					$this->graphData['units']['y'] = $value;
				}
				elseif($name == "DATATYPE") {
					$value = strtoupper($value);
					if($value == "XY") {
						$xColumns = 1;
					}
					elseif($value = "Y") {
						$xColumns = 0;
					}
				}
				elseif($name == "OFFSET") {
					if($value<10) {
						$this->graphData['extrema']['minima']['x'] = round($value, 2);
					}
					else {
						$this->graphData['extrema']['minima']['x'] = round($value);
					}
				}
				elseif($name == "XPERCHAN") { 
					$deltaX = $value;
				}
				elseif($name == "NCOLUMNS") {
					$yColumns = $value;
				}
				elseif($name == "SPECTRUM") {
					$phase = 1;
				}
				
			} // gets xy data
			elseif($phase == 1) {
				$point=array();
				for($j=0; $j<count(explode(',', $lines[$i])); $j++) {
					if(trim(explode(',', $lines[$i])[$j])!= "") {
						if($xColumns == 1 && $j==0) {
							$point['x'] = trim(explode(',', $lines[$i])[$j]);
						}
						else {
							$point['y'] = trim(explode(',', $lines[$i])[$j]);
						}
						if($xColumns == 1 && $j!=0) {
							$this->graphData['graphs'][$j-1]['points'][] = $point;
						}
						elseif($xColumns == 0) {
							$this->graphData['graphs'][$j]['points'][] = $point;
						}
					}
				}
				if($this->graphData['extrema']['maxima']['y']<$point['y']) {
					$this->graphData['extrema']['maxima']['y'] = $point['y'];
				}
			}
		} // calculates x value if there is none
		if($xColumns == 0) {
			for($j=0; $j<count($this->graphData['graphs']); $j++) {
				for($i=0; $i<count($this->graphData['graphs'][$j]['points']); $i++) {
					$this->graphData['graphs'][$j]['points'][$i]['x'] = $this->graphData['extrema']['minima']['x'] + $deltaX*$i;
				}
			}
		}
		for($j=0; $j<count($this->graphData['graphs']); $j++) {
			$this->graphData['graphNames'][$j] = "Graph ".($j+1);
			if($this->graphData['graphs'][$j]['points'][count($this->graphData['graphs'][$j]['points'])-1]['x']<10) {
				$this->graphData['extrema']['maxima']['x'] = round($this->graphData['graphs'][$j]['points'][count($this->graphData['graphs'][$j]['points'])-1]['x'], 2);
			}
			else {
				$this->graphData['extrema']['maxima']['x'] = round($this->graphData['graphs'][$j]['points'][count($this->graphData['graphs'][$j]['points'])-1]['x']);
			}
		}
	}
	
	/*
	 * checks if the signature of the file fits the signature of the converter
	 * it returns 0, if it fits, else 1. if there is none, return 2
	 */
	public function verifyFileSignature($file_contents) {
		$isCorrectConverter=0;
		for($i=0; $i<count($file_contents); $i++) {
			for($j=0; $j<count($file_contents[array_keys($file_contents)[$i]]); $j++) {
				if(strpos(explode("\n", $file_contents[array_keys($file_contents)[$i]][$j])[0], "EMSA")>0) {
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>