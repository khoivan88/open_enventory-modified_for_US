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
// Acros
$GLOBALS["code"]="Acros";
$code=$GLOBALS["code"];

require_once installPath."suppliers/lib/chemexper.php";

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "Acros", 
	"logo" => "LogoAcros.jpg", 
	"height" => 40, 
	"vendor" => true, 
	"hasPriceList" => 3, 
	"alwaysProcDetail" => true, 
	"forGroupNames" => "AcrosOrganics%2CFisherSci%2CMaybridgeBB%2CBioReagents",
	"chemExperServer" => "&server=www.acros.com",
	
"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="http://www.acros.com"; // startPage
	$suppliers[$code]["urls"]["startPage"]=$urls["server"];
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	return chemExperRequestResultList($self,$query_obj);
'),
"getDetailPageURL" => create_function('$catNo',getFunctionHeader().'
	return chemExperGetDetailPageURL($self,$catNo);
'),
"getInfo" => create_function('$catNo',getFunctionHeader().'
	return chemExperGetInfo($self,$catNo);
'),
"getHitlist" => create_function('$searchText,$filter,$mode="ct",$paramHash=array()',getFunctionHeader().'
	return chemExperGetHitlist($self,$searchText,$filter,$mode,$paramHash);
'),
"getBestHit" => create_function('& $hitlist,$name=NULL','
	return chemExperGetBestHit($hitlist,$name);
'),
);
$GLOBALS["suppliers"][$code]["init"]();
?>