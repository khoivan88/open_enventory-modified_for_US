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
Lokalisierung: $localizedString[iso-Sprachcode][identifier]ggf[index]
Benutzung mit s(identifier[,index]), die globale Variable $lang wird genutzt
*/
require_once "lib_formatting.php";

$globalString=array(
"copy_short" => " - &copy; 2018 <a href=\"mailto:fr@sciformation.com\">Felix Rudolphi</a>, Lukas Gooßen",
"lambda" => "Lambda",
"rho_bulk" => "&rho;<sub>bulk</sub>",
"licence" => "Copyright 2006-2018 Felix Rudolphi and Lukas Goossen
open enventory is distributed under the terms of the GNU Affero General Public License, see COPYING for details. You can also find the license under <a href=\"http://www.gnu.org/licenses/agpl.txt\" target=\"_blank\">http://www.gnu.org/licenses/agpl.txt</a>

open enventory is a registered trademark of Felix Rudolphi and Lukas Goossen. Usage of the name &quot;open enventory&quot; or the logo requires prior written permission of the trademark holders. 

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
along with open enventory.  If not, see <a href=\"http://www.gnu.org/licenses/\" target=\"_blank\">http://www.gnu.org/licenses/</a>.",
);

function autoLang() {
	global $lang;
	if ($lang=="" || $lang=="-1") {
		$lang=getBrowserLang($_REQUEST["user_lang"]);
		if ($lang=="") {
			$lang=default_language;
		}
		$_SESSION["user_lang"]=$lang;
	}
}

function loadLanguage($langToLoad=null) {
	global $lang,$localizedString,$globalString;
	
	if (is_null($langToLoad)) {
		autoLang();
		$langToLoad=$lang;
	}
	
	if ($langToLoad=="" || $langToLoad=="-1") {
		$langToLoad=default_language;
	}
	
	require_once "lib_language_".$langToLoad.".php";
}

?>
