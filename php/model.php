<?php

if (!isset($teajudgeset)){
	die();
}
include_once './php/sensetive_data.php';
date_default_timezone_set("UTC");

function generateRandomString($length = 8) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}


class Model
{
	
	private $db;

	public function __construct()
	{
		$this->db = new mysqli(DBHOST, DBUSER, DBPASS, 'teajudge');
		$this->db->set_charset("utf8");
	}
	public function __destruct(){
		$this->db->close();
	}


	/*
	 * Users
	 *
	 */


	public function checkUserAuth($login, $password){
		$result = $this->db->query('select uid, firstname, lastname, password from user where login = "'.$this->db->real_escape_string($login).'" limit 1');

		if ($result->num_rows === 1){
			$user_info = mysqli_fetch_assoc($result);
			//$user_info = $result::fetch_assoc();
			if ($user_info['password'] == md5($password)){
				return array($user_info['uid'], $user_info['firstname'], $user_info['lastname']);
			}
		}
		return null;
	}

	public function changeUserLogin($uid, $pass, $login){
		$result = $this->db->query('SELECT 1 FROM user WHERE login = "'.$this->db->real_escape_string($login).'"');
		if ($result->num_rows === 0 && preg_match("/^[\p{L}0-9]{4,20}$/u", $login)){
			$this->db->query('UPDATE `user` SET login = "'.$this->db->real_escape_string($login).'" WHERE uid = '.intval($uid).' AND `password` = "'.md5($pass).'" LIMIT 1');
			return $this->db->affected_rows;
		}
		return -1;
	}

	public function changeUserPassword($uid, $pass, $newpass){
		$this->db->query('UPDATE `user` SET `password` = "'.md5($newpass).'" WHERE uid = '.intval($uid).' AND `password` = "'.md5($pass).'" LIMIT 1');
		return $this->db->affected_rows;
	}

	public function getUserRole($uid){
		$result = $this->db->query('SELECT SUM(canCreateUser)>=1 as cuser, SUM(canCreateTask)>=1 as ctask, SUM(canGrant)>=1 as cgrant FROM (SELECT canCreateUser, canCreateTask, canGrant FROM user WHERE uid = '.intval($uid).' UNION ALL SELECT canCreateUser, canCreateTask, canGrant FROM `group` g JOIN user_group ug ON g.gid=ug.gid AND ug.uid = '.intval($uid).' UNION SELECT 0 as canCreateUser, 0 as canCreateTask, 0 as canGrant) up');

		return $result->fetch_assoc();
	}

	public function getUserList($uid){
		$up = $this->getUserRole($uid);
		if ($up['cuser']){
			$fresult = array();
			$result = $this->db->query('SELECT u.uid, u.firstName, u.lastName, g.gname FROM user u JOIN user_group ug ON u.uid=ug.uid JOIN `group` g ON ug.gid=g.gid ORDER BY g.gname, u.lastName, u.firstName');
			while ($row = $result->fetch_assoc()) {
				$fresult[] = $row;
			}
			return $fresult;
		}
		return array();
	}

