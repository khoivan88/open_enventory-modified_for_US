<?php
/*
Copyright 2006-2018 Felix Rudolphi and Lukas Goossen
open enventory is distributed under the terms of the GNU Affero General Public License, see COPYING for details. You can also find the license under http://www.gnu.org/licenses/agpl.txt

open enventory is a registered trademark of Felix Rudolphi and Lukas Goossen. Usage of the name "open enventory" or the logo requires prior written permission of the trademark holders. 

This file is part of open enventory.

open enventory is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

open enventory is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with open enventory.  If not, see <http://www.gnu.org/licenses/>.
*/

function getXrange($values) {
	$minX=array();
	$maxX=array();
	foreach($values as $idx => $this_values) {
		$x_values=array_keys($this_values);
		$minX[]=min($x_values);
		$maxX[]=max($x_values);
	}
	
	return array(min($minX),max($maxX));
}

function getYrange($values) {
	$minY=array();
	$maxY=array();
	foreach ($values as $trace_values) {
		$minY[]=min($trace_values);
		$maxY[]=max($trace_values);
	}
	
	return array(min($minY),max($maxY));
}

function getTickScale($delta,$no_ticks) {
	$tick_scale=pow(10,floor(log(2.5*abs($delta)/$no_ticks,10))); // increment between ticks, either 10.. or 20.. or 50..
	for ($a=0;$a<2;$a++) {
		if ($delta>$tick_scale*$no_ticks) {
			$tick_scale*=2;
		}
	}
	if ($delta<0) {
		$tick_scale*=-1;
	}
	return $tick_scale;
}

function prepareChromaGraph($values,& $paramHash) {
	list($paramHash["min_key"],$paramHash["max_key"])=getXrange($values);
	list($paramHash["min_y"],$paramHash["max_y"])=getYrange($values);
}

