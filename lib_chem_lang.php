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

$CL_symbols=array("-","(",")","+",",","'","[","]","{","}"," ","%");

// get regular expression for splitting in CLsplitFrag
$CL_symbols_preg="/([0-9\s".preg_quote(join("",$CL_symbols))."])/";

$CL_groups["prefix_no_o"]=array("hydroxy","alpha","beta","gamma","delta","epsilon","omega",);
$CL_groups["prefix_o"]=array("amin","imin","cyan","nitr","nitros","mercapt","az","ox","hydr","dehydr","deuteri",);

$CL_groups["suffix_no_e"]=array("o","ol","al","ic","ium","ia","yl");
$CL_groups["suffix_e"]=array("id","it","at","an","en","in","yn","on","am","ein"); // possible end letter e
$CL_groups["suffix_ar"]=array("ol","en","ene"); // toluol, xylol

$CL_groups["num_gr"]=array("bis","tris","tetrakis","pentakis","hexakis",);
$CL_groups["num_lat"]=array("bi","ter",);
$CL_groups["num"]=array("mono","di","tri","tetra","penta","hexa","hepta","octa","nona","deca","undeca","dodeca",);

$CL_groups["attrib"]=array("iso","sec","tert","neo","cyclo","nor","poly","trans","cis","ortho","meta","para","erythro","threo","pseudo","allo","exo","endo","per","hypo","spiro","form","acet","benz","phthal",);

$CL_groups["anywhere"]=array("toluol", // toloul is very common
"imid", // imidazol, phthalimid
);

$CL_groups["el"]=array( // deuterium treated elsewhere
"wasserstoff","hydrogen","tritium","helium",
"lithium","beryll","bor","kohlenstoff","carb","stickstoff","nitrogen","ammon","sauerstoff","oxygen","fluor","neon",
"natrium","sodium","magnes","alumin","silic","phosphor","phosphorus","schwefel","sulfur","sulf","chlor","argon",

"kalium","potassium","calcium",
"scandium","titan","vanad","chrom","mangan","manganese","eisen","iron","cobalt","nickel","kupfer","copper","zink","zinc",
"gallium","germanium","arsen","selen","brom","krypton",

"rubidium","strontium",
"yttrium","zirkon","zircon","niob","molybdän","molybdenum","technet","ruthen","rhod","pallad","silber","silver","cadm",
"indium","zinn","tin","antimon","antimony","tellur","iod","iodine","xenon",

"cäsium","cesium","barium",
"lanthan","lanthanum","cer","cerium","praseodym","neodym","neodymium","promethium","samarium","europium","gadolinium","terbium","dysprosium","holmium","erbium","thulium","ytterbium","lutetium","hafnium","tantal","tantalum","wolfram","tungsten","rhenium","osmium","iridium","platin","platinum","gold","quecksilber","mercury","thallium","blei","lead","wismut","bismut","bismuth",

"uran","uranium");

$CL_groups["rad_simple"]=array(
	"meth","eth","prop","but","pent","hex","hept","oct","non","dec","undec","dodec",
	"born","adamant","pin","camph","fench",
	"pyr","fur","ox","stann","plumb",
	"tol", // tolan
);
$CL_groups["suffix_simple"]=array("a","an","ane");

$CL_groups["rad_ar"]=array("xyl","ind","cum","cym",); // en ol
$CL_groups["rad"]=array(
"hydr","deuter","yl",
"ephedr","nitr",
"tolu", // toluol, toluen, toluidin,...
"mesit","kres","cres","anis","anil","phen","chin","naphth","anthrac","anthr","pyrr","thien","ket","lact","all","acr","phthal","form","acet","benz","benzo",
"mal","succin","fumar","valer","adip","capr","glutam","aspart","trypt","phan",
"tos","trifl","mes",
"glycer",
"hydro","thi","phosp","nitr","ferr","cupr","molybd","argent","osm","irid","aur","mercur",);

$CL_groups["rad_yl"]=array("vin","proparg","trit","tol",);

$CL_groups["hantzsch1"]=array("ox","az","thi","in","ep","oc","on","ec");
$CL_groups["hantzsch2"]=array("ir","et","ol","in","ep","oc","on","ec");
$CL_groups["hantzsch3"]=array("in","an","idin","en");

$CL_groups["sugar"]=array(
// C4
"thre","erythr","erythrul",

// C5
"rib","arabin","xyl","lyx","ribul","xylul",

// C6
"all","altr","gluc","mann","gul","id","galact","tal","fruct","fuc","rhamn","psic","sorb","tagat",

// C7
"sedoheptul","mannoheptul","alloheptul","taloheptul","mannohept","glucohept",

// Di
"malt","sacchar","gentiobi","trehal","lact",

);
$CL_groups["sugar_suffix"]=array("ose","it");

$CL_groups["amino"]=array("alan","argin","asparag","cyste","glutam","glyc","histid","leuc","lys","methion","prol","ser","threon","tyros","val", );

$CL_groups["saure"]=array(
"ameisen","essig",
"benzoe","malon","bernstein","adipin",
);
$CL_groups["suffix_saure"]=array(
"säure","anhydrid","nitril", 
);

$CL_groups["part_end"]=array("säure","acid","nitril","nitrile", ); // acid chloride, nitrile oxide
$CL_groups["total_end"]=array("ester","aldehyd","alcohol","alkohol","anhydrid","anhydride","harnstoff","urea","amid","amin","imin", );