	public function createUsers($users, $uid){
		$up = $this->getUserRole($uid);
		$fresult = array();
		if ($up['cuser']){
			$groups = $this->getGroupList(2);
			$urequest = "";
			$grequest = "";
			foreach ($users as $user) {
				$user = (array) $user;
				$user['group'] = mb_convert_case($user['group'], MB_CASE_LOWER, "UTF-8");
				if (!array_key_exists($user['group'], $groups)){
					$this->db->query('INSERT INTO `group`(`gname`) VALUES ("'.$this->db->real_escape_string($user['group']).'")');
					$groups[$user['group']] = $this->db->insert_id;
				}
				if ($groups[$user['group']]!=1 || $up['cgrant']){
					if (isset($user['password']) && preg_match("/^[A-z0-9]{4,20}$/u", $user['password'])){
						$password = $user['password'];
					}
					else{
						$password = generateRandomString();
					}
					if (isset($user['login']) && preg_match("/^[A-z0-9]{4,20}$/u", $user['login'])){
						$login = $user['login'];
					}
					else{
						$login = "tj".generateRandomString(6);
					}
					$this->db->query('INSERT INTO `user`(`login`, `password`,`firstname`, `lastname`) VALUES ("'.$this->db->real_escape_string($login).'", "'.md5(md5($password)).'", "'.$this->db->real_escape_string($user['firstName']).'", "'.$this->db->real_escape_string($user['lastName']).'")');
					$nuid =  $this->db->insert_id;
					$this->db->query('INSERT INTO `user_group`(`uid`, `gid`) VALUES ('.intval($nuid).','.intval($groups[$user['group']]).')');
					$fresult[] = array("login" => $login, "password" => $password, "group" => $user['group'], "firstname" => $user['firstName'], "lastname" => $user['lastName']);
				}
			}
		}
		return $fresult;
	}

	public function removeUser($users, $uid){
		$up = $this->getUserRole($uid);
		if ($up['cuser']){
			$in_clause = "";
			foreach ($users as $value) {
				if ($in_clause != "") {$in_clause .= ', '.intval($value);}
				else{$in_clause .= intval($value);}
			}
			$this->db->query('DELETE FROM user WHERE uid IN ('.$in_clause.')');
			$res = $this->db->affected_rows;
			$this->db->query('DELETE w FROM `group` w JOIN (SELECT g.gid, ug.uid FROM `group` g LEFT JOIN user_group ug ON g.gid = ug.gid) g ON w.gid=g.gid WHERE g.uid IS NULL');
			return $res;
		}
		return 0;
	}

	public function resetUserPassword($users, $uid){
		$up = $this->getUserRole($uid);
		if ($up['cuser'] && $up['cgrant']){
			$in_clause = "";
			foreach ($users as $value) {
				if ($in_clause != "") {$in_clause .= ', '.intval($value);}
				else{$in_clause .= intval($value);}
			}
			$ulist = array();
			$query = "";
			$result = $this->db->query('SELECT u.uid, u.login, u.firstName, u.lastName, g.gname FROM user u JOIN user_group ug ON u.uid=ug.uid JOIN `group` g ON ug.gid=g.gid WHERE u.uid IN ('.$in_clause.')');

			while ($row = $result->fetch_assoc()) {
				if (!array_key_exists($row['uid'], $ulist)) {
					$ulist[$row['uid']] = array("login" => $row['login'], "password" => generateRandomString(), "gname" => $row['gname'], "firstName" => $row['firstName'], "lastName" => $row['lastName']);
					$query .= 'WHEN `uid` = '.$row['uid'].' THEN "'.md5(md5($ulist[$row['uid']]['password'])).'" ';
				}
			}
			
			if ($query != ""){
				$this->db->query('UPDATE `user` SET `password` = CASE '.$query.' ELSE `password` END WHERE `uid` IN ('.$in_clause.') LIMIT '.count($users));
			}

			return $ulist;
		}
		return array();
	}

	public function getUserName($uid){
		$result = $this->db->query('SELECT firstName, lastName FROM user WHERE uid = '.intval($uid));
		return $result->fetch_assoc();
	}


	/*
	 * Groups
	 *
	 */


	public function getGroupList($assoc = 0, $attr = 0){
		if ($attr){
			$result = $this->db->query('SELECT * FROM `group` WHERE 1');
		}
		else{
			$result = $this->db->query('SELECT gid, gname FROM `group` WHERE 1');
		}

		$fresult = array();
		while ($row = $result->fetch_assoc()) {
			if ($attr){
				$fresult[] = $row;
			}
			else{
				if ($assoc == 1){
					$fresult["".$row['gid']] = $row['gname'];
				}
				elseif ($assoc == 2){
					$fresult[$row['gname']] = $row['gid'];	
				} 
				else{
					$fresult[] = array('gid' => $row['gid'], 'gname' => $row['gname']);
				}
			}
		}
		return $fresult;
	}