function getChromaGraphImg($values,$paramHash=array(),$peak_block_list=array()) {
	global $ttffontsize,$ttffontname;
		
	if (!count($values)) {
		return getEmptyImage($paramHash["format"]);
	}
	
	// process graph
	//~ list($minY,$maxY)=getYrange($values);
	$delta_y=$paramHash["max_y"]-$paramHash["min_y"];
	
	if ($delta_y<=0) {
		return getEmptyImage($paramHash["format"]);
	}
	
	// prepare image
	$width=& $paramHash["width"];
	$height=& $paramHash["height"];
	
	if (!isset($paramHash["min_x"]) && !isset($paramHash["max_x"])) { // autoscale
		$paramHash["min_x"]=$paramHash["min_key"];
		$paramHash["max_x"]=$paramHash["max_key"];
	}
	$delta_x=$paramHash["max_x"]-$paramHash["min_x"];
	
	if ($delta_x<=0) {
		return getEmptyImage($paramHash["format"]);
	}
	
	// validate parameters
	if ($width<100) {
		$width=800;
	}
	if ($height<100) {
		$height=600;
	}
	
	$im=imagecreate($width,$height);
	$white=imagecolorallocate($im,255,255,255); // bg
	$black=imagecolorallocate($im,0,0,0); // axis & text
	$green=imagecolorallocate($im,0,255,0); // peaks
	$color=array(
		imagecolorallocate($im,0,0,255),
		imagecolorallocate($im,0,255,0),
		imagecolorallocate($im,255,0,0),
		imagecolorallocate($im,0,255,255),
		imagecolorallocate($im,255,0,255),
		imagecolorallocate($im,255,255,0),
	);
	
	// draw scale
	$no_big_ticks=15; // approximately
	$smalltick_size=2;
	$bigtick_size=6;
	$ms_threshold=0.4;
	
	$x0=10;
	$x1=$width-$x0;
	$shift_x=1;
	if ($paramHash["right_to_left"]) {
		$x0=$x1;
		$shift_x=-1;
	}
	
	$y1=25;
	$y0=$height-$y1; // always at the bottom
	imageline($im,0,$y0,$width,$y0,$black); // x waagerecht
	
	// Skalen auf x
	$tick_scale=getTickScale($delta_x,$no_big_ticks);
	for ($a=0;$a<=$delta_x;$a+=$tick_scale) {
		$xpos=$x0+$width*$shift_x*$a/$delta_x;
		imagettftext($im,$ttffontsize,0,$xpos-12+6*$shift_x,$y0+17,$black,$ttffontname,round($paramHash["min_x"]+$a,1)); // text
		imageline($im,$xpos,$y0,$xpos,$y0+$bigtick_size,$black); // big
		
		$xpos=$x0+$width*$shift_x*($a-0.5*$tick_scale)/$delta_x;
		imageline($im,$xpos,$y0,$xpos,$y0+$smalltick_size,$black); // small
	}
	imagettftext($im,$ttffontsize,0,$x0+$width*$shift_x/2,$y0+22,$black,$ttffontname,$paramHash["unit"]);
	
	if (!empty($paramHash["text"])) {
		imagettftext($im,$ttffontsize,0,5,25,$black,$ttffontname,$paramHash["text"]);
	}
	
	// Skalen auf y
	if (isset($paramHash["unit_y"])) {
		imageline($im,$x0,0,$x0,$height,$black); // y senkrecht
		$tick_scale=getTickScale($delta_y,$no_big_ticks);
		for ($a=0;$a<=$delta_x;$a+=$tick_scale) {
			$ypos=$y0*($paramHash["max_y"]-$a)/$delta_y;
			imagettftext($im,$ttffontsize,0,$x0-20+10*$shift_x,$ypos+8,$black,$ttffontname,$a); // text
			imageline($im,$x0,$ypos,$x0+$bigtick_size,$ypos,$black); // big
			$ypos=$y0*($paramHash["max_y"]-$a-0.5*$tick_scale)/$delta_y;
			imageline($im,$x0,$ypos,$x0+$smalltick_size,$ypos,$black); // small
		}
		imagettftext($im,$ttffontsize,0,$x0+12*$shift_x,$height/2,$black,$ttffontname,$paramHash["unit_y"]);
	}
	
	// draw graph
	$fac=1;
	if ($paramHash["max_y"]>$paramHash["min_y"]) {
		$fac=1/$delta_y;
	}
	$colornum=0;
	$legend_x=0;
	$legend_y=10;
	
	switch ($paramHash["style"]) {
	case "ms":
		foreach($values as $idx => $this_values) {
			$x_fac=($shift_x*$x1)/$delta_x;
			foreach ($this_values as $xval => $yval) {
				$xpos=$x0+($xval-$paramHash["min_x"])*$x_fac;
				$ypos=$y0*(($paramHash["min_y"]-$yval)*$fac+1);
				imageline($im,$xpos+$colornum,$ypos,$xpos+$colornum,$y0,$color[$colornum]); // have 1px distance between multiple +$colornum
				if ($yval*$fac>$ms_threshold) { // label
					imagettftext($im,$ttffontsize,0,$xpos,$ypos+10,$black,$ttffontname,$xval); // text
				}
			}
			$colornum++;
		}
	break;
	case "chroma":
	default:
		foreach($values as $idx => $this_values) {
			unset($prevxpos);
			unset($prevypos);
			$xpos=$x0;
			$x_fac=($shift_x*$x1)/count($this_values);
			foreach ($this_values as $yval) {
				$xpos+=$x_fac;
				$ypos=$y0*(($paramHash["min_y"]-$yval)*$fac+1);
				if (isset($prevxpos)) { // && ($idx+$xpos)%2
					imageline($im,$xpos,$ypos,$prevxpos,$prevypos,$color[$colornum]);
				}
				
				$prevxpos=$xpos;
				$prevypos=$ypos;
			}
			
			// draw legend
			if (isset($paramHash["trace_names"][$colornum])) {
				$fontbox=imagettftext($im,$ttffontsize,0,$legend_x,$legend_y,$color[$colornum],$ttffontname,$paramHash["trace_names"][$colornum]);
				$legend_y+=abs($fontbox[1]-$fontbox[7]);
			}
			$colornum++;
		}
		// ticks
		foreach ($peak_block_list as $idx => $this_peak_block_list) {
			$block_count=count($values[$idx]);
			if (is_array($this_peak_block_list)) foreach ($this_peak_block_list as $block_no) {
				// get ypos for this block
				$xpos=$x0+$shift_x*$x1*$block_no/$block_count;
				$yval=$values[$idx][$block_no];
				$ypos=constrainVal($y0*(($paramHash["min_y"]-$yval)*$fac+1),$y1,$y0);
				imageline($im,$xpos,$ypos+5,$xpos,$ypos+15,$green);
				
				// labels
				if (isset($paramHash["label_distance"]) && (abs($xpos-$last_label_x)>$paramHash["label_distance"] || abs($ypos-$last_label_y)>15)) {
					$last_label_x=$xpos;
					$last_label_y=$ypos;
					$xval=round($paramHash["min_x"]+$delta_x*$block_no/$block_count,0);
					$text_xpos=constrainVal($xpos,$x0+10,$x1-10);
					$text_ypos=constrainVal($ypos+10,$y1,$y0);
					imagettftext($im,$ttffontsize,0,$xpos,$text_ypos,$green,$ttffontname,$xval); // text
				}
			}
		}
	}
	imagecolortransparent($im,$white);
	ob_start();
	switch (strtolower($paramHash["format"])) {
	case "":
	case "gif":
		imagegif($im); 
	break;
	case "jpg":
	case "jpeg":
		imagejpeg($im); 
	break;
	case "png":
		imagepng($im); 
	}
	return ob_get_clean();
}

