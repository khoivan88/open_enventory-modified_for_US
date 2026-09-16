<?php
$GLOBALS['type_code']="gc-ms";
$GLOBALS['device_driver']="varian_sms";
$GLOBALS['analytics'][ $GLOBALS['type_code'] ][ $GLOBALS['device_driver'] ]=array(
		'requiredFiles' => array(".sms"),
		'optionalFiles' => array());

/*
 * Reads and converts a gc-ms/*.sms file to sketchable graphdata
 */

class varian_sms extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 42;	// important data starts at cursor position 42
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.sms'][$this->fileNumber];	// puts all the data into a string
			
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
		$MSDataOffset=$this->readData($this->data, 'l', 4, $this->cursorPos);

		$this->cursorPos = $MSDataOffset+12;
		$this->graphData['massFactor'] = $this->readData($this->data, 'v', 2, $this->cursorPos);
		$this->cursorPos = $MSDataOffset+30;
		$NoOfScans = $this->readData($this->data, 'l', 4, $this->cursorPos); // scans accomplished
		$this->cursorPos = $MSDataOffset+38;
		$this->graphData['extrema']['maxima']['y'] = $this->readData($this->data, 'l', 4, $this->cursorPos); // y max value
		$this->cursorPos = $MSDataOffset+50;
		$HeaderSize = $this->readData($this->data, 'l', 4, $this->cursorPos); // the size of the header
		$this->cursorPos = $MSDataOffset+58;
		$this->graphData['scanDataIndex'] = $MSDataOffset+$this->readData($this->data, 'l', 4, $this->cursorPos);
		
		for ($i=0;$i<20;$i++) {
			$storage=substr($this->data,56+$i*50,50);
			if (startswith($storage,"SamplePrep.")) {
				$samplePrepOffset=$this->readData($storage, 'l', 4, 32);	// offset of the samplePrep
				break;
			}
		}
		
		// gets the method name
		$this->cursor=$samplePrepOffset+415;
		$method=fixStr00(substr($this->data,$this->cursor,128));
		$this->graphData['method']=cutFilename($method,"\\");
		
		// gets the x and y values
		$this->cursorPos=$MSDataOffset+$HeaderSize;
		$pointsAsXY=array();
		$first=true;
		$last=true;
		for ($i=0;$i<$NoOfScans;$i++) {
			$pointsAsXY[$i]=array();
			$this->cursorPos += 4;
			$pointsAsXY[$i]['scanOffset']=$this->readData($this->data, 'l', 4, $this->cursorPos);
			$this->cursorPos += 8;
			$pointsAsXY[$i]['x']=$this->readData($this->data, 'd', 8, $this->cursorPos);
			$this->cursorPos += 10;
			$pointsAsXY[$i]['y']=$this->readData($this->data, 'l', 4, $this->cursorPos);
			$this->cursorPos += 4;
			$pointsAsXY[$i]['highMass']=$this->readData($this->data, 'v', 2, $this->cursorPos);
			$this->cursorPos += 2;
			$pointsAsXY[$i]['lowMass']=$this->readData($this->data, 'v', 2, $this->cursorPos);
			$this->cursorPos += 11;
			if($pointsAsXY[$i]['y']>1 && $first) {
				$this->graphData['extrema']['minima']['x']=floor($pointsAsXY[$i]['x']);
				$first=false;
			}
			if($pointsAsXY[$i]['y']<1 && $first==false && $last) {
				$this->graphData['extrema']['maxima']['x']=ceil($pointsAsXY[$i]['x']);
				$last=false;
			}
		}
		
		// puts the data into the graphData array
		$this->graphData['graphs'][0]['points'] = $pointsAsXY;
		$this->graphData['units']['x'] = "min";
		$this->graphData['units']['y'] = "m/z";
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
			$ms[$i]['graphs'][0]['points'] = $this->getMS($this->data, $this->graphData['graphs'][0]['peaks'][$i], $this->graphData); // gets the ms of this peak
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
			$ms[$i]['drawingStyle']=1; // sets drawingStyle to candlesticks
			
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

		$this->graphData['ms']=$ms;	// sets sms additional field ms
		$this->graphData['interpretation']=$interpretationString;	// sets interpretation
	}
	
	/*
	 * additional function for varian_sms converter to get MS-Data
	 * returns MS-Data
	 */
	private function getMS($data, $peak, $graphData) {
		$ms=array();
		$cursorPos=$graphData['scanDataIndex']+$peak['scanOffset'];
		$noOfPeaks=$peak['highMass']-$peak['lowMass'];
		$cMass=0;
		
		// the following for-clause reads the x and y values of the ms out of the raw data
		for($i=0; $i<$noOfPeaks; $i++) {
			$storage=ord(substr($data,$cursorPos,1));
			if ($storage==0) {
				break;
			}
			elseif ($storage<64) {
				$cMass+=$storage;
			}
			else {
				$cursorPos++;
				$storage2=ord(substr($data,$cursorPos,1));
				$cMass+=($storage-64)*256+$storage2;
			}
		
			$cursorPos++;
			$storage=ord(substr($data,$cursorPos,1));
			if($storage<64) {
				$cIntensity=$storage;
			}
			elseif ($storage<128) {
				$cursorPos++;
				$storage2=ord(substr($data,$cursorPos,1));
				$cIntensity=($storage-64)*256+$storage2;
			}
			elseif ($storage<192){
				$cursorPos++;
				$storage2=ord(substr($data,$cursorPos,1));
				$cursorPos++;
				$storage3=ord(substr($data,$cursorPos,1));
				$cIntensity=($storage-128)*65536+$storage2*256+$storage3;
			}
			else {
				$cursorPos++;
				$storage2=ord(substr($data,$cursorPos,1));
				$cursorPos++;
				$storage3=ord(substr($data,$cursorPos,1));
				$cursorPos++;
				$storage4=ord(substr($data,$cursorPos,1));
				$cIntensity=($storage-192)*16777216+$storage2*65536+$storage3*256+$storage4;
			}
			// sets x and y value of the point
			$ms[$i]['x']=$cMass/$graphData['massFactor'];
			$ms[$i]['y']=$cIntensity;
			$cursorPos++;
		}
		return $ms;	
	}
	
	/*
	 * checks if the signature of the file fits the signature of the converter
	 * it returns 0, if it fits, else 1. if there is none, return 2
	 */
	public function verifyFileSignature($file_contents) {
		$isCorrectConverter=0;
		for($i=0; $i<count($file_contents); $i++) {
			for($j=0; $j<count($file_contents[array_keys($file_contents)[$i]]); $j++) {
			if(substr($file_contents[array_keys($file_contents)[$i]][$j], 0, 1)=="\x1c") {
				$isCorrectConverter = 1;
				$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>