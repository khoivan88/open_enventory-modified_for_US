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

function getEditButton($key) {
	global $permissions,$person_id,$belab_options,$g_settings,$baseTable;
	
	$retval="<td>";
	switch ($key) {
	case "goto_molecule":
		$retval.="<a href=\"Javascript:goto_reference(&quot;molecule&quot;,&quot;molecule_id&quot;);\" class=\"imgButtonSm\"><img src=\"lib/goto_mol_sm.png\" border=\"0\"".getTooltip("goto_molecule")."></a>";
	break;
	case "goto_chemical_storage":
		$retval.="<a id=\"btn_goto_chemical_storage\" href=\"Javascript:goto_reference(&quot;chemical_storage&quot;,&quot;chemical_storage_id&quot;);\" class=\"imgButtonSm\"><img src=\"lib/goto_chemical_storage_sm.png\" border=\"0\"".getTooltip("goto_chemical_storage")."></a>";
	break;
	case "goto_settlement":
		$retval.="<a id=\"btn_goto_settlement\" href=\"Javascript:goto_reference(&quot;settlement&quot;,&quot;settlement_id&quot;);\" class=\"imgButtonSm\"><img src=\"lib/goto_settlement_sm.png\" border=\"0\"".getTooltip("goto_settlement")."></a>";
	break;
	case "goto_institution":
		$retval.="<a href=\"Javascript:goto_reference(&quot;institution&quot;,&quot;vendor_id&quot;);\" class=\"imgButtonSm\"><img src=\"lib/goto_vendor_sm.png\" border=\"0\"".getTooltip("goto_vendor")."></a>";
	break;
	
	case "filter_off":
		$retval.="<a href=\"Javascript:filterOff()\" class=\"imgButtonSm\"><img src=\"lib/filter_off_sm.png\" border=\"0\"".getTooltip("filter_off")."></a>";
	break;
	case "this_lab_journal":
		$retval.="<a href=\"Javascript:filterOff(&quot;lab_journal&quot;)\" class=\"imgButtonSm\"><img src=\"lib/lab_journal_sm.png\" border=\"0\"".getTooltip("show_lab_journal")."></a>";
	break;
	case "this_project":
		$retval.="<a href=\"Javascript:filterOff(&quot;project&quot;)\" class=\"imgButtonSm\" id=\"btn_show_project\"><img src=\"lib/project_sm.png\" border=\"0\"".getTooltip("show_project")."></a>";
	break;
	
	case "add_analytical_data":
		$retval.="<a href=\"Javascript:void SILmanualAddLine(&quot;analytical_data&quot;);\" class=\"imgButtonSm\"><img src=\"lib/analytical_data_sm.png\" border=\"0\"".getTooltip("add_spectrum").">+</a>";
	break;
	case "add_project_literature":
		$retval.="<a href=\"Javascript:void SILmanualAddLine(&quot;project_literature&quot;);\" class=\"imgButtonSm\"><img src=\"lib/literature_sm.png\" border=\"0\"".getTooltip("add_literature").">+</a>";
	break;
	case "add_literature_doi":
		$retval.="<a href=\"Javascript:void addLiteratureByDOI();\" class=\"imgButtonSm\"><img src=\"lib/doi_sm.png\" border=\"0\"".getTooltip("add_lit_by_doi").">+</a>";
	break;
	
	case "cancel_edit":
		$retval.="<a href=\"Javascript:cancelEditMode()\" class=\"imgButtonSm\"><img src=\"lib/cancel_sm.png\" border=\"0\"".getTooltip("discard_changes")."></a>";
	break;
	case "save":
		$retval.="<a href=\"Javascript:void(0); \" id=\"btn_save_disabled\" class=\"imgButtonSm\"><img src=\"lib/save_disabled_sm.png\" border=\"0\"".getTooltip("save_changes")."></a>
		<a href=\"Javascript:saveChanges(); \" id=\"btn_save\" class=\"imgButtonSm\" style=\"display:none\"><img src=\"lib/save_sm.png\" border=\"0\"".getTooltip("save_changes")."></a>";
	break;
	case "del":
		if ($baseTable=="chemical_storage") {
			// different icon
			$retval.="<a href=\"Javascript:del();\" class=\"imgButtonSm\" id=\"btn_del\"><img src=\"lib/".($g_settings["dispose_instead_of_delete"]?"dispose_sm":"del_sm").".png\" border=\"0\"".getTooltip("delete")."></a>";
			$required_permissions=_chemical_edit+_chemical_delete;
		} else {
			$retval.="<a href=\"Javascript:del();\" class=\"imgButtonSm\" id=\"btn_del\"><img src=\"lib/del_sm.png\" border=\"0\"".getTooltip("delete")."></a>";
		}
	break;
	case "merge":
		$retval.="<a href=\"Javascript:startMerge();\" class=\"imgButtonSm\"><img src=\"lib/merge_sm.png\" border=\"0\"".getTooltip("merge")."></a>";
	break;
	case "save_version":
		$retval.="<a href=\"Javascript:saveNewVersion(); \" class=\"imgButtonSm\"><img src=\"lib/version_sm.png\" border=\"0\"".getTooltip("save_version")."></a>";
	break;
	case "versions_list":
		$retval.="<a href=\"Javascript:showVersionsList(); \" id=\"btn_version\" class=\"imgButtonSm\"><img src=\"lib/version_list_sm.png\" border=\"0\"".getTooltip("versions")."></a>";
	break;
	case "do_select":
		$retval.="<a href=\"javascript:transferThisPkToUID();\" class=\"imgButtonSm\"><img src=\"lib/select_sm.png\" border=\"0\"".getTooltip("do_select")."></a>";
	break;
	
	case "dymo":
		$retval.="<a id=\"dymo\" href=\"javascript:void(0);\" class=\"imgButtonSm\" title=".fixStr(s("print_dymo_label"))." style=\"display:none\" onClick=\"printDymoLabel();\"><img src=\"lib/print_label_sm.png\" border=\"0\"></a>";
	break;
	case "auto_trans":
		$retval.="<a href=\"javascript:void transferGCs(0);\" class=\"imgButtonSm\" id=\"btn_transfer\" title=".fixStr(s("transfer_gc1").getTransferDevices(0).s("transfer_gc2"))." onMouseover=\"showOverlayId(this,&quot;transferMenu&quot;,0,0,8); \" onMouseout=\"hideOverlayId(&quot;transferMenu&quot;);\"><img src=\"lib/auto_trans_sm.png\" border=\"0\"></a>";
	break;
	case "laws":
		$retval.="<a href=\"javascript:void(0);\" class=\"imgButtonSm\" title=".fixStr(s("laws"))." onMouseover=\"showOverlayId(this,&quot;lawsMenu&quot;,0,0,8); \" onMouseout=\"hideOverlayId(&quot;lawsMenu&quot;);\"><img src=\"lib/laws_sm.png\" border=\"0\"></a>";
	break;
	case "copy_reaction":
		$required_permissions=_lj_edit+_lj_edit_own;
		$retval.="<a href=\"javascript:void copyReaction();\" class=\"imgButtonSm\"><nobr><img src=\"lib/reaction_sm.png\" border=\"0\"".getTooltip("copyReaction").">x</nobr></a>";
	break;
	case "reaction_pdf":
		$retval.="<a href=\"javascript:void downloadRxnPDF();\" class=\"imgButtonSm\"><img src=\"lib/report_download.png\" border=\"0\"".getTooltip("downloadPDF")."></a>";
		if ($belab_options) {
			$retval.="</td><td><a href=\"javascript:void transferRxnPDF();\" class=\"imgButtonSm\"><img src=\"lib/report_archive.png\" border=\"0\"".getTooltip("transferPDFToBelab")."></a>";
		}
	break;
	case "compare_reaction":
		$retval.="<a href=\"Javascript:setRefRxn(&quot;set&quot;,&quot;edit&quot;);\" class=\"imgButtonSm\" style=\"margin-right:0px\"><img src=\"lib/compare_rxn_sm.png\" border=\"0\"".getTooltip("compare_rxn_ref")."></a>";
	break;
	
	case "set_order_status":
		$required_permissions=_order_approve;
		$retval.="<a id=\"btn_supplier_delivered\" href=\"Javascript:setOrderStatus(3);\" class=\"imgButtonSm\"><img src=\"lib/supplier_delivered_sm.png\" border=\"0\"".getTooltip("ready_for_collection")."></a></td>
			<td><a id=\"btn_customer_delivered\" href=\"Javascript:setOrderStatus(4);\" class=\"imgButtonSm\"><img src=\"lib/customer_delivered_sm.png\" border=\"0\"".getTooltip("order_collected")."></a>";
	break;
	case "return_rent":
		$required_permissions=_order_approve;
		$retval.="<a id=\"btn_return_rent\" href=\"Javascript:returnNow();\" class=\"imgButtonSm\"><img src=\"lib/bring_back_sm.png\" border=\"0\"".getTooltip("return_rent")."></a>";
	break;
	case "accept_order":
		$required_permissions=_order_accept;
		$retval.="<nobr><a id=\"btn_accept_order\" href=\"Javascript:acceptOrder();\" class=\"imgButtonSm\"><img src=\"lib/accepted_order_sm.png\" border=\"0\"".getTooltip("accept_order")."></a></nobr>";
	break;
	case "approve_chemical_order":
		$required_permissions=_order_approve;
		$retval.="<a id=\"btn_confirm_order\" href=\"Javascript:approveOrder();\" class=\"imgButtonSm\"><img src=\"lib/save_sm.png\" border=\"0\"".getTooltip("confirm_order")."></a>";
	break;
	case "new_chemical_order":
		$required_permissions=_order_order;
		$retval.="<nobr><a href=\"Javascript:orderThis();\" class=\"imgButtonSm\"><img src=\"lib/chemical_order_sm.png\" border=\"0\"".getTooltip("prepare_order").">+</a></nobr>";
	break;
	case "add_package":
		$required_permissions=_chemical_create+_chemical_edit+_chemical_edit_own;
		$retval.="<nobr><a id=\"btn_add_package\" href=\"Javascript:addPackage();\" class=\"imgButtonSm\"><img src=\"lib/add_chemical_to_molecule_sm.png\" border=\"0\"".getTooltip("new_cheminstor_for_molecule").">+</a></nobr>";
	break;
	case "scale_reaction":
		$retval.="<a href=\"Javascript:void scaleReaction();\" class=\"imgButtonSm\"><img src=\"lib/scale_rxn_sm.png\" border=\"0\"".getTooltip("scale_reaction")."></a>";
	break;
	case "search_commercial":
		// href set by JS
		$retval.="<a id=\"search_commercial\" href=\"Javascript:void(0)\" class=\"imgButtonSm\"><img src=\"lib/supplier_sm.png\" border=\"0\"".getTooltip("search_commercial")."></a></td>
			<td><a id=\"pred_nmr\" href=\"Javascript:void(0)\" class=\"imgButtonSm\" target=\"_blank\"><img src=\"lib/pred_nmr_sm.png\" border=\"0\"".getTooltip("pred_nmr")."></a>";
	break;
	case "borrow":
		$required_permissions=_chemical_edit+_chemical_borrow;
		$retval.="<a id=\"btn_borrow\" href=\"javascript:borrowEdit(".$person_id.");\" style=\"display:none\" class=\"imgButtonSm\"><img src=\"lib/borrow_sm.png\" border=\"0\"".getTooltip("borrow")."></a>
<a id=\"btn_return\" href=\"javascript:borrowEdit();\" style=\"display:none\" class=\"imgButtonSm\"><img src=\"lib/bring_back_sm.png\" border=\"0\"".getTooltip("return").">";
	break;
	case "undelete":
		$required_permissions=_chemical_edit;
		$retval.="<a id=\"btn_undelete\" href=\"javascript:undeleteChemical();\" style=\"display:none\" class=\"imgButtonSm\"><img src=\"lib/undelete_sm.png\" border=\"0\"".getTooltip("undelete_chemical")."></a>";
	break;
	default:
		return "";
	}
	if (!empty($required_permissions) && ($permissions & $required_permissions)==0) {
		return;
	}
	$retval.="</td>";
	return $retval;
}

