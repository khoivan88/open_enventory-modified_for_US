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

$tables["literature"]=array(
	"readPerm" => _lj_read+_lj_read_all, 
	"writePerm" => _lj_admin+_lj_project+_lj_edit+_lj_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"sci_journal" => array("condition" => "literature.sci_journal_id=sci_journal.sci_journal_id", ), 
		"project_literature" => array("condition" => "literature.literature_id=project_literature.literature_id", ), 
		"reaction_literature" => array("condition" => "literature.literature_id=reaction_literature.literature_id", ), 
		"chemical_storage_literature" => array("condition" => "literature.literature_id=chemical_storage_literature.literature_id", ), 
		"molecule_literature" => array("condition" => "literature.literature_id=molecule_literature.literature_id", ), 
		"reaction" => array("condition" => "reaction_literature.reaction_id=reaction.reaction_id", ), 
	),
	
	"remoteFilter" => $literatureFilter, 
	"defaultSecret" => true, 
	"merge" => array("nameField" => "sci_journal_name"), 
	"recordCreationChange" => true, 
	"fields" => array(
		"sci_journal_id" => array("type" => "INT UNSIGNED", "fk" => "sci_journal", ),
		"literature_year" => array("type" => "SMALLINT UNSIGNED", "search" => "auto"),
		"literature_volume" => array("type" => "SMALLINT UNSIGNED", "search" => "auto"),
		"issue" => array("type" => "SMALLINT UNSIGNED", "search" => "auto"),
		"page_low" => array("type" => "SMALLINT UNSIGNED"),
		"page_high" => array("type" => "SMALLINT UNSIGNED", "search" => "range", "low_name" => "page_low"),
		// "authors" => array("type" => "TEXT", "search" => "auto"),
		"literature_blob" => array("type" => "MEDIUMBLOB", "flags" => FIELD_IMAGE, ), 
		"literature_blob_fulltext" => array("type" => "TEXT", "flags" => FIELD_FULLTEXT, ), // too long fulltext will be cut away
		"literature_mime" => array("type" => "TINYTEXT"), 
		"literature_graphics_blob" => array("type" => "MEDIUMBLOB", "flags" => FIELD_IMAGE, ),
		"literature_graphics_type" => array("type" => "TINYTEXT"), // mime
		"doi" => array("type" => "TINYTEXT", "search" => "auto"),
		"isbn" => array("type" => "TINYTEXT", "search" => "auto"),
		"literature_title" => array("type" => "TEXT", "search" => "auto"), 
		"keywords" => array("type" => "TEXT", "search" => "auto"), 
		// >>> FR 091022
		"title" => array("type" => "TEXT", "search" => "auto"), 
		"edition" => array("type" => "SMALLINT UNSIGNED", "search" => "auto"), 
		"publisher" => array("type" => "TINYTEXT", "search" => "auto"), 
		"place" => array("type" => "TINYTEXT", "search" => "auto"), 
		"language_code" => array("type" => "TINYTEXT", ), 
		"literature_type" => array("type" => "SMALLINT UNSIGNED", ), // 0: journal, 1: book, 2: patent, 3:
		// <<< FR 091022
		"literature_group" => array("type" => "INT UNSIGNED", "fk" => "literature", ), // for angewandte and int ed, no chains allowed like A => B => C
	), 
);

// we do NOT assign one person to several citations, management (typos, etc) too much effort
$tables["author"]=array(
	"readPerm" => _lj_read+_lj_read_all, 
	"writePerm" => _lj_admin+_lj_project+_lj_edit+_lj_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"remoteFilter" => $literatureFilter, "defaultSecret" => true, 
	"fields" => array( // use this table transparent, i.e. don't show to user, like molecule_names
		"literature_id" => array("type" => "INT UNSIGNED", "fk" => "literature", ), 
		"nr_in_literature" => array("type" => "TINYINT UNSIGNED"), 
		"author_last" => array("type" => "TINYTEXT", "search" => "auto", "index" => " (10)", ),
		"author_first" => array("type" => "TINYTEXT", "search" => "auto"), 
		// >>> FR 091022
		"author_type" => array("type" => "SMALLINT UNSIGNED",  ), // 0: author, 1: editor, 2:
		// <<< FR 091022
	), 
);

