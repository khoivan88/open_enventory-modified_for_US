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

// Javascript ChemFunctions

function showCHNTooltip(obj,emp_formula) {
	if (!emp_formula) {
		return;
	}
	var res=computeMolecule(emp_formula,1),text="<div class=\"structureOverlay\">C: "+round(res["C"]*100,2)+" %<br>H: "+round(res["H"]*100,2)+" %<br>N: "+round(res["N"]*100,2)+" %";
	if (res["S"]>0) {
		text+="<br>S: "+round(res["S"]*100,2)+" %";
	}
	text+="</div>";
	showBottomOverlay(obj,text,0,5);
}

function getApproxF(emp_formula) { // auf Tetradecan gesetzt
	if (!emp_formula) {
		return 1;
	}
	else {
		var molData=computeMolecule(emp_formula,1);
		return round(molData["C"]/0.848,2);
	}
}

function readSumFormulaPart(emp_formulaStr) { // only real atoms, no groups
	var emp_formula=[];
	var group=/([A-Z][a-z]*)(\d*)/g;
	while (formula=group.exec(emp_formulaStr)) {
		var multiplier=formula[2];
		if (multiplier==="") {
			multiplier=1;
		}
		else {
			multiplier=parseInt(multiplier);
		}
		if (typeof emp_formula[ formula[1] ]=="undefined") {
			emp_formula[ formula[1] ]=0;
		}
		emp_formula[ formula[1] ]+=multiplier;
	}
	return emp_formula;
}

function getSumFormulaSimilarity(sum1,sum2) {
	var retval=0,divisor=0;
	sum1=readSumFormulaPart(sum1);
	sum2=readSumFormulaPart(sum2);
	var key,num1,num2,keys=arr_merge(array_keys(sum1),array_keys(sum2));
	for (var b=0,max=keys.length;b<max;b++) {
		key=keys[b];
		num1=def0(sum1[key]);
		num2=def0(sum2[key]);
		retval+=Math.abs(num1-num2);
		divisor+=num1+num2;
	}
	if (divisor>0) {
		return 1-retval/divisor;
	}
	return 0;
}

function fixSumFormulaBrackets(emp_formulaStr) {
	// replace all groups by bracketed sums
	for (var sym in func_groups) {
		var oldText=new RegExp(sym,"g");
		emp_formulaStr=emp_formulaStr.replace(oldText,"("+func_groups[sym]+")");
	}
	
	// handle expressions like *3H2O
	var bracket=/([^\*]*)\*\s*(\d*)\s*([^\*]+)(.*)/;
	while (formula_data=bracket.exec(emp_formulaStr)) {
		// read parts
		var emp_formula=readSumFormulaPart(formula_data[3]);
		var multiplier=formula_data[2];
		if (multiplier==="") {
			multiplier=1;
		}
		else {
			multiplier=parseInt(multiplier);
		}
		var emp_formula_text="";
		// multiply by factor
		for (var atom in emp_formula) {
			emp_formula_text+=atom+(emp_formula[atom]*multiplier);
		}
		emp_formulaStr=formula_data[1]+emp_formula_text+formula_data[4];
	}
	
	// replace innermost brackets by sum formulae until there is none found any more
	var bracket=/(.*)\(([^\(^\)]*)\)(\d*)(.*)/;
	while (formula_data=bracket.exec(emp_formulaStr)) {
		// read parts
		var emp_formula=readSumFormulaPart(formula_data[2]);
		var multiplier=formula_data[3];
		if (multiplier==="") {
			multiplier=1;
		}
		else {
			multiplier=parseInt(multiplier);
		}
		var emp_formula_text="";
		// multiply by factor
		for (var atom in emp_formula) {
			emp_formula_text+=atom+(emp_formula[atom]*multiplier);
		}
		emp_formulaStr=formula_data[1]+emp_formula_text+formula_data[4];
	}
	
	return emp_formulaStr;
}



