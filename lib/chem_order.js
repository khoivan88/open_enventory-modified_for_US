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

var warned=false;

function syncAccNo(costCentreId,accNoId) {
	var rawResults=controlData[costCentreId]["rawResults"],costCentreValue=getInputValue(costCentreId);
	for (var b=0,max=rawResults.length;b<max;b++) {
		if (rawResults[b]["cost_centre"]==costCentreValue) {
			setInputValue(accNoId,rawResults[b]["acc_no"]);
			return;
		}
	}
}

function evalNumberPackages(list_int_name,UID,int_name,group) {
	if (updateInProgress) {
		return;
	}
	updateInProgress=true;
	
	// get input values
	if (UID) { // SIL
		var package_amount=SILgetValue(list_int_name,UID,"package_amount"),package_amount_unit=SILgetValue(list_int_name,UID,"package_amount_unit");
		var number_packages_text=SILgetValue(list_int_name,UID,"number_packages"),density_20=SILgetValue(list_int_name,UID,"density_20");
		// ,undefined,"rounded"
	}
	else {
		var package_amount=getInputValue("package_amount"),package_amount_unit=getInputValue("package_amount_unit");
		var number_packages_text=getInputValue("number_packages"),density_20=getInputValue("density_20");
	}
	
	// calc factor
	var number_packages=getPackageFactor(package_amount,package_amount_unit,number_packages_text,density_20);
	
	// hide if number_packages==number_packages_text
	if (number_packages==number_packages_text) {
		if (package_amount_unit!="") {
			number_packages_text=(package_amount*number_packages)+" "+package_amount_unit;
		}
		else {
			number_packages_text="";
		}
	}
	
	// write entered value to number_packages_text and factor to number_packages
	if (UID) { // SIL
		SILsetValueUID(list_int_name,UID,undefined,undefined,"number_packages",undefined,{number_packages:number_packages}); // rw
		SILsetValueUID(list_int_name,UID,undefined,undefined,"number_packages_text",undefined,{number_packages_text:number_packages_text}); // ro
		var rw=SILgetObj(list_int_name,UID,"number_packages");
	}
	else {
		setInputValue(number_packages,"number_packages");
		setInputValue(number_packages_text,"number_packages_text");
		var rw=$("number_packages");
	}
	
	// call change routine
	if (is_function(rw.onkeyup)) { // NICHT onchange!!
		rw.onkeyup.call();
	}
	
	updateInProgress=false;
}

function setGlobalDiscount(list_int_name,discount) {
	// price=so_price*1+x%
	discount=1+parseFloat(discount)/100;
	if (isNaN(discount)) {
		return;
	}
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		var UID=controlData[list_int_name]["UIDs"][b];
		
		SILsetValue(round(SILgetValue(list_int_name,UID,"so_price")*discount,2,3),list_int_name,UID,"price");
		SILObjTouchOnchange(list_int_name,UID,"price");
	}
}

function askGlobalDiscount() {
	var discount=prompt(s("ask_global_discount"));
	if (discount!=null) {
		setGlobalDiscount("accepted_order",discount);
	}
}

function updateSupplier() {
	if (readOnly==false) {
		var int_name="vendor_id";
		var vendor_id=getInputValue(int_name);
		readOnlyForm("v_institution",vendor_id);
		//~ showForm("v_institution",vendor_id=="");
		visibleObj("v_institution_FS",vendor_id=="");
		
		if (vendor_id=="") {
			setChecked("vendor",true);
			setChecked("commercial",true);
		}
		// show data for selected vendor
		//~ if (vendor_id!="") {
			//~ PkSelectSetData(int_name,["v_institution_name"]); // ,"v_person_name","v_department_name","v_street","v_street_number","v_postcode","v_city","v_country","v_institution_type"
		//~ }
		
		// allow assignment of selected institution_id
		showControl("permanent_assignment",vendor_id);
	}
}

