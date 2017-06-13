
<?php
$teajudgeset = 1;
include_once './php/check_auth.php';


include_once './php/model.php';

$model = new Model();


$up = $model->getUserRole($uid);
if (!$up['cuser']){ header('Location: ./courses.php', true, 303); die(); }

$users  = $model->getUserList($uid);
$groups = $model->getGroupList(0, 1);

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
	<link href="./style/setting.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
	<script src="./lang/<?=LANG?>.js"></script>
	

	<title><?=SETTINGS?> - TeaJudge</title>

</head>
<body>

<div id="globalContainer">
	<div class="header">
		<a href="./courses.php"><div class="hvr-icon-back backButton noselect"><?=BACK?></div></a>
		<div class="avatar">
			<div id="avatar"><canvas id='avatarCanvas'></canvas></div>
			<div class="dropdown-content">
				<a href="./profile.php"><div class="dropChoice hvr-icon-push noselect profile"><?=PROFILE?></div></a>
				<a href="./courses.php"><div class="dropChoice hvr-icon-push noselect courses" id="courses"><?=MY_COURSES?></div></a>
				<a href="./controller.php?act=logout"><div class="dropChoice hvr-icon-push noselect logout"><?=LOG_OUT?></div></a>
			</div>
		</div>
		<div class="topButton noselect afont" id="plus" data-tip="<?=ACCOUNT_IMPORT?>"></div>
	</div>



	<div class="panel leftPanel" id="userPanel">
		<div class="inputContainer filterField noselect">
			<input type="text" name="uname" id="uname" class="fuzzy-search" placeholder="<?=USER_FILTER_PLACEHOLDER?>"/>
			<span class="fa fa-times-circle fa-lg search-clear" id="uclear" style="display: none;"></span>
		</div>
		<div class="inputContainer tableContainer noselect">
			<table class="users-table">
				<thead>
					<tr>
						<th class="check-col">
							<!--<label class="material-checkbox"><input type="checkbox" class="material-checkbox-input header-checkbox"></label>-->
						</th>
						<th class="sort uname-col" data-sort="user-name"><?=NAME?></th>
						<th class="sort" data-sort="user-group-name"><?=GROUP?></th>
					</tr>
				</thead>
				<tbody class="ulist">
				<?php
				foreach ($users as $user) {
				?>
					<tr uid="<?=$user['uid']?>">
						<td><label class="material-checkbox"><input type="checkbox" class="material-checkbox-input row-checkbox"></label></td>
						<td class="user-name"><?="{$user['lastName']} {$user['firstName']}"?></td>
						<td class="user-group-name"><?=$user['gname']?></td>
					</tr>
				<?php }?>
				</tbody>
			</table>
		</div>
		<div id="user-props" style="display: none;">
			<div class="buttonContainer noselect">
				<span class="button button-1x" id="restore"><?=RESET_PASSWORD?></span>
			</div>
			<div class="buttonContainer noselect">
				<span class="button button-1x" id="delete-user"><?=REMOVE?></span>
			</div>
		</div>
		<div class="buttonContainer bottomContainer noselect">
			<span class="button button-1x" id="import"><?=ACCOUNT_IMPORT?></span>
		</div>
	</div>

	<div class="panel rightPanel" id="groupPanel">
		<div class="inputContainer filterField noselect">
			<input type="text" name="gname" id="gname" class="fuzzy-search" placeholder="<?=GROUP_FILTER_PLACEHOLDER?>"/>
			<span class="fa fa-times-circle fa-lg search-clear" id="gclear" style="display: none;"></span>
		</div>
		<div class="inputContainer tableContainer noselect">
			<table class="groups-table">
				<thead>
					<tr><th><?=GROUP?></th></tr>
				</thead>
				<tbody class="glist">
				<?php
				foreach ($groups as $group) {
				?>
					<tr><td>
						<input type="radio" name="chkg" id="chkg<?=$group['gid']?>" gid="<?=$group['gid']?>" <?=$group['gid']==1?'checked':''?>/><label for="chkg<?=$group['gid']?>" class="group-name"><?=$group['gname']?></label>
					</td></tr>
				<?php }?>
				</tbody>
			</table>
		</div>
		<div id="group-props" style="display: none;">
			<div class="buttonContainer noselect">
				<span class="button button-1x" id="delete-group"><?=REMOVE?></span>
			</div>
			<div class="inputContainer noselect">
				<div class="group-perm">
					<div><input type="checkbox" name="chk-user" id="chk-user" checked/><label for="chk-user">&#8199;<?=PERM_USER?></label></div>

					<div><input type="checkbox" name="chk-task" id="chk-task" checked/><label for="chk-task">&#8199;<?=PERM_TASK?></label></div>

					<div><input type="checkbox" name="chk-grant" id="chk-grant" checked/><label for="chk-grant">&#8199;<?=PERM_GRANT?></label></div>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="frontDiv" class="FullPage popupFilter">
	<div id="loader-wrapper"><div id="loader"></div></div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="./js/list.min.js"></script>
<script src="./js/papaparse.min.js"></script>
<script src="./js/stats.js"></script>
<script src="./js/uploader.js"></script>

<script>
	var groupPerm = {
	<?php foreach ($groups as $group): ?>
		<?=$group['gid']?>: {'user':<?=$group['canCreateUser']?>, 'task':<?=$group['canCreateTask']?>, 'grant':<?=$group['canGrant']?>},
	<?php endforeach ?>
	};
	var locale = "<?=substr(LANG, 0, 2)?>";

	var gid = 1;
	var selectedUserCount = 0;

	var userOptions = {valueNames: [ 'user-name', 'user-group-name' ], listClass: 'ulist'};
	var userList = new List('userPanel', userOptions);

	var groupOptions = {valueNames: [ 'group-name' ], listClass: 'glist'};
	var groupList = new List('groupPanel', groupOptions);

	$(document).ready(function(){$("#frontDiv").hide();})
	function closePopup(){$("#frontDiv").html("").hide(); }
	function showLoader(){$("#frontDiv").html('<div id="loader-wrapper"><div id="loader"></div></div>').show();};
	jdenticon.drawIcon(document.getElementById('avatarCanvas').getContext('2d'), md5('<?=$avatar?>'), 55);

	$("#plus").click(ekUpload);
	$("#import").click(ekUpload);
</script>

<script src="./js/user-manager.js"></script>