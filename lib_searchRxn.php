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

function addAnalyticsQuery(& $fieldsArray,& $number,$code_key,$analytics_type_code,$analytics_method_name=null) {
	if (is_null($analytics_method_name)) {
		$fieldsArray[]=array("item" => "text", "int_name" => "text_has_".$analytics_type_code);
		$fieldsArray[]=array("item" => "check", "int_name" => "has_".$analytics_type_code, "name" => "query[]", SPLITMODE => true, "onChange" => "uncheckObj(&quot;has_no_".$analytics_type_code."&quot;)", "value" => " AND <".$number.">");
		$fieldsArray[]=array("item" => "check", "int_name" => "has_no_".$analytics_type_code, "name" => "query[]", SPLITMODE => true, "onChange" => "uncheckObj(&quot;has_".$analytics_type_code."&quot;)", "value" => " AND NOT <".$number.">");
		$fieldsArray[]=array("item" => "hidden", "int_name" => "crit".$number, "value" => "analytical_data_simple.".$code_key);
		$fieldsArray[]=array("item" => "hidden", "int_name" => "op".$number, "value" => "ex");
		$fieldsArray[]=array("item" => "hidden", "int_name" => "val".$number, "value" => $analytics_type_code);
		$number++;
	}
	else {
		$combi=$analytics_type_code."_".$analytics_method_name;
		$fieldsArray[]=array("item" => "text", "int_name" => "text_has_".$combi);
		$fieldsArray[]=array("item" => "check", "int_name" => "has_".$combi, "name" => "query[]", SPLITMODE => true, "onChange" => "uncheckObj(&quot;has_no_".$combi."&quot;)", "value" => " AND <".($number+2).">");
		$fieldsArray[]=array("item" => "check", "int_name" => "has_no_".$combi, "name" => "query[]", SPLITMODE => true, "onChange" => "uncheckObj(&quot;has_".$combi."&quot;)", "value" => " AND NOT <".($number+2).">");
		$fieldsArray[]=array("item" => "hidden", "int_name" => "crit".$number, "value" => "analytical_data_simple.".$code_key);
		$fieldsArray[]=array("item" => "hidden", "int_name" => "op".$number, "value" => "ex");
		$fieldsArray[]=array("item" => "hidden", "int_name" => "val".$number, "value" => $analytics_type_code);
		$fieldsArray[]=array("item" => "hidden", "int_name" => "crit".($number+1), "value" => "analytical_data_simple.analytics_method_name");
		$fieldsArray[]=array("item" => "hidden", "int_name" => "op".($number+1), "value" => "ex");
		$fieldsArray[]=array("item" => "hidden", "int_name" => "val".($number+1), "value" => $analytics_method_name);
		$fieldsArray[]=array("item" => "hidden", "int_name" => "crit".($number+2), "value" => "analytical_data_simple.reaction_id");
		$fieldsArray[]=array("item" => "hidden", "int_name" => "op".($number+2), "value" => "sq");
		$fieldsArray[]=array("item" => "hidden", "int_name" => "val".($number+2), "value" => "<".$number."> AND <".($number+1).">");
		$number+=3;
	}
}
?>