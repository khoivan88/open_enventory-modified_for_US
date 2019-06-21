<?php
/*
 * Reads and saves an unknown file
 */

class unknown extends converter {
	
	function __construct($file_content, $doCunstruction) {
		if($doCunstruction==true) {
			parent::__construct();
			$this->cursorPos = 0;	// important data starts at cursor position 0
			$this->data = $file_content;	// gets all the data
			
			// ENTER THE SPECIFIC CONFIGURATION OF THIS DATATYPE HERE
			// Please check up the converter.php for possible configuration variables
			/* no configuration variables set */
			
			$this->produceInterpretation();
			
			// following part is useless and can be uncommented if there is no graphData in xy format
			
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
		/* no converting is done */
	}
	
	/*
	 * produces the interpretation string
	 */
	public function produceInterpretation() {
		// just save raw data
		$this->graphData['interpretation'] = var_export($this->data, true);
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