	public function removeGroup($gid, $uid, $withUser = false){
		$up = $this->getUserRole($uid);
		if ($up['cuser'] && $gid > 1){
			if ($withUser){
				$this->db->query('DELETE FROM user WHERE uid IN (SELECT uid FROM user_group WHERE gid = '.intval($gid).')');
			}
			$this->db->query('DELETE FROM `group` WHERE gid = '.intval($gid));
			return 1;
		}
		return 0;
	}

	public function changeGroupRole($gid, $role, $uid){
		$up = $this->getUserRole($uid);
		if ($up['cgrant']){
			$request = "";
			$role = (array) $role;
			if (isset($role['user']) && $up['cuser']){
				if ($role['user']==0){ $request.="canCreateUser=0"; }
				else{ $request.="canCreateUser=1"; }	
			}
			if (isset($role['task']) && $up['ctask']){
				if ($request != ""){$request.=", ";}
				if ($role['task']==0){ $request.="canCreateTask=0"; }
				else{ $request.="canCreateTask=1"; }	
			}
			if (isset($role['grant'])){
				if ($request != ""){$request.=", ";}
				if ($role['grant']==0){ $request.="canGrant=0"; }
				else{ $request.="canGrant=1"; }	
			}
			if ($request != ""){
				$this->db->query('UPDATE `group` SET '.$request.' WHERE gid = '.intval($gid));
				return $this->db->affected_rows;
			}
		}
		return 0;
	}


	/*
	 * Courses
	 *
	 */


	public function createCourse($name, $uid){
		$up = $this->getUserRole($uid);
		if ($up['ctask']){
			$this->db->query('INSERT INTO `course`(`cname`, `author`) VALUES ("'.$this->db->real_escape_string($name).'",'.intval($uid).')');

			return $this->db->insert_id;
		}
		else{
			return -1;
		}
	}

	public function renameCourse($cid, $name, $uid){
		$this->db->query('UPDATE course SET cname = "'.$this->db->real_escape_string($name).'" WHERE cid = '.intval($cid).' AND author = '.intval($uid));
	}

	public function getOwnCourses($uid){
		$result = $this->db->query('select cid, cname from course where author = '.intval($uid));
		$time = time();

		$fresult = array();
		while ($row = $result->fetch_assoc()) {
			$res3 = $this->db->query('SELECT COUNT(tid) as count FROM task WHERE cid = '.$row['cid']);
			$fres3 = $res3->fetch_assoc();

			$res1 = $this->db->query('SELECT COUNT(ug.uid) as count FROM user_course uc JOIN user_group ug on ug.gid=uc.gid WHERE uc.cid = '.$row['cid'].' AND uc.startTime < '.$time.' AND uc.clearTime > '.$time);
			$res2 = $this->db->query('SELECT COUNT(uid) as count FROM (SELECT ut.uid as uid, COUNT(t.tid) as tcount FROM task t JOIN (SELECT tid, testCount FROM variant) v ON t.tid = v.tid AND t.cid = '.$row['cid'].' JOIN (SELECT uid, tid, passed FROM user_task WHERE uid IN (SELECT ug.uid as uid FROM user_course uc JOIN user_group ug ON ug.gid=uc.gid AND uc.cid = '.$row['cid'].' AND uc.clearTime > '.$time.' AND uc.startTime < '.$time.')) ut ON t.tid = ut.tid AND v.testCount = ut.passed GROUP BY uid) ur WHERE tcount = '.$fres3['count'].' AND uid > 0');
			$fres1 = $res1->fetch_assoc();
			$fres2 = $res2->fetch_assoc();
			$fresult[] = array('cid' => $row['cid'], 'cname' => $row['cname'], 'tcount' => $fres3['count'], 'ucount' => $fres1['count'], 'rcount' => $fres2['count']);
		}
		return $fresult;
	}

