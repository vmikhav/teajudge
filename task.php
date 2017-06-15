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

$up = $model->getUserRole($uid);

$task = $model->getUserTaskContent($tid, $uid);

if (is_null($task)){header('Location: ./courses.php', true, 303); die();}

$task['statement'] = json_decode($task['statement']);
$task['testData'] = json_decode($task['testData']);
$task['testAnswer'] = json_decode($task['testAnswer']);

function replaceSpecialChar($str){
	$str = str_replace("\r", "", $str);
	$str = str_replace("\n", "\\n", $str);
	$str = str_replace("\t", "\\t", $str);
	$str = str_replace("\"", "\\\"", $str);	
	return $str;
}

$task['pattern'] = replaceSpecialChar($task['submissionCode']);

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
	<link href="./style/task.css" rel="stylesheet">
	<link href="./style/style.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
	<script src="./lang/<?=LANG?>.js"></script>
	

	<title><?=$task['tname']?></title>

</head>
<body>

<div id="globalContainer">
	<div class="header">
		<a href="./courseview.php?cid=<?=intval($task['cid'])?>"><div class="hvr-icon-back backButton noselect"><?=BACK?></div></a>
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
		<!--<div class="topButton noselect afont" id="help" data-tip="<?=HELP?>"></div>-->
		<?php if ($task['canSubmit']){?>
		<?php if ($task['isAuthor'] == 0){?>
		<div class="topButton noselect afont" id="save" data-tip="<?=SAVE?>"></div>
		<?php } ?>
		<div class="topButton noselect afont" id="start" data-tip="<?=RUN?> (F9)"></div>
		<?php } ?>
	</div>
	<div class="leftPanel">
		<div class="panelTop">
			<div class="panelName taskName" task-id="<?=$task['tid']?>"><?=$task['tname']?></div>
		</div>
		<div class="leftPanelContent" id="taskText">
		</div>
	</div>
	<div class="rightPanel">
		<div class="panelTop">
			<div class="panelName"><?=SOLUTION?> :</div>
		</div>
		<div id="editor"></div>
		<div class="panelTop">
			<div class="panelName"><?=OUTPUT?> :</div>
		</div>
		<div id="log">
			<div id='logText'></div>
		</div>
	</div>
</div>
<div id="frontDiv" class="FullPage popupFilter">
	<div id="loader-wrapper"><div id="loader"></div></div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="./js/ace/ace.js" type="text/javascript" charset="utf-8"></script>
<script src="./js/ace/theme-cgdark.js" type="text/javascript" charset="utf-8"></script>
<script src="./js/ace/theme-tomorrow_night.js" type="text/javascript" charset="utf-8"></script>
<script src="./js/ace/mode-c_cpp.js" type="text/javascript" charset="utf-8"></script>
<script src="./js/ace/snippets/c_cpp.js" type="text/javascript" charset="utf-8"></script>
<script src="./js/ace/ext-language_tools.js"></script>
<script src="./js/ace/ext-spellcheck.js"></script>
<script src="./js/ace/ext-static_highlight.js"></script>
<script src="./js/jquery.mCustomScrollbar.min.js"></script>
<script src="./js/showdown.min.js"></script>
<script src="./js/highlight/highlight.pack.js"></script>
<link href="./style/tomorrow-night.css" rel="stylesheet">
<script type="text/x-mathjax-config">
MathJax.Hub.Config({
	styles: {
		".MathJax_SVG svg > g, .MathJax_SVG_Display svg > g": {
			fill: "#FFF", stroke: "#FFF"
		}
	},
	SVG: { scale: 120, font: "Neo-Euler"}
});
</script>
<!--<script src="./js/MathJax/MathJax.js?config=TeX-AMS_SVG"></script>-->
<script src="//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/MathJax.js?config=TeX-AMS_SVG"></script>

<script>
	var previewText = "";
	var task = "<?=replaceSpecialChar($task['statement'][0])?>";
	var iformat = "<?=replaceSpecialChar($task['statement'][1])?>";
	var oformat = "<?=replaceSpecialChar($task['statement'][2])?>";
	var tbounds = "<?=replaceSpecialChar($task['statement'][3])?>";
	<?php if ($task['testCount']>2){?>
		var testQuery = ["<?=replaceSpecialChar($task['testData'][0])?>", "<?=replaceSpecialChar($task['testData'][2])?>"];
		var testAnswer = ["<?=replaceSpecialChar($task['testAnswer'][0])?>", "<?=replaceSpecialChar($task['testAnswer'][2])?>"];
	<?php } else {?>
		var testQuery = ["<?=replaceSpecialChar($task['testData'][0])?>"];
		var testAnswer = ["<?=replaceSpecialChar($task['testAnswer'][0])?>"];
	<?php } ?>

	if (task != ""){
		previewText += "###"+LANG['Task']+"\n"+task+"\n\n";
	}
	if (iformat != "" && oformat != ""){
		previewText += "###"+LANG['Input_format']+"\n"+iformat+"\n\n###"+LANG['Output_format']+"\n"+oformat+"\n\n";	
		if (tbounds != ""){
			previewText += "###"+LANG['Bounds']+"\n"+tbounds+"\n\n";		
		}
	}
	previewText += "###"+LANG['Resourses']+"\n- "+LANG['Time']+" : "+(<?=$task['timeLimit']?>*250)+" ms\n- "+LANG['Memory']+" : "+(1<<<?=$task['memoryLimit']?>)+" Mb\n";

	
	testQuery[0]  = testQuery[0].replace(/\r?\n/g, "<br>");
	testAnswer[0] = testAnswer[0].replace(/\r?\n/g, "<br>");
	previewText += "\n| Input | Output |\n|--|--|\n|"+testQuery[0]+"|"+testAnswer[0]+"|\n";
	if (testQuery.length>1){
		
		testQuery[1]  = testQuery[1].replace(/\r?\n/g, "<br>");
		testAnswer[1] = testAnswer[1].replace(/\r?\n/g, "<br>");
		previewText += "|"+testQuery[1]+"|"+testAnswer[1]+"|\n";
	}
	
	var code = "<?=$task['pattern']?>";
	var ranges = JSON.parse("<?=$task['ranges']?>");

	var lid = <?=$task['lid']?>;
	var lcode = "<?=$task['lcode']?>";
</script>
<script src="./js/editor.js"></script>

<script>
	var converter = new showdown.Converter({tables:true});
	$("#taskText").html(converter.makeHtml(previewText));
	$('pre code').each(function(i, block) {hljs.highlightBlock(block);});
	$('#taskText').mCustomScrollbar({autoHideScrollbar:true, theme:"light-thin"});

	jdenticon.drawIcon(document.getElementById('avatarCanvas').getContext('2d'), md5('<?=$avatar?>'), 55);
	
	$("body").mCustomScrollbar({axis:"xy", theme:"light-thin"});

	$("#log").mCustomScrollbar({autoHideScrollbar:true, axis:"xy", theme:"light-thin"});

	$(document).ready(function(){$("#frontDiv").hide();})

	var log = document.getElementById('logText');
	var logDiv = document.getElementById('log');	

	$("#start").click(execCode);
	$("#save").click(saveCode);
</script>
</body>
</html>