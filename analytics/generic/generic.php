<?php
$GLOBALS["type_code"]="generic";
$GLOBALS["device_driver"]="generic";
$GLOBALS["analytics"][ $GLOBALS["type_code"] ][ $GLOBALS["device_driver"] ]=array(
"requiredFiles" => array(),
"optionalFiles" => array(
	"image.gif",".gif",".png",".jpg",".jpeg", ".bmp",".tif",".tiff",".pcx",".tga",".rle",".wmf", ".emf",
	".txt",".p",".htm",".html",".mol",".rxn",".spi", ".eps",".ps",".pdf", ".doc",".xls",".ppt",".docx",".xlsx",".pptx",".rtf",".odt",".ods",".odp",".odg",".odc",".odf",".odi"),
"excludeFiles" => array("report.txt","molecule.mol","peak.txt","integrals.txt","audita.txt","auditp.txt",));
$GLOBALS["generic_file_types"]=array(
		"gs" => array(".eps",".ps",".pdf", ),
		"soffice" => array(".doc",".xls",".ppt",".docx",".xlsx",".pptx",".rtf",".odt",".ods",".odp",".odg",".odc",".odf",".odi", ),
		"magick" => array(".bmp",".tif",".tiff",".pcx",".tga",".rle",".wmf", ),
		"vectorEMF" => array(".emf", ),
);

/*
 * Reads and converts a generic file to sktechable graphdata
 */

class generic extends converter {
	
	function __construct($file_content, $doCunstruction) {
		if($doCunstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->data = $file_content;	// gets all the data
			
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
		$img=array();
		$img_mime=array();
		$analytical_data_properties=array();
		$convert_list=array_values_r($GLOBALS["generic_file_types"]);
		for ($a=0;$a<count($GLOBALS['analytics']['generic']['generic']['optionalFiles']); $a++) {
			for ($b=0;$b<count($this->data[$GLOBALS['analytics']['generic']['generic']['optionalFiles'][$a]]); $b++) {
				$file_content=$this->data[$GLOBALS['analytics']['generic']['generic']['optionalFiles'][$a]][$b];
				if (!empty($file_content)) {
					// cut dot away
					$ext=strtolower($GLOBALS['analytics']['generic']['generic']['optionalFiles'][$a]);
		
					$dotpos=strrpos($ext,"."); // last dot separates extension
		
					if ($dotpos!==FALSE) {
						$dotext=substr($ext,$dotpos);
						$ext=substr($dotext,1);
					}
		
					$doScale=true;
					if (in_array($dotext,array(".txt",".p",".htm",".html",))) {
						$this->graphData['interpretation']=makeHTMLSafe($file_content); // makes safe
						continue; // does not make image
					}
					elseif ($dotext==".mol") {
						$molecule=readMolfile($file_content,array() );
						$file_content=getMoleculeGif($molecule,gif_x,gif_y,0,1,true,array("gif"));
						$ext="gif";
						$doScale=false;
					}
					elseif ($dotext==".rxn") {
						$reaction=readRxnfile($file_content);
						$file_content=getReactionGif($reaction,rxn_gif_x,rxn_gif_y,0,1,6,array("gif"));
						$ext="gif";
						$doScale=false;
					}
					elseif ($dotext==".spi") { // csv of chromatography integrals: rt [min], area [will be normalised], height
						// "Peak","Component","Time","Area","Height","Area","Norm. Area"
							
						// parses standard csv chromatography report
						$rep_lines=explode("\n",$file_content);
						$total_area=0;
						$stage=0;
							
						for ($c=0;$c<count($rep_lines);$c++) {
							$line=$rep_lines[$c];
							if ($line==="" || in_array($line{0},array("#",";"))) { // skips comments starting with # or ;
								continue;
							}
							$dataArray=parseCSV($line);
							array_walk($dataArray,"trim_value");
		
							switch ($stage) {
								case 0: // class definitions
									$col_idx=array();
									$col_idx["time"]=array_search("Time",$dataArray);
									$col_idx["rel_area"]=array_search("Area",$dataArray);
									$col_idx["height"]=array_search("Height",$dataArray);
									$col_idx["comment"]=array_search("Component",$dataArray);
									$stage+=2;
									break;
								case 1: // unit settings
									// later
									break;
								case 2:
									$area=fixNull($dataArray[ $col_idx["rel_area"] ]);
									$total_area+=$area;
									$analytical_data_properties["peaks"][]=array(
											"time" => fixNull($dataArray[ $col_idx["time"] ]),
											"rel_area" => $area,
											"height" => fixNull($dataArray[ $col_idx["height"] ]),
											"comment" => $dataArray[ $col_idx["comment"] ],
									);
									break;
							}
						}
							
						if ($total_area>0) for ($c=0;$c<count($analytical_data_properties["peaks"]);$c++) {
							$analytical_data_properties["peaks"][$c]["rel_area"]*=100/$total_area;
						}
		
						continue; // does no try to make image
					}
					elseif (in_array($dotext,$convert_list)) {
						$file_content=data_convert($file_content,$ext);
						$ext="png";
					}
					if ($doScale) {
						$tempImage = imagecreatefromstring($file_content);
						$newImage = imagecreatetruecolor($this->config['dimensions']['width'], round(imagesy($tempImage)/imagesx($tempImage)*$this->config['dimensions']['width']));
						imagecopyresampled($newImage, $tempImage, 0, 0, 0, 0, $this->config['dimensions']['width'], round(imagesy($tempImage)/imagesx($tempImage)*$this->config['dimensions']['width']), imagesx($tempImage), imagesy($tempImage));
						ob_start();
						ImagePNG($newImage);
						$file_content = ob_get_clean();
					}
					$img[]=$file_content;
					$img_mime[]=getMimeFromExt($ext);
				}
			}
		}
		$this->graphData['analytical_data_properties'] = $analytical_data_properties;
		$this->graphData['image'] = $img[0];
		$this->graphData['imageMime'] = $img_mime[0];
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