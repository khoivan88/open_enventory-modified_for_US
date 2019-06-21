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

function getPackageFactor(package_amount,package_amount_unit,number_packages_text,density) {
	var expr=/([\d\.\,\+\-\*\/\(\)]+)\s*([a-zA-Z]+)/;
	// check if number_packages_text is number+unit, else: is factor
	if (!expr.exec(number_packages_text)) {
		return evalNum(number_packages_text);
	}
	
	var entered_amount=evalNum(RegExp.$1),entered_amount_unit=RegExp.$2;
	entered_amount_unit=entered_amount_unit.toLowerCase();
	
	var package_amount_type=getUnitType(package_amount_unit),entered_amount_type=getUnitType(entered_amount_unit);
	var factor=getUnitFactor(entered_amount_unit)/getUnitFactor(package_amount_unit);
	// check if unit_types of package_amount_unit and entered_unit match, if yes: calc factor
	if (package_amount_type==entered_amount_type && package_amount>0) {
		return factor*entered_amount/package_amount;
	}
	
	// calc factor using density
	if (density>0) {
		if (entered_amount_type=="m" && package_amount_type=="v") { // angefragt 0.1kg=>0.12L, Packung 1L, d=0.8 g/l
			return factor*entered_amount/density/package_amount;
		}
		if (entered_amount_type=="v" && package_amount_type=="m") { // angefragt 0.1L=>0.08kg, Packung 1kg, d=0.8 g/l
			return factor*entered_amount*density/package_amount;
		}
	}
	
	return evalNum(number_packages_text);
}

function getClassType(className) {
	for (var b=0,max=class_result.length;b<max;b++) {
		if (class_result[b]["class_name"]==className) {
			return class_result[b]["class_type"];
		}
	}
}

function getDefaultUnitForClass(className) {
	for (var b=0,max=unit_result.length;b<max;b++) {
		if (unit_result[b]["unit_type"]==className && unit_result[b]["unit_factor"]==1) {
			return unit_result[b]["unit_name"];
		}
	}
}

function getUnitsList(allowedClasses) {
	var retval=[];
	if (allowedClasses==false) {
		return retval;
	}
	if (!is_array(allowedClasses)) {
		allowedClasses=[allowedClasses];
	}
	for (var b=0,max=unit_result.length;b<max;b++) {
		if (in_array(unit_result[b]["unit_type"],allowedClasses)) {
			retval.push(unit_result[b]["unit_name"]);
		}
	}
	return retval;
}

function getUnitProperty(unitName,propertyName) {
	for (var b=0,max=unit_result.length;b<max;b++) {
		if (unitName==unit_result[b]["unit_name"]) {
			return unit_result[b][propertyName];
		}
	}
}

function getUnitFactor(unitName) {
	return getUnitProperty(unitName,"unit_factor");
}

function getUnitType(unitName) {
	return getUnitProperty(unitName,"unit_type");
}