	public function getUserCourses($uid){
		$time = time();
		$result = $this->db->query('SELECT uc.cid as cid, MAX(endTime) as endTime, c.cname as cname FROM user_course uc JOIN user_group ug ON ug.gid=uc.gid AND ug.uid = '.intval($uid).' AND uc.clearTime > '.$time.' AND uc.startTime < '.$time.' JOIN course c ON c.cid = uc.cid GROUP BY cid');

		$fresult = array();
		while ($row = $result->fetch_assoc()) {
			$res1 = $this->db->query('SELECT COUNT(tid) as count FROM task WHERE cid = '.$row['cid']);
			$res2 = $this->db->query('SELECT COUNT(t.tid) AS count FROM task t JOIN user_task ut ON t.cid = '.$row['cid'].' AND ut.uid = '.intval($uid).' AND t.tid = ut.tid JOIN (SELECT vid, testCount FROM variant) v ON ut.vid = v.vid AND v.testCount = ut.passed');
			$fres1 = $res1->fetch_assoc();
			$fres2 = $res2->fetch_assoc();
			$fresult[] = array('cid' => $row['cid'], 'cname' => $row['cname'], 'endTime' => $row['endTime'], 'tcount' => $fres1['count'], 'rcount' => $fres2['count']);
		}
		return $fresult;
	}

	public function getAssignment($cid){
		$result = $this->db->query('SELECT * FROM user_course WHERE cid = '.intval($cid));

		$fresult = array();
		while ($row = $result->fetch_assoc()) {
			$fresult["".$row['gid']] = array('startTime' => $row['startTime'], 'endTime' => $row['endTime'], 'clearTime' => $row['clearTime']);
		}
		return $fresult;	
	}

	public function getGroupsWithAssignment($cid){
		$res = array();
		$groups = $this->getGroupList(1);
		$assign = $this->getAssignment($cid);
		foreach ($groups as $key => $value) {
			if ($key > 1){
				if (array_key_exists($key, $assign)){
					$res[] = array_merge(array("gid" => $key, "gname" => $value), $assign[$key]);
				}
				else{
					$res[] = array("gid" => $key, "gname" => $value, "startTime" => -1);
				}
			}
		}
		return $res;
	}

	public function addAssignment($cid, $gid, $startTime, $endTime, $clearTime, $uid, $new=false){
		$result = $this->db->query('SELECT 1 FROM course WHERE cid = '.intval($cid).' AND author = '.intval($uid));

		if ($result->num_rows>0){
			if ($new){
				$this->db->query('INSERT INTO `user_course`(`gid`, `cid`, `startTime`, `endTime`, `clearTime`) VALUES ('.intval($gid).','.intval($cid).','.intval($startTime).','.intval($endTime).','.intval($clearTime).')');
			}
			else{
				$this->db->query('UPDATE `user_course` SET `startTime`='.intval($startTime).',`endTime`='.intval($endTime).',`clearTime`='.intval($clearTime).' WHERE cid = '.intval($cid).' AND gid = '.intval($gid));
			}
		}
	}