function getTotalPrice(num,price,currency) {
	num=parseFloat(num);
	price=parseFloat(price);
	if (isNaN(price)) {
		return "";
	}
	if (isNaN(num) || num<0) {
		num=0;
	}
	return round(num*price,2,3)+"&nbsp;"+currency;
}

function updateTotal(list_int_name,UID,int_name) { // no group
	if (!UID) { // single
		var num=getInputValue("number_packages"),price=getInputValue("price"),currency=getInputValue("price_currency");
		setInputValue(currency,"so_price_currency");
		setiHTML("total_price",getTotalPrice(num,price,currency));
	}
	else { // SIL
		var num=SILgetValue(list_int_name,UID,"number_packages"),price=SILgetValue(list_int_name,UID,"price"),currency=SILgetValue(list_int_name,UID,"price_currency");
		SILsetValueUID(list_int_name,UID,undefined,undefined,"so_price_currency",undefined,{so_price_currency:currency});
		SILsetSpan(getTotalPrice(num,price,currency),list_int_name,UID,"total_price");
	}
}

var updatePackageInProgress;
function updatePackageAmount(list_int_name,UID,int_name) { // no group
	if (updatePackageInProgress) {
		return;
	}
	updatePackageInProgress=true;
	if (UID) { // SIL
		var number_packages_text=SILgetValue(list_int_name,UID,"number_packages_text");
	}
	else {
		var number_packages_text=getInputValue("number_packages_text");
	}
	
	// amount entered: what should be updated, number or amount??
	if (number_packages_text!="") {
		if (!confirm(s("update_number_packages_text"))) {
			// recalc number of packages
			if (UID) { // SIL
				SILsetValueUID(list_int_name,UID,undefined,undefined,"number_packages",undefined,{number_packages:number_packages_text}); // rw
			}
			else {
				setInputValue(number_packages_text,"number_packages");
			}
		}
		
		// recalc amount
		evalNumberPackages(list_int_name,UID,int_name);
	}
	
	updateTotal(list_int_name,UID,int_name);
	updatePackageInProgress=false;
}

var queryValue;
function autoSearch2(list_int_name,UID,int_name,group) {
	if (queryValue && queryValue==SILgetValue(list_int_name,UID,int_name,group)) {
		var url="chooseAsync.php?desired_action=loadDataForSearch&table=supplier_offer&list_int_name="+list_int_name+"&UID="+UID+"&int_name="+int_name+"&group="+group+"&query=<0>&crit0="+int_name+"&op0=ex&val0="+queryValue;
		setFrameURL("comm",url);
	}
}

function autoSearch(list_int_name,UID,int_name,group) {
	queryValue=SILgetValue(list_int_name,UID,int_name,group);
	window.setTimeout(function () { autoSearch2(list_int_name,UID,int_name,group); },800);
}

function searchSupplierOffer(list_int_name,UID,int_name,group) {
	//~ var tables="supplier_offer";
	var tables="supplier_offer,molecule";
	openSearchWin(list_int_name,UID,int_name,group,tables); //,params
}

function updateSelectedAlternative(list_int_name,UID,int_name) {
	// set values in upper form
	var transferFields=["name","cas_nr","catNo","beautifulCatNo","package_amount","package_amount_unit","price","price_currency","number_packages","vat_rate"],temp_values;
	for (var b=0,max=transferFields.length;b<max;b++) {
		int_name=transferFields[b];
		temp_values={};
		temp_values[int_name]=SILgetValue(list_int_name,UID,int_name);
		setControl(int_name,temp_values);
		//~ setInputValue(int_name,SILgetValue(list_int_name,UID,int_name));
	}
	
	// take price as regular price
	int_name="so_price";
	temp_values={};
	temp_values[int_name]=SILgetValue(list_int_name,UID,"price");
	setControl(int_name,temp_values);
	
	setInputValue("selected_alternative_id",SILgetValue(list_int_name,UID,"order_alternative_id"));
	updateTotal();
	
	int_name="supplier";
	var supplier=SILgetValue(list_int_name,UID,int_name);
	setControl(int_name,{supplier:supplier});
	
	// go through controlData and find the right supplier, if possible
	int_name="vendor_id";
	if (controlData[int_name]["data"]) for (var b=0,max=controlData[int_name]["data"].length;b<max;b++) {
		
		if (controlData[int_name]["data"][b]["institution_codes"]) for (var c=0,max2=controlData[int_name]["data"][b]["institution_codes"].length;c<max2;c++) {
			if (controlData[int_name]["data"][b]["institution_codes"][c]["supplier_code"]==supplier) {
				// we found it
				setInputValue(int_name,controlData[int_name]["data"][b]["v_institution_id"]);
			}
		}
	}
	//~ switchLager(supplier);
}

