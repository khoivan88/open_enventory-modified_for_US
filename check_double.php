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

require_once "lib_global_funcs.php";
require_once "lib_global_settings.php";
require_once "lib_db_query.php";
require_once "lib_constants.php";
require_once "lib_output.php";
require_once "lib_formatting.php";
require_once "lib_array.php";

pageHeader();

// buttons to go to specific
$left=array(
	"<a href=\"#smiles_stereo\" class=\"imgButtonSm\"><img src=\"lib/molecule_sm.png\" border=\"0\"".getTooltip("double_smiles_stereo")."></a>", 
	"<a href=\"#cas_nr\" class=\"imgButtonSm\"><img src=\"lib/cas_sm.png\" border=\"0\"".getTooltip("double_cas_nr")."></a>", 
);

echo stylesheet.
style.
getFixedStyleBlock().
_style.
loadJS(array("safety_".$lang.".js","chem.js","safety.js","molecule_edit.js","list.js"),"lib/").
"</head>
<body>".getHelperTop()."
<div id=\"browsenav\">".
getAlignTable($left,$center,$right).
"</div><div id=\"browsemain\">";

// list of double stereoSMILES
$double_res=mysql_select_array_from_dbObj("molecule_id,COUNT(*) AS count FROM molecule WHERE smiles_stereo!=\"\" GROUP BY smiles_stereo HAVING COUNT(*)>1",$db); // COLLATE utf8_bin is no longer needed, smiles_stereo is binary now
$counts=array_get_nvp($double_res,"molecule_id","count");

// output list with link to list of molecules
$_REQUEST["per_page"]=-1;
$_REQUEST["cached_query"]="";
$_REQUEST["dbs"]=-1;
$_REQUEST["table"]="molecule";
$_REQUEST["query"]="<1>";
$_REQUEST["crit1"]="molecule.molecule_id";
$_REQUEST["op1"]="in";
$_REQUEST["val1"]=join(",",array_get_col($double_res,"molecule_id"));
setGlobalVars();

list($fields,$hidden)=getFields($columns["molecule_double"]);
$fields[]="links_double_smiles";
list($res,$dataArray,$sort_hints)=handleQueryRequest(2);
for ($a=0;$a<count($res);$a++) {
	$res[$a]["double_count"]=intval($counts[ $res[$a]["molecule_id"] ]);
}

echo showAnchor(array("int_name" => "smiles_stereo", )).
showGroup("double_smiles_stereo",2).
outputList($res,$fields,array("noButtons" => true, "output_type" => "html", ));


// list of double CAS Nos
$double_res=mysql_select_array_from_dbObj("molecule_id,COUNT(*) AS count FROM molecule WHERE cas_nr!=\"\" GROUP BY cas_nr HAVING COUNT(*)>1",$db); // ,cas_nr,COUNT(*)
$counts=array_get_nvp($double_res,"molecule_id","count");

// output list with link to list of molecules
$_REQUEST["cached_query"]="";
$_REQUEST["val1"]=join(",",array_get_col($double_res,"molecule_id"));

// check struc/sum formula/MW (maybe later, time consuming)
list($fields,$hidden)=getFields($columns["molecule_double"]);
$fields[]="links_double_cas";
list($res,$dataArray,$sort_hints)=handleQueryRequest(2);
for ($a=0;$a<count($res);$a++) {
	$res[$a]["double_count"]=intval($counts[ $res[$a]["molecule_id"] ]);
}

echo showAnchor(array("int_name" => "cas_nr", )).
showGroup("double_cas_nr",2).
outputList($res,$fields,array("noButtons" => true, "output_type" => "html", )).
"</div>".
getHelperBottom().
"</body>
</html>
";
?>