//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
$CL_chains=array(
array(
	array("type" => "group", "id" => "hantzsch1", ), 
	array("type" => "group", "id" => "hantzsch1", ), 
), 

array(
	array("type" => "group", "id" => "hantzsch1", ), 
	array("type" => "group", "id" => "hantzsch2", ), 
	array("type" => "group", "id" => "hantzsch3", "opt" => true, ), 
), 

// General, anywhere
array(
	array("type" => "groups", "ids" => array("num_gr","prefix_no_o",), "noEnd" => true), 
), // nicht am Ende

array(
	array("type" => "groups", "alwaysCheck" => true, "ids" => array("attrib",), "noEnd" => true), 
), // nicht am Ende

array(
	array("type" => "group", "id" => "prefix_o" ), 
	array("type" => "text", "value" => "o", "opt" => true, "noEnd" => true), 
), // nicht am Ende

array(
	array("type" => "groups", "ids" => array("anywhere"), "alwaysCheck" => true, ), 
), // überall

array(
	array("type" => "group", "alwaysCheck" => true, "opt" => true, "id" => "num",), 
	array("type" => "groups", "ids" => array("part_end") ), 
), // überall

array(
	array("type" => "group", "alwaysCheck" => true, "opt" => true, "id" => "num",), 
	array("type" => "group", "id" => "total_end" ), 
	array("type" => "end", ), 
), // nur am Ende

// General inorganic
array(
	array("type" => "group", "opt" => true, "id" => "num", ), 
	array("type" => "group", "id" => "el",), 
	array("type" => "groups", "opt" => true, "ids" => array("suffix_no_e","suffix_e",) ),
),

// General organic
// Radikalstamm mit suffix
array(
	array("type" => "group", "opt" => true, "id" => "num",), 
	array("type" => "groups", "ids" => array("rad","rad_simple")), 
	array("type" => "group", "opt" => true, "id" => "num",), 
	array("type" => "groups", "ids" => array("suffix_no_e","suffix_e",) ),
	array("type" => "groups", "opt" => true, "ids" => array("suffix_no_e","suffix_e",) ),
),

array(
	//~ array("type" => "group", "opt" => true, "id" => "num",), 
	array("type" => "groups", "ids" => array("rad_simple")), 
	array("type" => "group", "id" => "suffix_simple",),
),

array(
	array("type" => "group", "opt" => true, "id" => "num", "alwaysCheck" => true, ), 
	array("type" => "groups", "ids" => array("rad_ar")), 
	array("type" => "group", "id" => "suffix_ar",),
),

array(
	array("type" => "groups", "ids" => array("saure")), 
	array("type" => "group", "id" => "suffix_saure",),
),

// Amin Amid Imin Imid
//~ array(array("type" => "group", "opt" => true, "id" => "num",), array("type" => "group", "id" => "rad_id_in",), array("type" => "group", "id" => "suffix_id_in", ),),

array(
	array("type" => "group", "opt" => true, "id" => "num",), 
	array("type" => "group", "id" => "rad_yl",), 
	array("type" => "text", "value" => "yl" ),
),

// Reihungen von Suffixen
array(
	array("type" => "group", "id" => "num", "noStart" => true,), 
	array("type" => "groups", "ids" => array("suffix_no_e","suffix_e",) ), 
	array("type" => "groups", "opt" => true, "ids" => array("suffix_no_e","suffix_e",) ), 
	array("type" => "groups", "opt" => true, "ids" => array("suffix_no_e","suffix_e",) ), 
), // nicht am Anfang

// Suffixe mit e
array(
	array("type" => "group", "id" => "suffix_e", "noStart" => true, ), 
	array("type" => "text", "value" => "e" ), 
), // nicht am Anfang

// Special
// Sugar stuff
array(
	array("type" => "group", "id" => "sugar",), 
	array("type" => "group", "id" => "sugar_suffix",), 
),

// Amino acid stuff
array(
	array("type" => "group", "id" => "amino",), 
	array("type" => "text", "value" => "in",), 
),

);
// ur

define("CL_frag_len_diff",2);
define("CL_word_max_cost",5);
define("CL_frag_max_cost",3);
define("CL_frag_del_cost",2);
define("CL_frag_repl_cost",1);
define("CL_frag_add_cost",1);
define("CL_frag_shuffle_cost",1);
define("CL_frag_shuffle_diff_cost",2);
define("CL_frag_chain_min_match",0.25);
define("CL_frag_1st_cost",1);
define("CL_max_frag_range",2);

function CLstring_sim($str1,$str2) {
	$str1=strtolower($str1);
	$str2=strtolower($str2);
	if ($str1==$str2) {
		return 0;
	}
	// char differences
	$chars1=count_chars($str1);
	$chars2=count_chars($str2);
	$diff=array(CL_frag_shuffle_cost); // default penalty
	foreach ($chars1 as $asc => $quant1) {
		$diff[0]+=CL_frag_shuffle_diff_cost*abs($quant1-$chars2[$asc]);
	}
	$diff[1]=levenshtein($str2,$str1,CL_frag_del_cost,CL_frag_repl_cost,CL_frag_add_cost);
	// give 1st letter higher error
	if (strlen($str1)>2 && substr($str1,0,1)!=substr($str2,0,1)) {
		$diff[0]+=CL_frag_1st_cost;
		//~ $diff[1]+=CL_frag_1st_cost;
	}
	//~ print_r($diff);
	return min($diff)*0.8+0.1*array_sum($diff);
}

//~ echo("X".CLstring_sim("pre","ter"));
//~ echo("X".CLstring_sim("ptal","pinal"));
//~ echo("X".CLstring_sim("ptal","phthal"));
//~ echo("X".CLstring_sim("ti","nitr"));
//~ echo("X".CLstring_sim("toloul","toluol"));
//~ die("X".CLstring_sim("emthyl","methyl"));
//~ die("X".CLstring_sim("toloul","tellur"));