function setLockSelectAlternative() { // thisLocked: darf nix an Radiobuttons ändern
	// change allowed?
	var thisLocked=(readOnly || getInputValue("may_change_supplier")==3),list_int_name="order_alternative",int_name="customer_selected_alternative_id";
	
	defaultReadOnlyControl(list_int_name,(thisLocked?"":"never"));
	readOnlyControl(list_int_name,thisLocked);
	
	SILlockSubitemlist(list_int_name,true); // lock all
	SILlockField(list_int_name,undefined,int_name,undefined,thisLocked); // lock/unlock radio buttons
}

/*
function switchLager(supplier) {
	if (typeof supplier=="object") { // "this"
		
	}
	else if (supplier==ausgabe_name) {
		setInputValue("btn_lagerchem","lager");
	}
	else if (supplier) { // all other
		setInputValue("btn_lagerchem","sonder");
	}
	var visible=(getInputValue("btn_lagerchem")=="sonder"); // ignore supplier in backend for "lager"
	showControl("supplier",visible);
}*/

function updateTotalOrder(list_int_name,UID,int_name) {
	//~ warnUID(UID);
	// sync currency
	SILsetValue(SILgetValue(list_int_name,UID,"price_currency"),list_int_name,UID,"so_price_currency");
	var net_total=fixNull(getInputValue("fixed_costs")),vat_sum=getVAT(net_total,fixNull(getInputValue("fixed_costs_vat_rate"))),currency=getInputValue("currency");
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		var UID=controlData[list_int_name]["UIDs"][b];
		var this_currency=SILgetValue(list_int_name,UID,"price_currency"),total=fixNull(SILgetValue(list_int_name,UID,"price"))*fixNull(SILgetValue(list_int_name,UID,"number_packages"));
		
		setiHTML(SILgetObjName(list_int_name,UID,"total_price"),getTotalPrice(1,total,this_currency));
		if (currency==this_currency) {
			net_total+=total;
			vat_sum+=getVAT(total,fixNull(SILgetValue(list_int_name,UID,"vat_rate")));
		}
	}
	
	// set span
	setiHTML("grand_total",s("net_total")+" "+round(net_total,2,3)+"&nbsp;"+currency+"<br>"+s("vat_sum")+" "+round(vat_sum,2,3)+"&nbsp;"+currency+"<br>"+s("gross_total")+" "+round(net_total+vat_sum,2,3)+"&nbsp;"+currency+"<br>");
}

function updateCurrencyOrder(list_int_name,UID,int_name) {
	//~ warnUID(UID);
	var currency=getInputValue("currency");
	SILclearFilter(list_int_name);
	SILaddFilter(list_int_name,"price_currency","eq",currency);
	updateTotalOrder(list_int_name,UID,int_name);
}

function warnUID(UID) {
	if (UID!=undefined && warned==false) {
		warned=true;
		alert(s("warn_change_price"));
	}
}

function sort_order_comp(b,c) { // absteigend
	return (b["total_price"]<c["total_price"]?1:-1);
}

