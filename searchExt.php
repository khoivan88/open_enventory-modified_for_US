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
/*
gibt zuerst Treffer aus der eigenen Datenbank und dann Angebote bei den ausgewählten Suppliern an (letzteres asynchron über searchExtAsync)
Caching in external_queries und external_results
*/

require_once "lib_global_funcs.php";
require_once "lib_simple_forms.php";
require_once "lib_form_elements.php";
require_once "lib_supplier_scraping.php";
require_once "lib_output.php";
require_once "lib_db_query.php";


pageHeader(true,true,false);

echo stylesheet.
style.
getFixedStyleBlock().
_style.
loadJS(array("safety_".$lang.".js","chem.js","safety.js","molecule_edit.js","list.js"),"lib/").
script.
addParamsJS().
_script.
"</head>
<body>".getHelperTop()."
<div id=\"browsenav\">";

// Sprung zu Molekül, Kaufangeboten, externen Angeboten durchführen
$left=array(
	"<a href=\"#molecule\" class=\"imgButtonSm\"><img src=\"lib/molecule_sm.png\" border=\"0\"".getTooltip("molecule_pl")."></a>", 
	"<a href=\"#supplier_offer\" class=\"imgButtonSm\"><img src=\"lib/supplier_offer_sm.png\" border=\"0\"".getTooltip("edit_supplier_offers")."></a>", 
	"<a href=\"#suppliers\" class=\"imgButtonSm\"><img src=\"lib/supplier_sm.png\" border=\"0\"".getTooltip("molecules_at_suppl")."></a>", 
);

if ((capabilities & 1) && mayWrite("chemical_order",-1)) {
	$center[]="<a href=\"Javascript:document.orderForm.submit()\" class=\"imgButtonSm\"><img src=\"lib/chemical_order_sm.png\" border=\"0\"".getTooltip("prepare_order")."></a>";
}
echo getAlignTable($left,$center,$right);

// Moleküle
$_REQUEST["per_page"]=-1;
$_REQUEST["cached_query"]="";
$_REQUEST["dbs"]="";
$_REQUEST["table"]="molecule";
setGlobalVars();

//~ print_r($_REQUEST);
//~ list($res,$totalCount,$page,$skip,$per_page,$from_cache)=handleQueryRequest(2);
list($res,$dataArray,$sort_hints)=handleQueryRequest(2);
$totalCount=& $dataArray["totalCount"];
$page=& $dataArray["page"];
$skip=& $dataArray["skip"];
$per_page=& $dataArray["per_page"];
$from_molecule_cache=& $dataArray["cache_active"];

$molecule_cache=$cache;
//~ $from_molecule_cache=$from_cache;
$cache_id=$_REQUEST["cached_query"];

echo script.
addParamsJS().";".
_script.
"</div><div id=\"browsemain\">

<form name=\"orderForm\" action=\"edit.php?desired_action=new&".getSelfRef(array("~script~","table"))."\" method=\"post\" target=\"_blank\">
<input type=\"hidden\" name=\"db_id\" value=\"-1\">
<input type=\"hidden\" name=\"table\" value=\"my_chemical_order\">";
//~ print_r($cache);
// echo $_REQUEST["cached_query"];
if (count($res)) { // in eigener Datenbank etwas gefunden
	// Anker für Moleküle
	list($fields,$hidden)=getFields($columns[ $_REQUEST["table"] ]);
	echo showAnchor(array("int_name" => $_REQUEST["table"], )).
	showGroup("molecule_pl",2).
	outputList($res,$fields,array("separatorField" => "db_id", "noButtons" => true, "output_type" => "html", ));
	
	if ($cache["filter_obj"]["crits"][0]=="smiles_stereo") {
		$a=0;
		do {
			if (!empty($res[$a]["cas_nr"])) {
				$casFoundInOwn=true;
				$ext_result=$res[$a];
				echo s("has_cas1a").$ext_result["show_db_beauty_name"].s("has_cas1b").$ext_result["molecule_name"].s("has_cas1c").$ext_result["cas_nr"].s("has_cas2")."<br>";
				$molecule_cache["filter_obj"]["crits"][0]="cas_nr";
				$molecule_cache["filter_obj"]["ops"][0]="ex";
				$molecule_cache["filter_obj"]["vals"][0][0]=$ext_result["cas_nr"];
				break;
			}
			$a++;
		} while ($a<count($res));
	}
}

