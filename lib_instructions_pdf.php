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
require_once "FPDF/mem_image.php";

function drawPictograms(& $pdf,$images,$widths,$height,$prefix="lib/",$suffix=".png") {
	$symCount=count($images);
	if (!$symCount) {
		return;
	}
	$perRow=ceil(sqrt($symCount));
	if (!is_array($widths)) {
		$widths=array_fill(0,count($images),$widths);
	}
	$top=$pdf->GetY();
	$maxTotalWidth=0;
	foreach ($images as $idx => $image) {
		if (($idx%$perRow)==0) {
			$left=($pdf->w-$pdf->rMargin);
			if ($idx>0) {
				$top+=$height;
				$maxTotalWidth=max($maxTotalWidth,$totalWidth);
			}
			$totalWidth=0;
		}
		$width=$widths[$idx];
		$totalWidth+=$width;
		$left-=$width;
		try {
			$pdf->Image($prefix.$image.$suffix,$left,$top,$width);
		} catch (Exception $e) {
			// ignore any missing files
		}
	}
	return array(max($maxTotalWidth,$totalWidth),ceil($symCount/$perRow)*$height);
}

function ensurePictograms(& $pdf,$oldTop,$boxHeight) {
	if ($pdf->GetY()-$oldTop<$boxHeight) {
		$pdf->SetY($oldTop+$boxHeight);
	}
}

function addSection(& $pdf,$text) {
	// give a bit of extra space
	$pdf->Ln(2);
	
	$pdf->SetFontSize(14);
	$pdf->SetDrawColor(0); // black
	$pdf->SetTextColor(255); // white
	$pdf->SetFillColor(255,0,0); // red
	
	$pdf->Cell(0,7,$text,1,1,"C",true);
	
	$pdf->SetTextColor(0); // black
	$pdf->SetFillColor(255); // white
	$pdf->SetFontSize(9);
	
	// give a bit of extra space
	$pdf->Ln(2);
}

function getSafetyWGKHtml(& $hash,$langToUse) {
	$value=$hash["safety_wgk"];
	if (!isEmptyStr($value)) {
		return "WGK".$value.": ".l($langToUse,"wgk".$value);
	}
	return "";
}

function writeHtmlWithPictos(& $pdf,$html,$rMargin) {
	$origMargin=$pdf->rMargin;
	$pdf->SetRightMargin($origMargin+$rMargin);
	$pdf->WriteHTML($html);
	$pdf->Ln();
	$pdf->SetRightMargin($origMargin);
}

