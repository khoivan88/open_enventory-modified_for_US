<?php

abstract class IRconverter extends converter {

	/*
	 * produces the interpretation string
	 */
	public function produceInterpretation() {
		// gets the peaks and their intensity and save them as interpretation
		$interpretationString="ñ=";
		for($i=0; $i<count($this->graphData['graphs'][0]['peaks']); $i++) {
			if($this->graphData['drawingStyle'] == 2) {
				$relativeIntensity = ($this->graphData['extrema']['maxima']['y']-$this->graphData['graphs'][0]['peaks'][$i]['y'])/($this->graphData['extrema']['maxima']['y']-$this->graphData['extrema']['minima']['y']);
			}
			else {
				$relativeIntensity = ($this->graphData['extrema']['minima']['y']+$this->graphData['graphs'][0]['peaks'][$i]['y'])/($this->graphData['extrema']['maxima']['y']-$this->graphData['extrema']['minima']['y']);
			}
			if ($relativeIntensity>0.9) {
				$interpretation="vs";
			}
			elseif ($relativeIntensity>0.75) {
				$interpretation="s";
			}
			elseif ($relativeIntensity>0.5) {
				$interpretation="m";
			}
			else 
			{
				$interpretation="w";
			}
			if($i==0) {
				$interpretationString=$interpretationString.round($this->graphData['graphs'][0]['peaks'][$i]['x'],0)." (".$interpretation.")";	
			}
			else {
				$interpretationString=$interpretationString.", ".round($this->graphData['graphs'][0]['peaks'][$i]['x'],0)." (".$interpretation.")";
			}
		}
		$this->graphData['interpretation']=$interpretationString." 1/cm";
	}
}