function getScaledImg($img_data,$paramHash=array()) { // if one is <1 preserve scale, if both return src
	$width=ifempty($paramHash["width"],0);
	$height=ifempty($paramHash["height"],0);
	$format=$paramHash["format"];
	
	$im2=@imagecreatefromstring($img_data);
	if ($im2===FALSE) {
		//~ return $img_data;
		return getEmptyImage();
	}
	// clipping of original img, clipLeft,clipTop,clipWidth,clipHeight
	
	
	$old_width=imagesx($im2);
	$old_height=imagesy($im2);
	
	if (isset($paramHash["clipWidth"])) {
		$old_width=$paramHash["clipWidth"];
	}
	
	if (isset($paramHash["clipHeight"])) {
		$old_height=$paramHash["clipHeight"];
	}
	
	if ($old_width<1 || $old_height<1) { // nonsense
		return;
	}
	if ($width<1 && $height<1) {
		$width=$old_width;
		$height=$old_height;
	}
	elseif ($width<1) {
		$width=$old_width/$old_height*$height;
	}
	elseif ($height<1) {
		$height=$old_height/$old_width*$width;
	}
	
	switch (strtolower($format)) {
	case "gif":
		$im=imagecreate($width, $height);
	break;
	case "jpg":
	case "jpeg":
	case "png":
	default:
		$im=imagecreatetruecolor($width, $height);
	}
	
	imagecopyresampled($im,$im2,0,0,max(0,intval($paramHash["clipLeft"])),max(0,intval($paramHash["clipTop"])),$width, $height,$old_width,$old_height);
	ob_start();
	switch (strtolower($format)) {
	case "gif":
		imagegif($im); 
	break;
	case "jpg":
	case "jpeg":
		imagejpeg($im); 
	break;
	case "png":
	default:
		imagepng($im); 
	}
	return ob_get_clean();
}

// OO spectrum image creator
class gdImage {
	private $im;
	private $width;
	private $height;
	private $format;
}

class specImage extends gdImage {
	private $ttffontname="lib/arial.ttf";
	private $ttffontsize=10;
	
	/* private $im;
	private $width;
	private $height;
	private $format;*/
	private $colors=array();
	
	// für alle Kurven
	public $min_x;
	public $max_x;
	private $min_y;
	private $max_y;
	
	// set directly
	public $unit_x;
	public $unit_y;
	public $decimals_x=1;
	public $decimals_y=1;
	public $margin_left=0;
	public $margin_right=0;
	public $margin_top=0;
	public $margin_bottom=0;
	
	// to flip img
	public $shift_x=1;
	public $shift_y=1;
	
	public $no_big_ticks=15;
	public $bigtick_size=6;
	public $smalltick_size=2;
	public $ms_threshold=40; // %
	
	private $values;
	public $peaks=array();
	public $xy_data=array();
	public $trace_names;
	
