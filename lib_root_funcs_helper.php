<?php
/*
Copyright 2006-2009 Felix Rudolphi and Lukas Goossen
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

function getBarcodeFieldType($table) {
	global $barcodePrefixes;
	if (is_array($barcodePrefixes)) foreach ($barcodePrefixes as $prefix => $data) {
		$baseTable=getBaseTable($data["table"]);
		if ($baseTable!=$table) {
			continue;
		}
		if ($data["field"]=="field") {
			$field=$data["type"];
			if ($field=="") {
				//~ $field="BIGINT"; // suitable for EAN13 etc.
				$field="VARBINARY(20)";
			}
			return $field;
		}
	}
}

function getBarcodeColumn($tabname) { // Array
	$fieldType=getBarcodeFieldType($tabname);
	if ($fieldType) {
		return array(
			array("name" => getBarcodeFieldName($tabname),"def" => $fieldType, "type" => "field"),
		);
	}
	return array();
}

function getPkColumn($pk) {
	if (empty($pk)) {
		return array();
	}
	return array(
		array("name" => $pk, "def" => "INT NOT NULL AUTO_INCREMENT PRIMARY KEY", "type" => "pk"),
	);
}

function getCustomIndex($tabdata) { // Array
	if (!empty($tabdata["index"])) {
		return array(
			array("name" => $tabdata["index"]["name"], "def" => "(".join(",",$tabdata["index"]["fields"]).")", "type" => $tabdata["index"]["type"]),
		);
	}
	return array();
}

?>