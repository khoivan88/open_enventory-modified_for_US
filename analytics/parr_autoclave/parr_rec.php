<?php
$GLOBALS["type_code"]="parr_autoclave";
$GLOBALS["device_driver"]="parr_rec";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(".rec"),
		"optionalFiles" => array());
/*
 * Reads and converts a *.rec file to sketchable graphdata
 */

class parr_rec extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.rec'][$this->fileNumber];	// puts all the data into a string
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['peaks']['computePeaks'] = false;
			$this->config['legendOffset'] = 170;
			$this->config['precision']['x'] = 2;
			$this->config['precision']['y'] = 1;
			$this->config['2ndYAxis'] = true; // activate second y axis
			
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
	 * converts a parr_autoclave/*.rec file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		// some preparations
		$rawSize = strlen($this->data);
		$blockSize = 25;
		
		$channels =array();
		for($int=0; $i<12; $i++) {
			$channels[] = $this->readData($this->data, 'C', 1, $this->cursorPos);
			$this->cursorPos += 1;
		}
		$temps = array();
		$rpms = array();
		$press = array();
		
		$samples = 0;
		
		$values = array();
		for($i=12; $i<$rawSize; $i=$i+$blockSize) {
			for($j=$i; $j<$i+20; $j+=2) {
				$values[$i][] = $this->readData($this->data, 'n', 2, $this->cursorPos);
				$this->cursorPos += 2;
			}
			$this->cursorPos += 5;
		}
		$values = array_values($values);
		
		
		// reads xyy2 data
		$this->graphData['extrema']['maxima']['y'] = -PHP_INT_MAX;
		$this->graphData['extrema']['maxima']['y2'] = -PHP_INT_MAX;
		$this->graphData['extrema']['minima']['y'] = 0;
		$this->graphData['extrema']['minima']['y2'] = 0;
		for($i=0; $i<count($values); $i++) {
			$temps[$i] = 0.1*$values[$i][0];
			$rpms[$i] = $values[$i][1];
			$press[$i] = 0.1*$values[$i][2];
			if(1000 > $temps[$i] && $temps[$i] > 0 && 1000 > $rpms[$i] && $rpms[$i] > 0 && 1000 > $press[$i] && $press[$i] > 0) {
				$this->graphData['graphs'][0]['points'][$i]['x'] = $samples * $channels[10]/60;
				$this->graphData['graphs'][1]['points'][$i]['x'] = $samples * $channels[10]/60;
				$this->graphData['graphs'][2]['points'][$i]['x'] = $samples * $channels[10]/60;
				$this->graphData['graphs'][0]['points'][$i]['y'] = $temps[$i];
				$this->graphData['graphs'][2]['points'][$i]['y2'] = $rpms[$i];
				$this->graphData['graphs'][1]['points'][$i]['y'] = $press[$i];
				if(max($temps[$i], $press[$i]) > $this->graphData['extrema']['maxima']['y']) {
					$this->graphData['extrema']['maxima']['y'] = max($temps[$i], $press[$i]);
				}
				if($rpms[$i] > $this->graphData['extrema']['maxima']['y2']) {
					$this->graphData['extrema']['maxima']['y2'] = $rpms[$i];
				}
			}
			$samples++;
		}
		for($j=0; $j<count($this->graphData['graphs']); $j++) {
			$this->graphData['graphs'][$j]['points'] = array_values($this->graphData['graphs'][$j]['points']);
		}
		
		// sets all the data
		$this->graphData['extrema']['maxima']['x'] = round($samples * $channels[10]/60, $this->config['precision']['x']);
		$this->graphData['extrema']['minima']['x'] = 0;
		$this->graphData['units']['x'] = "min";
		$this->graphData['units']['y'] = "°C / bar";
		$this->graphData['units']['y2'] = "rpm";
		$this->graphData['graphNames'][0] = "Temperature";
		$this->graphData['graphNames'][2] = "rpm";
		$this->graphData['graphNames'][1] = "Pressure";
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