function computeMolecule(dataStr,flags) { // dataStr is molfile (flags&1==0) or empirical formula (flags&1) should set retval["chemFormula"] to "C5H6N+" o.Ã¤, flags&2==allowWildcards
	var retval={"MW":0,"mw_monoiso":0,"C":0,"H":0,"N":0,"S":0,"chemFormula":"","prettyPrint":""},formulaArray={},atom,totalCharge=0,maxH=0,atom,sym,num;
	
	if (!dataStr) {
		return retval;
	}

	if (flags&1) { // Summenformel
		dataStr=fixSumFormulaBrackets(dataStr);
		var searchAtom=/([A-Z%][a-z]?)(\d*)/g;
		while (result=searchAtom.exec(dataStr)) {
			sym=result[1];
			atom=atom_data[sym];
			if (atom && ((flags&2) || !atom["wc"]) ) {
				var number=result[2];
				if (number==="") {
					number=1;
				}
				else {
					number=parseInt(number);
				}
				if (!formulaArray[sym]) {
					formulaArray[sym]=number;
				}
				else {
					formulaArray[sym]+=number;
				}
			}
		}
	}
	else { // molfile
		var bonds=0,totalCharge=0,result,searchAtom = /[\r|\n|\|][\-\s\d\.]{31}\s{0,2}([A-Z\%][a-z]?)[^\d^\-]+[\d\-]+[^\d^\-]+([\d\-]+)/gi; // be more tolerant for chemDoodle
		var head=true,searchBond=/[\r|\n|\|]\s{0,2}(\d{1,3})\s{0,2}(\d{1,3})\s{0,2}(\d{1,3})/gi;
		// alert(dataStr);
		while (result=searchAtom.exec(dataStr)) {
			sym=trim(result[1]);
			if (result[1]=="*") { // do not include polymer links
				continue;
			}
			atom=atom_data[sym];
			if (atom && ((flags&2) || !atom["wc"]) ) {
				if (!formulaArray[sym]) {
					formulaArray[sym]=1;
				}
				else {
					formulaArray[sym]++;
				}
				maxH+=atom["v"];
				if (result[2]!=0) {
					totalCharge+=4-result[2];
				}
			}
		}
		while (result=searchBond.exec(dataStr)) {
			if (head) { // eine Zeile Kopf, dann Bindungen
				head=false;
			}
			else {
				bonds+=parseInt(result[3]);
			}
		}
		formulaArray["H"]+=maxH-2*bonds+totalCharge; // add implicit Hydrogens, not very safe
	}

	// make Hill form
	ksort(formulaArray);
	for (var arr=["C","H"],b=0,max=arr.length;b<max;b++) {
		sym=arr[b];
		num=formulaArray[sym];
		if (num>0) {
			retval["chemFormula"]+=sym;
			retval["prettyPrint"]+=sym;
			if (num>1) {
				retval["chemFormula"]+=num;
				retval["prettyPrint"]+="<sub>"+num+"</sub>";
			}
		}
	}
	for (var sym in formulaArray) {
		num=formulaArray[sym];
		retval["MW"]+=atom_data[sym]["m"]*num;
		retval["mw_monoiso"]+=atom_data[sym]["m_mono"]*num;
		if (!atom_data[sym]["hill"] && num>0) {
			retval["chemFormula"]+=sym;
			retval["prettyPrint"]+=sym;
			if (num>1) {
				retval["chemFormula"]+=num;
				retval["prettyPrint"]+="<sub>"+num+"</sub>";
			}
		}
	}
	
	for (var arr=["C","N","S"],b=0,max=arr.length;b<max;b++) {
		sym=arr[b];
		retval[sym]=def0(formulaArray[sym])*atom_data[sym]["m"]/retval["MW"];
	}
	retval["H"]=(def0(formulaArray["H"])*atom_data["H"]["m"]+def0(formulaArray["D"])*atom_data["D"]["m"])/retval["MW"]; // also D
	
	// add charge
	if (totalCharge==1) {
		retval["chemFormula"]+="+";
		retval["prettyPrint"]+="<sup>+</sup>";
	}
	else if (totalCharge==-1) {
		retval["chemFormula"]+="-";
		retval["prettyPrint"]+="<sup>-</sup>";
	}
	else if (totalCharge>1) {
		retval["chemFormula"]="("+retval["chemFormula"]+")"+totalCharge+"+";
		retval["prettyPrint"]+="<sup>"+totalCharge+"+</sup>";
	}
	else if (totalCharge<-1) {
		retval["chemFormula"]="("+retval["chemFormula"]+")"+(-totalCharge)+"-";
		retval["prettyPrint"]+="<sup>"+(-totalCharge)+"-</sup>";
	}
	return retval;
}

