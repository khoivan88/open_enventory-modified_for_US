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

// benutzerverwaltung+other_db
define("_admin",1); // globale Einstellungen machen, Benutzer anlegen/ändern/löschen, other_db eintragen
define("_remote_read",2); // remote lesen
define("_remote_direct",4); // *auch Geheimes über remote lesen
// define("_remote_write",8); // *über remote schreiben (entsprechend der anderen Rechte)
// unnötig, _remote_direct erlaubt, die weiteren Rechte per remote zu nutzen
define("_barcode_user",16); // Barcode-Terminal-Benutzer
// 4

// lagerverwaltung
define("_storage_modify",32); // lager anlegen/ändern,...
define("_chemical_create",64); // nur anlegen
define("_chemical_edit",128); // alles bearbeiten
define("_chemical_edit_own",256); // eigenes bearbeiten oder erstellen, aber NIX löschen
define("_chemical_borrow",512); // ausleihen/zurückgeben
define("_chemical_inventarise",1024); // inventarisieren
define("_chemical_delete",2048); // löschen, eingefügt FR 24.07.2009
define("_chemical_read",4096); // lesen
// 7

// laborjournal
define("_lj_admin",8192); // LJs anlegen
define("_lj_project",16384); // Projekte verwalten
define("_lj_edit",32768); // alles schreiben
define("_lj_edit_own",65536); // eigenes schreiben
define("_lj_read",131072); // lesen
define("_lj_read_all",262144); // alles lesen, auch wo nicht im Geheim-Projekt
// 6

// order_system
define("_order_order",524288); // bestellen
define("_order_approve",1048576); // genehmigen
define("_order_accept",2097152); // Bestellung ausführen
// 3

$permissions_groups=array(5,8,6,3);

$permissions_list_value=array(
	"admin" => _admin+_storage_modify+
		_chemical_edit+_chemical_read+
		_lj_admin+_lj_project+_lj_edit+_lj_read+
		_order_order+_order_approve,
	"write" => _chemical_edit+_chemical_read+
		_lj_edit+_lj_read+
		_order_order,
	"limited_write" => _chemical_edit_own+_chemical_read+
		_lj_edit_own+_lj_read,
	"read_other" => _chemical_read+
		_lj_read,
	"remote_read" => _remote_read,
	"barcode_user" => _chemical_edit+_chemical_read+_barcode_user,
);

?>
