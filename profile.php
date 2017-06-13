<?php
$teajudgeset = 1;
include_once './php/check_auth.php';


include_once './php/model.php';

$model = new Model();

$name = $model->getUserName($uid);
$up = $model->getUserRole($uid);
?>

<html lang="<?=LANG?>">

<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<link rel="icon" href="./favicon.ico">
	<meta name="author" content="Volodymyr Mikhav">
	<meta name="keywords" content="sandbox, education, learning">
	<meta name="description" content="<?=DESCRIPTION?>">
	<meta name="robots" content="all">
	<meta name="copyright" content="Volodymyr Mikhav">
	<link href="./style/font-awesome.min.css" rel="stylesheet" media="all">
	<script src="./js/jdenticon-1.4.0.min.js"></script>
	<script src="./js/md5.min.js"></script>
	<link href="./style/style.css" rel="stylesheet">
	<link href="./style/courseview.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
	<script src="./lang/<?=LANG?>.js"></script>
	

	<title><?=PROFILE?> - TeaJudge</title>

</head>
<body>

<div id="globalContainer">
	<div class="header">
		<a href="./courses.php"><div class="locationName"><i class="fa fa-user-o" aria-hidden="true"></i> <?=$name['firstName']." ".$name['lastName'];?></div></a>
		<div class="avatar">
			<div id="avatar"><canvas id='avatarCanvas'></canvas></div>
			<div class="dropdown-content">
				<a href="./courses.php"><div class="dropChoice hvr-icon-push noselect courses" id="courses"><?=MY_COURSES?></div></a>
				<?php if ($up['cuser']){ ?>
				<a href="./setting.php"><div class="dropChoice hvr-icon-push noselect users"><?=SETTINGS?></div></a>
				<?php }; ?>
				<a href="./controller.php?act=logout"><div class="dropChoice hvr-icon-push noselect logout"><?=LOG_OUT?></div></a>
			</div>
		</div>
	</div>


	<div class="blankContent">
		<div class="profile-dialog" id="mainD">
			<div class="buttonContainer noselect">
				<span class="button" id="change-login"><?=CHANGE_LOGIN?></span>
				<span class="button" id="change-pass"><?=CHANGE_PASSWORD?></span>
			</div>
		</div>
		<div class="profile-dialog" id="loginD" style="display: none;">
			<div class="inputContainer">
				<label for="oldPassL"><?=ENTER_PASS?></label>
				<input type="password" name="oldPassL" id="oldPassL" value="">
			</div>
			<div class="inputContainer">
				<label for="newLogin"><?=ENTER_NEW_LOGIN?></label>
				<input type="text" name="newLogin" id="newLogin" value="">
			</div>
			<div class="buttonContainer noselect"><span id="resultL">&#8199;</span></div>
			<div class="buttonContainer noselect">
				<span class="button" id="changeLogin"><?=CHANGE_LOGIN?></span>
				<span class="button backB"><?=BACK?></span>
			</div>
		</div>
		<div class="profile-dialog" id="passD" style="display: none;">
			<div class="inputContainer">
				<label for="oldPassP"><?=ENTER_OLD_PASS?></label>
				<input type="password" name="oldPassP" id="oldPassP" value="">
			</div>
			<div class="inputContainer">
				<label for="newPass"><?=ENTER_NEW_PASS?></label>
				<input type="text" name="newPass" id="newPass" value="">
			</div>
			<div class="buttonContainer noselect"><span id="resultP">&#8199;</span></div>
			<div class="buttonContainer noselect">
				<span class="button" id="changePass"><?=CHANGE_PASSWORD?></span>
				<span class="button backB"><?=BACK?></span>
			</div>
		</div>
	</div>
</div>
<div id="frontDiv" class="FullPage popupFilter">
	<div id="loader-wrapper"><div id="loader"></div></div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

<script>

	$(document).ready(function(){$("#frontDiv").hide();})
	function closePopup(){$("#frontDiv").html("").hide(); }
	jdenticon.drawIcon(document.getElementById('avatarCanvas').getContext('2d'), md5('<?=$avatar?>'), 55);

	$("#change-login").click(function(){
		$("#mainD").hide(); $("#loginD").show();
	});

	$("#change-pass").click(function(){
		$("#mainD").hide(); $("#passD").show();
	});

	$(".backB").click(function(){
		$(this).parent().parent().hide(); $("#mainD").show();
		$("#resultL").html("&#8199;"); $("#resultP").html("&#8199;");
		$("#changeLogin").show(); $("#changePass").show();
		$("#newPass").val("");
	});

	$("#changeLogin").click(function(){
		if (!( /^[A-z0-9]{4,20}$/.test( $("#newLogin").val()) )){
			$("#resultL").text(LANG['Login_pattern']); return;
		}
		$("#frontDiv").show();
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				var res = parseInt(this.responseText);
				if (res == -1){
					$("#resultL").text(LANG['Login_already_used']);
				}
				else if (res == 0){
					$("#resultL").text(LANG['Incorrect_pass']);
				}
				else{
					$("#resultL").text(LANG['Login_change_success']);
					$("#oldPassL").val("");
					$("#changeLogin").hide();
				}
				$("#frontDiv").hide();
			}
		};
		xhttp.open("POST", "controller.php?act=changeUserLogin", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("pass="+encodeURIComponent(md5($("#oldPassL").val()))+"&login="+encodeURIComponent($("#newLogin").val()));
	});

	$("#changePass").click(function(){
		if (!( /^[A-z0-9]{4,20}$/.test( $("#newPass").val()) )){
			$("#resultP").text(LANG['Pass_pattern']); return;
		}
		$("#frontDiv").show();
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				var res = parseInt(this.responseText);
				 if (res == 0){
					$("#resultP").text(LANG['Incorrect_pass']);
				}
				else{
					$("#resultP").text(LANG['Pass_change_success']);
					$("#oldPassP").val("");
					$("#changePass").hide();
				}
				$("#frontDiv").hide();
			}
		};
		xhttp.open("POST", "controller.php?act=changeUserPassword", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("pass="+encodeURIComponent(md5($("#oldPassP").val()))+"&newpass="+encodeURIComponent(md5($("#newPass").val())));
	});

</script>