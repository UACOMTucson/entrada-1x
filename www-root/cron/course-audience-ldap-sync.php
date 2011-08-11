<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2006 Queen's University, MEdTech Unit
 *
 * $Id: community-quota.php 1103 2010-04-05 15:20:37Z simpson $
*/

@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");
require_once("dbconnection.inc.php");

$ldap = NewADOConnection("ldap");
$ldap->SetFetchMode(ADODB_FETCH_ASSOC);
$ldap->debug = false;
$query = "	SELECT `course_code`,`course_id` 
			FROM `courses` 
			WHERE `course_active` = 1
			AND `ldap_sync` = 1";
$results = $db->GetAll($query);
if ($results) {
	foreach ($results as $course) {
		
					
		
		$query = "	SELECT a.`id`, a.`number` 
					FROM `".AUTH_DATABASE."`.`user_data` AS a 
					JOIN `group_members` AS b	
					ON a.`id` = b.`proxy_id` 
					AND b.`member_active` = 1
					JOIN `groups` AS c 
					ON b.`group_id` = c.`group_id`
					WHERE c.`group_type` = 'class_list' 
					AND c.`group_value` = ".$db->qstr($course["course_id"])."
					AND b.`entrada_only` = 0";
		
		
		
		$audience = $db->GetAll($query);
		
		if ($audience) {
			foreach ($audience as $key=>$audience_member) {
				$course_audience["id"][$key] = $audience_member["id"];
				$course_audience["number"][$key] = $audience_member["number"];
			}
			unset($audience);
		} else {
			$course_audience = false;
		}
			
		print_r($course_audience);
		
		
		//create LDAP connection
		if ($ldap->Connect(LDAP_HOST, LDAP_SEARCH_DN,LDAP_SEARCH_DN_PASS, LDAP_GROUPS_BASE_DN)) {
			//get the course information, in particular the list of unique members
			if (($result = $ldap->GetRow("cn=".$course["course_code"]."*"))) {
				$ldap->Close();
				//make new connection with the base set to people to get user information
				if ($ldap->Connect(LDAP_HOST, LDAP_SEARCH_DN,LDAP_SEARCH_DN_PASS, LDAP_PEOPLE_BASE_DN)) {
					
					$query = "	SELECT `group_id` FROM `groups` WHERE `group_type` = 'class_list' AND `group_value` = ".$db->qstr($course["course_id"]);
					$group_id = $db->GetOne($query);
					
					if (!$group_id && count($result["uniqueMember"])) {
						$query = "	INSERT INTO `groups` VALUES(NULL,".$db->qstr($course["course_code"]." Class List").",0,'class_list',".$db->qstr($course["course_id"]).",0,0,1,0,0)";
						$db->Execute($query);
						$group_id = $db->Insert_Id();
					}
					
					if ($group_id) {
						
						$query = "	SELECT * FROM `course_audience` 
									WHERE `course_id` = ".$db->qstr($course["course_id"])." 
									AND `audience_type` = 'group_id' 
									AND `audience_value` = ".$db->qstr($group_id);
						
						if (!$db->GetAll($query)) {
							$query = "	INSERT INTO `course_audience` VALUES (NULL,".$db->qstr($course["course_id"]).",'group_id',".$db->qstr($group_id).",0,0,1)";
							$db->Execute($query);
						}
						
						if ($result["uniqueMember"] && count($result["uniqueMember"])){			
							//for each user in the unique member list get their queensuCaPkey
							foreach ($result["uniqueMember"] as $key=>$member) {
								$member_path = explode(',',$member);
								$uniUid = trim(str_replace('QueensuCaUniUid=', '', $member_path[0]));

								//there should always be a result, if not the LDAP server has a student enrolled with no LDAP entry
								if (($result = $ldap->GetRow("QueensuCaUniUid=".$uniUid."*"))) {
									//echo $uniUid."'s student number is ".$result["queensuCaPKey"]."        ";

									$pKey = str_replace("S","",$result["queensuCaPKey"]);
									$query = "	SELECT `id` 
												FROM `".AUTH_DATABASE."`.`user_data` 
												WHERE `number` = ".$db->qstr($pKey);
									//if there is a record, the student is created inside Entrada, no result means there is no linked Entrada account
									if ($id = $db->GetOne($query)) {
										$query = "	SELECT * FROM `group_members` 
													WHERE `proxy_id` = ".$db->qstr($id)."
													AND `group_id` = ".$db->qstr($group_id);

										//if no result, insert into the course audience, otherwise remove from array
										if (!$result=$db->GetAll($query)) {
											//insert into audience
											$query = "	INSERT INTO `group_members` VALUES(NULL,".$db->qstr($group_id).",".$db->qstr($id).",".time().",".time().",1,0,".time().",0)";
											if ($db->Execute($query)) {
												echo $pKey." WAS SUCCESSFULLY REGISTERED INTO THE COURSE: ".$course["course_code"]."    ";
											} else {
												echo "Error occurred while adding ".$pKey." to the course.";
											}
										} elseif ($course_audience) {
											$key = array_search($pKey,$course_audience["number"]);
											if ($key !== false) {
												unset($course_audience["number"][$key]);
												unset($course_audience["id"][$key]);
												echo $pKey." was already a course member in Entrada amd the key was unset.";
											}
											echo $pKey." was already a course member in Entrada";
										}
									} else {
										//echo 'Student found in course on LDAP server who is not registered in Entrada.';
									}

								} else {
									echo 'LDAP records out of date, inform LDAP admin.';
								}

							}
						} else {
							echo 'No members found for course '.$course["cource_code"];
						}
						
						if ($course_audience) {
							print_r($course_audience);
							$end_stamp = time();
							foreach ($course_audience["id"] as $key=>$audience_member) {
								$query = "	UPDATE `group_members` 
											SET `finish_date` = ".$db->qstr($end_stamp).", 
											`member_active` = 0 
											WHERE `group_id` = ".$db->qstr($group_id)."
											AND `proxy_id` = ".$db->qstr($audience_member);
								if ($db->Execute($query)) {
									echo $course_audience["number"][$key]." is no longer a member of the course";
								} else {
									echo "Error occurred while removing ".$pKey." from the course list.";
								}
							}					
						}
					} else {
						echo 'No group_id for course '.$course["course_code"];
					}
						 
					$ldap->Close();
				
				
				} else {
					echo 'Could not connect to get student information';
				}

			} else {
				echo 'No results from LDAP server for Entrada course '.$course["course_code"].'. Check that course code is valid.               ';
			}
		} else {
			echo 'Could not connect to get course information.';
		}
	}
} else {
	echo 'No courses found in system.';
}
?>