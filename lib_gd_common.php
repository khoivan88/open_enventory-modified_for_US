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
$ttffontsize=10;
$ftfontsize=10;
$ttffontname="./lib/arial.ttf";
$ftfontname="./lib/arial.ttf";
$textType="ft";
define("max_image_size",2500);

function getAbsFontPath() {
	global $textType,$ttffontname,$ftfontname,$absFontPath;
	if (!$absFontPath) {
		switch (strToLower($textType)) {
		case "ttf":
			$absFontPath=realpath($ttffontname);
		break;
		default:
			$absFontPath=realpath($ftfontname);
		}
	}
	return $absFontPath;
}

function getEmptyImage($format="gif") { // return 1x1 transparent Gif
	$im=imgcreate(1,1,$format);
	switch ($format) {
	case "svg":
		return svg_header."<svg height=\"1px\" version=\"1.0\" width=\"1px\" xmlns=\"http://www.w3.org/2000/svg\"></svg>";
	break;
	case "png":
		//~ $im=imagecreate(1, 1);
		$white=ImageColorAllocate($im,255,255,255);
		imagecolortransparent($im,$white);
		ob_start();
		ImagePng($im);
		return ob_get_clean();
	break;
	case "gif":
	default:
		//~ $im=imagecreate(1, 1);
		$white=ImageColorAllocate($im,255,255,255);
		imagecolortransparent($im,$white);
		ob_start();
		ImageGif($im);
		return ob_get_clean();
	}
}

function imgcreate($width,$height,$format) {
	$width=constrainVal($width,1,max_image_size);
	$height=constrainVal($height,1,max_image_size);
	//~ die($width."x".$height);
	
	switch ($format) {
	case "svg":
	break;
	case "gif":
		return imagecreate($width,$height);
	break;
	default:
		$im=imagecreatetruecolor($width,$height);
		$white=imagecolorallocate($im,255,255,255); // bg
		imagefill($im,0,0,$white);
		return $im;
	}
}

function imgellipse(& $im,$x1,$y1,$x2,$y2,$color,$format="gif") {
	switch ($format) {
	case "png":
	case "gif":
		imageellipse($im,$x1,$y1,$x2,$y2,$color);
	break;
	case "svg":
		//~ if ($color=="") {
			//~ $im.="<clipPath id=\"Test\">";
		//~ }
		$im.="<ellipse cx=".fixStr($x1)." cy=".fixStr($y1)." rx=".fixStr($x2)." ry=".fixStr($y2)."/>";
		//~ if ($color=="") {
			//~ $im.="</clipPath>";
		//~ }
	break;
	}
}

function imgfilledellipse(& $im,$x1,$y1,$x2,$y2,$color,$format="gif") {
	switch ($format) {
	case "png":
	case "gif":
		imagefilledellipse($im,$x1,$y1,$x2,$y2,$color);
	break;
	case "svg":
		//~ if ($color=="") {
			//~ $im.="<clipPath id=\"Test\">";
		//~ }
		$im.="<ellipse cx=".fixStr($x1)." cy=".fixStr($y1)." rx=".fixStr($x2)." ry=".fixStr($y2);
		//~ if ($color!="") {
			$im.=" fill=".fixStr($color);
		//~ }
		$im.="/>";
		//~ if ($color=="") {
			//~ $im.="</clipPath>";
		//~ }
	break;
	}
}

function imgfilledrectangle(& $im,$x1,$y1,$x2,$y2,$color,$format="gif") {
	switch ($format) {
	case "png":
	case "gif":
		imagefilledrectangle($im,$x1,$y1,$x2,$y2,$color);
	break;
	case "svg":
		//~ if ($color=="") {
			//~ $im.="<clipPath id=\"Test\">";
		//~ }
		$im.="<rect x=".fixStr($x1-1)." y=".fixStr($y1)." width=".fixStr($x2-$x1)." height=".fixStr($y2-$y1);
		//~ if ($color!="") {
			$im.=" fill=".fixStr($color);
		//~ }
		$im.="/>";
		//~ if ($color=="") {
			//~ $im.="</clipPath>";
		//~ }
	break;
	}
}

