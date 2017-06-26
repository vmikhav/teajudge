<?php
$tid = intval($_GET['tid']);
if ($tid < 1){ header('Location: ./courses.php', true, 303); die();}

$teajudgeset = 1;
include_once './php/check_auth.php';

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
include_once './php/model.php';
$model = new Model();

include_once './php/compare_code.php';

$up = $model->getUserRole($uid);

$stats = $model->exportStats($tid, 1, $uid);

if (count($stats) < 1 || $stats[0]['isAuthor'] == 0 || count($stats[0]['results']) < 2){
	header('Location: ./courses.php', true, 303); die();
}

$cid = intval($stats[0]['cid']);
$users = array();
foreach ($stats[0]['results'] as $value) {
	$users[] = array("uid" => $value['uid'], "name" => $value['lastName'].' '.$value['firstName'], "gname" => $value['gname']);
}

$diff = compareCode($model, $tid, $uid, $users[0]['uid'], $users[1]['uid']);
$diff['diff'] = str_replace("\n", "\\n", addslashes($diff['diff']));

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
	<link href="./style/jquery.mCustomScrollbar.min.css" rel="stylesheet">
	<link href="./style/font-awesome.min.css" rel="stylesheet" media="all">
	<script src="./js/jdenticon-1.4.0.min.js"></script>
	<script src="./js/md5.min.js"></script>
	<link href="./style/style.css" rel="stylesheet">
	<link href="./style/courseview.css" rel="stylesheet">
	<link href="./style/compare.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
	<link href="./style/devicon.min.css" rel="stylesheet">
	<script src="./lang/<?=LANG?>.js"></script>
	

	<title><?=COMPARE?> - Teajudge</title>

</head>
<body>

<div id="globalContainer">
	<div class="header">
		<a href="./courseview.php?cid=<?=$cid?>"><div class="hvr-icon-back backButton noselect"><?=BACK?></div></a>
		<div class="avatar">
			<div id="avatar"><canvas id='avatarCanvas'></canvas></div>
			<div class="dropdown-content">
				<a href="./profile.php"><div class="dropChoice hvr-icon-push noselect profile"><?=PROFILE?></div></a>
				<a href="./courses.php"><div class="dropChoice hvr-icon-push noselect courses" id="courses"><?=MY_COURSES?></div></a>
				<?php if ($up['cuser']){ ?>
				<a href="./setting.php"><div class="dropChoice hvr-icon-push noselect users"><?=SETTINGS?></div></a>
				<?php }; ?>
				<a href="./controller.php?act=logout"><div class="dropChoice hvr-icon-push noselect logout"><?=LOG_OUT?></div></a>
			</div>
		</div>
	</div>

	<div class="user-lists">
		<div class="user-select" id="left-user-list">
			<div class="inputContainer filterField noselect">
				<input type="text" name="uname" id="lname" class="fuzzy-search" placeholder="<?=USER_FILTER_PLACEHOLDER?>"/>
				<span class="fa fa-times-circle fa-lg search-clear" id="uclear" style="display: none;"></span>
			</div>
			<div class="inputContainer tableContainer noselect">
				<table class="users-table">
					<thead>
						<tr>
							<th class="check-col"></th>
							<th class="sort uname-col" data-sort="user-name"><?=NAME?></th>
							<th class="sort" data-sort="user-group-name"><?=GROUP?></th>
						</tr>
					</thead>
					<tbody class="ulist" id="llist">
					<?php
					$i = 0;
					foreach ($users as $user) {
						$i++;
					?>
						<tr uid="<?=$user['uid']?>">
							<td><label class="material-checkbox"><input type="checkbox" class="material-checkbox-input row-checkbox" <?=$i==1 ? 'checked':''?> ></label></td>
							<td class="user-name"><?=$user['name']?></td>
							<td class="user-group-name"><?=$user['gname']?></td>
						</tr>
					<?php }?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="user-select" id="right-user-list">
			<div class="inputContainer filterField noselect">
				<input type="text" name="uname" id="rname" class="fuzzy-search" placeholder="<?=USER_FILTER_PLACEHOLDER?>"/>
				<span class="fa fa-times-circle fa-lg search-clear" id="uclear" style="display: none;"></span>
			</div>
			<div class="inputContainer tableContainer noselect">
				<table class="users-table">
					<thead>
						<tr>
							<th class="check-col"></th>
							<th class="sort uname-col" data-sort="user-name"><?=NAME?></th>
							<th class="sort" data-sort="user-group-name"><?=GROUP?></th>
						</tr>
					</thead>
					<tbody class="ulist" id="rlist">
					<?php
					$i=0;
					foreach ($users as $user) {
						$i++;
					?>
						<tr uid="<?=$user['uid']?>">
							<td><label class="material-checkbox"><input type="checkbox" class="material-checkbox-input row-checkbox" <?=$i==2 ? 'checked':''?>></label></td>
							<td class="user-name"><?=$user['name']?></td>
							<td class="user-group-name"><?=$user['gname']?></td>
						</tr>
					<?php }?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="code-review">
		<div id="line-by-line"></div>
	</div>

	
