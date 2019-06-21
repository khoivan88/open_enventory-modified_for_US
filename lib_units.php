<?php

$phys_quantities=array();
$phys_quantities["mass"]=array("defaultUnit" => "kg", "SIUnit" => "kg", 
	"factorUnits" => array(), // for imperial units
	"expUnits" => array(
		"t" => 3, "mt" => 3, "metric ton" => 3,
		"g" => -3, "gram" => -3,
		"mg" => -6, "milligram" => -6,
		"µg" => -9, "microg" => -9, "mycrog" => -9, "microgram" => -9, "mycrogram" => -9,
		"ng" => -12, "nanogram" => -12,
		"pg" => -15, "picogram" => -15
		), // for metric units
	"nonLinearUnits" => array() // for temperature units
	);

$phys_quantities["volume"]=array("defaultUnit" => "l", "SIUnit" => "m3", 
	"factorUnits" => array(), // for imperial units
	"expUnits" => array(
		"m3" => 3, "cbm" => 3, "cubic metre" => 3,
		"ml" => -3, "millilitre" => -3,
		"µl" => -6, "microl" => -6, "mycrol" => -6, "microlitre" => -6, "mycrolitre" => -6,
		"nl" => -9, "nanolitre" => -9,
		"pl" => -12, "picolitre" => -12
		), // for metric units
	"nonLinearUnits" => array() // for temperature units
	);

$phys_quantities["amountOfSustance"]=array("defaultUnit" => "mol", "SIUnit" => "mol",
	"factorUnits" => array(), // for imperial units
	"expUnits" => array(
		"mmol" => -3,
		"umol" => -6,
		"nmol" => -9,
		"pmol" => -12
), // for metric units
	"nonLinearUnits" => array() // for temperature units
	);

$phys_quantities["density"]=array("defaultUnit" => "g/ml", "SIUnit" => "kg/m3",
	"factorUnits" => array(), // for imperial units
	"expUnits" => array(
		"g/cm3" => 0,
		"kg/m3" => 0
		),
	// for metric units
	"nonLinearUnits" => array() // for temperature units
	);

$phys_quantities["molarMass"]=array("defaultUnit" => "g/mol", "SIUnit" => "kg/mol",
	"factorUnits" => array(), // for imperial units
	"expUnits" => array(
		"kg/mol" => 3
		), // for metric units
	"nonLinearUnits" => array() // for temperature units
	);

$phys_quantities["temperature"]=array("defaultUnit" => "°C", "SIUnit" => "K",
	"factorUnits" => array(), // for imperial units
	"expUnits" => array("°C" => 0, "C" => 0), // for metric units
	"nonLinearUnits" => array("K" => "K","°K" => "°K","F" => "F","°F" => "°F") // for temperature units
	);

$phys_quantities["pressure"]=array("defaultUnit" => "bar", "SIUnit" => "Pa",
	"factorUnits" => array(), // for imperial units
	"expUnits" => array(
		"Pa" => -5,
		"MPa" => 1,
		"kPa" => -2
		), // for metric units
	"nonLinearUnits" => array() // for temperature units
	);

$phys_quantities["extinctionCoefficient"]=array("defaultUnit" => "l/(mol*cm)", "SIUnit" => "m2/mol",
	"factorUnits" => array(), // for imperial units
	"expUnits" => array(
		"m2/mol" => 3
		), // for metric units
	"nonLinearUnits" => array() // for temperature units
	);

$phys_quantities["molarity"]=array("defaultUnit" => "mol/l", "SIUnit" => "mol/m3",
	"factorUnits" => array(), // for imperial units
	"expUnits" => array(
		"mol/m3" => -3
		), // for metric units
	"nonLinearUnits" => array() // for temperature units
	);

$phys_quantities["molality"]=array("defaultUnit" => "mol/kg", "SIUnit" => "mol/kg",
	"factorUnits" => array(), // for imperial units
	"expUnits" => array(), // for metric units
	"nonLinearUnits" => array() // for temperature units
	);

