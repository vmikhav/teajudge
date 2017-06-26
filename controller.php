<?php 
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
if (!isset($_GET['act'])){ die();}

$teajudgeset = 1;
$act = $_GET['act'];
$noRedirect = 1;

include_once './php/check_auth.php';
include_once './php/model.php';
include_once './php/compare_code.php';

class Controller
{
	private $model;
	private $uid;

	function __construct($uid){
		$this->model = new Model();
		$this->uid = $uid;
	}

	public function route($act)
	{
		if (is_callable(array($this, $act))){
			$this->$act();
		}
	}

	private function login(){
		if (isset($_POST['login']) && isset($_POST['pass']) && isset($_POST['stay'])){
			$uid = $this->model->checkUserAuth($_POST['login'], $_POST['pass']);
			if (!is_null($uid)){
				$token = array();
				$token['id'] = $uid[0];
				$token['hash'] = md5($uid[1].$uid[0].$uid[2]);
				if ($_POST['stay'] == 0){
					$time = 0;
					$token['eat'] = time()+60*60;
				}
				else{
					$time = time()+60*60*24*365*4;
				}
				$en = JWT::encode($token, TJSECRET);
				setcookie("jwt",$en,$time);
				setcookie("lid",1,$time);
				echo "ok";
			}
			else{ echo "nee"; }
		}
		else{ echo "nein"; }
	}

	private function logout(){
		//unset($_COOKIE['jwt']);
		setcookie('jwt', '', time()-3600);
    	setcookie('jwt', '', time()-3600, '/');
    	header('Location: ./index.php', true, 303);
		die();
	}

	private function createCourse(){
		$res = array("status" => "nope");
		if (isset($_POST['name'])){
			$name = $_POST['name'];
			$cid = $this->model->createCourse($name, $this->uid);
			if ($cid > 0){
				$res['status'] = "created";
				$res['cid'] = $cid;
			}
		}
		echo json_encode($res);
	}

	private function deleteCourse(){
		if (isset($_POST['cid'])){
			$cid = intval($_POST['cid']);
			$this->model->deleteCourse($cid, $this->uid);
			echo "ok".$cid." ".$this->uid;
		}
		echo "ok";
	}

	private function renameCourse(){
		$res = array("status" => "nope");
		if (isset($_POST['name']) && isset($_POST['cid'])){
			$name = $_POST['name']; $cid = intval($_POST['cid']);
			$this->model->renameCourse($cid, $name, $this->uid);
			$res['status'] = "renamed";
			$res['cid'] = $cid;
		}
		echo json_encode($res);
	}

	private function courseGroupList(){
		$res = array();
		if (isset($_POST['cid'])){
			$cid = intval($_POST['cid']);
			$res = $this->model->getGroupsWithAssignment($cid);
		}
		echo json_encode($res);
	}

	private function setAssignment(){
		if (isset($_POST['cid']) && isset($_POST['gid']) && isset($_POST['type']) && isset($_POST['startTime']) && isset($_POST['endTime']) && isset($_POST['clearTime'])){
			$cid = intval($_POST['cid']);
			$gid = intval($_POST['gid']);
			$type = $_POST['type'];
			$startTime = intval($_POST['startTime']);
			$endTime = intval($_POST['endTime']);
			$clearTime = intval($_POST['clearTime']);
			$new = $type=='new'?true:false;
			$this->model->addAssignment($cid, $gid, $startTime, $endTime, $clearTime, $this->uid, $new);
			echo "ok";
		}
	}

	private function createTask(){
		if (isset($_POST['cid']) && isset($_POST['tname']) && isset($_POST['statement']) && isset($_POST['testCount']) && isset($_POST['testNames']) && isset($_POST['testData']) && isset($_POST['testAnswer']) && isset($_POST['publicity']) && isset($_POST['timeLimit']) && isset($_POST['memoryLimit']) && isset($_POST['lid']) && isset($_POST['pattern']) && isset($_POST['ranges'])){
			$cid = intval($_POST['cid']);
			$tname = $_POST['tname'];
			$statement = $_POST['statement'];
			$testCount = intval($_POST['testCount']);
			$testNames = $_POST['testNames'];
			$testData = $_POST['testData'];
			$testAnswer = $_POST['testAnswer'];
			$publicity = $_POST['publicity'];
			$timeLimit = intval($_POST['timeLimit']);
			$memoryLimit = intval($_POST['memoryLimit']);
			$lid = intval($_POST['lid']);
			$pattern = $_POST['pattern'];
			$ranges = $_POST['ranges'];
			$tid = $this->model->createTask($cid, $this->uid, $tname, $statement, $testCount, $testNames, $testData, $testAnswer, $publicity, $timeLimit, $memoryLimit, $lid, $pattern, $ranges);
			setcookie("lid",$lid,time()+60*60*24*365);
			if ($tid > 0){
				echo $tid;
			}
			else{ echo -1;}
		}
		echo -1;
	}

