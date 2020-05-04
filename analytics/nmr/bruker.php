<?php
$GLOBALS["type_code"]="nmr";
$GLOBALS["device_driver"]="bruker";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
"requiredFiles" => array("/acqus.","/pdata/1/procs.","/pdata/1/1r."),
"optionalFiles" => array("/pdata/1/1i.","/pdata/1/peak.txt","/pdata/1/integrals.txt"));
/*
 * Reads and converts a bruker file to sketchable graphdata
 */

class bruker extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['/pdata/1/1r.'][$this->fileNumber];	// puts all the data into a string
			$this->report[0] = $file_content['/pdata/1/procs.'][$this->fileNumber];
			$this->report[1] = $file_content['/acqus.'][$this->fileNumber];
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['y'] = 2;
			$this->config['precision']['x'] = 2;
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
	 * converts a bruker file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		// parses procs
		$fqs_lines=explode("\n",fixLineEnd($this->report[0]));
		if (is_array($fqs_lines)) foreach ($fqs_lines as $fqs_line) {
		
			list($name,$value)=explode("=",substr(trim($fqs_line),2),2);
			$name=strtolower($name);
			$value=trim($value);
		}
		
		// reads acqus
		$aqs_lines=explode("\n",fixLineEnd($this->report[1]));
		if (is_array($aqs_lines)) foreach ($aqs_lines as $aqs_line) {
			list($name,$value)=explode("=",substr(trim($aqs_line),2),2);
			$name=strtolower($name);
			$value=trim($value);
			switch ($name) {
				case "\$nuc1":
					preg_match("/<(\d*)([A-Z][a-z]*)>/",$value,$temp);
					$nuc_mass=$temp[1];
					$nuc_sym=$temp[2];
					unset($temp);
					break;
				case "\$sw_h":
					$x_sweep=$value;
					break;
				case "\$o1":
					$x_offset=$value;
					break;
				case "\$sfo1":
					$freq_mhz=$value;
					break;
				case "\$solvent":
					$solvent=substr($value,1,-1);
					break;
				case "\$te":
					$temperature=$value;
					break;
				case "origin":
					$origin=$value;
					break;
				case "owner":
					$owner=$value;
					break;
				case "\$instrum":
					$instrum=substr($value,1,-1);
					break;
				case "\$date":
					$date=date("r",$value);
					break;
				case "\$td":
					$npoints=$value; 
					break;
			}
		}
		
		// determines x range and unit
		$hz_max=$x_offset+$x_sweep/2;
		$hz_min=$hz_max-$x_sweep;
		if ($freq_mhz>0) {
			$ppm_max=$hz_max/$freq_mhz;
			$ppm_min=$hz_min/$freq_mhz;
		}
		
		// sets x extrema
			if($ppm_max>20) {
			$this->graphData['extrema']['maxima']['x'] = round($ppm_max, -1);
			$this->graphData['extrema']['minima']['x'] = round($ppm_min, -1);
		}
		else {
			$this->graphData['extrema']['maxima']['x'] = floor($ppm_max);
			$this->graphData['extrema']['minima']['x'] = ceil($ppm_min);
		}
		
		// reads graph
		$blocks=str_split($this->data, 4);
		$blocks_count=count($blocks);
		
		$y_min=PHP_INT_MAX;
		$y_max=-PHP_INT_MAX;
		$pointsAsXY=array();
		for ($a=0; $a<$blocks_count; $a++) {
			$pointsAsXY[$a]['x']=$ppm_max-$a*($ppm_max-$ppm_min)/$blocks_count;
			$pointsAsXY[$a]['y']= $this->readData($blocks[$a], 'l', strlen($blocks[$a]), 0);
			if($pointsAsXY[$a]['y']<0) {
				$pointsAsXY[$a]['y']=0;
			}
			if($pointsAsXY[$a]['y']<$y_min) {
				$y_min=$pointsAsXY[$a]['y'];
			}
			if($pointsAsXY[$a]['y']>$y_max) {
				$y_max=$pointsAsXY[$a]['y'];
			}
		}
		
		// norms data
		$d=abs($y_max-$y_min);
		for($b=0; $b<count($pointsAsXY); $b++) {
			$pointsAsXY[$b]['y']=$pointsAsXY[$b]['y']/$d;
		}
		
		// sets all the graphdata
		$this->graphData['drawingStyle'] = 2; // set painting style to y axis right
		$this->graphData['extrema']['maxima']['y'] = round($y_max/$d, $this->config['precision']['y']);
		$this->graphData['extrema']['minima']['y'] = round($y_min/$d, $this->config['precision']['y']);
		$this->graphData['graphs'][0]['points']=$pointsAsXY;
		$this->graphData['units']['x']="ppm";
		$this->graphData['units']['y']="arb. units";
		$this->graphData['method']=$nuc_mass.$nuc_sym;
		$this->graphData['graphNames'][0]=$nuc_mass.$nuc_sym.s("nmr_spectrum")."\n".round($freq_mhz,2)." MHz"."\n".$solvent."\n".round($temperature,2)." K";
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
		$this->fileNumber = 0;
		return 2;
	}
}
?>
