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

function getSettlementTop(type,kst,kst_name,acc,from_date,to_date) {
	// von acc nur erste drei Buchstaben
	return "L. Napast<br>Fachbereich Chemie<br>Chemikalienausgabe<br>Tel. 2470/2520<br><b>"+type+"</b><br>Chemikalienrechnung für Kundennr.: "+acc.substr(0,3)+" vom "+from_date+" bis "+to_date+"<br>Kostenstelle <b>"+kst+"</b>, "+kst_name;
}

function getSettlementH(currency) {
	return "<table class=\"subitemlist\"><thead><tr><td>"+s("acc_no")+"</td><td>"+s("beautifulCatNo")+"</td><td>"+s("name")+"</td><td class=\"numeric\">"+s("amount")+"</td><td class=\"numeric\">"+s("price")+" ["+currency+"]</td><td class=\"numeric\">"+s("vat_rate")+" [%]</td></tr></thead><tbody><colgroup><col><col><col><col><col><col></colgroup>"; // numerische Spalten rechtsbündig
}

function getSettlementL(name,beautifulCatNo,amount,price,vat_rate,acc_no) {
	return "<tr><td>"+defNbsp(acc_no)+"</td><td>"+defBlank(beautifulCatNo)+"</td><td>"+defBlank(name)+"</td><td class=\"numeric\">"+defBlank(amount)+"</td><td class=\"numeric\">"+round(price,2,3)+"</td><td class=\"numeric\">"+defBlank(vat_rate)+"</td></tr>";
}

function getSettlementF(net_total,vat_sum) {
	var retval="<tr><td colspan=\"6\"><hr></td></tr>";
	retval+="<tr><td colspan=\"4\" class=\"numeric\">"+s("net_total")+"</td><td class=\"numeric\">"+round(net_total,2,3)+"</td><td></td></tr>";
	retval+="<tr><td colspan=\"4\" class=\"numeric\">"+s("vat_sum")+"</td><td class=\"numeric\">"+round(vat_sum,2,3)+"</td><td></td></tr>";
	retval+="<tr><td colspan=\"6\"><hr></td></tr>";
	retval+="<tr><td colspan=\"4\" class=\"numeric\">"+s("gross_total")+"</td><td class=\"numeric\">"+round((net_total+vat_sum),2,3)+"</td><td></td></tr>";
	retval+="</tbody></table>";
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
	var iHTML="",chem_order=dataCache[a_db_id][a_pk]["accepted_order"],rent=dataCache[a_db_id][a_pk]["rent"],net_total=0,vat_sum=0,currency=getCacheValue("currency"),from_date=toGerDate(getCacheValue("from_date")),to_date=toGerDate(getCacheValue("to_date")),name,kst,acc_no,supplier,firstDone;
	var fixed_costs_share=s("fixed_costs_share"),flaschenmiete=s("flaschenmiete"),days=s("days");
	// neue Kostenstelle: weitergehen
	// neues Konto: Tabelleneintrag
	// Lagerchemikalien
	// Sonderchemikalien
	var lists=[s("lagerchemikalien"),s("sonderchemikalien")];
	for (var d=0,max3=lists.length;d<max3;d++) {
		var line_found=false;
		if (chem_order) for (var b=0,max2=chem_order.length;b<max2;b++) {
			supplier=chem_order[b]["supplier"];
			if ( XOR(supplier.toLowerCase()!=ausgabe_name,d==1) ) { // wahr ist: 1. Durchgang: supplier!=ausgabe, 2.: d==1
				continue;
			}
			line_found=true;
			if (kst!=undefined && chem_order[b]["order_cost_centre_cp"]!=kst) {
				iHTML+=getSettlementF(net_total,vat_sum);
				net_total=0;
				vat_sum=0;
			}
			name=chem_order[b]["name"];
			if (d==1) {
				name+=" ("+supplier+")";
			}
			acc_no=chem_order[b]["order_acc_no_cp"];
			// new sheet
			if (chem_order[b]["order_cost_centre_cp"]!=kst) {
				// no break before 1st list
				if (firstDone) {
					iHTML+=getPageBreak();
				}
				firstDone=true;
				
				kst=chem_order[b]["order_cost_centre_cp"];
				kst_name=getKstName(kst);
				iHTML+=getSettlementTop(lists[d],kst,kst_name,acc_no,from_date,to_date)+getSettlementH(currency);
			}
			// item
			var item_sum=fixNull(chem_order[b]["price"]),item_vat=fixNull(chem_order[b]["vat_rate"]),addLine="";
			if ( item_vat==fixNull(chem_order[b]["fixed_costs_share_vat_rate"]) ) { // gleicher MwSt-Satz: einrechnen
				item_sum+=fixNull(chem_order[b]["fixed_costs_share"]);
			}
			else { // abweichend: Zusatzzeile
				addLine=getSettlementL(fixed_costs_share,"","",chem_order[b]["fixed_costs_share"],chem_order[b]["fixed_costs_share_vat_rate"],acc_no);
				net_total+=fixNull(chem_order[b]["fixed_costs_share"]);
				vat_sum+=getVAT(fixNull(chem_order[b]["fixed_costs_share"]),fixNull(chem_order[b]["fixed_costs_share_vat_rate"]));
			}
			iHTML+=getSettlementL(name,chem_order[b]["beautifulCatNo"],ifnotempty("",chem_order[b]["package_amount"],"&nbsp;"+defBlank(chem_order[b]["package_amount_unit"])),item_sum,item_vat,acc_no);
			iHTML+=addLine;
			net_total+=item_sum;
			vat_sum+=getVAT(item_sum,item_vat);
		}
		if (line_found) {
			// letzten Eintrag abschließen
			iHTML+=getSettlementF(net_total,vat_sum);
			
			// reset values for next loop
			net_total=0;
			vat_sum=0;
			var kst=undefined;
		}
	}
	
	// Flaschenmiete
	if (rent) {
		for (var b=0,max2=rent.length;b<max2;b++) {
			if (kst!=undefined && rent[b]["order_cost_centre_cp"]!=kst) {
				iHTML+=getSettlementF(net_total,vat_sum);
				net_total=0;
				vat_sum=0;
			}
			acc_no=rent[b]["order_acc_no_cp"];
			// new sheet
			if (rent[b]["order_cost_centre_cp"]!=kst) {
				// no break before 1st list
				if (firstDone) {
					iHTML+=getPageBreak();
				}
				firstDone=true;
				
				kst=rent[b]["order_cost_centre_cp"];
				kst_name=getKstName(kst);
				iHTML+=getSettlementTop(flaschenmiete,kst,kst_name,acc_no,from_date,to_date)+getSettlementH(currency);
			}
			var item_sum=fixNull(rent[b]["grand_total_rent"]),item_vat=fixNull(rent[b]["vat_rate"]);
			iHTML+=getSettlementL(rent[b]["item_identifier"],"",ifnotempty("",rent[b]["days_count"],"&nbsp;"+days),item_sum,item_vat,acc_no);
			net_total+=item_sum;
			vat_sum+=getVAT(item_sum,item_vat);
		}
		if (max2>0) {
			iHTML+=getSettlementF(net_total,vat_sum);
		}
	}
	
	return iHTML;
}
