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


// use LIKE BINARY for type!!
$GLOBALS["default_table_data"]["class"]=array(
array("name" => "FP", "type" => "T"), 
array("name" => "mp", "type" => "T"), 
array("name" => "bp", "type" => "T"), 
array("name" => "Autoign_temp", "type" => "T"), 
array("name" => "extinguishant", "type" => "Text"), 
array("name" => "VbF", "type" => "Text"), 
array("name" => "Sol_text", "type" => "Text"), 
array("name" => "UN_No", "type" => "Text"), 
array("name" => "EG_No", "type" => "Text"), 
array("name" => "EG_Idx_No", "type" => "Text"), 

array("name" => "adr", "type" => "Text"), 
array("name" => "imdg", "type" => "Text"), 
array("name" => "iata", "type" => "Text"), 
array("name" => "packing_group", "type" => "Text"), 
array("name" => "antidot", "type" => "Text"), 
array("name" => "substitutes", "type" => "Text"), 
array("name" => "risk_assessment", "type" => "Text"), 

array("name" => "Sol_water", "type" => "density"), 
array("name" => "Sat_conc_air", "type" => "density"), 
array("name" => "MAK", "type" => "density"), 
array("name" => "MAK_vol", "type" => "v/v"), 
array("name" => "TRK", "type" => "density"), 
array("name" => "TRK_vol", "type" => "v/v"), 
array("name" => "Ex_limits", "type" => "v/v"), 
array("name" => "Vap_press", "type" => "p"), 
array("name" => "Kin_visc", "type" => "kin_visc"), 
array("name" => "LD50_or", "type" => "m/m"), 
array("name" => "LD50_derm", "type" => "m/m"), 
array("name" => "IC50", "type" => "c"), 

array("name" => "rotation_20", "type" => "ang"), 
);

// use LIKE BINARY for type!!
$GLOBALS["default_table_data"]["units"]=array(
array("type" => "m", "name" => "t", "factor" => 1e6, "disabled" => 1, ),
array("type" => "m", "name" => "kg", "factor" => 1e3, ),
array("type" => "m", "name" => "g", "factor" => 1, "standard" => true, ),
array("type" => "m", "name" => "mg", "factor" => 1e-3, ),
array("type" => "m", "name" => "µg", "factor" => 1e-6, ),

array("type" => "v", "name" => "m³", "factor" => 1e6, "disabled" => 1, ),
array("type" => "v", "name" => "l", "factor" => 1e3, ),
array("type" => "v", "name" => "ml", "factor" => 1, "standard" => true, ),
array("type" => "v", "name" => "µl", "factor" => 1e-3, ),

array("type" => "n", "name" => "mol", "factor" => 1, "standard" => true, ),
array("type" => "n", "name" => "mmol", "factor" => 1e-3, ),
array("type" => "n", "name" => "µmol", "factor" => 1e-6, ),

array("type" => "c", "name" => "mol/l", "factor" => 1e-3, "standard" => true, ),
array("type" => "c", "name" => "mmol/l", "factor" => 1e-6, ),
array("type" => "c", "name" => "µmol/l", "factor" => 1e-9, ),
array("type" => "c", "name" => "nmol/l", "factor" => 1e-12, ),

array("type" => "molal", "name" => "mol/g", "factor" => 1, "standard" => true, ),
array("type" => "molal", "name" => "mmol/g", "factor" => 1e-3, ),
array("type" => "molal", "name" => "mol/kg", "factor" => 1e-3, ),
array("type" => "molal", "name" => "µmol/g", "factor" => 1e-6, ),
array("type" => "molal", "name" => "nmol/g", "factor" => 1e-9, ),

array("type" => "d", "name" => "g/ml", "factor" => 1, "standard" => true, ),
array("type" => "d", "name" => "kg/l", "factor" => 1, ),

array("type" => "mw", "name" => "g/mol", "factor" => 1, "standard" => true, ),

array("type" => "p", "name" => "bar", "factor" => 1, "standard" => true, ),
array("type" => "p", "name" => "mbar", "factor" => 1e-3, ),
array("type" => "p", "name" => "Pa", "factor" => 1e-5, ),
array("type" => "p", "name" => "mmHg", "factor" => 1.3332e-3, ),
array("type" => "p", "name" => "torr", "factor" => 1.3332e-3, ),
array("type" => "p", "name" => "hPa", "factor" => 1e-3, ), // new

array("type" => "T", "name" => "°C", "factor" => 1, "standard" => true, ),

array("type" => "kin_visc", "name" => "m2/s", "factor" => 1, "standard" => true, ),

array("type" => "m/m", "name" => "%", "factor" => 1e-2, "standard" => true, ),
array("type" => "m/m", "name" => "mg/kg", "factor" => 1e-3, ),
array("type" => "m/m", "name" => "ppm", "factor" => 1e-6, ),

array("type" => "v/v", "name" => "Vol.-%", "factor" => 1e-2, "standard" => true, ),
array("type" => "v/v", "name" => "ml/m3", "factor" => 1e-6, ),

array("type" => "density", "name" => "g/ml", "factor" => 1, "standard" => true, ),
array("type" => "density", "name" => "g/l", "factor" => 1e-3, ),
array("type" => "density", "name" => "mg/m3", "factor" => 1e-9, ),

array("type" => "ang", "name" => "°", "factor" => 1, "standard" => true, ),

);

$GLOBALS["default_table_data"]["sci_journal"]=array(
array("name" => "Angewandte Chemie", "abbrev" => "Angew. Chem.", "driver" => "vch"),
array("name" => "Angewandte Chemie International Edition", "abbrev" => "Angew. Chem. Int. Ed.", "driver" => "vch"),
array("name" => "Chemistry - A European Journal", "abbrev" => "Chem. Eur. J.", "driver" => "vch"),

array("name" => "Journal of the American Chemical Society", "abbrev" => "J. Am. Chem. Soc.", "driver" => "acs"),
array("name" => "Organometallics", "abbrev" => "Organometallics", "driver" => "acs"),
array("name" => "Journal of Organic Chemistry", "abbrev" => "J. Org. Chem.", "driver" => "acs"),

array("name" => "Dalton Transactions", "abbrev" => "Dalton Trans.", "driver" => "rcs"),

array("name" => "Tetrahedron", "abbrev" => "Tetrahedron", "driver" => "elsevier"),
);

// change from generic to lowercase_codes, fallback to generic if driver is then not found
$GLOBALS["default_table_data"]["analytics_type"]=array(
array("name" => "NMR", "code" => "nmr"), 
array("name" => "GC", "code" => "gc"), 
array("name" => "GC-MS", "code" => "gc-ms"), 
array("name" => "CHN", "code" => "generic"), 
array("name" => "HPLC", "code" => "hplc"), 
array("name" => "HPLC-MS", "code" => "hplc-ms"), 
array("name" => "MPLC", "code" => "generic"), 
array("name" => "IR", "code" => "ir"), 
array("name" => "SEM", "code" => "generic"), 
array("name" => "STM", "code" => "generic"), 
array("name" => "TEM", "code" => "generic"), 
array("name" => "XRD", "code" => "generic"), // change to xray_struc later
array("name" => "TLC", "code" => "generic"), 
array("name" => "MS", "code" => "ms"), 
);

?>