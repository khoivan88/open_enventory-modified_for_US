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

function readJCamp($jdx) {
	// reads a JCamp file, returns array of lines
	// nach zeilen trennen
	// echo microtime(true)."\n";
	// $jdx=fixMultispace($jdx);
	$jdx=fixLineEnd($jdx);
	$lines=explode("\n",$jdx);
	// cut comment
	for ($a=0;$a<count($lines);$a++) {
		$comment_start=strpos($lines[$a],"\$\$");
		if ($comment_start!==FALSE) {
			$lines[$a]=substr($lines[$a],0,$comment_start);
		}
		$lines[$a]=ltrim($lines[$a]);
	}
	return $lines;
}

$sqz=array("@","A","B","C","D","E","F","G","H","I","a","b","c","d","e","f","g","h","i");
$sqznum=array(" 0"," 1"," 2"," 3"," 4"," 5"," 6"," 7"," 8"," 9"," -1"," -2"," -3"," -4"," -5"," -6"," -7"," -8"," -9");
$difdup=array("%","J","K","L","M","N","O","P","Q","R","j","k","l","m","n","o","p","q","r","S","T","U","V","W","X","Y","Z","s");
$difdupnum=array(" D0"," D1"," D2"," D3"," D4"," D5"," D6"," D7"," D8"," D9"," D-1"," D-2"," D-3"," D-4"," D-5"," D-6"," D-7"," D-8"," D-9"," R1"," R2"," R3"," R4"," R5"," R6"," R7"," R8"," R9");
$bondLetter=array("1" => "S", "2" => "D", "3" => "T", "4" => "A");

