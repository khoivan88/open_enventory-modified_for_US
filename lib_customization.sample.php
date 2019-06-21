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

$default_g_settings=array();
$default_g_settings["organisation_name"]="TU Kaiserslautern";
//~ $default_g_settings["disable_login_lab_journal"]=true;
$default_g_settings["links_in_topnav"]=array(
	"uni_logo" => array(
		"url" => "http://www.uni-kl.de", 
		"target" => "_blank", 
		"src" => "lib/uni-logo.png", 
		"w" => "206", 
		"h" => "56", 
		"b" => "0", 
	), 
	"fb_logo" => array(
		"url" => "http://www.chemie.uni-kl.de/fachrichtungen/oc", 
		"target" => "_blank", 
		"src" => "lib/chemielogo.gif", 
		"w" => "192", 
		"h" => "64", 
		"b" => "0", 
	), 
);
$default_g_settings["order_system"]="fundp";

function performOrder() {
	global $own_data,$settings,$suppliers;
	require_once "lib_supplier_scraping.php";
	
	// generate mail text
	
	// header
	$eMailSubject=s("order_for")." ".formatPersonNameCommas($own_data);
	$eMailText=s("ordered_by").": ".formatPersonNameCommas($own_data)." (Tel. ".$settings["tel_no"].", eMail ".$own_data["email"].")
Kto-Nr./Proj.-Nr. ".$_REQUEST["order_acc_no"]."<br><br>";
	
	$list_int_name="order_alternative";
	$alternatives=count($_REQUEST[$list_int_name]);
	if ($alternatives>1) {
		$eMailText.=$alternatives." ".s("order_alternative_pl")."<br>";
	}
	
	$eMailText.="<table><thead><tr>
<td>".s("molecule_name")."</td>
<td>".s("cas_nr")."</td>
<td>".s("migrate_id_mol")."</td>
<td>".s("supplier")."</td>
<td>".s("beautifulCatNo")."</td>
<td>".s("package_amount")."</td>
<td>".s("number_packages")."</td>
</tr></thead><tbody>";
	
	if ($alternatives) foreach ($_REQUEST[$list_int_name] as $UID) { // add alternatives
		// get cas from supplier
		$supplier=getValueUID($list_int_name,$UID,"supplier");
		$catNo=getValueUID($list_int_name,$UID,"catNo");
		
		if (!empty($catNo) && function_exists($suppliers[$supplier]["getInfo"])) {
			$supplier_data=$suppliers[$supplier]["getInfo"]($catNo);
			$cas_nr=$supplier_data["cas_nr"];
			
			// check if there is no container existing for this CAS No.
			list($db_result)=mysql_select_array(array(
				"table" => "chemical_storage",
				"filter" => "cas_nr=".fixStrSQL($cas_nr)."", 
			));
		}
		
		$eMailText.="<tr>
<td>".getValueUID($list_int_name,$UID,"name")."</td>
<td>".$cas_nr."</td>
<td>".$bessi."</td>
<td>".$supplier."</td>
<td>".getValueUID($list_int_name,$UID,"beautifulCatNo")."</td>
<td>".getValueUID($list_int_name,$UID,"package_amount")."&nbsp;".getValueUID($list_int_name,$UID,"package_amount_unit")."</td>
<td>".getValueUID($list_int_name,$UID,"number_packages")."</td>
</tr>";
	}
	$eMailText.="</tbody></table>".ifNotEmpty("<br>".s("comment").":<br>",$_REQUEST["customer_comment"]);

	// mail versenden
	mail(
		$own_data["email_chemical_supply"], // in this case the professor
		$eMailSubject,
		$eMailText,
		"From: ".$own_data["email"]
	);
	/*
	echo $own_data["email_chemical_supply"]." ".
		$eMailSubject." ".
		$eMailText." ".
		"From: ".$own_data["email"];
	*/
	return array(ABORT_PROCESS,s("order_sent_to1").$own_data["email_chemical_supply"].s("order_sent_to2"));
}

$g_settings=array_merge($g_settings,$default_g_settings);

?>