var exportData = [];
var exportName = "";
var exportText = "";
var codes = {};

function download(filename, text) {
	var element = document.createElement('a');
	element.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(text));
	element.setAttribute('download', filename);

	element.style.display = 'none';
	document.body.appendChild(element);

	element.click();

	document.body.removeChild(element);
}

function showStatistic(data, type, isAuthor, id) {
	var addClass = isAuthor&&type==1?'name-cursor':'';
	var formHtml = '<div class="dialogContainer">\
						<div class="ct-chart"></div>\
						<div class="inputContainer" id="dataTable">\
							<div class="thead noselect">\
								<table>\
									<thead>\
										<th class="sort tuname" data-sort="name">'+LANG['User_name']+'</th>\
										<th class="sort tugroup" data-sort="group">'+LANG['Group']+'</th>\
										<th class="sort turate" data-sort="rating">'+LANG['Rating']+'</th>\
									</thead>\
								</table>\
							</div>\
							<div class="tbody">\
								<table>\
									<tbody class="list">';

	if (isAuthor&&type==1){
		for (var i=0; i<data[2].length; i++){
			formHtml += '<tr class="name-cursor" uid="'+data[2][i][3]+'"><td class="name">'+data[2][i][0]+'</td><td class="group">'+data[2][i][1]+'</td><td class="rating">'+data[2][i][2]+'</td></tr>';	
		}
	}
	else{
		for (var i=0; i<data[2].length; i++){
			formHtml += '<tr><td class="name">'+data[2][i][0]+'</td><td class="group">'+data[2][i][1]+'</td><td class="rating">'+data[2][i][2]+'</td></tr>';	
		}
	}

	if (isAuthor && type==1 && data[2].length>1){
		formHtml += '</tbody></table></div></div>\
					<div class="buttonContainer noselect">\
						<span class="button" id="export">'+LANG['Export']+'</span>\
						<span class="button" id="compare"><a href="./compare.php?tid='+id+'" target="_blank">'+LANG['Compare']+'</a></span>\
						<span class="button blueButton" id="closePopup">'+LANG['Close']+'</span>\
					</div>\
				</div>';
	}
	else {
		formHtml += '</tbody></table></div></div>\
						<div class="buttonContainer noselect">\
							<span class="button" id="export">'+LANG['Export']+'</span>\
							<span class="button blueButton" id="closePopup">'+LANG['Close']+'</span>\
						</div>\
					</div>';
	}	

	$("#frontDiv").html(formHtml).show();
	$(".dialogContainer").mCustomScrollbar({scrollbarPosition:"outside", axis:"y", theme:"rounded", autoExpandScrollbar:true, mouseWheel:{scrollAmount:150,normalizeDelta:true}});
	
	if (type == 1){
		new Chartist.Line('.ct-chart', {
			labels: data[0],
			series: [data[1]]
		}, {
			low: 0,
			showArea: true, 
			chartPadding: {
				top: 20//, right:45
			},
			axisY: {
				labelInterpolationFnc: function(value, index) {
					return value == ~~ value ? value : "";
				}
			},
			plugins: [
				Chartist.plugins.ctPointLabels({
					textAnchor: 'middle'
				})
			]
		});
	}
	else{
		new Chartist.Bar('.ct-chart', {
			labels: data[0],
			series: [data[1]]
		}, {
			chartPadding: {
				top: 20//, right:45
			},
			axisY: {
				onlyInteger: true
			},
		});
	}

	var options = {
		valueNames: [ 'name', 'group', 'rating' ]
	};
	var userList = new List('dataTable', options);

	$("#closePopup").click(function(){exportData = ""; closePopup();});
	$(".dialogContainer").click(function(event){ event.stopPropagation();});
	$(".popupFilter").click(function(){exportData = ""; closePopup();});
	$("#export").click(function(){download(exportName, exportData)});

	$(".name-cursor").click(function(){
		var uid = $(this).attr('uid');
		var currCode = {code: codes[uid][2].split("\n"), step: 0};
		var currHistory = codes[uid][1];
		$("body").append('<div class="FullPage popupFilter" id="previewDiv">\
							<div class="dialogContainer" id="previewDialog">\
								<div class="previewText" id="ptext">\
									<pre><code>'+''+'</code></pre>\
								</div>\
								<div class="resourses frameresourses">\
									<span class="framecontrol"><i class="fa fa-2x fa-play-circle" id="framebutton" aria-hidden="true"></i></span>\
									<div class="range framerange" id="framerange"></div>\
								</div>\
								<div class="buttonContainer noselect">\
									<span class="button" id="closePreview">'+LANG['Close']+'</span>\
								</div>\
							</div>\
						</div>');
		$('pre code').text(codes[uid][0]).each(function(i, block) {hljs.highlightBlock(block);});

		$("#ptext").mCustomScrollbar({scrollbarPosition:"outside", axis:"y", theme:"light-thin", mouseWheel:{scrollAmount:200,normalizeDelta:true}});
		
		var interval = 0; var frame = currHistory.length+1;

		function displayStep(step){
			makeChanges(step);
			$('pre code').text(currCode.code.join("\n")).each(function(i, block) {hljs.highlightBlock(block);});
		}

		function makeChanges(step){
			var currRow = "";
			var tmpLines = [];
			if (step < currCode.step){
				currCode.step = 0; currCode.code = codes[uid][2].split("\n");
			}
			for (var i=currCode.step; i<step; i++ ){
				for (var j=0; j<currHistory[i].length; j++){
					if (currHistory[i][j].action=="insert"){
						currRow = currCode.code[currHistory[i][j].start.row];
						tmpLines = [currRow.substr(0, currHistory[i][j].start.column) + currHistory[i][j].lines[0]];
						for (var k=1; k<currHistory[i][j].lines.length; k++){
							tmpLines.push(currHistory[i][j].lines[k]);
						}
						tmpLines[tmpLines.length - 1] += currRow.substr(currHistory[i][j].start.column);
						Array.prototype.splice.apply(currCode.code, [currHistory[i][j].start.row, 1].concat(tmpLines));
					}
					else if (currHistory[i][j].action=="remove"){
						currRow = currCode.code[currHistory[i][j].start.row].substr(0, currHistory[i][j].start.column) + currCode.code[currHistory[i][j].end.row].substr(currHistory[i][j].end.column);
						currCode.code.splice(currHistory[i][j].start.row, currHistory[i][j].lines.length, currRow);
					}
				}
			}
			currCode.step = step;
		}
		function pause(){
			clearInterval(interval);
			$("#framebutton.fa-pause-circle").removeClass("fa-pause-circle").addClass("fa-play-circle");
		}

		$("#framerange").slider({
			range: "min",
			min: 0,
			max: currHistory.length,
			value: currHistory.length,
			slide: function(e, ui) {
				pause();
				frame = ui.value;
				displayStep(frame);
			}
		});

		function playback(){
			if (frame >= currHistory.length) {frame = 0;}
			$("#framebutton.fa-play-circle").removeClass("fa-play-circle").addClass("fa-pause-circle");
			$("#framerange").slider('value', frame); displayStep(frame); frame++;
			interval = setInterval(function(){
				$("#framerange").slider('value', frame);
				displayStep(frame); frame++;
				if (frame > currHistory.length){pause();}
			}, 250);
		}

		function closePreview(){
			clearInterval(interval); $("#previewDiv").remove();
		}
		$("#closePreview").click(closePreview);
		$("#previewDiv").click(closePreview);
		$("#previewDialog").click(function(event){ event.stopPropagation();});
		$("#framebutton").click(function(){
			if ($(this).hasClass("fa-play-circle")){ playback(); } else { pause(); }
		});
	});
}


