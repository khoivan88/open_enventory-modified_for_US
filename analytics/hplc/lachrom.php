<?php
$GLOBALS["type_code"]="hplc";
$GLOBALS["device_driver"]="lachrom";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(".dad"),
		"optionalFiles" => array());
/*
 * Reads and converts a lachrom file to sketchable graphdata
 */

class lachrom extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.dad'][$this->fileNumber];	// puts all the data into a string
			$this->cursorPos=0;
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['x'] = 2;
			$this->config['peaks']['significanceLevel'] = 1.7;
			$this->config['peaks']['range']=25;
			
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
	 * converts a lachrom file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		// further preperation steps
		$nrOfPointsPerBlock = $this->readData($this->data, 's', 2, strpos($this->data, "NumberOfPoints")+19);
		$xyDataStart = strpos($this->data, "Boundary")+15;	// searches startposition of xydata
		$this->cursorPos = $xyDataStart;
		$retentionTime = $this->readData($this->data, 's', 2, strpos($this->data, "DelayTime")+31)/600; // gets the time
		// sets the extrema
		$this->graphData['extrema']['maxima']['x'] = round($retentionTime, 1);
		$this->graphData['extrema']['minima']['x'] = 0;
		// reads min and max wavelength
		$minWaveLength = $this->readData($this->data, 's', 2, strpos($this->data, "WV_RangeMin")+16);
		$this->graphData['minWaveLength'] = $minWaveLength;
		$maxWaveLength = $this->readData($this->data, 's', 2, strpos($this->data, "WV_RangeMax")+16);
		$this->graphData['maxWaveLength'] = $maxWaveLength;
		// calculates the difference between the wavelengths
		$waveDisplayDistance = round(($maxWaveLength-$minWaveLength-10)/3, -1);
		
		
		// gets xy data for 5 wavelengths
		$yMin=PHP_INT_MAX;
		$yMax=-PHP_INT_MAX;
		for($j=0; $j<4; $j++) {
			$pointsAsXY = array();
			$i=0;
			while($this->cursorPos<strlen($this->data)) {
				if($this->readData($this->data, 's', 2, $this->cursorPos)>0) {
					$pointsAsXY[$i]['x']=$i;
					$pointsAsXY[$i]['y']=$this->readData($this->data, 's', 2, $this->cursorPos);
					if($pointsAsXY[$i]['y']>$yMax) {
						$yMax=$pointsAsXY[$i]['y'];
					}
					if($pointsAsXY[$i]['y']<$yMin) {
						$yMin=$pointsAsXY[$i]['y'];
					}
					$i++;
				}
				$this->cursorPos += $nrOfPointsPerBlock*2;
			}
			$this->graphData['graphs'][$j]['points']=$pointsAsXY;
			$this->cursorPos = $xyDataStart+$waveDisplayDistance*2*($j+1);
			$this->graphData['graphNames'][$j] = ($minWaveLength+$j*$waveDisplayDistance)." nm";
		}
		// sets extrema and units
		$this->graphData['extrema']['maxima']['y']=100;
		$this->graphData['extrema']['minima']['y']=0;
		$this->graphData['units']['x'] = "min";
		$this->graphData['units']['y'] = "%";
		// normalisation
		for($j=0; $j<4; $j++) {
			for($i=0; $i<count($this->graphData['graphs'][$j]['points']); $i++) {
				$this->graphData['graphs'][$j]['points'][$i]['x'] = ($this->graphData['extrema']['maxima']['x']-$this->graphData['extrema']['minima']['x'])/count($this->graphData['graphs'][$j]['points'])*$i;
				$this->graphData['graphs'][$j]['points'][$i]['y'] = 100/($yMax-$yMin)*($this->graphData['graphs'][$j]['points'][$i]['y']-$yMin);
			}
		}
	}
	
	/*
	 * produces the interpretation string
	 */
	public function produceInterpretation() {
		// saves all the peaks and their intensity as interpretation
		$waveDisplayDistance = round(($this->graphData['maxWaveLength']-$this->graphData['minWaveLength']-10)/3, -1);
		for($j=0; $j<4; $j++) {
			$this->graphData['interpretation'] .= ($this->graphData['minWaveLength']+$j*$waveDisplayDistance)." nm (min (rel%)):";
			for($i=0; $i<count($this->graphData['graphs'][$j]['peaks']); $i++) {
				$this->graphData['interpretation'] .= " ".round($this->graphData['graphs'][$j]['peaks'][$i]['x'], 2)." (".round($this->graphData['graphs'][$j]['peaks'][$i]['y'])."),";
			}
			$this->graphData['interpretation'] = substr($this->graphData['interpretation'], 0, strlen($this->graphData['interpretation'])-1)."\n";
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
				if(substr($file_contents[array_keys($file_contents)[$i]][$j], 2, 8) == "D7000DAD") {
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>