function getKleinauftragH(besteller,central_cost_centre,firma,kleinauftragNr,kdNr,variante,pageBreakBefore) {
	var varianteText="";
	switch (variante) {
	case 1:
		varianteText="für Beschaffung";
	break;
	case 2:
		varianteText="für Besteller";
	break;
	}
	var retval="<table cellspacing=\"0\" class=\"kleinauftrag\" style=\"";
	if (pageBreakBefore) {
		retval+="page-break-before:always;";
	}
	retval+="table-layout:fixed;width=181mm;height=138mm\"><tbody>";
	retval+="<colgroup>";
	retval+="<col style=\"width:10mm\">";
	retval+="<col style=\"width:20mm\">";
	retval+="<col style=\"width:34mm\">";
	retval+="<col style=\"width:31mm\">";
	retval+="<col style=\"width:23mm\">";
	retval+="<col style=\"width:33mm\">";
	retval+="<col style=\"width:30mm\">";
	retval+="</colgroup>";
	retval+="<tr>";
	retval+="<td colspan=\"3\" rowspan=\"2\"><span class=\"big\" style=\"font-weight:bold\">TU Kaiserslautern</span><br><span class=\"normal\">Abteilung Controlling<br>Gottlieb-Daimler-Straße<br>67653 Kaiserslautern<br>Geb. 47 Telefon (06 31) 2 05 - .......<br>USt-ID-Nr.: DE 148 642 087</span></td>";
	
	retval+="<td style=\"height:15mm;border:1mm solid black;border-bottom:0.5mm solid black;vertical-align:top\" colspan=\"2\">Besteller: ";
	
	retval+=besteller;
	retval+="<br>Datum: ";
	retval+=today();
	retval+="</td>";
	
	retval+="<td colspan=\"2\" style=\"height:15mm;vertical-align:top\">Firma: ";
	
	retval+=firma;
	retval+="</td>";
	retval+="</tr>";
	retval+="<tr>";
	
	retval+="<td colspan=\"2\" style=\"height:5mm;border-left:1mm solid black;border-right:1mm solid black\"><span class=\"normal\" style=\"font-weight:bold\">Höchstbetrag: &euro; ";
	
	retval+=maxKleinauftrag;
	retval+=",-</span></td><td colspan=\"2\">Kd.-Nr.:";
	retval+=kdNr;
	retval+="</td></tr>";
	retval+="<tr>";
	retval+="<td colspan=\"3\" rowspan=\"2\" class=\"big\" style=\"font-weight:bold\">KLEINAUFTRAG Nr.</td>";
	
	retval+="<td style=\"height:7mm;border-left:1mm solid black;border-top:1mm solid black\" class=\"big\">";
	
	retval+=kleinauftragNr;
	retval+="<span style=\"font-weight:bold\">9/</span></td>";
	
	retval+="<td style=\"height:7mm;border-right:1mm solid black;border-top:1mm solid black\">Kostenstelle:<br><span class=\"big\">";
	
	//~ retval+=ownCostCentre;
	retval+=central_cost_centre;
	retval+="</span></td>";
	retval+="<td colspan=\"2\" rowspan=\"2\" class=\"big\" style=\"vertical-align:middle;text-align:center;font-weight:bold\">";
	if (varianteText!="") {
		retval+="<span style=\"border:1mm solid black\">";
		retval+=varianteText;
		retval+="</span>";
	}
	retval+="</td>";
	retval+="</tr>";
	retval+="<tr>";
	
	retval+="<td style=\"height:7mm;font-weight:bold;border:1mm solid black;border-top:0.5mm solid black\" colspan=\"2\">(bei Schriftwechsel unbedingt angeben)</td>";
	
	retval+="</tr>";
	retval+="<tr>";
	retval+="<td style=\"text-align:center;height:6mm\">Pos.</td>";
	retval+="<td style=\"text-align:center;height:6mm\">Menge</td>";
	retval+="<td style=\"text-align:center;height:6mm\" colspan=\"4\">Artikel/Bestell-Nr.</td>";
	retval+="<td style=\"text-align:center;height:6mm\">Nettopreis</td>";
	retval+="</tr>";
	return retval;
}