function getWorkingInstructionsPDF(& $hash,$list_int_name,$UID,$molecule_names) {
	global $g_settings;
	
	$langToUse=getValueUID($list_int_name,$UID,"lang");
	
	$pdf=new PDF_MemImage("P","mm","A4");
	$pdf->SetTextColor(0);
	$pdf->SetFont("Arial","",9);
	$pdf->AddPage();
	
	// top left: organization name and working instr
	$pdf->WriteHTML($g_settings["organisation_name"]."<br>".l($langToUse,"betriebsanweisung"));
	
	// top right: logo
	try {
		$pdf->Image($g_settings["links_in_topnav"]["uni_logo"]["src"],130);
	} catch (Exception $e) {
		// ignore any missing files
	}
	$pdf->Ln();
	
	// headline
	$pdf->SetFont("","B",16);
	$pdf->Cell(0,8,l($langToUse,"betriebsanweisung")." ".l($langToUse,"accordance_with")." §14 GefStoffV",0,1,"C");
	
	// metadata
	$create_username=getValueUID($list_int_name,$UID,"molecule_instructions_created_by");
	$pdf->SetFont("","",9);
	$pdf->WriteHTML("<table>
	<tr><td><b>".l($langToUse,"org_unit")."</b></td><td>".$g_settings["org_unit"]."</td><td><b>".l($langToUse,"room")."</b></td><td></td></tr>
	<tr><td><b>".l($langToUse,"workgroup")."</b></td><td>".$g_settings["workgroup_name"]."</td><td><b>".l($langToUse,"type_of_work")."</b></td><td></td></tr>
	<tr><td><b>".l($langToUse,"instr_responsible")."</b></td><td>".$g_settings["instr_responsible"]."</td>
		<td><b>".l($langToUse,"molecule_created_by")."</b></td><td>".ifempty(formatPersonNameNatural(getUserForUsername($create_username)),$create_username)."</td></tr>
	<tr><td><b>".l($langToUse,"molecule_created_when")."</b></td><td>".toDateTime(getValueUID($list_int_name,$UID,"molecule_instructions_created_when"))."</td>
		<td><b>".l($langToUse,"molecule_changed_when")."</b></td><td>".getGermanDate(null,true)."</td></tr>
	</table>");
	
	// substance info: name(s), CAS, sum formula
	addSection($pdf,l($langToUse,"instr_subst_ident"));
	
	// main name
	$pdf->SetFont("","B",16);
	$pdf->SetFontSize(16);
	$pdf->Cell(0,8,$molecule_names[0],0,0,"C");
	
	// rest smaller
	$pdf->SetFont("","",9);
	
	// CAS and sum formula on right side
	$line_height=5;
	$picto_size=13; // width==height
	$pdf->Cell(0,$line_height,$hash["cas_nr"],0,2,"R"); // below
	$pdf->Cell(0,$line_height,$hash["emp_formula"],0,1,"R"); // next line
	
	// remaining names below
	$pdf->Cell(0,$line_height,ifnotempty("(",strcut(join("; ",array_slice($molecule_names,1)),100),")"),0,1,"C");
	
	// hazards
	addSection($pdf,l($langToUse,"instr_hazards"));
	$pictos=array();
	if ($g_settings["use_rs"]) {
		$pictos=array_merge($pictos,explode(",",$hash["safety_sym"]));
	}
	if ($g_settings["use_ghs"]) {
		$pictos=array_merge($pictos,explode(",",$hash["safety_sym_ghs"]));
	}
	$pictos=array_unique($pictos);
	$pictoFilenames=array();
	foreach ($pictos as $picto) {
		$picto=getSafetyFilename(trim($picto));
		if ($picto) {
			$pictoFilenames[]=$picto;
		}
	}
	list($boxWidth,$boxHeight)=drawPictograms($pdf,$pictoFilenames,$picto_size,$picto_size,"lib/","");
	$oldTop=$pdf->GetY();
	
	// signal word below, try to center
	if (!isEmptyStr($hash["safety_text"])) {
		if (count($pictoFilenames)) {
			// below symbols
			$pdf->SetY($oldTop+$boxHeight);
		}
		$pdf->SetFont("","B",12);
		$pdf->SetX($pdf->w-$pdf->rMargin-$boxWidth);
		$pdf->Cell($boxWidth,$line_height,$hash["safety_text"],0,0,"C");
		$boxHeight+=$line_height; // prevent signal word hidden under next section
		
		// back to start, normal font again
		$pdf->SetFont("","",9);
		$pdf->SetY($oldTop);
	}
	
	$lines=getSafetyHtml($hash,$langToUse,array("r","h"));
	$lines[]=getSafetyWGKHtml($hash,$langToUse);
	$lines[]=nl2br($hash[$list_int_name."_".$UID."_betr_anw_gefahren"]);
	writeHtmlWithPictos($pdf,joinIfNotEmpty($lines,"<br>"),$boxWidth);
	ensurePictograms($pdf,$oldTop,$boxHeight);
	
	// precautions, pictograms on right side
	addSection($pdf,l($langToUse,"instr_protect"));
	$pictos=$hash[$list_int_name."_".$UID."_betr_anw_schutzmass_sym"];
	list($boxWidth,$boxHeight)=drawPictograms($pdf,$pictos,$picto_size,$picto_size);
	$oldTop=$pdf->GetY();
	
	$lines=getSafetyHtml($hash,$langToUse,array("s","p"));
	$lines[]=nl2br($hash[$list_int_name."_".$UID."_betr_anw_schutzmass"]);
	writeHtmlWithPictos($pdf,joinIfNotEmpty($lines,"<br>"),$boxWidth);
	ensurePictograms($pdf,$oldTop,$boxHeight);
	
	// behavior in case of emergency, pictograms on right side
	addSection($pdf,l($langToUse,"instr_behavior"));
	$pictos=$hash[$list_int_name."_".$UID."_betr_anw_verhalten_sym"];
	list($boxWidth,$boxHeight)=drawPictograms($pdf,$pictos,$picto_size,$picto_size);
	$oldTop=$pdf->GetY();
	writeHtmlWithPictos($pdf,nl2br($hash[$list_int_name."_".$UID."_betr_anw_verhalten"]),$boxWidth);
	ensurePictograms($pdf,$oldTop,$boxHeight);
	
	// first aid, pictograms on right side
	addSection($pdf,l($langToUse,"instr_first_aid")." ".ifEmpty($g_settings["emergency_call"],"112"));
	$pictos=$hash[$list_int_name."_".$UID."_betr_anw_erste_h_sym"];
	list($boxWidth,$boxHeight)=drawPictograms($pdf,$pictos,$picto_size,$picto_size);
	$oldTop=$pdf->GetY();
	writeHtmlWithPictos($pdf,nl2br($hash[$list_int_name."_".$UID."_betr_anw_erste_h"]),$boxWidth);
	ensurePictograms($pdf,$oldTop,$boxHeight);
	
	// disposal
	addSection($pdf,l($langToUse,"instr_disposal"));
	$pdf->WriteHTML(nl2br($hash[$list_int_name."_".$UID."_betr_anw_entsorgung"]));
	
	return $pdf;
}

?>