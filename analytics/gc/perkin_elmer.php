<?php
$GLOBALS["type_code"]="gc";
$GLOBALS["device_driver"]="perkin_elmer";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
"requiredFiles" => array(".raw",),
"optionalFiles" => array(".tx0",".tx1",));

/*
 * Reads and converts a gc/*.raw file to sketchable graphdata
 */

class perkin_elmer extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.raw'][$this->fileNumber];	// puts all the data into a string
			// gets the report data into a string
			$this->report = $file_content['.tx0'][$this->fileNumber];
			if(empty($this->report)) {
				$this->report = $file_content['.tx1'][$this->fileNumber];
			}
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['x'] = 2;
			$this->config['peaks']['significanceLevel'] = 1.1;
			$this->config['peaks']['range']=100;
			$this->config['peaks']['relativePeak'] = 0.2;
			
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
		// parses report and saves properties
		if($this->report!="") {
			$this->config['peaks']['computePeaks'] = false; // only compute peaks if there is no report
			
			$rep_lines=explode("\n",$this->report);
			$stage=0;
			$timeIndex=$areaPercIndex=$heightIndex=$commentIndex=-1;
			for ($a=0; $a < count($rep_lines);$a++) {
				if (startswith($rep_lines[$a],"------")) {
					$stage=1;
		
					// column assignment
					$prevline=parseCSV($rep_lines[$a-1]);
					$preline2=parseCSV($rep_lines[$a-2]);
		
					for ($b=0;$b < count($preline2);$b++) {
						switch ($preline2[$b]) {
							case "Time":
								$timeIndex=$b;
								break;
							case "Height":
								$heightIndex=$b;
								break;
							case "Area":
								if ($prevline[$b]=="[%]") {
									$areaPercIndex=$b;
								}
								break;
							case "Component":
								$commentIndex=$b;
								break;
						}
					}
		
					if ($timeIndex<0 || $areaPercIndex<0) {
						break; // has no sense
					}
		
					continue;
				}
				if ($stage==1) {
					$dataArray=parseCSV($rep_lines[$a]);
					if ($dataArray[0]==0) {
						break;
					}
					$this->graphData['graphs'][0]['peaks'][] = array('x' => fixNull($dataArray[$timeIndex]), 'y' => 0);
					$this->graphData['analytical_data_properties']['peaks'][]=array(
							"time" => fixNull($dataArray[$timeIndex]),
							"rel_area" => fixNull($dataArray[$areaPercIndex]),
							"height" => fixNull($dataArray[$heightIndex]),
							"comment" => $dataArray[$commentIndex],
					);
				}
			}
		}
		
		$block_count=0;
		
		$dataLength=strlen($this->data);
		$fields=array();
		
		// reads the header
		$had_zero=0;
		for ($a=16;$a<$dataLength;$a++) {
			if ($this->data{$a}=="\0") { // walks ramps
				$had_zero++;
				continue;
			}
			if ($had_zero<1) {
				continue;
			}
		
			// reads field
			$had_zero=0;
			$fieldLength=ord($this->data{$a});
			if ($fieldLength==63 && substr($this->data,$a+1,47)=="\xf0\0\0\0\0\0\0\x3f\xf0\0\0\0\0\0\0\x3f\xf0\0\0\0\0\0\0\x3f\xf0\0\0\0\0\0\0\x3f\xf0\0\0\0\0\0\0\x3f\xf0\0\0\0\0\0\0") {
				$a+=146;
				if (!isset($block_count)) {
					$block_count=$this->readData($this->data, 'n', 2, $a);
				}
				$a+=3;
				break;
			}
			if ($fieldLength==73) { // some bin fields, maybe date/time
				$fieldLength=11;
			}
		
			$text=substr($this->data,$a+1,$fieldLength);
			if (strpos($text,"\0")===FALSE) {
				$fields[]=$text;
				$a+=$fieldLength;
					
				if ($built_in) { // gets field after built-in
					// find 3F F0 00 00  00 00 00 00  00 00 00 00  00 00
					$before_block_count="\x3f\xf0\0\0\0\0\0\0\0\0\0\0\0\0";
					$a=strpos($this->data,$before_block_count,$a);
					$a+=strlen($before_block_count);
					$block_count=$this->readData($this->data, 'n', 2, $a);
					$built_in=false;
				}
				// gets the maximum x value
				elseif ($text=="BUILT-IN") {
					$a+=13;
					$this->graphData['extrema']['maxima']['x']=$this->readData(strrev(substr($this->data,$a,8)), 'd', 8, 0);
					$built_in=true;
				}
			}
		}
		$this->cursorPos=$a;
		$this->graphData['method']=cutFilename($fields[14],"\\"); // gets the method name
		
		// reads points of the graph
		$binaryGraphData=substr($this->data,$this->cursorPos,$block_count*4);
		$binaryGraphData=str_split($binaryGraphData,4);
		$pointsAsXY=array();
		$yMax=-PHP_INT_MAX;
		$yMin=0;
		$peakNr=0;
		for ($b=0;$b<count($binaryGraphData); $b++) {
			$pointsAsXY[$b]['x']=$this->graphData['extrema']['maxima']['x']/count($binaryGraphData)*$b; // norm it to the x scale
			$pointsAsXY[$b]['y']=$this->readData($binaryGraphData[$b], 'N', 4, 0);
			if($pointsAsXY[$b]['y'] < 0) {
				$pointsAsXY[$b]['y'] = 0;
			}
			if(round($pointsAsXY[$b]['x'], 3)-0.001==$this->graphData['graphs'][0]['peaks'][$peakNr]['x'] || round($pointsAsXY[$b]['x'], 3)==$this->graphData['graphs'][0]['peaks'][$peakNr]['x'] || round($pointsAsXY[$b]['x'], 3)+0.001==$this->graphData['graphs'][0]['peaks'][$peakNr]['x']) {
				$this->graphData['graphs'][0]['peaks'][$peakNr]['y'] = $pointsAsXY[$b]['y'];
				$peakNr++;
			}
			
			// determines maximum and minimum y value
			if($yMax<$pointsAsXY[$b]['y']) {
				$yMax=$pointsAsXY[$b]['y'];
			}
		}
		// normalisation
		for ($b=0;$b<count($pointsAsXY); $b++) {
			$pointsAsXY[$b]['y']=100/($yMax-$yMin)*($pointsAsXY[$b]['y']-$yMin); // get y value in percent
		}
		for ($b=0;$b<count($this->graphData['graphs'][0]['peaks']); $b++) {
			$this->graphData['graphs'][0]['peaks'][$b]['y']=100/($yMax-$yMin)*($this->graphData['graphs'][0]['peaks'][$b]['y']-$yMin); // get y value in percent
		}
		// sets the graphData
		$this->graphData['extrema']['maxima']['y'] = 100;
		$this->graphData['extrema']['minima']['y'] = 0;
		$this->graphData['graphs'][0]['points']=$pointsAsXY;
		$this->graphData['units']['x']="min";
		$this->graphData['units']['y']="%";
	}
	
	/*
	 * checks if the signature of the file fits the signature of the converter
	 * it returns 0, if it fits, else 1. if there is none, return 2
	 */
	public function verifyFileSignature($file_contents) {
		$isCorrectConverter=0;
		for($i=0; $i<count($file_contents); $i++) {
			for($j=0; $j<count($file_contents[array_keys($file_contents)[$i]]); $j++) {
				if(substr($file_contents[array_keys($file_contents)[$i]][$j], 0, 4)=="PENX" && $file_contents[array_keys($file_contents)[$i]][$j]{7}=="\x01") {
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>