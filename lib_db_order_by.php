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

function getUserDefOrderObj($table) {
	global $settings;
	$pkName=getShortPrimary($table);
	return array(
		array(
			"field" => (
				is_array($settings[$table."_order"]) && count($settings[$table."_order"])
			//~ )?"FIELD(".$pkName.",".fixArrayListString(array_values($settings[$table."_order"])).")":""
			)?"FIELD(".$pkName.",".fixArrayListString(array_reverse(array_values($settings[$table."_order"]))).")":"", // prevent new devices etc to show up at the very top
			"order" => "DESC", 
		), 
	);
}

function getSortArrow($order_key,$down,$sel=false) {
	return "<a href=\"Javascript:setOrder(&quot;".$order_key."&quot;)\" class=\"noprint\"><img src=\"lib/".($down?"down":"up").($sel?"_sel":"").".png\" width=\"16\" height=\"14\" border=\"0\"".getTooltip(($down?"sort_down":"sort_up"))."></a>";
}

function getSortLinks($order_key) {
	if (empty($order_key)) {
		return;
	}
	
	$order_keys=explode(",",$_REQUEST["order_by"]);
	$secondary="";
	
	if (in_array($order_key,$order_keys)) {
		$sel_up=true;
	}
	elseif (in_array("-".$order_key,$order_keys)) {
		$sel_down=true;
	}
	elseif (!empty($_REQUEST["order_by"])) {
		// show arrows for secondary order
		$secondary=" +".getSortArrow($_REQUEST["order_by"].",".$order_key,false).getSortArrow($_REQUEST["order_by"].",-".$order_key,true);
	}
	
	$retval="<br><nobr>".getSortArrow($order_key,false,$sel_up).getSortArrow("-".$order_key,true,$sel_down).$secondary."</nobr>";
	return $retval;
}

function addSortHintField(& $fields,$order_obj) {
	if (!empty($order_obj[0]["field"]) && !$order_obj[0]["no_hints"]) {
		$fields[]=$order_obj[0]["field"]." AS sort_hint";
	}
}

function getOptOrder($crit,$val) {
	return array(
		array("field" => $crit." LIKE ".fixStrSQL($val), "order" => "DESC", "no_hints" => true),
		array("field" => $crit." LIKE ".fixStrSQL($val."%"), "order" => "DESC", "no_hints" => true),
	);
}

function reverseOrderObj($order_obj) {
	for ($a=0;$a<count($order_obj);$a++) {
		if ($order_obj[$a]["no_flip"]) {
			continue;
		}
		if ($retval[$a]["order"]=="DESC") {
			$order_obj[$a]["order"]="ASC";
		}
		else {
			$order_obj[$a]["order"]="DESC";
		}
	}
	return $order_obj;
}

function getOrderObjFromKey($order_key_str,$table) { // check if order_key is suitable for table
	global $order_by_keys,$query;
	if (empty($order_key_str)) {
		return array();
	}
	
	// combinations separated by commas
	$order_keys=explode(",",$order_key_str);
	$retval=array();
	
	foreach ($order_keys as $order_key) {
		// inverted by minus
		$reverse=startswith($order_key,"-");
		if ($reverse) {
			$order_key=substr($order_key,1);
		}
		
		// check if order keys fits to table
		if (is_array($order_by_keys[$order_key]["for_table"]) && !in_array($table,$order_by_keys[$order_key]["for_table"])) {
			if (!is_array($query[$table]["joins"]) || !count(array_intersect($order_by_keys[$order_key]["for_table"],$query[$table]["joins"])) ) {
				return array();
			}
		}
		
		$retval_part=$order_by_keys[$order_key]["columns"];
		if ($reverse) {
			$retval_part=reverseOrderObj($retval_part);
		}
		if (is_array($retval_part)) { // stfu
			$retval=array_merge($retval,$retval_part);
		}
	}
	
	return $retval;
}

function getOrderStr($order_obj) {
	if (is_string($order_obj)) {
		return $order_obj;
	}
	$retval="";
	if ($order_obj) for ($a=0;$a<count($order_obj);$a++) {
		if (!empty($order_obj[$a]["field"])) {
			$retval.=$order_obj[$a]["field"]." ".$order_obj[$a]["order"].",";
		}
	}
	if (empty($retval)) {
		return;
	}
	$retval=substr($retval,0,strlen($retval)-1);
	return $retval;
}

function getOrderSelect($order_obj) {
	if (is_string($order_obj)) {
		return "";
	}
	if ($order_obj["no_indicators"]) {
		return "";
	}
	return $order_obj[0]["field"];
}

?>