<?php
$GLOBALS['type_code']="gc-ms";
$GLOBALS['device_driver']="chemstation";
$GLOBALS['analytics'][ $GLOBALS['type_code'] ][ $GLOBALS['device_driver'] ]=array(
		'requiredFiles' => array(".ms"),
		'optionalFiles' => array("acqmeth.txt"));

/*
 * Reads and converts a gc-ms/*.ms file to sketchable graphdata
 */

class chemstation extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.ms'][$this->fileNumber];	// puts all the data into a string
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['x'] = 2;
			$this->config['precision']['y'] = -2;
			$this->config['peaks']['significanceLevel'] = 1.4;
			$this->config['axisOffset']['y']=70;
			$this->config['peaks']['range']=10;
			
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
	 * does the converting
	 */
	public function convertFileToGraphData() {
		$this->graphData['method'] = fixStr00(substr($this->data, 229, 19)); // gets the methodname
		
		$decode_long="N"; // big
		$long_two=$this->readData($this->data, $decode_long, 4, 248);
		if ($long_two!=2) {
			$decode_long="V"; // little
			$long_two=$this->readData($this->data, $decode_long, 4, 248);
		}
		$decode_short=strtolower($decode_long);
		
		// Diretory offset
		$dir_offset = ($this->readData($this->data, $decode_long, 4, 260)-1)*2; // unit is words
		$scanDataIndex = ($this->readData($this->data, $decode_long, 4, 264)-1)*2; // unit is words
		$NoOfScans=$this->readData($this->data, $decode_long, 4, 278);
		
		// primary chromatogram
		$this->graphData['extrema']['minima']['x'] = round($this->readData($this->data, $decode_long, 4, 282)/60000, 0);
		$this->graphData['extrema']['maxima']['x'] = round($this->readData($this->data, $decode_long, 4, 286)/60000, 0);
		$this->graphData['units']['x'] = "min";
		$this->graphData['units']['y'] = "m/z";
		
		// reads directory to create chromatogram
		$scans=array();
		$this_data_offset=$dir_offset;
		$yMax = -PHP_INT_MAX;
		$yMin = 0;
		for ($idx=0;$idx<$NoOfScans;$idx++) {
			$scan=array();
			$point['offset']=($this->readData($this->data, $decode_long, 4, $this_data_offset)-1)*2; // unit is words
			$point['RetentionTime']=$this->readData($this->data, $decode_long, 4, $this_data_offset+4)/60000; // min
			$point['x'] = round($this->graphData['extrema']['minima']['x']+$idx*($this->graphData['extrema']['maxima']['x']-$this->graphData['extrema']['minima']['x'])/$NoOfScans, 4);
			$point['y'] = $this->readData($this->data, $decode_long, 4, $this_data_offset+8);
			$this->graphData['graphs'][0]['points'][] = $point;
			if($point['y'] > $yMax) {
				$yMax = $point['y'];
			}
			$scans[]=$scan;
			$this_data_offset+=12;
		}
		// sets y extrema
		$this->graphData['extrema']['minima']['y'] = round($yMin, $this->config['precision']['y']);
		$this->graphData['extrema']['maxima']['y'] = round($yMax, $this->config['precision']['y']);
	}
	
	/*
	 * additional function for chemstation converter to get MS-Data
	 * returns MS-Data
	 */
	private function getMS($data, $peak) {
		$decode_long="N"; // big
		$long_two=$this->readData($this->data, $decode_long, 4, 248);
		if ($long_two!=2) {
			$decode_long="V"; // little
			$long_two=$this->readData($this->data, $decode_long, 4, 248);
		}
		$decode_short=strtolower($decode_long);
		
		$ms=array();
		$filePos=$peak["offset"];
		
		// gets some data to read and calculates xydata
		$NoPeaks=$this->readData($this->data, $decode_short, 2, $filePos+12);
		$scale=$this->readData(substr($this->data,$filePos+16,2) & "\xc0\0", $decode_short, 4, 0) >> 14; // \0\x03
		$mantissa=$this->readData(substr($this->data,$filePos+16,2) & "\x3f\xff", $decode_short, 4, 0); // \xff\xfc
		$maxIntensity=$mantissa<<(3*$scale);
		
		// gets xydata
		$filePos+=18;
		for($i=0;$i<$NoPeaks;$i++) {
			$cMass=$this->readData($this->data, $decode_short, 2, $filePos)/20;
			$scale=$this->readData(substr($this->data,$filePos+2,2) & "\xc0\0", $decode_short, 4, 0) >> 14; // \0\x03
			$mantissa=$this->readData(substr($this->data,$filePos+2,2) & "\x3f\xff", $decode_short, 4, 0); // \xff\xfc
			$cIntensity=$mantissa<<(3*$scale);
			if ($cIntensity>$maxIntensity) { // skip
				continue;
			}
			$ms[$i]['x'] = $cMass;
			$ms[$i]['y'] = $cIntensity;
			$filePos+=4;
		}
		return $ms;
	}
	
	/*
	 * returns the interpretation string
	 */
	public function produceInterpretation() {
		$interpretationString="Retention time: m/z (%)\n";
		$ms=array();
		
		// gets through all peaks, gets the ms and converts it to sketchable graphData. Finally, it produces the interpretation
		for($i=0; $i<count($this->graphData['graphs'][0]['peaks']); $i++) {
			$RetentionTime=round($this->graphData['graphs'][0]['peaks'][$i]['x'], 2)." min";
			$interpretationString=$interpretationString.$RetentionTime.": ";	// adds the time of the peak to interpretation
			$ms[$i]['graphs'][0]['points'] = $this->getMS($this->data, $this->graphData['graphs'][0]['peaks'][$i]); // gets the ms of this peak
			$maxY=-PHP_INT_MAX;
			$minY=PHP_INT_MAX;
			$maxX=-PHP_INT_MAX;
			// gets min and max values of the ms
			for($a=0; $a<count($ms[$i]['graphs'][0]['points']); $a++) {
				if($maxY<$ms[$i]['graphs'][0]['points'][$a]['y']) {
					$maxY=$ms[$i]['graphs'][0]['points'][$a]['y'];
				}
				if($maxX<$ms[$i]['graphs'][0]['points'][$a]['x']) {
					$maxX=$ms[$i]['graphs'][0]['points'][$a]['x'];
				}
				if($minX>$ms[$i]['graphs'][0]['points'][$a]['x']) {
					$minX=$ms[$i]['graphs'][0]['points'][$a]['x'];
				}
			}
			// converts the ms to sketchable graphData
			$ms[$i]['graphs'][0]['peaks']=array();
			$ms[$i]['graphNames'][0]="Graph";
			$ms[$i]['extrema']['maxima']['x']=$maxX;
			$ms[$i]['extrema']['maxima']['y']=100;
			$ms[$i]['extrema']['minima']['x']=$minX;
			for($a=0; $a<count($ms[$i]['graphs'][0]['points']); $a++) {
				$ms[$i]['graphs'][0]['points'][$a]['y']=round($ms[$i]['graphs'][0]['points'][$a]['y']/$maxY*100, 0);
			}
			$ms[$i]['units']['x']="m/z";
			$ms[$i]['units']['y']="% at ". $RetentionTime;
			$tickDistancesAndTickScales = $this->getBestTickDistance($ms[$i], $this->config);
			$ms[$i]['tickDistance'] = $tickDistancesAndTickScales['tickDistance'];
			$ms[$i]['tickScale'] = $tickDistancesAndTickScales['tickScale'];
			$ms[$i]['drawingStyle']=1; // sets the drawingStyle to candlesticks
			
			// produces csvDataString
			$this->graphData['csvDataString'][$i+1] = $this->produceCsvDataString($ms[$i]);
			
			// produces the interpretation
			if($maxY>0) {
				$this->config['peaks']['maxPeaks'] = 7;
				$ms[$i]=$this->getPeaks($ms[$i], $this->config); // sets these as peaks
				
				// adds the interpretation to the string
				for($a=0; $a<count($ms[$i]['graphs'][0]['peaks']); $a++) {
					if($a!=0) {
						$interpretationString=$interpretationString.", ".round($ms[$i]['graphs'][0]['peaks'][$a]['x'], 0)." (".$ms[$i]['graphs'][0]['peaks'][$a]['y'].")";
					}
					else {
						$interpretationString=$interpretationString.round($ms[$i]['graphs'][0]['peaks'][$a]['x'], 0)." (".$ms[$i]['graphs'][0]['peaks'][$a]['y'].")";
					}
				}
				$interpretationString=$interpretationString."\n";
			}
			$ms[$i] = $this->convertPointsToPixelCoordinates($ms[$i], $this->config);
		}

		$this->graphData['ms']=$ms;	// sets chemstation additional field ms
		$this->graphData['interpretation']=$interpretationString;	// sets interpretation
	}
	
	/*
	 * checks if the signature of the file fits the signature of the converter
	 * it returns 0, if it fits, else 1. if there is none, return 2
	 */
	public function verifyFileSignature($file_contents) {
		$isCorrectConverter=0;
		for($i=0; $i<count($file_contents); $i++) {
			for($j=0; $j<count($file_contents[array_keys($file_contents)[$i]]); $j++) {
				if(substr($file_contents[array_keys($file_contents)[$i]][$j], 0, 4)=="\x01\x32\0\0") {
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>