function getKleinauftragL(Pos,Menge,ArtNo,Nettopreis) {
	var retval="<tr>";
	retval+="<td style=\"text-align:center;height:6mm\">";
	retval+=defNbsp(Pos);
	retval+="</td>";
	retval+="<td style=\"text-align:center;height:6mm\">";
	retval+=defNbsp(Menge);
	retval+="</td>";
	retval+="<td style=\"text-align:center;height:6mm\" colspan=\"4\">";
	retval+=defNbsp(ArtNo);
	retval+="</td>";
	retval+="<td style=\"text-align:right;height:6mm\">";
	if (Nettopreis!=undefined) {
		retval+=round(Nettopreis,2,3);
	}
	else {
		retval+="&nbsp;";
	}
	retval+="</td>";
	retval+="</tr>";
	return retval;
}

function getKleinauftragF() {
	var retval="<tr>";
	retval+="<td style=\"height:22mm\" colspan=\"4\" class=\"normal\"><span style=\"font-weight:bold\">Hinweis für den Lieferer:</span><br>Abrechnung ist nur möglich unter Angabe der Auftrags-<br>Nr. und des Bestellers, zu unseren Auftragsbedingungen<br>(siehe verw.uni-kl.de/Informationen/für alle)<br><span style=\"font-weight:bold\">Lieferanschrift: Zentrale Warenannahme<br>Geb. 47 Telefon (06 31) 2 05-23 56</span></td>";
	retval+="<td style=\"height:25mm;vertical-align:top\" colspan=\"3\">Stempel und Unterschrift</td>";
	retval+="</tr>";
	retval+="<tr><td style=\"height:1mm;border:0px solid black\" colspan=\"7\">&nbsp</td></tr>";
	retval+="</tbody>";
	retval+="</table>";
	return retval;
}

function setAdressTable(values) {
	setiHTML("addresses","<table width=\"100%\"><tbody><tr><td width=\"50%\"><b>"+s("to")+":</b><br>"+getFormattedAdress(values)+"</td><td width=\"50%\"><b>"+s("from")+":</b><br>"+own_address+"</td></tr></tbody></table>");
}

function updatePermissions(thisValue) {
	var ordered_by_person=getControlValue("ordered_by_person"),other_db_id=getControlValue("other_db_id"),order_status=parseInt(getControlValue("order_status")),accepted_by_user=getControlValue("accepted_by_user");
	var is_customer=(!editMode || ((other_db_id=="" || other_db_id==-1) && a_db_id==-1 && ordered_by_person==person_id));
	var is_the_order_manager=(accepted_by_user==db_user);
	var customer_may_change=(order_status<=2 && isEmptyStr(accepted_by_user));
	var order_manager_may_take=(order_status==2 && isEmptyStr(accepted_by_user));
	var mayChangeAnything=((is_customer && customer_may_change) || (is_order_manager && order_manager_may_take) || is_the_order_manager);
	var readOnly1=(mayChangeAnything?"":"always");
	var readOnly2=(is_order_manager?"":"always");
	var readOnly3=(is_customer?"":"always");
	formulare["chemical_order"]["disableEdit"]=!mayChangeAnything;
	updateButtons();
	if (thisValue==false && is_customer==false && is_order_manager==false) {
		return false;
	}
	defaultReadOnlyControl("order_alternative",readOnly1);
	defaultReadOnlyControl("order_cost_centre",readOnly1);
	defaultReadOnlyControl("order_acc_no",readOnly1);
	defaultReadOnlyControl("order_status",readOnly1);
	defaultReadOnlyControl("billing_date",readOnly2);
	defaultReadOnlyControl("central_comment",readOnly2);
	defaultReadOnlyControl("customer_comment",readOnly3);
}


function getSettlementTop(kst,kst_name,from_date,to_date) {
	return "<img style=\"float:left;margin-right:16px\" border=\"0\" width=\"205\" height=\"56\" src=\"lib/uni-logo.gif\"><img style=\"float:right\" border=\"0\" width=\"192\" height=\"64\" src=\"lib/chemielogo.gif\">L. Napast<br>Fachbereich Chemie<br>Chemikalienausgabe<br>Tel. 2470/2520<br>Chemikalienrechnung für Kostenstelle <b>"+kst+"</b>"+ifnotempty(", <br>",kst_name)+"<br>"+ifnotempty(" vom ",from_date)+" bis "+to_date;
}