function parseJCampData(& $data,& $lines,& $a,& $persistent) {
// verarbeitet Datenblöcke im JCamp, alle Formate
	global $sqz,$sqznum,$difdup,$difdupnum;
	
	$retval=true; // assume no error
	do {
		$lastLine=($a+1==count($lines) || startswith($lines[$a+1],"##")); // to finish processing
		$emptyLine=(trim($lines[$a])=="");
		switch ($persistent["varname"]) { // peaktable,xydata,...
		case "peaktable":
			if ($emptyLine) break;
			list($data[],)=explode(", ",$lines[$a]);
		break;
		case "integrals":
		case "peakassignments":
			$data.=trim($lines[$a]); // split afterwards
			if ($lastLine) {
				$peaksass=explode(")(",substr($data,1,-1));
				$data=array();
				foreach ($peaksass as $dataset) {
					$datasplit=explode(",",$dataset); // ppm, intensity,M?,<atomnumber>
					// wir brauchen nur ppm und Atomname
					switch (strtolower($persistent["value"])) {
					case "(xyma)":
						$data[]=array("shift" => $datasplit[0],"atomname" => trim($datasplit[3],"<>"));
					break;
					case "(xya)":
						$data[]=array("shift" => $datasplit[0],"atomname" => trim($datasplit[2],"<>"));
					break;
					case "acdtable(x1,x2,logvalue)":
						$data["int_start"][]=$datasplit[1];
						$data["int_end"][]=$datasplit[0];
						$data["int_area"][]=$datasplit[2];
					break;
					}
				}
			}
		break;
		case "molfile":
			if ($emptyLine) break;
			$data.=$lines[$a]."\n";
		break;
		case "atomlist":
		case "bondlist":
		case "xyraster":
		case "stereopair":
			if ($emptyLine) break;
			$data[]=spaceSplit($lines[$a]);
		break;
		case "datatable":
		case "xydata": // decode the whole to X/Y-values x -> [0], y -> [1]
			if ($emptyLine) break;
			if ($mode==0) {
				if ( containsMulti($lines[$a],$difdup) ) {
					$mode=3; // DIFDUP
				}
				elseif ( containsMulti($lines[$a],$sqz) ) {
					$mode=2; // SQZ
				}
				else {
					$mode=1; // fixed (furchtbar langsam) or PAC
				}
			}
			$lines[$a]=ltrim(str_replace(array("+","-"),array(" "," -"),$lines[$a])); // insert spaces
			if ($mode>=2) {
				$lines[$a]=str_replace($sqz,$sqznum,$lines[$a]); // make PAC out of SQZ
			}
			$data_count=count($data);
			if ($mode>=3) {
				$line=str_replace($difdup,$difdupnum,$lines[$a]);
				$values=explode(" ",$line); // gives X Y1 DY2 DY3...
				$newvalues=array();
				for ($b=0;$b<count($values);$b++) {
					$idx=count($newvalues);
					$firstLett=$values[$b]{0};
					$restStr=substr($values[$b],1);
					if ($firstLett=="D") { // DIF
						$newvalues[$idx]=$newvalues[$idx-1]+$restStr;
						$last_is_dif=true; // needed for DIFDUP to see if a SQZ or a DIF was duped
					}
					elseif ($firstLett=="R") { // DUP
						if ($last_is_dif) { // DIFDUP
							for ($c=1;$c<$restStr;$c++) {
								$newvalues[]=$newvalues[$idx-1]+substr($newvalues[$idx-1],1)*($c+1);
							}
						}
						else { // SQZDUP
							for ($c=1;$c<$restStr;$c++) {
								$newvalues[]=$newvalues[$idx-1];
							}
						}
					}
					else {
						$newvalues[$idx]=$values[$b];
						$last_is_dif=false;
					}
				}
				// print_r($newvalues);
				// $newvalues=xyyyyy
				if ($data_count==0 || $newvalues[1]==$data[$data_count-1]) { // consistent
					$newvalues[0]=array_shift($newvalues); // remove 2nd element by removing 1st and overwriting 2nd with 1st
				}
				else {
					echo "JCamp y data corrupt on line ".$a."\n";
					$retval=false;
				}
			}
			else {
				$newvalues=spaceSplit($lines[$a]);
			}
			$x_val=$newvalues[0];
			$this_line_count=count($newvalues)-1;
			for ($b=1;$b<count($newvalues);$b++) {
				$data[]=$newvalues[$b];
			}
			// check for x consistency
			// echo $x_val." ".($persistent["first_x"]+$this_npoints*$persistent["dx"])."\n";
			$diff=abs($x_val-($persistent["first_x"]+$this_npoints*$persistent["dx"]));
			if ($diff>2) { // data coming from ACD 7 is really s***, therefore pretty high tolerance
				// something wrong
				echo "JCamp x data corrupt on line ".$a." ".$x_val."/".($persistent["first_x"]+$this_npoints*$persistent["dx"])."\n";
				$retval=false;
			}
			$this_npoints+=$this_line_count; // add points within this line to total
		break;
		default:
			if ($emptyLine) break;
			$data[]=$lines[$a];
		}
	$a++;
	} while (!$lastLine);
	$a--;
	return $retval;
}

function parseJCampNTuples(& $lines,& $a,& $persistent) {
// verarbeitet NTUPLES-Struktur
	$attribs=array("varname","symbol","vartype","varform","vardim","units","first","last","min","max","factor");
	do {
		// set_time_limit(5);
		if (trim($lines[$a])=="") { // ignore empty lines
		
		}
		elseif (startswith($lines[$a],"##")) {
			// variable
			$equals_start=strpos($lines[$a],"=");
			$persistent["varname"]=fixJCampVar(substr($lines[$a],2,$equals_start-2));
			$persistent["value"]=substr($lines[$a],$equals_start+1);
			if ($persistent["varname"]=="page") { // new index
				$idx++;
				list($name,$persistent["value"])=explode("=",$persistent["value"],2);
				$jcamp[$idx][strtolower($name)]=$persistent["value"];
			}
			elseif ($persistent["varname"]=="endntuples") {
				return $jcamp;
			}
			elseif (in_array($persistent["varname"],$attribs)) {
				// split and assign
				$values=explode(",",$persistent["value"]);
				array_walk($values,"trim_value");
				foreach ($values as $thisIdx => $thisValue) {
					$jcamp[$thisIdx][ $persistent["varname"] ]=$thisValue; // ["value"]
				}
			}
			else {
				$jcamp[$idx][ $persistent["varname"] ]["value"]=$persistent["value"];
			}
		}
		else {
			$persistent["dx"]=($jcamp[0]["last"]-$jcamp[0]["first"])/($jcamp[0]["vardim"]-1);
			$persistent["first_x"]=$jcamp[0]["first"];
			parseJCampData($jcamp[$idx][ $persistent["varname"] ]["data"],$lines,$a,$persistent);
		}
		$a++;
	} while($a<count($lines));
	return $jcamp;
}

