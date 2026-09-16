<?php
/* function to display edit_mol_form, edit_cheminstor_form, edit_storage, edit_institution
enthält funktionen zur Anzeige von Eingabe-/Bearbeitungsformularen (showMolEditForm, showCheminstorEditForm, showStorageEditForm, ...), parametergesteuerte Eingabe-/Anzeigefelder (showInput, showCheck,...) mit automatisch erzeugten Javascript-Set-Funktionen (zum Setzen aller Werte per Skript), asnychrone Auswahlfelder (die ggf. die passenden Werte per JS setzen), Dropdown-Feldern für Seitenwahl, Treffer pro Seite, Sortierlinks in Tabellenspalten
*/
require_once "lib_language.php";
require_once "lib_constants.php";
require_once "lib_form_elements.php";

?>