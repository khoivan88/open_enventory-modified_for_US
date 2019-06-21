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

require_once "lib_global_funcs.php";
require_once "lib_atom_data.php";
require_once "lib_applet.php";
require_once "lib_db_query.php";

header("Content-Type: text/javascript");

$barcodeTerminal=true;
pageHeader(true,false);

foreach ($hazardSymbols as $sym) {
	$temp_arr[$sym]=s("safety_".$sym);
}
$hazardSymbols=$temp_arr;
unset($temp_arr);

foreach($column_options as $key => $data) {
	$col_options[$key]=array_keys($data["fields"]);
}

// unit part
$unit_result=mysql_select_array(array("table" => "units", "dbs" => "-1"));
$class_result=mysql_select_array(array("table" => "class", "dbs" => "-1"));

echo "unit_result=".json_encode($unit_result).";
class_result=".json_encode($class_result).";
currency_list=".json_encode($price_currency_list).";
method_aware_types=".json_encode($method_aware_types).";
col_options=".json_encode($col_options).";
analytical_data_priority=".json_encode($analytical_data_priority).";
arrSymURL=".json_encode($arrSymURL).";
var analytical_data_lines=".fixNull($analytical_data_lines).",analytical_data_cols=".fixNull($analytical_data_cols).",use_rs=".intval($g_settings["use_rs"]).",use_ghs=".intval($g_settings["use_ghs"]).";
func_groups=".json_encode($func_groups).";\n";

echo "var std_smiles=".fixStr($settings["std_smiles"]).",person_id=".fixNull($person_id).";\n";

// language part

$trans_texts=array(
	"structure","standard_name","package_name","compartment","borrowed_by","chemical_storage_barcode","required_amount","safety_sym","safety_sym_short","safety_r","safety_s","safety_sheet_by", // "solvent","solvent_amount",
	"scale_reaction","enter_scale_factor","enter_value",
	"auto_gc","gc_cross","gc_yield","gc_standard","ret_time","area_perc","resp_fac","no_method","gc_peak_comment","approx",
	"reactant","reagent","product","rxn_mixture",
	"warning_close_lj",
	"enter_comment","current_version","recover_warning", 
	"get_literature","literature_citation",
	"own_database",
	"edit_structure","save_gif","save_svg","save_molfile","copy_structure","search_as_product","search_as_educt", 
	"save_changes","discard_changes",
	"collapse","expand","prev_image","next_image",
	"get_analytical_data_raw_blob",
	"please_wait",
	"no_structure","no_search_term","add_condition","delete","AND","OR","XOR","NOT","change_table",
	"password_dont_match","password_none","error_password_too_short","error_password_not_username","error_password_too_simple",
	"add_line","details",
	"error_no_name_or_cas","delWarning","delWarningLiterature","reset_settings","merge_warning1","merge_warning2","merge_warning3","no_results","no_cas_nr",
	"storage_name","institution","molecule_name","cas_nr","emp_formula","mw","institution_name","from_reaction_id","bp_high","ret_time",
	"total1","total2","total1_sing","total2_sing",
	"readExtStart","readExtFailed",
	"nobody","enter_password1","enter_password2","delWarningUser", 
	"sci_journal_name","sci_journal_abbrev",
	"project_name",
	"db_name",
	"warn_change_price","from","to","split_count_question",
	"trm_is_logged_in1","trm_is_logged_in2","trm_welcome1","trm_welcome2","trm_goodbye1","trm_goodbye2",
	"fixed_costs","sonderchemikalien","lagerchemikalien","fixed_costs_share","flaschenmiete","days","name","amount","price","vat_rate","acc_no","beautifulCatNo","net_total","vat_sum","gross_total",
	"missing1","missing2","additional1","additional2","instead_of","identical","fundamentally_different","reference_reaction","compare_rxn_disable","diagram_yield","diagram_gc_yield","product", 
	"ask_global_discount","lagerpauschale","update_number_packages_text",  
);

// add reaction conditions
$trans_texts=arr_merge($trans_texts,array_keys($reaction_conditions));

foreach ($trans_texts as $trans_text) {
	$translated[$trans_text]=s($trans_text);
}

echo "var messageCheckInterval=".fixNull(messageCheckInterval).";
var localizedString=".json_encode($translated).";
arrSymTooltip=".json_encode($hazardSymbols).";
diagram_colors=".json_encode($diagram_colors).";\n";

if ($settings["lj_round_type"]=="fixed") {
	$roundMode=0; // fixed
}
else {
	$roundMode=4; // sign
}

$decimals=$settings["digits_count"];
if (!is_numeric($decimals)) {
	$decimals=3; // default value
}

// applet part
// ,isFF36=".intval(isFF36())."
echo "var own_address=".fixStr(getFormattedAdress($own_data)).",bar_height=".bar_height.",bar_width=".bar_width.",highlight_inputs=".intval(getSetting("highlight_inputs")).",ausgabe_name=".fixNull(ausgabe_name).",dispose_instead_of_delete=".intval(getSetting("dispose_instead_of_delete")).",keepStructures=".intval(getSetting("keep_structures")).",yield_digits=".yield_digits.",yield_mode=".yield_mode.",isMSIE=".intval(isMSIE()).",isFF1x=".intval(isFF1x()).",isFF3x=".intval(isFF3x()).",isChrome=".intval(isChrome()).",isSafari=".intval(isSafari()).",isOpera=".intval(isOpera()).",isMac=(navigator.appVersion.indexOf(\"Mac\")!=-1),molApplet=".fixStr(getAppletSetting("mol")).",rxnApplet=".fixStr(getAppletSetting("rxn")).";

function rxnRound(val) {
	return round(val,".$decimals.",".$roundMode.");
}";

?>