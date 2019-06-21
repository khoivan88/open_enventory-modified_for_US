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
require_once "lib_gd_common.php";
require_once "lib_molecule_geometry.php";

function getReactionGif($reaction,$minX,$minY,$aspectRatio,$scale,$flags=0,$format=array("png") ) { // $flags: 1: Color, 2: Identifier, 4: stöchiometrische Koeff, 8: SVG
	global $fontsize,$fontname,$g_settings;
	$margin=struc_margin; //px
	$arrowlength=60*$scale;
	$plussize=7*$scale;
	
	$format_count=count($format);
	if ($format_count==0) {
		return;
	}
	$retval=array_fill(0,$format_count,"");
	
	// bei $format_count==1 nur den String zurückgeben
	
	// see http://bugs.php.net/bug.php?id=12775
	$reaction=unserialize(serialize($reaction)); // fix PHP Bug that leads to object corruption though passed byval
	// serialisierung erhält referenz von bondsFromNeighbours
	
	$reaction["reactants"]+=0;
	$reaction["products"]+=0;
	if (!count($reaction["molecules"]) || ($reaction["reactants"]<1 && $reaction["products"]<1) || count($reaction["molecules"])!=$reaction["reactants"]+$reaction["products"]) {
		foreach($format as $idx => $ext) {
			$retval[$idx]=getEmptyImage($ext);
		}
		if ($format_count==1) {
			return $retval[0];
		}
		return $retval;
	}
	
	// leere Teile herausnehmen, Zuordnung der Bezeichner und stöch Faktoren erhalten
	for ($a=count($reaction["molecules"])-1;$a>=0;$a--) {
		if (count($reaction["molecules"][$a]["atoms"])>0) {
			$atomsFound=true;
		}
		else {
			// rausnehmen
			array_splice($reaction["molecules"],$a,1);
			if (is_array($reaction["identifier"])) {
				array_splice($reaction["identifier"],$a,1);
			}
			if (is_array($reaction["stoch_coeff"])) {
				array_splice($reaction["stoch_coeff"],$a,1);
			}
			if ($a<$reaction["reactants"]) {
				$reaction["reactants"]--;
			}
			else {
				$reaction["products"]++;
			}
		}
	}
	
	if (!$atomsFound) {
		foreach($format as $idx => $ext) {
			$retval[$idx]=getEmptyImage($ext);
		}
		if ($format_count==1) {
			return $retval[0];
		}
		return $retval;
	}
	
	$reaction["molecules"]=array_values($reaction["molecules"]);
	if (is_array($reaction["identifier"])) {
		$reaction["identifier"]=array_values($reaction["identifier"]);
	}
	if (is_array($reaction["stoch_coeff"])) {
		$reaction["stoch_coeff"]=array_values($reaction["stoch_coeff"]);
	}
	
	// für alle Moleküle Dimensionen holen
	//~ $molscale=getMolscale($reaction);
	$molscale=1;
	//~ normaliseReaction($reaction); // can we save time here as normalisation is already done when reading the rxn?
	
	$totalHeight=$plussize;
	for ($a=0;$a<count($reaction["molecules"]);$a++) {
		$reaction["molecules"][$a]["dimensions"]=getMoleculeDimensions($reaction["molecules"][$a],$scale*$molscale,$margin);
		// print_r($reaction["molecules"][$a]["dimensions"]);
		if ($flags & 4) {
			list($stoichW,$stoichH)=getTextDimensions($reaction["stoch_coeff"][$a],font_scale);
			$textHeight=max($textHeight,$stoichH);
			if ($g_settings["stoich_in_line"] && $stoichW>0) {
				$totalWidth+=$stoichW+$margin;
			}
		}
		$totalWidth+=$reaction["molecules"][$a]["dimensions"][4];
		$totalHeight=max($totalHeight,$reaction["molecules"][$a]["dimensions"][5]);
	}
	
	if ($flags & 4 && !$g_settings["stoich_in_line"]) {
		$totalHeight+=$textHeight+1.5*$margin;
	}
	
	$totalHeight+=2*$margin;
	$maxHeight=$totalHeight;
	if ($flags & 2) { // hier für Identifier Höhe hinzufügen
		$totalHeight+=$textHeight+2.5*$margin;
	}
	$totalWidth+=(count($reaction["molecules"])*2+1)*$margin+$arrowlength+$plussize*($reaction["reactants"]+$reaction["products"]-2); // Zwischenraum + Pfeil + Plus
	// echo "<br>".$totalWidth." ".$totalHeight."<br>";
	// ggf. zentrieren (dürfte in der Praxis kaum auftreten)
	$xOffset=0;
	$yOffset=0;
	if ($totalHeight<$minY) {
		$yOffset=($minY-$totalHeight)*0.5;
		$totalHeight=$minY;
	}
	$Xmargin=$margin;
	if ($totalWidth<$minX) {
		$Xmargin=($minX-$totalWidth)/(count($reaction["molecules"])*2+1);
		$totalWidth=$minX;
	}
	
	// Seitenverhältnis anpassen
	if ($aspectRatio==0 && $minX>0 && $minY>0) { // use minX,Y for aspect ratio
		$aspectRatio=$minX/$minY;
	}
	
	if ($aspectRatio>0) { // Höhe oder Breite vergrößern, um Seitenverhältnis zu erreichen
		if ($totalWidth/$totalHeight>$aspectRatio) {
			$totalHeight=$totalWidth/$aspectRatio;
		}
		else {
			$totalWidth=$aspectRatio*$totalHeight;
		}
	}
	
	foreach($format as $idx => $ext) {
		$xOffset_arr[$idx]=$xOffset; // byref!!
		$retval[$idx]=imgcreate($totalWidth,$totalHeight,$ext);
		switch ($ext) {
		case "svg":
			$colorIndex[$idx]=getColorIndex($retval[$idx],$ext);
			$retval[$idx].=svg_header."<svg height=\"".$totalHeight."\" width=\"".$totalWidth."\" viewBox=\"0 0 ".$totalWidth." ".$totalHeight."\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">
<script>
<![CDATA[
function followLink() {
	var url=document.defaultView.frameElement.getAttribute(\"href\");
	if (parent && url) {
		parent.location.href=url;
	}
}
]]>
</script><a xlink:href=\"javascript:followLink()\"><rect x=\"0\" y=\"0\" height=\"".$totalHeight."\" width=\"".$totalWidth."\" fill=".fixStr($colorIndex[$idx]["transparent"])."/>";
		break;
		case "png":
		case "gif":
			// Bild erzeugen
			$colorIndex[$idx]=getColorIndex($retval[$idx],$ext);
		break;
		}
		
		// Edukte reinmalen
		for ($a=0;$a<count($reaction["molecules"]);$a++) {
			if ($a==$reaction["reactants"]) {
				paintArrowIntoImage(
					$retval[$idx],
					$xOffset_arr[$idx],
					0.5*$totalHeight,
					$arrowlength,
					$Xmargin,
					$colorIndex[$idx]["black"],
					$ext
				); // ---> malen
			}
			elseif ($a!=0) {
				paintPlusIntoImage(
					$retval[$idx],
					$xOffset_arr[$idx],
					0.5*$totalHeight,
					$plussize,
					$Xmargin,
					$colorIndex[$idx]["black"],
					$ext
				); // + malen
			}
			// hier den stöchiometrischen Koeffizienten für das nachfolgende Molekül zeichnen
			if ($flags & 4) {
				if ($g_settings["stoich_in_line"]) {
					$xOffset_arr[$idx]+=$Xmargin;
					$stoichX=$xOffset_arr[$idx];
					$stoichY=0.5*($totalHeight+$textHeight);
				}
				else {
					$stoichX=$xOffset_arr[$idx]+0.5*($reaction["molecules"][$a]["dimensions"][4]-$margin);
					$stoichY=0.5*($maxHeight+$totalHeight)-$margin;
				}
				paintStoichCoeff(
					$retval[$idx],
					$stoichX,
					$stoichY,
					$reaction["stoch_coeff"][$a],
					0,
					$colorIndex[$idx]["black"],
					$ext
				);
			}
			if ($flags & 2) { // hier den Identifier mittig unter das Molekül zeichnen
				drawText(
					$retval[$idx],
					$xOffset_arr[$idx]+0.5*($reaction["molecules"][$a]["dimensions"][4]-$margin),
					0.5*($maxHeight+$totalHeight)+$textHeight,
					$colorIndex[$idx]["black"],
					$reaction["identifier"][$a],
					font_scale,
					$ext
				);
			}
			paintMoleculeIntoImage(
				$retval[$idx],
				$colorIndex[$idx],
				$reaction["molecules"][$a],
				$xOffset_arr[$idx],
				0.5*($totalHeight-$reaction["molecules"][$a]["dimensions"][5]),
				$reaction["molecules"][$a]["dimensions"][0],
				$reaction["molecules"][$a]["dimensions"][3],
				$reaction["molecules"][$a]["dimensions"][4],
				$reaction["molecules"][$a]["dimensions"][5],
				$scale,
				$molscale,
				$margin,
				($flags & 1),
				$Xmargin,
				$ext
			);
			// echo $xOffset."<br>";
		}
		
		switch ($ext) {
		case "svg":
			$retval[$idx].="</a></svg>";		
		break;
		default:
			// Bild erzeugen
			$retval[$idx]=getImage($retval[$idx],$colorIndex[$idx]["white"],$ext);
		}
	}
	
	// Bild zurückgeben
	if ($format_count==1) {
		return $retval[0];
	}
	return $retval;	
}

function formatStoichCoeff($stoich_coeff) {
	if ($stoich_coeff==1 || $stoich_coeff=="") {
		return "";
	}
	return roundLJ($stoich_coeff);
}

function paintStoichCoeff(& $im,& $xOffset,$yOffset,$stoich_coeff,$margin,$color,$format="png") {
	$stoich_coeff=formatStoichCoeff($stoich_coeff);
	$dimensions=drawText($im,$xOffset,$yOffset,$color,$stoich_coeff,font_scale,$format);
	$xOffset+=abs($dimensions[2]-$dimensions[0])+$margin;
}

function paintPlusIntoImage(& $im,& $xOffset,$yOffset,$plussize,$margin,$color,$format="png") {
	// Standard: 10x10 px, mittig zu yOffset und links von xOffset
	$xMiddle=$xOffset+0.5*$plussize;
	imgline($im,$xMiddle,$yOffset-0.5*$plussize,$xMiddle,$yOffset+0.5*$plussize,$color,2,$format); // |
	imgline($im,$xOffset,$yOffset,$xOffset+$plussize,$yOffset,$color,2,$format); // -
	$xOffset+=$plussize+$margin;
}

function paintArrowIntoImage(& $im,& $xOffset,$yOffset,$arrowlength,$margin,$color,$format="png") {
	// Standard: 40x10 px, mittig zu yOffset und links von xOffset
	$xMiddle=$xOffset+0.8*$arrowlength;
	$xEnd=$xOffset+$arrowlength;
	
	imgline($im,$xOffset,$yOffset,$xEnd,$yOffset,$color,2,$format); // ----
	
	imgline($im,$xMiddle,$yOffset-0.05*$arrowlength,$xEnd,$yOffset,$color,2,$format); // /
	
	imgline($im,$xMiddle,$yOffset+0.05*$arrowlength,$xEnd,$yOffset,$color,2,$format); // \
	
	$xOffset=$xEnd+$margin;
}

function getMoleculeGif($molecule,$minX=400,$minY=300,$aspectRatio=1.33,$scale=1,$color=true,$format=array("png") ) {
	$format_count=count($format);
	if ($format_count==0) {
		return;
	}
	$retval=array_fill(0,$format_count,"");
	
	// bei $format_count==1 nur den String zurückgeben
	
	foreach($format as $idx => $ext) {
		switch ($ext) {
		case "svg":
			$retval[$idx].=svg_header;
		break;
		}
	}
	
	// see http://bugs.php.net/bug.php?id=12775
	$molecule=unserialize(serialize($molecule)); // fix PHP Bug that leads to object corruption though passed byval
	// serialisierung erhält referenz von bondsFromNeighbours
	
	$margin=7; //px
	if (!count($molecule["atoms"])) {
		foreach($format as $idx => $ext) {
			$retval[$idx]=getEmptyImage($ext);
		}
		if ($format_count==1) {
			return $retval[0];
		}
		return $retval;
	}
	
	//~ echo "<table valign=top><tbody><tr><td valign=top><pre>";
	//~ print_r($molecule);
	//~ echo "</pre></td><td valign=top><pre>";
	
	if (!$molecule["has_transition_metal"]) { // not for transition metal complexes as they might look BS
		optimiseMoleculeParts($molecule); // es werden keine Bindungslängen verändert!!
	}
	
	//~ print_r($molecule);
	//~ echo "</pre></td></tr></tbody></table>";
	
	$molscale=getMolscale($molecule);
	//~ print_r(getMoleculeDimensions($molecule,$scale*$molscale,$margin));
	//~ die($molscale."X".$margin);
	
	// Dimensionen holen
	list($xMin,$yMin,$xMax,$yMax,$iWidth,$iHeight)=getMoleculeDimensions($molecule,$scale*$molscale,$margin);
	// kleine Moleküle zentrieren
	$xOffset=0;
	$yOffset=0;
	if ($iHeight<$minY) {
		$yOffset=($minY-$iHeight)*0.5;
		$iHeight=$minY;
	}
	if ($iWidth<$minX) {
		$xOffset=($minX-$iWidth)*0.5;
		$iWidth=$minX;
	}
	// Skalierungsfaktoren festlegen, damit es paßt
	// Vorsicht, minX/minY nicht mit xMin,yMin verwechseln
	if ($aspectRatio==0 && $minX>0 && $minY>0) { // use minX,Y for aspect ratio
		$aspectRatio=$minX/$minY;
	}
	
	if ($aspectRatio>0) { // Höhe oder Breite vergrößern, um Seitenverhältnis zu erreichen
		if ($iWidth/$iHeight>$aspectRatio) {
			$iHeight=$iWidth/$aspectRatio;
		}
		else {
			$iWidth=$aspectRatio*$iHeight;
		}
	}
	
	foreach($format as $idx => $ext) {
		$xOffset_arr[$idx]=$xOffset; // byref
		$retval[$idx]=imgcreate($iWidth,$iHeight,$ext);
		
		switch ($ext) {
		case "svg":
			$colorIndex[$idx]=getColorIndex($retval[$idx],$ext);
			$retval[$idx].="<svg height=\"".$iHeight."\" width=\"".$iWidth."\" viewBox=\"0 0 ".$iWidth." ".$iHeight."\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">
<script>
<![CDATA[
function followLink() {
	var url=document.defaultView.frameElement.getAttribute(\"href\");
	if (parent && url) {
		parent.location.href=url;
	}
}
]]>
</script><a xlink:href=\"javascript:followLink()\"><rect x=\"0\" y=\"0\" height=\"".$iHeight."\" width=\"".$iWidth."\" fill=".fixStr($colorIndex[$idx]["transparent"])."/>";
		break;
		case "png":
		case "gif":
			// Bild erzeugen
			$colorIndex[$idx]=getColorIndex($retval[$idx],$ext);
		break;
		}
		
		
		paintMoleculeIntoImage($retval[$idx],$colorIndex[$idx],$molecule,$xOffset_arr[$idx],$yOffset,$xMin,$yMax,$iWidth,$iHeight,$scale,$molscale,$margin,$color,0,$ext);
		
		switch ($ext) {
		case "svg":
			$retval[$idx].="</a></svg>";		
		break;
		default:
			// Bild erzeugen
			$retval[$idx]=getImage($retval[$idx],$colorIndex[$idx]["white"],$ext);
		}
	}
	
	if ($format_count==1) {
		return $retval[0];
	}
	return $retval;	
}

function getImage($im,$colortransparent,$format) {
	imagecolortransparent($im,$colortransparent);
	ob_start();
	switch ($format) {
	case "gif":
		ImageGif($im);
	break;
	case "png":
		ImagePng($im);
	break;
	}
	return ob_get_clean();
}

function getChargeText($charge) {
	if ($charge==1) { // Ladungssymbol => sup
		return "+";
	}
	elseif ($charge==-1) { // Ladungssymbol => sup
		return "-";
	}
	elseif ($charge>1) { // Ladungssymbol => sup
		return $charge."+";
	}
	elseif ($charge<-1) { // Ladungssymbol => sup
		return (-$charge)."-";
	}
}

function paintMoleculeIntoImage(& $im,& $colorIndex,$molecule,& $xOffset,$yOffset,$xMin,$yMax,$iWidth,$iHeight,$scale,$molscale,$margin,$color,$Xmargin=0,$format="png") { // malt molekül in GIF
	// xOffset wird automatisch um iWidth und margin erhöht
	// xyOffset: Verschiebung des Starts, px
	// xMin yMax: niedrigste x/höchste y-Koordinate im Molekül (Zahlen im Molfile)
	// iW/H: Breite/Höhe in px
	// scale: vom Benutzer gesetzter Skalierungsfaktor
	// molscale: Normierungsfaktor, aus mittlerer Bindungslänge berechnet
	// margin: Rand, px
	// color: bool
	// Xmargin: zusätzlicher Inkrement für xOffset
	global $fontsize,$fontname;
	// Farben init
	
	transformMoleculeForDisplay($molecule);
	
	$sup_px=4;
	$sub_px=4;
	$right_text=2;
	$att_radius=5;
	
	// x(img)=xm*x(mol)+xb
	// y(img)=ym*y(mol)+yb
	$xm=$scale*$molscale;
	$xb=$margin+$xOffset-$xMin*$xm;
	$ym=-1*$scale*$molscale;
	$yb=$margin+$yOffset-$yMax*$ym;
	
	// bindungsgerüst zeichnen
	for ($a=0;$a<count($molecule[BONDS]);$a++) {
		$atom1=& $molecule[BONDS][$a][ATOM1];
		$atom2=& $molecule[BONDS][$a][ATOM2];
		if ($molecule["atoms"][$atom1][HIDE] || $molecule["atoms"][$atom2][HIDE]) {
			continue;
		}
		
		$ax1=$molecule["atoms"][$atom1]["x"]+$molecule["atoms"][$atom1]["t_x"];
		$ay1=$molecule["atoms"][$atom1]["y"]+$molecule["atoms"][$atom1]["t_y"];
		$ax2=$molecule["atoms"][$atom2]["x"]+$molecule["atoms"][$atom2]["t_x"];
		$ay2=$molecule["atoms"][$atom2]["y"]+$molecule["atoms"][$atom2]["t_y"];
		$x1=$xm*$ax1+$xb;
		$y1=$ym*$ay1+$yb;
		$x2=$xm*$ax2+$xb;
		$y2=$ym*$ay2+$yb;
		
		switch($molecule[BONDS][$a][ORIG_BOND_ORDER]) {
		case 1:
			switch($molecule[BONDS][$a][STEREO]) {
			case 1: // up
				drawUpLine($im,$x1,$y1,$x2,$y2,$colorIndex["black"],2*$scale,$format);			
			break;
			case 4: // undefined
				drawEitherLine($im,$x1,$y1,$x2,$y2,$colorIndex["black"],2*$scale,$format);			
			break;
			case 6: // down
				drawDownLine($im,$x1,$y1,$x2,$y2,$colorIndex["black"],2*$scale,$format);
			break;
			default:
				drawSingleBond($im,$x1,$y1,$x2,$y2,$colorIndex["black"],$scale,$format);
			}
		break;
		case 2:
			unset($smallestRing);
			unset($maxPrio);
			unset($refPointX);
			unset($refPointY);
			unset($count);
			
			$neighbours1=count($molecule["atoms"][$atom1][NEIGHBOURS]);
			$neighbours2=count($molecule["atoms"][$atom2][NEIGHBOURS]);
			if (($neighbours1==1 && $neighbours2>=3) || ($neighbours1>=3 && $neighbours2==1) || $molecule["atoms"][$atom1][IS_CUMULENE] || $molecule["atoms"][$atom2][IS_CUMULENE]) { // also P / S
				// symmetric double bond, do nothing
			}
			else {
				// von den kleinen zu den großen Ringen arbeiten, Aromaten bevorzugen (-0.5)
				for ($b=0;$b<count($molecule[BONDS][$a][RINGS]);$b++) {
					$ring_no=$molecule[BONDS][$a][RINGS][$b];
					$thisPrio=$molecule[RINGS][$ring_no]["size"]-($molecule[RINGS][$ring_no]["ar"]?0.5:0);
					if (!isset($maxPrio) || $thisPrio<$maxPrio) {
						$maxPrio=$thisPrio;
						$smallestRing=$ring_no;
					}
				}
				if (isset($maxPrio)) { // Ring gefunden
					$refPointX=$molecule[RINGS][$smallestRing]["x"];
					$refPointY=$molecule[RINGS][$smallestRing]["y"];
				}
				else { // Substituenten
					for ($b=0;$b<$neighbours1;$b++) {
						$atom_no=$molecule["atoms"][$atom1][NEIGHBOURS][$b];
						if ($atom_no!=$atom2) {
							$refPointX+=$molecule["atoms"][$atom_no]["x"];
							$refPointY+=$molecule["atoms"][$atom_no]["y"];
							$count++;
						}
					}
					//~ echo "D";
					for ($b=0;$b<$neighbours2;$b++) {
						$atom_no=$molecule["atoms"][$atom2][NEIGHBOURS][$b];
						if ($atom_no!=$atom1) {
							$refPointX+=$molecule["atoms"][$atom_no]["x"];
							$refPointY+=$molecule["atoms"][$atom_no]["y"];
							$count++;
						}
					}
					if ($count) {
						$refPointX/=$count;
						$refPointY/=$count;
					}
				}
			}
			if (isset($refPointX) && isset($refPointX)) {
				// orientation towards ref point
				$ang_bond=atan2($ay2-$ay1,$ax2-$ax1);
				$ang_ref=atan2($ay2-$refPointY,$ax2-$refPointX);
				//~ echo $refPointX."X".$refPointY." ".$ax1."X".$ay1." ".$ax2."X".$ay2."<br>";
				//~ imagesetpixel($im, $margin+$xOffset+($refPointX-$xMin)*$scale*$molscale, $margin+$yOffset+($yMax-$refPointY)*$scale*$molscale, $colorIndex["blue"]);
				$delta_rad=$ang_bond-$ang_ref;
				if (abs($delta_rad)>pi()) {
					$delta_rad*=-1;
				}
				if ($delta_rad>0) {
					drawDoubleBondR($im,$x1,$y1,$x2,$y2,$colorIndex["black"],$scale,$format);
				}
				elseif ($delta_rad<0) {
					drawDoubleBondL($im,$x1,$y1,$x2,$y2,$colorIndex["black"],$scale,$format);
				}
				else {
					drawDoubleBond($im,$x1,$y1,$x2,$y2,$colorIndex["black"],$scale,$format);
				}
			}
			else {
				drawDoubleBond($im,$x1,$y1,$x2,$y2,$colorIndex["black"],$scale,$format);
			}
		break;
		case 3:
			drawTripleBond($im,$x1,$y1,$x2,$y2,$colorIndex["black"],$scale,$format);
		break;
		}
	}
	
	$atomColors=array(
		"N" => $colorIndex["blue"], 
		"O" => $colorIndex["red"], 
		"F" => $colorIndex["pink"], 
		"Cl" => $colorIndex["pink"], 
		"Br" => $colorIndex["pink"], 
		"I" => $colorIndex["pink"], 
		"S" => $colorIndex["yellow"]
	);

	// Atome drauf
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		if ($molecule["atoms"][$a][HIDE] && empty($molecule["atoms"][$a]["te"]) ) {
			continue;
		}
		
		$thisT=$molecule["atoms"][$a][ATOMIC_SYMBOL];
		$charge=$molecule["atoms"][$a][ORIG_CHARGE];
		$radical=$molecule["atoms"][$a][ORIG_RADICAL];
		
		$fullPaint=($thisT!="C" || $charge!=0 || count($molecule["atoms"][$a][NEIGHBOURS])==0 || $molecule["atoms"][$a][IS_CUMULENE] || $molecule["atoms"][$a][IS_ISOTOPE] || $radical!=0);
		$hasText=!empty($molecule["atoms"][$a]["te"]);
		$thisX=$xm*($molecule["atoms"][$a]["x"]+$molecule["atoms"][$a]["t_x"])+$xb;
		$thisY=$ym*($molecule["atoms"][$a]["y"]+$molecule["atoms"][$a]["t_y"])+$yb;
		
		if ($fullPaint || !empty($molecule["atoms"][$a]["assign"]) || $hasText) { // print CH4 for methane
			
			if ($color && !$hasText) {
				$thisColor=$atomColors[$thisT];
				if (empty($thisColor)) {
					// fallback
					$thisColor=$colorIndex["black"];
				}
			}
			else {
				$thisColor=$colorIndex["black"];
			}
			
			// isotopic superscript left
			if ($molecule["atoms"][$a][IS_ISOTOPE] && !$molecule["atoms"][$a]["hideMass"]) {
				$supText=round($molecule["atoms"][$a][MASS]);
				list($thisW,$thisH)=getTextDimensions($supText,font_scale);
				drawTextWithBG($im,$thisX-$thisW,$thisY-$sup_px,$supText,$thisColor,4,null,font_scale*0.85,$format); // $white
			}
			
			$thisT.=$molecule["atoms"][$a]["assign"];
			if ($fullPaint) { // not Cs when assignment is on
				$iHyd=$molecule["atoms"][$a][ORIG_IMPLICIT_H];
				if (isset($molecule["att_atom"]) && $molecule["att_atom"]==$a) {
					$iHyd--;
				}
				if ($iHyd>=1) {
					$thisT.="H";
				}
			}
			
			// double dot looks better normal
			if ($radical==1) {
				$thisT.=":";
			}
			
			//~ list($thisW,$thisH)=getTextDimensions($thisT,font_scale);
			//~ imagefilledrectangle($im,$thisX-0.5*$thisW,$thisY-0.5*$thisH,$thisX+0.5*$thisW,$thisY+0.5*$thisH,$white);
			//~ drawText($im,$thisX-0.5*$thisW,$thisY+0.5*$thisH,$thisColor,$thisT,font_scale);
			list($thisW,$thisH)=drawTextWithBG(
				$im, 
				$thisX+$right_text, 
				$thisY, 
				ifempty($molecule["atoms"][$a]["te"],$thisT), 
				$thisColor, 
				5, 
				$colorIndex["transparent"], 
				font_scale, 
				$format
			);
			// drawTextWithBG($im,$x,$y,$text,$textColor,$orientation=0,$bgColor=null,$size_scale=1)
			
			if ($fullPaint && !$hasText) {
				// hochgestellt
				$supText="";
				
				// dot looks better superscript
				if ($radical==2) {
					$supText.=".";
				}
				
				$supText.=getChargeText($charge);
				
				drawTextWithBG($im,$thisX+0.5*$thisW,$thisY-$sup_px,$supText,$thisColor,4,null,font_scale*0.85,$format); // $white
				
				// tiefgestellt
				$subText="";
				if ($iHyd>1) { // Zahl der Hs => sub
					$subText.=$iHyd;
				}
				drawTextWithBG($im,$thisX+0.5*$thisW,$thisY+$sub_px,$subText,$thisColor,4,null,font_scale*0.85,$format); // $white
			}
		}
		
		// mark att atom
		if (isset($molecule["att_atom"]) && $molecule["att_atom"]==$a) {
			imgfilledellipse($im,$thisX,$thisY,$att_radius,$att_radius,$colorIndex["red"],$format);
		}
	}
	
	// Boxen für Oligomer/Polymer
	$fac=0.1;
	if (is_array($molecule[GROUPS])) foreach ($molecule[GROUPS] as $group) {
		// superatom with no outgoing bonds
		if ($group[GROUP_TYPE]=="SUP"
			&& !$group[EXPAND]
			&& !count($group["repres_atoms"])) {
			// get group center and draw text
			drawText($im,$xm*$group["cx"]+$xb,$ym*$group["cy"]+$yb,$colorIndex["black"],$group[GROUP_TEXT],font_scale,$format);
		}
		
		if (is_array($group[BRACKETS])) {
			// transform coords
			foreach ($group[BRACKETS] as $idx => $bracket) {
				$group[BRACKETS][$idx][0]=$xm*$bracket[0]+$xb;
				$group[BRACKETS][$idx][1]=$ym*$bracket[1]+$yb;
				$group[BRACKETS][$idx][2]=$xm*$bracket[2]+$xb;
				$group[BRACKETS][$idx][3]=$ym*$bracket[3]+$yb;
			}
			
			// main lines
			imgline($im,$group[BRACKETS][0][0],$group[BRACKETS][0][1],$group[BRACKETS][0][2],$group[BRACKETS][0][3],$colorIndex["black"],1,$format);
			imgline($im,$group[BRACKETS][1][0],$group[BRACKETS][1][1],$group[BRACKETS][1][2],$group[BRACKETS][1][3],$colorIndex["black"],1,$format);
			
			// corner lines
			$x_ol=$group[BRACKETS][0][0]*(1-$fac)+$group[BRACKETS][1][2]*$fac;
			$y_ol=$group[BRACKETS][0][1]*(1-$fac)+$group[BRACKETS][1][3]*$fac;
			
			$x_or=$group[BRACKETS][0][2]*$fac+$group[BRACKETS][1][0]*(1-$fac);
			$y_or=$group[BRACKETS][0][3]*$fac+$group[BRACKETS][1][1]*(1-$fac);
			
			$x_ul=$group[BRACKETS][0][2]*(1-$fac)+$group[BRACKETS][1][0]*$fac;
			$y_ul=$group[BRACKETS][0][3]*(1-$fac)+$group[BRACKETS][1][1]*$fac;
			
			$x_ur=$group[BRACKETS][0][0]*$fac+$group[BRACKETS][1][2]*(1-$fac);
			$y_ur=$group[BRACKETS][0][1]*$fac+$group[BRACKETS][1][3]*(1-$fac);
			
			imgline($im,$group[BRACKETS][0][0],$group[BRACKETS][0][1],$x_ol,$y_ol,$colorIndex["black"],1,$format);
			imgline($im,$group[BRACKETS][0][2],$group[BRACKETS][0][3],$x_ul,$y_ul,$colorIndex["black"],1,$format);
			imgline($im,$group[BRACKETS][1][0],$group[BRACKETS][1][1],$x_or,$y_or,$colorIndex["black"],1,$format);
			imgline($im,$group[BRACKETS][1][2],$group[BRACKETS][1][3],$x_ur,$y_ur,$colorIndex["black"],1,$format);
			
			// Texts
			$max_x=max($group[BRACKETS][0][0],$group[BRACKETS][0][2],$group[BRACKETS][1][0],$group[BRACKETS][1][2])+2;
			$max_y=max($group[BRACKETS][0][1],$group[BRACKETS][0][3],$group[BRACKETS][1][1],$group[BRACKETS][1][3]);
			$min_y=min($group[BRACKETS][0][1],$group[BRACKETS][0][3],$group[BRACKETS][1][1],$group[BRACKETS][1][3])+8;
			drawText($im,$max_x,$min_y,$colorIndex["black"],$group[GROUP_TEXT2],font_scale,$format);
			drawText($im,$max_x,$max_y,$colorIndex["black"],$group[GROUP_TEXT],font_scale,$format);
		}
	}
	
	switch ($format) {
	case "png":
	case "gif":
		imagecolortransparent($im,$colorIndex["white"]);
	break;
	}
	$xOffset+=$iWidth+$Xmargin;
}

?>