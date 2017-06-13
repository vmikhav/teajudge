// File Upload
// 
function ekUpload(){
	var importData = [];
	$("#frontDiv").html('<div class="dialogContainer">\
		<div class="buttonContainer">\
			<label>'+LANG['import_format_description']+' :</label>\
		</div>\
		<div class="buttonContainer">\
			<label>'+LANG['import_format']+'</label>\
		</div>\
		<div class="buttonContainer">\
			<form id="file-upload-form" class="uploader">\
				<input id="file-upload" type="file" name="fileUpload" accept="text/csv" />\
				<label for="file-upload" id="file-drag">\
					<div id="startd">\
						<i class="fa fa-download" aria-hidden="true"></i>\
						<div>'+LANG['Select_or_drag']+'</div>\
						<div id="notimage" class="hidden">'+LANG['Select_csv']+'</div>\
						<span id="file-upload-btn" class="btn btn-primary">'+LANG['Select_file']+'</span>\
					</div>\
					<div id="response" class="hidden">\
						<div id="messages"></div>\
					</div>\
				</label>\
			</form>\
		</div>\
		<div class="buttonContainer noselect">\
			<span class="button" id="send" style="display: none;">'+LANG['Save']+'</span>\
			<span class="button blueButton" id="closePopup">'+LANG['Cancel']+'</span>\
		</div>\
	</div>').show();

	$("#send").click(sendData);
	$("#closePopup").click(closePopup);
	function Init() {
		var fileSelect    = document.getElementById('file-upload'),
			fileDrag      = document.getElementById('file-drag'),
			submitButton  = document.getElementById('submit-button');

		fileSelect.addEventListener('change', fileSelectHandler, false);

		// Is XHR2 available?
		var xhr = new XMLHttpRequest();
		if (xhr.upload) {
			// File Drop
			fileDrag.addEventListener('dragover', fileDragHover, false);
			fileDrag.addEventListener('dragleave', fileDragHover, false);
			fileDrag.addEventListener('drop', fileSelectHandler, false);
		}
	}

	function fileDragHover(e) {
		var fileDrag = document.getElementById('file-drag');

		e.stopPropagation();
		e.preventDefault();

		fileDrag.className = (e.type === 'dragover' ? 'hover' : 'modal-body file-upload');
	}

	function fileSelectHandler(e) {
		output('');
		var fileSizeLimit = 1;
		// Fetch FileList object
		var files = e.target.files || e.dataTransfer.files;

		// Cancel event and hover styling
		fileDragHover(e);

		var isGood = (/\.(?=csv|tsv|dsv)/gi).test(files[0].name);
		if (isGood) {
			document.getElementById('startd').classList.add("hidden");
			document.getElementById('response').classList.remove("hidden");
			document.getElementById('notimage').classList.add("hidden");

			if (files[0].size <= fileSizeLimit * 1024 * 1024) {
				var config = buildConfig();
				Papa.parse(files[0], config);
			}
			else{
				output(LANG['File_is_too_large']);
			}
		}
		else {
			document.getElementById('notimage').classList.remove("hidden");
			document.getElementById('startd').classList.remove("hidden");
			document.getElementById('response').classList.add("hidden");
			document.getElementById("file-upload-form").reset();
		}

	}

	function buildConfig()
	{
		return {
			dynamicTyping: 1,
			skipEmptyLines: 1,
			worker: 1,
			complete: completeFn
		};
	}

	function completeFn(results)
	{
		//end = now();
		var previewLimit = 5;

		if (results && results.errors)
		{
			if (results.errors && results.errors.length > 0)
			{
				firstError = results.errors[0];
				output(firstError); $("#send").hide(); importData = [];
				return;
			}
			if (results.data && results.data.length > 0)
			{
				importData = [];
				for (var i=0; i<results.data.length; i++){
					if (results.data[i].length<3 || results.data[i].length>5){
						output(LANG['Input_format_error']+" : "+results.data[i].join(" , ")); $("#send").hide(); importData = [];
						return;
					}
					importData.push({firstName:results.data[i][0], lastName:results.data[i][1], group:results.data[i][2], login:results.data[i][3]||"", password:results.data[i][4]||""});
				}
				//importData = results.data;
				var x = results.data.length>previewLimit ? previewLimit:results.data.length;
				var out = "";
				for (var i=0; i<x; i++){
					out += "<tr>"+results.data[i].map(function(z){return "<td>"+z+"</td>";}).join("")+"</tr>";
				}
				output("<table>"+out+"</table>");
				$("#send").show();
			}
		}

		//console.log("    Results:", results);
	}

	// Output
	function output(msg) {
		// Response
		var m = document.getElementById('messages');
		m.innerHTML = msg;
	}

	function parseFile(file) {

		console.log(file.name);
		output(
			'<strong>' + encodeURI(file.name) + '</strong>'
		);
		
		// var fileType = file.type;
		// console.log(fileType);
		var imageName = file.name;

		var isGood = (/\.(?=csv)/gi).test(imageName);
		if (isGood) {
			document.getElementById('startd').classList.add("hidden");
			document.getElementById('response').classList.remove("hidden");
			document.getElementById('notimage').classList.add("hidden");
			// Thumbnail Preview
			document.getElementById('file-image').classList.remove("hidden");
			document.getElementById('file-image').src = URL.createObjectURL(file);
			file.parse
		}
		else {
			document.getElementById('file-image').classList.add("hidden");
			document.getElementById('notimage').classList.remove("hidden");
			document.getElementById('startd').classList.remove("hidden");
			document.getElementById('response').classList.add("hidden");
			document.getElementById("file-upload-form").reset();
		}
	}

	// Check for the various File API support.
	if (window.File && window.FileList && window.FileReader) {
		Init();
	} else {
		document.getElementById('file-drag').style.display = 'none';
	}

	function sendData(){
		showLoader();
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				closePopup();
				var res = this.responseText;
				download("users.csv", LANG['export_format']+"\n"+res);
				location.reload();
			}
		};
		xhttp.open("POST", "controller.php?act=createUsers", true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("users="+encodeURIComponent(JSON.stringify(importData)));

	}
}