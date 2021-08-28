<?php
$GLOBALS['type_code']='hplc';
$GLOBALS['device_driver']='shimadzu';
$GLOBALS['analytics'][ $GLOBALS['type_code'] ][ $GLOBALS['device_driver'] ]=array(
		'requiredFiles' => array('.'),
		'optionalFiles' => array());
/*
 * Reads and converts a shimadzu file to sketchable graphdata
 */

class shimadzu extends converter {
	
	function __construct($file_content, $doConstruction) {
		if($doConstruction==true) {
			parent::__construct();
			$this->verifyFileSignature($file_content);
			$this->data = $file_content['.'][$this->fileNumber];	// puts all the data into a string
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			$this->config['precision']['x'] = 2;
			$this->config['peaks']['significanceLevel'] = 1.05;
			$this->config['peaks']['range']=50;
			
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
	 * converts a shimadzu file into sketchable graphData
	 */
	public function convertFileToGraphData() {
		// creates OLE object using a tempfile
		$ole=new OLE();
		$filename=make_tempfile($this->data);
		$ole->read($filename);
		@unlink($filename);
		
		// reads runtime
		$trac_obj=$trac_obj=getOLEchild($ole,array("Detector Trace Handler"));
		$size=$trac_obj->Size;
		$trac_data=$ole->getData($trac_obj->No,0,$size);
		$this->graphData['extrema']['maxima']['x'] = $this->readData($trac_data, 'f', 4, 110)/60;
		$this->graphData['units']['x'] = "min";
		$this->graphData['units']['y'] = "%";
		
		// gets wavelengths
		preg_match_all("/(?ms)Detector [A-Z]+ \- \d+ \((\d+nm)\)/",$trac_data,$trac_split,PREG_PATTERN_ORDER);
		$this->graphData['graphNames']=$trac_split[1];
		
		// reads method name
		$method_obj=getOLEchild($ole,array("Method","Chromatography Reports","Text Data"));
		$size=$method_obj->Size;
		$method_data=$ole->getData($method_obj->No,0,$size);
		preg_match("/(?ims)Method Name: (.*?) \x09\x09/",$method_data,$method_name);
		$this->graphData['method']=cutFilename($method_name[1],"\\");
		
		// reads graph
		$xy_data=array();
		$det_obj=getOLEchild($ole,array("Detector Data"));
		$det_traces=$det_obj->children;
		if (is_array($det_traces)) foreach ($det_traces as $det_trace) {
			$det_trace_name=$det_trace->Name;
			$size=$det_trace->Size;
			list($num)=sscanf($det_trace_name,"Detector %d Trace");
			$xy_data[$num]=$ole->getData($det_trace->No,0,$size);
		}
		unset($det_obj);
		unset($det_traces);
		
		// process graph
		$yMax=-PHP_INT_MAX;
		$yMin=0;
		
		foreach($xy_data as $idx => $this_xy_data) {
			$blocks=str_split($this_xy_data,4);
			array_splice($blocks,-10,10);
			$points=count($blocks);
			$pointsAsXY = array();
		
			for ($a=0;$a<$points;$a++) {
				$pointsAsXY[$a]['x']=$this->graphData['extrema']['maxima']['x']/$points*$a;
				$pointsAsXY[$a]['y']=$this->readData($blocks[$a], 'l', strlen($blocks[$a]), 0);
				// determines maximum and minimum y value
				if($yMax<$pointsAsXY[$a]['y']) {
					$yMax=$pointsAsXY[$a]['y'];
				}
			}
			// normalisation
			for ($b=0;$b<count($pointsAsXY); $b++) {
				$pointsAsXY[$b]['y']=100/($yMax-$yMin)*($pointsAsXY[$b]['y']-$yMin); // get y value in percent
			}
			$this->graphData['graphs'][$idx]['points']=$pointsAsXY;
		}
		$this->graphData['extrema']['maxima']['y']=100;
		
		// reads peaks
		$rep_obj=getOLEchild($ole,array("Results","LastResults"));
		$size=$rep_obj->Size;
		$data=$ole->getData($rep_obj->No,0,$size);
		$report_data=explode("\x00\x00\x00\x00\x37\x00\x00\x00\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF",$data);
		unset($data);
		
		// reads and saves some properties
		$this->graphData['analytical_data_properties']=array();
		$this->graphData['analytical_data_properties']['peaks']=array();
		$total_area=0;
		foreach ($report_data as $el_num => $element) {
			if (strlen($element)==506) {
				break;
			}
			$cut_pos=strrpos($element,"\x02\x00\x00\x00");
			$peak_data=substr($element,$cut_pos+4);
		
			$dataArray=colUnpack($peak_data,array("f","f","f","f","d","f","d","d","d","f","d","f","f","f"));
		
			$total_area+=$dataArray[8];
			$this->graphData['analytical_data_properties']['peaks'][]=array('time' => $dataArray[2]/60, 'width' => ($dataArray[1]-$dataArray[0]/60), 'rel_area' => $dataArray[8], 'height' => $dataArray[10]);
		}
		if ($total_area>0) for ($a=0;$a<count($this->graphData['analytical_data_properties']['peaks']);$a++) {
			$this->graphData['analytical_data_properties']['peaks'][$a]['rel_area']/=$total_area*0.01;
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
				if(substr($file_contents[array_keys($file_contents)[$i]][$j], 0, 8)=="\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
					$isCorrectConverter = 1;
					$this->fileNumber = $j;
				}
			}
		}
		return $isCorrectConverter;
	}
}
?>
