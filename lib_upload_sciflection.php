<?php

require_once "HTTP/Request2.php";

$key_sort = array("uuid", "@id"); // last ones come first

function jsonSort($a, $b) {
	global $key_sort;

	return array_idx_of($b, $key_sort) - array_idx_of($a, $key_sort);
}

function uploadAssignmentBlock(&$sciflectionCredentials, &$publicationToUpload, $assignmentCollectionName, $assignmentCollectionPk) {
	global $db, $conversion_map;

	if (is_array($publicationToUpload[$assignmentCollectionName])) {
		for ($i = 0; $i < count($publicationToUpload[$assignmentCollectionName]); $i += DEFAULT_BLOCK_SIZE) {
			// tracker variables during serialization process
			$tblpk_id_map = array(); // table_name+pk => 123 (Jackson @id)
			$tblpk_uuid_map = array(); // table_name+pk => UUID
			$uploaded_entities = array(); // table_name => array(pk1,pk2,...)
			$id = 1;

			$json = array();
			$assignment_block = array_slice($publicationToUpload[$assignmentCollectionName], $i, DEFAULT_BLOCK_SIZE);
			for ($j = 0; $j < count($assignment_block); $j++) {
				$json[] = getJsonBranch($uploaded_entities, $tblpk_id_map, $tblpk_uuid_map, $id, $assignmentCollectionName, $assignment_block[$j][$assignmentCollectionPk]);
			}

			// upload JSON
			//error_log(json_encode($json));
			$upload = new HTTP_Request2($sciflectionCredentials["host"] . "/login?useCase=receivePublication.uc&format=gzip&entityClass=" .
					$conversion_map[$assignmentCollectionName]["className"], HTTP_Request2::METHOD_POST);
			$upload->setAuth($sciflectionCredentials["db_user"], $sciflectionCredentials["db_pass"]);
			$upload->setHeader("Content-Type", "application/octet-stream");
			$upload->setConfig("timeout", 240);
			$upload->setBody(gzencode(json_encode($json)));
			$response = $upload->send();

			$http_code = $response->getStatus();
			if ($response->getBody() == "success") { // 2
				// upload BLOB data
				foreach ($uploaded_entities as $table => $pks) { // 1
					if ($conversion_map[$table]["blob_fields"])
						foreach ($pks as $pk) {
							$failed = false;
							foreach ($conversion_map[$table]["blob_fields"] as $oeField => $seField) {
								$sql = "SELECT " . $oeField . " AS bytedata FROM " . $table . " WHERE " . getShortPrimary($table) . "=" . $pk . ";";
								$result_handle = mysqli_query($db, $sql);
								if ($result_handle && mysqli_num_rows($result_handle) == 1) {
									list($bytedata) = mysqli_fetch_array($result_handle, MYSQLI_NUM);
									mysqli_free_result($result_handle);

									$url = $sciflectionCredentials["host"] . "/login?useCase=receivePublication.uc&UUID=" . $tblpk_uuid_map[$table . "+" . $pk] .
											"&entityClass=" . $conversion_map[$table]["className"] .
											"&field=" . $seField;
									/*if ($seField == "anaDataBlob") {
										$url .= "&mustReprocess=true";
									}*/
									
									//error_log($url);
									$upload = new HTTP_Request2($url, HTTP_Request2::METHOD_POST);
									$upload->setAuth($sciflectionCredentials["db_user"], $sciflectionCredentials["db_pass"]);
									$upload->setHeader("Content-Type", "application/octet-stream");
									$upload->setConfig("timeout", 240);
									$upload->setBody($bytedata);
									$response = $upload->send();

									$http_code = $response->getStatus();
									if ($response->getBody() != "success") {
										$failed = true;
										break;
									}
								} else {
									error_log(mysqli_error($db) . ": " . $sql);
								}
							}

							if ($failed) {
								break 2;
							}
						}
				}
			}

			// update http_code
			mysqli_query($db, "UPDATE " . $assignmentCollectionName . " SET http_code=" . fixNull($http_code) .
					" WHERE " . getShortPrimary($assignmentCollectionName) . "=" . $assignmentCollectionPk . ";");
		}
	}
	return true;
}