function drawTextWithBG(& $im,$x,$y,$text,$textColor,$orientation=0,$bgColor=null,$size_scale=1,$format="gif") { // 0:links, 1: mitte, 2: rechts, 0: oben, 4: mitte, 8: unten
	if ($text.""==="") {
		return array(0,0);
	}
	list($w,$h)=getTextDimensions($text,$size_scale);
	if ($orientation & 1) {
		$xShift=0.5;
	}
	elseif ($orientation & 2) {
		$xShift=1;
	}
	else {
		$xShift=0;
	}
	if ($orientation & 4) {
		$yShift=0.5;
	}
	elseif ($orientation & 8) {
		$yShift=1;
	}
	else {
		$yShift=0;
	}
	
	if (!is_null($bgColor)) {
		imgfilledrectangle(
			$im,
			$x-$xShift*$w,
			$y-$yShift*$h,
			$x+(1-$xShift)*$w,
			$y+(1-$yShift)*$h,
			$bgColor,
			$format
		);
	}
	$retval=drawText($im,$x-$xShift*$w,$y+(1-$yShift)*$h,$textColor,$text,$size_scale,$format); // tatsächliche Dimensionen
	return array($w,$h);
}

function getTextDimensions($text,$size_scale=1) {
	global $textType,$ttffontsize,$ftfontsize;
	switch (strToLower($textType)) {
	case "ttf":
		$this_ttffontsize=$ttffontsize*$size_scale;
		$fontbox=imagettfbbox($this_ttffontsize,0,getAbsFontPath(),$text);
	break;
	default:
		$this_ftfontsize=$ftfontsize*$size_scale;
		$fontbox=imageftbbox($this_ftfontsize,0,getAbsFontPath(),$text);
		/* $retval=array(
			$retval2[0],$retval2[3],
			$retval2[2],$retval2[3], // OR
			$retval2[2],$retval2[1],
			$retval2[0],$retval2[1]); // UL */
	}
	//~ print_r($retval);die("X");
	return array(abs($fontbox[2]-$fontbox[0]),abs($fontbox[1]-$fontbox[7]));
}

function drawText(& $im,$x,$y,$color,$text,$size_scale=1,$format="gif") { // Molecule AND Barcode
	if ($text==="") {
		return array(0,0);
	}
	global $textType,$ttffontsize,$ftfontsize;
	switch ($format) {
	case "png":
	case "gif":
		switch (strToLower($textType)) {
		case "ttf":
			$this_ttffontsize=$ttffontsize*$size_scale;
			$fontbox=imagettftext($im,$this_ttffontsize,0,$x,$y,$color,getAbsFontPath(),$text);
		break;
		default:
			$this_ftfontsize=$ftfontsize*$size_scale;
			$fontbox=imagefttext($im,$this_ftfontsize,0,$x,$y,$color,getAbsFontPath(),$text);
			/* $retval=array(
				$retval2[0],$retval2[3],
				$retval2[2],$retval2[3], // OR
				$retval2[2],$retval2[1],
				$retval2[0],$retval2[1]); // UL */
		}
		$retval=array(abs($fontbox[2]-$fontbox[0]),abs($fontbox[1]-$fontbox[7]));
	break;
	case "svg":
		$im.="<text x=".fixStr($x-1)." y=".fixStr($y)." fill=".fixStr($color)." font-family=\"Arial,helvetica,sans-serif\" font-size=\"".($size_scale*9)."pt\">".$text."</text>"; //  text-anchor=\"middle\"
		$retval=getTextDimensions($text,$size_scale);
	break;
	}
	//~ print_r($retval);die("Y");
	return $retval;
}

function drawTextDown(& $im,$x,$y,$color,$text) { // Barcode
	global $textType,$ttffontsize,$ftfontsize,$width;
	switch (strToLower($textType)) {
	case "ttf":
		$retval=imagettftext($im,$ttffontsize*$width/40,270,$x,$y,$color,getAbsFontPath(),$text);
	break;
	default:
		$retval=imagefttext($im,$ftfontsize*$width/40,270,$x,$y,$color,getAbsFontPath(),$text);
		/* $retval=array(
			$retval2[0],$retval2[3],
			$retval2[2],$retval2[3], // OR
			$retval2[2],$retval2[1],
			$retval2[0],$retval2[1]); // UL */
	}
	return $retval;
}

function colorAlloc(& $im,$r,$g,$b,$format="gif") {
	$r=constrainVal($r,0,255);
	$g=constrainVal($g,0,255);
	$b=constrainVal($b,0,255);
	switch ($format) {
	case "png":
	case "gif":
		return ImageColorAllocate($im,$r,$g,$b); 
	break;
	case "svg":
		// return text with # in hex
		return "#".getHex($r).getHex($g).getHex($b);
	break;
	}
}

function getGrey(& $im,$percent,$format="gif") {
	$val=round(255*$percent);
	return colorAlloc($im,$val,$val,$val,$format);
}