// http://www.sisweb.com/referenc/source/exactmaa.htm
atom_data={
	"H":{"an":1,"m":1.00794,"v":1,"hill":true,"m_mono":1.007825},
	"D":{"an":1,"m":2.01402,"v":1,"m_mono":2.014102},
	"T":{"an":1,"m":3.0160492,"v":1,"m_mono":3.0160492},
	"He":{"an":2,"m":4.002602,"v":0,"m_mono":4.002603},
	"Li":{"an":3,"m":6.941,"v":1,"m_mono":7.016005},
	"Be":{"an":4,"m":9.012182,"v":2,"m_mono":9.012183},
	"B":{"an":5,"m":10.811,"v":3,"m_mono":11.009305},
	"C":{"an":6,"m":12.011,"v":4,"hill":true,"m_mono":12.000000},
	"N":{"an":7,"m":14.00674,"v":3,"m_mono":14.003074},
	"O":{"an":8,"m":15.9994,"v":2,"m_mono":15.994915},
	"F":{"an":9,"m":18.9984032,"v":1,"m_mono":18.998403},
	"Ne":{"an":10,"m":20.1797,"v":0,"m_mono":19.992439},
	"Na":{"an":11,"m":22.989768,"v":1,"m_mono":22.989770},
	"Mg":{"an":12,"m":24.305,"v":2,"m_mono":23.985045},
	"Al":{"an":13,"m":26.9811539,"v":3,"m_mono":26.981541},
	"Si":{"an":14,"m":28.0855,"v":4,"m_mono":27.976928},
	"P":{"an":15,"m":30.973762,"v":3,"m_mono":30.973763},
	"S":{"an":16,"m":32.066,"v":2,"m_mono":31.972072},
	"Cl":{"an":17,"m":35.4527,"v":1,"m_mono":34.968853},
	"Ar":{"an":18,"m":39.948,"v":0,"m_mono":39.962383},
	"K":{"an":19,"m":39.0983,"v":1,"m_mono":38.963708},
	"Ca":{"an":20,"m":40.078,"v":2,"m_mono":39.962591},
	"Sc":{"an":21,"m":44.95591,"v":0,"m_mono":44.955914},
	"Ti":{"an":22,"m":47.867,"v":0,"m_mono":47.947947},
	"V":{"an":23,"m":50.9415,"v":0,"m_mono":50.943963},
	"Cr":{"an":24,"m":51.9961,"v":0,"m_mono":51.940510},
	"Mn":{"an":25,"m":54.93805,"v":0,"m_mono":54.938046},
	"Fe":{"an":26,"m":55.845,"v":0,"m_mono":55.934939},
	"Co":{"an":27,"m":58.9332,"v":0,"m_mono":58.933198},
	"Ni":{"an":28,"m":58.6934,"v":0,"m_mono":57.935347},
	"Cu":{"an":29,"m":63.546,"v":0,"m_mono":62.929599},
	"Zn":{"an":30,"m":65.39,"v":0,"m_mono":63.929145},
	"Ga":{"an":31,"m":69.723,"v":3,"m_mono":68.925581},
	"Ge":{"an":32,"m":72.61,"v":4,"m_mono":73.921179},
	"As":{"an":33,"m":74.92159,"v":3,"m_mono":74.921596},
	"Se":{"an":34,"m":78.96,"v":2,"m_mono":79.916521},
	"Br":{"an":35,"m":79.904,"v":1,"m_mono":78.918336},
	"Kr":{"an":36,"m":83.8,"v":0,"m_mono":83.911506},
	"Rb":{"an":37,"m":85.4678,"v":1,"m_mono":84.911800},
	"Sr":{"an":38,"m":87.62,"v":2,"m_mono":87.905625},
	"Y":{"an":39,"m":88.90585,"v":0,"m_mono":88.905856},
	"Zr":{"an":40,"m":91.224,"v":0,"m_mono":89.904708},
	"Nb":{"an":41,"m":92.90638,"v":0,"m_mono":92.906378},
	"Mo":{"an":42,"m":95.94,"v":0,"m_mono":97.905405},
	"Tc":{"an":43,"m":97.9072,"v":0},
	"Ru":{"an":44,"m":101.07,"v":0,"m_mono":101.904348},
	"Rh":{"an":45,"m":102.9055,"v":0,"m_mono":102.905503},
	"Pd":{"an":46,"m":106.42,"v":0,"m_mono":105.903475},
	"Ag":{"an":47,"m":107.8682,"v":0,"m_mono":106.905095},
	"Cd":{"an":48,"m":112.411,"v":0,"m_mono":113.903361},
	"In":{"an":49,"m":114.818,"v":3,"m_mono":114.903875},
	"Sn":{"an":50,"m":118.71,"v":4,"m_mono":119.902199},
	"Sb":{"an":51,"m":121.76,"v":3,"m_mono":120.903824},
	"Te":{"an":52,"m":127.6,"v":2,"m_mono":129.906229},
	"I":{"an":53,"m":126.90447,"v":1,"m_mono":126.904477},
	"Xe":{"an":54,"m":131.29,"v":0,"m_mono":131.904148},
	"Cs":{"an":55,"m":132.90543,"v":1,"m_mono":132.905433},
	"Ba":{"an":56,"m":137.327,"v":2,"m_mono":137.905236},
	"La":{"an":57,"m":138.9055,"v":3,"m_mono":138.906355},
	"Ce":{"an":58,"m":140.115,"v":3,"m_mono":139.905442},
	"Pr":{"an":59,"m":140.90765,"v":3,"m_mono":140.907657},
	"Nd":{"an":60,"m":144.24,"v":3,"m_mono":141.907731},
	"Pm":{"an":61,"m":144.9127,"v":3},
	"Sm":{"an":62,"m":150.36,"v":3,"m_mono":151.919741},
	"Eu":{"an":63,"m":151.965,"v":3,"m_mono":152.921243},
	"Gd":{"an":64,"m":157.25,"v":3,"m_mono":157.924111},
	"Tb":{"an":65,"m":158.92534,"v":3,"m_mono":158.925350},
	"Dy":{"an":66,"m":162.5,"v":3,"m_mono":163.929183},
	"Ho":{"an":67,"m":164.93032,"v":3,"m_mono":164.930332},
	"Er":{"an":68,"m":167.26,"v":3,"m_mono":165.930305},
	"Tm":{"an":69,"m":168.9342,"v":3,"m_mono":168.934225},
	"Yb":{"an":70,"m":173.04,"v":3,"m_mono":173.938873},
	"Lu":{"an":71,"m":174.967,"v":3,"m_mono":174.940785},
	"Hf":{"an":72,"m":178.49,"m_mono":179.946561},
	"Ta":{"an":73,"m":180.9479,"m_mono":180.948014},
	"W":{"an":74,"m":183.84,"m_mono":183.950953},
	"Re":{"an":75,"m":186.207,"m_mono":186.955765},
	"Os":{"an":76,"m":190.23,"m_mono":191.961487},
	"Ir":{"an":77,"m":192.217,"m_mono":192.962942},
	"Pt":{"an":78,"m":195.08,"m_mono":194.964785},
	"Au":{"an":79,"m":196.96654,"m_mono":196.966560},
	"Hg":{"an":80,"m":200.59,"m_mono":201.970632},
	"Tl":{"an":81,"m":204.3833,"m_mono":204.974410},
	"Pb":{"an":82,"m":207.2,"m_mono":207.976641},
	"Bi":{"an":83,"m":208.98037,"m_mono":208.980388},
	"Po":{"an":84,"m":208.9824},
	"At":{"an":85,"m":209.9871},
	"Rn":{"an":86,"m":222.0176},
	"Fr":{"an":87,"m":223.0197},
	"Ra":{"an":88,"m":226.0254},
	"Ac":{"an":89,"m":227.0278},
	"Th":{"an":90,"m":232.0381,"m_mono":232.038054},
	"Pa":{"an":91,"m":231.03588},
	"U":{"an":92,"m":238.0289,"m_mono":238.050786},
	"Np":{"an":93,"m":237.0482},
	"Pu":{"an":94,"m":244.0642},
	"Am":{"an":95,"m":243.0614},
	"Cm":{"an":96,"m":247.0703},
	"Bk":{"an":97,"m":247.0703},
	"Cf":{"an":98,"m":251.0796},
	"Es":{"an":99,"m":252.083},
	"Fm":{"an":100,"m":257.0915},
	"Md":{"an":101,"m":256.094},
	"No":{"an":102,"m":259.1009},
	"Lr":{"an":103,"m":262.11},
	"Rf":{"an":104,"m":261.11},
	"Db":{"an":105,"m":268},
	"Sg":{"an":106,"m":271},
	"Bh":{"an":107,"m":270},
	"Hs":{"an":108,"m":277},
	"Mt":{"an":109,"m":276},
	"Ds":{"an":110,"m":281},
	"Rg":{"an":111,"m":272},
	"Cn":{"an":112},
	"%":{"wc":true},
	"M":{"wc":true},
	"X":{"wc":true},
	"Ln":{"wc":true}
};