function getJsonBranch(&$uploaded_entities, &$tblpk_id_map, &$tblpk_uuid_map, &$id, $table, $pk) { // &$json, $json_key, 
	global $db, $db_user, $conversion_map, $tables;

	if (!isset($pk) || !is_numeric($pk)) {
		return null;
	}

	$idKey = $table . "+" . $pk;
	$id_ref = $tblpk_id_map[$idKey];
	if ($id_ref) {
		//$json[$json_key] = $id_ref;
		return $id_ref;
	}

	// handling a new entity
	if (!isset($uploaded_entities[$table])) {
		$uploaded_entities[$table] = array();
	}
	$uploaded_entities[$table][] = $pk;

	$sql = "SELECT ";
	if (!$conversion_map[$table]["field_sql"]) { // cache this
		$conversion_map[$table]["field_sql"] = buildFieldSql($table);
	}
	$sql .= $conversion_map[$table]["field_sql"] . " FROM " . $table . " WHERE " . getShortPrimary($table) . "=" . $pk . ";";
	$result_handle = mysqli_query($db, $sql);
	if ($result_handle && mysqli_num_rows($result_handle) == 1) {
		$row = mysqli_fetch_array($result_handle, MYSQLI_ASSOC);
		mysqli_free_result($result_handle);
		unset($row["pk"]);

		foreach ($row as $key => $value) {
			if (is_null($value)) {
				// check for missing created/changed by values, fill with current user
				if ($key == "creator" || $key == "modifier") {
					$row[$key] = $db_user;
				}
				// check for missing created/changed when values, fill with current date/time
				elseif ($key == "created" || $key == "modified") {
					$row[$key] = time() * 1000;
				} else {
					// replace null values in string fields by ""
					$field_info = &$tables[$table]["fields"][$conversion_map[$table]["reverse_field_names"][$key]];
					if ($field_info && stripos($field_info["type"], "text") !== FALSE) {
						$row[$key] = "";
					}
				}
			}
		}

		//  register @id as first key value pair
		$row["@id"] = $id;
		$tblpk_id_map[$idKey] = $id;
		$id++;

		// load child fields
		if (is_array($conversion_map[$table]["many2one"]))
			foreach ($conversion_map[$table]["many2one"] as $fieldName => $child_table_name) {
				$row[$fieldName] = getJsonBranch($uploaded_entities, $tblpk_id_map, $tblpk_uuid_map, $id, $child_table_name, $row[$fieldName]);
			}
		// load child collections
		if (is_array($conversion_map[$table]["one2many"]))
			foreach ($conversion_map[$table]["one2many"] as $collectionName => $data) {
				$row[$collectionName] = getJsonCollection($uploaded_entities, $tblpk_id_map, $tblpk_uuid_map, $id, $data[0], $data[1], $pk);
			}

		// encode binary as b64
		if ($row["uuid"]) {
			$tblpk_uuid_map[$idKey] = $row["uuid"];
			$row["uuid"] = base64_encode(uuid2bin($row["uuid"]));
		}
		if (is_array($conversion_map[$table]["uuid_fields"]))
			foreach ($conversion_map[$table]["uuid_fields"] as $fieldName) { // additional uuid fields, like for chromaPeak assignments
				$row[$fieldName] = base64_encode(uuid2bin($row[$fieldName]));
			}
		if (is_array($conversion_map[$table]["binary"]))
			foreach ($conversion_map[$table]["binary"] as $fieldName) {
				$row[$fieldName] = base64_encode($row[$fieldName]);
			}

		if (is_array($conversion_map[$table]["convert"]))
			foreach ($conversion_map[$table]["convert"] as $fieldName => $targetFormat) {
				if ($targetFormat == "string") {
					if (is_null($row[$fieldName])) {
						$row[$fieldName] = "";
					}
				}
			}

		uksort($row, "jsonSort");

		//$json[$json_key] = $row;
		return $row;
	} else {
		error_log(mysqli_error($db) . ": " . $sql);
	}
}

function getJsonCollection(&$uploaded_entities, &$tblpk_id_map, &$tblpk_uuid_map, &$id, $table, $fk_col, $fk) {
	global $db;

	$result = array();
	$sql = "SELECT " . getShortPrimary($table) . " AS pk FROM " . $table . " WHERE " . $fk_col . "=" . $fk . ";";
	$result_handle = mysqli_query($db, $sql);
	if ($result_handle) {
		$totalCount = mysqli_num_rows($result_handle);
		for ($a = 0; $a < $totalCount; $a++) {
			$pk_result = mysqli_fetch_array($result_handle, MYSQLI_ASSOC);
			$result[] = getJsonBranch($uploaded_entities, $tblpk_id_map, $tblpk_uuid_map, $id, $table, $pk_result["pk"]);
		}
		mysqli_free_result($result_handle);
	} else {
		error_log(mysqli_error($db) . ": " . $sql);
	}
	return $result;
}

