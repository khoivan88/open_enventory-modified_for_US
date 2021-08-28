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
$reaction_condition_exclude=array("fix_stoch","reactants_rc_amount_unit","reactants_mass_unit","reactants_volume_unit","reactants_rc_conc_unit", "products_mass_unit","products_rc_amount_unit","betr_anw_gefahren","betr_anw_schutzmass","betr_anw_verhalten","betr_anw_erste_h","betr_anw_entsorgung");

function addReactionToPDF($pdf,$rxn,$paramHash=array()) {
	global $reaction_condition_exclude;
	$pdf->SetTextColor(0);
	$pdf->SetFont("Arial","",16);
	$pdf->AddPage();
	
	$page_width=($pdf->w-$pdf->lMargin-$pdf->rMargin);
	
	// entry in larger font
	$pdf->Cell(85,9,$rxn["lab_journal_code"]." ".$rxn["nr_in_lab_journal"]);
	
	// metadata
	$pdf->SetFontSize(9);
	$pdf->Cell(85,9,s("reaction_title").": ".$rxn["reaction_title"],0,1);
	$pdf->Cell(85,0,s("reaction_carried_out_by").": ".$rxn["reaction_carried_out_by"]);
	$pdf->Cell(0,0,s("reaction_started_when").": ".$rxn["reaction_started_when"]);
	$pdf->Ln();
	if ($rxn["project_name"]) {
		$pdf->Cell(0,7,s("project_name").": ".$rxn["project_name"],0,1);
	}
	if ($rxn["reaction_type_name"]) {
		$pdf->Cell(0,7,s("reaction_type_name").": ".$rxn["reaction_type_name"],0,1);
	}
	
	// image of equation
	list($result)=mysql_select_array(array(
		"table" => "reaction_gif", 
		"dbs" => $rxn["db_id"], 
		"filter" => "reaction_id=".fixNull($rxn["reaction_id"]), 
		"limit" => 1, 
	));
	if ($result["image"]) {
		$pdf->MemImage($result["image"],null,null,$page_width);
	}
	
	// reaction conditions (use blacklist)
	$cell_width=$page_width/4;
	$line_height=8;
	$idx=0;
	foreach ($rxn["reaction_property"] as $reaction_property) {
		if (!in_array($reaction_property,$reaction_condition_exclude) && !isEmptyStr($rxn[$reaction_property])) {
			$pdf->Cell($cell_width,$line_height,s($reaction_property),0);
			$pdf->Cell($cell_width,$line_height,$rxn[$reaction_property],0);
			$idx++;
			if ($idx%2==0) {
				$pdf->Ln();
			}
		}
	}
	if ($idx%2!=0) {
		$pdf->Ln();
	}
	
	// table of reactants and reagents
	$pdf->SetFont("","B");
	$pdf->Cell(0,7,s("reactants"));
	$pdf->SetFont("","");
	$pdf->Ln();
	addReactionComponentTable($pdf,$rxn,"reactants");
	addReactionComponentTable($pdf,$rxn,"reagents",false);
	
	// html for procedure and observation
	$pdf->SetFont("","B");
	$pdf->Cell(0,7,s("realization_text"));
	$pdf->SetFont("","");
	$pdf->Ln();
	$pdf->WriteHTML($rxn["realization_text"]);
	$pdf->Ln();
	
	$pdf->SetFont("","B");
	$pdf->Cell(0,7,s("realization_observation"));
	$pdf->SetFont("","");
	$pdf->Ln();
	$pdf->WriteHTML($rxn["realization_observation"]);
	$pdf->Ln();
	
	// table of products
	$pdf->SetFont("","B");
	$pdf->Cell(0,7,s("products"));
	$pdf->SetFont("","");
	$pdf->Ln();
	addReactionComponentTable($pdf,$rxn,"products");
	
	// table of analytical data
	if (count($rxn["analytical_data"])) {
		// prepare reaction_chemical_id map
		$list_int_names=array("reactant","reagent","product");
		$reaction_chemical_map=array();
		foreach ($list_int_names as $list_int_name) {
			if (is_array($rxn[$list_int_name])) foreach ($rxn[$list_int_name."s"] as $idx => $reaction_chemical) {
				$reaction_chemical_map[ $reaction_chemical["reaction_chemical_id"] ]=ifNotEmpty($reaction_chemical["standard_name"],s($list_int_name)." ".($idx+1));
			}
		}
		
		$line_height2=85;
		$cell_width=$page_width/8;
		
		foreach ($rxn["analytical_data"] as $idx => $analytical_data) {
			// check if page break is required
			$page_break_required=($pdf->y+2*$line_height+$line_height2+($idx==0?7:0)>$pdf->PageBreakTrigger);
			if ($page_break_required || $idx==0) {
				if ($page_break_required) {
					$pdf->AddPage();
				}
				
				if ($idx==0) {
					$pdf->SetFont("","B");
					$pdf->Cell(0,7,s("analytical_data"));
					$pdf->SetFont("","");
					$pdf->Ln();
				}
				
				// headline
				$pdf->SetFont("","B");
				$pdf->Cell($cell_width,$line_height,s("analytical_data_identifier"),1);
				$pdf->Cell($cell_width,$line_height,s("analytics_type_name"),1);
				$pdf->Cell($cell_width,$line_height,s("analytics_device_name"),1);
				$pdf->Cell($cell_width,$line_height,s("analytics_method_name"),1);
				$pdf->Cell($cell_width,$line_height,s("measured_by"),1);
				$pdf->Cell($cell_width,$line_height,s("reaction_chemical_uid"),1);
				$pdf->Cell($cell_width,$line_height,s("fraction_no"),1);
				$pdf->Cell($cell_width,$line_height,s("analytical_data_comment"),1);
				$pdf->Ln();
				$pdf->SetFont("","");
			}
			
			$pdf->Cell($cell_width,$line_height,$analytical_data["analytical_data_identifier"],1);
			$pdf->Cell($cell_width,$line_height,$analytical_data["analytics_type_name"],1);
			$pdf->Cell($cell_width,$line_height,$analytical_data["analytics_device_name"],1);
			$pdf->Cell($cell_width,$line_height,$analytical_data["analytics_method_name"],1);
			$pdf->Cell($cell_width,$line_height,$analytical_data["measured_by"],1);
			if ($analytical_data["reaction_chemical_id"]) {
				$pdf->Cell($cell_width,$line_height,$reaction_chemical_map[ $analytical_data["reaction_chemical_id"] ],1);
			} else {
				$pdf->Cell($cell_width,$line_height,s("reaction_mixture"),1);
			}
			$pdf->Cell($cell_width,$line_height,$analytical_data["fraction_no"],1);
			$pdf->Cell($cell_width,$line_height,$analytical_data["analytical_data_comment"],1);
			$pdf->Ln();
			
			list($result)=mysql_select_array(array(
				"table" => "analytical_data_gif", 
				"dbs" => $rxn["db_id"], 
				"filter" => "analytical_data_id=".fixNull($analytical_data["analytical_data_id"]), 
				"limit" => 1, 
			));
			if ($result["image"]) {
				$pdf->Cell(0,$line_height2,$pdf->MemImage($result["image"],$pdf->GetX(),$pdf->GetY(),0,$line_height2),1);
			}
			$pdf->Ln();
		}
	}
	
	// no table of literature for now
	
	//~ return $pdf->Output("","S");
	//~ $pdf->Output("test.pdf","I");
}