/*
 * id   : cid or tid
 * type : 1 - task, 2 - course
 */
function getStats(id, type){
	$("#frontDiv").html('<div id="loader-wrapper"><div id="loader"></div></div>').show();
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			var res = JSON.parse(this.responseText);
			var data = [[],[],[]];
			var testCount = 0;
			var users = {};
			codes = {};
			exportData = [[LANG['Last_name'], LANG['First_name'], LANG['Group']]];

			exportName = (type==1?res[0].tname:res[0].cname) + ".csv";

			if (type != 1){exportData[0].push(LANG['Progress']); for (var i=0; i<res.length; i++){data[1].push(0);}}
			for (var i=0; i<res.length; i++){
				res[i].testNames = JSON.parse(res[i].testNames);
				res[i].testCount = parseInt(res[i].testCount);
				testCount += res[i].testCount;
				if (type != 1){ 
					data[0].push(res[i].tname);
					exportData[0].push(res[i].tname);
				}
				else{
					exportData[0].push(LANG['Completed_tests']);
					for (var j=0; j<res[0].testNames.length; j++){ 
						data[1].push(0);
						exportData[0].push(res[0].testNames[j]+" : "+LANG['result']);
						exportData[0].push(res[0].testNames[j]+" : "+LANG['memory']+" (kB)");
						exportData[0].push(res[0].testNames[j]+" : "+LANG['time']+" (ms)");
					}
				}
				var tempRating = 0;
				
				for (var j=0; j<res[i].results.length; j++){
					
					res[i].results[j].memory = JSON.parse(res[i].results[j].memory);
					res[i].results[j].status = JSON.parse(res[i].results[j].status);
					res[i].results[j].time   = JSON.parse(res[i].results[j].time);
					if (type == 1){
						exportData.push([res[i].results[j].lastName, res[i].results[j].firstName, res[i].results[j].gname, res[i].results[j].passed]);
						for (var k=0; k<res[i].results[j].status.length; k++){
							if (res[i].results[j].status[k] == "OK"){
								data[1][k]++;
							}
							exportData[j+1].push(res[i].results[j].status[k]);
							exportData[j+1].push(res[i].results[j].memory[k]);
							exportData[j+1].push(res[i].results[j].time[k]);
						}
						data[2].push([res[i].results[j].lastName+" "+res[i].results[j].firstName, res[i].results[j].gname, parseInt(res[i].results[j].passed), res[i].results[j].uid]);
						if (res[i].isAuthor){
							codes[res[i].results[j].uid] = [res[i].results[j].submissionCode, 
								[].concat.apply([], JSON.parse(res[i].results[j].history)).map(function(x){return x.deltas;}),
								res[i].results[j].pattern];
						}
					}
					else{

						tempRating += parseInt(res[i].results[j].passed);
						if (users.hasOwnProperty(res[i].results[j].uid)){
							if (users[res[i].results[j].uid][4] != i){
								users[res[i].results[j].uid][3] += parseInt(res[i].results[j].passed);
								users[res[i].results[j].uid][4] = i;
								while (users[res[i].results[j].uid].length < 5+i){
									users[res[i].results[j].uid].push(0);
								}
								users[res[i].results[j].uid].push(~~(100*parseInt(res[i].results[j].passed)/res[i].testCount));
							}
						}
						else{
							users[res[i].results[j].uid] = [res[i].results[j].lastName, res[i].results[j].firstName, res[i].results[j].gname, parseInt(res[i].results[j].passed), i];
							while (users[res[i].results[j].uid].length < 5+i){
								users[res[i].results[j].uid].push(0);
							}
							users[res[i].results[j].uid].push(~~(100*parseInt(res[i].results[j].passed)/res[i].testCount));
						}
					}
					
				}
				if (type != 1){
					data[1][i] = ~~((100*tempRating)/res[i].results.length/res[i].testCount);
				}
				
			}
			
			
			if (type == 1){
				data[0] = res[0].testNames;
			}
			else{
				var tempRes = [];
				for (var prop in users){
					data[2].push([users[prop][0]+" "+users[prop][1], users[prop][2], users[prop][3]]);

					while (users[prop].length < 5+res.length){
						users[prop].push(0);
					}
					users[prop].splice(4, 1);
					tempRes.push(users[prop]);
				}
				
				tempRes.sort(function(a, b){
					var nA=a[0].toLowerCase(), nB=b[0].toLowerCase();
					var gA=a[2].toLowerCase(), gB=b[2].toLowerCase();
					if(gA < gB){ return -1;}
					else if(gA > gB){return 1;}
					else{
						if(nA < nB){ return -1;}
						else if(nA > nB){return 1;}
						return 0;
					}
				});
				tempRes.splice(0, 0, exportData[0]);
				exportData = tempRes;
			}
			exportData = Papa.unparse(exportData, {quotes:true, header:false});
			showStatistic(data, type, parseInt(res[0].isAuthor), parseInt(id));
		}
	};
	xhttp.open("POST", "controller.php?act=exportStats", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("id="+parseInt(id)+"&type="+parseInt(type));
}