function getSettlementH(currency) {
	return "<table class=\"subitemlist\"><thead><tr><td>"+s("acc_no")+"</td><td>"+s("beautifulCatNo")+"</td><td>"+s("name")+"</td><td class=\"numeric\">"+s("amount")+"</td><td class=\"numeric\">"+s("price")+" ["+currency+"]</td><td class=\"numeric\">"+s("vat_rate")+" [%]</td></tr></thead><tbody><colgroup><col><col><col><col><col><col></colgroup>"; // numerische Spalten rechtsbündig
}

function getSettlementL(name,beautifulCatNo,amount,price,vat_rate,acc_no) {
	return "<tr><td>"+defNbsp(acc_no)+"</td><td>"+defBlank(beautifulCatNo)+"</td><td>"+defBlank(name)+"</td><td class=\"numeric\">"+defBlank(amount)+"</td><td class=\"numeric\">"+round(price,2,3)+"</td><td class=\"numeric\">"+defBlank(vat_rate)+"</td></tr>";
}

function getSettlementF(net_total,vat_sum,lagerpauschale,col_count) {
	if (!col_count) {
		col_count=6;
	}
	var retval="<tr><td colspan=\""+col_count+"\"><hr></td></tr>";
	retval+="<tr><td colspan=\""+(col_count-2)+"\" class=\"numeric\">"+s("net_total")+"</td><td class=\"numeric\">"+round(net_total,2,3)+"</td><td></td></tr>";
	retval+="<tr><td colspan=\""+(col_count-2)+"\" class=\"numeric\">"+s("vat_sum")+"</td><td class=\"numeric\">"+round(vat_sum,2,3)+"</td><td></td></tr>";
	retval+="<tr><td colspan=\""+col_count+"\"><hr></td></tr>";
	retval+="<tr><td colspan=\""+(col_count-2)+"\" class=\"numeric\">"+s("gross_total")+"</td><td class=\"numeric\">"+round((net_total+vat_sum),2,3)+"</td><td></td></tr>";
	if (lagerpauschale) {
		retval+="<tr><td colspan=\""+(col_count-2)+"\" class=\"numeric\">"+s("lagerpauschale")+" ("+lagerpauschale+"%)</td><td class=\"numeric\">"+round((net_total*lagerpauschale/100),2,3)+"</td><td></td></tr>";
	}
	return retval;
}

function getVAT(amount,rate) {
	return amount*rate/100;
}

function getKstName(kst) {
	for (var b=0,max=cost_centre.length;b<max;b++) {
		if (kst==cost_centre[b]["cost_centre"]) {
			return cost_centre[b]["cost_centre_name"];
		}
	}
	return "";
}

