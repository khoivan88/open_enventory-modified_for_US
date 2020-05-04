<?php
$GLOBALS["type_code"]="nmr";
$GLOBALS["device_driver"]="jcampNMR";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(),
		"optionalFiles" => array(".dx", ".jdx"));
/*
 * Reads and converts a nmr jcamp file to sketchable graphdata
 */

class jcampNMR extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.jdx'][$this->fileNumber];	// puts all the data into a string
			if(strlen($this->data)==0) {
				$this->data = $file_content['.dx'][$this->fileNumber];
			}
			if(strlen($this->data) == 0) {
				$this->data = $file_content['.jcamp'][$this->fileNumber];
			}
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['y'] = 2;
			$this->config['precision']['x'] = 0;
			$this->config['legendOffset']=115;
			$this->config['peaks']['significanceLevel'] = 3;
			$this->config['peaks']['range']=300;
			$this->config['peaks']['maxPeaks']=7;
			
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
	 * converts a nmr jcamp file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		$lines=readJCamp($this->data); // uses lib_jcamp for this
		$a=0;
		$jcamp=parseJCampBlock($lines,$a);	// uses lib_jcamp for this
		set_time_limit(30);
		$yMax=-PHP_INT_MAX;
		$yMin=PHP_INT_MAX;
		$xMin=PHP_INT_MAX;
		$deltaX=0;
		$nrOfPoints=0;
		// process jcamp data object now
		if (is_array($jcamp['objects'])) foreach ($jcamp['objects'] as $block) {
			switch (strtolower(trim($block['datatype']['value']))) {
				case "link":
					// determine nr of points and x and y extrema
					$graph_data=$block['objects'][3]['xydata']['data'];
					$deltaX=strtolower(trim($block['objects'][3]['deltax']['value']));
					$nrOfPoints=strtolower(trim($block['objects'][3]['npoints']['value']));
					$yFactor=strtolower(trim($block['objects'][3]['yfactor']['value']))*100;
					$this->graphData['units']['x']=$block['objects'][3]['xunits']['value'];
					$xMin=trim($block['objects'][3]['firstx']['value']);
					$xMax=trim($block['objects'][3]['lastx']['value']);
					$sign=1;
					$start=$xMin;
					if($xMin>$xMax) {
						$temp=$xMax;
						$xMax=$xMin;
						$xMin=$temp;
						$sign=-1;
						$start=$xMin;
					}
					$this->graphData['extrema']['maxima']['x']=round($xMax, $this->config['precision']['x']-2);
					$this->graphData['extrema']['minima']['x']=round($xMin, $this->config['precision']['x']-2);
				
					$this->graphData['units']['y']="arb. units";
					$yMax=strtolower(trim($block['objects'][3]['maxy']['value']*100));
					$yMin=strtolower(trim($block['objects'][3]['miny']['value']*100));
					$this->graphData['extrema']['maxima']['y']=100;
					$this->graphData['extrema']['minima']['y']=0;
					break;
			}
		}
		
		// sets collected data to graphData and norms data
		$this->graphData['drawingStyle'] = 2; // set painting style to y axis right
		$point=array();
		for($i=0; $i<$nrOfPoints; $i++) {
			$point['x'] = $start+$i*$deltaX*$sign;
			$point['y'] = 100/($yMax-$yMin)*($graph_data[$i]*$yFactor);
			$this->graphData['graphs'][0]['points'][]=$point;
		}
	}
	
	/*
	 * produces the interpretation string
	 */
	public function produceInterpretation() {
		$this->graphData['interpretation']="d=";
		for($i=0; $i<count($this->graphData['graphs'][0]['peaks']); $i++) {
			if($i<count($this->graphData['graphs'][0]['peaks'])-1) {
				$this->graphData['interpretation'] .= round($this->graphData['graphs'][0]['peaks'][$i]['x'], 2).", ";
			}
			else {
				$this->graphData['interpretation'] .= round($this->graphData['graphs'][0]['peaks'][$i]['x'], 2)." ppm";
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
				$lines=readJCamp($file_contents[array_keys($file_contents)[$i]][$j]);
				if(strpos($lines[4], "LINK")!=FALSE) {
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>
