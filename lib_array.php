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

/* function arr_del_value(& $arr,$valueArr) { // deletes all element in $valueArr from $arr
	if (!is_array($valueArr)) {
		$valueArr=array($valueArr);
	}
	if (count($valueArr)) foreach ($valueArr as $value) {
		while ($pos=array_search($value,$arr)) {
			array_splice($arr,$pos,1);
		}
	}
}*/

function array_clean($arr) {
	if (!is_array($arr)) {
		return $arr;
	}
	foreach ($arr as $key => $value) {
		if ($value==="") {
			unset($arr[$key]);
		}
	}
	return $arr;
}

function array_key_clear(& $arr,$keys) {
	for ($a=0,$d=count($keys);$a<$d;$a++) {
		$arr[ $keys[$a] ]="";
	}
}

function array_key_remove(& $arr,$keys) {
	for ($a=0,$d=count($keys);$a<$d;$a++) {
		unset($arr[ $keys[$a] ]);
	}
}

function array_key_filter($arr,$keys) {
	$retval=array();
	if (is_array($keys)) for ($a=0,$d=count($keys);$a<$d;$a++) {
		$key=& $keys[$a];
		if (isset($arr[$key])) {
			$retval[$key]=$arr[$key];
		}
	}
	return $retval;
}

function arr_replace($search,$replace,$arr) { // replaces array elements, similar to strreplace
	if (!is_array($arr)) {
		return;
	}
	if (is_array($search) && is_array($replace) && count($replace)>=count($search)) {
		$arr_mode=true;
	}
	elseif (!is_array($search)) {
		$search=array($search); // unifies search command
	}
	
	$retval=$arr;
	foreach ($arr as $key => $value) {
		$idx=array_search($value,$search);
		if ($idx===FALSE) {
			continue;
		}
		if ($arr_mode) {
			$retval[$key]=$replace[$idx];
		}
		else {
			$retval[$key]=$replace;
		}
	}
	return $retval;
}

function arr_trans(& $target,& $source,$fields,$strip_tags=false) { // & $source saves mem
	if (!is_array($source) || !is_array($fields)) {
		return;
	}
	if (!is_array($target)) {
		$target=array();
	}
	for ($a=0,$d=count($fields);$a<$d;$a++) {
		$field=$fields[$a];
		$value=$source[$field];
		if ($strip_tags) {
			$value=strip_tags($value);
		}
		$target[$field]=$value;
	}
}

function arr_safe($arr) {
	if (!is_array($arr)) {
		return array();
	}
	return $arr;
}

function exp2(& $item) {
	$item=pow(2,$item);
}

function getExpArray($len,$offset=0) {
	$arr=range($offset,$offset+$len-1);
	array_walk($arr,"exp2");
	return $arr;
}

function cumSum($arr,$until) {
	if (!is_array($arr)) {
		return;
	}
	if (!count($arr) || $until==0) {
		return 0;
	}
	return array_sum(array_slice($arr,0,$until));
}

function arr_intersect($arr1,$arr2) {
	return array_values(array_intersect(arr_safe($arr1),arr_safe($arr2)));
}

function arr_merge($arr1,$arr2,$overwriteEmpty=false) {
	if ($overwriteEmpty && is_array($arr2)) { // remove empty from $arr1
		foreach ($arr2 as $key => $value) {
			if (isEmptyStr($value)) {
				unset($arr2[$key]);
			}
		}
	}
	return array_merge(arr_safe($arr1),arr_safe($arr2));
}

function getVecLen(& $v) {
	return sqrt(getScalarProd($v,$v));
}

function getVecAngle(& $v1,& $v2) {
	$l=getVecLen($v1)*getVecLen($v2);
	if ($l>0) {
		return acos(getScalarProd($v1,$v2)/$l);
	}
	return false;
}

function getScalarProd($a,$b) {
	$retval=0;
	for ($c=0,$d=count($a);$c<$d;$c++) {
		$retval+=$a[$c]*$b[$c];
	}
	return $retval;
}

