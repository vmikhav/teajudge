function editTask(tid, name, task, inputFormat, outputFormat, bounds, time, mem, testcases, lang, code, ranges){

	lang = lang<1?1:lang>2?2:lang;

	var formHtml = '<div class="dialogContainer">\
						<div class="inputContainer">\
							<label for="taskName">'+LANG['Task_name']+'</label>\
							<input type="text" name="taskName" id="taskName" value="' + name + '">\
						</div>';
	formHtml += '<div class="inputContainer"><label>'+LANG['Formulation_of_the_problem']+'</label><textarea id="ttask">'+task+'</textarea></div>';
	formHtml += '<div class="inputContainer"><label>'+LANG['Input_description']+'</label><textarea id="iformat">'+inputFormat+'</textarea></div>';
	formHtml += '<div class="inputContainer"><label>'+LANG['Output_description']+'</label><textarea id="oformat">'+outputFormat+'</textarea></div>';
	formHtml += '<div class="inputContainer"><label>'+LANG['Bounds']+'</label><textarea id="tbounds">'+bounds+'</textarea></div>';
	formHtml += '<div class="inputContainer">\
					<label>'+LANG['Resourses']+'</label>\
					<div class="resourses">\
						<span>'+LANG['Time']+'</span>\
						<div class="range" id="timerange"></div>\
						<span class="rangeText" id="timeval">1250 ms</span>\
					</div>\
					<div class="resourses">\
						<span>'+LANG['Memory']+'</span>\
						<div class="range" id="memrange"></div>\
						<span class="rangeText" id="memval">32 Mb</span>\
					</div>\
				</div>';
	formHtml += '<div class="inputContainer" id=""><label>'+LANG['Test_data']+'</label><div class="testContainer">';

	var testCounter = 1; var realTestCount = 0;
	if (testcases.length==0){
		formHtml += generateTestForm('', '', '', '', '', '');
	}
	else{
		for (var i=0; i<testcases.length; i++){
			formHtml += generateTestForm(testcases[i][0], testcases[i][1], testcases[i][2], testcases[i][3], testcases[i][4], testcases[i][5]);		
		}
	}
	formHtml += '</div></div><div class="buttonContainer noselect"><span class="button" id="addTest">'+LANG['Add_test']+'</span></div>';

	formHtml += '<div class="inputContainer"><label>'+LANG['Answer_template']+'</label><ul class="langFlex noselect">';
	formHtml += '<li><input type="radio" name="chkcb" id="chkcb1"/><label for="chkcb1">C/C++</label></li>';
	formHtml += '<li><input type="radio" name="chkcb" id="chkcb2"/><label for="chkcb2">Python</label></li>';
	formHtml += '</ul><div class="editor-block">\
				<div id="patternEditor"></div>\
				<div class="editor-button-container noselect">\
					<div class="editor-button" id="remove-range">\
						<i class="fa fa-unlock" aria-hidden="true"></i> \
					</div>\
					<div class="editor-button" id="add-range">\
						<i class="fa fa-lock" aria-hidden="true"></i> \
					</div>\
					<div class="editor-button" id="restore">\
						<i class="fa fa-file-code-o fa-lg" aria-hidden="true"></i> \
					</div>\
				</div></div></div>';

	if (tid >0){
		formHtml += '<div class="inputContainer"><label>'+LANG['Attention']+'</label></div>';
	}

	formHtml += '<div class="buttonContainer noselect">\
					<span class="button" id="saveButton">'+LANG['Save']+'</span>\
					<span class="button blueButton" id="closePopup">'+LANG['Cancel']+'</span>\
					<span class="button" id="previewButton">'+LANG['Preview']+'</span>\
				</div></div></div>';

	function generateTestForm(tname, tinput, toutput, vname, vinput, voutput){
		if (tname == ''){tname = LANG['Test']+' '+testCounter;}
		if (vname == ''){vname = LANG['Validator']+' '+testCounter;}
		realTestCount++; testCounter++;
		return '<div class="testCase">'+
				'<div class="input-wrap">'+
					'<input name="foo" type="text" class="editable-label" placeholder="'+LANG['type_test_name']+'" value="'+tname+'" />'+
				'</div>'+
				'<div class="testData">'+
					'<textarea class="testDataArea testQuery" placeholder="Input">'+tinput+'</textarea>'+
					'<textarea class="testDataArea testAnswer" placeholder="Output">'+toutput+'</textarea>'+
				'</div>'+
				'<div class="input-wrap">'+
					'<input name="foo" type="text" class="editable-label" placeholder="'+LANG['type_validator_name']+'" value="'+vname+'" />'+
				'</div>'+
				'<div class="testData">'+
					'<textarea class="testDataArea testQuery" placeholder="Input">'+vinput+'</textarea>'+
					'<textarea class="testDataArea testAnswer" placeholder="Output">'+voutput+'</textarea>'+
				'</div>'+
				'<div class="delimiter"><span class="delete-test noselect">'+LANG['Remove_test']+'</span></div>'+
			'</div>';
	}

	$("#frontDiv").html(formHtml);

	$("#chkcb"+lang).prop('checked', true);

	$(".dialogContainer").mCustomScrollbar({scrollbarPosition:"outside", axis:"y", theme:"rounded", autoExpandScrollbar:true, mouseWheel:{scrollAmount:350,normalizeDelta:true}});

	$("#timerange").slider({
		range: "min",
		min: 1,
		max: 10,
		value: time,
		slide: function(e, ui) {
			time = ui.value;
			$("#timeval").html((ui.value*250)+" ms");
		}
	});
	$("#timeval").html((time*250)+" ms");

	$("#memrange").slider({
		range: "min",
		min: 4,
		max: 8,
		value: mem,
		slide: function(e, ui) {
			mem = ui.value;
			$("#memval").html((1<<ui.value)+" Mb");
		}
	});
	$("#memval").html((1<<mem)+" Mb");
	if (realTestCount >= 5){$("#addTest").show();}

	var patterns = ["", 
					"#include <stdio.h>\n\nint main(){\n\t\n\treturn 0;\n}",
					"print('Hello, world!')"];
	var oldCodes = patterns;
	var langCodes = ["", "c_cpp", "python"];


	var unlockButton = $("#remove-range"); $(unlockButton).hide();
	var unlockButtonHide = true;

	var editor = ace.edit("patternEditor");
	var Range = require("ace/range").Range;
	ace.require("ace/ext/language_tools");
	ace.require("ace/ext/spellcheck");
	ace.require("ace/ext/static_highlight");
	editor.setHighlightActiveLine(true);
	editor.setTheme("ace/theme/tomorrow_night");
	editor.setShowPrintMargin(false);
	var session = editor.getSession();
	session.setUseSoftTabs(false);
	session.setMode("ace/mode/"+langCodes[lang]);
	editor.setOptions({
		enableBasicAutocompletion: true,
		enableSnippets: true,
		enableMultiselect: true,
		fontSize: "14px",
		showInvisibles: false
	});
	if (code == ""){code = patterns[lang];}
	session.getDocument().setValue(code);
	session.setUndoManager(new ace.UndoManager());

	editor.selection.on("changeSelection", function(){
		var range = editor.getSelectionRange();
		var intersect = intersects(range);
		if (unlockButtonHide && intersect){
			unlockButtonHide=false; $(unlockButton).show();
		}
		else if ( ! (unlockButtonHide||intersect)){
			unlockButtonHide=true;  $(unlockButton).hide();
		}
	});

	var rangeList = [];
	for (var i=0; i<ranges.length; i++){
		addMarker(new Range(ranges[i][0], ranges[i][1], ranges[i][2], ranges[i][3]));
	}
	$("#add-range").click(function(){ if (addMarker()) {unlockButtonHide=false; $(unlockButton).show();} });
	$("#remove-range").click(function(){ unlockButtonHide=true; $(unlockButton).hide(); removeMarker();});
	$("#restore").click(function(){ unlockButtonHide=true; $(unlockButton).hide(); removeAllMarkers(); session.getDocument().setValue(patterns[lang]);});

	function addMarker(range = null){
		var range = range || editor.selection.getRange();
		if (range.start.column != range.end.column || range.start.row != range.end.row){
			removeMarker(range);
			var markerId = session.addMarker(range, "readonly-highlight");
			range.start  = session.doc.createAnchor(range.start);
			range.end    = session.doc.createAnchor(range.end);
			range.end.$insertRight = true;
			rangeList.push([range, markerId]);
			return true;
		}
		return false;
	}

	function removeMarker(range = null){
		var range = range || editor.selection.getRange();
		var res = intersects(range, true);
		for (var i=0; i<res.length; i++){
			session.removeMarker(rangeList[res[i]][1]);
			rangeList.splice(res[i], 1);
		}
	}
	function removeAllMarkers(){
		for (var i=rangeList.length - 1; i>=0; i--){
			session.removeMarker(rangeList[i][1]);
		}
		rangeList = [];
	}

	function intersects(range, list=false) {
		var r = editor.getSelectionRange();
		var res = [];
		for (var i=rangeList.length - 1; i>=0; i--){
			if (r.intersects(rangeList[i][0])){
				res.push(i);
			}
		}
		if (list == false){ return res.length>0;}
		else{ return res;}
	}

	$("#frontDiv").show();

	$("#addTest").click(function(){
		$(".testContainer").append(generateTestForm('', '', '', '', '', ''));
		$(".delete-test").off("click").click(function(){$(this).parent().parent().remove(); realTestCount--; if (realTestCount<1){$("#addTest").click();} if (realTestCount == 4){$("#addTest").show();}});
		if (realTestCount >= 5){$("#addTest").hide();}
	})
	$(".delete-test").click(function(){$(this).parent().parent().remove(); realTestCount--; if (realTestCount<1){$("#addTest").click();} if (realTestCount == 4){$("#addTest").show();}});

	$("#closePopup").click(closePopup);
	$(".dialogContainer").click(function(event){ event.stopPropagation();});
	$(".popupFilter").click(closePopup);

	$('input[type=radio][name=chkcb]').click(function(){
		oldCodes[lang] = editor.getSession().getDocument().getAllLines().join('\n');
		lang = parseInt($(this).attr('id').substr(5), 10);
		session.setMode("ace/mode/"+langCodes[lang]);
		unlockButtonHide=true; $(unlockButton).hide(); 
		removeAllMarkers(); session.getDocument().setValue(oldCodes[lang]);
	});

	$("#saveButton").click(function(){
		function checkValue(el){
			if ($(el).val() == ""){ $(".dialogContainer").mCustomScrollbar("scrollTo", $(el)); $(el).focus(); return false; }
			else{return true;}
		}
		var flag = true;

		var tname = $("#taskName").val(); if (!checkValue($("#taskName"))){return false;}

		var task = $("#ttask").val();
		var iformat = $("#iformat").val();
		var oformat = $("#oformat").val();
		var tbounds = $("#tbounds").val();

		if ( task == "" && (iformat == "" || oformat == "") && !checkValue($("#ttask"))){return false;}
		var timerange = time;
		var memrange = mem;

		var testNames = [];
		var testQuery = [];
		var testAnswer = [];
		$(".editable-label").each(function(){
			var v = $(this).val(); if (!checkValue($(this))){flag = false;return false;}; testNames.push(v);
		}); if (flag == false){return false;}

		$(".testQuery").each(function(){
			var v = $(this).val(); if (!checkValue($(this))){flag = false;return false;} testQuery.push(v);
		}); if (flag == false){return false;}

		$(".testAnswer").each(function(){
			var v = $(this).val(); if (!checkValue($(this))){flag = false;return false;} testAnswer.push(v);
		}); if (flag == false){return false;}

		//console.log(tname, task, iformat, oformat, tbounds, timerange, memrange, testNames, testQuery, testAnswer);
		var statement = [task, iformat, oformat, tbounds];
		var testCount = testNames.length;
		var ranges = []; var publicity = [];
		for (var i=0; i<testCount; i++){
			publicity.push((i+1)%2);
		}
		for (var i=0; i<rangeList.length; i++){
			ranges.push([rangeList[i][0].start.row, rangeList[i][0].start.column, rangeList[i][0].end.row, rangeList[i][0].end.column]);
		}

		lang = parseInt($('input[type=radio][name=chkcb]:checked').attr('id').substr(5), 10);

		var xhttp = new XMLHttpRequest();
		if (tid == -1){
			xhttp.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					var res = parseInt(this.responseText);
					if ($(".taskItem").length > 0){
						if (res>0){
							$(".clist").append('<div class="taskItem" task-id="'+res+'">\
								<div class="taskInfo">\
									<div class="taskName">\
										<i class="'+lang==1?'devicon-cplusplus-plain':lang==2?'devicon-python-plain':''+' devicon green" aria-hidden="true"></i>\
										<a href="./task.php?tid='+res+'" class="tname">'+tname+'</a>\
									</div>\
									<div class="taskStatus">\
										<span data-tip="'+LANG['User_task_complete']+'"><i class="fa fa-user-o green" aria-hidden="true"></i>0</span>\
										<span data-tip="'+LANG['All_test']+'"><i class="fa fa-question-circle-o" aria-hidden="true"></i>'+testCount+'</span>\
									</div>\
								</div>\
								<div class="taskToolbar noselect">\
									<i class="fa fa-pencil-square-o toolButton edit" data-tip="'+LANG['Edit']+'"></i>\
									<i class="fa fa-bar-chart toolButton stats" data-tip="'+LANG['Stats']+'"></i>\
									<i class="fa fa-trash-o toolButton delete" data-tip="'+LANG['Delete']+'"></i>\
								</div>\
								<div class="clear"></div></div>');
						}
						closePopup();
					}
					else{
						location.reload();
					}
				}
			};
			var cid = $("#plus").attr('course-id');
			xhttp.open("POST", "controller.php?act=createTask", true);
			xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xhttp.send("cid="+encodeURIComponent(cid)+"&tname="+encodeURIComponent(tname)+"&statement="+encodeURIComponent(JSON.stringify(statement))+"&testCount="+encodeURIComponent(testCount)+"&testNames="+encodeURIComponent(JSON.stringify(testNames))+"&testData="+encodeURIComponent(JSON.stringify(testQuery))+"&testAnswer="+encodeURIComponent(JSON.stringify(testAnswer))+"&publicity="+encodeURIComponent(JSON.stringify(publicity))+"&timeLimit="+encodeURIComponent(timerange)+"&memoryLimit="+encodeURIComponent(memrange)+"&lid="+encodeURIComponent(lang)+"&pattern="+encodeURIComponent(editor.getSession().getDocument().getAllLines().join('\n'))+"&ranges="+encodeURIComponent(JSON.stringify(ranges)));
		}
		else{
			xhttp.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					$("[task-id='"+tid+"']").find(".tname").text(tname);
					closePopup();
				}
			};
			xhttp.open("POST", "controller.php?act=editTask", true);
			xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xhttp.send("tid="+encodeURIComponent(tid)+"&tname="+encodeURIComponent(tname)+"&statement="+encodeURIComponent(JSON.stringify(statement))+"&testCount="+encodeURIComponent(testCount)+"&testNames="+encodeURIComponent(JSON.stringify(testNames))+"&testData="+encodeURIComponent(JSON.stringify(testQuery))+"&testAnswer="+encodeURIComponent(JSON.stringify(testAnswer))+"&publicity="+encodeURIComponent(JSON.stringify(publicity))+"&timeLimit="+encodeURIComponent(timerange)+"&memoryLimit="+encodeURIComponent(memrange)+"&lid="+encodeURIComponent(lang)+"&pattern="+encodeURIComponent(editor.getSession().getDocument().getAllLines().join('\n'))+"&ranges="+encodeURIComponent(JSON.stringify(ranges)));
		}
	});

	$("#previewButton").click(function(){
		var previewText = "";

		var task = $("#ttask").val();
		var iformat = $("#iformat").val();
		var oformat = $("#oformat").val();
		var tbounds = $("#tbounds").val();
		var testQuery = $(".testQuery").val();
		var testAnswer = $(".testAnswer").val();

		if (task != ""){
			previewText += "###"+LANG['Task']+"\n"+task+"\n\n";
		}
		if (iformat != "" && oformat != ""){
			previewText += "###"+LANG['Input_format']+"\n"+iformat+"\n\n###"+LANG['Output_format']+"\n"+oformat+"\n\n";	
			if (tbounds != ""){
				previewText += "###"+LANG['Bounds']+"\n"+tbounds+"\n\n";		
			}
		}
		previewText += "###"+LANG['Resourses']+"\n- "+LANG['Time']+" : "+(time*250)+" ms\n- "+LANG['Memory']+" : "+(1<<mem)+" Mb\n";

		if (testQuery != "" && testAnswer != ""){
			testQuery  = testQuery.replace(/\r?\n/g, "<br>");
			testAnswer = testAnswer.replace(/\r?\n/g, "<br>");
			previewText += "\n| Input | Output |\n|--|--|\n|"+testQuery+"|"+testAnswer+"|\n";
			if ($(".testQuery").length>2){
				var testQuery = $($(".testQuery").get(2)).val();
				var testAnswer = $($(".testAnswer").get(2)).val();
				if (testQuery != "" && testAnswer != ""){
					testQuery  = testQuery.replace(/\r?\n/g, "<br>");
					testAnswer = testAnswer.replace(/\r?\n/g, "<br>");
					previewText += "|"+testQuery+"|"+testAnswer+"|\n";
				}
			}
		}

		if (previewText == ""){previewText = '<p style="text-align:center;">'+LANG['Nothing_to_display']+'</p>'}
		$("body").append('<div class="FullPage popupFilter" id="previewDiv">\
			<div class="dialogContainer" id="previewDialog">\
				<div class="previewText" id="ptext">'+''+'</div>\
				<div class="buttonContainer noselect">\
					<span class="button" id="closePreview">'+LANG['Close']+'</span>\
				</div>\
			</div></div>');
		
		var converter = new showdown.Converter({tables:true});
		$(".previewText").html(converter.makeHtml(previewText));

		MathJax.Hub.Queue(["Typeset",MathJax.Hub]);
		
		$('pre code').each(function(i, block) {hljs.highlightBlock(block);});

		setTimeout( function(){$("#previewDialog").mCustomScrollbar({scrollbarPosition:"outside", axis:"y", theme:"light-thin", autoHideScrollbar:true, mouseWheel:{scrollAmount:200,normalizeDelta:true}})}, 500);
		$("#closePreview").click(function(){$("#previewDiv").remove();});
		$("#previewDiv").click(function(){$("#previewDiv").remove();});
		$("#previewDialog").click(function(event){ event.stopPropagation();});
	});
}