<?php
$GLOBALS["type_code"]="gc";
$GLOBALS["device_driver"]="agilent";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
"requiredFiles" => array("page1.gif",),
"optionalFiles" => array("report.txt",));

/*
 * Reads and converts an agilent file into sketchable graphdata
 */

class agilent extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 1032;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['page1.gif'][$this->fileNumber];	// puts all the data into a string
			// gets the report data into a string
			$this->report = $file_content['report.txt'][$this->fileNumber];
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			/* no configuration variables set */
			
			// does the converting
			$this->convertFileToGraphData();
			
			// following part is useless and can be comment out if there is no graphData in xy format
			
			/*
			 // gets the peaks
			 $this->graphData = $this->getPeaks($this->graphData, $this->config);
			
			 // produces interpretation
			 $this->produceInterpretation();
			
			 // gets the best considered fitting tickScales and its proper tickDistances
			 $tickDistancesAndTickScales = $this->getBestTickDistance($this->graphData, $this->config);
			 $this->graphData['tickDistance'] = $tickDistancesAndTickScales['tickDistance'];
			 $this->graphData['tickScale'] = $tickDistancesAndTickScales['tickScale'];
			
			 // converts to the specific coordinates of the various pixels
			 $this->graphData = $this->convertPointsToPixelCoordinates($this->graphData, $this->config);
			 */
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
			for ($a=0;$a<count($lines);$a++) {
				if (startswith($lines[$a],'Method')) {
					$method_name=$lines[$a];
					$start=strrpos($method_name,'\\')+1;
					$this->graphData['method']=substr($method_name,$start);
				}
				elseif (startswith($lines[$a],'Totals')) {
					$resultsTableActive=false;
				}
				elseif ($resultsTableActive) {
					$peakData=colSplit($lines[$a],array(4,	8,	5,	8,	11,	11,	9));
					//							num	time	type	w	area	h	a%
					$this->graphData['analytical_data_properties']['peaks'][]=array('time' => $peakData[1], 'width' => $peakData[3], 'rel_area' => $peakData[6], 'comment' => '');
				}
				elseif (startswith($lines[$a],'----')) {
					$resultsTableActive=true;
				}
			}
			// make ASCII table work
			$this->graphData['interpretation']="<pre>".$this->graphData['interpretation']."</pre>";
		}
		
		// saves the spectrum image if there is one available
		if(!empty($this->data)) {
			$tempImage = imagecreatefromstring($this->data);
			$newImage = imagecreatetruecolor($this->config['dimensions']['width'], $this->config['dimensions']['height']);
			imagecopyresampled($newImage, $tempImage, 0, 0, 26, 128, $this->config['dimensions']['width'], $this->config['dimensions']['height'], 638, 395);
			ob_start();
			ImagePNG($newImage);
			$this->graphData['image'] = ob_get_clean();
			$this->graphData['imageMime'] = 'image/png';
		}
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