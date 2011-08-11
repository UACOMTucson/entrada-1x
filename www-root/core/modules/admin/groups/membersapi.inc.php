<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * This file looks a bit different because it is called only by AJAX requests
 * and returns the members relevant to the requested group and role.
 *
 * @author Organisation: University of Calgary
 * @author Unit: School of Medicine
 * @author Developer: Howard Lu <yhlu@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
 * @version $Id: add.inc.php 317 2009-01-19 19:26:35Z simpson $
 *
 */

if((!defined("PARENT_INCLUDED")) || (!defined("IN_GROUPS"))) {
/**
 * @exception 0: Unable to start processing request.
 */
	echo "Authentication error!";
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
/**
 * @exception 0: Unable to start processing request.
 */
	echo "Authentication error!";
	exit;
}

ob_clear_open_buffers();
$GROUP_ID		= 0;

if((isset($_GET["group"])) && ((int) trim($_GET["group"]))) {
	$GROUP_ID	= (int) trim($_GET["group"]);
} elseif((isset($_POST["group_id"])) && ((int) trim($_POST["group_id"]))) {
	$GROUP_ID	= (int) trim($_POST["group_id"]);
}

if((isset($_GET["action"])) && ($tmp_action_type = clean_input(trim($_GET["action"]), "alphanumeric"))) {
	$ACTION	= strcmp($tmp_action_type,"all") ? 0 : 1;
} elseif((isset($_POST["action"])) && ($tmp_action_type = clean_input(trim($_POST["action"]), "alphanumeric"))) {
	$ACTION	= strcmp($tmp_action_type,"all") ? 0 : 1;
} else {
	$ACTION = 0;
}

unset($tmp_action_type);

if(isset($GROUP_ID)) {
	ob_clear_open_buffers();

	//Figure out the organisation, group, and role requested
	if(isset($_POST["ogr"])) {
		$pieces = explode("|", $_POST["ogr"]);
		if(isset($pieces[0])) {
			$ORGANISATION_ID = clean_input($pieces[0], array("trim", "int"));
		}
		if(isset($pieces[1])) {
			$GROUP = clean_input($pieces[1], array("trim", "alphanumeric"));
		}
		if(isset($pieces[2])) {
			$ROLE = clean_input($pieces[2], array("trim", "alphanumeric"));
		}
	}

	if((isset($_POST["added_ids"])) && (is_array($_POST["added_ids"])) && count($_POST["added_ids"])) {
		$previously_added_ids = array();
		foreach ($_POST["added_ids"] as $id) {
			$previously_added_ids[] = (int) trim($id);
		}
	}
	
	if(isset($ORGANISATION_ID) && isset($GROUP) && isset($ROLE)) {
		$query			= "SELECT * FROM `groups` WHERE `group_id` = ".$db->qstr($GROUP_ID)." AND `group_active` = '1'";
		$group_details	= $db->GetRow($query);
		if($group_details || !$GROUP_ID) {
			if($ENTRADA_ACL->amIAllowed("group", "update")) {
				//Groups  exists and is editable by the current users
				$nmembers_results		= false;
				$nmembers_query	= "	SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`username`, a.`organisation_id`, b.`group`, b.`role`
									FROM `".AUTH_DATABASE."`.`user_data` AS a
									LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
									ON a.`id` = b.`user_id`
									WHERE (( 1 = ".$db->qstr($ACTION).") OR (
									a.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
									AND b.`group` = ".$db->qstr($GROUP)."
									".($ROLE != "all" ? "AND b.`role` = ".$db->qstr($ROLE) : "")."))
									AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
									AND b.`account_active` = 'true'
									AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
									AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
									GROUP BY a.`id`
									ORDER BY a.`lastname` ASC, a.`firstname` ASC";

				//Fetch list of current members
				$current_member_list	= array();
				$query		= "SELECT `proxy_id` FROM `group_members` WHERE `group_id` = ".$db->qstr($GROUP_ID)." AND `member_active` = '1'";
				$results	= $db->GetAll($query);
				if($results) {
					foreach($results as $result) {
						if($proxy_id = (int) $result["proxy_id"]) {
							$current_member_list[] = $proxy_id;
						}
					}
				}
				
				if($nmembers_query != "") {
					$nmembers_results = $db->GetAll($nmembers_query);
					if($nmembers_results) {
						$members = array(array("text" => $GROUP." > ".$ROLE, "value"=>$GROUP.$ROLE, "options"=>array(), "disabled"=>false, "category"=>"true"));
						foreach($nmembers_results as $member) {
							if(in_array($member["proxy_id"], $current_member_list)) {
								$registered = true;
							} else {
								$registered = false;
							}
							$members[0]["options"][] = array("text" => $member["fullname"].($registered ? " (already a member)" : ""), "value" => $member["proxy_id"], "disabled" => $registered, "checked" => (isset($previously_added_ids) && in_array(((int)$member["proxy_id"]), $previously_added_ids) ? "checked=\"checked\"" : ""));
//							$members[0]["options"][] = array("text" => $member["fullname"], "value" => $member["proxy_id"], "disabled" => false, "checked" => ($registered ? "checked=\"checked\"" : ""));	
						}
						
						foreach($members[0]["options"] as $key => $member) {
							if(isset($member["options"]) && is_array($member["options"]) && !empty($member["options"])) {
								//Alphabetize members
								sort($members[0]["options"][$key]["options"]);
							}
						}
						echo "<table cellspacing=\"0\" cellpadding=\"0\" class=\"select_multiple_table\" width=\"100%\">";
						echo lp_multiple_select_table($members, 0, 0, true);
						echo "</table>";
					} else {
						echo "No One Available [1]";
					}
				} else {
					echo "No One Available [2]";
				}
			} else {
				echo "Permissions error!";
			}
		} else {
//			echo "Malformed request!";
			echo "<h2>Group is inactive!</h2>";
		}
	}
}
exit();