function cacheDataset($a,$fake=false) {
	global $result,$pk_name;
	if ($fake) { // say as if it were there, will be loaded delayed, fake-load prevents async reload
		echo "as(\"isThere\",true,".$result[$a]["db_id"].",".fixNull($result[$a][$pk_name]).");\n";
	}
	else {
		/*$json=json_encode($result[$a]);
		if (isEmptyStr($json)) {
			echo "/* could not encode:\n";
			var_dump($result[$a]);
			echo "* /";
		}
		else {
			echo "cacheDataset(".$result[$a]["db_id"].",".fixNull($result[$a][$pk_name]).",(".$json.")); "; // kein \n hier, Parameter für window.setTimeout
		}*/
		echo "cacheDataset(".$result[$a]["db_id"].",".fixNull($result[$a][$pk_name]).",(".safe_json_encode($result[$a]).")); "; // kein \n hier, Parameter für window.setTimeout
	}
}

function resetSDB(& $result) {
	// reload from supplier
	$result[0]["safety_sheet_url"]="-".$result[0]["safety_sheet_url"];
	$result[0]["alt_safety_sheet_url"]="-".$result[0]["alt_safety_sheet_url"];
	$result[0]["default_safety_sheet_url"]="-".$result[0]["default_safety_sheet_url"];
	$result[0]["alt_default_safety_sheet_url"]="-".$result[0]["alt_default_safety_sheet_url"];
}

?>