function CLgetSuggestionsMicrofragChain($frag,$chainNo,$posInWord,$wordLength) { // Vorschlag auf Basis dieser Kette machen
	global $CL_groups,$CL_chains;
	$limits=array();
	
	//~ echo $frag."X".$chainNo."<br>";
	
	// start/nostart
	$CL_start="";
	$CL_end="";
	if ($CL_chains[$chainNo][0]["type"]=="start") {
		if ($posInWord!=0) {
			return array();
		}
	}
	if ($CL_chains[$chainNo][0]["noStart"]) {
		if ($posInWord==0) {
			return array();
		}
	}
	
	// end/noend
	$el_count=count($CL_chains[$chainNo]);
	$is_at_end=($posInWord+strlen($frag)==$wordLength); // fragment ist am Ende des Worts
	//~ echo $frag."G".$is_at_end."T".$el_count."<br>";
	if ($CL_chains[$chainNo][$el_count-1]["type"]=="end") {
		if (!$is_at_end) {
			return array();
		}
	}
	if ($CL_chains[$chainNo][$el_count-1]["noEnd"]) {
		//~ echo "Z";
		if ($is_at_end) {
			return array();
		}
	}
	
	for ($b=0;$b<$el_count;$b++) { // Elemente durchgehen und exakte Treffer suchen, $limits aufbauen
		// try to match exactly
		$alternatives=CLgetAlternatives($chainNo,$b);
		//~ print_r($alternatives);
		
		$limits[$b]=array();
		for ($a=0;$a<count($alternatives);$a++) {
			//~ $pos=strpos($frag,$alternatives[$a]);
			if ($b==0) {
				$CL_start="^";
			}
			else {
				$CL_start=".+";
			}
			if ($b==$el_count-1) {
				$CL_end="\$";
			}
			else {
				$CL_end=".+";
			}
			$re="/(?ims)".$CL_start."(".$alternatives[$a].")".$CL_end."/";
			
			//~ echo $re."t<br>";
			// check if end conditions matched
			$limits[$b]=array_merge($limits[$b],CLgetLimits($re,$frag,1));
			
			//~ if ($pos!==FALSE && ($b!=0 || $pos==0) && ($b!=count($CL_chains[$chainNo])-1 || $pos+strlen($alternatives[$a])==strlen($frag) ) ) { // force begin for 1st and end for last
				//~ echo $alternatives[$a]."<br>";
				//~ $limits[$b][]=array($pos,$pos+strlen($alternatives[$a]));
			//~ }
		}
		usort($limits[$b],"CLlimitSort");
	}
	//~ if ($chainNo==9) {
		//~ echo "<pre>";
		//~ print_r($limits);
	//~ }
	
	// build solutions
	return CLbuildGuessSolutions($frag,$chainNo,$limits);
	//~ echo $chainNo;print_r($solutions);
}

function CLgetNextElWithLimits($chainNo,$limits,$skip=0) {
	global $CL_chains;
	for ($a=$skip;$a<count($limits);$a++) {
		if (count($limits[$a])) {
			return $a;
		}
		elseif (!$CL_chains[$chainNo][$a]["opt"]) { // accept that optional is not found
			return false;
		}
	}
	return -1;
}

function CLbuildGuessSolutions($frag,$chainNo,$limits,$check_element=0,$pos=0,$guess="") { // called recursively
	// $check_element: aktueller Eintrag der Kette $chainNo
	global $CL_groups,$CL_chains;
	//~ echo $chainNo."A".$check_element."B".$pos."C".$guess."<br>";
	//~ $check_element=count($path);
	if ($check_element==count($CL_chains[$chainNo])) { // fertiggeraten, d.h. letztes Element der Kette
		//~ echo $guess."X".$frag."<br>";
		if ($pos!=strlen($frag)) { // paßt nicht (na und? Können wir doch auch etwas zurückgeben)
			return array();
		}
		return array($guess); // Länge paßt
	}
	
	$retval=array();
	for ($a=0;$a<count($limits[$check_element]);$a++) { // etwas passendes für Element gefunden
		$start=$limits[$check_element][$a][0]; // [0]: Startposition, [1]: Endposition
		if ($start==$pos) { // passender Anküpfungspunkt
			$new_guess=$guess.substr($frag,$start,$limits[$check_element][$a][1]-$start); // neuen Teil anhängen
			$retval=array_merge($retval,CLbuildGuessSolutions($frag,$chainNo,$limits,$check_element+1,$limits[$check_element][$a][1],$new_guess));
		}
	}
	if (!count($limits[$check_element])) { // nichts passendes für Element gefunden
		$next_def=CLgetNextElWithLimits($chainNo,$limits,$check_element+1);
		//~ if ($check_element==count($CL_chains[$chainNo])-1) { // last
		if ($next_def==-1) { // last
			// guess until end
			$guess_frag=CLguessAtom($chainNo,$check_element,substr($frag,$pos));
			//~ echo $guess_frag."A<br>";
			if ($guess_frag!==FALSE) {
				$retval=array_merge($retval,CLbuildGuessSolutions($frag,$chainNo,$limits,$check_element+1,strlen($frag),$guess.$guess_frag));
			}
		}
		elseif ($next_def!==FALSE) {
			for ($a=0;$a<count($limits[$next_def]);$a++) { // go through next
				if ($limits[$next_def][$a][0]>=$pos) {
					$atom=substr($frag,$pos,$limits[$next_def][$a][0]-$pos); // durch $limits[$next_def] vorgegebener Teil
					$guess_frag=CLguessAtom($chainNo,$check_element,$atom);
					//~ echo $guess_frag."B<br>";
					//~ echo $guess_frag."Y".$atom."R".$chainNo."X".$check_element."<br>";
					if ($guess_frag!==FALSE) {
						$retval=array_merge($retval,CLbuildGuessSolutions($frag,$chainNo,$limits,$check_element+1,$limits[$next_def][$a][0],$guess.$guess_frag));
					}
				}
			}
		}
	}
	if ($CL_chains[$chainNo][$check_element]["opt"]) { // skip
		//~ echo $guess."<br>";
		$retval=array_merge($retval,CLbuildGuessSolutions($frag,$chainNo,$limits,$check_element+1,$pos,$guess));
	}
	return $retval;
}

