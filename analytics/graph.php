<?php
/*
 * Draws a graph into a coordinatesystem
 */

class graph {
	private $image; 				// image source
	private $graphData;				// the converted graphdata
	private $config;				// the configuration variables
	private $yAxisCorrection;		// the correction of the yAxis
	private $secondYAxisPosition;	// the position of the 2nd y-axis
	private $xAxisCorrection;		// the correction of the xAxis
	private $xGraphStart;			// the x coordinate of the starting point of the graph to be drawn, default is zero
	private $sign;					// assisting sign
	
	/*
	 * Constructor
	 */
	function __construct($graphData, $config) {
		// initialisation of the variables
		$this->graphData = $graphData;
		$this->config = $config;
		$this->sign = 1;
		$this->xGraphStart = 0;
		$this->yAxisCorrection = 0;
		$this->xAxisCorrection = 0;
		$this->image = @imagecreate($config['dimensions']['width'], $config['dimensions']['height']) or die("Kann keinen GD-Stream erzeugen");
		$this->config['colors'] = array(
						'text' => imagecolorallocate($this->image, 122, 122, 122),				// default color for text
						'peaks' => imagecolorallocate($this->image, 249, 149, 0),			// default color for peaks and peaklabels
						'lines' => imagecolorallocate($this->image, 122, 122, 122),				// default color for lines
						'axis' => imagecolorallocate($this->image, 122, 122, 122),				// default color for axis
						'grid' => imagecolorallocate($this->image, 210, 210, 210),			// default color for the grid
						'background' => imagecolorallocate($this->image, 230, 220, 255),	// default backgroundcolor
						'graphs' => array(
								0 => imagecolorallocate($this->image, 0, 0, 0),				// color of the 1, graph
								1 => imagecolorallocate($this->image, 96, 186, 90),			// color of the 2. graph
								2 => imagecolorallocate($this->image, 80, 192, 220),		// color of the 3. graph
								3 => imagecolorallocate($this->image, 243, 34, 76),			// color of the 4. graph
								4 => imagecolorallocate($this->image, 176, 101, 240)		// color of the 5. graph
						//		5 => anothercolor)		
						)); // more possible graphcolors by adding lines with colors here
		
		// calculates the correction of the axis
		$this->calculateAxisCorrection();
		
		// calculates the x coordinate of the starting point of the graph to be drawn
		$this->calculateXGraphStart();
		
		// 2nd y axis, right or left?
		if($this->graphData['drawingStyle'] == 2) {
			$this->secondYAxisPosition = $this->config['axisOffset']['y']+$this->graphData['tickDistance']['x'];
		}
		else {
			$this->secondYAxisPosition = $this->config['dimensions']['width']-$this->config['margin']['right']-$this->graphData['tickDistance']['x'];
		}

		// fills in with backgroundcolor
		imagefill($this->image, 0, 0, $this->config['colors']['background']);
		if($this->config['transparent']==true) {
			imagecolortransparent($this->image, $this->config['colors']['background']);
		}
		
		// draws grid
		$this->drawGrid();
		
		// draws axis with units
		$this->drawAxis();
		
		// draws ticks with labels
		$this->drawTicksAndLabels();
		
		// draws the legend
		$this->drawLegend();
		
		// draws the graph
		$this->drawGraph();
		
		// draws peaks with labels
		$this->drawPeaksWithLabels();
	}
	
	public function __destruct() {
		// nothing happens
	}
	
