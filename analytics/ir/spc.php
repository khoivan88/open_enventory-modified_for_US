<?php
$GLOBALS["type_code"]="ir";
$GLOBALS["device_driver"]="spc";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
		"requiredFiles" => array(".spc"),
		"optionalFiles" => array());
/*
 * Reads and converts a *.spc file to sketchable graphdata
 */

class spc extends IRconverter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.spc'][$this->fileNumber];	// puts all the data into a string
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['y'] = 0;
			$this->config['precision']['x'] = 2;
			$this->config['peaks']['range'] = 50;
			
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
	 * converts an ir/*.spc file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		// some preparations
		$x_text = array("Arbitrary","Wavenumber (cm-1)","Microns","Nanometers","Seconds","Minutes","Hz","KHz","MHz","Mass (M/z)","ppm","Days","Years","Raman Shift (cm-1)","None");
		$y_text = array("Arbitrary","Interferogram","Absorbance","Kubelka-Monk","Counts","Volts","Degrees","milliamps","millimeters","millivolts","Log (1/R)","Percent",128 => "Transmission",129 => "Reflectance",130 => "Single Beam",131 => "Emission Beam");
		$type_text = array("General","GC","Chromatogram","HPLC","IR","NIR","UV-VIS","XRD","MS","NMR","Raman","Fluorescence","Atomic","Chromatography Diode Array");		
		
		$version_byte=ord($this->data{1}); // makes int
		$version_byte-=0x4b; // 0 (LSBfirst),1 (MSBfirst),2 (old)
		$xy_data=array();
		switch ($version_byte) {
			case 2:
				$len=256;
				$code="Cftflgs/Cfversn/vfexp/ffnpts/fffirst/fflast/Cfxtype/Cfytype/vyear/Cmonth/Cday/Chour/Cminute/a8fres/vfpeakpt/vscans/a7spare/a130fcmnt/a30fcatxt/a32subh1";
				$decode_long="l"; //"V"; best would be w
				$decode_short="s"; // best would be g
				break;
			case 1: // BE
				$len=512;
				$code="Cftflgs/Cfversn/Cfexper/Cfexp/Nfnpts/dffirst/dflast/Nfnsub/Cfxtype/Cfytype/Cfztype/Cfpost/Nfdate/a9fres/a9fsource/nfpeakpt/ffspare/c130fcmnt/c30fcatxt/Nflogoff/Nfmods/Cfprocs/Cflevel/nfsampin/fffactor/a48fmethod/ffzinc/Nfwplanes/ffwinc/Cfwtype";
				$decode_long="N"; // "N"; best would be m
				$decode_short="n"; // best would be b
				break;
			case 0: // LE
				$len=512;
				$code="Cftflgs/Cfversn/Cfexper/Cfexp/Vfnpts/dffirst/dflast/Vfnsub/Cfxtype/Cfytype/Cfztype/Cfpost/Vfdate/a9fres/a9fsource/vfpeakpt/ffspare/a130fcmnt/a30fcatxt/Vflogoff/Vfmods/Cfprocs/Cflevel/nfsampin/fffactor/a48fmethod/ffzinc/Vfwplanes/ffwinc/Cfwtype";
				$decode_long="l"; //"V"; best would be w
				$decode_short="s"; // best would be g
				break;
			default:
				return;
		}
		$data=unpack($code,substr($this->data,$this->cursorPos,$len));
		$this->cursorPos += $len;
		
		$single_prec=($data["ftflgs"] & 1);
		$decode=($single_prec?$decode_short:$decode_long);

		if ($data["ftflgs"] & 128) { // TXVALS?
			$len=$data["fnpts"]*4;
			$xy_data["x"]=array_values($this->readData($this->data, $decode_long."*", $len, $this->cursorPos));
			$this->cursorPos+=$len;
		}
		
		// general stuff
		if(abs($data["flast"])<10) {
			$this->graphData['extrema']['minima']['x']=round($data["flast"], 2);
			$this->graphData['extrema']['maxima']['x']=round($data["ffirst"], 2);
			$this->config['precision']['x'] = 2;
		}
		else {
			$this->graphData['extrema']['minima']['x']=round($data["flast"], -1);
			$this->graphData['extrema']['maxima']['x']=round($data["ffirst"], -1);
			$this->config['precision']['x'] = 0;
		}
		$this->graphData['units']['x']=$x_text[ $data["fxtype"] ];
		$this->graphData['units']['y']=$y_text[ $data["fytype"] ];
		
		// reads sub header
		$len=32;
		$code="Csubflgs/Csubexp/".$decode_short."subindx/fsubtime/fsubnext/fsubnois/".$decode_long."subnpts/".$decode_long."subscan/fsubwlevel";
		$sub_data=unpack($code,substr($this->data,$this->cursorPos,$len));
		$this->cursorPos+=$len;
		
		$yMax=-PHP_INT_MAX;
		$yMin=PHP_INT_MAX;
		// reads y values
		$len=$data["fnpts"]*2*(2-$single_prec); // 32 or 16 bit
		if ($sub_data["subexp"]==0x80) { // IEEE float
			$xy_data["y"]=array_values(unpack("f*",substr($this->data,$this->cursorPos,$len)));
		}
		else {
			$xy_data["y"]=str_split(substr($this->data,$this->cursorPos,$len),($single_prec?2:4));
		}
		$this->cursorPos+=$len;
		
		// decodes y values according to fexp/subexp
		if ($sub_data["subexp"]!=0x80) {
			for ($a=0;$a<count($xy_data["y"]);$a++) {
				$block=$xy_data["y"][$a];
				if ($version_byte==2 && !$single_prec) {
					// swap words
					$block=substr($block,2,2).substr($block,0,2);
				}
				//~ $xy_data["y"][$a]=$fac*up($decode,$block);
				$xy_data["y"][$a]=$this->readData($block, $decode, strlen($block), 0); // will be normalized anyway
				if($xy_data["y"][$a]>$yMax) {
					$yMax = $xy_data["y"][$a];
				}
				if($xy_data["y"][$a]<$yMin) {
					$yMin = $xy_data["y"][$a];
				}
			}
		}
		
		// sets extrema
		$this->graphData['extrema']['maxima']['y'] = 100;
		$this->graphData['extrema']['minima']['y'] = 0;
		if ($this->graphData['extrema']['minima']['x']>$this->graphData['extrema']['maxima']['x']) {
			$xy_data["y"]=array_reverse($xy_data["y"]);
			swap($this->graphData['extrema']['minima']['x'],$this->graphData['extrema']['maxima']['x']);
		}
		
		// normalisation
		$factor = ($this->graphData['extrema']['maxima']['x']-$this->graphData['extrema']['minima']['x'])/$data['fnpts'];
		$pointsAsXY=array();
		for($i=0; $i<count($xy_data['y']); $i++) {
			$xy_data['y'][$i] = $this->fixLongInt($xy_data['y'][$i], $xy_data['y'][$i-1], 3000000000);
			$pointsAsXY[$i]['x']=round($this->graphData['extrema']['minima']['x'])+$i*$factor;
			$pointsAsXY[$i]['y']=100/($yMax-$yMin)*($xy_data['y'][$i]-$yMin);
		}
		$this->graphData['graphs'][0]['points'] = $pointsAsXY;
		$this->graphData['drawingStyle'] = 2; // sets drawingStyle to y-axis right
	}
	
	/*
	 * checks if the signature of the file fits the signature of the converter
	 * it returns 0, if it fits, else 1. if there is none, return 2
	 */
	public function verifyFileSignature($file_contents) {
		$isCorrectConverter=0;
		for($i=0; $i<count($file_contents); $i++) {
			for($j=0; $j<count($file_contents['.spc']); $j++) {
				$version_byte=ord($file_contents[array_keys($file_contents)[$i]][$j]{1});
				if ($version_byte<0x4d || $version_byte>0x4b) { // version check
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>