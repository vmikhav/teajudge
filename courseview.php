<?php
$cid = intval($_GET['cid']);
if ($cid < 1){ header('Location: ./courses.php', true, 303); die();}

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

$course = $model->getCourseContent($cid, $uid);

if (array_key_exists('endTime', $course)){
	$time = intval($course['endTime']);
}
else{
	$time = "";
}
if (array_key_exists('task', $course)){
	$taskCount = count($course['task']);
}
else{
	$taskCount = 0;
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
	

	<title><?=$course['cname']?></title>

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
				<?php if ($up['cuser']){ ?>
				<a href="./setting.php"><div class="dropChoice hvr-icon-push noselect users"><?=SETTINGS?></div></a>
				<?php }; ?>
				<a href="./controller.php?act=logout"><div class="dropChoice hvr-icon-push noselect logout"><?=LOG_OUT?></div></a>
			</div>
		</div>
		<?php if ($taskCount){ ?>
		<div class="topButton noselect afont" id="rating" data-tip="<?=RATING?>" course-id="<?=$cid?>"></div>
		<?php }; ?>
		<?php if (array_key_exists('isAuthor', $course) && $course['isAuthor']){?>
		<div class="topButton noselect afont" id="plus" data-tip="<?=CREATE_TASK?>" course-id="<?=$cid?>"></div>
		<?php }; ?>
	</div>

	<?php if ($taskCount == 0){ ?>

	<div class="blankContent">
		<div class="blankLabel noselect">
			<?=NO_TASK?>
		</div>
	</div>

	<?php } else { ?>
	
	<div class="leftPanel">
		<div class="searchPanel">
			<input type="text" name="name" id="name" class="fuzzy-search" placeholder="Search..."/>
		</div>
	</div>
	<div class="rightPanel">
		<div class="listPanel">
			<div class="courseTitle">
				<?=$course['cname']?>
				<?php if ($course['isAuthor']){?>
				<div class="infoSpan" data-tip="<?=TASK_ALL?>"><i class="fa fa-file-o" aria-hidden="true"></i> <?=$taskCount?></div>
				<div class="infoSpan" data-tip="<?=PARTICIPANT_ALL?>"><i class="fa fa-user-o" aria-hidden="true"></i> <?=$course['ucount']?></div>
				<?php } else {
					$rtask = 0; $btask = 0; $ntask = 0;
					foreach ($course['task'] as $task) {
						if ($task['passed'] == -1){$ntask++;}
						elseif ($task['passed'] == $task['testCount']){$rtask++;}
						else{$btask++;} 
					}
				?>
				<?php if ($rtask){?>
				<div class="infoSpan" data-tip="<?=COMPLETED_TASK?>"><i class="fa fa-check green" aria-hidden="true"></i> <?=$rtask?></div>
				<?php }; ?>
				
				<?php if ($btask){?>
				<div class="infoSpan" data-tip="<?=UNCOMPLETED_TASK?>"><i class="fa fa-exclamation orange" aria-hidden="true"></i> <?=$btask?></div>
				<?php }; ?>
				
				<?php if ($ntask){?>
				<div class="infoSpan" data-tip="<?=NEW_TASK?>"><i class="fa fa-file-o" aria-hidden="true"></i> <?=$ntask?></div>
				<?php }; ?>
				
				<?php if ($time != ""){?>
				<div class="infoSpan clock" data-tip="<?=COURSE_ENDTIME?>"><i class="fa fa-clock-o" aria-hidden="true"></i> <?=$time?></div>
				<?php }; ?>

				<?php }; ?>
			</div>
			<div class="clist">
				<?php
				foreach ($course['task'] as $task) {
					$taskIcon = ""; $taskColor = "blue";
					switch ($task['lang']) {
						case 'c_cpp':
							$taskIcon = "devicon-cplusplus-plain";
							break;
						case 'python':
							$taskIcon = "devicon-python-plain";
								break;
					}

					if ($course['isAuthor']){
						if ($task['upassed'] == $course['ucount']){$taskColor = "green";}
					}
					else{
						if ($task['passed']>=0){
							if ($task['passed'] == $task['testCount']){ $taskColor = "green"; }
							else{$taskColor = "orange";}
						}
					}
				?>
				<div class="taskItem" task-id="<?=$task['tid']?>">
					<div class="taskInfo">
						<div class="taskName"><i class="<?=$taskIcon?> devicon <?=$taskColor?>" aria-hidden="true"></i><a href="./task.php?tid=<?=$task['tid']?>" class="tname"><?=$task['tname']?></a></div>
						<div class="taskStatus">
							<?php  if ($course['isAuthor']){?>
							<span data-tip="<?=USER_TASK_COMPLETE?>"><i class="fa fa-user-o green" aria-hidden="true"></i><?=$task['upassed']?></span>
							<?php } elseif ($task['passed']>=0){?>
							<span data-tip="<?=COMPLETED_TEST?>"><i class="fa fa-check" aria-hidden="true"></i><?=$task['passed']?></span>
							<?php } ?>
							<span data-tip="<?=ALL_TEST?>"><i class="fa fa-question-circle-o" aria-hidden="true"></i><?=$task['testCount']?></span>
						</div>
					</div>
					<div class="taskToolbar noselect">
						<?php if ($course['isAuthor']){?>
						<i class="fa fa-pencil-square-o toolButton edit" data-tip="<?=EDIT?>"></i>
						<i class="fa fa-bar-chart toolButton stats" data-tip="<?=STATS?>"></i>
						<i class="fa fa-trash-o toolButton delete" data-tip="<?=REMOVE?>"></i>
						<?php } else {?>
						<i class="fa fa-bar-chart toolButton stats" data-tip="<?=STATS?>"></i>
						<?php };?>
					</div>
					<div class="clear"></div>
				</div>
				<?php }; ?>
			</div>
		</div>
	</div>
	<footer class="ascii">
		<pre class="no-space noselect">
               _.      z z
          .__/|_. z z          z          
            -&#34;)\                      z
        __ //  )        
          &#34;&#34;/  \-=,,._
            `;;'   `'&#34;`    -.__             ___   __
                                &#34; ~ -  __&#34;&#34;&#34;         &#34;&#34; --_
		</pre>
	</footer>
	<?php };?>
</div>
<div id="frontDiv" class="FullPage popupFilter">
	<div id="loader-wrapper"><div id="loader"></div></div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="./js/jquery.mCustomScrollbar.min.js"></script>
<script src="./js/ace/ace.js" type="text/javascript" charset="utf-8"></script>
<script src="./js/ace/theme-tomorrow_night.js" type="text/javascript" charset="utf-8"></script>
<script src="./js/ace/mode-c_cpp.js" type="text/javascript" charset="utf-8"></script>
<script src="./js/ace/snippets/c_cpp.js" type="text/javascript" charset="utf-8"></script>
<script src="./js/ace/ext-language_tools.js"></script>
<script src="./js/ace/ext-spellcheck.js"></script>
<script src="./js/ace/ext-static_highlight.js"></script>
<script src="./js/list.min.js"></script>
<script src="./js/moment-with-locales.min.js"></script>
<script src="./js/highlight/highlight.pack.js"></script>
<link href="./style/tomorrow-night.css" rel="stylesheet">

<script type="text/x-mathjax-config">
MathJax.Hub.Config({
	styles: {
		".MathJax_SVG svg > g, .MathJax_SVG_Display svg > g": {
			fill: "#FFF", stroke: "#FFF"
		}
	},
	SVG: { scale: 120, font: "Neo-Euler"},
});
</script>
<!--<script src="./js/MathJax/MathJax.js?config=TeX-AMS_SVG"></script>-->
<script src="//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/MathJax.js?config=TeX-AMS_SVG"></script>
<script src="./js/showdown.min.js"></script>
<script src="./js/highlight/highlight.pack.js"></script>
<link href="./style/tomorrow-night.css" rel="stylesheet">
<script src="./js/chartist.min.js"></script>
<script src="./js/chartist-plugin-pointlabels.js"></script>
<script src="./js/editTask.js"></script>
<script src="./js/papaparse.min.js"></script>
<script src="./js/stats.js"></script>

<script>
//	tex2jax: { inlineMath: [['$','$'],['\\(','\\)']] }
	var locale = "<?=substr(LANG, 0, 2)?>";
	moment.locale(locale);
	$(".clock").each(function(){$(this).html('<i class="fa fa-clock-o" aria-hidden="true"></i> '+moment.unix($(this).text()).fromNow());});

	jdenticon.drawIcon(document.getElementById('avatarCanvas').getContext('2d'), md5('<?=$avatar?>'), 55);

	<?php if ($taskCount){ ?>
	var courseList = new List('globalContainer', { 
		valueNames: ['tname'],
		listClass: 'clist'
	});
	<?php }; ?>
	
	$("body").mCustomScrollbar({axis:"y", theme:"light-thin"});

	$(document).ready(function(){$("#frontDiv").hide();})

	$("#plus").click(function(){editTask(-1, '', '', '', '', '', 5, 5, [], parseInt("<?=$_COOKIE['lid']?>"), "", []);});

	function closePopup(){$("#frontDiv").html("").hide();}

	$(".edit").click(function(){
		var tid = $(this).parent().parent().attr("task-id");

		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				var x = JSON.parse(this.responseText);
				var y = JSON.parse(x.statement);
				x.testAnswer = JSON.parse(x.testAnswer);
				x.testData = JSON.parse(x.testData);
				x.testNames = JSON.parse(x.testNames);
				var tcount = parseInt(x.testCount);
				var testCases = [];
				for (var i=0; i<tcount; i+=2){
					testCases.push([x.testNames[i], x.testData[i], x.testAnswer[i], x.testNames[i+1], x.testData[i+1], x.testAnswer[i+1]]);
				}
				$("#frontDiv").html("").hide();
				editTask(x.tid, x.tname, y[0], y[1], y[2], y[3], x.timeLimit, x.memoryLimit, testCases, parseInt(x.lid), x.pattern, JSON.parse(x.ranges));
			}
		};
		xhttp.open("POST", "controller.php?act=getTaskContent", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("tid="+encodeURIComponent(tid));
		$("#frontDiv").html('<div id="loader-wrapper"><div id="loader"></div></div>').show();
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
				xhttp.open("POST", "controller.php?act=deleteTask", true);
				xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				xhttp.send("tid="+encodeURIComponent($(el).attr("task-id")));
			}
		}, LANG['Delete']);
	});
	function readTextInput(fieldName, defaultValue, callback, buttonText = LANG['Save']){
		$("#frontDiv").html('<div class="dialogContainer"><div class="inputContainer"><label for="courseName">'+fieldName+'</label><input type="text" name="courseName" id="courseName" value="'+defaultValue+'"></div><div class="buttonContainer noselect"><span class="button" id="saveButton">'+buttonText+'</span><span class="button blueButton" id="closePopup">'+LANG['Cancel']+'</span></div></div>').show();
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

	$(".stats").click(function(){getStats($(this).parent().parent().attr("task-id"), 1);});
	$("#rating").click(function(){getStats($(this).attr("course-id"), 2);});
	

</script>

</body>
</html>