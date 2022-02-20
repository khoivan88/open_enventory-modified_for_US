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
function showMessageEditForm($paramHash) { // if from_person==person_id: edit message, else read message
	global $editMode,$person_id,$own_data;
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"message");
	
	if (!$editMode) {
		$paramHash["checkSubmit"]=
			'var recipients=getControlValue("recipients"); '.
			'if (recipients.length==0) { '.
				'alert('.fixStr(s("error_no_to_person")).'); '.
				'return false; '.
			'}';
	}
	
	if ($editMode) { // no edit available
		$paramHash[DEFAULTREADONLY]="always";
		//~ $paramHash["disableEdit"]=true;
		
		// ist Person a) Absender b) Empfänger c) garnix ?
		$paramHash["setControlValues"]=
			'var recipients=a(values,"recipients"),completion_status,p_comment,is_sender=(a(values,"from_person")=='.fixNull($person_id).'),sender_defRo="always",is_recipient=false,recipient_defRo="always",accept_defRo="always",do_defRo="always",showSenderButtons=false; '.
			'for (var b=0;b<recipients.length;b++) { '.
				'if (recipients[b]["person_id"]=='.fixNull($person_id).') { '.
					'is_recipient=true; '.
					'completion_status=recipients[b]["completion_status"]; '.
					'p_comment=recipients[b]["p_comment"]; '.
					'if (completion_status==1) { '.
						'completion_status=2; '.
						'changeMessageCompletion(completion_status); '.
					'} '.
					'if (completion_status!=6) { '.
						'do_defRo="never"; '.
					'} '.
					'setControl("completion_status",{"completion_status":completion_status}); '.
					'setControl("p_comment",{"p_comment":p_comment}); '.
				'} '.
				'if (is_sender && recipients[b]["completion_status"]>=6) { '.
					'showSenderButtons=true; '.
				'} '.
			'} '.
			'if (is_sender) { '.
				'sender_defRo=""; '.
			'} '.
			'if (is_recipient) { '.
				'recipient_defRo=""; '.
			'} '.
			'defaultReadOnlyControl("message_subject",sender_defRo); '.
			'defaultReadOnlyControl("priority",sender_defRo); '.
			'defaultReadOnlyControl("do_until",sender_defRo); '.
			'defaultReadOnlyControl("message_text",sender_defRo); '.
			'defaultReadOnlyControl("p_comment",recipient_defRo); '.
			'showControl("completion_status",is_recipient); '.
			'showControl("senderButtons",showSenderButtons); '.
			'showControl("recipients",is_sender); '.
			'defaultReadOnlyControl("completion_status",do_defRo); '.
			'setControl("from_person_text",{"from_person_text":formatPerson(values)}); '.
			'setControl("recipients_list",{"recipients_list":recipients}); ';
	}
	
 	$retval=getFormElements($paramHash,array(
		array("item" => "text", "text" => "<table><tr><td>"), 
		array(
			"item" => "select", 
			"int_name" => "completion_status", 
			"int_names" => range(2,6), 
			"langKeys" => getValueList("message_person","completion_status"), 
			"skip" => !$editMode, 
			DEFAULTREADONLY => "never", 
			"onChange" => "changeMessageCompletion(this.value)", 
		), 
		array(
			"item" => "text", 
			"int_name" => "senderButtons", 
			"skip" => !$editMode, 
			"text" => 
				"<table class=\"noborder\"><tr><td>
				<a href=\"javascript:changeMessageCompletion(7)\" class=\"imgButtonSm\"><img src=\"lib/ok_sm.png\" border=\"0\"".getTooltip("accept_completion")."></a>
				</td><td>
				<a href=\"javascript:changeMessageCompletion(5)\" class=\"imgButtonSm\"><img src=\"lib/nok_sm.png\" border=\"0\"".getTooltip("set_back_to_incomplete")."></a>
				</td></tr></table>", 
			), 
		"tableStart", 
		array("item" => "input", "int_name" => "from_person_text", DEFAULTREADONLY => "always"),
		array("item" => "hidden", "int_name" => "from_person"),
		array("item" => "input", "int_name" => "message_subject", "size" => 20, "maxlength" => 100), 
		
		array(
			"item" => "select", 
			"int_name" => "priority", 
			"langKeys" => getValueList("message","priority"), 
		), 
		
		array("item" => "input", "int_name" => "do_until", "size" => 10, "maxlength" => 10, "type" => "date"), 
		"tableEnd", 
		array("item" => "text", "text" => "</td><td>"), 
		"tableStart", 
		array(
			"item" => "pk_select", 
			"table" => "person", 
			"int_name" => "recipients", 
			"pkName" => "person_id", 
			"text" => s("recipients"), 
			"multiMode" => true, 
			"size" => 6, 
			//~ "pk_exclude" => $person_id, 
		), 
		"tableEnd", 
		array("item" => "text", "text" => "</td></tr></table>"), 
		"br",
		array("item" => "input", "int_name" => "message_text", "type" => "textarea", "rows" => 20, "cols" => 80, ),
		"br",
		array("item" => "input", "int_name" => "p_comment", "type" => "textarea", "rows" => 20, "cols" => 80, "skip" => !$editMode),
		// Subitemlist, readOnly für Kommentare und Bearbeitungsstatus
		"br",
		array("item" => "subitemlist", "int_name" => "recipients_list", "skip" => !$editMode, DEFAULTREADONLY => "always", 
			"fields" => array(
				array("item" => "cell"), 
				array("item" => "js", "int_name" => "detailbutton", "functionBody" => "formatPerson(values);" ),
				array("item" => "cell"), 
				array(
					"item" => "select", 
					"int_name" => "completion_status", 
					"int_names" => range(1,7), 
					"langKeys" => getValueList("message_person","completion_status"), 
				),
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "p_comment"),
			)
		),
	));
	
	return $retval;
}
?>