function parseJCampBlock(& $lines,& $a) {
	// make name value pairs, detecting multiline entries
	do {
		// set_time_limit(5);
		if (trim($lines[$a])=="") { // ignore empty lines
		
		}
		elseif (startswith($lines[$a],"##")) {
			// variable
			$equals_start=strpos($lines[$a],"=");
			$persistent["varname"]=fixJCampVar(substr($lines[$a],2,$equals_start-2));
			$persistent["value"]=substr($lines[$a],$equals_start+1);
			// echo $a." ".$lines[$a]."\n";
			if ($persistent["varname"]=="title") {
				$a++;
				$jcamp["objects"][]=parseJCampBlock($lines,$a); // get sub object starting from following line
			}
			elseif ($persistent["varname"]=="end") {
				return $jcamp; // give back to prior level
			}
			elseif ($persistent["varname"]=="ntuples") {
				$a++;
				$jcamp["objects"]=parseJCampNTuples($lines,$a,$persistent); // get sub object starting from following line
			}
			else {
				$jcamp[ $persistent["varname"] ]["value"]=$persistent["value"];
			}
		}
		else {
			if ($persistent["varname"]=="xydata") {
				$persistent["dx"]=($jcamp["lastx"]["value"]-$jcamp["firstx"]["value"])/($jcamp["npoints"]["value"]-1);
				$persistent["first_x"]=$jcamp["firstx"]["value"];
			}
			parseJCampData($jcamp[ $persistent["varname"] ]["data"],$lines,$a,$persistent);
		}
		$a++;
	} while($a<count($lines));
	return $jcamp;
}

//---------------------------------JCamp writing----------------------------------------------------

function getMolfile($molecule) {
	global $bondLetter;
	
	$retval=<<<END
##ATOMLIST=
$$        AN        AS        NH

END;
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		$retval.=leftSpace("",10).rightSpace($a+1,10).rightSpace($molecule["atoms"][$a][ATOMIC_SYMBOL],10).$molecule["atoms"][$a][ORIG_IMPLICIT_H]."\n";
	}
	
	$retval.=<<<END
##BONDLIST=
$$        AN1       AN2       BT

END;
	for ($a=0;$a<count($molecule[BONDS]);$a++) {
		if ($molecule[BONDS][$a][ATOM1]!=$molecule["bonds"][$a][ATOM2]) {
			$retval.=leftSpace("",10).rightSpace($molecule["bonds"][$a][ATOM1],10).rightSpace($molecule["bonds"][$a][ATOM2],10).$bondLetter[ $molecule["bonds"][$a][ORIG_BOND_ORDER] ]."\n";
		}
	}
	
	$retval.=<<<END
##XY_RASTER=
$$        AN        X         Y         Z

END;
	for ($a=0;$a<count($molecule["atoms"]);$a++) { // Koordinaten zu Ganzzahlen machen
		$retval.=leftSpace("",10).rightSpace($a+1,10).rightSpace($molecule["atoms"][$a]["x"],10).rightSpace($molecule["atoms"][$a]["y"],10).$molecule["atoms"][$a]["z"]."\n";
	}
	
	$retval.=<<<END
##$MOLFILE=  $$ Empty String
  TU_Kaiserslautern
  $$ Empty String

END;

	// MOLFILE for all
	$retval.=getMolfileBody($molecule);
	return $retval;
}