$rxn_component_widths=array(7,13,35,30,10,24,14,24,24);
function addReactionComponentTable($pdf,$rxn,$list_int_name,$headline=true) {
	global $rxn_component_widths;
	$line_height=8;
	if ($headline) {
		$pdf->SetFont("","B");
		$pdf->Cell($rxn_component_widths[0],$line_height,"",1);
		$pdf->Cell($rxn_component_widths[1],$line_height,s("stoch_coeff"),1);
		$pdf->Cell($rxn_component_widths[2],$line_height,s("molfile_blob"),1);
		$pdf->Cell($rxn_component_widths[3],$line_height,s("molecule_id"),1);
		$pdf->Cell($rxn_component_widths[4],$line_height,s("mw"),1);
		$pdf->Cell($rxn_component_widths[5],$line_height,s("rc_amount"),1);
		$pdf->Cell($rxn_component_widths[6],$line_height,s("rc_conc"),1);
		$pdf->Cell($rxn_component_widths[7],$line_height,s("m_brutto"),1);
		if ($list_int_name=="products") {
			$pdf->Cell($rxn_component_widths[8],$line_height,"isol. %/ana. %",1);
		} else {
			$pdf->Cell($rxn_component_widths[8],$line_height,s("volume"),1);
		}
		$pdf->Ln();
		$pdf->SetFont("","");
	}
	$line_height=12;
	$pdf->SetFontSize(9);
	if (is_array($rxn[$list_int_name])) foreach ($rxn[$list_int_name] as $idx => $reaction_chemical) {
		$idx_txt=$idx+1;
		switch ($list_int_name) {
		case "reactants":
			$idx_txt=numToLett($idx_txt);
		break;
		case "reagents":
			$idx_txt="R".$idx_txt;
		break;
		}
		$pdf->Cell($rxn_component_widths[0],$line_height,$idx_txt,1);
		
		$pdf->Cell($rxn_component_widths[1],$line_height,($reaction_chemical["stoch_coeff"]?roundLJ($reaction_chemical["stoch_coeff"]):""),1);
		
		list($result)=mysql_select_array(array(
			"table" => "reaction_chemical_gif", 
			"dbs" => $rxn["db_id"], 
			"filter" => "reaction_chemical_id=".fixNull($reaction_chemical["reaction_chemical_id"]), 
			"limit" => 1, 
		));
		if ($result["image"]) {
			$pdf->Cell($rxn_component_widths[2],$line_height,$pdf->MemImage($result["image"],$pdf->GetX(),$pdf->GetY(),0,$line_height),1);
		}
		
		$x=$pdf->GetX();
		$y=$pdf->GetY();
		$pdf->SetFontSize(7);
		$pdf->MultiCell($rxn_component_widths[3],$line_height*.45,$reaction_chemical["standard_name"]."\n".$reaction_chemical["package_name"],0,"L");
		$pdf->SetXY($x,$y);
		$pdf->Cell($rxn_component_widths[3],$line_height,"",1);
		
		$x+=$rxn_component_widths[3];
		$pdf->SetFontSize(9);
		$pdf->SetXY($x,$y);
		//~ $pdf->Cell(0,$line_height,$reaction_chemical["emp_formula"]."\n".$reaction_chemical["cas_nr"],1);
		$pdf->Cell($rxn_component_widths[4],$line_height,roundIfNotEmpty($reaction_chemical["mw"],1),1);
		$pdf->Cell($rxn_component_widths[5],$line_height,($reaction_chemical["rc_amount"]?roundLJ($reaction_chemical["rc_amount"])." ".$reaction_chemical["rc_amount_unit"]:""),1);
		$pdf->Cell($rxn_component_widths[6],$line_height,($reaction_chemical["rc_conc"]?roundLJ($reaction_chemical["rc_conc"])." ".$reaction_chemical["rc_conc_unit"]:""),1);
		$pdf->Cell($rxn_component_widths[7],$line_height,($reaction_chemical["m_brutto"]?roundLJ($reaction_chemical["m_brutto"])." ".$reaction_chemical["mass_unit"]:""),1);
		//~ $pdf->Cell(0,$line_height,$reaction_chemical["density_20"],1);
		if ($list_int_name=="products") {
			$pdf->Cell($rxn_component_widths[8],$line_height,roundIfNotEmpty($reaction_chemical["yield"])." / ".roundIfNotEmpty($reaction_chemical["gc_yield"]),1);
		} else {
			$pdf->Cell($rxn_component_widths[8],$line_height,($reaction_chemical["volume"]?roundLJ($reaction_chemical["volume"])." ".$reaction_chemical["volume_unit"]:""),1);
		}
		// omit safety
		$pdf->Ln();
	}
}

?>