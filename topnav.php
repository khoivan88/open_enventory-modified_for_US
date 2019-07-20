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
Menü oben
*/
require_once "lib_global_funcs.php";
require_once "lib_global_settings.php";
require_once "lib_simple_forms.php";
require_once "lib_navigation.php";
pageHeader();
$color="blue";

// Khoi: add bootstrap 4
if ($g_settings["use_bootstrap4"]) {
	echo '
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<!-- Bootstrap CSS CDN-->
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
		<!-- Bootstrap CSS local fallback -->
		<script>
			var test = document.createElement("div")
			test.className = "hidden d-none"

			document.head.appendChild(test)
			var cssLoaded = window.getComputedStyle(test).display === "none"
			document.head.removeChild(test)

			if (!cssLoaded) {
				var link = document.createElement("link");

				link.type = "text/css";
				link.rel = "stylesheet";
				link.href = "lib/bootstrap.min.css";

				document.head.appendChild(link);
			}
		</script>
	';
	
	echo loadJS(array("sidenav.js"),"lib/");

	echo style."
		body {
			background-color: ".defBgColor.";
		}
		
		a {
			line-height: 100%;
			text-align: left;
			text-decoration: none;
		}
		
		a:link,
		a:visited,
		a:active {}
		
		a:hover,
		a:focus {
			font-weight: bold
		}
		
		a.graybg {
			font-weight: bold;
			color: #132F90
		}
		
		a.graybg:hover {
			color: #FF0000
		}
		
		#nav {
			table-layout: fixed;
			position: absolute;
			left: 0px;
			width: 100%;
			vertical-align: middle;
			border-collapse: collapse
		}
		
		#header {
			position: absolute;
			left: 0px;
			top: 5px;
			width: 100%;
			vertical-align: middle;
			border-collapse: collapse;
			display: none;
		}
		
		td {
			// font-size: 12pt;
			line-height: 100%;
			// font-family: Arial;
			// font-family: 'Cardo', serif;
			// font-family: 'Work Sans', sans-serif;
			// font-family: 'Crimson Text', serif;
			// font-family: 'Oswald', sans-serif;
			// font-family: 'Lato', sans-serif;
			// font-family: 'Lora', serif;
			font-family: 'Montserrat', sans-serif;
			font-weight: 400;
			// font-family: 'Prata', serif;
			// font-family: 'Quicksand', sans-serif;
			// font-family: 'Playfair Display', serif;
			// font-weight: 700;
			// padding: 0px;
			// margin: 0px;
			// height: 21px
		}
		
		td.path {
			text-align: center;
			position: absolute;
			left: 0px;
			width: 100%;
			height: 16px;
			font-weight: bold;
			color: black;
			font-size: 9pt;
		}
				
		#middle,
		#middle a {
			border-width: 0px;
			text-align: center;
			font-size: 11pt;
			font-family: Arial;
			color: #132F90
		}
		
		td.spacer {
			width: 8px
		}
		
		td.link {
			text-align: right;
		}
		
		td.info {
			font-size: 10pt;
			line-height: 100%;
			color: #132F90;
			padding-left: 10px
		}
		
		#selectInfo {
			font-size: 10pt;
			color: #132F90;
			vertical-align: top;
		}
		
		a.btn_logout:hover,
		a.btn_logout:focus {
			font-weight: bolder
		}
		
		#expand-icon {
			display: none;
		}

		"._style;

		echo "
		</head>
		<body>
			<table id=\"header\" width=\"100%\"><tr><td id=\"middle\"><img src=\"lib/open_env_logo.png\" border=\"0\" height=\"58\" width=\"454\"><br>".
			s("list_of_chemicals_title"). s("copy_short"). "</td><td style=\"width:200px\" align=\"right\">". getImageLink($g_settings["links_in_topnav"]["fb_logo"]). 
			"</td></tr></table>".
			
			// Khoi: add bootstrap container fluid for class nav and path
			"<div class=\"mx-auto px-0\" >
				<nav class=\"navbar sticky-top navbar-expand-md navbar-dark bg-dark\">
					<button class=\"btn btn-dark btn-sm\" id=\"expand-icon\" type=\"button\" data-toggle=\"collapse\" data-target=\"\" aria-controls=\"\" aria-expanded=\"false\" aria-label=\"Toggle search\" onclick=\"Javascript:switchSideframe(true)\"".getTooltip("expand").">
						<span id=\"collapse-icon\" class=\"fa fa-2x fa-angle-double-right\"></span>
					</button>
					<button class=\"navbar-toggler\" type=\"button\" data-toggle=\"collapse\" data-target=\"#navbarSupportedContent\" aria-controls=\"navbarSupportedContent\" aria-expanded=\"false\" aria-label=\"Toggle navigation\">
						<span class=\"navbar-toggler-icon\"></span>
					</button>
			
					  <div class=\"collapse navbar-collapse\" id=\"navbarSupportedContent\">
						<ul class=\"navbar-nav mr-auto col-8 nav-fill w-100\">";
		
		showTopLinkBootstrap(array(
			"url"=> "sidenav.php?desired_action=search&table=chemical_storage&".getSelfRef(array("~script~", "table")),
			"text"=> s("search_menu"), 
			"target"=> "sidenav",
			"class" => "active "));
		// showSpacer();
		
		if ($permissions & (_lj_read+_lj_read_all)) {
			showTopLinkBootstrap(array(
				"url"=> "lj_main.php?".getSelfRef(array("~script~", "ref_cache_id")), 
				"text"=> s("change_to_lj_menu"), 
				"width"=> "230", 
				"target"=> "_top"));
		}
		else {
			showTopLinkBootstrap(array());
		}
				
		//~ if ($permissions & _storage_modify+_chemical_create+_chemical_edit+_chemical_edit_own+_chemical_borrow+_chemical_inventarise+_lj_admin+_lj_project+_lj_edit+_lj_edit_own) { // Einstellungen-Menü, dort auch Password-Änderung
		showTopLinkBootstrap(array(
			"url"=> "sidenav.php?desired_action=settings&".getSelfRef(array("~script~")), 
			"text"=> s("settings_menu"), 
			"target"=> "sidenav"));
		//~ }
		//~ else { // Password-Änderung direkt oben im Menü
		//~ showTopLink(array("url" => "change_pw.php?".getSelfRef(array("~script~")), "text" => s("change_pw"), "target" => "\"mainpage\""));
		//~ }
			
		// Bestellsystem
		if ($permissions & (_order_accept + _order_approve + _admin)) {
			switch ($g_settings["order_system"]) {
				case "mpi_kofo":
				showTopLinkBootstrap(array(
					"url"=> "sidenav.php?desired_action=mpi_order&".getSelfRef(array("~script~", "table")), 
					"text"=> s("order_system"), 
					"target"=> "sidenav"));
				break;
				default:
				showTopLinkBootstrap(array(
					"url"=> "sidenav.php?desired_action=order&".getSelfRef(array("~script~", "table")), 
					"text"=> s("order_system"), 
					"target"=> "sidenav"));
			}
		}
		else {
			showTopLinkBootstrap(array());
		}
		
		echo '</ul><ul class="navbar-nav ml-auto">';
		showTopLinkBootstrap(array(
			"class"=> "btn_logout", 
			"url"=> "index.php?desired_action=logout&".getSelfRef(array("~script~")), 
			"text"=> s("logout"), 
			"target"=> "_top", ));
		echo '</ul></div></nav></div>';
		
		echo "<div class=\"container-fluid\">
				<table class=\"table table-sm table-borderless\">
					<tbody>
						<tr>
							<td class=\"info\" >".s("you_are_logged_in_as")." <b>".$db_user."</b> ".s("you_are_logged_in_on")." <b>".$db_server."/".$db_name."</b>.</td>\n";
		
		showTopLink(array(
			"url"=> "list.php?table=chemical_storage&query=&filter_disabled=1&selected_only=1&per_page=-1&buttons=print_labels&".getSelfRef(array("~script~")), 
			"text"=> $selected_text, 
			"id"=> "selectInfo", 
			"target"=> "mainpage"));
		
		// Show text for Extra databases access:
		echo "</tr><tr><td class=\"path\" colspan=\"2\" style=\"text-align:center\">".s("more_databases").": ";
		
		$other_db_names=array();
		$disabled_db_names=array();
		
		for ($a=0; $a<count($other_db_data); $a++) {
			// Anzeige der verfügbaren Fremddatenbanken
			if (in_array($other_db_data[$a]["other_db_id"], $_SESSION["other_db_disabled"])) {
				$disabled_db_names[]=$other_db_data[$a]["db_beauty_name"];
			}
			else {
				$other_db_names[]=$other_db_data[$a]["db_beauty_name"];
			}
		}
		
		if (count($other_db_names)) {
			echo join(",", $other_db_names);
		}
		else {
			echo s("no_databases");
		}
		
		if (count($disabled_db_names)) {
			echo s("disabled_databases").": ".join(",", $disabled_db_names);
		}
		
		echo "</td></tr></tbody></table>";    // End bootstrap
		echo "</div>";
		echo script.'
			var numberSelected='.(getSelectionCount("chemical_storage")+0).";
				function updateNumberSelected() {
					if (numberSelected>0) {
						document.getElementById(\"selectInfo\").innerHTML = \"".s("selected").": \"+numberSelected;
					}
					else {
						document.getElementById(\"selectInfo\").innerHTML = ".fixStr(s("nothing_selected")).";
					}
				}
				function changeTotalSelect(changeBy) {
					numberSelected+=changeBy;
					updateNumberSelected()
				}
				function resetTotalSelect() {
					top.comm.location.href=\"manageSelect.php?desired_action=reset&table=chemical_storage\";
					numberSelected=0;
					updateNumberSelected()
				}
				updateNumberSelected();
			"._script;
			
		// Khoi: for Bootstrap 4, add at the end, right before </body>
		echo '
			<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
			<!-- Khoi: set jQuery no conflict mode required below script for OE javascript to work -->
			<script>
			$.noConflict();
			jQuery(document).ready(function($){
				$(\'[data-toggle="tooltip"]\').tooltip();
			});
			</script>
			<!-- jQuery local fallback -->
			<script>window.jQuery || document.write(\'<script src="lib/jquery-3.4.1.min.js"><\/script>\')</script>
		';	
		echo '
			<!-- Bootstrap JS and popper.js CDN -->
			<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
			<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
			<!-- Bootstrap JS local fallback -->
			<script>if(typeof($.fn.modal) === "undefined") {document.write(\'<script src="lib/bootstrap.bundle.min.js"><\/script>\')}</script>
		';
}
// without Bootstrap4
else {
	echo style."
		body {font-family:Arial,Verdana,Helvetica,sans-serif;background-image:url(lib/top_blue.png)}
		a {font-size:9pt;line-height:100%;color:#ffffff;text-align:center;text-decoration:none}
		a:link,a:visited,a:active {}
		a:hover,a:focus {font-weight:bold}
		a.graybg {font-weight:bold;color:#132F90}
		a.graybg:hover {color:#FF0000}
		#nav {table-layout:fixed;position:absolute;left:0px;top:82px;width:100%;vertical-align:middle;border-collapse:collapse}
		#header {position:absolute;left:0px;top:5px;width:100%;vertical-align:middle;border-collapse:collapse}
		#path {text-align:center;position:absolute;left:0px;top:127px;width:100%;height:16px;font-weight:bold;color:black;font-size:9pt;line-height:100%}
		td {font-size:9pt;line-height:100%;font-family:Arial;padding:0px;margin:0px;height:20px }
		#middle, #middle a {border-width:0px;text-align:center;font-size:11pt;font-family:Arial;color:#132F90 }
		td.spacer {width:8px}
		td.link {text-align:center}
		td.info {font-size:9pt;line-height:100%;color:#ffffff;padding-left:10px}
		a.btn_logout { color:red;font-weight:bold }
		a.btn_logout:hover,a.btn_logout:focus { font-weight:bolder }"
		._style."
		</head>
		<body>
		<table id=\"header\" width=\"100%\"><tr><td id=\"middle\">
		<img src=\"lib/open_env_logo.png\" border=\"0\" height=\"58\" width=\"454\"><br>".
		s("list_of_chemicals_title").
		s("copy_short").
		"</td><td style=\"width:200px\">".
		getImageLink($g_settings["links_in_topnav"]["fb_logo"]).
		"</td></tr></table><table id=\"nav\" cellspacing=\"0\" cellpadding=\"0\"><tr>";

	showTopLink(array("url" => "sidenav.php?desired_action=search&table=chemical_storage&".getSelfRef(array("~script~","table")), "text" => s("search_menu"), "target" => "sidenav"));
	showSpacer();

	if ($permissions & (_lj_read+_lj_read_all)) {
		showTopLink(array("url" => "lj_main.php?".getSelfRef(array("~script~","ref_cache_id")), "text" => s("change_to_lj_menu"), "width" => "230", "target" => "_top"));
	}
	else {
		showTopLink(array());
	}

	showSpacer();

	//~ if ($permissions & _storage_modify+_chemical_create+_chemical_edit+_chemical_edit_own+_chemical_borrow+_chemical_inventarise+_lj_admin+_lj_project+_lj_edit+_lj_edit_own) { // Einstellungen-Menü, dort auch Password-Änderung
	showTopLink(array("url" => "sidenav.php?desired_action=settings&".getSelfRef(array("~script~")), "text" => s("settings_menu"), "target" => "sidenav"));
	//~ }
	//~ else { // Password-Änderung direkt oben im Menü
		//~ showTopLink(array("url" => "change_pw.php?".getSelfRef(array("~script~")), "text" => s("change_pw"), "target" => "\"mainpage\""));
	//~ }

	showSpacer();

	showTopLink(array("class" => "btn_logout", "url" => "index.php?desired_action=logout&".getSelfRef(array("~script~")), "text" => s("logout"), "target" => "_top", ));

	echo "</tr><tr>
	<td class=\"info\" colspan=\"3\">".s("you_are_logged_in_as")." <b>".$db_user."</b> ".s("you_are_logged_in_on")." <b>".$db_server."/".$db_name."</b>.</td>\n";

	showSpacer();

	// Bestellsystem
	if ($permissions & (_order_accept + _order_approve + _admin)) {
		switch ($g_settings["order_system"]) {
		case "mpi_kofo":
			showTopLink(array("url" => "sidenav.php?desired_action=mpi_order&".getSelfRef(array("~script~","table")), "text" => s("order_system"), "target" => "sidenav"));
		break;
		default:
			showTopLink(array("url" => "sidenav.php?desired_action=order&".getSelfRef(array("~script~","table")), "text" => s("order_system"), "target" => "sidenav"));
		}
	}
	else {
		showTopLink(array());
	}

	showSpacer();

	showTopLink(array("url" => "list.php?table=chemical_storage&query=&filter_disabled=1&selected_only=1&per_page=-1&buttons=print_labels&".getSelfRef(array("~script~")), "text" => $selected_text, "id" => "selectInfo", "target" => "mainpage"));

	echo "</tr></table><div id=\"path\">".s("more_databases").": ";

	$other_db_names=array();
	$disabled_db_names=array();
	for ($a=0;$a<count($other_db_data);$a++) { // Anzeige der verfügbaren Fremddatenbanken
		if (in_array($other_db_data[$a]["other_db_id"],$_SESSION["other_db_disabled"])) {
			$disabled_db_names[]=$other_db_data[$a]["db_beauty_name"];
		}
		else {
			$other_db_names[]=$other_db_data[$a]["db_beauty_name"];
		}
	}

	if (count($other_db_names)) {
		echo join(",",$other_db_names);
	}
	else {
		echo s("no_databases");
	}
	if (count($disabled_db_names)) {
		echo s("disabled_databases").": ".join(",",$disabled_db_names);
	}

	echo "</div>".
		script."
		var numberSelected=".intval(getSelectionCount("chemical_storage")).";
		function updateNumberSelected() {
			if (numberSelected>0) {
				setiHTML(\"selectInfo\",\"".s("selected").": \"+numberSelected);
			}
			else {
				setiHTML(\"selectInfo\",".fixStr(s("nothing_selected")).");
			}
		}
		function changeTotalSelect(changeBy) {
			numberSelected+=changeBy;
			updateNumberSelected()
		}
		function resetTotalSelect() {
			top.comm.location.href=\"manageSelect.php?desired_action=reset&table=chemical_storage\";
			numberSelected=0;
			updateNumberSelected()
		}
		updateNumberSelected();
		"._script;
}

// Khoi: add event listener for expand-icon and collapse-icon
echo '
	<script>
		// add listening event for sidenav (where collapse-icon is
		window.addEventListener("resize", function() { 
			// Get the width of the sidenav to see if it is open or hide
			var sidenavWidth = top.$("sidenav").scrollWidth;

			if (sidenavWidth == 0) {
				document.getElementById("expand-icon").style.display = "block";
				document.getElementById("expand-icon").style.padding = "0px 5px 0px 5px";
				document.getElementById("expand-icon").style.marginRight = "10px";
			}
			else if (sidenavWidth > 0) {
				document.getElementById("expand-icon").style.display = "none";
			}
		});
	</script>
';

echo "</body></html>";

completeDoc();
?>