	public function getCourseContent($cid, $uid){
		$time = time();

		$result = $this->db->query('SELECT cname, '.intval($uid).' = author as isAuthor, COALESCE(MAX(uc.endTime), 0) as endTime FROM user_course uc JOIN user_group ug ON ug.gid=uc.gid AND ug.uid = '.intval($uid).' AND uc.cid = '.intval($cid).' JOIN course c ON c.cid = uc.cid AND uc.clearTime > '.$time.' AND uc.startTime < '.$time);
		$result2 = $this->db->query('SELECT COUNT(ug.uid) as ucount FROM user_course uc JOIN user_group ug ON uc.gid = ug.gid AND uc.cid = '.intval($cid).' WHERE uc.clearTime > '.$time.' AND uc.startTime < '.$time);

		$ares = $result->fetch_assoc();
		$ares2 = $result2->fetch_assoc();
		$ares['ucount'] = $ares2['ucount'];

		$fresult = array();
		if ($ares['isAuthor']==1 || $ares['endTime'] > 0){
			$fresult['cname'] = $ares['cname'];
			$fresult['isAuthor'] = $ares['isAuthor'];
			$fresult['task'] = array();
			if ($ares['isAuthor']==1){
				$fresult['ucount'] = $ares['ucount'];
				$res1 = $this->db->query('SELECT t.tid, t.tname, v.testCount, COALESCE(ut.passed, -1) as passed FROM task t JOIN (SELECT tid, vid, MIN(testCount) as testCount FROM variant GROUP BY tid) v ON v.tid = t.tid AND t.cid = '.intval($cid).' LEFT JOIN user_task ut ON ut.tid = t.tid AND ut.uid = '.intval($uid));
				while ($row = $res1->fetch_assoc()) {
					$res2 = $this->db->query('SELECT lcode FROM lang l JOIN variant_lang vl ON vl.lid = l.lid AND vl.tid = '.$row['tid'].' LIMIT 1');
					$fres2 = $res2->fetch_assoc();
					$res3 = $this->db->query('SELECT COUNT(ut.uid) as count FROM user_task ut WHERE ut.tid = '.$row['tid'].' AND ut.passed >= '.$row['testCount'].' AND ut.uid IN (SELECT ug.uid FROM user_course uc JOIN user_group ug ON ug.gid = uc.gid AND uc.cid = '.intval($cid).' AND uc.clearTime > '.$time.' AND uc.startTime < '.$time.')');
					$fres3 = $res3->fetch_assoc();
					$fresult['task'][] = array('tid' => $row['tid'], 'tname' => $row['tname'], 'testCount' => $row['testCount'],'passed' => $row['passed'], 'upassed' => $fres3['count'], 'lang' => $fres2['lcode']);
				}
			}
			else{
				$fresult['endTime'] = $ares['endTime'];
				$res1 = $this->db->query('SELECT t.tid, t.tname, v.testCount, COALESCE(ut.passed, -1) as passed FROM task t JOIN (SELECT tid, vid, MIN(testCount) as testCount FROM variant GROUP BY tid) v ON v.tid = t.tid AND t.cid = '.intval($cid).' LEFT JOIN user_task ut ON ut.tid = t.tid AND ut.uid = '.intval($uid));
				while ($row = $res1->fetch_assoc()) {
					$res2 = $this->db->query('SELECT lcode FROM lang l JOIN variant_lang vl ON vl.lid = l.lid AND vl.tid = '.$row['tid'].' LIMIT 1');
					$fres2 = $res2->fetch_assoc();
					$fresult['task'][] = array('tid' => $row['tid'], 'tname' => $row['tname'], 'testCount' => $row['testCount'], 'passed' => $row['passed'], 'lang' => $fres2['lcode']);
				}
			}

		}
		return $fresult;
	}

	public function deleteCourse($cid, $uid){
		$this->db->query('DELETE FROM `course` WHERE cid = '.intval($cid).' AND author = '.intval($uid));
	}


	/*
	 * Task
	 *
	 */


