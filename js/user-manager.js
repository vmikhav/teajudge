$('input[type=radio][name=chkg]').click(function(){
	gid = parseInt($(this).attr('gid'), 10);
	$('#group-props').toggle(gid!=1);
	$('#chk-user').prop('checked', groupPerm[gid].user);
	$('#chk-task').prop('checked', groupPerm[gid].task);
	$('#chk-grant').prop('checked', groupPerm[gid].grant);
});
$('input.row-checkbox').mousedown(function(e){
	e.preventDefault();
	$(this).prop('checked', !($(this)[0].checked));
});

$('.ulist tr').click(function(){
	var el = $(this).find('.row-checkbox');
	if ($(this).attr("uid") == 1){return;}
	$(el).prop('checked', !($(el)[0].checked));
	selectedUserCount = $('input.row-checkbox:checked').length;
	$('#user-props').toggle(selectedUserCount>0);
});
$('.fuzzy-search').on('input', function(){
	$(this).next().toggle(!!this.value);
});
$('.search-clear').click(function(){
	var event = new Event('input', {'bubbles': true,'cancelable': true});
	var el = $(this).prev().val(""); $(el).get(0).dispatchEvent(event);
	if ($(el).parent().parent().get(0).id == "userPanel"){
		userList.fuzzySearch();
	}
	else{ groupList.fuzzySearch(); }
});

$("#delete-user").click(function(){
	var uids = [];
	$("input.row-checkbox:checked").each(function(){
		uids.push($(this).parent().parent().parent().attr("uid"));
	});
	readTextInput(LANG['user_delete_confirm1']+uids.length+LANG['user_delete_confirm2'], "", function(n){
		if (n.toLowerCase() == LANG['delete']){
			showLoader();

			var xhttp = new XMLHttpRequest();
			xhttp.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					var res = this.responseText;
					location.reload();
				}
			};
			xhttp.open("POST", "controller.php?act=removeUser", true);
			xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xhttp.send("users="+encodeURIComponent(JSON.stringify(uids)));
		}
	}, LANG['Delete']);
});

$("#restore").click(function(){
	showLoader();
	var uids = [];
	$("input.row-checkbox:checked").each(function(){
		uids.push($(this).parent().parent().parent().attr("uid"));
		$(this).prop('checked', 0);
	})
	selectedUserCount = 0;

	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			var res = this.responseText;
			download("users.csv", LANG['export_format']+"\n"+res);
			closePopup();
		}
	};
	xhttp.open("POST", "controller.php?act=resetUserPassword", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("users="+encodeURIComponent(JSON.stringify(uids)));
});

$("#delete-group").click(function(){
	if (gid>1){
		readTextInput(LANG['group_delete_confirm'], "", function(n){
			if (n.toLowerCase() == LANG['delete']){
				showLoader();

				var xhttp = new XMLHttpRequest();
				xhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
						var res = this.responseText;
						location.reload();
					}
				};
				xhttp.open("POST", "controller.php?act=removeGroup", true);
				xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				xhttp.send("gid="+encodeURIComponent(gid));
			}
		}, LANG['Delete']);
	}
});

$(".group-perm input").click(function(){
	if (gid>1){
		showLoader();
		var perm = {'user':$("#chk-user")[0].checked?1:0, 'task':$("#chk-task")[0].checked?1:0, 'grant':$("#chk-grant")[0].checked?1:0};
		groupPerm[gid] = perm;

		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				var res = this.responseText;
				closePopup();
			}
		};
		xhttp.open("POST", "controller.php?act=changeGroupRole", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("gid="+encodeURIComponent(gid)+"&role="+encodeURIComponent(JSON.stringify(perm)));
	}
});

function readTextInput(fieldName, defaultValue, callback, buttonText = LANG['Save']){
	$("#frontDiv").html('<div class="dialogContainer"><div class="inputContainer"><label for="courseName">'+fieldName+'</label><input type="text" name="courseName" id="courseName" value="'+defaultValue+'"></div><div class="buttonContainer noselect"><span class="button" id="saveButton">'+buttonText+'</span><span class="button blueButton" id="closePopup">Скасувати</span></div></div>').show();
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