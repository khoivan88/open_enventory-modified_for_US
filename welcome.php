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
Begrüßungsseite, zeigt abhängig von den permissions mögliche aktionen
*/
require_once "lib_global_funcs.php";
require_once "lib_navigation.php";
pageHeader();
echo <<<END
<link href="style.css.php" rel="stylesheet" type="text/css">
</head>
<body>
<p>
END;
echo getAlignTable(array(),array(),getMessageButton());
echo s("welcome_all");
echo "<div style=\"position:absolute;right:5px;bottom:5px\"><a href=\"http://www.gnu.org/licenses/agpl.txt\" target=\"_blank\"><img src=\"lib/agplv3-88x31.png\" border=\"0\"></a></div></body></html>";
completeDoc();
?>