function CLgetAlternatives($chainNo,$check_element) {
	global $CL_groups,$CL_chains;
	$alternatives=array();
	switch ($CL_chains[$chainNo][$check_element]["type"]) {
	case "text":
		$alternatives[0]=$CL_chains[$chainNo][$check_element]["value"];
	break;
	case "group":
		$alternatives=$CL_groups[ $CL_chains[$chainNo][$check_element]["id"] ];
	break;
	case "groups":
		for ($c=0;$c<count($CL_chains[$chainNo][$check_element]["ids"]);$c++) {
			$id=$CL_chains[$chainNo][$check_element]["ids"][$c];
			if (is_array($CL_groups[$id])) {
				$alternatives=array_merge($alternatives,$CL_groups[$id]);
			}
		}
	break;
	}
	return $alternatives;
}

function CLmatchRoman($frag) { // nur für Oxidationszahlen
	return preg_match("/^[IV]+\$/",$frag);
}

function CLguessAtom($chainNo,$check_element,$frag) {
	$frag_len=strlen($frag);
	$best_suggestion=false;
	$alternatives=CLgetAlternatives($chainNo,$check_element);
	//~ print_r($alternatives);
	for ($a=0;$a<count($alternatives);$a++) {
		if (abs($frag_len-strlen($alternatives[$a]))>CL_frag_len_diff) {
			continue;
		}
		$new_quality=CL_frag_max_cost-CLstring_sim($frag,$alternatives[$a]);
		if ($new_quality>$quality) {
			$best_suggestion=$alternatives[$a]; // Einzelfragmente
			$quality=$new_quality;
		}
	}
	//~ echo $best_suggestion."<br>";
	return $best_suggestion;
}

function CLgetSuggestionsForMicrofrag($frag,$posInWord,$wordLength,$flags=0) {
	global $CL_groups,$CL_chains; // ,$CL_universalGroups
	//~ echo $frag."R".$posInWord."S".$wordLength."<br>";
	$frag_len=strlen($frag);
	$best_suggestion=false;
	$quality=-1;
	
	// 1. identify groups
	$possible_groups=array();
	$suggestions=array();
	foreach ($CL_groups as $id => $group) {
		$group_in=false;
		//~ $universal_group=in_array($id,$CL_universalGroups);
		for ($b=0;$b<count($group);$b++) {
			if (!$group_in && (strpos($frag,$group[$b])!==FALSE || strpos($group[$b],$frag)!==FALSE)) {
				$possible_groups[]=$id;
				$group_in=true;
				//~ echo $group[$b]."<br>";
			}
			//~ if ($universal_group) {
				//~ $suggestions[]=$group[$b];
			//~ }
		}
	}
	//~ print_r($possible_groups);
	
	// 2. go through chains and try to find something
	for ($a=0;$a<count($CL_chains);$a++) { // Zusammenhängende Fragmente
		if (!$CL_chains[$a][0]["alwaysCheck"]) {
			$chain_points=0;
			for ($b=0;$b<count($CL_chains[$a]);$b++) {
				switch ($CL_chains[$a][$b]["type"]) {
				case "text":
					if (strpos($frag,$CL_chains[$a][$b]["value"])!==FALSE || strpos($CL_chains[$a][$b]["value"],$frag)!==FALSE) {
						$chain_points++;
					}
				break;
				case "group":
					if (in_array($CL_chains[$a][$b]["id"],$possible_groups)) {
						$chain_points++;
					}
				break;
				case "groups":
					for ($c=0;$c<count($CL_chains[$a][$b]["ids"]);$c++) {
						if (in_array($CL_chains[$a][$b]["ids"][$c],$possible_groups)) {
							$chain_points++;
							break;
						}
					}
				break;
				}
			}
			//~ echo $chain_points."F".$a."E".$non_opt_count."<br>";
			if ($chain_points==0) {
				continue;
			}
		}
		//~ echo $frag."D".$a."<br>";
		// have closer look
		// go through all chain combinations and try
		$suggestions=array_merge($suggestions,CLgetSuggestionsMicrofragChain($frag,$a,$posInWord,$wordLength));
	}
	//~ print_r($suggestions);
	//~ die();
	
	// 3. choose best suggestion
	for ($a=0;$a<count($suggestions);$a++) {
		$new_quality=CL_frag_max_cost-CLstring_sim($frag,$suggestions[$a]);
		if ($new_quality>$quality) {
			$best_suggestion=$suggestions[$a]; // Einzelfragmente
			$quality=$new_quality;
		}
	}
	
	//~ echo $frag."<br>";
	//~ print_r($possible_groups);
	return array($best_suggestion,$quality,$flags);
}