function buildFieldSql($table) {
	global $conversion_map;

	$field_sql = array();
	$conversion_map[$table]["reverse_field_names"] = array_flip($conversion_map[$table]["field_names"]);
	foreach ($conversion_map[$table]["field_names"] as $key => $value) {
		// $table.".".
		$field_sql[] = $key . " AS " . $value;
	}
	return implode(",", $field_sql);
}

$conversion_map = array(
	"data_publication" => array(
		"className" => "ElnPublication",
		"field_names" => array(
			// oe => SE
			"data_publication_id" => "pk",
			"data_publication_uid" => "uuid",
			"publication_name" => "name",
			"publication_license" => "sysLicense",
			"publication_doi" => "doi",
			"publication_text" => "description",
			"publication_confirmed_by" => "confirmedByUsername",
			"UNIX_TIMESTAMP(publication_confirmed_when)*1000" => "confirmationDate",
			"literature_id" => "litCitation",
			"publication_text" => "description",
			"data_publication_created_by" => "creator",
			"UNIX_TIMESTAMP(data_publication_created_when)*1000" => "created",
			"data_publication_changed_by" => "modifier",
			"UNIX_TIMESTAMP(data_publication_changed_when)*1000" => "modified",
		),
		"many2one" => array(
			"litCitation" => "literature",
		),
	/* "one2many" => array(
	  "elnReactionPublicationAssignmentCollection" => array("publication_reaction", "data_publication_id"),
	  "anaDataPublicationAssignmentCollection" => array("publication_analytical_data", "data_publication_id"),
	  ), */
	),
	"publication_reaction" => array(
		"className" => "ElnReactionPublicationAssignment",
		"field_names" => array(
			"publication_reaction_id" => "pk",
			"publication_reaction_uid" => "uuid",
			"data_publication_id" => "elnPublication",
			"reaction_id" => "elnReaction",
			"publication_reaction_text" => "comment",
			"publication_reaction_created_by" => "creator",
			"UNIX_TIMESTAMP(publication_reaction_created_when)*1000" => "created",
			"publication_reaction_changed_by" => "modifier",
			"UNIX_TIMESTAMP(publication_reaction_changed_when)*1000" => "modified",
		),
		"many2one" => array(
			"elnPublication" => "data_publication",
			"elnReaction" => "reaction",
		),
	),
	"publication_analytical_data" => array(
		"className" => "AnaDataPublicationAssignment",
		"field_names" => array(
			"publication_analytical_data_id" => "pk",
			"publication_analytical_data_uid" => "uuid",
			"data_publication_id" => "elnPublication",
			"analytical_data_id" => "anaData",
			"publication_analytical_data_text" => "comment",
			"publication_analytical_data_created_by" => "creator",
			"UNIX_TIMESTAMP(publication_analytical_data_created_when)*1000" => "created",
			"publication_analytical_data_changed_by" => "modifier",
			"UNIX_TIMESTAMP(publication_analytical_data_changed_when)*1000" => "modified",
		),
		"many2one" => array(
			"elnPublication" => "data_publication",
			"anaData" => "analytical_data",
		),
	),
	"lab_journal" => array(
		"field_names" => array(
			"lab_journal_id" => "pk",
			"lab_journal_uid" => "uuid",
			"lab_journal_code" => "code",
			"lab_journal_created_by" => "creator",
			"UNIX_TIMESTAMP(lab_journal_created_when)*1000" => "created",
			"lab_journal_changed_by" => "modifier",
			"UNIX_TIMESTAMP(lab_journal_changed_when)*1000" => "modified",
		),
	),
	"reaction" => array(
		"field_names" => array(
			"reaction_id" => "pk",
			"reaction_uid" => "uuid",
			"nr_in_lab_journal" => "nrInLabJournal",
			"realization_text" => "realizationText",
			"realization_observation" => "observationText",
			"rxnfile_blob" => "rxnfileBlob",
			"reaction_carried_out_by" => "reactionCarriedOutBy",
			"reaction_title" => "reactionTitle",
			"UNIX_TIMESTAMP(reaction_started_when)*1000" => "reactionStartedWhen",
			"ref_amount" => "refAmount", // unified
			"ref_amount_unit" => "refAmountUnit",
			"project_id" => "elnProject",
			"lab_journal_id" => "elnLabNotebook",
			"(SELECT reaction_uid FROM reaction WHERE reaction.reaction_id = reaction.reaction_prototype)" => "reactionPrototype",
			"reaction_created_by" => "creator",
			"UNIX_TIMESTAMP(reaction_created_when)*1000" => "created",
			"reaction_changed_by" => "modifier",
			"UNIX_TIMESTAMP(reaction_changed_when)*1000" => "modified",
		),
		"many2one" => array(
			"elnLabNotebook" => "lab_journal",
			"elnProject" => "project",
		),
		"one2many" => array(
			"elnReactionComponentCollection" => array("reaction_chemical", "reaction_id"),
			"elnReactionPropertyCollection" => array("reaction_property", "reaction_id"),
			"elnReactionCitationCollection" => array("reaction_literature", "reaction_id"),
		),
		"uuid_fields" => array(
			"reactionPrototype",
		),
		"binary" => array(
			"rxnfileBlob",
		),
	),
	"reaction_literature" => array(
		"field_names" => array(
			"reaction_literature_id" => "pk",
			"reaction_id" => "elnReaction",
			"literature_id" => "litCitation",
		),
		"many2one" => array(
			"elnReaction" => "reaction",
			"litCitation" => "literature",
		),
	),
	"reaction_property" => array(
		"field_names" => array(
			"reaction_property_id" => "pk",
			"reaction_id" => "elnReaction",
			"reaction_property_name" => "name",
			"reaction_property_value" => "strValue",
		),
		"many2one" => array(
			"elnReaction" => "reaction",
		),
	),
	"reaction_chemical" => array(
		"field_names" => array(
			"reaction_chemical_id" => "pk",
			"reaction_chemical_uid" => "uuid",
			"nr_in_reaction" => "nrInReaction",
			"standard_name" => "moleculeName",
			"package_name" => "containerName",
			"cas_nr" => "casNr",
			"molfile_blob" => "molfileBlob",
			"emp_formula" => "empFormula",
			"mw" => "mw",
			"density_20" => "density20",
			"role+0" => "rxnRole",
			"stoch_coeff" => "stoichCoeff",
			"m_brutto" => "mass",
			"mass_unit" => "massUnit",
			"rc_amount" => "amount",
			"rc_amount_unit" => "amountUnit",
			"rc_conc" => "concentration",
			"rc_conc_unit" => "concentrationUnit",
			"volume" => "volume",
			"volume_unit" => "volumeUnit",
			"gc_yield" => "instYield",
			"yield" => "isolYield",
			"measured+0" => "measured",
			"colour" => "color",
			"safety_r" => "safetyR",
			"safety_s" => "safetyS",
			"safety_h" => "safetyH",
			"safety_p" => "safetyP",
			"safety_sym" => "safetySym",
			"safety_sym_ghs" => "safetySymGhs",
			"reaction_id" => "elnReaction",
			"(SELECT reaction_uid FROM reaction WHERE reaction.reaction_id = reaction_chemical.from_reaction_id)" => "fromReaction",
			"(SELECT reaction_chemical_uid FROM reaction_chemical WHERE reaction_chemical.reaction_chemical_id = reaction_chemical.from_reaction_chemical_id)" => "fromReactionComponent",
		),
		"many2one" => array(
			"elnReaction" => "reaction",
		),
		"uuid_fields" => array(
			"fromReaction", "fromReactionComponent",
		),
		"binary" => array(
			"molfileBlob",
		),
	),
	"analytical_data" => array(
		"className" => "AnaData",
		"field_names" => array(
			"analytical_data_id" => "pk",
			"analytical_data_uid" => "uuid",
			"fraction_no" => "fraction",
			"analytical_data_blob" => "originalFile", // allows immediate img generation
//			"solvent" => "solvent", // field was never used, is INT UNSIGNED
			"analytical_data_identifier" => "identifier",
			"measured_by" => "measuredBy",
			"analytical_data_interpretation" => "interpretation",
			"analytical_data_comment" => "comment",
			"analytics_type_name" => "typeName",
			"analytics_type_code" => "typeCode",
			"analytics_type_text" => "typeText",
			"analytics_device_name" => "deviceName",
			"analytics_device_driver" => "deviceCode",
			"analytics_type_text" => "deviceText",
			"analytics_method_name" => "methodName",
			"analytics_method_text" => "methodText",
			"(SELECT reaction_uid FROM reaction WHERE reaction.reaction_id = analytical_data.reaction_id)" => "elnReaction",
			"(SELECT reaction_chemical_uid FROM reaction_chemical WHERE reaction_chemical.reaction_chemical_id = analytical_data.reaction_chemical_id)" => "elnReactionComponent",
			"analytical_data_created_by" => "creator",
			"UNIX_TIMESTAMP(analytical_data_created_when)*1000" => "created",
			"analytical_data_changed_by" => "modifier",
			"UNIX_TIMESTAMP(analytical_data_changed_when)*1000" => "modified",
		),
		"blob_fields" => array(
//			"analytical_data_blob" => "anaDataBlob",
			"analytical_data_raw_blob" => "anaDataOriginalBlob",
		),
		"one2many" => array(
//			"anaDataImageCustomCollection" => array("analytical_data_image", "analytical_data_id"),
			"anaChromaPeakCollection" => array("gc_peak", "analytical_data_id"),
		),
		"uuid_fields" => array(
			"elnReaction", "elnReactionComponent",
		),
		"binary" => array(
			"originalFile",
		),
	),
	/* "analytical_data_image" => array( // will be re-built anyway
	  "field_names" => array(
	  "analytical_data_image_id" => "pk",
	  "analytical_data_id" => "anaData",
	  "image_no" => "idx",
	  "image_comment" => "comment",
	  ),
	  "many2one" => array(
	  "anaData" => "analytical_data",
	  ),
	  ), */
	"gc_peak" => array(
		"field_names" => array(
			"gc_peak_id" => "pk",
			"retention_time" => "retentionTime",
			"area_percent" => "areaPercent",
			"gc_peak_comment" => "comment",
			"gc_yield" => "yield",
			"response_factor" => "responseFactor",
			"(SELECT reaction_chemical_uid FROM reaction_chemical WHERE reaction_chemical.reaction_chemical_id = gc_peak.reaction_chemical_id)" => "elnReactionComponent",
			"(SELECT analytical_data_uid FROM analytical_data WHERE analytical_data.analytical_data_id = gc_peak.analytical_data_id)" => "anaData",
		),
//		"many2one" => array(
//			"anaData" => "analytical_data",
//		),
		"uuid_fields" => array(
			"elnReactionComponent",
			"anaData",
		),
	),
	"project" => array(
		"field_names" => array(
			"project_id" => "pk",
			"project_uid" => "uuid",
			"project_name" => "name",
			"project_text" => "text",
			"project_created_by" => "creator",
			"UNIX_TIMESTAMP(project_created_when)*1000" => "created",
			"project_changed_by" => "modifier",
			"UNIX_TIMESTAMP(project_changed_when)*1000" => "modified",
		),
		"one2many" => array(
			"elnProjectCitationCollection" => array("project_literature", "project_id"),
		),
	),
	"project_literature" => array(
		"field_names" => array(
			"project_literature_id" => "pk",
			"project_id" => "elnProject",
			"literature_id" => "litCitation",
		),
		"many2one" => array(
			"elnProject" => "project",
			"litCitation" => "literature",
		),
	),
	"literature" => array(
		"field_names" => array(
			"literature_id" => "pk",
			"literature_uid" => "uuid",
			"literature_year" => "citationYear",
			"literature_volume" => "volumeText",
			"issue" => "issueText",
			"page_low" => "pageLow",
			"page_high" => "pageHigh",
			"doi" => "doi",
			"isbn" => "isbn",
			"keywords" => "keywords",
			"literature_title" => "title",
			"edition" => "edition",
			"publisher" => "publisher",
			"place" => "place",
			"language_code" => "languageCode",
			"literature_type" => "citationType",
			"sci_journal_id" => "litJournal",
			"literature_created_by" => "creator",
			"UNIX_TIMESTAMP(literature_created_when)*1000" => "created",
			"literature_changed_by" => "modifier",
			"UNIX_TIMESTAMP(literature_changed_when)*1000" => "modified",
		),
		"many2one" => array(
			"litJournal" => "sci_journal",
		),
		"one2many" => array(
			"litAuthorCollection" => array("author", "literature_id"),
		),
		"convert" => array(
			"edition" => "string",
		),
	),
	"author" => array(
		"field_names" => array(
			"author_id" => "pk",
			"nr_in_literature" => "nrInCitation",
			"author_last" => "lastName",
			"author_first" => "firstName",
			"literature_id" => "litCitation",
		),
		"many2one" => array(
			"litCitation" => "literature",
		),
	),
	"sci_journal" => array(
		"field_names" => array(
			"sci_journal_id" => "pk",
			"sci_journal_name" => "journalName",
			"sci_journal_abbrev" => "journalAbbrev",
			"sci_journal_created_by" => "creator",
			"UNIX_TIMESTAMP(sci_journal_created_when)*1000" => "created",
			"sci_journal_changed_by" => "modifier",
			"UNIX_TIMESTAMP(sci_journal_changed_when)*1000" => "modified",
		),
	),
);
?>