	private function editTask(){
		if (isset($_POST['tid']) && isset($_POST['tname']) && isset($_POST['statement']) && isset($_POST['testCount']) && isset($_POST['testNames']) && isset($_POST['testData']) && isset($_POST['testAnswer']) && isset($_POST['publicity']) && isset($_POST['timeLimit']) && isset($_POST['memoryLimit']) && isset($_POST['lid']) && isset($_POST['pattern']) && isset($_POST['ranges'])){
			$tid = intval($_POST['tid']);
			$tname = $_POST['tname'];
			$statement = $_POST['statement'];
			$testCount = intval($_POST['testCount']);
			$testNames = $_POST['testNames'];
			$testData = $_POST['testData'];
			$testAnswer = $_POST['testAnswer'];
			$publicity = $_POST['publicity'];
			$timeLimit = intval($_POST['timeLimit']);
			$memoryLimit = intval($_POST['memoryLimit']);
			$lid = intval($_POST['lid']);
			$pattern = $_POST['pattern'];
			$ranges = $_POST['ranges'];
			$this->model->editTask($tid, $this->uid, $tname, $statement, $testCount, $testNames, $testData, $testAnswer, $publicity, $timeLimit, $memoryLimit, $lid, $pattern, $ranges);
			$this->model->dropTaskProgress($tid, $this->uid);
			setcookie("lid",$lid,time()+60*60*24*365);
		}
		echo "ok";
	}

	private function getTaskContent(){
		if (isset($_POST['tid'])){
			$tid = intval($_POST['tid']);
			echo json_encode($this->model->getTaskContent($tid));
		}
		else{echo '{"tid":-1}';}
	}

	private function deleteTask(){
		if (isset($_POST['tid'])){
			$tid = intval($_POST['tid']);
			$this->model->deleteTask($tid, $this->uid);
			echo "ok";
		}
	}

	private function saveCode(){
		if (isset($_POST['ranges']) && isset($_POST['code']) && isset($_POST['task']) && isset($_POST['lid']) && isset($_POST['history'])){
			$tid = intval($_POST['task']);
			$lid = intval($_POST['lid']);
			$data = array("ranges" => $_POST['ranges'], "code" => $_POST['code'], "lid" => $lid, "history" => $_POST['history']);
			echo $this->model->updateTaskProgress($tid, $this->uid, $data);
		}
	}

	private function exportStats(){
		if (isset($_POST['id']) && isset($_POST['type'])){
			echo json_encode($this->model->exportStats(intval($_POST['id']), intval($_POST['type']), $this->uid));
		}
	}

	private function resetUserPassword(){
		if (isset($_POST['users'])){
			$res = $this->model->resetUserPassword(json_decode($_POST['users']), $this->uid);
			header("Content-type: text/csv");
			header("Content-Disposition: attachment; filename=users.csv");
			header("Content-Transfer-Encoding: UTF-8");
			header("Pragma: no-cache");
			header("Expires: 0");
			
			$output = fopen("php://output", "w");
			foreach ($res as $row){
				fputcsv($output, $row);
			}
			fclose($output);
		}
	}

	private function createUsers(){
		if (isset($_POST['users'])){
			$res = $this->model->createUsers(json_decode($_POST['users']), $this->uid);
			header("Content-type: text/csv");
			header("Content-Disposition: attachment; filename=users.csv");
			header("Content-Transfer-Encoding: UTF-8");
			header("Pragma: no-cache");
			header("Expires: 0");
			
			$output = fopen("php://output", "w");
			foreach ($res as $row){
				fputcsv($output, $row);
			}
			fclose($output);
		}
	}

	private function removeUser(){
		if (isset($_POST['users'])){
			echo $this->model->removeUser(json_decode($_POST['users']), $this->uid);
		}
	}

	private function changeUserLogin(){
		if (isset($_POST['pass']) && isset($_POST['login'])){
			echo $this->model->changeUserLogin($this->uid, $_POST['pass'], $_POST['login']);
		}
	}

	private function changeUserPassword(){
		if (isset($_POST['pass']) && isset($_POST['newpass'])){
			echo $this->model->changeUserPassword($this->uid, $_POST['pass'], $_POST['newpass']);
		}
	}

	private function removeGroup(){
		if (isset($_POST['gid'])){
			echo $this->model->removeGroup(intval($_POST['gid']), $this->uid, true);
		}
	}

	private function changeGroupRole(){
		if (isset($_POST['gid']) && isset($_POST['role'])){
			echo $this->model->changeGroupRole(intval($_POST['gid']), json_decode($_POST['role']), $this->uid);
		}
	}

	private function compareCode(){
		if (isset($_POST['tid']) && isset($_POST['luid']) && isset($_POST['ruid'])){
			echo json_encode(compareCode($this->model, intval($_POST['tid']), $this->uid, intval($_POST['luid']), intval($_POST['ruid']) ));
		}
	}
}


$controller = new Controller($uid);

$controller->route($act);

?>