	public function createTask($cid, $uid, $tname, $statement, $testCount, $testNames, $testData, $testAnswer, $publicity, $timeLimit, $memoryLimit, $lid, $pattern, $ranges){
		
		$result = $this->db->query('SELECT 1 FROM course WHERE cid = '.intval($cid).' AND author = '.intval($uid));
		if ($result->num_rows>0){

			$this->db->query('INSERT INTO `task`(`cid`, `tname`) VALUES ('.intval($cid).',"'.$this->db->real_escape_string($tname).'")');
			$tid = $this->db->insert_id;

			$this->db->query('INSERT INTO `variant`(`tid`, `statement`, `testCount`, `testNames`, `testData`, `testAnswer`, `publicity`, `timeLimit`, `memoryLimit`) VALUES ('.$tid.',"'.$this->db->real_escape_string($statement).'",'.intval($testCount).',"'.$this->db->real_escape_string($testNames).'","'.$this->db->real_escape_string($testData).'","'.$this->db->real_escape_string($testAnswer).'","'.$this->db->real_escape_string($publicity).'",'.intval($timeLimit).','.intval($memoryLimit).')');
			$vid = $this->db->insert_id;

			$this->db->query('INSERT INTO `variant_lang`(`tid`, `vid`, `lid`, `pattern`, `ranges`) VALUES ('.$tid.','.$vid.','.intval($lid).',"'.$this->db->real_escape_string($pattern).'","'.$this->db->real_escape_string($ranges).'")');

			return $tid;
		}
		else{
			return -1;
		}
	}

	public function editTask($tid, $uid, $tname, $statement, $testCount, $testNames, $testData, $testAnswer, $publicity, $timeLimit, $memoryLimit, $lid, $pattern, $ranges){

		$result = $this->db->query('SELECT 1 FROM task t JOIN course c ON c.cid = t.cid AND t.tid = '.intval($tid).' AND c.author = '.intval($uid));
		if ($result->num_rows>0){
			$result = $this->db->query('SELECT vid FROM variant WHERE tid = '.intval($tid).' LIMIT 1');
			$fres = $result->fetch_assoc();
			$vid = $fres['vid'];

			$this->db->query('UPDATE `task` SET `tname`="'.$this->db->real_escape_string($tname).'" WHERE tid = '.intval($tid));

			$this->db->query('UPDATE `variant` SET `statement`="'.$this->db->real_escape_string($statement).'",`testCount`='.intval($testCount).',`testNames`="'.$this->db->real_escape_string($testNames).'",`testData`="'.$this->db->real_escape_string($testData).'",`testAnswer`="'.$this->db->real_escape_string($testAnswer).'",`publicity`="'.$this->db->real_escape_string($publicity).'",`timeLimit`='.intval($timeLimit).',`memoryLimit`='.intval($memoryLimit).' WHERE vid = '.$vid);

			$this->db->query('UPDATE `variant_lang` SET `pattern`="'.$this->db->real_escape_string($pattern).'",`ranges`="'.$this->db->real_escape_string($ranges).'" WHERE vid = '.$vid);	
		}
	}

	public function getTaskContent($tid){
		$result = $this->db->query('SELECT t.tid, t.cid, author, tname, v.vid, statement, testCount, testNames, testData, testAnswer, publicity, timeLimit, memoryLimit, vl.lid, lcode, pattern, ranges FROM task t JOIN course c ON t.cid=c.cid JOIN variant v ON v.tid = t.tid AND t.tid = '.intval($tid).' JOIN variant_lang vl ON vl.vid = v.vid JOIN `lang` l ON vl.lid=l.lid');

		return $result->fetch_assoc();
	}	

