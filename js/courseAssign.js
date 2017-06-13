function courseAssignDialog(cid, groups){

	var formHtml = '<div class="dialogContainer">\
						<div class="inputContainer" id="groups">\
							<label for="grName">'+LANG['Group']+'</label>\
								<input type="text" class="fuzzy-search" name="grName" id="grName" value="" placeholder="'+LANG['Type_group_name']+'">\
								<ul class="list">';
	for (var i = 0; i < groups.length; i++) {
		formHtml += '<li gid="'+groups[i].gid+'" num="'+i+'"><p class="name">'+groups[i].gname+'</p></li>';	
	}
	var currGroup = 0; var currElemnet = -1;
	var currStart = 0;
	var currEnd = 0;
	var currClear = 0;
	var time;
	formHtml += '</ul></div>\
				<div id="dataBlock" style="display: none;">\
					<div class="inputContainer">\
						<label for="startTime">'+LANG['Course_open_time']+'</label>\
						<input type="text" name="startTime" id="startTime" value="">\
					</div>\
					<div class="inputContainer">\
						<label for="endTime">'+LANG['Course_close_time']+'</label>\
						<input type="text" name="endTime" id="endTime" value="">\
					</div>\
					<div class="inputContainer">\
						<label for="saveTime">'+LANG['Results_storage_time']+'</label>\
						<input type="text" name="saveTime" id="saveTime" value="">\
					</div>\
				</div>\
				<div class="buttonContainer noselect">\
					<span class="button" id="saveButton">'+LANG['Save']+'</span>\
					<span class="button blueButton" id="closePopup">'+LANG['Cancel']+'</span>\
				</div></div>';
	
	$("#frontDiv").html(formHtml).show();

	var list = new List('groups', { 
		valueNames: ['name']
	});
	$("#grName").on('input', function(){$(".list").show(); $("#dataBlock").hide();});
	$("li").click(function(){
		currGroup = $(this).attr("gid");
		currElemnet = $(this).attr("num");
		$("#grName").val($(this).text()); 
		$(".list").hide();
		$("#dataBlock").show();
		if (groups[currElemnet].startTime == -1){
			time = moment(new Date()).format('X');
			$('#startTime').bootstrapMaterialDatePicker('setMaxDate', moment().add(2, 'years'));
			$('#endTime').bootstrapMaterialDatePicker('setMaxDate', moment().add(2, 'years')).bootstrapMaterialDatePicker('setMinDate', moment().subtract(2, 'years'));
			$('#saveTime').bootstrapMaterialDatePicker('setMinDate', moment().subtract(2, 'years'));
		}
		else{
			$('#startTime').bootstrapMaterialDatePicker('setDate', moment.unix(groups[currElemnet].startTime)).bootstrapMaterialDatePicker('setMaxDate', moment.unix(groups[currElemnet].endTime));
			$('#endTime').bootstrapMaterialDatePicker('setDate', moment.unix(groups[currElemnet].endTime)).bootstrapMaterialDatePicker('setMaxDate', moment.unix(groups[currElemnet].clearTime)).bootstrapMaterialDatePicker('setMinDate', moment.unix(groups[currElemnet].startTime));
			$('#saveTime').bootstrapMaterialDatePicker('setDate', moment.unix(groups[currElemnet].clearTime)).bootstrapMaterialDatePicker('setMinDate', moment.unix(groups[currElemnet].endTime));
		}
	});
	$('#startTime').bootstrapMaterialDatePicker({ weekStart : LANG['week_start'], lang : locale, format : 'DD MMMM YYYY - HH:mm', cancelText : LANG['CANCEL'] }).on('change', function(e, date){$('#saveTime').bootstrapMaterialDatePicker('setMinDate', date); $('#endTime').bootstrapMaterialDatePicker('setMinDate', date);}).on('change', function(e, date) {currStart = parseInt(moment(date).format('X')); $("#grName").prop( "disabled", true );});

	$('#endTime').bootstrapMaterialDatePicker({   weekStart : LANG['week_start'], lang : locale, format : 'DD MMMM YYYY - HH:mm', cancelText : LANG['CANCEL'] }).on('change', function(e, date){$('#saveTime').bootstrapMaterialDatePicker('setMinDate', date); $('#startTime').bootstrapMaterialDatePicker('setMaxDate', date);}).on('change', function(e, date) {currEnd = parseInt(moment(date).format('X')); $("#grName").prop( "disabled", true );});

	$('#saveTime').bootstrapMaterialDatePicker({  weekStart : LANG['week_start'], lang : locale, format : 'DD MMMM YYYY - HH:mm', cancelText : LANG['CANCEL'] }).on('change', function(e, date){$('#startTime').bootstrapMaterialDatePicker('setMaxDate', date); $('#endTime').bootstrapMaterialDatePicker('setMaxDate', date);}).on('change', function(e, date) {currClear = parseInt(moment(date).format('X')); $("#grName").prop( "disabled", true );});
	
	function sendNewTime(gid, type, start, end, clear){
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				console.log(this.responseText);
				closePopup();
			}
		};
		xhttp.open("POST", "controller.php?act=setAssignment", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("cid="+encodeURIComponent(cid)+"&gid="+encodeURIComponent(currGroup)+"&type="+encodeURIComponent(type)+"&startTime="+encodeURIComponent(start)+"&endTime="+encodeURIComponent(end)+"&clearTime="+encodeURIComponent(clear));
		console.log(cid, gid, type);
	}


	$("#closePopup").click(closePopup);
	$(".dialogContainer").click(function(event){ event.stopPropagation();});
	$(".popupFilter").click(closePopup);

	$("#saveButton").click(function(){
		if (currElemnet != -1){
			if (groups[currElemnet].startTime == -1){
				if (currStart + currEnd + currClear > 0){
					if (currStart == 0){$('#startTime').focus();}
					else if (currEnd == 0){$('#endTime').focus();}
					else if (currClear == 0){$('#saveTime').focus();}
					else{
						sendNewTime(currGroup, "new", currStart, currEnd, currClear);
					}
				}
				else{$(".popupFilter").click();}
			}
			else{
				if ( Math.abs(currStart-groups[currElemnet].startTime) < 60 && Math.abs(currEnd-groups[currElemnet].endTime) && Math.abs(currClear-groups[currElemnet].clearTime)){
					$(".popupFilter").click();		
				}
				else{
					if (currStart == 0){currStart = groups[currElemnet].startTime; }
					if (currEnd == 0){currEnd = groups[currElemnet].endTime; }
					if (currClear == 0){currClear = groups[currElemnet].clearTime; }
					sendNewTime(currGroup, "update", currStart, currEnd, currClear);
				}
			}
		}
	});
}