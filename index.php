<?php 

$teajudgeset = 1; $indexP = 1;
include_once './php/check_auth.php';

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
?>

<!DOCTYPE html>
<html lang="<?=LANG?>">

<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<title>Teajudge</title>
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<link rel="icon" href="./favicon.ico">
	<meta name="author" content="Volodymyr Mikhav">
	<meta name="keywords" content="sandbox, education, learning">
	<meta name="description" content="<?=DESCRIPTION?>">
	<meta name="robots" content="all">
	<meta name="copyright" content="Volodymyr Mikhav">
	<link rel='stylesheet prefetch' href='https://fonts.googleapis.com/icon?family=Material+Icons'>
	<link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
	<link href="./style/index.css" rel="stylesheet">
	<script src="./lang/<?=LANG?>.js"></script>
</head>
<body>

<div id="signin">
	<div class="form-title"><?=LOG_IN?></div>
	<div class="input-field">
		<input type="text" id="login" autocomplete="off"/>
		<i class="material-icons noselect">person</i>
		<label for="login" class="noselect">Login</label>
	</div>
	<div class="input-field">
		<input type="password" id="password"/>
		<i class="material-icons noselect">lock</i>
		<label for="password" class="noselect">Password</label>
	</div>
	<div>
	<input type="checkbox" name="cb" id="stay" />
		<label for="stay" class="stay-label noselect"><?=REMEMBER_ME?></label>
	</div>
	<!--
	<a href="" class="forgot-pw"><?=FORGOT_PASSWORD?></a>
	-->
	<button class="login noselect underline">Log in</button>
	<div class="check">
		<div class="loader">
			<span class="loader-block"></span>
			<span class="loader-block"></span>
			<span class="loader-block"></span>
			<span class="loader-block"></span>
			<span class="loader-block"></span>
			<span class="loader-block"></span>
			<span class="loader-block"></span>
			<span class="loader-block"></span>
			<span class="loader-block"></span>
		</div>
		<i class="material-icons" id="ok" style="display: none;">check</i>
		<i class="material-icons" id="error" style="display: none;">highlight_off</i>
	</div>

</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="./js/md5.min.js"></script>

<script>
$("#login").focus();
$("input").on('focusout', function(){
	$(this).each(function(i, e){
		if($(e).val() != ""){
			$(e).addClass('not-empty');
		}else{
			$(e).removeClass('not-empty');
		}
	});
});

$(document).keypress(function(e) {
		if(e.which == 13) {
				$(".login").focus();
				$(".login").click();
		}
});

$(".login").on('click', function(){
	if ($("#login").val() == ""){$("#login").focus(); return 0;}
	if ($("#password").val() == ""){$("#password").focus(); return 0;}
	
	$(this).animate({
		fontSize : 0
	}, 300, function(){
		$(".check").addClass('in');
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (this.responseText == 'ok'){
					$(".loader").hide(); setTimeout( () => $("#ok").show(), 25);
					if (window.location.search.substr(1) == ""){
						setTimeout(() => window.location.href = "./courses.php", 1000);
					}
					else{
						window.close();
					}
				}
				else{
					$(".loader").hide(); setTimeout( () => $("#error").show(), 25);
					setTimeout(function(){
						$(".check").removeClass('in');
						$(".login").animate({	fontSize : 16 }, 300);
						$(".loader").show();$("#error").hide();
					}, 3000);
				}
			}
		};
		xhttp.open("POST", "controller.php?act=login", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("login="+encodeURIComponent($("#login").val())+"&pass="+encodeURIComponent(md5($("#password").val()))+"&stay="+encodeURIComponent($("#stay").is(':checked')?1:0));
	});
});
</script>

</body>
</html>