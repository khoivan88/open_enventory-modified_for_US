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

define("_anyone_borrowed",1);
define("_not_borrowable",2);
define("_borrowed",4);

function borrowStatus($borrowed_by_person_id,$borrowed_by_db_id,$owner_person_id) {
	global $person_id,$permissions;
	$retval=0;
	if (!empty($borrowed_by_person_id)) {
		$retval+=_anyone_borrowed; // durch jemanden ausgeliehen
	}
	if (empty($person_id) || ($permissions & (_chemical_edit+_chemical_borrow))==0 && (($permissions & _chemical_edit_own)==0 || $owner_person_id!=$person_id)) {
		$retval+=_not_borrowable; // kein Recht/keine Möglichkeit zum Ausleihen
	}
	if (!empty($borrowed_by_person_id) && $borrowed_by_person_id==$person_id && $borrowed_by_db_id==-1) { // selbst ausgeliehen
		$retval+=_borrowed; // selbst ausgeliehen
	}
	return $retval;
}

?>