	// colors
	private $white;
	private $black;
	private $green;
	private $color;
	
	function specImage($values,$type="chroma",$width="",$height="",$format="") {
		global $analytics_img_params;
		
		$this->values=$values;
		$this->type=$type;
		$this->width=ifempty($width,$analytics_img_params["width"]);
		$this->height=ifempty($height,$analytics_img_params["height"]);
		$this->format=ifempty($format,$analytics_img_params["format"]);
		$this->im=imagecreate($width,$height);
		$this->initColors();
		$this->autoRange();
	}
	
	function initColors() {
		$this->white=imagecolorallocate($this->im,255,255,255); // bg
		$this->black=imagecolorallocate($this->im,0,0,0); // axis & text
		$this->green=imagecolorallocate($this->im,0,255,0); // peaks
		$this->color=array(
			imagecolorallocate($this->im,0,0,255),
			imagecolorallocate($this->im,0,255,0),
			imagecolorallocate($this->im,255,0,0),
			imagecolorallocate($this->im,0,255,255),
			imagecolorallocate($this->im,255,0,255),
			imagecolorallocate($this->im,255,255,0),
		);
	}
	
	function getImage() {
		imagecolortransparent($this->im,$this->white);
		ob_start();
		switch (strtolower($this->format)) {
		case "gif":
			imagegif($this->im); 
		break;
		case "jpg":
		case "jpeg":
			imagejpeg($this->im); 
		break;
		case "png":
		default:
			imagepng($this->im); 
		}
		return ob_get_clean();
	}
	
	function calcXYData($type,$idx=0) {
		switch ($type) {
		case "ir":
			$fac=($this->max_x-$this->min_x)/count($this->values[$idx]);
			
			for ($a=0;$a<count($this->values[$idx]);$a++) {
				if ($values[$idx][$a]>10) { // only relevant peaks
					$this->xy_data[]=array(
						"x" => $this->min_x+$fac*$a, 
						"y" => $this->values[$idx][$a], 
					);
				}
			}
		break;
		case "ms":
			for ($a=0;$a<count($this->values["y"]);$a++) {
				if ($values["y"][$a]>10) { // only relevant peaks
					$this->xy_data[]=array(
						"x" => $this->values["x"][$a], 
						"y" => $this->values["y"][$a], 
					);
				}
			}
		break;
		}
	}
	
	function getPeaksText($type,$idx=0) {
		$peaks_interpretation=array();
		switch ($type) {
		case "ir":
			$fac=($this->max_x-$this->min_x)/count($this->values[$idx]);
			foreach ($this->peaks[$idx] as $peak_idx) {
				$x=round($this->min_x+$fac*$peak_idx,0);
				$peaks_interpretation[]=$x." (".getIRInt($this->values[$idx][ $peak_idx ]).")";
			}
			$retval.=join(", ",$peaks_interpretation);
			return $retval;
		break;
		case "ms":
			$idx="y";
			foreach ($this->peaks[$idx] as $peak_idx) {
				$peaks_interpretation[]=$this->values["x"][ $peak_idx ]." (".round($this->values[$idx][ $peak_idx ]).")";
			}
			$retval.=join(", ",$peaks_interpretation);
			return $retval;
		break;
		}
	}
	
	function autoPickPeaks($idx,$paramHash=array()) { // use "y" for MS
		$this->peaks[$idx]=getPeakList($this->values[$idx],$paramHash);
	}
	
	function normalizeChromas() {
		if ($this->max_y==0) {
			return;
		}
		$fac=100/$this->max_y;
		for ($idx=0;$idx<count($this->values);$idx++) {
			array_mult_byref($this->values[$idx],$fac);
		}
		$this->max_y=100;
		$this->min_y*=$fac;
	}
	
	function normalizeMS() {
		if ($this->max_y<=0) {
			return;
		}
		$fac=100/$this->max_y;
		array_mult_byref($this->values["y"],$fac);
		$this->max_y=100;
		$this->min_y*=$fac;
	}

