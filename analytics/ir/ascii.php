<?php
$GLOBALS["type_code"]="ir";
$GLOBALS["device_driver"]="ascii";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(".txt"),
		"optionalFiles" => array());
/*
 * Reads and converts an ir ascii file to sketchable graphdata
 */

class ascii extends IRconverter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.txt'][$this->fileNumber];	// puts all the data into a string
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['y'] = -1;
			$this->config['precision']['x'] = 0;
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
	 * converts an ir ascii file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		$sep_re="/[\s\t]+/";
		
		// searches for xy-pairs
		$this->data=str_replace(array("\r\n","\r"),"\n",$this->data);
		$lines=explode("\n",$this->data);

		// gets extrema and xy data
		$pointsAsXY=array();
		for ($a=0;$a<count($lines);$a++) {
			list($left_value,$right_value)=preg_split($sep_re,trim($lines[$a]),2);
			$left_value=trim($left_value);
			$right_value=trim($right_value);
			if($a==11) {
				$this->graphData['extrema']['minima']['x']=round($right_value, $this->config['precision']['x']-1);
			}
			if($a==12) {
				$this->graphData['extrema']['maxima']['x']=round($right_value, $this->config['precision']['x']-1);
			}
			if($a==15) {
				$this->graphData['extrema']['maxima']['y']=round($right_value, $this->config['precision']['y']);
			}
			if($a==16) {
				$this->graphData['extrema']['minima']['y']=round($right_value, $this->config['precision']['y'])-10;
			}
			if (is_numeric($left_value) && is_numeric($right_value)) { // checks if it is a datapoint
				$pointsAsXY[$a]['x']=$left_value;
				$pointsAsXY[$a]['y']=$right_value;
			}
		}
		$this->graphData['drawingStyle'] = 2; // sets drawingStyle to y-axis right
		$this->graphData['graphs'][0]['points'] = array_values($pointsAsXY);
		
		// sets units
		$this->graphData['units']['x']="cm-1";
		$this->graphData['units']['y']="%T";
	}
	
	/*
	 * checks if the signature of the file fits the signature of the converter
	 * it returns 0, if it fits, else 1. if there is none, return 2
	 */
	public function verifyFileSignature($file_contents) {
		$isCorrectConverter=0;
		for($i=0; $i<count($file_contents); $i++) {
			for($j=0; $j<count($file_contents[array_keys($file_contents)[$i]]); $j++) {
				$string = str_replace(array("\r\n","\r"),"\n", $file_contents[array_keys($file_contents)[$i]][$j]);
				if(explode("\n", $string)[1]=="DATA TYPE	INFRARED SPECTRUM") {
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>