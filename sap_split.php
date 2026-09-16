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
 *------------------------------------------------------------------------------
 * History
 *
 * 2009-10-19 MST00 Add Amount fields for "mpi_order" processing
 * 2009-10-20 MST01 Set department for orderer
 *------------------------------------------------------------------------------
 */
require_once "lib_global_funcs.php";
require_once "lib_global_settings.php";
require_once "lib_db_manip.php";
require_once "lib_db_query.php";
require_once "lib_formatting.php";
require_once "lib_constants.php";
require_once "lib_simple_forms.php";

function parseSAP($data) {
	global $db, $db_pw, $db_user;

//>>>MST01
// SELCT cost centres
//
        $pdoMpical = null;
        $costCentres = null;
        $lagerDbIds = null;
        try {

            //establish connections
            $pdoMpical = new PDO('mysql:host='.MPICAL_DB_HOST.';dbname=mpical', $db_user, $db_pw);

            $pdoLager = new PDO('mysql:host='.db_server.';dbname=lager', $db_user, $db_pw);

            //get cost centres
            $sql = "SELECT c.cost_centre, d.name \n"
                 . "FROM adm_cost_centre AS c \n"
                 . "LEFT JOIN adm_databases AS d ON c.dbid=d.dbid";

            $stSql = $pdoMpical->prepare( $sql );

            $ret = $stSql->execute();
            if( $ret ) {
                if( $stSql->rowCount() > 0 ) {

                    $costCentres = $stSql->fetchAll( PDO::FETCH_ASSOC );

                    //get corresponding db ids for cost centres
                    $stSql = $pdoLager->prepare("SELECT other_db_id,db_name FROM other_db");
                    $ret = $stSql->execute();
                    if( $ret ) {
                        if( $stSql->rowCount() > 0 ) {

                            $lagerDbIds = $stSql->fetchAll( PDO::FETCH_ASSOC );
                        }
                    }

                }
            }

        } catch (Exception $e) {
            //do nothing
            echo $e;
            die( "EOF" );
        }

//
//<<<MST01
	$old_REQUEST=$_REQUEST;
	$data=fixLineEnd($data);
	$raw_lines=explode("\n",$data);
	echo "RAW lines: ".count($raw_lines)."<br>\n";
	$line_len=81;
	$lines=array();
	$stage=-1;
	$format=0;
	// 0=unknown
	// 1=|-------------------------------------------------------------------------------|
	// 2=oben/mitte/unten ---------------------------------------------------------------------------------
	$dashLine="---------------------------------------------------------------------------------";
	$pipeLine="|-------------------------------------------------------------------------------|";

	for ($a=0;$a<count($raw_lines);$a++) {
		$this_line_len=strlen($raw_lines[$a]);
		if ($this_line_len==0) {
			continue;
		}
		if ($line_len!=$this_line_len) {
			echo "Warning: Line ".($a+1)." is ".$this_line_len." characters long, should be ".$line_len.".<br>\n";
		}
		if ($raw_lines[$a]==$dashLine) { // end of data area
			$stage++;
			$stage%=3;
			if ($format==1) {
				$stage=0;
			}
			elseif ($stage>0) {
				$format=2;
			}
			continue;
		}
		if ($raw_lines[$a]==$pipeLine) { // start of data area
			$stage=1;
			$format=1;
			continue;
		}
		if ($stage!=1) { // header
			continue;
		}
		$lines[]=utf8_encode($raw_lines[$a]);
	}

	echo "Data lines: ".count($lines)."<br>\n";
	echo "<table class=\"subitemlist\"><thead><tr><td>".s("order_date")."</td><td>".s("sap_stamm_nr")."</td><td>".s("identifier")."</td><td>".s("amount")."</td><td>".s("supplier")."</td><td>".s("order_account")."</td><td>".s("order_person")."</td></tr></thead><tbody>\n";

	$skip=0;
	for ($a=0;$a<count($lines);$a++) {
		if ($skip>0) {
			$skip--;
			continue;
		}
		$delta=0;

		// check if date in 1st line
		$test_date=substr($lines[$a],69,10);
		if (preg_match("/\d{2}\.\d{2}\.\d{4}/",$test_date)) {
			list(,$sap_bestell_nr,,$lieferant_nr,$supplier,$ekg,$order_date)=colSplit($lines[$a],
				array(1,	11,	   5,		11,		37,							4,	10)); // +2
			//		|346451     NB   30730      Sigma-Aldrich Chemie GmbH            523 16.01.2009 |
		}
		else {
			$delta--;
		}

		list(,$pos,,$sap_stamm_nr,$molecule_name,$warengrp,)=colSplit($lines[$a+$delta+1],
			array(1,	7,   1,	   19,		41,						10)); // +2
		//		|  00005 102480             Brompentafluorbenzol 99%                 4000       |
		list(,$typ,,$werk_l_ort,$amount,,$amount_unit,$price_netto,,$price_netto_currency,$package_amount,,$package_amount_unit,)=colSplit($lines[$a+$delta+2],
			array(7,	1,1,23,			11,		2,9,		10,	1,	4,    7,1,	3)); // +1
		//		|      P KOFO                        20,000  G            34,80  EUR      10 G  |

		// manchmal ist Kostenstelle angegeben
		unset($acc_no);
		if (strpos($lines[$a+$delta+3],"Kostenstelle")!==FALSE) {
			list($check5,$acc_no)=colSplit($lines[$a+$delta+3],
				array(19,			8));
			// |     Kostenstelle 103100                                                       |
			if ($check5!="|     Kostenstelle") {
				echo "Warning: Line ".($a+$delta+4)." may be faulty (A1).<br>\n";
			}
			$delta++;
		}
		elseif (strpos($lines[$a+$delta+3],"PSP-Element")!==FALSE) {
			list($check8,$project)=colSplit($lines[$a+$delta+3],
				array(18,			14));
			// |     Kostenstelle 103100                                                       |
			if ($check8!="|     PSP-Element") {
				echo "Warning: Line ".($a+$delta+4)." may be faulty (A2).<br>\n";
			}
			$delta++;
		}
		elseif (strpos($lines[$a+$delta+3],"liefern")===FALSE) { // andere Zwischenzeile, deprecated
			$delta++;
		}

		list($check1,$amount_deliver,,$amount_deliver_unit,$price_netto,,$price_netto_currency,$percent,,$check2)=colSplit($lines[$a+$delta+3],
			array(32,					11,		2,9,		10,	1,	4,    8,1,	2)); // +1
		//		|     noch zu liefern                20,000  G            69,60  EUR   100,00 % |
		list($check3,$amount_bill,,$amount_bill_unit,$price_netto,,$price_netto_currency,$percent,,$check4)=colSplit($lines[$a+$delta+4],
			array(32,					11,		2,9,		10,	1,	4,    8,1,	2)); // +1
		//		|     noch zu berechnen              20,000  G            69,60  EUR   100,00 % |
		if ($check1!="|     noch zu liefern" || $check2!="%") {
			echo "Warning: Line ".($a+$delta+4)." may be faulty (B).<br>\n";
		}
		if ($check3!="|     noch zu berechnen" || $check4!="%") {
			echo "Warning: Line ".($a+$delta+5)." may be faulty (C).<br>\n";
		}

		// manchmal ist Bedarfsnummer angegeben
		unset($order_person);
		if (strpos($lines[$a+$delta+5],"Bedarfsnummer")!==FALSE) {
			list($check6,$check7,$order_person)=colSplit($lines[$a+$delta+5],
				array(19,			   2,	10));
			// |     Bedarfsnummer *RINALDI                                                    |
			$order_person=ucwords(strtolower($order_person));
			if ($check6!="|     Bedarfsnummer" || $check7!="*") {
				echo "Warning: Line ".($a+$delta+6)." may be faulty (D).<br>\n";
			}
			$delta++;
		}

		$amount=fixGerNumber($amount);
		$amount_unit=strtolower($amount_unit);

		// Dubletten suchen
		// sap_bestell_nr,sap_stamm_nr,order_date, if available: order_account
		$filter="sap_bestell_nr=".fixStrSQL($sap_bestell_nr)." AND sap_stamm_nr=".fixStrSQL($sap_stamm_nr)." AND order_date=".fixDateSQL($order_date);
		if (!empty($acc_no)) {
			$filter.=" AND order_account=".fixStrSQL($acc_no);
		}
		list($mpi_order)=mysql_select_array(array(
			"table" => "mpi_order",
			"dbs" => -1,
			"filter" => $filter,
			"limit" => 1,
		));

		if (count($mpi_order)) {
			// add missing info



		}
		else {
			echo "<tr><td>".$order_date."</td><td>".$sap_stamm_nr."</td><td>".$molecule_name."</td><td>".$amount." ".$amount_unit."</td><td>".$supplier."</td><td>".$acc_no."</td><td>".$order_person."</td></tr>\n";

			// Eintragen
			$_REQUEST["mpi_order_id"]="";
			$_REQUEST["sap_bestell_nr"]=$sap_bestell_nr;
			$_REQUEST["sap_stamm_nr"]=$sap_stamm_nr;
			$_REQUEST["molecule_name"]=$molecule_name;
			$_REQUEST["supplier"]=$supplier;
			$_REQUEST["order_date"]=getSQLFormatDate(getTimestampFromDate($order_date));
			if (!empty($order_person)) {
				$_REQUEST["order_person"]=$order_person;
			}
			if (!empty($acc_no)) {

				$_REQUEST["order_account"]=$acc_no;
//>>>MST01
                                $_REQUEST["other_db_id"] = getDbIdForCostCentre( $costCentres, $lagerDbIds, $acc_no );
//<<<MST01

			}

			if (!empty($sap_stamm_nr)) { // cas_nr is cleared after each loop
				// get CAS no for MatStammNo
				list($cas_data)=mysql_select_array(array(
					"table" => "mat_stamm_nr_for_mpi_order",
					"dbs" => -1,
					"filter" => "mat_stamm_nr.sap_stamm_nr=".fixStrSQL($sap_stamm_nr),
					"limit" => 1,
				));
				$_REQUEST["cas_nr"]=$cas_data["cas_nr"];
			}

			// Amount
			$list_int_name="mpi_order_item";
			$UID=1;
			$_REQUEST[$list_int_name][]=$UID;
			$_REQUEST["desired_action_".$list_int_name."_".$UID]="add";
			$_REQUEST[$list_int_name."_amount_".$UID]=$amount;
			$_REQUEST[$list_int_name."_amount_unit_".$UID]=$amount_unit;
//<<<MST00
                        $_REQUEST["total_amount"] = $amount;
                        $_REQUEST["amount_unit"] = $amount_unit;
//>>>MST00
			performEdit("mpi_order",-1,$db);
			$_REQUEST=$old_REQUEST;

		}

		$skip=4+$delta; // skip 4 (or 3) lines which have been taken care of
		//~ echo $skip."<br>\n";
	}
	echo "</tbody></table>";

        echo "<br><br>";
        echo "<h1> Bitte prüfen Sie die CAS-Nummern und die Kennzeichnung der Positivliste</h1>";
}

