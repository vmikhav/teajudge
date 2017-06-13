<?php
$teajudgeset = 1;
include_once './php/check_auth.php';
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
include_once './php/model.php';

$model = new Model();

$up = $model->getUserRole($uid);

$ownCourses = $model->getOwnCourses($uid);
$assignCourses = $model->getUserCourses($uid);

$courseCount = count($ownCourses) + count($assignCourses);
$courses = array();
foreach ($assignCourses as $course) {
	if ($course['endTime']<=time()){continue;}
	$time = intval($course['endTime']) - time();
	$courses[] = array('cid' => $course['cid'], 'own' => 0, 'status' => $course['tcount']==$course['rcount']?0:1, 'cname' => $course['cname'], 'endTime' => $course['endTime'], 'tcount' => $course['tcount'], 'rcount' => $course['rcount'], 'time' => $time);
}
foreach ($ownCourses as $course) {
	$courses[] = array('cid' => $course['cid'], 'own' => 1, 'status' => 2, 'cname' => $course['cname'], 'tcount' => $course['tcount'], 'ucount' => $course['ucount'], 'rcount' => $course['rcount']);
}
foreach ($assignCourses as $course) {
	if ($course['endTime']>time()){continue;}
	$courses[] = array('cid' => $course['cid'], 'own' => 0, 'status' => 3, 'cname' => $course['cname'], 'endTime' => $course['endTime'], 'tcount' => $course['tcount'], 'rcount' => $course['rcount']);
}

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
	<link href="./style/chartist.min.css" rel="stylesheet">
	<link href="./style/courseview.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
	<link href="./style/devicon.min.css" rel="stylesheet">
	<script src="./lang/<?=LANG?>.js"></script>
	

	<title>TeaJudge</title>

</head>
<body>

<div id="globalContainer">
	<div class="header">
		<div class="locationName"><i class="fa fa-list-ul" aria-hidden="true"></i> <?=MY_COURSES?></div>
		<div class="avatar">
			<div id="avatar"><canvas id='avatarCanvas'></canvas></div>
			<div class="dropdown-content">
				<a href="./profile.php"><div class="dropChoice hvr-icon-push noselect profile"><?=PROFILE?></div></a>
				<?php if ($up['cuser']){ ?>
				<a href="./setting.php"><div class="dropChoice hvr-icon-push noselect users"><?=SETTINGS?></div></a>
				<?php }; ?>
				<a href="./controller.php?act=logout"><div class="dropChoice hvr-icon-push noselect logout"><?=LOG_OUT?></div></a>
			</div>
		</div>
		<?php if ($up['ctask']){ ?>
		<div class="topButton noselect afont" id="plus" data-tip="<?=CREATE_COURSE?>"></div>
		<?php }; ?>
	</div>

	<?php if ($courseCount == 0){ ?>

	<div class="blankContent">
		<div class="blankLabel noselect">
			<?=NO_COURSES?>
		</div>
	</div>

	<?php } else{ ?>

	<div class="leftPanel">
		<div class="searchPanel">
			<input type="text" name="name" id="name" class="fuzzy-search" placeholder="Search..."/>
		</div>
	</div>
	<div class="rightPanel">
		<div class="listPanel">
			<div class="courseTitle">
				<?=COURSE_COUNT?> : <span id="courseCount"><?=$courseCount?></span>
			</div>
			<div class="clist">
				<?php
				foreach ($courses as $course) {
					if ($course['own']==0){
						$color = $course['tcount']==$course['rcount']?"green":"orange";
					}
					else{$color = "blue";}
				?>
				
				<div class="taskItem" course-id="<?=$course['cid']?>" own-course="<?=$course['own']?>" course-status="<?=$course['status']?>">
					<div class="taskInfo">
						<div class="taskName"><i class="fa fa-code fa-lg <?=$color?>"></i><a href="./courseview.php?cid=<?=$course['cid']?>" class="tname"><?=$course['cname']?></a></div>
						<div class="taskStatus">
							<?php if ($course['own']==0){?>
							<span data-tip="<?=TASK_COMPLETE?>"><i class="fa fa-check" aria-hidden="true"></i><?=$course['rcount']?></span>
							<?php }?>
							<span data-tip="<?=TASK_ALL?>"><i class="fa fa-puzzle-piece" aria-hidden="true"></i><?=$course['tcount']?></span>
							<?php if ($course['own']==0){?>
							<?php if ($course['status']!=2){?>
							<span data-tip="<?=PARTICIPANT?>"><i class="fa fa-user-o fa-lg" aria-hidden="true"></i></span>
							<span data-tip="<?=COURSE_ENDTIME?>" class="clock"><?=$course['endTime']?></span>
							<?php }?>
							<?php } else{?>
							<span data-tip="<?=USER_COURSE_COMPLETE?>"><i class="fa fa-user-o green" aria-hidden="true"></i><?=$course['rcount']?></span>
							<span data-tip="<?=USER_COURSE_PARTICIPANT?>"><i class="fa fa-user" aria-hidden="true"></i><?=$course['ucount']?></span>
							<span data-tip="<?=AUTHOR?>"><i class="fa fa-wrench fa-lg" aria-hidden="true"></i></span>
							<?php }?>
						</div>
					</div>
					<div class="taskToolbar noselect">
						<?php if ($course['own']==0){?>
						<i class="fa fa-bar-chart toolButton stats" data-tip="<?=STATS?>"></i>
						<?php } else{?>
						<i class="fa fa-pencil-square-o toolButton rename" data-tip="<?=RENAME?>"></i>
						<i class="fa fa-users toolButton access" data-tip="<?=ACCESS_MANAGE?>"></i>
						<?php if ($course['tcount']>0){?>
						<i class="fa fa-bar-chart toolButton stats" data-tip="<?=STATS?>"></i>
						<?php }?>
						<i class="fa fa-trash-o toolButton delete" data-tip="<?=REMOVE?>"></i>
						<?php }?>
					</div>
					<div class="clear"></div>
				</div>

				<?php } ?>
			</div>
		</div>
	</div>

	<footer class="ascii">
		<pre class="no-space noselect">
           boing         boing         boing
 e-e           . - .         . - .         . - .
(\_/)\       '       `.   ,'       `.   ,'       .
 `-'\ `--.___,         . .           . .          .
    '\( ,_.-'
       \\               &#34;             &#34;
       ^'
		</pre>
	</footer>
	<?php }; ?>
