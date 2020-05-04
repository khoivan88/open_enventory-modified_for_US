<?php
$GLOBALS["type_code"]="gc";
$GLOBALS["device_driver"]="shimadzu_txt";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
"requiredFiles" => array(".txt"));

/*
 * Reads and converts a Shimdazu TXT file into sketchable graphdata
 */

class shimadzu_txt extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			// gets the report data into a string
			$this->report = $file_content['.txt'][$this->fileNumber];
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['x'] = 2;
			$this->config['peaks']['significanceLevel'] = 1.1;
			$this->config['peaks']['range']=100;
			$this->config['peaks']['relativePeak'] = 0.2;
			
			// does the converting
			$this->convertFileToGraphData();
			
			// following part is useless and can be comment out if there is no graphData in xy format
			
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
			 //var_dump($this);die("X");
		}
	}
	
	/*
	 * does the converting
	 */
	public function convertFileToGraphData() {
		
		// converts the report and saves it as the interpretation
		if (!empty($this->report)) {
			if (startswith($this->report,"\xff\xfe")) { // UTF16
				$this->graphData['interpretation']=fixLineEnd(iconv('UTF-16LE','UTF-8',$this->report));
			}
			else {
				$this->graphData['interpretation']=fixLineEnd(utf8_encode($this->report));
			}
			
			// makes table of ret_times and integrals
			$lines=explode("\n",$this->graphData['interpretation']);
			$this->graphData['analytical_data_properties']=array();
			$this->graphData['analytical_data_properties']['peaks']=array();
			$phase=0;
			$total_area=0.0;
			$report="";
			$maxY=0;
			$solventCutoff=4.5;
			for ($a=0;$a<count($lines);$a++) {
				$line=trim($lines[$a]);
				if ($line=="") {
					$phase=0;
				}
				elseif ($phase<2) {
					$report.=$line."\n";
				}
				
				if ($phase>0 && $phase < 3) {
					$peakData=explode("\t",$line);
					if ($phase==1) {
						$valX=floatval(getNumber($peakData[1]));
						if ($valX>$solventCutoff) {
							$area=floatval(getNumber($peakData[4]));
							$total_area+=$area;
							$this->graphData['graphs'][0]['peaks'][] = array('x' => $valX, 'y' => 0);
							$this->graphData['analytical_data_properties']['peaks'][]=array('time' => $valX, 'height' => floatval(getNumber($peakData[5])), 'rel_area' => $area, 'comment' => '');
						}
					}
					elseif ($phase==2) {
						// plot graph
						$valX=floatval(getNumber($peakData[0]));
						$valY=floatval(getNumber($peakData[1]));
						if ($valX>$solventCutoff && $valY>$maxY) { // ignore solvent peak
							$maxY=$valY;
						}
						$maxX=$valX;
						$this->graphData['graphs'][0]['points'][] = array('x' => $valX,  'y' => $valY);
					}
				}
				elseif (startswith($line,'[Chromatogram')) {
					$phase=3;
				}
				elseif (startswith($line,'Method File')) {
					$method_name=$line;
					$start=strrpos($method_name,'\\')+1;
					$this->graphData['method']=substr($method_name,$start);
				}
				elseif (startswith($line,'Peak#')) { // Peak#	R.Time	I.Time	F.Time	Area	Height	A/H	Conc.	Mark	ID#	Name	k'	Plate #	Plate Ht.	Tailing	Resolution	Sep.Factor	Area Ratio	Height Ratio	Conc. %	Norm Conc.
					$phase=1;
				}
				elseif (startswith($line,'R.Time')) { // R.Time (min)	Intensity
					if ($phase==3) {
						$phase=2;
					}
					else {
						$phase=4; // leave out of report
					}
				}
			}
			// make ASCII table work
			$this->graphData['interpretation']="<pre>".$report."</pre>";
		}
		
		// normalize
		if ($total_area > 0.0) {
			for ($a=0;$a<count($this->graphData['analytical_data_properties']['peaks']);$a++) {
				$this->graphData['analytical_data_properties']['peaks'][$a]['rel_area']*=100.0/$total_area;
			}
		}
		
		// sets the graphData
		$this->graphData['extrema']['maxima']['y'] = $maxY;
		$this->graphData['extrema']['minima']['y'] = 0;
		$this->graphData['extrema']['maxima']['x'] = $maxX;
		$this->graphData['extrema']['minima']['x'] = 0;
		$this->graphData['units']['x']="min";
		$this->graphData['units']['y']="%";
		
		//var_dump($this->graphData['analytical_data_properties']['peaks']);die("X");
	}
	
	/*
	 * checks if the signature of the file fits the signature of the converter
	 * it returns 0, if it fits, else 1. if there is none, return 2
	 */
	public function verifyFileSignature($file_contents) {
		$isCorrectConverter=0;
		for($i=0; $i<count($file_contents); $i++) {
			for($j=0; $j<count($file_contents[array_keys($file_contents)[$i]]); $j++) {
				if(substr($file_contents[array_keys($file_contents)[$i]][$j], 0, 8)=="[Header]") {
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>