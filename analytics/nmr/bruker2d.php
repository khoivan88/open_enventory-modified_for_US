<?php
$GLOBALS["type_code"]="nmr";
$GLOBALS["device_driver"]="bruker2d";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
"requiredFiles" => array("/pdata/1/2rr.","/acqus.","/acqu2s.","/pdata/1/procs.","/pdata/1/proc2s.",),
"optionalFiles" => array("/pdata/1/peak.txt","/pdata/1/integrals.txt"));
/*
 * Reads and converts a bruker2d file to sketchable graphdata
 */

class bruker2d extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['/pdata/1/2rr.'][$this->fileNumber];	// puts all the data into a string
			$this->report[0][0] = $file_content['/pdata/1/procs.'][$this->fileNumber];
			$this->report[0][1] = $file_content['/pdata/1/proc2s.'][$this->fileNumber];
			$this->report[1][0] = $file_content['/acqus.'][$this->fileNumber];
			$this->report[1][1] = $file_content['/acqu2s.'][$this->fileNumber];
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['y'] = 2;
			$this->config['precision']['x'] = 2;
			$this->config['legendOffset']=115;
			$this->config['peaks']['significanceLevel'] = 3;
			$this->config['peaks']['range']=300;
			$this->config['peaks']['minRad']=0;
			$this->config['peaks']['maxPeaks']=7;
			
			// does the converting
			$this->convertFileToGraphData();
			
			// gets the peaks
			$this->graphData = $this->getPeaks($this->graphData, $this->config);
			
			// produces interpretation
			//$this->produceInterpretation();
			
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
	 * converts a bruker2d file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		if ($this->data=="") {
			return;
		}
		foreach ($this->report[0] as $idx => $proc) {
			// parse procs
			$file_lines=explode("\n",fixLineEnd($proc));
			
			for ($a=0;$a<count($file_lines);$a++) {
				$file_line=trim($file_lines[$a]);
				list($name,$value)=explode("=",substr($file_line,2),2);
				$name=strtolower($name);
				$value=trim($value);
				$values=array($value);
				
				// gets following lines
				while (startswith($file_lines[$a+1],"\$\$")) {
					$a++;
					$values[]=trim(substr($file_lines[$a],2));
				}
				
				switch ($name) {
				case "\$bytordp":
					$big_en[$idx]=$value+0;
				break;
				case "\$ftsize":
					$proc_blocks[$idx]=$value;
				break;
				case "\$offset":
					$x_offset[$idx]=$value;
				break;
				case "\$sw_p":
					$x_sweep[$idx]=$value;
				break;
				case "\$axnuc":
					preg_match("/<(\d*)([A-Z][a-z]*)>/",$value,$temp);
					$nuc_mass[$idx]=$temp[1];
					$nuc_sym[$idx]=$temp[2];
					unset($temp);
				break;
				case "\$sf":
					$freq_mhz[$idx]=$value;
				break;
				}
			}
			$x_offset[$idx]*=$freq_mhz[$idx]; // ppm => Hz to have everything in Hz
		}
		
		// read acqus
		foreach ($this->report[1] as $idx => $acqu) {
			$file_lines=explode("\n",fixLineEnd($acqu));
		
			for ($a=0;$a<count($file_lines);$a++) {
				$file_line=trim($file_lines[$a]);
				list($name,$value)=explode("=",substr($file_line,2),2);
				$name=strtolower($name);
				$value=trim($value);
				$values=array($value);
				
				// gets following lines
				while (startswith($file_lines[$a+1],"\$\$")) {
					$a++;
					$values[]=trim(substr($file_lines[$a],2));
				}
				
				switch ($name) {
				case "\$solvent":
					$solvent[$idx]=substr($value,1,-1);
				break;
				case "\$te":
					$temperature[$idx]=$value;
				break;
				case "origin":
					$origin[$idx]=$value;
				break;
				case "owner": // gets spectrum path
					$owner[$idx]=$value;
					$filePath[$idx]=$values[2];
				break;
				case "\$instrum":
					$instrum[$idx]=substr($value,1,-1);
				break;
				case "\$date":
					$date[$idx]=date('r',$value);
				break;
				case "\$td":
					$npoints[$idx]=$value;
				break;
				}
			}
		}

		// reads graph
		$blocks=str_split($this->data, 4);
		$blocks_count=count($blocks);
		$pointsAsXY=array();
		$block_no=0;
		
		for ($x=0;$x<$proc_blocks[0];$x++) {
			for ($y=0;$y<$proc_blocks[1];$y++) {
				$pointsAsXY[$y][$x]['y']=$this->readData($blocks[($x+1)*($y+1)-1], 'l', strlen($blocks[($x+1)*($y+1)-1]), 0);
			}
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
		$this->fileNumber = 0;
		return 2;
	}
}
?>