/*--------------------------------------------------------------------------------------------------
/ Function: CLsplitFrag
/
/ Purpose: split a fragment at numbers or special characters, return list of fragments and delimiters
/
/ Parameter:
/ 		$frag : fragment of $word
/
/ Return : array of smaller fragments and delimiters like /number/, -, (, ), [, ] (should we use $CL_symbols somehow???)
/------------------------------------------------------------
/ History:
/ 2009-07-17 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function CLsplitFrag($frag) {
	global $CL_symbols_preg;
	return preg_split($CL_symbols_preg,$frag,-1,PREG_SPLIT_NO_EMPTY+PREG_SPLIT_DELIM_CAPTURE);
}

/*--------------------------------------------------------------------------------------------------
/ Function: CLisSpecialChar
/
/ Purpose: determine if a part does not require to be matched, like single letters used as locators or stereodescriptors
/
/ Parameter:
/ 		$parts : list of parts, the neighbours may determine if a single letter is ok
/ 		$partNo : number of part to check
/
/ Return : true (no matching required) or false
/------------------------------------------------------------
/ History:
/ 2009-07-17 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function CLisSpecialChar($parts,$partNo) {
	global $CL_symbols;
	$part=$parts[$partNo];
	if (strlen($part)>1) {
		return false;
	}
	if (is_numeric($part) || in_array($part,$CL_symbols)) {
		return true;
	}
	// N-
	$symbol_after=in_array($parts[$partNo+1],$CL_symbols);
	if ($partNo==0 && $symbol_after) {
		return true;
	}
	$symbol_before=in_array($parts[$partNo-1],$CL_symbols);
	if ($symbol_before==0 && $symbol_after) {
		return true;
	}
	return false;
}

function CLgetSolutionPart($word,$solution,$partNo) {
	//~ echo "A".$partNo;
	if ($partNo>=0 && $partNo<count($solution)) {
		return substr($word,$solution[$partNo][0],$solution[$partNo][1]-$solution[$partNo][0]);
	}
	return false;
}

function CLgetGuessPart($word,$solution,$partNo) {
	//~ echo "B".$partNo;
	if ($partNo>=0 && $partNo<=count($solution)) {
		if ($partNo==0) {
			$start=0;
		}
		else {
			$start=$solution[$partNo-1][1];
		}
		if ($partNo==count($solution)) {
			$end=strlen($word);
		}
		else {
			$end=$solution[$partNo][0];
		}
		return substr($word,$start,$end-$start);
	}
	return false;
}

/*--------------------------------------------------------------------------------------------------
/ Function: CLgetFragRange
/
/ Purpose: returns an array of merged texts around the "gap" at position $b, including a maximum of $items items (matched or unmatched)
/
/ Parameter:
/ 		$word : the full word passed to CLgetSuggestionsForWord
/ 		$solutions : one solution generated by CLbuildSolutionsFromLimits 
/ 		$b : part of the solution which is considered
/ 		$items : number of items (matched or unmatched) to be included (positive: forward, negative: backward)
/
/ Return : array of substrings from $word around $b in the given solution, limited by $items
/------------------------------------------------------------
/ History:
/ 2009-07-17 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function CLgetFragRange($word,$solution,$b,$items) {
	$retval=array();
	$str="";
	if ($items>0) {
		$inc=1;
		$offset=0;
	}
	elseif ($items<0) {
		$inc=-1;
		$offset=-1;
		$items*=$inc;
	}
	else {
		return $retval;
	}
	for ($a=0;$a<$items;$a++) {
		if ($a%2) { // ungerade => dazwischen
			$this_str=CLgetGuessPart($word,$solution,$b+$inc*($a+1)/2);
			// b+(a+1)/2
			// b-(a-1)/2
		}
		else { // gerade => solution
			$this_str=CLgetSolutionPart($word,$solution,$b+$inc*$a/2+$offset);
		}
		if ($this_str===false) { // end reached
			break;
		}
		elseif ($this_str==="") {
			$items++;
		}
		else {
			if ($inc==1) {
				$str.=$this_str;
			}
			else {
				$str=$this_str.$str;
			}
			$retval[$a*$inc]=$str;
		}
	}
	return $retval;
}

//~ var_dump(CLgetFragRange("hexaflouroacetylacetonat",array(array(10,16),array(16,24),),2,-3));die();

/*--------------------------------------------------------------------------------------------------
/ Function: CLgetSuggestionsForFragment
/
/ Purpose: generate possible replacements for unmatched fragments of $word
/
/ Parameter:
/ 		$word : the full word passed to CLgetSuggestionsForWord
/ 		& $solutions : list of solutions generated by CLbuildSolutionsFromLimits
/ 		& $suggestions : array that will be filled by suggestions, using the same indeces $solution_no and $b
/ 		$solution_no : index for the solution being processed
/ 		$b : index for the part of the active solution being processed
/
/ Return : none
/------------------------------------------------------------
/ History:
/ 2009-07-17 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function CLgetSuggestionsForFragment($word,& $solutions,& $suggestions,$solution_no,$b) { // Vorschläge für nicht zuordenbare Fragmente zur Korrektur
	//~ echo $word."D<br>";
	
	// 0 <= $b <= count($solutions[$solution_no])
	// $prev, $next: arrays of (full) text fragments at each side of the unmatched fragment, like ch,chol,cholrid,...
	
	if ($b==0) { // am Anfang
		$start=0;
		$prev=array();
	}
	else {
		$start=$solutions[$solution_no][$b-1][1];
		$prev=CLgetFragRange($word,$solutions[$solution_no],$b,-CL_max_frag_range);
	}
	
	if ($b==count($solutions[$solution_no])) { // am Ende
		$end=strlen($word);
		$next=array();
	}
	else {
		$end=$solutions[$solution_no][$b][0];
		$next=CLgetFragRange($word,$solutions[$solution_no],$b,CL_max_frag_range);
	}
	//~ $prev_len=strlen($prev);
	//~ $next_len=strlen($next);
	// nix zu tun
	if ($start==$end) {
		return;
	}
	$frag=substr($word,$start,$end-$start); // unmatched fragment
	
	//~ echo $solution_no." ".$b." ".$frag."<br>";
	//~ print_r($solution);
	//~ echo "E";
	//~ print_r($prev);
	//~ echo "F";
	//~ print_r($next);
	
	// split at - ( and so on
	$parts=CLsplitFrag($frag);
	
	//~ print_r($parts);
	//~ die();
	
	$retval="";
	// anything left ?
	$posInWord=$start;
	$wordLength=strlen($word);
	for ($a=0;$a<count($parts);$a++) {
		// einzelZeichen lassen und Oxidationszahlen
		if (CLisSpecialChar($parts,$a) || CLmatchRoman($parts[$a])) {
			$retval.=$parts[$a];
			$posInWord+=strlen($parts[$a]);
			continue;
		}
		
		$text_suggestions=array(
			//~ array($parts[$a],0), // keep the old
			array("",0), // nothing
			CLgetSuggestionsForMicrofrag($parts[$a],$posInWord,$wordLength)
		);
		if (count($prev) && $a==0) { // also try combined with $prev
			//~ for ($c=0;$c<count($prev);$c++) {
			foreach($prev as $c => $prev_frag) {
				$text_suggestions[]=CLgetSuggestionsForMicrofrag($prev_frag.$parts[$a],$posInWord-strlen($prev_frag),$wordLength,$c-1);
			}
		}
		if (count($next) && $a==count($parts)-1) { // also try combined with $next
			//~ for ($c=0;$c<count($next);$c++) {
			foreach($next as $c => $next_frag) {
				$text_suggestions[]=CLgetSuggestionsForMicrofrag($parts[$a].$next_frag,$posInWord,$wordLength,$c+1);
			}
		}
		$posInWord+=strlen($parts[$a]);
		
		//~ print_r($suggestions);
		$max_sugg=0;
		for ($c=1;$c<count($text_suggestions);$c++) {
			if ($text_suggestions[$c][1]>$text_suggestions[$max_sugg][1]) {
				$max_sugg=$c;
			}
		}
		$max_sugg_flags=$text_suggestions[$max_sugg][2];
		//~ echo $max_sugg_flags."W".$solution_no."<br>";
		if ($max_sugg_flags!=0) {
			// generate new copy of solution and suggestion
			$new_solution=$solutions[$solution_no];
			$new_suggestion=$suggestions[$solution_no];
		}
		
		//~ print_r($solutions);
		//~ print_r($suggestions);
		//~ echo "X".$solution_no."<br>";
		//~ echo $b."-".$retval."<br>";
		
		if ($max_sugg_flags<0) { // prev x
			for ($c=0;$c>$max_sugg_flags;$c--) { // $c<=0
				if ($c%2) {
					if (is_array($new_suggestion)) { // -1,-3,-5,... => -1,-2,-3
						$idx=$a+($c+1)/2;
						//~ $suggestions[$solution_no][$idx]=""; // Numerierung muß erhalten bleiben
						array_splice($new_suggestion,$idx,1);
					}
				}
				elseif (is_array($new_solution)) { // 0,-2,-4,... => -1,-2,-3...
					$idx=$a+$c/2-1;
					//~ $solutions[$solution_no][$idx]=array(); // Numerierung muß erhalten bleiben
					array_splice($new_solution,$idx,1);
				}
			}
		}
		elseif ($max_sugg_flags>0) { // next x
			for ($c=0;$c<$max_sugg_flags;$c++) { // $c>=0
				if ($c%2) {
					if (is_array($new_suggestion)) { // 1,3,5,... => 1,2,3
						$idx=$a+($c+1)/2;
						//~ $suggestions[$solution_no][$idx]=""; // Numerierung muß erhalten bleiben
						array_splice($new_suggestion,$idx,1);
					}
				}
				elseif (is_array($new_solution)) { // 0,2,4,... => 0,1,2,...
					$idx=$a+$c/2;
					//~ $solutions[$solution_no][$idx]=array(); // Numerierung muß erhalten bleiben
					array_splice($new_solution,$idx,1);
				}
			}
		}
		
		if ($max_sugg_flags!=0) {
			// check for double entries
			$found_idx=array_search($new_solution,$solutions);
			if ($found_idx!==FALSE && $suggestions[$found_idx]==$new_suggestion) {
				continue;
			}
			
			$found_idx=array_search($new_suggestion,$suggestions);
			if ($found_idx!==FALSE && $solutions[$found_idx]==$new_solution) {
				continue;
			}
			
			$solutions[]=$new_solution;
			$suggestions[]=$new_suggestion;
			$solution_no=count($solutions)-1;
		}
		
		$retval.=$text_suggestions[$max_sugg][0];
		//~ print_r($solutions);
		//~ print_r($suggestions);
	}
	
	$suggestions[$solution_no][$b]=$retval;
	//~ echo $solution_no;
	//~ print_r($solutions[$solution_no]);
	//~ print_r($suggestions[$solution_no]);
		
	//~ echo $frag."/".$retval."Y<br>";
	
	//~ return $retval;
}

/*--------------------------------------------------------------------------------------------------
/ Function: CLgetLimits
/
/ Purpose: match a regular expression on a word, returning an array of all matches (=array($start,$end)) for that chain (or the 1st alternative) in $word
/
/ Parameter:
/ 		$re : regular expression to be matched
/ 		$word : the full word passed to CLgetSuggestionsForWord
/ 		$item : return the whole word matches or only for the 1st bracket in $re
/
/ Return : array of all matches (=array($start,$end)) for that chain (or the 1st alternative) in $word
/------------------------------------------------------------
/ History:
/ 2009-07-17 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function CLgetLimits($re,$word,$item=0) {
	//~ echo $re."<br>";
	preg_match_all($re,$word,$results,PREG_OFFSET_CAPTURE+PREG_PATTERN_ORDER);
	$results=$results[$item];
	//~ print_r($results);
	//~ die($word);
	
	$limits=array();
	// return array of start and end indices
	for ($a=0;$a<count($results);$a++) {
		$limits[]=array($results[$a][1],$results[$a][1]+strlen($results[$a][0]));
	}
	return $limits;
}

/*--------------------------------------------------------------------------------------------------
/ Function: CLmatchChainOnWord
/
/ Purpose: removes duplicate entries from an array that has arrays as elements (not supported by built-in function array_unique), keys are not preserved
/
/ Parameter:
/ 		$chainNo : the chain (sequence definition) number to match on $word (partially)
/ 		$word : the full word passed to CLgetSuggestionsForWord
/
/ Return : the result of CLgetLimits($re,$word), where $re is a regular expression generated based on the chain definition
/------------------------------------------------------------
/ History:
/ 2009-07-17 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function CLmatchChainOnWord($chainNo,$word) { // gibt Array mit Treffern zurück, auch mehrfach nicht-überlappend
	// build RE from $chain
	global $CL_groups,$CL_chains;
	$re="/(?ims)";
	for ($a=0;$a<count($CL_chains[$chainNo]);$a++) {
		$alternatives=CLgetAlternatives($chainNo,$a);
		if (count($alternatives)) {
			$re.="(".join("|",$alternatives).")";
			if ($CL_chains[$chainNo][$a]["opt"]) {
				$re.="?";
			}
		}
		else {
			switch ($CL_chains[$chainNo][$a]["type"]) {
			case "start":
				$re.="^";
			break;
			case "end":
				$re.="\$";
			break;
			}
		}
	}
	$re.="/";
	
	//~ echo $re."V<br>";
	
	return CLgetLimits($re,$word);
}

/*--------------------------------------------------------------------------------------------------
/ Function: CLlimitSort
/
/ Purpose: compares limits (=array($start,$end)) to sort the earlier beginning and - if equal, the longer - matches in front
/
/ Parameter:
/ 		$a, $b : array($start,$end) describing matches of chains in a word
/
/ Return : 1 or -1
/------------------------------------------------------------
/ History:
/ 2009-07-17 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function CLlimitSort($a,$b) {
	if ($a[0]==$b[0]) {
		return ($a[1]>$b[1]?-1:1); // länger zuerst
	}
	return ($a[0]>$b[0]?1:-1);
}

/*--------------------------------------------------------------------------------------------------
/ Function: CLbuildSolutionsFromLimits
/
/ Purpose: tries to build (sensible) non-overlapping combinations of limits
/
/ Parameter:
/ 		& $limits : array of all matches (=array($start,$end)) of chains in $word (which is not used here)
/
/ Return : an array of solutions, where every solution is an array of sequential non-overlapping (gaps are possible) limits (which are arrays with 2 elements, $start and $end, themselves)
/------------------------------------------------------------
/ History:
/ 2009-07-17 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function CLbuildSolutionsFromLimits(& $limits) {
	// sort limits by start
	usort($limits,"CLlimitSort");
	
	// combine limits and identify unused areas
	$solutions=array();
	for ($a=0;$a<count($limits);$a++) { // abdeckbare Bereiche durchgehen
		$solution_found=false;
		$solutions_count=count($solutions);
		// limit-Bereich an Lösungen anfügen, längere Fragmente werden bevorzugt
		for ($b=0;$b<$solutions_count;$b++) { // bestehende Lösungskombinationen durchgehen
			$this_sol_count=count($solutions[$b]);
			if ($solutions[$b][ $this_sol_count-1 ][1]<=$limits[$a][0]) { // an bestehende Lösung anhängen, keine Überlappung
				$solutions[$b][]=$limits[$a];
				$solution_found=true;
			}
			elseif (is_array($solutions[$b][ $this_sol_count-2 ]) && $solutions[$b][ $this_sol_count-2 ][1]<=$limits[$a][0]) { // Überlappung mit letztem Element, aber nicht mit vorletztem: neue Lösungsmöglichkeit, letztes Element wird ersetzt
				$new_solution=$solutions[$b];
				$new_solution[$this_sol_count-1]=$limits[$a];
				$solutions[]=$new_solution;
				$solution_found=true;
			}
		}
		// wenn noch keine $solutions oder paßt an keine ran: neue $solution
		if (!$solution_found) {
			$solutions[]=array($limits[$a]);
		}
	}
	return $solutions;
}

/*--------------------------------------------------------------------------------------------------
/ Function: CLarray_unique
/
/ Purpose: removes duplicate entries from an array that has arrays as elements (not supported by built-in function array_unique), keys are not preserved
/
/ Parameter:
/ 		$arr : an array of arrays to be cleaned from duplicates
/
/ Return : an array without duplicate array entries
/------------------------------------------------------------
/ History:
/ 2009-07-17 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function CLarray_unique($arr) {
	$retval=array();
	for ($a=0;$a<count($arr);$a++) {
		for ($b=$a+1;$b<count($arr);$b++) {
			if ($arr[$a]==$arr[$b]) {
				continue 2;
			}
		}
		$retval[]=$arr[$a];
	}
	return $retval;
}

/*--------------------------------------------------------------------------------------------------
/ Function: CLgetSuggestionsForWord
/
/ Purpose: checks if a word is valid according to the rules defined through $CL_chains and $CL_groups and returns suggestion (if applies)
/
/ Parameter:
/ 		$word : a string entered a search query, possibly a molecule name or a part, maybe misspelled
/
/ Return : true if the word seems to be valid, false if no good guess was found and a string containing the guess otherwise
/------------------------------------------------------------
/ History:
/ 2009-07-17 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function CLgetSuggestionsForWord($word) {
	global $CL_chains;
	
	$word_len=strlen($word);
	
	// short words result in bogus suggestions, not enough context
	if ($word_len<5) {
		return;
	}
	
	// get start and end for matches
	$limits=array();
	for ($a=0;$a<count($CL_chains);$a++) {
		$limits=array_merge($limits,CLmatchChainOnWord($a,$word));
	}
	//~ print_r($limits);
	//~ die();
	
	$solutions=CLbuildSolutionsFromLimits($limits);
	//~ print_r($solutions);
	$solutions=CLarray_unique($solutions);
	//~ print_r($solutions);
	//~ die();
	
	// check if solution matches $word
	for ($a=0;$a<count($solutions);$a++) {
		if ($solutions[$a][0][0]!=0) {
			continue;
		}
		$solution_count=count($solutions[$a]);
		for ($b=1;$b<$solution_count;$b++) {
			if ($solutions[$a][$b-1][1]!=$solutions[$a][$b][0]) {
				continue 2;
			}
		}
		if ($solutions[$a][$solution_count-1][1]!=$word_len) {
			continue;
		}
		// durchgekommen
		return true;
	}
	//~ print_r($solutions);
	//~ die();
	
	// find unused areas and make suggestions
	$suggestions=array();
	for ($a=0;$a<count($solutions);$a++) {
		//~ for ($b=count($suggestions[$a]);$b<=count($solutions[$a]);$b++) { // start at point where it continues
		for ($b=0;$b<=count($solutions[$a]);$b++) { // start at point where it continues
			//~ echo $a."X".$b."<br>";
			//~ print_r($solutions[$a]);
			//~ flush();
			CLgetSuggestionsForFragment($word,$solutions,$suggestions,$a,$b);
			//~ var_dump($suggestions[$a]);
			//~ flush();
		}
	}
	
	//~ print_r($solutions);print_r($suggestions);
	
	// check suggestions for two consecutive equal entries
	for ($a=count($solutions)-1;$a>=0;$a--) {
		for ($b=0;$b<count($solutions[$a]);$b++) {
			if ($suggestions[$a][$b]!="" && $suggestions[$a][$b]==$suggestions[$a][$b+1] && preg_match("/(ims?)^[a-zA-Z]+\$/",$suggestions[$a][$b])) { // allow with symbols or numbers
				array_splice($solutions,$a,1);
				array_splice($suggestions,$a,1);
				continue 2;
			}
		}
	}
	//~ print_r($solutions);print_r($suggestions);
	//~ die();
	
	// merge
	$suggestion_texts=array();
	$suggestion_cost=array();
	for ($a=0;$a<count($solutions);$a++) {
		$suggestion_texts[$a]="";
		for ($b=-1;$b<count($solutions[$a]);$b++) {
			if (is_array($solutions[$a][$b])) { // ignore -1 and deleted ones
				$suggestion_texts[$a].=substr($word,$solutions[$a][$b][0],$solutions[$a][$b][1]-$solutions[$a][$b][0]);
			}
			$suggestion_texts[$a].=$suggestions[$a][$b+1];
		}
	}
	
	//~ if (!count($solutions)) { // no fragment found
	list($whole)=CLgetSuggestionsForMicrofrag($word,0,$word_len);
	$suggestion_texts[]=$whole;
	//~ }
	
	$suggestion_texts=array_values(array_unique($suggestion_texts));
	
	for ($a=0;$a<count($suggestion_texts);$a++) {
		if ($suggestion_texts[$a]==="") {
			continue;
		}
		$suggestion_cost[$a]=CLstring_sim($word,$suggestion_texts[$a]);
		if ($suggestion_cost[$a]==0) { // nothing to do
			return true;
		}
		if ($a==0 || $suggestion_cost[$a]<$suggestion_cost[$best_solution]) {
			$best_solution=$a;
		}
	}
	//~ print_r($suggestion_texts);print_r($suggestion_cost);
	//~ die($best_solution);
	
	if ($suggestion_cost[$best_solution]>CL_word_max_cost) {
		return false; // no good idea
	}
	
	// find best suggestion
	
	return $suggestion_texts[$best_solution];
}

function displayFixedQuery() {
	if (!empty($_REQUEST["query"]) && in_array($_REQUEST["crit0"],array("molecule.molecule_auto","molecule_names.molecule_name")) && $_REQUEST["val0"]!="") {
		// Vorschlag holen
		$suggestion=CLgetSuggestionsForWord($_REQUEST["val0"]);
		if (!empty($suggestion) && $suggestion!==TRUE) {
			echo "<br><span class=\"bigger\">".s("did_you_mean1")." <a href=\"javascript:searchSidenav(".fixQuot($suggestion).")\">".$suggestion."</a>".s("did_you_mean2")."</span>";
		}
	}
}

/* function CLgetSuggestionsForWord($word) {
	// 1. Prüfen, wahrscheinlichste Einteilungen in Fragmente finden
	$fragmentations=CLgetPossibleFragmentations($word);
	
	// 2. Korrigieren, wahrscheinlichste Ersetzungen für nicht auffindbare Fragmente ausmachen
	
} */

?>