</div>
<div id="frontDiv" class="FullPage popupFilter">
	<div id="loader-wrapper"><div id="loader"></div></div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="./js/jquery.mCustomScrollbar.min.js"></script>
<script src="./js/list.min.js"></script>
<script src="./js/highlight/highlight.pack.js"></script>
<link href="./style/github.css" rel="stylesheet">

<link rel="stylesheet" type="text/css" href="./style/diff2html.css">
<script type="text/javascript" src="./js/diff2html.min.js"></script>
<script type="text/javascript" src="./js/diff2html-ui.min.js"></script>


<script>
	var locale = "<?=substr(LANG, 0, 2)?>";
	var luid = <?=$users[0]['uid']?>, ruid = <?=$users[1]['uid']?>;
	var tid = <?=$tid?>;

	jdenticon.drawIcon(document.getElementById('avatarCanvas').getContext('2d'), md5('<?=$avatar?>'), 55);
	$("body").mCustomScrollbar({axis:"y", theme:"light-thin"});
	$(document).ready(function(){$("#frontDiv").hide();})
	var diff = "<?=$diff['diff']?>";

	function showDiff(diff){
		if (diff == ""){
			$('#line-by-line').html('<div class="buttonContainer"><span class="no-diff"><?=NODIFF?></span></div>');
		}
		else{
			var diff2htmlUi = new Diff2HtmlUI({diff: diff});
			diff2htmlUi.draw('#line-by-line', {outputFormat:"side-by-side", showFiles: false, matching: 'words'});
			diff2htmlUi.highlightCode('#line-by-line');
		}
	}
	showDiff(diff);

	function compareCode(){
		if (luid != ruid){
			$("#frontDiv").show();
			var xhttp = new XMLHttpRequest();
			xhttp.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					var res = this.responseText;
					showDiff(JSON.parse(res)['diff']);
					$("#frontDiv").hide();
				}
			};
			xhttp.open("POST", "controller.php?act=compareCode", true);
			xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xhttp.send("tid="+encodeURIComponent(parseInt(tid))+"&luid="+encodeURIComponent(parseInt(luid))+"&ruid="+encodeURIComponent(parseInt(ruid)));
		}
		else{
			$('#line-by-line').html('');
		}
	}

	var userOptions = {valueNames: [ 'user-name', 'user-group-name' ], listClass: 'ulist'};
	var lList = new List('left-user-list',  userOptions);
	var rList = new List('right-user-list', userOptions);
	lList.on('searchComplete', function(){
		var tr;
		for (var i=0; i<lList.matchingItems.length; i++){
			tr = lList.matchingItems[i].elm;
			$(tr).find('.row-checkbox').prop('checked', $(tr).attr('uid') == luid);
		}
	});
	rList.on('searchComplete', function(){
		var tr;
		for (var i=0; i<rList.matchingItems.length; i++){
			tr = rList.matchingItems[i].elm;
			$(tr).find('.row-checkbox').prop('checked', $(tr).attr('uid') == ruid);
		}
	});

	function swapSelect(){
		$('.users-table').find('.row-checkbox:checked').prop('checked', 0);
		$('#left-user-list  tr[uid="'+luid+'"]').find('.row-checkbox').prop('checked', 1);
		$('#right-user-list tr[uid="'+ruid+'"]').find('.row-checkbox').prop('checked', 1);
	}

	$('input.row-checkbox').click(function(e){
		$(this).prop('checked', !($(this)[0].checked));
		$(this).parent().click();
	});
	$('.ulist tr').click(function(){
		var el = $(this).find('.row-checkbox');
		if (!$(el).prop('checked')){
			var parent = $(this).parent();
			$(parent).find('.row-checkbox:checked').prop('checked', 0);
			var uid;
			if ($(parent)[0].id == 'llist'){
				uid = parseInt($(this).attr('uid'));
				if (uid == ruid){ ruid = luid; luid = uid; swapSelect(); }
				else{ luid = uid;}
			}
			else{
				uid = parseInt($(this).attr('uid'));
				if (uid == luid){ luid = ruid; ruid = uid; swapSelect(); }
				else{ ruid = uid;}
			}
			compareCode();
		}
		$(el).prop('checked', 1);
	});
	$('.fuzzy-search').on('input', function(){
		$(this).next().toggle(!!this.value);
	});
	$('.search-clear').click(function(){
		var event = new Event('input', {'bubbles': true,'cancelable': true});
		var el = $(this).prev().val(""); $(el).get(0).dispatchEvent(event);
		if ($(el).parent().parent().get(0).id == "left-user-list"){
			lList.fuzzySearch();
		}
		else{ rList.fuzzySearch(); }
	});




</script>

</body>
</html>