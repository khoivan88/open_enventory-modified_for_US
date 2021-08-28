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

var person_db_id,person_id=0,username="",person_name="",chemical_storage_id="",refreshInt,personInt,refreshInterval=600,personInterval,personCountdown,editMode=true,readOnly=false,a_db_id=-1;

function setBaseData() {
	$("barcodeAsyncForm").reset();
	setInputValue("async_person_db_id",person_db_id);
	setInputValue("async_person_id",person_id);
	setInputValue("async_username",username);
	setInputValue("async_pk",chemical_storage_id);
}

function beforeiHTML(id,iHTML) {
	var obj=$(id);
	if (obj) {
		obj.innerHTML=iHTML+obj.innerHTML;
	}
}

function afteriHTML(id,iHTML) {
	var obj=$(id);
	if (obj) {
		obj.innerHTML+=obj.innerHTML;
	}
}

function showMessage(message) {
	setiHTML("message",message);
	beforeiHTML("message_log",message+"<br>");
}

function showPersonLoggedIn(text) {
	setiHTML("status",s("trm_is_logged_in1")+text+s("trm_is_logged_in2"));
}

function inventarisationMode() {
	inventarisation_mode=getChecked("inventarisation_mode");
	showControl("storage_permanent",inventarisation_mode);
	personInterval=(inventarisation_mode?inventarisationInterval:normalInterval);
	resetPersonInterval();
}

function barcodeReadToServer(barcode) {
	inventarisation_mode=getChecked("inventarisation_mode");
	setBaseData();
	setInputValue("async_barcode",barcode);
	setInputValue("async_storage_permanent",inventarisation_mode&&chemical_storage_id&&getChecked("storage_permanent")); // active only if there is already an active dataset (=> load storage_id on 1st time)
	setInputValue("async_desired_action",(inventarisation_mode?"loadDataForInventory":""));
	submitForm("barcodeAsyncForm");
}

function showCountdown(time) {
	var obj=$("countdown");
	if (obj) {
		// format as time?
		if (time>=0) {
			obj.innerHTML=time;
		}
		else {
			obj.innerHTML="";
		}
	}
}

function countdown() {
	showCountdown(personCountdown);
	if (personCountdown<0) {
		setActivePerson([]);
	}
	else {
		personCountdown--;
		personInt=window.setTimeout(countdown,1000); // 1s
	}
}

function resetPersonInterval() {
	// unset active after 1 min if someone is logged in
	if (personInt) {
		window.clearTimeout(personInt);
	}
	if (person_id) {
		// personInt=window.setTimeout(setActivePerson,personInterval);
		personCountdown=personInterval; // reset to 60s
		countdown();
	}
}

function setStorage(storage_id) {
	if (chemical_storage_id) {
		setInputValue("storage_id",storage_id);
	}
}

function setActivePerson(values,silent) {
	if (values==undefined) {
		values=[];
	}
	person_db_id=a(values,"db_id");
	person_id=a(values,"person_id");
	username=a(values,"username");
	// alert("login "+username+" caller "+setActivePerson.caller);
	if (person_id) { // einloggen
		person_name=formatPerson(values);
		// say hello
		showMessage(s("trm_welcome1")+person_name+s("trm_welcome2"));
		showPersonLoggedIn(person_name);
		resetPersonInterval();
		if (chemical_storage_id) { // do not unlock empty form, looks crap
			unlockObj("updateInventory");
			unlockObj("btn_del");
			readOnlyForm("chemical_storage",false);
		}
		unlockObj("btn_login");
		unlockObj("btn_logout");
	}
	else { // ausloggen
		if (!silent && person_name) {
			showMessage(s("trm_goodbye1")+person_name+s("trm_goodbye2"));
		}
		person_name="";
		showPersonLoggedIn(s("nobody"));
		window.clearTimeout(personInt);
		lockObj("updateInventory");
		lockObj("btn_del");
		lockObj("btn_login");
		lockObj("btn_logout");
		readOnlyForm("chemical_storage",true);
	}
	focusInput("barcode");
}

function delChemicalStorage(no_confirm) {
	if (!chemical_storage_id) {
		return;
	}
	if (!no_confirm && !confirm(s("delWarning"))) {
		return;
	}
	// aktuellen datensatz löschen
	setBaseData();
	setInputValue("async_barcode","");
	setInputValue("async_desired_action","del");
	submitForm("barcodeAsyncForm");
	setActiveChemicalStorage(new Array()); // reset values
}

function setActiveChemicalStorage(values) {
	chemical_storage_id=a(values,"chemical_storage_id");
	
	// open here
	readOnlyForm("chemical_storage",false);
	unlockObj("btn_del");
	unlockObj("updateInventory");
	
	setControlValues(values,true);
	if (inventarisation_mode) { // inventur
		focusInput("total_mass_rounded");
	}
	else if (a(values,"borrowed_by_person_id")) { // ausgeliehen
		focusInput("barcode");
	}
	else if (a(values,"tmd")) { // zurückgegeben, tmd is gesetzt
		focusInput("total_mass_rounded");
	}
	else { // zurückgegeben, nur schätzen
		focusInput("actual_amount_rounded");
	}
}

function takeForAsync(id) {
	setInputValue("async_"+id,getControlValue(id));
}

function doInventar() { // this is to reduce network traffic, by only sending the required data
	if (person_id && chemical_storage_id) {
		// füllmenge und standort senden
		setBaseData();
		setInputValue("async_desired_action","inventory");
		//~ setInputValue("async_actual_amount",getControlValue("actual_amount"));
		takeForAsync("actual_amount");
		takeForAsync("amount");
		//~ setInputValue("async_amount_unit",getControlValue("amount_unit"));
		takeForAsync("amount_unit");
		takeForAsync("tmd");
		takeForAsync("tmd_unit");
		takeForAsync("chemical_storage_conc");
		takeForAsync("chemical_storage_conc_unit");
		//~ setInputValue("async_storage_id",getControlValue("storage_id"));
		takeForAsync("chemical_storage_bilancing");
		takeForAsync("chemical_storage_barcode");
		takeForAsync("storage_id");
		//~ setInputValue("async_compartment",getControlValue("compartment"));
		takeForAsync("compartment");
		takeForAsync("history_entry");
		submitForm("barcodeAsyncForm");
	}
}

function doRefresh(doIt) { // avoid session timeouts
	if (doIt) {
		comm.location.href="barcodeTerminalAsync.php";
	}
	refreshInt=window.setTimeout(function () { doRefresh(true); },refreshInterval);
}
