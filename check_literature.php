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
require_once "lib_literature.php";

pageHeader();

echo <<<END
<link href="style.css.php" rel="stylesheet" type="text/css">
</head>
<body>
END;

// simple DOI
$simple_doi_data=getDataForDOI("10.1002/ange.200705127");
if (!count($simple_doi_data)) {
	echo "Problem with simple DOI<br/>";
}

// DOI with < >
$doi_with_brackets_a_data=getDataForDOI("10.1002/1521-3757(20020402)114:7<1285::AID-ANGE1285>3.0.CO;2-Y");
if (!count($doi_with_brackets_a_data)) {
	echo "Problem with DOI having brackets (not encoded)<br/>";
}
$doi_with_brackets_b_data=getDataForDOI("10.1002/1521-3757(20020402)114:7%3C1285::AID-ANGE1285%3E3.0.CO;2-Y");
if (!count($doi_with_brackets_b_data)) {
	echo "Problem with DOI having brackets (encoded)<br/>";
}

// DOI-URL
$doi_url_data=getDataForDOI("http://dx.doi.org/10.1002/adsc.200800508");
if (!count($doi_url_data)) {
	echo "Problem with DOI URL<br/>";
}

// DOI-URL with < >
$doi_with_brackets_c_data=getDataForDOI("http://dx.doi.org/10.1002/1521-3757(20020402)114:7%3C1285::AID-ANGE1285%3E3.0.CO;2-Y");
if (!count($doi_with_brackets_c_data)) {
	echo "Problem with DOI URL having brackets (encoded)<br/>";
}

// other URL
$other_url_data=getDataForDOI("http://onlinelibrary.wiley.com/doi/10.1002/adsc.201000798/abstract");
if (!count($other_url_data)) {
	echo "Problem with abstract URL<br/>";
}

echo "<h2>".s("checks_complete").<<<END
</h2>
</body>
</html>
END;

completeDoc();

?>