// Kaufangebote
if (capabilities & 1) {
	$_REQUEST["cached_query"]="";
	$_REQUEST["per_page"]=-1;
	$_REQUEST["dbs"]="";
	$_REQUEST["table"]="supplier_offer";
	setGlobalVars();
	unset($cache);
	unset($filter_obj);
	
	list($res,$dataArray,$sort_hints)=handleQueryRequest(2);
	$totalCount=& $dataArray["totalCount"];
	$page=& $dataArray["page"];
	$skip=& $dataArray["skip"];
	$per_page=& $dataArray["per_page"];
	$from_cache=& $dataArray["cache_active"];

	if (count($res)) { // in eigener Datenbank etwas gefunden
		// Anker für Moleküle
		list($fields,$hidden)=getFields($columns[ $_REQUEST["table"] ]);
		echo showAnchor(array("int_name" => $_REQUEST["table"], )).
		showGroup("edit_supplier_offers",2).
		outputList($res,$fields,array("separatorField" => "db_id", "noButtons" => true, "order_alternative" => true, "output_type" => "html", )); // Knöpfe zum Bestellen anzeigen
	}
}

// extern nach CAS suchen
if (!$from_molecule_cache && $molecule_cache["filter_obj"]["crits"][0]=="smiles_stereo" && !$casFoundInOwn) { // dont search structure we have
	$ext_result=getCASfromStr($molecule_cache["filter_obj"]["vals"][0][1]);
	if (!isEmptyStr($ext_result["cas_nr"])) {
		echo s("has_cas1a")."<a href=\"".$suppliers[$ext_result["supplierCode"]]["getDetailPageURL"]($ext_result["catNo"])."\" target=\"_blank\">".getSupplierLogo($suppliers[$ext_result["supplierCode"]])."</a>".s("has_cas1b").$ext_result["molecule_name"].s("has_cas1c").$ext_result["cas_nr"].s("has_cas2")."<br>";
		$molecule_cache["filter_obj"]["crits"][0]="cas_nr";
		$molecule_cache["filter_obj"]["ops"][0]="ex";
		$molecule_cache["filter_obj"]["vals"][0][0]=$ext_result["cas_nr"];
	}
	else {
		echo s("no_cas")."<br>";
	}
}
if (!isEmptyStr($_REQUEST["supplier"])) {
	$molecule_cache["supplier"]=$_REQUEST["supplier"];
}

gcCache();
$_REQUEST["cached_query"]=writeCache($molecule_cache,$cache_id);

// print_r($cache["filter_obj"]);
// async starten
if (empty($_REQUEST["supplier"])) {
	// do nothing
}
elseif (in_array($molecule_cache["filter_obj"]["crits"][0],$ext_crits) && !isEmptyStr($molecule_cache["filter_obj"]["vals"][0][0])) {
	echo showAnchor(array("int_name" => "suppliers", )).
	showGroup("molecules_at_suppl",2).
	showBr().
	s("no_responsibility").
	"<div id=\"results\"></div>";
	
	showCommFrame(array("url" => "searchExtAsync.php?".getSelfRef(array("~script~","table"))));
	showCommFrame(array("name" => "comm2")); // allows to search for prices while still running "Search all"
}
else {
	echo s("no_search_term");
}

echo "
</form>
</div>".
getHelperBottom().
"</body>
</html>
";
// completeDoc();
?>