	public function getUserTaskContent($tid, $uid){
		$time = time();

		$result = $this->db->query('SELECT '.intval($uid).' = author as isAuthor, COALESCE(MAX(uc.endTime), 0) as endTime FROM (SELECT c.cid, c.author FROM task t JOIN course c  ON t.cid = c.cid AND t.tid = '.intval($tid).') c JOIN  user_course uc ON c.cid = uc.cid JOIN user_group ug ON ug.gid=uc.gid AND ug.uid = '.intval($uid).' WHERE uc.clearTime > '.$time.' AND uc.startTime < '.$time);
		$ares = $result->fetch_assoc();

		if ($ares['isAuthor'] == 1){
			$fresult = $this->getTaskContent($tid);
			$fresult['isAuthor'] = 1;
			$fresult['submissionCode'] = $fresult['pattern'];
			$fresult['canSubmit'] = 1;
			return $fresult;
		}
		elseif ($ares['endTime'] > 0){
			$res1 = $this->db->query('SELECT * FROM user_task WHERE uid = '.intval($uid).' AND tid = '.intval($tid));
			$fres2 = $this->getTaskContent($tid);
			$fres2['isAuthor'] = 0;
			$fres2['endTime']  = $ares['endTime'];
			$fres2['canSubmit']= $ares['endTime'];

			if ($res1->num_rows > 0){
				$fres1 = $res1->fetch_assoc();
				$fresult = array_merge($fres2, $fres1);
				return $fresult;
			}
			else{
				$fres2['submissionCode'] = $fres2['pattern'];

				$timet = "[0"; $memt = "[0"; $statust = '["NA"';
				for ($i=1; $i < $fres2['testCount']; $i++) { 
					$timet .= ",0"; $memt .= ",0"; $statust .= ',"NA"';
				}
				$timet .= "]"; $memt .= "]"; $statust .= "]";
				$this->db->query('INSERT INTO `user_task`(`uid`, `tid`, `vid`, `passed`, `time`, `memory`, `status`, `lid`, `ranges`, `submissionCode`, `history`, `startDate`, `submissionDate`) VALUES ('.intval($uid).','.intval($tid).','.$fres2['vid'].',0,"'.$timet.'","'.$memt.'","'.$this->db->real_escape_string($statust).'",'.$fres2['lid'].',"'.$fres2['ranges'].'","'.$this->db->real_escape_string($fres2['pattern']).'","[]",'.$time.','.$time.')');
				return $fres2;
			}
		}
		else{
			return null;
		}
	}

	public function isTaskAvailable($tid, $uid){
		$time = time();

		$result = $this->db->query('SELECT '.intval($uid).' = author as isAuthor, COALESCE(MAX(uc.endTime), 0) as endTime FROM (SELECT c.cid, c.author FROM task t JOIN course c  ON t.cid = c.cid AND t.tid = '.intval($tid).') c JOIN  user_course uc ON c.cid = uc.cid JOIN user_group ug ON ug.gid=uc.gid AND ug.uid = '.intval($uid).' WHERE uc.endTime > '.$time.' AND uc.startTime < '.$time);

		if ($result->num_rows>0){
			$ares = $result->fetch_assoc();
			return (($ares['isAuthor'] == 1) || ($ares['endTime'] > 0));
		}
		else{
			return false;
		}

	}

	public function updateTaskProgress($tid, $uid, $data){
		if ($this->isTaskAvailable($tid, $uid)){ 
			if (count($data) == 4){
				$this->db->query('UPDATE `user_task` SET `lid`='.intval($data['lid']).', `ranges`="'.$this->db->real_escape_string($data['ranges']).'",`submissionCode`="'.$this->db->real_escape_string($data['code']).'",`history`="'.$this->db->real_escape_string($data['history']).'" WHERE tid = '.intval($tid).' AND uid = '.intval($uid));
			}
			else{
				$this->db->query('UPDATE `user_task` SET `passed`='.intval($data['passed']).',`time`="'.$this->db->real_escape_string($data['time']).'",`memory`="'.$this->db->real_escape_string($data['memory']).'",`status`="'.$this->db->real_escape_string($data['status']).'",`lid`='.intval($data['lid']).',`ranges`="'.$this->db->real_escape_string($data['ranges']).'",`submissionCode`="'.$this->db->real_escape_string($data['code']).'",`history`="'.$this->db->real_escape_string($data['history']).'",`submissionDate`='.time().' WHERE tid = '.intval($tid).' AND uid = '.intval($uid));
			}
			return $this->db->affected_rows;
		}
		return 0;
	}

