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

// calculation

function get_volume(volume_unit,mass,mass_unit,density_20) { // aus Masse und Dichte das Volumen berechnen
	var factor=getUnitFactor(mass_unit)/getUnitFactor(volume_unit);
	if (density_20>0 && mass>0) {
		return mass/density_20*factor;
	}
	return "";
}

function get_amount(amount_unit,mass,mass_unit,mw) { // aus Masse und Molmasse die Stoffmenge berechnen
	var factor=getUnitFactor(mass_unit)/getUnitFactor(amount_unit);
	if (mw>0 && mass>0) {
		return mass/mw*factor;
	}
	return "";
}

function get_mass_from_volume(mass_unit,volume,volume_unit,density_20) { // aus Volumen und Dichte die Masse berechnen
	var factor=getUnitFactor(volume_unit)/getUnitFactor(mass_unit);
	if (density_20>0 && volume>0) {
		return volume*density_20*factor;
	}
	return "";
}

function get_mass_from_amount(mass_unit,amount,amount_unit,mw) { // aus Stoffmenge und Molmasse die Masse berechnen
	var factor=getUnitFactor(amount_unit)/getUnitFactor(mass_unit);
	if (mw>0 && amount>0) {
		return amount*mw*factor;
	}
	return "";
}

// conc related

function get_amount_from_volume(amount_unit,volume,volume_unit,conc,conc_unit) { // aus Volumen und Konzentration die Stoffmenge berechnen
	var factor=getUnitFactor(conc_unit)*getUnitFactor(volume_unit)/getUnitFactor(amount_unit);
	if (volume>0 && conc>0) {
		return volume*conc*factor;
	}
	return "";
}

function get_volume_from_amount(volume_unit,amount,amount_unit,conc,conc_unit) { // aus Stoffmenge und Konz das Volumen berechnen
	var factor=getUnitFactor(amount_unit)/getUnitFactor(volume_unit)/getUnitFactor(conc_unit);
	if (amount>0 && conc>0) {
		return amount*factor/conc;
	}
	return "";
}

// molal related

function get_amount_from_mass_molal(amount_unit,mass,mass_unit,molal,molal_unit) { // aus Masse und Molalität die Stoffmenge berechnen
	var factor=getUnitFactor(molal_unit)*getUnitFactor(mass_unit)/getUnitFactor(amount_unit);
	if (mass>0 && molal>0) {
		return mass*molal*factor;
	}
	return "";
}

function get_mass_from_amount_molal(mass_unit,amount,amount_unit,molal,molal_unit) { // aus Stoffmenge und Molalität das Masse berechnen
	var factor=getUnitFactor(amount_unit)/getUnitFactor(mass_unit)/getUnitFactor(molal_unit);
	if (amount>0 && molal>0) {
		return amount*factor/molal;
	}
	return "";
}

// stoch_coeff related

function get_amount_from_stoch_coeff(amount_unit,ref_amount,ref_amount_unit,stoch_coeff) {
	var factor=getUnitFactor(ref_amount_unit)/getUnitFactor(amount_unit);
	if (stoch_coeff>0 && ref_amount>0) {
		return stoch_coeff*ref_amount*factor;
	}
	return "";
}

function get_stoch_coeff_from_amount(amount,amount_unit,ref_amount,ref_amount_unit) {
	var factor=getUnitFactor(amount_unit)/getUnitFactor(ref_amount_unit);
	if (amount>0 && ref_amount>0) {
		return amount/ref_amount*factor;
	}
	return "";
}

/* function get_ref_amount_from_amount(ref_amount_unit,amount,amount_unit,stoch_coeff) {
	var factor=getUnitFactor(amount_unit)/getUnitFactor(ref_amount_unit);
	if (stoch_coeff>0 && amount>0) {
		return amount/stoch_coeff*factor;
	}
	return "";	
}*/

function get_ref_amount_from_amount(amount,stoch_coeff) {
	if (stoch_coeff>0 && amount>0) {
		return amount/stoch_coeff;
	}
	return "";	
}