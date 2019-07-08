<?php
$GLOBALS["type_code"]="ir";
$GLOBALS["device_driver"]="sp";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(".sp"),
		"optionalFiles" => array());
/*
 * Reads and converts a *.sp file to sketchable graphdata
 */

class sp extends IRconverter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 44;	// important data starts at cursor position 44
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.sp'][$this->fileNumber];	// puts all the data into a string
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['y'] = -1;
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
	 * converts an ir/*.sp file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		
		while($this->cursorPos < strlen($this->data)) {
			// the next blockID and size. they are required for the decision what should be made with the following binary code segment
			$blockID = $this->readData($this->data, 's', 2, $this->cursorPos); 
			$this->cursorPos += 2;
			$size = $this->readData($this->data, 'l', 4, $this->cursorPos);
			$this->cursorPos += 4;
			
			// reads and puts the necessary data of the block into the graphData array
			switch ($blockID) {
				case 120:
					break;
				case -29825:
					break;
				case -29838: // DataSetAbscissaRangeMember
					$this->cursorPos += 2;
					$this->graphData['extrema']['maxima']['x'] = floor($this->readData($this->data, 'd', 8, $this->cursorPos));
					$this->cursorPos += 8;
					$this->graphData['extrema']['minima']['x'] = ceil($this->readData($this->data, 'd', 8, $this->cursorPos));
					$this->cursorPos += 8;
					break;
				case -29835: // DataSetNumPointsMember
					$innerID = $this->readData($this->data, 's', 2, $this->cursorPos);
					$this->cursorPos += 2;
					$points = $this->readData($this->data, 'l', 4, $this->cursorPos);
					$this->cursorPos += 4;
					break;
				case -29833: // DataSetXAxisLabelMember
					$this->cursorPos += 2;
					$field_len = $this->readData($this->data, 's', 2, $this->cursorPos);
					$this->cursorPos += 2;
					$this->graphData['units']['x'] = substr($this->data,$this->cursorPos,$field_len);
					$this->cursorPos += $field_len;
					break;
				case -29832: // DataSetYAxisLabelMember
					$this->cursorPos += 2;
					$field_len = $this->readData($this->data, 's', 2, $this->cursorPos);
					$this->cursorPos += 2;
					$this->graphData['units']['y'] = substr($this->data,$this->cursorPos,$field_len);
					$this->cursorPos += $field_len;
					break;
				case -29828: // DataSetDataMember
					$this->cursorPos += 2;
					$field_len = $this->readData($this->data, 'l', 4, $this->cursorPos);
					$this->cursorPos += 4;
					if ($points == 0) {
						$points = $field_len/8;
					}
					
					// reads the graph and determines the y ranges
					$yMin=PHP_INT_MAX;
					$yMax=-PHP_INT_MAX;
					$pointsAsXY=array();
					$factor=($this->graphData['extrema']['maxima']['x']-$this->graphData['extrema']['minima']['x'])/$points; // the factor if range is greater then number of points
					for ($a=0;$a<$points;$a++) {
						$pointsAsXY[$points-1-$a]['x']=$a*$factor+$this->graphData['extrema']['minima']['x'];
						$pointsAsXY[$a]['y']=$this->readData($this->data, 'd', 8, $this->cursorPos);
						$this->cursorPos += 8;
						if($yMin>$pointsAsXY[$a]['y'] || $yMin==0) {
							$yMin = $pointsAsXY[$a]['y'];
						}
						if($yMax<$pointsAsXY[$a]['y'] || $yMax==0) {
							$yMax = $pointsAsXY[$a]['y'];
						}
					}
					// sets graphdata
					$this->graphData['graphs'][0]['points'] = $pointsAsXY;
					$this->graphData['extrema']['minima']['y'] = round($yMin, $this->config['precision']['y'])-10;
					$this->graphData['extrema']['maxima']['y'] = round($yMax, $this->config['precision']['y']);
					break;
				default:
					$this->cursorPos += $size;
			}
		}
		$this->graphData['drawingStyle'] = 2; // sets drawingStyle to y axis right
	}
	
	/*
	 * checks if the signature of the file fits the signature of the converter
	 * it returns 0, if it fits, else 1. if there is none, return 2
	 */
	public function verifyFileSignature($file_contents) {
		$isCorrectConverter=0;
		for($i=0; $i<count($file_contents); $i++) {
			for($j=0; $j<count($file_contents[array_keys($file_contents)[$i]]); $j++) {
				if(substr($file_contents[array_keys($file_contents)[$i]][$j], 0, 4)=="PEPE") {
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>