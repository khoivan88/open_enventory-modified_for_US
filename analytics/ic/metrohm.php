<?php
$GLOBALS["type_code"]="ic";
$GLOBALS["device_driver"]="metrohm";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(".txt"),
		"optionalFiles" => array(".xml"));
/*
 * Reads and converts a *.msp file to sketchable graphdata
 */

class metrohm extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.txt'][$this->fileNumber];	// puts all the data into a string
			$this->xmlData = $file_content['.xml'][$this->fileNumber];	// puts XML data into a string, if present
			
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
		$peak_sep=";";
		$yMax=-PHP_INT_MAX;
		$xMax=-PHP_INT_MAX;
		$yMin=PHP_INT_MAX;
		$xMin=PHP_INT_MAX;
		if (is_array($lines)) foreach ($lines as $line_no => $line) {
			if (strpos($line,$peak_sep)===FALSE) {
				continue;
			}
		
			switch ($stage) {
				case 0:
					list($this->graphData['units']['x'], $this->graphData['units']['y'])=explode($peak_sep,$line,2);
					$stage = 1;
					break;
				case 1:
					$cells=explode($peak_sep,$line);
					$cells[0]+=0.0;
					$cells[1]+=0.0;
					$this->graphData['graphs'][0]['points'][] = array('x' => $cells[0], 'y' => $cells[1]);
					if($cells[0]>$xMax) {
						$xMax = $cells[0];$cells[0];
					}
					if($cells[0]<$xMin) {
						$xMin = $cells[0];
					}
					if($cells[1]<$yMin) {
						$yMin = $cells[1];
					}
					if($cells[1]>$yMax) {
						$yMax = $cells[1];
					}
					break;
			}
		}
		
		// sets all the graphData
		$this->graphData['extrema']['maxima']['y'] = round($yMax, -1);
		$this->graphData['extrema']['minima']['y'] = round($yMin, -1);
		$this->graphData['extrema']['maxima']['x'] = round($xMax, -1);
		$this->graphData['extrema']['minima']['x'] = round($xMin, -1);
	}
	
	/*
	 * produces the interpretation string
	 */
	public function produceInterpretation() {
		if ($this->xmlData) {
			$xml = new SimpleXMLElement($this->xmlData);
			list($this->graphData['method'])=$xml->xpath('/DeterminationReport/Method/Identification/methodName/@val');
			list($sample)=$xml->xpath('/DeterminationReport/Sample/SmplData/ident/data/vr/@val');
			
			$this->graphData['analytical_data_properties']['peaks']=array();
			$interpretationString = "<pre>".ifnotempty("Method: ", $this->graphData['method'], "\n").ifnotempty("Sample: ", $sample, "\n")
				."\nPeak Ion     RetTime  Width     Area      Height     Area  \n"
				."  #           [min]   [min]                            %\n"
				."----|-------|-------|-------|----------|----------|--------|\n";
			$analysesEntries = $xml->xpath('/DeterminationReport/Analyses/Analysis/Components/Component');
			if (is_array($analysesEntries)) foreach ($analysesEntries as $idx => $analysesEntry) {
				$data = array();
				$dataEntries = $analysesEntry->xpath('StandardResults/data');
				if (is_array($dataEntries)) foreach ($dataEntries as $dataEntry) {
					list($key) = $dataEntry->xpath('vn/@val');
					list($data[$key.""])=$dataEntry->xpath('vr/@val');
				}
				$this->graphData['analytical_data_properties']['peaks'][]=array('time' => $data["RET"]+0.0, 'width' => $data["WIDTH"]+0.0, 'rel_area' => $data["AREA%"]+0.0, 'comment' => $data["COMP"]);
				$interpretationString.=str_pad($idx+1, 4, " ", STR_PAD_LEFT)." ".str_pad($data["COMP"], 7)." ".str_pad($data["RET"], 7, " ", STR_PAD_LEFT)." ".str_pad($data["WIDTH"], 7, " ", STR_PAD_LEFT)." ".str_pad($data["AREA"], 10, " ", STR_PAD_LEFT)." ".str_pad($data["HGT"], 10, " ", STR_PAD_LEFT)." ".str_pad($data["AREA%"], 8, " ", STR_PAD_LEFT)."\n";
			}
			$messageEntries = $xml->xpath('/DeterminationReport/Analyses/Analysis/Components/Component/msgText/@val');
			if (count($messageEntries)) {
				$interpretationString.="\n---------\nWarnings:\n".join($messageEntries,"\n");
			}
			$this->graphData['interpretation']=$interpretationString."</pre>";	// set interpretation
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
				if(startswith($file_contents[array_keys($file_contents)[$i]][$j],"20")) { // date starting with 20 in first line
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>