function getType(Char,Pos) {
	Pos=parseInt(Pos);
	if (isNaN(Pos)) {
		Pos=0;
	}
	var cc=Char.charCodeAt(Pos);
	if (cc==NaN) {
		return 0;
	}
	if (cc==40) { // Klauf
		return 1;
	}
	if (cc>=65 && cc<=90) { // Großbuch
		return 2;
	}
	if (cc>=97 && cc<=122) { // Kleinbuch
		return 3;
	}
	if (cc>=48 && cc<=57) { // Zahl
		return 4;
	}
	if (cc==41) { // Klzu
		return 5;
	}
	return 0;
}

function getMassFromSign(sign) {
	/* 	for (var idx=0;idx<elSigns.length;idx++) {
		if (grSigns[idx]==sign) {
			return grMasses[idx];
		}
	} */
	for (var idx=0,max=elSigns.length;idx<max;idx++) {
		if (elSigns[idx]==sign) {
			return elMasses[idx];
		}
	}
	return NaN;
}

function getMolecMass(formel) {
	var evalStr="",level=0,typ,elSig="",numAct=false;
	if (!formel) return;
	for (var b=0,max=formel.length;b<max;b++) {
		typ=getType(formel,b);
		if (typ==3) { // in Zeichen drin
			elSig+=formel.charAt(b);
		}
		else {
			if (elSig!="") {
				evalStr+="+"+getMassFromSign(elSig);
				numAct=false;
				elSig="";
			}
			if (typ==1) {
				level++;
				evalStr+="+(";
				numAct=false;
			}
			else if (typ==5) {
				level--;
				evalStr+=")";
				numAct=false;
			}
			else if (typ==2) {
				elSig=formel.charAt(b);
			}
			else if (typ==4) {
				evalStr+=(numAct?"":"*")+formel.charAt(b);
				numAct=true;
			}
		}
	}
	if (elSig!="") {
		evalStr+="+"+getMassFromSign(elSig);
		elSig="";
	}
	if (level==0) {
		return eval(evalStr);
	}
	else {
		return NaN;
	}
}