$tables["sci_journal"]=array(
	"readPerm" => _lj_read+_lj_read_all, 
	"writePerm" => _lj_admin+_lj_project+_lj_edit+_lj_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"merge" => array("nameField" => "sci_journal_name"), 
	"recordCreationChange" => true, // no special filtering, not very secret
	"fields" => array(
		"sci_journal_name" => array("type" => "TINYTEXT", "search" => "auto", "index" => " (10)", ),
		"sci_journal_abbrev" => array("type" => "TINYTEXT", "search" => "auto", "index" => " (10)", ),
		"sci_journal_impact_factor" => array("type" => "DOUBLE"),
		"sci_journal_publisher" => array("type" => "TINYTEXT"),
		"sci_journal_driver" => array("type" => "TINYTEXT"), 
	), 
);

$tables["reaction_literature"]=array(
	"readPerm" => _lj_read+_lj_read_all, 
	"writePerm" => _lj_admin+_lj_project+_lj_edit+_lj_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"literature" => array("condition" => "reaction_literature.literature_id=literature.literature_id", ), 
		"sci_journal" => array("condition" => "literature.sci_journal_id=sci_journal.sci_journal_id", ), 
		
		"reaction" => array("condition" => "reaction_literature.reaction_id=reaction.reaction_id", ), 
		"lab_journal" => array("condition" => "reaction.lab_journal_id=lab_journal.lab_journal_id", ), 
	),
	
	"versioning" => true, 
	"fields" => array(
		"reaction_id" => array("type" => "INT UNSIGNED", "fk" => "reaction", ), // ???
		"literature_id" => array("type" => "INT UNSIGNED", "fk" => "literature", ), 
	), 
);

$tables["project_literature"]=array(
	"readPerm" => _lj_read+_lj_read_all, 
	"writePerm" => _lj_admin+_lj_project+_lj_edit+_lj_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"literature" => array("condition" => "project_literature.literature_id=literature.literature_id", ), 
		"sci_journal" => array("condition" => "literature.sci_journal_id=sci_journal.sci_journal_id", ), 
		
		"project" => array("condition" => "project_literature.project_id=project.project_id", ), 
	),
	
	"remoteFilter" => $reactionFilter, "defaultSecret" => true, 
	"fields" => array(
		"project_id" => array("type" => "INT UNSIGNED", "fk" => "project", ),
		"literature_id" => array("type" => "INT UNSIGNED", "fk" => "literature", ), 
		
		"nr_in_project" => array("type" => "TINYINT UNSIGNED"), 
	), 
);


$tables["chemical_storage_literature"]=array(
	"readPerm" => _chemical_read, 
	"writePerm" => _lj_admin+_chemical_edit+_chemical_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"literature" => array("condition" => "chemical_storage_literature.literature_id=literature.literature_id", ), 
		"sci_journal" => array("condition" => "literature.sci_journal_id=sci_journal.sci_journal_id", ), 
	),
	
	"versioning" => true, 
	"fields" => array(
		"chemical_storage_id" => array("type" => "INT UNSIGNED", "fk" => "chemical_storage", ), // ???
		"literature_id" => array("type" => "INT UNSIGNED", "fk" => "literature", ), 
	), 
);

$tables["molecule_literature"]=array(
	"readPerm" => _chemical_read, 
	"writePerm" => _lj_admin+_chemical_edit+_chemical_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"literature" => array("condition" => "molecule_literature.literature_id=literature.literature_id", ), 
		"sci_journal" => array("condition" => "literature.sci_journal_id=sci_journal.sci_journal_id", ), 
		
		"molecule" => array("condition" => "molecule_literature.molecule_id=molecule.molecule_id", ), 
	),
	
	"fields" => array(
		"molecule_id" => array("type" => "INT UNSIGNED", "fk" => "molecule", ),
		"literature_id" => array("type" => "INT UNSIGNED", "fk" => "literature", ), 
	), 
);

?>