$phys_quantities["massConcentration"]=array("defaultUnit" => "%", "SIUnit" => "%",
	"factorUnits" => array(), // for imperial units
	"expUnits" => array(), // for metric units
	"nonLinearUnits" => array() // for temperature units
	);

$phys_quantities["volumeConcentration"]=array("defaultUnit" => "Vol.-%", "SIUnit" => "Vol.-%",
	"factorUnits" => array(), // for imperial units
	"expUnits" => array(), // for metric units
	"nonLinearUnits" => array() // for temperature units
	);

$phys_quantities["amountOfSustanceConcentration"]=array("defaultUnit" => "mol-%", "SIUnit" => "mol-%",
	"factorUnits" => array(), // for imperial units
	"expUnits" => array(), // for metric units
	"nonLinearUnits" => array() // for temperature units
        );

$phys_quantities["ld50"]=array("defaultUnit" => "mg / kg", "SIUnit" => "kg / kg", 
	"factorUnits" => array(), // for imperial units
	"expUnits" => array(
		"g / kg" => -3,
		"mg / kg" => -6,
		"µg / kg" => -9,
		"ng / kg" => -12,
		"pg / kg" => -15
		), // for metric units
	"nonLinearUnits" => array() // for temperature units
	);
$phys_quantities["refractionIndex"]= array("defaultUnit" => "", "SIUnit" => "",
        "factorUnits" => array(),
        "expUnits" => array(),
        "nonLinearUnits" => array()
);

// weitere physikalische Größen: opticalRotation, refractionIndex (ist dimensionslos...), ppm
// toxikologische Größen: LD50, LC50
$phys_quantities["lc50"]=&$phys_quantities["molarity"];

function getValueInStdUnit($type,$value,$unit) {
	global $phys_quantities;
	// Prüfen, ob Einheit zu phys. Größe paßt, in Standardgröße umrechnen und Zahl mit Standardeinheit (array("value" => , "unit" => , "SI" =>)) zurückgeben. In der DB wird dann die Zahl und die Standardeinheit (zur Kontrolle) gespeichert
	$value+=0.0;
	if (!is_numeric($value)) { // not a numeric value
		return array();
	}
	$typeInformation=& $phys_quantities[$type];
	if (count($typeInformation)==0) { // type not defined
		return array();
	}
	if (isset($typeInformation["expUnits"][$unit])) { // nach expUnits suchen
		$value*=pow(10,$typeInformation["expUnits"][$unit]);
		$unit=$typeInformation["defaultUnit"];
	}
	elseif (isset($typeInformation["factorUnits"][$unit])) { // nach factorUnits suchen
		$value*=$typeInformation["factorUnits"][$unit];
		$unit=$typeInformation["defaultUnit"];
	}
	elseif (in_array($unit,$typeInformation["nonLinearUnits"])) { // nach nonLinearUnits suchen (Funktionen hier eingebaut)
		switch ($type) {
		case "temperature":
			switch ($unit) {
			case "K":
			case "°K":
				$value-=273.15;
				$unit=$typeInformation["defaultUnit"];
			break;
			case "F":
			case "°F":
				$value=($value-32)/1.8;
				$unit=$typeInformation["defaultUnit"];
			break;
			}
		break;
		}
	}
	return array("value" => $value, "unit" => $unit);
}

function getAllUnits(){
    global $phys_quantities;
    $UnitArray = array();
    foreach ($phys_quantities as $key => $value) {
        $UnitArray[]=$key;
        foreach ($value["factorUnits"] as $unitkey => $unit) {
            $UnitArray[$key][]=$unitkey;
        }
        foreach ($value["expUnits"] as $unitkey => $unit) {
            $UnitArray[$key][]=$unitkey;
        }
        foreach ($value["nonLinearUnits"] as $unitkey => $unit) {
            $UnitArray[$key][]=$unitkey;
        }        
    }
    return json_encode($UnitArray);
}
?>