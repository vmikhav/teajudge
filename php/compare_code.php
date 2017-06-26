<?php
if (!isset($teajudgeset)){
	die();
}


function compareCode($model, $tid, $uid, $uidL, $uidR){
	$res = $model->exportCodeToCompare(intval($tid), intval($uid), intval($uidL), intval($uidR));
	$fres = array("status" => -1, "diff" => "");
	if (count($res['results']) == 2){
		$lname = generateRandomString(12);
		$rname = generateRandomString(12);
		file_put_contents("/tmp/{$lname}.txt", $res['results'][$uidL]);
		file_put_contents("/tmp/{$rname}.txt", $res['results'][$uidR]);
		$process = proc_open("git diff --no-index -w -b --ignore-space-at-eol --ignore-blank-lines --no-prefix -U1000 --histogram /tmp/{$lname}.txt /tmp/{$rname}.txt", array(1 => array("pipe", "w")), $pipes);
		$content = stream_get_contents($pipes[1]);
		if ($content != ""){
			$fres['status'] = 1;
			$fres['diff'] = $content;
		}
		else{
			$fres['status'] = 2;
		}
		@fclose($pipes[1]);
		@unlink("/tmp/{$lname}.txt");
		@unlink("/tmp/{$rname}.txt");
	}
	return $fres;
}

?>