function getCrossProd(& $a,& $b) {
	return array(
		$a[1]*$b[2]-$a[2]*$b[1],
		$a[2]*$b[0]-$a[0]*$b[2],
		$a[0]*$b[1]-$a[1]*$b[0]
		);
}

function getTripleProd(& $a,& $b,& $c) { // Spatprodukt für Chiralität
	return getScalarProd(getCrossProd($a,$b),$c);
}

function array_key_sum(& $arr_sum,& $arr_keys) {
	$sum=0;
	if (!is_array($arr_sum) || is_array($arr_keys)==0) {
		return $sum;
	}
	foreach ($arr_keys as $key) {
		$sum+=$arr_sum[$key];
	}
	return $sum;
}

function array_subtract(& $arr1,& $arr2) {
	if (!is_array($arr1) || count($arr1)==0) {
		return array();
	}
	if (!is_array($arr2) || count($arr2)==0) {
		return $arr1;
	}
	$retval=array();
	for ($a=0;$a<count($arr1);$a++) {
		$retval[$a]=$arr1[$a]-$arr2[$a];
	}
	return $retval;
}

function array_add(& $arr1,& $arr2) {
	$arrays=func_get_args();
	$retval=array();
	for ($b=0,$d=count($arrays);$b<$d;$b++) {
		for ($a=0,$e=count($arrays[$b]);$a<$e;$a++) {
			$retval[$a]+=$arrays[$b][$a];
		}
	}
	return $retval;
}

function array_round($arr,$s) {
	for ($c=0,$d=count($arr);$c<$d;$c++) {
		$arr[$c]=round($arr[$c],$s);
	}
	return $arr;
}

function array_mult($arr,$s) {
	for ($c=0,$d=count($arr);$c<$d;$c++) {
		$arr[$c]*=$s;
	}
	return $arr;
}

function array_mult_byref(& $arr,$s) { // faster
	for ($c=0,$a=count($arr);$c<$a;$c++) {
		$arr[$c]*=$s;
	}
}

function array_get_col(& $arr,$col_name) {
	$retval=array();
	if (is_array($arr)) foreach ($arr as $key => $sub_arr) {
		$retval[$key]=$sub_arr[$col_name];
	}
	return $retval;
}

function array_get_nvp(& $arr,$name_col,$val_col) {
	$retval=array();
	if (is_array($arr)) foreach ($arr as $sub_arr) {
		$retval[ $sub_arr[$name_col] ]=$sub_arr[$val_col];
	}
	return $retval;
}

function array_values_r($arr) {
	$retval=array();
	if (is_array($arr)) foreach ($arr as $sub_arr) {
		if (is_array($sub_arr)) {
			$retval=array_merge($retval,array_values_r($sub_arr));
		}
		else {
			$retval[]=$sub_arr;
		}
	}
	return $retval;
}

function array_diff_r($arr1,$arr2) {
	$retval=array();
	if (count($arr2)==0) {
		return $arr1;
	}
	if (is_array($arr1)) foreach ($arr1 as $item) {
		if (!in_array($item,$arr2)) {
			$retval[]=$item;
		}
	}
	return $retval;
}

function array_intersect_r($arr1,$arr2) { // assume that arr1 is longer
	$retval=array();
	if (is_array($arr1) && is_array($arr2)) foreach ($arr2 as $item) {
		if (in_array($item,$arr1)) {
			$retval[]=$item;
		}
	}
	return $retval;
}

function array_slice_r($arr,$offset,$length) { // nur 2 Ebenen, arr[db_id]=array(pks)
	// print_r($arr);
	// echo $offset." ".$length;
	$retval=array();
	if (count($arr)>0) {
		$total_count=0;
		$ende=$offset+$length; // 
		foreach ($arr as $key => $value) {
			$len=count($value);
			if (is_array($value) && $len>$offset-$total_count && $len>0) {
				$this_start=max(0,$offset-$total_count);
				$this_len=max(0,$ende-$total_count-$this_start);
				$retval[$key]=array_slice($value,$this_start,$this_len);
			}
			$total_count+=$len;
			if ($total_count>=$ende) {
				break;
			}
		}
	}
	// print_r($retval);
	return $retval;
}
?>
