var rangeList = [];
var editor = ace.edit("editor");
var Range = require("ace/range").Range;
ace.require("ace/ext/language_tools");
ace.require("ace/ext/spellcheck");
ace.require("ace/ext/static_highlight");
editor.setHighlightActiveLine(true);
editor.setTheme("ace/theme/tomorrow_night");
editor.setShowPrintMargin(false);
var session = editor.getSession();
session.setUseSoftTabs(false);
session.setMode("ace/mode/"+lcode);
editor.setOptions({
	enableBasicAutocompletion: true,
	enableSnippets: true,
	enableMultiselect: true,
	fontSize: "14px",
	showInvisibles: false
});
session.getDocument().setValue(code);
session.setUndoManager(new ace.UndoManager());
session.$undoManager.$undoStack = JSON.parse(codeHistory);
session.$undoManager.dirtyCounter = session.$undoManager.$undoStack.length;
session.$undoManager.$doc = session;

for (var i = 0; i < ranges.length; i++) {
	addMarker(new Range(ranges[i][0], ranges[i][1], ranges[i][2], ranges[i][3]));
}

editor.keyBinding.addKeyboardHandler({
	handleKeyboard : function(data, hash, keyString, keyCode, event) {
		if (hash === -1 || (keyCode <= 40 && keyCode >= 37) || (hash == 1 && (keyCode == 67 || keyCode == 90 || keyCode == 89 || keyCode == 83) ) || keyCode == 120  || keyCode == 115) return false;
		if (intersects()) {
			return {command:"null", passEvent:false};
		}
	}
});
before(editor, 'onPaste', preventReadonly);
before(editor, 'onCut',   preventReadonly);

function before(obj, method, wrapper) {
	var orig = obj[method];
	obj[method] = function() {
		var args = Array.prototype.slice.call(arguments);
		return wrapper.call(this, function(){
			return orig.apply(obj, args);
		}, args);
	}
	
	return obj[method];
}

function addMarker(range = null){
	var range = range || editor.selection.getRange();
	if (range.start.column != range.end.column || range.start.row != range.end.row){
		var markerId = session.addMarker(range, "readonly-highlight");
		range.start  = session.doc.createAnchor(range.start);
		range.end    = session.doc.createAnchor(range.end);
		range.end.$insertRight = true;
		rangeList.push([range, markerId]);
	}
}

function intersects() {
	var r = editor.getSelectionRange();
	var res = [];
	for (var i=rangeList.length - 1; i>=0; i--){
		if (r.intersects(rangeList[i][0])){
			return 1;
		}
	}
	return 0;
}

function preventReadonly(next, args) {
	if (intersects()) return;
	next();
}

var inEditorScroll = false;
var scrollbars = $('.ace_scrollbar').css('overflow', 'hidden').mouseenter(function() {inEditorScroll = true;}).mouseleave(function() {inEditorScroll = false;});


var scrollbarv = $('.ace_scrollbar-v'); var scrollbarh = $('.ace_scrollbar-h');
scrollbarv.mCustomScrollbar({autoHideScrollbar:true, callbacks:{whileScrolling:function(){
			if (inEditorScroll == true) {session.setScrollTop(-this.mcs.top);}
}}});
scrollbarh.mCustomScrollbar({axis:'x', autoHideScrollbar:true, scrollInertia:750, callbacks:{whileScrolling:function(){
			if (inEditorScroll == true) {session.setScrollLeft(-this.mcs.left);}
}}});
var scrollvInner = $('#mCSB_1_container > .ace_scrollbar-inner');
var customScrollv = $('#mCSB_1_container');
var scrollhInner = $('#mCSB_2_container > .ace_scrollbar-inner');
var customScrollh = $('#mCSB_2_container');
function updateEditorScrollbarSize(){
	setTimeout(function(){
		$(customScrollv).height($(scrollvInner).height());
		$(scrollbarh).mCustomScrollbar('update');
	}, 1);
}
session.on('change', updateEditorScrollbarSize);
session.on('changeScrollLeft', function (x){
	if (inEditorScroll == false){
		setTimeout(function(){scrollbarh.mCustomScrollbar("scrollTo",x,{scrollEasing:"linear"});}, 1);
	}
});
session.on('changeScrollTop', function (y){
	if (inEditorScroll == false){
		setTimeout(function(){scrollbarv.mCustomScrollbar("scrollTo",y,{scrollEasing:"easeOut"});}, 1);
	}
});

var frontDiv = document.getElementById('frontDiv');
var runHotkeyFlag = false;

