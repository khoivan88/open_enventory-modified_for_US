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

function sql_connect($db_server,$db_user,$db_pw) {
	switch (db_system) {
	case "MySQL":
		return mysqli_connect($db_server,$db_user,$db_pw);
	break;
	case "Oracle":
		
	break;
	}
}

function sql_query($query,$db) {
	switch (db_system) {
	case "MySQL":
		return mysqli_query($db,$query);
	break;
	case "Oracle":
		
	break;
	}
}

function sql_close($db) {
	switch (db_system) {
	case "MySQL":
		return mysqli_close($db);
	break;
	case "Oracle":
		
	break;
	}
}

function sql_error($db) {
	switch (db_system) {
	case "MySQL":
		return mysqli_error($db);
	break;
	case "Oracle":
		
	break;
	}
}

function sql_errno($db) {
	switch (db_system) {
	case "MySQL":
		return mysqli_errno($db);
	break;
	case "Oracle":
		
	break;
	}
}

function sql_set_charset($charset,$db) {
	switch (db_system) {
	case "MySQL":
		return mysqli_set_charset($db,$charset);
	break;
	case "Oracle":
		
	break;
	}
}

?>