function getSQZ($num) {
	global $sqz;
	
	$num=round($num)."";
	if ($num<0) {
		return $sqz[$num{1}+9].substr($num,2);
	}
	else {
		return $sqz[$num{0}].substr($num,1);
	}
}

function getDIF($num) {
	global $difdup;

	$num=round($num)."";
	if ($num<0) {
		return $difdup[$num{1}+9].substr($num,2);
	}
	else {
		return $difdup[$num{0}].substr($num,1);
	}
}

function createDIF($data,$low_hz,$high_hz,$factor=1) {
// for bruker data, no conversion necessary (already integer), for ACD we multiply the float values by (max_float-min_float) to keep max precision, JCamp in principle is BS
	$data_count=count($data);
	if ($data_count<=0 || $high_hz==$low_hz || $factor<=0) {
		// echo $high_hz." ".$low_hz." ".$factor;
		return "";
	}
	if ($high_hz<$low_hz) {
		swap($high_hz,$low_hz);
	}
	$dx=($high_hz-$low_hz)/$data_count;
	$a=0;
	do {
		if ($line_len==0) { // new line
			// add x value
			// add sqz
			$open_line=round($high_hz-$a*$dx,1).getSQZ($data[$a]*$factor);
			$line_len=strlen($open_line);
		}
		else { // existing line
			// make dif
			$next_dif=getDIF(round($data[$a]*$factor)-round($data[$a-1]*$factor));
			$next_len=strlen($next_dif);
			// if result would be too long, close line and reduce $a by one to add the last value as 1st of the next line 
			if ($line_len+$next_len>78) { // new line
				$lines[]=$open_line;
				$line_len=0;
				if ($a+1<$data_count) { // not the last line
					$a-=2;
				}
			}
			else {
				$open_line.=$next_dif;
				$line_len+=$next_len;
				if ($a+1==$data_count) { // last line
					$lines[]=$open_line;
					$lines[]=round($high_hz-$a*$dx,1).getSQZ($data[$a]*$factor);
				}
			}
		}
		$a++;
	} while ($a<$data_count);
	return join("\n",$lines);
}