pageHeader();

echo "<title>".s("read_sap_dump")."</title><link href=\"style.css.php\" rel=\"stylesheet\" type=\"text/css\">
</head>
<body>
<form name=\"sapfile\" method=\"post\" enctype=\"multipart/form-data\" action=".fixStr(getSelfRef()).">
<input type=\"file\" name=\"load_sap\" id=\"load_sap\">
<input type=\"submit\">
</form>";
if (count($_FILES["load_sap"])) {
	// print_r($_FILES);
	/*
    [load_molfile] => Array
	(
	    [name] => Toluene.mol
	    [type] => chemical/x-mdl-molfile
	    [tmp_name] => /var/tmp/phpNhjfSD
	    [error] => 0
	    [size] => 719
	)
*/
	if ($_FILES["load_sap"]["error"]==0) {
		$filename=& $_FILES["load_sap"]["tmp_name"];
		$filesize=& $_FILES["load_sap"]["size"];
		// datei öffnen
		$handle = fopen($filename, "rb");
		// größe prüfen
		if ($filesize>0 && filesize($filename)==$filesize) {
			// datei einlesen
			$sapfile=fread($handle,$filesize);
		}
		// datei schließen
		fclose($handle);
		// datei löschen
		@unlink($filename);
		echo $_FILES["load_sap"]["name"]."<br>\n";
		parseSAP($sapfile);
	}
}

echo "</body></html>";


function getDbIdForCostCentre( $costCentres, $dbIds, $account_no ) {

    $ret = 0;   //unknown

    if( $costCentres != null && $dbIds != null && !empty ($account_no) ) {

        $dbname = null;

        //find dbname for given account number
        for( $i=0; $i < count( $costCentres); $i++ ) {

            if( $costCentres[ $i ]["cost_centre"] == $account_no ) {
                $dbname = $costCentres[ $i ]["name"];
                break;
            }
        }

        //find local db id for given dbname
        if( $dbname != null ) {
            for( $i=0; $i < count( $dbIds); $i++ ) {
                if( $dbIds[ $i ]["db_name"] == $dbname ) {
                    $ret = $dbIds[ $i ]["other_db_id"];
                    break;
                }
            }
        }
    }

    return $ret;
}
?>