<?php

if (!isset($_POST['lang']) || !isset($_POST['lid']) || !isset($_POST['code']) || !isset($_POST['task']) || !isset($_POST['ranges']) || !isset($_POST['history']) ){die();}

$tid = intval($_POST['task']);
$lid = intval($_POST['lid']);
$lang = $_POST['lang'];
if ($tid < 1){ die();}
if ($lid<1 || $lid>2){die();}

if (strlen($_POST['code'])>7000){
	$ares = array('result' => 'CE', 'cmperr' => LONG_CODE);
	echo json_encode($ares);
	die();
}

$teajudgeset = 1;
$noRedirect = 1;
include_once './php/check_auth.php';
if ($uid<1){ echo "auth"; die();}

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
include_once './php/model.php';

$model = new Model();

if (!$model->isTaskAvailable($tid, $uid)){
	$ares = array('result' => 'CE', 'cmperr' => TASK_CLOSED);
	echo json_encode($ares);
	die();
}

$task = $model->getTaskContent($tid);

$saveData = array('ranges' => $_POST['ranges'], 'code' => $_POST['code'], 'lid' => $lid, "history" => $_POST['history']);
$task['testCount'] = intval($task['testCount']);
$vid = intval($task['vid']);

function checkAnswer($userAnswer, $realAnswer){
	$userAnswer = preg_split("/\s+/", trim($userAnswer));
	$realAnswer = preg_split("/\s+/", trim($realAnswer));

	$result = 'OK';

	if (count($userAnswer) != count($realAnswer)){
		$result = 'WA';
	}
	else{
		foreach ($realAnswer as $key => $value) {
			$pos = strpos($value, '.');
			if ($pos !== false){
				$userAnswer[$key] = substr($userAnswer[$key], 0, strlen($value));
			}

			if (strcasecmp($value, $userAnswer[$key])){
				$result = 'WA'; break;
			}
		}
	}

	return $result;
}

function generateRandomName($length = 5) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
$name = generateRandomName();

if ($lang == "c_cpp"){
	file_put_contents("/tmp/{$name}.cpp", $_POST['code']);

	$process = proc_open('g++ '."/tmp/{$name}.cpp".' -w -o /tmp/'.$name, array(1 => array("pipe", "w"), 2 => array("pipe", "w")), $pipes);
	$content = str_replace ( "/tmp/{$name}.cpp:" , "" ,stream_get_contents($pipes[2]));
	@fclose($pipes[0]);@fclose($pipes[1]);@fclose($pipes[2]);
	$result=proc_close($process);

	@unlink("/tmp/{$name}.cpp");
}
else if ($lang == "python"){
	file_put_contents("/tmp/{$name}.py", $_POST['code']);

	$process = proc_open("python3 ./python/compile.py /tmp/{$name}.py", array(1 => array("pipe", "w"), 2 => array("pipe", "w")), $pipes);
	$content = str_replace ( "File \"/tmp/{$name}.py\"," , "" ,stream_get_contents($pipes[2]));
	@fclose($pipes[0]);@fclose($pipes[1]);@fclose($pipes[2]);
	$result=proc_close($process);

	@unlink("/tmp/{$name}.py");
}

if ($result!=0){
	$ares = array('result' => 'CE', 'cmperr' => $content);
	if ($task['author'] != $uid){
		$model->updateTaskProgress($tid, $uid, $saveData);
	}
	echo json_encode($ares);
	die();
}

$questions_text = $task['testData'];
$answers_text = $task['testAnswer'];
$publicity_text = $task['publicity'];
$names_text = $task['testNames'];
$timeLimit = intval($task['timeLimit'])*250;
$memoryLimit = intval($task['memoryLimit']);

$questions = json_decode($questions_text);
$answers = json_decode($answers_text);
$publicity = json_decode($publicity_text);
$names = json_decode($names_text);
$memoryLimitByte = $memoryLimit+20;
$passedTest = 0;

$testResult = array();
$stime = array(); $smemory = array(); $sresult = array();

foreach ($questions as $key => $value) {
	if ($lang == "c_cpp"){
		$process = proc_open("python3 ./python/test_cpp.py /tmp/{$name} {$timeLimit} {$memoryLimitByte}", array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w")), $pipes);
	}
	else if ($lang == "python"){
		$memoryLimitByte += 2;
		$process = proc_open("python3 ./python/test_python.py /tmp/{$name}.pyc {$timeLimit} {$memoryLimitByte}", array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w")), $pipes);
	}
	if (is_resource($process)) {

		fwrite($pipes[0], $value);
		fclose($pipes[0]);
		$content = stream_get_contents($pipes[1]);

		$data = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);

		$lastBranshPos = strrpos($data, '{');

		if ($lastBranshPos > 0){
			$realStderr = substr($data, 0, $lastBranshPos);
		}
		else{
			$realStderr = '';
		}
		$data = substr($data, $lastBranshPos);

		$result = proc_close($process); 
		$jdata = json_decode($data, true);	
		//print_r($jdata); die();
		
		$stime[] = $jdata['elapsed'];
		$smemory[] = $jdata['mem_info'][1];
		if ($jdata['result'] == 'OK'){
			$res = checkAnswer($content, $answers[$key]);
			$sresult[] = $res;
			if ($res == 'OK'){
				$passedTest++;
			}
			if ($publicity[$key] == 1){
				$testResult[] = array('name' => trim($names[$key]), 'result' => $res, 'stdin' => trim($value), 'answer' => trim($answers[$key]), 'stdout' => trim($content), 'stderr' => trim($realStderr), 'memory' => $jdata['mem_info'][1], 'cpu' => $jdata['elapsed']);
			}
			else{
				$testResult[] = array('name' => trim($names[$key]), 'result' => $res, 'stdin' => '', 'stdout' => '', 'stderr' => '', 'memory' => $jdata['mem_info'][1], 'cpu' => $jdata['elapsed']);
			}
		}
		else{
			$sresult[] = $jdata['result'];
			if ($publicity[$key] == 1){
				$testResult[] = array('name' => trim($names[$key]), 'result' => $jdata['result'], 'stdin' => trim($value), 'answer' => trim($answers[$key]), 'stdout' => trim($content), 'stderr' => trim($realStderr), 'memory' => $jdata['mem_info'][1], 'cpu' => $jdata['elapsed']);
			}
			else{
				$testResult[] = array('name' => trim($names[$key]), 'result' => $jdata['result'], 'stdin' => '', 'stdout' => '', 'stderr' => '', 'memory' => $jdata['mem_info'][1], 'cpu' => $jdata['elapsed']);
			}
		}
	}
}
if ($lang == "c_cpp"){
	unlink("/tmp/{$name}");	
}
else if ($lang == "python"){
	unlink("/tmp/{$name}.pyc");
}

$saveData['passed'] = $passedTest;
$saveData['time'] = json_encode($stime);
$saveData['memory'] = json_encode($smemory);
$saveData['status'] = json_encode($sresult);

$success = $passedTest/count($questions);
$ares = array('result' => $success, 'testCount' => count($questions), 'passedTest' => $passedTest, 'info' => $testResult);
if ($task['author'] != $uid){
	$model->updateTaskProgress($tid, $uid, $saveData);
}
echo json_encode($ares);
