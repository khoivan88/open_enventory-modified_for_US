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

function findFloatRange($data,$code,$low,$high) {
	$code_len=get_up_len($code);
	$retval="";
	for ($a=0;$a<$code_len;$a++) {
		$trun_data=substr($data,$a);
		$blocks=str_split($trun_data,$code_len);
		
		for($b=0;$b<count($blocks);$b++) {
			$val=up($code,$blocks[$b]);
			if (!is_nan($val) && $val>=$low && $val<=$high) {
				$retval.=$code."@".dechex($a+$b*$code_len)." - ".$val." (".getBinhex($blocks[$b]).")<br>";
			}
		}
	}
	
	return $retval;
}

function findFRange($data,$low,$high) {
	return 
	//~ findFloatRange($data,"V",$low,$high).
	//~ findFloatRange($data,"N",$low,$high).
	findFloatRange($data,"F",$low,$high).
	findFloatRange($data,"f",$low,$high).
	findFloatRange($data,"D",$low,$high).
	findFloatRange($data,"d",$low,$high);
}

function prepareBinSearch() {
	global $search_nums,$search_values,$formats_int,$formats_float;
	if (is_array($search_nums)) foreach ($search_nums as $idx => $num) {
		$value=array();
		if (is_numeric($num)) {
			$formats=$formats_float;
			if (floor($num)==$num) {
				$formats=array_merge($formats,$formats_int);
			}
			foreach ($formats as $format) {
				$value[$format]=pk($format,$num);
			}
		}
		else {
			$value["U"]=OLE::Asc2Ucs($num);
		}
		$value["T"]=$num;
		$search_values[$idx]=$value;
	}
}

function binSearch($bindata) {
	global $search_values;
	$retval="";
	foreach ($search_values as $search_idx => $search_value) {
		$hits="";
		foreach ($search_value as $format => $bin) {
			$pos=strpos($bindata,$bin."");
			if ($pos!==FALSE) {
				$hits.=$format."@".dechex($pos)." (".getBinhex($bin).") ";
			}
		}
		if ($hits!="") {
			$retval.=$search_idx."(".$search_value["T"]."): ".$hits."<br>";
		}
	}
	return $retval;
}


?>