function getBeautySum(formel) {
	var retStr="",typ,numAct=false;
	if (!formel) {
		return "";
	}
	for (var b=0,max=formel.length;b<max;b++) {
		typ=getType(formel,b);
		if (typ==4 && !numAct) {
			retStr+="<sub>";
			numAct=true;
		}
		else if (numAct && typ!=4) {
			retStr+="</sub>";
			numAct=false;
		}
		retStr+=formel.charAt(b);
	}
	if (numAct) {
		retStr+="</sub>";
	}
	return retStr;
}

function getScalarP(Arr1,Arr2) {
	var retVal=0;
	for (var aq=0,max=Math.min(Arr1.length,Arr2.length);aq<max;aq++) {
		retVal+=Arr1[aq]*Arr2[aq];
	}
	return retVal;
}

function filledArray(text,dimension) {
	var retArr=[dimension];
	for (var b=0;b<dimension;b++) {
		retArr[b]=text;
	}
	return retArr;
}

/*
function addAS(code) {
	switch (code) {
	case "A":
	case "Ala":
		this.atom_molfile+="";
		this.bond_molfile+="";
		this.atoms+=;
		this.bonds+=
		this.x_offset+=
	break;
	}
	this.AS_count++;
}

function ASseq() {
	this.atom_molfile="";
	this.bond_molfile="";
	this.footer="";
	this.atoms=0;
	this.bonds=0;
	this.AS_count=0;
	this.x_offset=0;
	this.addAS=addAS;
}

function getASmolfile(sequence) {
	var seqObj=new ASseq();
	// split
	
	// start mit N-Terminus
	
	// loop
	
	// C-Terminus
	
	return retval;
}*/