</div>
<div id="frontDiv" class="FullPage popupFilter">
	<div id="loader-wrapper"><div id="loader"></div></div>
</div>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="./style/bootstrap-material-datetimepicker.css" rel="stylesheet">
<script src="./js/jquery.mCustomScrollbar.min.js"></script>
<script src="./js/list.min.js"></script>
<script src="./js/moment-with-locales.min.js"></script>
<script src="./js/bootstrap-material-datetimepicker.js"></script>
<script src="./js/chartist.min.js"></script>
<script src="./js/chartist-plugin-pointlabels.js"></script>
<script src="./js/courseAssign.js"></script>
<script src="./js/papaparse.min.js"></script>
<script src="./js/stats.js"></script>

<script>
	var locale = "<?=substr(LANG, 0, 2)?>";

	moment.locale(locale);
	$(".clock").each(function(){$(this).html('<i class="fa fa-clock-o" aria-hidden="true"></i>'+moment.unix($(this).text()).fromNow());});


	$(document).ready(function(){$("#frontDiv").hide();})
	function closePopup(){$("#frontDiv").html("").hide(); }
	jdenticon.drawIcon(document.getElementById('avatarCanvas').getContext('2d'), md5('<?=$avatar?>'), 55);

	<?php if ($courseCount > 0){ ?>
	var courseList = new List('globalContainer', { 
		valueNames: ['tname'],
		listClass: 'clist'
	});
	courseList.on('searchComplete', function(){$("#courseCount").text(courseList.matchingItems.length);});
	<?php }; ?>
	$("#plus").click(function(){setCourseName(-1, "");});
	$(".rename").click(function(){
		var el = $(this).parent().parent();
		setCourseName($(el).attr("course-id"), $(el).find(".tname").text());
	});
	$(".delete").click(function(){
		var el = $(this).parent().parent();
		
		readTextInput(LANG['delete_confirm'], "", function(n){
			if (n.toLowerCase() == LANG['delete']){
				var xhttp = new XMLHttpRequest();
				xhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
						$(el).remove();
						if ($(".taskItem").length == 0){
							location.reload();
						}
					}
				};
				xhttp.open("POST", "controller.php?act=deleteCourse", true);
				xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				xhttp.send("cid="+encodeURIComponent($(el).attr("course-id")));
				console.log($(el).attr("course-id"));
			}
		}, LANG['Delete']);
	});

	function setCourseName(id, name){
		readTextInput("Назва курсу", name, function(n){ 
			var xhttp = new XMLHttpRequest();
			xhttp.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					var res = JSON.parse(this.responseText);
					console.log(this.responseText);
					if (res.status == "renamed"){
						$("[course-id='"+res.cid+"']").find(".tname").text(n);
					}
					else if (res.status == "created"){
						window.location.href = './courseview.php?cid='+res.cid;
					}
				}
			};
			xhttp.open("POST", "controller.php?act="+(id==-1?"createCourse":"renameCourse"), true);
			xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xhttp.send("name="+encodeURIComponent(n)+"&cid="+encodeURIComponent(id));
		});
	}

	

	$(".access").click(function(){
		var cid = $(this).parent().parent().attr("course-id");
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				var res = JSON.parse(this.responseText);
				console.log(res);
				courseAssignDialog(cid, res);
			}
		};
		xhttp.open("POST", "controller.php?act=courseGroupList", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("cid="+encodeURIComponent(cid));
	});
	
	
	$("body").mCustomScrollbar({axis:"y", theme:"light-thin"});
	

	function readTextInput(fieldName, defaultValue, callback, buttonText = LANG['Save']){//, controlValue = "") {
		$("#frontDiv").html('<div class="dialogContainer"><div class="inputContainer"><label for="courseName">'+fieldName+'</label><input type="text" name="courseName" id="courseName" value="'+defaultValue+'"></div><div class="buttonContainer noselect"><span class="button" id="saveButton">'+buttonText+'</span><span class="button blueButton" id="closePopup">'+LANG['Cancel']+'</span></div></div>').show();
		//if (controlValue != ""){$("#courseName").on("input", function(){if ($("#courseName").val().toLowerCase() == controlValue){$("#saveButton").click();}}); }
		$("#courseName").focus();
		$("#courseName").keypress(function (e) {
			var key = e.which;
			if(key == 13){ $("#saveButton").click(); return false;}
		});  
		$("#closePopup").click(closePopup);
		$(".dialogContainer").click(function(event){ event.stopPropagation();});
		$(".popupFilter").click(closePopup);
		$("#saveButton").click(function(){callback($("#courseName").val()); closePopup();})
	}

	$(".stats").click(function(){getStats($(this).parent().parent().attr("course-id"), 2);});
	
</script>

</body>
</html>