	public function dropTaskProgress($tid, $uid){
		$result = $this->db->query('SELECT 1 FROM task t JOIN course c ON c.cid = t.cid AND t.tid = '.intval($tid).' AND c.author = '.intval($uid));
		if ($result->num_rows>0){
			$this->db->query('DELETE FROM `user_task` WHERE tid = '.intval($tid));
		}
	}

	public function deleteTask($tid, $uid){
		$result = $this->db->query('SELECT 1 FROM task t JOIN course c ON c.cid = t.cid AND t.tid = '.intval($tid).' AND c.author = '.intval($uid));
		if ($result->num_rows>0){
			$this->db->query('DELETE FROM `task` WHERE tid = '.intval($tid));
		}
	}

	
	/*
	 * Statistics
	 *
	 */


	public function exportStats($id, $type, $uid){
		$fresult = array(); $time = time();
		if ($type == 1){ // task stats
			$result = $this->db->query('SELECT tname, t.tid, t.cid, c.cname, c.author = '.intval($uid).' AS isAuthor, testCount, testNames FROM task t JOIN course c ON c.cid=t.cid JOIN variant v ON v.tid = t.tid AND t.tid = '.intval($id));
		}
		else{ // course stats
			$result = $this->db->query('SELECT tname, t.tid, t.cid, c.cname, c.author = '.intval($uid).' AS isAuthor, testCount, testNames FROM task t JOIN course c ON c.cid=t.cid JOIN variant v ON v.tid = t.tid AND t.cid = '.intval($id));
		}
		while ($row = $result->fetch_assoc()) {
			$tresult = array("cname" => $row["cname"], "cid" => $row["cid"], "isAuthor" => $row["isAuthor"], "tname" => $row["tname"], "testCount" => $row["testCount"], "testNames" => $row["testNames"], "results" => array());
			$res2 = $this->db->query('SELECT ug.uid, firstName, lastName, gname, passed, `time`, memory, `status`, submissionCode, history, startDate, submissionDate, submissionDate - startDate as duration, `pattern` FROM user_course uc JOIN user_group ug ON uc.gid=ug.gid AND uc.cid = '.$row['cid'].' AND uc.clearTime > '.$time.' AND uc.startTime < '.$time.' JOIN `group` g ON g.gid=ug.gid JOIN `user` u ON u.uid=ug.uid JOIN user_task ut ON ut.uid=ug.uid AND ut.tid='.$row['tid'].' JOIN `variant_lang` vl ON vl.tid='.$row['tid'].' AND vl.lid=ut.lid ORDER BY gname, lastName, firstName');
			while ($row2 = $res2->fetch_assoc()){
				$tresult['results'][] = $row2;
			}
			$fresult[] = $tresult;
		}
		return $fresult;
	}

	public function exportCodeToCompare($tid, $uid, $uidL, $uidR){
		$time = time();
		$result = $this->db->query('SELECT tname, t.tid, t.cid, c.author = '.intval($uid).' AS isAuthor FROM task t JOIN course c ON c.cid=t.cid JOIN variant v ON v.tid = t.tid AND t.tid = '.intval($tid));
		$row = $result->fetch_assoc();
		$fresult = array("isAuthor" => $row["isAuthor"], "results" => array());
		if (intval($row["isAuthor"]) != 0){
			$res2 = $this->db->query('SELECT ug.uid, submissionCode FROM user_course uc JOIN user_group ug ON uc.gid=ug.gid AND uc.cid = '.$row['cid'].' AND uc.clearTime > '.$time.' AND uc.startTime < '.$time.' JOIN `group` g ON g.gid=ug.gid JOIN user_task ut ON ut.uid=ug.uid AND ut.tid='.$row['tid'].' WHERE ug.uid = '.intval($uidL).' OR ug.uid = '.intval($uidR));
			while ($row2 = $res2->fetch_assoc()){
				$fresult['results'][$row2['uid']] = $row2['submissionCode'];
			}
		}
		return $fresult;
	}
}