function getColorIndex(& $im,$format="gif") {
	$retval=array();
	$retval["white"]=colorAlloc($im,255,255,255,$format); 
	$retval["black"]=colorAlloc($im,0,0,0,$format); 
	$retval["blue"]=colorAlloc($im,0,0,255,$format); 
	$retval["red"]=colorAlloc($im,255,0,0,$format); 
	$retval["green"]=colorAlloc($im,0,255,0,$format); 
	$retval["yellow"]=colorAlloc($im,191,191,0,$format); 
	$retval["pink"]=colorAlloc($im,255,0,255,$format); 
	$retval["turquoise"]=colorAlloc($im,0,255,255,$format); 
	$retval["gray"]=colorAlloc($im,127,127,127,$format);
	switch ($format) {
	case "png":
	case "gif":
		$retval["transparent"]=$retval["white"];
	break;
	case "svg":
		$retval["transparent"]=defBgColor; // bad workaround, find some better solution later
	break;
	}
	return $retval;
}

function imgline(& $im,$x1,$y1,$x2,$y2,$color,$width=1,$format="gif") {
	switch ($format) {
	case "png":
	case "gif":
		$width+=0;
		$width/=2;
		list($dx,$dy)=getLineTranslation($x1,$y1,$x2,$y2,$width);
		for ($a=-$width+0.5;$a<$width;$a+=0.5) {
			$dax=$a*$dx;
			$day=$a*$dy;
			return imageline($im,(int) round($x1+$dax),(int) round($y1-$day),(int) round($x2+$dax),(int) round($y2-$day),$color);
		}
	break;
	case "svg":
		$im.="<g stroke=".fixStr($color)." stroke-width=".fixStr($width).">
      <line x1=".fixStr($x1)." x2=".fixStr($x2)." y1=".fixStr($y1)." y2=".fixStr($y2)."/>
</g>";
	break;
	}
}

function imgfilledpolygon(& $im,$xypairs,$color,$format="gif") {
	$numpairs=count($xypairs);
	if ($numpairs==0 || $numpairs%2) {
		return;
	}
	switch ($format) {
	case "png":
	case "gif":
		$numpairs/=2;
		imagefilledpolygon($im,$xypairs,$numpairs,$color);
	break;
	case "svg":
		$xypairText="";
		for ($a=0;$a<$numpairs;$a++) {
			$xypairText.=$xypairs[$a];
			if ($a==$numpairs-1) {
				// letzter, nix tun
			}
			elseif ($a%2) { // ungerade eben hinzugefügt
				$xypairText.=" ";
			}
			else {
				$xypairText.=",";
			}
		}
		$im.="<polygon fill=".fixStr($color)." fill-rule=\"evenodd\" points=".fixStr($xypairText)." stroke=".fixStr($color)." stroke-width=\"1\"/>";
	break;
	}
}

function getLineTranslation($x1,$y1,$x2,$y2,$r) {
	$lineLength=hypot(($x2-$x1),($y2-$y1)); // Länge
	if ($lineLength) { // zero if 3d along z-axis
		$dx=$r*($y2-$y1)/$lineLength;
		$dy=$r*($x2-$x1)/$lineLength;
	}
	return array($dx,$dy);
}

function drawTranslatedLine(& $im,$x1,$y1,$x2,$y2,$color,$r,$format="gif") {
	// zeichnet in Bild im eine um r versetzte Linie von x1/y1 nach x2/y2 mit der farbe color
	list($dx,$dy)=getLineTranslation($x1,$y1,$x2,$y2,$r);
	$x1+=$dx;
	$y1-=$dy;
	$x2+=$dx;
	$y2-=$dy;
	imgline($im,$x1,$y1,$x2,$y2,$color,1,$format);
}

function drawSingleBond(& $im,$x1,$y1,$x2,$y2,$black,$scale,$format="gif") {
	drawTranslatedLine($im,$x1,$y1,$x2,$y2,$black,0,$format);
}

function drawDoubleBond(& $im,$x1,$y1,$x2,$y2,$black,$scale,$format="gif") {
	switch ($format) {
	case "svg":
		$im.="<g>";
	break;
	}
	
	drawTranslatedLine($im,$x1,$y1,$x2,$y2,$black,-2*$scale,$format);
	drawTranslatedLine($im,$x1,$y1,$x2,$y2,$black,2*$scale,$format);
	
	switch ($format) {
	case "svg":
		$im.="</g>";
	break;
	}
	
}