function saveCode(){
	$(frontDiv).show();
	var xhttp = new XMLHttpRequest();
	var req='./controller.php?act=saveCode';
	xhttp.open("POST", req, true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	var tid = parseInt($(".taskName").attr("task-id"));
	var jranges = [];
	for (var i=0; i<rangeList.length; i++){
		jranges.push([rangeList[i][0].start.row, rangeList[i][0].start.column, rangeList[i][0].end.row, rangeList[i][0].end.column]);
	}
	xhttp.send('lang='+encodeURIComponent(lcode)+'&lid='+encodeURIComponent(lid)+'&task='+encodeURIComponent(tid)+'&code='+encodeURIComponent(editor.getSession().getDocument().getAllLines().join('\n'))+'&ranges='+encodeURIComponent(JSON.stringify(jranges))+'&history='+encodeURIComponent(JSON.stringify(session.$undoManager.$undoStack)));
	xhttp.onreadystatechange = function() {
		if (xhttp.readyState == 4 && xhttp.status == 200) {
			console.log(xhttp.responseText);
			if (xhttp.responseText == "auth"){
				window.open('./index.php?close=1','_blank');
			}
			$(frontDiv).hide();
		}
	};
}
function execCode(){
	$(frontDiv).show();
	var xhttp = new XMLHttpRequest();
	var req='./tryCode.php';
	xhttp.open("POST", req, true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	var tid = parseInt($(".taskName").attr("task-id"));
	var jranges = [];
	for (var i=0; i<rangeList.length; i++){
		jranges.push([rangeList[i][0].start.row, rangeList[i][0].start.column, rangeList[i][0].end.row, rangeList[i][0].end.column]);
	}
	xhttp.send('lang='+lcode+'&lid='+lid+'&task='+tid+'&code='+encodeURIComponent(editor.getSession().getDocument().getAllLines().join('\n'))+'&ranges='+encodeURIComponent(JSON.stringify(jranges))+'&history='+encodeURIComponent(JSON.stringify(session.$undoManager.$undoStack)));
	xhttp.onreadystatechange = function() {
		if (xhttp.readyState == 4 && xhttp.status == 200) {
			console.log(xhttp.responseText);
			if (xhttp.responseText == "auth"){
				$(log).html(LANG['Auth_end']);
				$(logDiv).css('border-color', '#ac5700');
				$(frontDiv).hide();
				return;
			}
			var res= JSON.parse(xhttp.responseText);
			if (res.result == 'CE'){
				$(log).html('<pre>'+res.cmperr+'</pre>');
				$(logDiv).css('border-color', '#c05c48');
			}
			else{
				var logHtml = "<p>"+LANG['Progress']+" : "+res.passedTest+' / '+res.testCount+" ( "+~~(100*res.result)+'% )</p>';
				for (var i=0; i<res.info.length; i++){
					if (res.info[i].stdin == ''){
						logHtml += "<p>"+res.info[i].name+" : <span class='"+(res.info[i].result=='OK'?"greenText":"redText")+"'>"+res.info[i].result+"</span> ( "+res.info[i].cpu+" ms, "+(res.info[i].memory/1024).toFixed(1)+" MB )</p>";
					}
					else{
						logHtml += "<details>";
						logHtml += "<summary>"+res.info[i].name+" : <span class='"+(res.info[i].result=='OK'?"greenText":"redText")+"'>"+res.info[i].result+"</span> ( "+res.info[i].cpu+" ms, "+(res.info[i].memory/1024).toFixed(1)+" MB )</summary>";
						logHtml += "<pre>stdin  :\n"+res.info[i].stdin +"</pre>";
						logHtml += "<pre>answer :\n"+res.info[i].answer+"</pre>";
						logHtml += "<pre>stdout :\n"+res.info[i].stdout+"</pre>";
						logHtml += "<pre>stderr :\n"+res.info[i].stderr+"</pre>";
						logHtml += "</details>";
					}
				}
				if (res.result == 1){
					$(logDiv).css('border-color', '#5f8120');	
				}
				else if (res.result == 0){
					$(logDiv).css('border-color', '#c05c48');
				}
				else{
					$(logDiv).css('border-color', '#ac5700');
				}
				$(log).html(logHtml);
			}
			$("#log").mCustomScrollbar("update");
			$(frontDiv).hide();
		}
	};
}

editor.commands.addCommand({
	name: 'run',
	bindKey: {win: "F9", "mac": "F9"},
	exec: execCode
})
editor.commands.addCommand({
	name: 'save',
	bindKey: {win: "Ctrl-S", "mac": "Cmd-S"},
	exec: saveCode
})
document.addEventListener("keyup",function(e){if (e.keyCode==120) {execCode();}},false);