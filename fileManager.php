<?php
	session_start();
	
	/*Recursive copy -- Courtesy of Felix King and Mooseman on Stack Overflow*/ function recursive_copy($src,$dst) { if($dir = opendir($src)) { if(mkdir($dst)) { while(false !== ( $file = readdir($dir)) ) { if (( $file != '.' ) && ( $file != '..' )) { if ( is_dir($src . '/' . $file) ) { recursive_copy($src . '/' . $file,$dst . '/' . $file); } else { copy($src . '/' . $file,$dst . '/' . $file); } } } } else { return false; } } else { return false; } closedir($dir); return true; }  
	/*Recursive delete -- Courtesy of itay at itgoldman dot com on php.net*/ function recursive_delete($src) { $dir = opendir($src); while(false !== ( $file = readdir($dir)) ) { if(($file != '.') && ($file != '..')) { $full = $src . '/' . $file; if(is_dir($full)) { recursive_delete($full); } else { unlink($full); } } } closedir($dir); if(rmdir($src)) { return true; } else { return false; } }
	/*Multisort -- courtesy of RWC on php.net*/ function multi_sort($array, $akey, $order) { function compare($a, $b) { global $key; return strcmp($a[$key], $b[$key]); } usort($array, "compare"); if($order == -1) { $array = array_reverse($array); } return $array; }
	/*Human Filesize -- Courtesy of rommel at rommelsantor dot com on php.net*/ function human_filesize($bytes, $decimals = 2) { $sz = 'BKMGTP'; $factor = floor((strlen($bytes) - 1) / 3); return array(sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)), @$sz[$factor],$bytes); }
	/*returnStatus -- Courtesy of... me. */ function returnStatus($desc,$level) { die(json_encode(["desc"=>$desc,"level"=>$level])); }
	/*fileFilter -- Courtesy of Sean Vieira on Stack Overflow*/ function fileFilter($file) { return mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $file); }
	
	//Establish where we are
	$currentDirectory = getcwd();
	if($_POST['directory'] != "") { $currentDirectory .= $_POST['directory']; }
	
	/* AJAX responses begin here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
	** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
	if(isset($_POST['apiCall'])) {
		
		
		/* File list begins here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			if(($_POST['ls'])) {
		
				//Get all files in the current directory
				$fileList = glob($currentDirectory . "/*");
				$fileDetails = [];
				
				//Iterate through that list
				for($v = 0; $v < sizeof($fileList); $v++) {
				
					//Name
					$fileDetails[$v]["name"] = str_replace($currentDirectory . "/", "", $fileList[$v]);
					//Directory?
					$fileDetails[$v]["isDir"] = is_dir($fileList[$v]);
					//In directory -- DELETEME
					//$fileDetails[$v]["currentDir"] = $currentDirectory;
					
					//Get file size
					$fileDetails[$v]["fileSize"] = ($fileDetails[$v]["isDir"] ? array("","","0") : human_filesize(filesize($fileList[$v]),2));
		
					//Permissions
					$fileDetails[$v]["permissions"] = substr(sprintf("%o",fileperms($fileList[$v])),-3);;
					
					//Modified
					$fileDetails[$v]["dateModified"] = filemtime($fileList[$v]);
				}
				
				//Sort the array as per the user's filter request
				$sort = explode(",",$_POST['sortBy']);
				$fileDetails = multi_sort($fileDetails,$key=$sort[0],$sort[1]);
				
				//Echo the file info
				die(json_encode($fileDetails));
			}
		/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** File list ends here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
		
		
		/* File previews begin here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			else if(($_POST['previewFile'])) {
				
				$fileName = $currentDirectory . "/" . fileFilter($_POST['fileName']);
				if(file_exists($fileName)) {
					die(json_encode(htmlspecialchars(file_get_contents($fileName))));
				} else { returnStatus("Couldn't find file.","fatal"); }
			}
		
		/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** File previews ends here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
	
		
		/* Create file or directory begins here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			else if(($_POST['makeFile'])) {
				
				$file = $_POST['fileName'];
				$destination = $currentDirectory . "/" . fileFilter($_POST['fileName']);
				
				if(!file_exists($destination)) {
					if($_POST['fod'] == "file") {
						if($f = @fopen($destination, "w")) {
							chmod($destination,0775);
							returnStatus("Successfuly created file '" . $file . "'.","success");
						} else { returnStatus("Couldn't open stream. Permission denied?","fatal"); }
					} else if($_POST['fod'] == "dir") {
						if($f = @mkdir($destination,0775,true)) { 
							returnStatus("Successfuly created directory '" . $file . "'.","success");
						} else { returnStatus("Failed to create directory. Permission denied?","fatal"); }
					}
				} else { returnStatus("File already exists.","fatal"); }
			} 
		/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** Create file or directory ends here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
		
		
		/* Delete file or directory begins here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			else if(($_POST['deleteFile'])) {
				
				$fileName = $currentDirectory . "/" . fileFilter($_POST['fileName']);
				
				if(file_exists($fileName)) {
					
					if(!is_dir($fileName)) { 
						if(unlink($fileName)) {
							returnStatus("Deleted " . $fileName . ".","success");
						} else { returnStatus("Couldn't delete " . $fileName . ".","fatal"); }
					} else {
						if(recursive_delete($fileName)) {
							returnStatus("Deleted " . $fileName . ".","success");
						} else { returnStatus("Couldn't delete " . $fileName . ".","fatal"); }
					}
				} else {
					returnStatus("Couldn't find file '" . $fileName . "'.","fatal");
				}
			}
		/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** Delete file or directory ends here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
		
		
		/* Copy file or directory begins here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			else if(($_POST['copy'])) {
				$fileName = $currentDirectory . "/" . fileFilter($_POST['fileName']);
				$copyName = $currentDirectory . "/" . fileFilter($_POST['copyName']);
				
				if(file_exists($fileName)) {
					if(!file_exists($copyName)) {
						if(!is_dir($fileName)) { 
							if(copy($directory . "/" . $fileName,$directory . "/" . $copyName)) {
								returnStatus("Successfuly copied file.","success");
							} else { returnStatus("Copy failed.","fatal"); }
						} 
						else {
							if(recursive_copy($fileName,$copyName)) { returnStatus("Successfuly copied folder.","success"); }
							else { returnStatus("Failed to copy folder.","fatal"); }
						}
					} else { returnStatus($_POST['copyName'] . " already exists.","fatal"); }
				}
				else {
					returnStatus("Couldn't find file '" . explode("/",$fileName)[substr_count($fileName,"/")] . "'.","fatal");
				}
			}
		/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** Copy file or directory ends here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ 
		
		
		/* Move file or directory begins here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			else if(($_POST['move'])) {
			
				$fileName = $currentDirectory . "/" . fileFilter($_POST['fileName']);
				$newFile = $currentDirectory . "/" . $_POST['newDir'] . "/" . $_POST['fileName'];
				
				//TODO: Make this better.
				if(file_exists($fileName)) {
					if(!file_exists($newFile)) {
						
						//Supress error here so we can show our own.
						if(@rename($fileName,$newFile)) { returnStatus("Moved file.","success"); }
					else { returnStatus("Couldn't move file. Do you have permissions?","fatal"); }
						
					} else { returnStatus($_POST['fileName'] . " already exists.","fatal"); }
				}
				else {
					returnStatus("Couldn't find file '" . explode("/",$fileName)[substr_count($fileName,"/")] . "'.","fatal");
				}
			}
		/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** Move file or directory ends here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
	
	
		/* Rename file or directory begins here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			else if(($_POST['rename'])) {
				
				$fileName = $currentDirectory . "/" . fileFilter($_POST['fileName']);
				$copyName = $currentDirectory . "/" . fileFilter($_POST['copyName']);
				
				if(file_exists($fileName)) {
					if(!file_exists($copyName)) {
						if(rename($fileName,$copyName)) { returnStatus("Successfuly renamed file.","success"); }
					} else { returnStatus($_POST['copyName'] . " already exists.","fatal"); }
				}
				else { returnStatus("Couldn't find file '" . explode("/",$fileName)[substr_count($fileName,"/")] . "'.","fatal"); }
			}
		/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** Rename file or directory ends here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
		
		
		/* Permission changes begins here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			else if(($_POST['changePermissions'])) {
				
				$fileName = $currentDirectory . "/" . fileFilter($_POST['fileName']);
				$newPermissions = $_POST['newPermissions'];
				$npl = strlen($newPermissions);
				
				//CHMOD numbers must be octals in PHP
				if($npl == 3) { $newPermissions = "0" . $newPermissions; $npl++; }
				
				if($npl == 4) {
					if(file_exists($fileName)) {
						if(chmod($fileName,octdec($newPermissions))) {
							returnStatus("Successfuly changed permissions of $fileName to $newPermissions.","success");
						} else { returnStatus("Permission change failed.","fatal"); }
					} else { returnStatus("Couldn't find file '" . explode("/",$fileName)[substr_count($fileName,"/")] . "'.","fatal"); }
				} else { returnStatus("Permission value was not correctly formatted.","fatal"); }
			}
		/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** Permission changes ends here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
		
		
		/* File upload begins here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			else if(($_POST['fileUpload'])) {
				
				$finalName = $currentDirectory . "/" . basename($_FILES["fileToUpload"]["name"]);
				
				if(!file_exists($finalName)) {
					if(move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $finalName)) {
						chmod($finalName,0755);
						returnStatus("Uploaded file.","success");
						
					} else { returnStatus("Couldn't upload file.","fatal"); }
				} else { returnStatus("File already exists.","fatal"); }
			}
		/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
		** File upload ends here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
		
		
		//If an apiCall is specified but we reach here, no command was actually specified. 
		die(returnStatus("No command was issued.","fatal"));
	
	}
	/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
	** AJAX responses ends here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
	
	
	/* Login form begins here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
	** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
	
		//THIS IS NOT VERY SECURE! USE AT YOUR OWN RISK!
	
		// *** Generate a password with this function and replace $password with the result: ***
		/*die(password_hash("your password here",PASSWORD_BCRYPT));*/
		
		//Default password is 'alpine'. CHANGE THIS BEFORE YOU USE THE EDITOR!
		$password = '$2y$10$NpfqQZ3/i/ExRTsVyaHIRuE7TtKAchPi2gvz4LRnpiaBtJczy.WM2';
		
		//If we've come here from the form
		if(isset($_POST['login'])) { 
		
			//Verify password
			if(password_verify($_POST['password'],$password)) { $_SESSION['loggedIn'] = true; } 
			else { echo "Incorrect password."; }
		}
		
		//If the session didn't get set above, show the login form.
		if(!$_SESSION['loggedIn']) {
			
			die("
				<form action = '?' method = 'POST'>
					<label>Password: <input type = 'password' name = 'password'></label>
					<input type = 'submit' name = 'login' value = 'Login'>
				</form>
			");
			
		}
	/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
	** Login form ends here    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
?>

<html>
	<head>
		<title>toitFM</title>
		
		<meta charset = 'UTF-8'>
		<meta name="viewport" content="width=device-width, initial-scale=1"
		
		<!-- Various libraries -->
		<script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
		<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
		
		<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.bundle.min.js" integrity="sha384-pjaaA8dDz/5BgdFUPX6M/9SUZv4d12SUPF0axWc+VRZkx5xU3daN+lYb49+Ax+Tl" crossorigin="anonymous"></script>
		
		<!-- Minified-ish AJAX shorthand -- courtesy of iworkforthem on Github -->
		<script> function postAjax(url, data, success) {var params = typeof data == 'string' ? data : Object.keys(data).map(function(k){return encodeURIComponent(k) + '=' + encodeURIComponent(data[k])}).join('&');var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");xhr.open('POST', url);xhr.onreadystatechange = function() {if(xhr.readyState>3 && xhr.status==200) { success(xhr.responseText); }};xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');xhr.send(params);return xhr;} </script>
		<!-- Non jQuery DOM shorthand -->
		<script> function bid(el) { let first = el[0]; el = el.substring(1); if(first == "#") { return document.getElementById(el); } if(first == ".") { return document.getElementsByClassName(el); } } </script>

		
		<script>
		
			//Controls whether or not 'edit' option appears in the modal.
			<?php echo (file_exists("toitText.php") ? "editorInstalled = true;\n" : "editorInstalled = false;\n"); ?>
		
			//Sorting file directions/criteria
			var sortKey = "name"; var orderDir = 1;
			function changeOrder(newSort) { 
				if(newSort == sortKey) { orderDir *= -1; }
				sortKey = newSort; 
				populateTable(dirFromHash()); 
			}
			
			//Gets the icon that a file should use based on extension
			function getIcon(filename) {
				var format = filename.split(".")[filename.split(".").length - 1]; 
				var formats = [ {"extension":["wav","mp3","ogg","flac","aif","auc"],"icon":"far fa-file-audio"},{"extension":["mov","avi","flv","mp4","mkv","wmv"],"icon":"far fa-file-video"},{"extension":["zip","rar","7z","rar5"], "icon":"far fa-file-archive"}, {"extension":["bmp","png","gif","pdn","jpg","jpeg","tiff","tif","tga","agif"], "icon":"far fa-file-image"},  {"extension":["html","htm","js","css","xml","php","asp","py","xhtml"], "icon":"far fa-file-code"}, {"extension":["txt","rtf","doc","docx","odf"], "icon":"far fa-file-alt"} ];
				for(let v = 0; v < formats.length; v++) { if(formats[v].extension.indexOf(format) != -1) { return "<i class='" + formats[v].icon + "'></i>"; } }
				return "<i class='far fa-file'></i>";
			}
			
			//Simplify getting current directory from URL hash
			function dirFromHash() { return location.hash.substring(1); }
			
			//Create breadcrumb trail so we can find our way home
			function createBreadcrumbs(dir) {
				
				bid("#breadcrumbs").innerHTML = "";
				var bdcms = dir.split("/");
				for(let v = 0; v < bdcms.length - 1; v++) {
					
					//Bit of a hacky way to move down the breadcrumbs.
					var nod = ""; var trail = "";
					for(let f = 0; f < (bdcms.length - 1) - v; f++) { nod += "../"; }
					for(let f = 0; f < v + 1; f++) { trail += bdcms[f] + "/"; }	
					trail = trail.substr(0,trail.length-1);	
					
					//Create the element
					var breadcrumbButton = document.createElement("button");
					
					//Set text to level in trail
					breadcrumbButton.innerHTML = "" + bdcms[v] + "/";
					
					//Populate dataset for dragging and dropping
					breadcrumbButton.dataset.trail = trail;
					breadcrumbButton.dataset.dragName = bdcms[v];
					breadcrumbButton.dataset.numberUp = nod;
					
					breadcrumbButton.id = bdcms[v].substring(0,bdcms[v].length-1);
					breadcrumbButton.className = "breadcrumb";
					
					//On click move to this subdir
					breadcrumbButton.onclick = function(ev) { populateTable(ev.target.dataset.trail); bid("#filterInput").value=""; }
					
					//Allow things to be dropped into the directory
					breadcrumbButton.ondragover = function(ev) { ev.preventDefault(); }
					breadcrumbButton.ondrop = function(ev) {
						ev.preventDefault();
						
						//Get the filename of the thing being dropped
						var file = ev.dataTransfer.getData("text");
						
						if(confirm("Really move " + file + " to " + ev.target.dataset.dragName + "?")) {
							
							postAjax("?",{apiCall:true,move:true,fileName:file,directory:dirFromHash(),newDir:ev.target.dataset.numberUp},function(data){
								generalResponse(data);
							})
							
						}
					}
					bid("#breadcrumbs").appendChild(breadcrumbButton);
				}
				
				var currentCrumb = document.createElement("span");
				currentCrumb.className = "breadcrumb";
				currentCrumb.innerHTML = "<u>" + bdcms[bdcms.length-1] + "</u>";
				bid("#breadcrumbs").appendChild(currentCrumb); 
			}
			
			/* Populate file manager table  ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
			** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			function populateTable(dir,filter) {
				
				//Change locatioh hash to current directory
				location.hash = dir;
				
				//Create the breadcrumbs to our current directory
				createBreadcrumbs(dir);
				
				//Send AJAX call to retrieve list of files
				postAjax("?",{apiCall:true,ls:true,directory:dir,sortBy:[sortKey,orderDir]},function(data) {
					
					//Reset table
					bid("#fileListBody").innerHTML = "";
					
					//Turn the raw data into a JSON object
					var fileList = JSON.parse(data);
					
					//Get our current subdirectory
					var cHash = dirFromHash();
					
					//Iterate through all the files in the list
					for(let v = 0; v < fileList.length; v++) {
						
						//Check if there's a filter and check if this fits in it
						var goOn = true; if(typeof filter != "undefined") { if(fileList[v].name.indexOf(filter) == -1) { goOn = false; } }
						
						//If there's no filter or the file fits in it
						if(goOn) {
						
							//Get DOM object for the row
							var thisRow = bid("#fileListBody").insertRow(0);
							
							//Set the row id
							thisRow.id = fileList[v].name;
							
							//Set a data property so we know from anywhere what the name of this file is
							thisRow.dataset.dragName = fileList[v].name;
							
							//Enable drag and dropping
							thisRow.draggable = true;  
							thisRow.ondragstart = function(ev) { ev.dataTransfer.setData("text", ev.target.dataset.dragName); }
							
							//If this row is a directory create a button for the name collumn
							if(fileList[v].isDir) {
								
								//Allow things to be dropped into the directory
								thisRow.ondragover = function(ev) { ev.preventDefault(); }
								thisRow.ondrop = function(ev) { ev.preventDefault(); moveFile(ev); }
								
								//Create the folder button
								var fileNameButton = document.createElement("button");
								fileNameButton.innerHTML = "<i class='far fa-folder'></i>" + fileList[v].name;
								fileNameButton.dataset.dragName = fileList[v].name;
								
								//On click switch to directory in file manager view
								fileNameButton.onclick = function() { populateTable(cHash + "/" + this.dataset.dragName); bid("#filterInput").value=""; }  
							}
							
							//Otherwise make a link that opens the file
							else { 
								var fileNameButton = document.createElement("a"); 
								fileNameButton.href = "" + location.pathname.substring(0,location.pathname.lastIndexOf("/")) + cHash + "/" + fileList[v].name; 
								fileNameButton.innerHTML = getIcon(fileList[v].name) + " " + fileList[v].name; 
								fileNameButton.dataset.dragName = fileList[v].name;
							}
							
							//Add the filename button/link TD
							var nameTd = thisRow.insertCell(0)
							
							//Give it the needed dataset variable for dragging and dropping
							nameTd.dataset.dragName = thisRow.dataset.dragName;
							
							//Add it to the table
							nameTd.appendChild(fileNameButton);
							
							//Filesize collumn
							var fileSizeCollumn = thisRow.insertCell();
							fileSizeCollumn.innerHTML = fileList[v]['fileSize'][0] + fileList[v]['fileSize'][1]; 
							//We need to set a dataset for each TD or else it comes up as undefined.
							fileSizeCollumn.dataset.dragName = fileList[v].name;
							
							
							//Permissions button
							var permissionsButton = document.createElement("button");
							permissionsButton.innerHTML = fileList[v]['permissions'];
							permissionsButton.value = fileList[v]['permissions'];
							permissionsButton.onclick = function() { changePermissions(fileList[v]['name'],this.value); }
							permissionsButton.dataset.dragName = fileList[v].name;
							var permissionCollumn = thisRow.insertCell();
							permissionCollumn.dataset.dragName = fileList[v].name;
							permissionCollumn.appendChild(permissionsButton)
							
							//Date modified collumn
							var date = new Date(fileList[v]['dateModified']*1000);
							date = date.getMonth() + 1 + "/" + date.getDate() + "/" + date.getFullYear() + " - " + date.getHours() + ":" + date.getMinutes();
							var dateCollumn = thisRow.insertCell(); 
							dateCollumn.innerHTML = date; 
							dateCollumn.dataset.dragName = fileList[v].name; 
							
							//Begin file 'actions' collumn:
							actionCol = thisRow.insertCell();
							actionCol.dataset.dragName = fileList[v].name;
							
								//FIXME: The buttons aren't accepting the drag name.
							
								//Preview button
								var previewButton = document.createElement("button");
								previewButton.innerHTML = '<i class="far fa-eye"></i>'; 
								previewButton.onclick = function() { previewFile(fileList[v]['name']); }
								if(fileList[v].isDir) { previewButton.disabled; previewButton.style['visibility'] = "hidden"; }
								previewButton.dataset.dragName = fileList[v].name;
								actionCol.appendChild(previewButton); 
								
								//Copy button
								var copyButton = document.createElement("button");
								copyButton.innerHTML = '<i class="far fa-copy"></i>'; 
								copyButton.onclick = function() { copyFile(fileList[v]['name']); }
								copyButton.dataset.dragName = fileList[v].name;
								actionCol.appendChild(copyButton);
								
								//Delete button
								var deleteButton = document.createElement("button");
								deleteButton.innerHTML = '<i class="far fa-trash-alt"></i>'; 
								deleteButton.onclick = function() { deleteFile(fileList[v]['name']); }
								deleteButton.dataset.dragName = fileList[v].name;
								actionCol.appendChild(deleteButton);
								
								//Rename button
								var renameButton = document.createElement("button");
								renameButton.innerHTML = '<i class="fas fa-font"></i>'; 
								renameButton.onclick = function() { renameFile(fileList[v]['name']); }
								renameButton.dataset.dragName = fileList[v].name;
								actionCol.appendChild(renameButton);
							//End file 'actions' collumn
							
						}
					}
				});
			}
			
			/* Show table with filter    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
			** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			function runFilter() { if(bid("#filterInput").value != "") { populateTable(dirFromHash(),bid("#filterInput").value); } else { populateTable(dirFromHash()) } }
			
			
			//Every AJAX response below comforms to a similar format so we made it a function
			function generalResponse(data,andThen) {
				
				//HTML in the error means something broke and we don't have error handling for it.
				if(data.indexOf("/>") == -1) {
					var resp = JSON.parse(data);
					if(resp.level != "fatal") { 
						populateTable(location.hash.substring(1));
						if(typeof andThen != "undefined") { andThen(); }
					} else { alert(resp.desc); }
				} else { alert("An unknown error occurred."); }
			}
			
			
			/* Go up a directory    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
			** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			function upDir() {
				var cHash = dirFromHash().substring(0,dirFromHash().lastIndexOf("/"));
				location.hash = cHash;
				populateTable(cHash); 
			}
			
			/* Create a file (from form)    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
			** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			function createFile(toMake) {
				
				postAjax("?",{apiCall:true,makeFile:true,fileName:bid("#" + toMake + "Name").value,fod:toMake,directory:dirFromHash()},function(data){
					generalResponse(data,function() { bid("#" + toMake + "Name").value = ""; });
				});
			}
			
			/* Delete a file    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
			** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			function deleteFile(fname) {
				
				if(confirm("Really delete " + fname + "?")) {
					postAjax("?",{apiCall:true,deleteFile:true,fileName:fname,directory:dirFromHash()},function(data) {
						generalResponse(data);
					});
				}
			}			
			
			/* Rename a file    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
			** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			function renameFile(fname) {
				
				var copyNamed = prompt("Enter new name for file:",fname);
				if(copyNamed != "" && copyNamed != null) {
				
					postAjax("?",{apiCall:true,rename:true,fileName:fname,directory:dirFromHash(),copyName:copyNamed},function(data) {
						generalResponse(data);
					});
				}
			}
			
			/* Copy a file    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
			** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			function copyFile(fname) {
				
				var copyNamed = prompt("Enter name for copy:",fname);
				if(copyNamed != "" && copyNamed != null) {
				
					postAjax("?",{apiCall:true,copy:true,fileName:fname,directory:dirFromHash(),copyName:copyNamed},function(data) {
						generalResponse(data);
					});
				}
			}
			
			/* Move a file    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
			** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			function moveFile(ev) {
			
				var fileDropping = ev.dataTransfer.getData("text");
				var dirTo = ev.target.dataset.dragName;
				
				//Make sure we're not dropping a folder on itself.
				if(fileDropping != dirTo) {
				
					if(confirm("Really move " + fileDropping + " to " + dirTo + "?")) {
						
						postAjax("?",{apiCall:true,move:true,fileName:fileDropping,directory:dirFromHash(),newDir:dirTo},function(data){
							generalResponse(data,function() { });
						})
						
					}
				}
				
			}
			
			/* Change file permissions    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
			** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			function changePermissions(fname,initialPermissions) {
				  
				var newPerms = prompt("Enter new permissions:",initialPermissions);
				if(newPerms != "" && newPerms != null && newPerms != initialPermissions) {
				
					postAjax("?",{apiCall:true,changePermissions:true,newPermissions:newPerms,fileName:fname,directory:dirFromHash()},function(data) {
						generalResponse(data);	
					});
				}
			}
			
			/* Open 'preview file' modal    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
			** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			function previewFile(fname) {
				  
				postAjax("?",{apiCall:true,previewFile:true,fileName:fname,directory:dirFromHash()},function(data) {
					generalResponse(data,function() {
						var resp = JSON.parse(data);
						
						bid("#modalTitle").innerHTML = fname + (editorInstalled ? " / <a href = 'toitText.php?edit=1&fileName="+dirFromHash().substring(1)+"/"+fname+"'>edit</a>" : "");
						bid("#modalBody").innerHTML = resp.toString();
						$('#modal').modal();
					});
				});
				
			}
			
			/* Upload a file    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ **
			** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
			$(document).ready(function (e) {
				$("#uploadFile").on('submit',(function(e) {
					
					bid("#directoryForUpload").value = dirFromHash(); 
					e.preventDefault();
					$.ajax({ url: "?", type: "POST",	data: new FormData(this), contentType: false, cache: false, processData:false, success: function(data) {
							generalResponse(data,function() { bid("#uploadFile").reset(); });
					}});
				}));
			});
		</script>
		
		<!-- Some custom CSS needed for below. -->
		<style> *{font-size:17px;} .breadcrumb {background:gray;font-size:1em;display:inline;;margin-right:.15em;background:none;padding:0px;} h1{font-size:26px;} label{margin-bottom:0px;} .th { font-weight:bold; } button { border:0px;background:none;outline:0px;color:#0056b3;margin:0px;padding:0px; } button:hover{text-decoration:underline;cursor:pointer;} .far { margin-right:5px; } .modalContent {font-family:courier;font-size:12px;} </style>
	
	</head>
	
	<body onload = 'populateTable(dirFromHash());'>
		<div class = 'container-fluid'>
			
			<!-- Breadcrumbs and filter --> 
			<label>Filter: <input type = 'text' style = 'height:1.5em;' onkeydown = 'runFilter();' onkeyup = 'runFilter();' id = 'filterInput'></label>
			<br/>
			Directory: <span id = 'breadcrumbs'></span>   
			
			<!-- The table where the files are displayed    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ ---
			---- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
			<table class = 'table table-striped table-hover table-responsive-sm'>
				<thead>
					<tr>
						<th><button class = 'th' onclick = 'changeOrder("name")'>Name:</button></th>
						<th><button class = 'th' onclick = 'changeOrder("fileSize[2]")'>Size:</button></th>
						<th><button class = 'th' onclick = 'changeOrder("permissions")'>Permissions:</button></th>
						<th><button class = 'th' onclick = 'changeOrder("dateModified")'>Date Modified:</button></th>
						<th>Actions:</th>
					</tr>
				</thead>
				<tbody id = 'fileListBody'></tbody>
			</table>
			
			<!-- Forms to create file or directory/upload    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ ---
			---- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
			<div class = 'row' style = 'position:fixed;bottom:0;width:100%;background:rgba(240,240,240,.8);padding:8px;font-size:13px;'>
				<div class = 'col-sm-2'></div>
				
				<!-- Create file or dir -->
				<div class = 'col-sm-4'>
					<label for = 'fileName'>Create File:</label><br/> 
					<input placeholder = 'Name' type = 'text' id = 'fileName'> <input type = 'button' value = 'Create' onclick = 'createFile("file")'><br/>
					<label for = 'dirName'>Create Directory:</label><br/> 
					<input placeholder = 'Name' type = 'text' id = 'dirName'> <input type = 'button' value = 'Create' onclick = 'createFile("dir")'><br/>
				</div>
				
				<!-- Upload file -->
				<div class = 'col-sm-4'>
					<form id = 'uploadFile' class = 'form'>
					
						<label for = 'fileToUpload'>Upload File:</label><br/>
						<input type = 'file' name = 'fileToUpload' id = 'fileToUpload' style = 'width:177px;background:white;'> 
						
						<!-- jQuery does weird things with POST variables so we use hidden inputs instead. -->
						<input type = 'hidden' name = 'apiCall' value = 'true'> <input type = 'hidden' name = 'fileUpload' value = 'true'> <input type = 'hidden' name = 'directory' id = 'directoryForUpload'>
						
						<input type = 'submit' value = 'Upload'>
					</form>
				</div>
				
				<div class = 'col-sm-2'></div>
			</div>
			<br/><br/><br/><br/><br/><br/>
			
			<!-- Condensed Bootstrap modal for file previews--nothing interesting here. -->
			<div class="modal fade" id="modal" tabindex="-1" role="dialog"> <div class="modal-dialog modal-lg" role="document"> <div class="modal-content"> <div class="modal-header"> <h5 class="modal-title" id="modalTitle"></h5> <button class="close" data-dismiss="modal">&times;</button> </div> <pre class="modal-body modalContent" id = 'modalBody' > ... </pre></div> </div> </div> </div>
			
		</div>
	</body>
</html>
