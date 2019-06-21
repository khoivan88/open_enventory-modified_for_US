<?php
$GLOBALS["type_code"]="photolum";
$GLOBALS["device_driver"]="hamamatsu";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(".qua"),
		"optionalFiles" => array());
/*
 * Reads and converts a hamamatsu *.qua file to sketchable graphdata
 */

class hamamatsu extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.qua'][$this->fileNumber];	// puts all the data into a string
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['legendOffset'] = 250;
			$this->config['precision']['x'] = -1;
			$this->config['precision']['y'] = -2;
			$this->config['peaks']['computePeaks'] = false;
			
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
	 * converts a hamamatsu *.qua file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		// some preparations
		$this->data=str_replace(array("\r\n","\r"),"\n",$this->data);;
		$lines=explode("\n",$this->data);
		
		$minX=PHP_INT_MAX;
		$maxX=-PHP_INT_MAX;
		$minY=PHP_INT_MAX;
		$maxY=-PHP_INT_MAX;
		
		$empty_lines=0;
		$phase=0; // 0: header, 1: channel props, 2: graphs
		for ($a=0;$a<count($lines);$a++) {
			$line=$lines[$a];
			if ($line==="") {
				$empty_lines++;
				continue;
			}
			
			if ($empty_lines>0) {
				if ($empty_lines==1) {
					$phase=1;
				}
				elseif ($empty_lines==2) {
					$phase=2;
					
					// reads trace names
					$this->graphData['graphNames']=explode("\t",$line);
					array_shift($this->graphData['graphNames']);
				}
				$empty_lines=0;
				// skips 1st of block
				continue;
			}
			
			if ($phase==1) {
				// adds row
				$this->graphData['graphs'][]=array();
			}
			if ($phase==2) {
				$cells=explode("\t",$line);
				// skips values < 400nm
				if ($cells[0]<400) {
					continue;
				}
				if ($cells[0]<$minX) {
					$minX=$cells[0];
				}
				if ($cells[0]>$maxX) {
					$maxX=$cells[0];
				}
				
				// reads graph
				for ($b=1;$b<count($cells);$b++) {
					if ($cells[$b]==="") {
						continue;
					}
					$this->graphData['graphs'][$b-1]['points'][]=array("x" => $cells[0], "y" => $cells[$b]);
					
					if ($cells[$b]<$minY) {
						$minY=$cells[$b];
					}
					if ($cells[$b]>$maxY) {
						$maxY=$cells[$b];
					}
				}
			}
		}
		
		// sets the collected data to graphData
		$this->graphData['extrema']['maxima']['x'] = round($maxX, $this->config['precision']['x']);
		$this->graphData['extrema']['maxima']['y'] = round($maxY, $this->config['precision']['y']);
		$this->graphData['extrema']['minima']['x'] = round($minX, $this->config['precision']['x']);
		$this->graphData['extrema']['minima']['y'] = round($minY, $this->config['precision']['y']);
		$this->graphData['units']['x']="nm";
		$this->graphData['units']['y']="PL Intensity (arb. units)";
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