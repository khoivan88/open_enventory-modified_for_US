<?php
$GLOBALS["type_code"]="ms";
$GLOBALS["device_driver"]="nist_ascii";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(".msp"),
		"optionalFiles" => array());
/*
 * Reads and converts a *.msp file to sketchable graphdata
 */

class nist_ascii extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.msp'][$this->fileNumber];	// puts all the data into a string
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['legendOffset'] = 130;
			$this->config['computePeaks'] = true;
			$this->config['peaks']['maxPeaks'] = 7;
			
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
	 * converts a ms/*.msp file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		$lines=explode("\n",$this->data);
		
		// gets xy data
		$stage=0;
		$peak_sep="; ";
		$yMax=-PHP_INT_MAX;
		$xMax=-PHP_INT_MAX;
		$yMin=0;
		$xMin=PHP_INT_MAX;
		if (is_array($lines)) foreach ($lines as $line_no => $line) {
			if (strpos($line,": ")===FALSE && strpos($line,$peak_sep)!==FALSE) {
				$stage=1;
			}
		
			switch ($stage) {
				case 0:
					list($name,$value)=explode(": ",$line,2);
					if($name=="Name") {
						$this->graphData['graphNames'][0] = substr($value, 0, strlen($value)-1);
					}
					break;
				case 1:
					$peaks=explode($peak_sep,$line);
					for ($a=0;$a<count($peaks);$a++) {
						$cells=explode(" ",$peaks[$a],2);
						if($cells[1]!=NULL) {
							$point=array();
							$point['x'] = $cells[0];
							$point['y'] = $cells[1];
							$this->graphData['graphs'][0]['points'][] = $point;
							if($cells[0]>$xMax) {
								$xMax = $cells[0];
							}
							if($cells[0]<$xMin) {
								$xMin = $cells[0];
							}
							if($cells[1]>$yMax) {
								$yMax = $cells[1];
							}
						}
					}
					break;
			}
		}
		
		// normalisation
		for($i=0; $i<count($this->graphData['graphs'][0]['points']); $i++) {
			$this->graphData['graphs'][0]['points'][$i]['y'] = 100/($yMax-$yMin)*($this->graphData['graphs'][0]['points'][$i]['y']-$yMin);
		}
		
		// sets all the graphData
		$this->graphData['extrema']['maxima']['y'] = 100;
		$this->graphData['extrema']['minima']['y'] = 0;
		$this->graphData['extrema']['maxima']['x'] = round($xMax, -1);
		$this->graphData['extrema']['minima']['x'] = round($xMin, -1);
		$this->graphData['units']['x'] = "m/z";
		$this->graphData['units']['y'] = "%";
		$this->graphData['drawingStyle'] = 1; // set drawingStyle to candlesticks
	}
	
	/*
	 * produces the interpretation string
	 */
	public function produceInterpretation() {
		// gets the peaks and their intensity as interpretation
		$interpretationString="m/z (%)\n";
		for($a=0; $a<count($this->graphData['graphs'][0]['peaks']); $a++) {
			if($a!=0) {
				$interpretationString.=", ".round($this->graphData['graphs'][0]['peaks'][$a]['x'], 0)." (".round($this->graphData['graphs'][0]['peaks'][$a]['y']).")";
			}
			else {
				$interpretationString.=round($this->graphData['graphs'][0]['peaks'][$a]['x'], 0)." (".round($this->graphData['graphs'][0]['peaks'][$a]['y']).")";
			}
		}
		$this->graphData['interpretation']=$interpretationString;	// set interpretation
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