	function autoRangeChroma() { // series of values + min/max_x
		$minX=array();
		$maxX=array();
		$minY=array();
		$maxY=array();
		foreach($this->values as $idx => $values) {
			$x_values=array_keys($values);
			$minX[]=min($x_values);
			$maxX[]=max($x_values);
			$minY[]=min($values);
			$maxY[]=max($values);
		}
		$this->min_x=min($minX);
		$this->max_x=max($maxX);
		$this->min_y=min($minY);
		$this->max_y=max($maxY);
	}
	
	function autoRangeMS() { // "x" and "y", only one spectrum
		$this->min_x=min($this->values["x"]);
		$this->max_x=max($this->values["x"]);
		$this->min_y=min($this->values["y"]);
		$this->max_y=max($this->values["y"]);
	}
	
	function autoRange() {
		switch ($this->type) {
		case "chroma":
			$this->autoRangeChroma();
		break;
		case "ms":
			$this->autoRangeMS();
		break;
		}
	}
	
	function getRelX($x) {
		// x=0 => margin_left
		// x=1 => width-margin_right
		if ($this->shift_x==-1) {
			$x=1-$x;
			// x=1 => margin_left
			// x=0 => width-margin_right
		}
		return $this->margin_left+($this->width-$this->margin_right-$this->margin_left)*$x;
	}
	
	function getRelY($y) {
		// y=1 => height-margin_bottom
		// y=0 => margin_top
		if ($this->shift_y==1) {
			$y=1-$y;
			// y=0 => height-margin_bottom
			// y=1 => margin_top
		}
		return $this->margin_top+($this->height-$this->margin_bottom-$this->margin_top)*$y;
	}
	
	function drawXaxisIntoImage($ypos="") {
		if ($ypos==="") {
			$ypos=-$this->margin_bottom;
		}
		if ($ypos<0) {
			$ypos+=$this->height;
		}
		
		imageline($this->im,$this->margin_left,$ypos,$this->width-$this->margin_right,$ypos,$this->black); // x waagerecht
		$delta_x=$this->max_x-$this->min_x;
		
		// Skalen auf x
		$tick_scale=getTickScale($delta_x,$this->no_big_ticks);
		for ($a=0;$a<=$delta_x;$a+=$tick_scale) {
			$xpos=$this->getRelX($a/$delta_x);
			imageline($this->im,$xpos,$ypos,$xpos,$ypos+$this->bigtick_size,$this->black); // big
			imagettftext($this->im,$this->ttffontsize,0,$xpos-12+6*$this->shift_x,$ypos+17,$this->black,$this->ttffontname,round($this->min_x+$a,$this->decimals_x)); // text
			
			$xpos=$this->getRelX(($a-0.5*$tick_scale)/$delta_x);
			imageline($this->im,$xpos,$ypos,$xpos,$ypos+$this->smalltick_size,$this->black); // small
		}
		imagettftext($this->im,$this->ttffontsize,0,$this->getRelX(0.5),$ypos+22,$this->black,$this->ttffontname,$this->unit_x);
	}

	function drawYaxisIntoImage($xpos="") {
		if ($xpos==="") {
			$xpos=-$this->margin_right;
		}
		if ($xpos<0) {
			$xpos+=$this->width;
		}
		
		imageline($this->im,$xpos,$this->margin_top,$xpos,$this->height-$this->margin_bottom,$this->black); // y senkrecht
		$delta_y=$this->max_y-$this->min_y;
		
		$tick_scale=getTickScale($delta_y,$this->no_big_ticks);
		for ($a=0;$a<=$delta_y;$a+=$tick_scale) {
			$ypos=$this->getRelY(($a-$this->min_y)/$delta_y);
			imagettftext($this->im,$this->ttffontsize,0,$xpos+10*$this->shift_x,$ypos+8,$this->black,$this->ttffontname,$a); // text
			imageline($this->im,$xpos,$ypos,$xpos+$this->bigtick_size,$ypos,$this->black); // big
			
			$ypos=$this->getRelY(($a+0.5*$tick_scale)/$delta_y);
			imageline($this->im,$xpos,$ypos,$xpos+$this->smalltick_size,$ypos,$black); // small
		}
		imagettftext($this->im,$this->ttffontsize,0,$xpos+12*$this->shift_x,$this->getRelY(0.5),$this->black,$this->ttffontname,$this->unit_y);
	}