function createData($paramHash,$real_data,$imag_data=array(),$molecule=array()) {
	procSweep($paramHash);
	// DIF format
	// structure block
	if (count($molecule)) {
		$retval.='
##TITLE=                             $$ Begin of the structure block
##JCAMP-CS=3.7
##ORIGIN= TU_Kaiserslautern
##OWNER= 
##BLOCK_ID='.(++$blocks).'
'.getMolfile($molecule).'
$$$$

##END=$$ End of the structure block
';
	}
	// assignment block gibt es nicht
	// peak block lassen wir auch erstmal weg, auto peak picking dürfte im ACD recht leicht gehen 	
	// $factor=pow(2,31)/($result["dataHash"]["y_max"]-$result["dataHash"]["y_min"]); // maximise precision for float values
	$factor=1; 
	if (count($imag_data)) { // make ntuples structure
		$retval.='
##TITLE=                             $$ Begin of the data block
##JCAMP-DX=5.00
##BLOCK_ID='.(++$blocks).'
##DATE='.$paramHash["date"].'
##DATA TYPE=NMR SPECTRUM
##DATA CLASS=NTUPLES
##ORIGIN='.$paramHash["instrum"].'
##OWNER='.$paramHash["owner"].'
##.OBSERVE FREQUENCY='.$paramHash["freq_mhz"].'
##.ACQUISITION TIME='.$paramHash["acquisition_time"].'
##.ZERO FILL=0
##.OBSERVE NUCLEUS=^'.$paramHash["nuc_mass"].$paramHash["nuc_sym"].'
##.ACQUISITION MODE=SIMULTANEOUS
##SPECTROMETER/DATA SYSTEM=??
##SOLVENT NAME='.$paramHash["solvent"].'
##NTUPLES=NMR SPECTRUM
##VAR_NAME=       FREQUENCY,     SPECTRUM/REAL, SPECTRUM/IMAG
##SYMBOL=         X,             R,             I,             N
##VAR_TYPE=       INDEPENDENT,   DEPENDENT,     DEPENDENT,     PAGE
##VAR_FORM=       AFFN,          ASDF,          ASDF,          AFFN
##VAR_DIM=        '.multStr( rightSpace($paramHash["npoints"]."," ,15),3).'2
##UNITS=          HZ,            ARBITRARY UNITS,ARBITRARY UNITS
##FIRST=          '.rightSpace($paramHash["hz_max"].",",15).rightSpace($real_data[0].",",15).rightSpace($imag_data[0].",",15).'1
##LAST=           '.rightSpace($paramHash["hz_min"].",",15).rightSpace($real_data[count($real_data)-1].",",15).rightSpace($imag_data[count($imag_data)-1].",",15).'2
##MIN=            '.rightSpace($paramHash["hz_min"].",",15).rightSpace($paramHash["y_min"].",",15).rightSpace($paramHash["i_min"].",",15).'1
##MAX=            '.rightSpace($paramHash["hz_max"].",",15).rightSpace($paramHash["y_max"].",",15).rightSpace($paramHash["i_max"].",",15).'2
##FACTOR=         1.0000000000,'.multStr( rightSpace($factor."," ,15),3).'1
##PAGE=N=1
##DATA TABLE=(X++(R..R)),  XYDATA
'.createDIF($real_data,$paramHash["hz_min"],$paramHash["hz_max"],$factor).'
##PAGE=N=2
##DATA TABLE=(X++(I..I)),  XYDATA
'.createDIF($imag_data,$paramHash["hz_min"],$paramHash["hz_max"],$factor).'
##END NTUPLES=NMR SPECTRUM
##END=$$ End of the data block
##END=$$ End of the link block
';
	}
	else { // xydata
		$retval.='
##TITLE=                             $$ Begin of the data block
##JCAMP-DX=5.00
##BLOCK_ID='.(++$blocks).'
##DATE='.$paramHash["date"].'
##DATA TYPE=NMR SPECTRUM
##DATA CLASS=XYDATA
##ORIGIN='.$paramHash["instrum"].'
##OWNER='.$paramHash["owner"].'
##.OBSERVE FREQUENCY='.$paramHash["freq_mhz"].'
##.ACQUISITION TIME='.$paramHash["acquisition_time"].'
##.ZERO FILL=0
##.OBSERVE NUCLEUS=^'.$paramHash["nuc_mass"].$paramHash["nuc_sym"].'
##.ACQUISITION MODE=SIMULTANEOUS
##SPECTROMETER/DATA SYSTEM=??
##SOLVENT NAME='.$paramHash["solvent"].'
##NPOINTS='.$paramHash["npoints"].'
##FIRSTX='.$paramHash["hz_max"].'
##LASTX='.$paramHash["hz_min"].'
##MAXX='.$paramHash["hz_max"].'
##MINX='.$paramHash["hz_min"].'
##MAXY='.($paramHash["y_max"]*$factor).'
##MINY='.($paramHash["y_min"]*$factor).'
##XFACTOR='.$factor.'
##YFACTOR=1.000000000000000
##FIRSTY='.$real_data[0].'
##DELTAX='.($paramHash["hz_min"]-$paramHash["hz_max"])/$paramHash["npoints"].'
##XUNITS=HZ
##YUNITS=ARBITRARY UNITS
##XYDATA=(X++(Y..Y))
'.createDIF($real_data,$paramHash["hz_min"],$paramHash["hz_max"],$factor).'
##END=$$ End of the data block
##END=$$ End of the link block
';
	}
	$retval='
##TITLE=                             $$ Begin of the link block
##JCAMP-DX=5.00
##ORIGIN= '.$paramHash["origin"].'
##OWNER= '.$paramHash["owner"].'
##DATA TYPE=LINK
##BLOCKS='.$blocks.$retval;
	return $retval;
}

?>