	/*
	 * draws the grid
	 */
	private function drawGrid() {
		for ($tickcount = ceil(($this->config['dimensions']['width']-($this->config['axisOffset']['y']+$this->config['margin']['right']))/($this->graphData['tickDistance']['x']/2))-1; $tickcount>-1; $tickcount--) {
			for($a=0; $a<$this->config['thickness']; $a++) {
				imgline($this->image, $this->config['axisOffset']['y']+($this->graphData['tickDistance']['x']/2)+$a+($tickcount-1)*($this->graphData['tickDistance']['x']/2), $this->config['margin']['top'], $this->config['axisOffset']['y']+($this->graphData['tickDistance']['x']/2)+$a+($tickcount-1)*($this->graphData['tickDistance']['x']/2), $this->config['dimensions']['height']-($this->config['axisOffset']['x']-$this->config['tickSize']['small']), $this->config['colors']['grid']);
			}
		}
		for ($tickcount = ceil(($this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->config['margin']['top']))/($this->graphData['tickDistance']['y']/2))-1; $tickcount>-1; $tickcount--) {
			for($a=0; $a<$this->config['thickness']; $a++) {
				imgline($this->image, ($this->config['axisOffset']['y']-$this->config['tickSize']['small']), $this->config['dimensions']['height']-($this->config['axisOffset']['x']+($this->graphData['tickDistance']['y']/2)+$a)-(($this->graphData['tickDistance']['y']/2)*($tickcount-1)), $this->config['dimensions']['width']-$this->config['margin']['right'], $this->config['dimensions']['height']-($this->config['axisOffset']['x']+($this->graphData['tickDistance']['y']/2)+$a)-(($this->graphData['tickDistance']['y']/2)*($tickcount-1)), $this->config['colors']['grid']);
			}
		}
	}
	
	/*
	 * draws the x and y axis with units
	 */
	private function drawAxis() {
		// draws x axis
		for($a=0; $a<$this->config['thickness']; $a++) {
			imgline($this->image, $this->config['axisOffset']['y'], $this->config['dimensions']['height']-$this->config['axisOffset']['x']-$this->xAxisCorrection+$a, $this->config['dimensions']['width']-$this->config['margin']['right'], $this->config['dimensions']['height']-$this->config['axisOffset']['x']-$this->xAxisCorrection+$a, $this->config['colors']['axis']);
		}
		if($this->graphData['drawingStyle']==2) {
			$pointsXArrow = array($this->config['axisOffset']['y'], $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->xAxisCorrection+$this->config['arrowSize']), $this->config['axisOffset']['y'], $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->xAxisCorrection-$this->config['arrowSize']), $this->config['axisOffset']['y']-$this->config['arrowSize'], $this->config['dimensions']['height']-$this->config['axisOffset']['x']-$this->xAxisCorrection);
		}
		else {
			$pointsXArrow = array($this->config['dimensions']['width']-$this->config['margin']['right'], $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->xAxisCorrection+$this->config['arrowSize']), $this->config['dimensions']['width']-$this->config['margin']['right'], $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->xAxisCorrection-$this->config['arrowSize']), $this->config['dimensions']['width']-$this->config['margin']['right']+$this->config['arrowSize'], $this->config['dimensions']['height']-$this->config['axisOffset']['x']-$this->xAxisCorrection);
		}
		imagefilledpolygon($this->image, $pointsXArrow, 3, $this->config['colors']['axis']);
		
		// draws y axis
		for($a=0; $a<$this->config['thickness']; $a++) {
			imgline($this->image, $this->config['axisOffset']['y']+$a+$this->yAxisCorrection, $this->config['margin']['top'], $this->config['axisOffset']['y']+$a+$this->yAxisCorrection, $this->config['dimensions']['height']-$this->config['axisOffset']['x'], $this->config['colors']['axis']);
		}
		$pointsYArrow = array($this->config['axisOffset']['y']+$this->yAxisCorrection-$this->config['arrowSize'], $this->config['margin']['top'], $this->config['axisOffset']['y']+$this->yAxisCorrection+$this->config['arrowSize'], $this->config['margin']['top'], $this->config['axisOffset']['y']+$this->yAxisCorrection, $this->config['margin']['top']-$this->config['arrowSize']);
		imagefilledpolygon($this->image, $pointsYArrow, 3, $this->config['colors']['axis']);
		
		// draws 2nd y axis
		if($this->config['2ndYAxis'] == true) {
			for($a=0; $a<$this->config['thickness']; $a++) {
				imgline($this->image, $this->secondYAxisPosition+$a, $this->config['margin']['top'], $this->secondYAxisPosition+$a, $this->config['dimensions']['height']-$this->config['axisOffset']['x'], $this->config['colors']['axis']);
			}
			$pointsYArrow = array($this->secondYAxisPosition-$this->config['arrowSize'], $this->config['margin']['top'], $this->secondYAxisPosition+$this->config['arrowSize'], $this->config['margin']['top'], $this->secondYAxisPosition, $this->config['margin']['top']-$this->config['arrowSize']);
			imagefilledpolygon($this->image, $pointsYArrow, 3, $this->config['colors']['axis']);
		}
		
		// draws units
		if($this->graphData['drawingStyle']==2) {
			imagefttext($this->image, $this->config['textAttributes']['size'], 0, $this->config['axisOffset']['y']-$this->config['arrowSize'], $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->xAxisCorrection+15), $this->config['colors']['text'], $this->config['textAttributes']['font'], $this->graphData['units']['x']);
		}
		else {
			imagefttext($this->image, $this->config['textAttributes']['size'], 0, $this->config['dimensions']['width']-($this->config['margin']['right']+$this->config['yUnitOffset']), $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->xAxisCorrection+15), $this->config['colors']['text'], $this->config['textAttributes']['font'], $this->graphData['units']['x']);
		}
		imagefttext($this->image, $this->config['textAttributes']['size'], 0, $this->config['axisOffset']['y']+$this->yAxisCorrection-35, $this->config['margin']['top']-10, $this->config['colors']['text'], $this->config['textAttributes']['font'], $this->graphData['units']['y']);
		if($this->config['2ndYAxis'] == true) {
			imagefttext($this->image, $this->config['textAttributes']['size'], 0, $this->secondYAxisPosition-35, $this->config['margin']['top']-10, $this->config['colors']['text'], $this->config['textAttributes']['font'], $this->graphData['units']['y2']);
		}
	}
	
	/*
	 * draws the ticks and labels of the x and y axis
	 */
	private function drawTicksAndLabels() {
		// x-direction
		$factor=ceil(($this->config['dimensions']['width']-($this->config['axisOffset']['y']+$this->config['margin']['right']))/$this->graphData['tickDistance']['x']);
		for ($tickcount = ceil(($this->config['dimensions']['width']-($this->config['axisOffset']['y']+$this->config['margin']['right']))/$this->graphData['tickDistance']['x']); $tickcount>0; $tickcount--) {
			for($a=0; $a<$this->config['thickness']; $a++) {
				imgline($this->image, $this->config['axisOffset']['y']+$a+($tickcount-1)*$this->graphData['tickDistance']['x'], $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->xAxisCorrection+$this->config['tickSize']['big']), $this->config['axisOffset']['y']+$a+($tickcount-1)*$this->graphData['tickDistance']['x'], $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->xAxisCorrection-$this->config['tickSize']['big']), $this->config['colors']['axis']);
			}
			if($this->graphData['drawingStyle']==2) {
				$xLabel = $this->graphData['extrema']['minima']['x']+($factor-1)*$this->graphData['tickScale']['x']-($tickcount-1)*$this->graphData['tickScale']['x'];
			}
			else {
				$xLabel = $this->graphData['extrema']['minima']['x']+($tickcount-1)*$this->graphData['tickScale']['x'];
			}
			imagefttext($this->image, $this->config['textAttributes']['size'], 0, ($this->config['axisOffset']['y']-5)-((strlen(round(($this->graphData['extrema']['maxima']['x']-$this->graphData['extrema']['minima']['x'])/ceil(($this->config['dimensions']['width']-($this->config['axisOffset']['y']+$this->config['margin']['right']))/$this->graphData['tickDistance']['x']-1)*($tickcount-1)))-1)+abs($this->config['precision']['x']))*3+($tickcount-1)*$this->graphData['tickDistance']['x'], $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->xAxisCorrection-25), $this->config['colors']['text'], $this->config['textAttributes']['font'], $xLabel);
		}
		
		// y-direction
		for ($tickcount = ceil(($this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->config['margin']['top']))/$this->graphData['tickDistance']['y']); $tickcount>0; $tickcount--) {
			for($a=0; $a<$this->config['thickness']; $a++) {
				imgline($this->image, $this->config['axisOffset']['y']+$this->yAxisCorrection-$this->config['tickSize']['big'], $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$a)-($this->graphData['tickDistance']['y']*($tickcount-1)), $this->config['axisOffset']['y']+$this->yAxisCorrection+$this->config['tickSize']['big'], $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$a)-($this->graphData['tickDistance']['y']*($tickcount-1)), $this->config['colors']['axis']);
			}
			if($this->config['2ndYAxis'] == true) {
				for($a=0; $a<$this->config['thickness']; $a++) {
					imgline($this->image, $this->secondYAxisPosition-$this->config['tickSize']['big'], $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$a)-($this->graphData['tickDistance']['y']*($tickcount-1)), $this->secondYAxisPosition+$this->config['tickSize']['big'], $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$a)-($this->graphData['tickDistance']['y']*($tickcount-1)), $this->config['colors']['axis']);
					imagefttext($this->image, $this->config['textAttributes']['size'], 0, $this->secondYAxisPosition+$this->config['tickSize']['big']+5, $this->config['dimensions']['height']-($this->config['axisOffset']['x']-5)-($this->graphData['tickDistance']['y']*($tickcount-1)), $this->config['colors']['text'], $this->config['textAttributes']['font'], $this->graphData['extrema']['minima']['y2']+($tickcount-1)*$this->graphData['tickScale']['y2']);
				}
			}
			if($this->graphData['drawingStyle']==2) {
				$yLabel="";
			}
			else {
				$yLabel = $this->graphData['extrema']['minima']['y']+($tickcount-1)*$this->graphData['tickScale']['y'];
			}
			imagefttext($this->image, $this->config['textAttributes']['size'], 0, 5+$this->yAxisCorrection, $this->config['dimensions']['height']-($this->config['axisOffset']['x']-5)-($this->graphData['tickDistance']['y']*($tickcount-1)), $this->config['colors']['text'], $this->config['textAttributes']['font'], $yLabel);
		}
	}
	
	/*
	 * draws the graphs
	 */
	private function drawGraph() {
		for($j = 0; $j<count($this->graphData['graphs']); $j++) {
			for($i = 0; $i<count($this->graphData['graphs'][$j]['points']); $i++)	{
				if($this->graphData['graphs'][$j]['points'][$i]['x']<$this->config['dimensions']['width']-($this->config['axisOffset']['y']+$this->config['margin']['right']) && $this->graphData['graphs'][$j]['points'][$i]['x']>=0) {
					for($a=0; $a<$this->config['thickness']; $a++) {
						if($this->graphData['graphs'][$j]['points'][$i+1] != NULL && $this->graphData['drawingStyle']==0) {
							imgline($this->image, $this->config['axisOffset']['y']+$this->graphData['graphs'][$j]['points'][$i]['x']+$a, $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->graphData['graphs'][$j]['points'][$i]['y']+$a), $this->config['axisOffset']['y']+$this->graphData['graphs'][$j]['points'][$i+1]['x']+$a, $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->graphData['graphs'][$j]['points'][$i+1]['y']+$a), $this->config['colors']['graphs'][$j]);
						}
						elseif($this->graphData['drawingStyle']==1) {
							imgline($this->image, $this->config['axisOffset']['y']+$this->graphData['graphs'][$j]['points'][$i]['x']+$a, $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$a), $this->config['axisOffset']['y']+$this->graphData['graphs'][$j]['points'][$i]['x']+$a, $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->graphData['graphs'][$j]['points'][$i]['y']+$a), $this->config['colors']['graphs'][$j]);
						}
						elseif($this->graphData['graphs'][$j]['points'][$i+1] != NULL && $this->graphData['drawingStyle']==2) {
							imgline($this->image, $this->config['axisOffset']['y']+$this->xGraphStart-$this->graphData['graphs'][$j]['points'][$i]['x']+$a, $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->graphData['graphs'][$j]['points'][$i]['y']+$a), $this->config['axisOffset']['y']+$this->xGraphStart-$this->graphData['graphs'][$j]['points'][$i+1]['x']+$a, $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->graphData['graphs'][$j]['points'][$i+1]['y']+$a), $this->config['colors']['graphs'][$j]);
						}
					}
				}
			}
		}
	}
	
	/*
	 * draws the peaks with its labels
	 */
	private function drawPeaksWithLabels() {
		$tickCorrection = $this->config['peaks']['tickLength'];
		$labelCorrection = -20;
		$spacer=3;
		if($this->config['peaks']['minimum']) {
			$tickCorrection = -$tickCorrection;
			$spacer = -3;
			$labelCorrection = $this->config['peaks']['tickLength']+15;
		}
		for($j = 0; $j<count($this->graphData['graphs']); $j++) {
			for($i = 0; $i<count($this->graphData['graphs'][$j]['peaks']); $i++)	{
				for($a=0; $a<$this->config['thickness']; $a++) {
					imgline($this->image, $this->config['axisOffset']['y']+$this->xGraphStart+$this->sign*$this->graphData['graphs'][$j]['peaks'][$i]['x']+$a,  $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->graphData['graphs'][$j]['peaks'][$i]['y']+$spacer), $this->config['axisOffset']['y']+$this->xGraphStart+$this->sign*$this->graphData['graphs'][$j]['peaks'][$i]['x']+$a,  $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$tickCorrection+$this->graphData['graphs'][$j]['peaks'][$i]['y']), $this->config['colors']['peaks']);
				}
				imagefttext($this->image, $this->config['textAttributes']['size']-1, 0, $this->config['axisOffset']['y']+$this->xGraphStart+$this->sign*$this->graphData['graphs'][$j]['peaks'][$i]['x'], $this->config['dimensions']['height']-($this->config['axisOffset']['x']+$this->graphData['graphs'][$j]['peaks'][$i]['y'])+$labelCorrection, $this->config['colors']['peaks'], $this->config['textAttributes']['font'], round($this->graphData['graphs'][$j]['peaks'][$i]['xOld'], $this->config['precision']['x']));
			}
		}
	}
	
	/*
	 * draws the legend
	 */
	private function drawLegend() {
		if($this->graphData['drawingStyle']==2) {
			$xLegendPos=50;
		}
		else {
			$xLegendPos=$this->config['dimensions']['width']-($this->config['margin']['right']+$this->config['legendOffset']);
		}
		//imagefttext($this->image, $this->config['textAttributes']['size'], 0, $xLegendPos, $this->config['margin']['top']+$this->config['textAttributes']['size'], $this->config['colors']['text'], $this->config['textAttributes']['font'], "Legend");
		for($j = 0; $j<count($this->graphData['graphNames']); $j++) {
			imagefttext($this->image, $this->config['textAttributes']['size']-1, 0, $xLegendPos, $this->config['margin']['top']+($j+1)*($this->config['textAttributes']['size']+2), $this->config['colors']['graphs'][$j], $this->config['textAttributes']['font'], $this->graphData['graphNames'][$j]);
		}
	}
	
	/*
	 * calculates the axis corrections
	 */
	private function calculateAxisCorrection() {
		$precision = strlen($this->graphData['extrema']['maxima']['x'])-strpos($this->graphData['extrema']['maxima']['x'], '.')-1;
		if($this->graphData['drawingStyle']==2) {
			if($this->graphData['extrema']['minima']['x']>0) {
				$axisPosition=$this->graphData['extrema']['minima']['x'];
			}
			else {
				$axisPosition=0;
			}
			$factor=ceil(($this->config['dimensions']['width']-($this->config['axisOffset']['y']+$this->config['margin']['right']))/$this->graphData['tickDistance']['x']);
			for($i=0; $i<50; $i++) {
				if(round($this->graphData['extrema']['minima']['x']+($factor-1)*$this->graphData['tickScale']['x']-$i*$this->graphData['tickScale']['x'], $precision)==round($axisPosition, $precision)) {
					$this->yAxisCorrection=$i*$this->graphData['tickDistance']['x'];
					break;
				}
				elseif(round($this->graphData['extrema']['minima']['x']+($factor-1)*$this->graphData['tickScale']['x']-$i*$this->graphData['tickScale']['x'], $precision)>round($axisPosition, $precision) && round($this->graphData['extrema']['minima']['x']+($factor-1)*$this->graphData['tickScale']['x']-($i+1)*$this->graphData['tickScale']['x'], $precision)<round($axisPosition, $precision)) {
					$this->yAxisCorrection=$i*$this->graphData['tickDistance']['x']+$this->graphData['tickDistance']['x']*($this->graphData['extrema']['minima']['x']+(($factor-1)-$i)*$this->graphData['tickScale']['x'])/$this->graphData['tickScale']['x'];
					break;
				}
			}
		}
		else {
			for($i=0; $i<50; $i++) {
				if($this->graphData['extrema']['maxima']['x']<0) {
					$this->yAxisCorrection=0;
					break;
				}
				elseif($this->graphData['extrema']['minima']['x']+$i*$this->graphData['tickScale']['x']==0) {
					$this->yAxisCorrection=$i*$this->graphData['tickDistance']['x'];
					break;
				}
				elseif($this->graphData['extrema']['minima']['x']+$i*$this->graphData['tickScale']['x']<0 && $this->graphData['extrema']['minima']['x']+($i+1)*$this->graphData['tickScale']['x']>0) {
					$this->yAxisCorrection=$i*$this->graphData['tickDistance']['x']+$this->graphData['tickDistance']['x']*abs($this->graphData['extrema']['minima']['x']+$i*$this->graphData['tickScale']['x'])/$this->graphData['tickScale']['x'];
					break;
				}
			}
		}

		for($i=0; $i<50; $i++) {
			if($this->graphData['extrema']['minima']['y']+$i*$this->graphData['tickScale']['y']==0) {
				$this->xAxisCorrection=$i*$this->graphData['tickDistance']['y'];
				break;
			}
			elseif($this->graphData['extrema']['minima']['y']+$i*$this->graphData['tickScale']['y']<0 && $this->graphData['extrema']['minima']['y']+($i+1)*$this->graphData['tickScale']['y']>0) {
				$this->xAxisCorrection=$i*$this->graphData['tickDistance']['y']+$this->graphData['tickDistance']['y']*abs($this->graphData['extrema']['minima']['y']+$i*$this->graphData['tickScale']['y'])/$this->graphData['tickScale']['y'];
				break;
			}
		}
	}
	
	/*
	 * calculates the x coordinate of the starting point of the graph to be drawn
	 */
	private function calculateXGraphStart() {
		if($this->graphData['drawingStyle']==2) {
			$this->sign=-1;
			$axisPosition=$this->graphData['extrema']['minima']['x'];

			$factor=ceil(($this->config['dimensions']['width']-($this->config['axisOffset']['y']+$this->config['margin']['right']))/$this->graphData['tickDistance']['x']);
			for($i=0; $i<50; $i++) {
				if($this->graphData['extrema']['minima']['x']+($factor-1)*$this->graphData['tickScale']['x']-$i*$this->graphData['tickScale']['x']==$axisPosition) {
					$this->xGraphStart=$i*$this->graphData['tickDistance']['x'];
					break;
				}
				elseif($this->graphData['extrema']['minima']['x']+($factor-1)*$this->graphData['tickScale']['x']-$i*$this->graphData['tickScale']['x']>$axisPosition && $this->graphData['extrema']['minima']['x']+($factor-1)*$this->graphData['tickScale']['x']-($i+1)*$this->graphData['tickScale']['x']<$axisPosition) {
					$this->xGraphStart=$i*$this->graphData['tickDistance']['x']+$this->graphData['tickDistance']['x']/2;
					break;
				}
			}
		}
	}
	
	/*
	 * gets the image as binary data stream
	 * returns the binary data stream
	 */
	public function getBinaryData() {
		ob_start();
		ImagePNG($this->image);
		return ob_get_clean();
	}

}
?>