	function drawChroma($idx,$color_index) {
		$count=count($this->values[$idx]);
		if (!$count) {
			return;
		}
		
		$delta_y=$this->max_y-$this->min_y;
		//~ die($this->min_y."_".$this->max_y."/".min($this->values[$idx])."_".max($this->values[$idx]));
		$a=0;
		foreach ($this->values[$idx] as $yval) {
			$xpos=$this->getRelX($a/$count);
			$ypos=$this->getRelY(($yval-$this->min_y)/$delta_y);
			if (isset($prevxpos)) { // && ($idx+$xpos)%2
				imageline($this->im,$xpos,$ypos,$prevxpos,$prevypos,$this->color[$color_index]);
			}
			
			$a++;
			$prevxpos=$xpos;
			$prevypos=$ypos;
		}
	}
	
	function drawPeaks($idx) { // $this->peaks has list of x-values
		$delta_x=$this->max_x-$this->min_x;
		$delta_y=$this->max_y-$this->min_y;
		
		// ticks
		$block_count=count($this->values[$idx]);
		
		if (is_array($this->peaks[$idx])) foreach ($this->peaks[$idx] as $block_no) {
			// get ypos for this block
			$xpos=$this->getRelX($block_no/$block_count);
			$yval=$this->values[$idx][$block_no];
			$ypos=constrainVal(
				$this->getRelY(($yval-$this->min_y)/$delta_y), 
				$this->margin_top, 
				($this->height-$this->margin_bottom)
			);
			imageline($this->im,$xpos,$ypos-$this->shift_y*5,$xpos,$ypos-$this->shift_y*15,$this->green);
			
			// labels
			if (isset($this->label_distance) && (abs($xpos-$last_label_x)>$this->label_distance || abs($ypos-$last_label_y)>15)) {
				$last_label_x=$xpos;
				$last_label_y=$ypos;
				$xval=round($this->min_x+$delta_x*$block_no/$block_count,0);
				$text_xpos=constrainVal($xpos,$this->margin_left+10,($this->width-$this->margin_right)-10);
				$text_ypos=constrainVal(
					$ypos-$this->shift_y*10,
					$this->margin_top, 
					($this->height-$this->margin_bottom)
				);
				imagettftext($this->im,$this->ttffontsize,0,$xpos,$text_ypos,$this->green,$this->ttffontname,$xval); // text
			}
		}
	}
	
	function drawIntegrals() { // for NMR
		
	}
	
	function drawText($text,$x="",$y="") {
		imagettftext($this->im,$this->ttffontsize,0,intval($x),intval($y)+15,$this->black,$this->ttffontname,$text);
	}
	
	function drawChromas() {
		$legend_x=0;
		$legend_y=10;
		for ($idx=0;$idx<count($this->values);$idx++) {
			$this->drawChroma($idx,$idx);
			
			// draw legend
			if (isset($this->trace_names[$idx])) {
				$fontbox=imagettftext($this->im,$this->ttffontsize,0,$legend_x,$legend_y,$this->color[$idx],$this->ttffontname,$this->trace_names[$idx]);
				$legend_y+=abs($fontbox[1]-$fontbox[7]);
			}
			$idx++;
		}
	}

	function drawMS($color_index=0) { // only one
		$delta_x=$this->max_x-$this->min_x;
		$delta_y=$this->max_y-$this->min_y;
		
		if (!$delta_x || !$delta_y) {
			return;
		}
		
		$y0=$this->getRelY(0);
		
		for ($a=0;$a<count($this->values["y"]);$a++) {
			$xval=$this->values["x"][$a];
			$yval=$this->values["y"][$a];
			$xpos=$this->getRelX(($xval-$this->min_x)/$delta_x);
			$ypos=$this->getRelY(($yval-$this->min_y)/$delta_y);
			imageline($this->im,$xpos,$ypos,$xpos,$y0,$this->color[$color_index]); // have 1px distance between multiple +$colornum
			
			if ($yval>$this->ms_threshold) { // label
				imagettftext($this->im,$this->ttffontsize,0,$xpos,$ypos+10,$this->black,$this->ttffontname,$xval); // text
			}
		}
	}
	
}
?>