function drawDoubleBondR(& $im,$x1,$y1,$x2,$y2,$black,$scale,$format="gif") {
	switch ($format) {
	case "svg":
		$im.="<g>";
	break;
	}
	
	drawTranslatedLine($im,$x1,$y1,$x2,$y2,$black,0,$format); // main line
	$shorten=0.15;
	drawTranslatedLine($im,
	$x1*(1-$shorten)+$x2*$shorten,
	$y1*(1-$shorten)+$y2*$shorten,
	$x2*(1-$shorten)+$x1*$shorten,
	$y2*(1-$shorten)+$y1*$shorten,
	$black,4*$scale,$format); // side line
	
	switch ($format) {
	case "svg":
		$im.="</g>";
	break;
	}
}

function drawDoubleBondL(& $im,$x1,$y1,$x2,$y2,$black,$scale,$format="gif") {
	drawDoubleBondR($im,$x2,$y2,$x1,$y1,$black,$scale,$format);
}

function drawTripleBond(& $im,$x1,$y1,$x2,$y2,$black,$scale,$format="gif") {
	switch ($format) {
	case "svg":
		$im.="<g>";
	break;
	}
	
	drawTranslatedLine($im,$x1,$y1,$x2,$y2,$black,-3*$scale,$format);
	drawTranslatedLine($im,$x1,$y1,$x2,$y2,$black,0,$format);
	drawTranslatedLine($im,$x1,$y1,$x2,$y2,$black,3*$scale,$format);
	
	switch ($format) {
	case "svg":
		$im.="</g>";
	break;
	}
}

function drawUpLine(& $im,$x1,$y1,$x2,$y2,$color,$r,$format="gif") {
	// zeichnet UP-Bindung von x1/y1 nach x2/y2 mit der farbe color
	$lineLength=hypot(($x2-$x1),($y2-$y1)); // Länge
	$dx=$r*($y2-$y1)/$lineLength;
	$dy=$r*($x2-$x1)/$lineLength;
	imgfilledpolygon(
	$im,
	array(
		$x1,
		$y1,
		$x2+$dx,
		$y2-$dy,
		$x2-$dx,
		$y2+$dy
	),
	$color,$format);
}

function drawDownLine(& $im,$x1,$y1,$x2,$y2,$color,$r,$format="gif") {
	// zeichnet UP-Bindung von x1/y1 nach x2/y2 mit der farbe color
	$distance=3;
	$lineLength=hypot(($x2-$x1),($y2-$y1)); // Länge
	if ($lineLength==0) {
		return;
	}
	$dx=$r*($y2-$y1)/$lineLength;
	$dy=$r*($x2-$x1)/$lineLength;
	
	switch ($format) {
	case "svg":
		$im.="<g>";
	break;
	}
	
	for ($a=0;$a<=$lineLength;$a+=$distance) {
		$ratio=$a/$lineLength;
		imgline(
		$im,
		$x1*(1-$ratio)+$ratio*($x2+$dx),
		$y1*(1-$ratio)+$ratio*($y2-$dy),
		$x1*(1-$ratio)+$ratio*($x2-$dx),
		$y1*(1-$ratio)+$ratio*($y2+$dy),
		$color,
		1,
		$format);		
	}
	
	switch ($format) {
	case "svg":
		$im.="</g>";
	break;
	}
}

function drawEitherLine(& $im,$x1,$y1,$x2,$y2,$color,$r,$format="gif") {
	// zeichnet UP-Bindung von x1/y1 nach x2/y2 mit der farbe color
	$distance=1.5;
	$lineLength=hypot(($x2-$x1),($y2-$y1)); // Länge
	if ($lineLength==0) {
		return;
	}
	$dx=$r*($y2-$y1)/$lineLength;
	$dy=$r*($x2-$x1)/$lineLength;
	
	switch ($format) {
	case "svg":
		$im.="<g>";
	break;
	}
	
	for ($a=0;$a<=$lineLength;$a+=$distance) {
		$steps++;
		if ($steps % 2) {
			$ratio1=$a/$lineLength;
		}
		else {
			$ratio2=$a/$lineLength;
		}
		imgline(
		$im,
		$x1*(1-$ratio1)+$ratio1*($x2+$dx),
		$y1*(1-$ratio1)+$ratio1*($y2-$dy),
		$x1*(1-$ratio2)+$ratio2*($x2-$dx),
		$y1*(1-$ratio2)+$ratio2*($y2+$dy),
		$color,
		1,
		$format);		
	}
	
	switch ($format) {
	case "svg":
		$im.="</g>";
	break;
	}
}

?>