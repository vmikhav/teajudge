<?php
if (!isset($teajudgeset)){
	die();
}
include_once './php/jwt_helper.php';
include_once './php/sensetive_data.php';
include_once './php/lang/'.LANG.'.php';

date_default_timezone_set("UTC");


$uid = 0;
$avatar = 0;
if (isset($_COOKIE['jwt'])){
	try {
		$jwtData = (array) JWT::decode($_COOKIE['jwt'], TJSECRET);
		if (array_key_exists('id', $jwtData) && $jwtData['id'] > 0){
			if (!array_key_exists('eat', $jwtData) || $jwtData['eat'] > time()){
				$uid = $jwtData['id'];
				$avatar = $jwtData['hash'];
			}
		}
	} catch (Exception $e) {
		$uid = 0;
	}
}

if (!isset($noRedirect)){
	if ($uid == 0){
		if (!isset($indexP)){
			header('Location: ./', true, 303);
			die();
		}
	}
	else{
		if (isset($indexP)){
			header('Location: ./courses.php', true, 303);
			die();
		}
	}
}

?>