function getSettlementPrintout() {
	var retval="",iHTML={},idx=0,chem_order=dataCache[a_db_id][a_pk]["accepted_order"],rent=dataCache[a_db_id][a_pk]["rent"],lagerpauschale=def0(dataCache[a_db_id][a_pk]["lagerpauschale"]);
	var net_total={},vat_sum={},currency=getCacheValue("currency");
	var from_date=toGerDate(getCacheValue("from_date")),to_date=toGerDate(getCacheValue("to_date")),name,kst,acc_no,supplier,firstDone;
	var fixed_costs_share=s("fixed_costs_share"),days=s("days");
	
	//~ var lists=[s("flaschenmiete"),s("lagerchemikalien"),s("sonderchemikalien")];
	
	if (rent) for (var b=0,max2=rent.length;b<max2;b++) {
		kst=rent[b]["order_cost_centre_cp"];
		acc_no=rent[b]["order_acc_no_cp"];
		
		// gibt es schon einen Eintrag für diese Kst?
		//~ if (!iHTML[kst]) {
		if (typeof iHTML[kst]!="object") {
			iHTML[kst]=[];
			net_total[kst]=0;
			vat_sum[kst]=0;
		}
		//~ if (!iHTML[kst][idx]) {
		if (typeof iHTML[kst][idx]!="object") {
			iHTML[kst][idx]=[];
		}
		
		var item_sum=fixNull(rent[b]["grand_total_rent"]),item_vat=fixNull(rent[b]["vat_rate"]);
		iHTML[kst][idx].push(getSettlementL(rent[b]["item_identifier"],"",ifnotempty("",rent[b]["days_count"],"&nbsp;"+days),item_sum,item_vat,acc_no));
		net_total[kst]+=item_sum;
		vat_sum[kst]+=getVAT(item_sum,item_vat);
	}
	
	if (chem_order) for (var b=0,max2=chem_order.length;b<max2;b++) {
		kst=chem_order[b]["order_cost_centre_cp"];
		acc_no=chem_order[b]["order_acc_no_cp"];
		supplier=chem_order[b]["supplier"];
		
		name=chem_order[b]["name"];
		if (supplier.toLowerCase()==ausgabe_name) { // Lagerchemikalie
			idx=1;
		}
		else { // Sonderchemikalie
			name+=" ("+chem_order[b]["v_institution_name"]+")";
			idx=2;
		}
		
		// item
		var number_packages=fixNull(chem_order[b]["number_packages"]);
		var item_sum=fixNull(chem_order[b]["price"])*number_packages,item_vat=fixNull(chem_order[b]["vat_rate"]),addLine="",fixed_costs_share=fixNull(chem_order[b]["fixed_costs_share"]);
		// filter out zeros
		if (item_sum==0 && fixed_costs_share==0) {
			continue;
		}
		
		addLine="";
		if (fixed_costs_share==0) {
			// do nothing
		}
		else if ( item_vat==fixNull(chem_order[b]["fixed_costs_share_vat_rate"]) ) { // gleicher MwSt-Satz: einrechnen
			item_sum+=fixed_costs_share;
		}
		else { // abweichend: Zusatzzeile
			addLine=getSettlementL(fixed_costs_share,"","",fixed_costs_share,chem_order[b]["fixed_costs_share_vat_rate"],acc_no);
			net_total[kst]+=fixed_costs_share;
			vat_sum[kst]+=getVAT(fixed_costs_share,fixNull(chem_order[b]["fixed_costs_share_vat_rate"]));
		}
		
		// gibt es schon einen Eintrag für diese Kst?
		//~ if (!iHTML[kst]) {
		if (typeof iHTML[kst]!="object") {
			iHTML[kst]=[];
			net_total[kst]=0;
			vat_sum[kst]=0;
		}
		//~ if (!iHTML[kst][idx]) {
		if (typeof iHTML[kst][idx]!="object") {
			iHTML[kst][idx]=[];
		}
		
		// <tr>...</tr>
		iHTML[kst][idx].push(getSettlementL(name,chem_order[b]["beautifulCatNo"],ifnotempty("",number_packages*chem_order[b]["package_amount"],"&nbsp;"+defBlank(chem_order[b]["package_amount_unit"])),item_sum,item_vat,acc_no));
		// <tr>...</tr>
		iHTML[kst][idx].push(addLine);
		net_total[kst]+=item_sum;
		vat_sum[kst]+=getVAT(item_sum,item_vat);
	}
	
	for (var kst in iHTML) {
		// Umbruch
		if (firstDone) {
			retval+=getPageBreak();
		}
		firstDone=true;
		
		kst_name=getKstName(kst);
		// Überschrift
		retval+=getSettlementTop(kst,kst_name,from_date,to_date)+getSettlementH(currency);
		for (var idx in iHTML[kst]) {
			for (var b=0,max=iHTML[kst][idx].length;b<max;b++) {
				// Posten
				retval+=iHTML[kst][idx][b];
			}
		}
		// Summe
		retval+=getSettlementF(net_total[kst],vat_sum[kst],lagerpauschale);
		retval+="</tbody></table>";
	}
	
	return retval;
}
