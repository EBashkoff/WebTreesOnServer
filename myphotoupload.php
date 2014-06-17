<?php
define('WT_SCRIPT_NAME', 'myphotoupload.php');
define('PHOTO_UPLOAD_PAGE_URL', 'myphotouploadserver.php');
define('GALLERY_PATH_PREFIX', "gallery");
require 'mysession.php';

if (!$canadmin) {
    echo '***  ERROR: You must be an administrator to enter this area of the site  ***<br>';
    echo '*** Click the BACK button in your browser to return to the previous page ***<br>';
    exit;
}

if ($BROWSERTYPE === 'msie') {
    echo '***  ERROR: You cannot use Microsoft Internet Explorer for this functionality  ***<br>';
    echo '***    Click the BACK button in your browser to return to the previous page    ***<br>';
    exit;
}

$max_post_size = convert_phpini_param(ini_get('post_max_size'));
$max_upload_size = convert_phpini_param(ini_get('upload_max_filesize'));

// ************************  BEGIN = 'Build the folderlist array' ************************

$folderlist = array();
buildfolderlist('', GALLERY_PATH_PREFIX);

function buildfolderlist($indexpath, $indexfolder) {  // Recursively parses through folders to get pertinent folder names
    global $folderlist;
    $indexpath = ($indexpath) ? ($indexpath . '/') : '';
    $filelist = scandir($indexpath . $indexfolder);
    foreach ($filelist as $key => $onefile) {
        if (is_dir($indexpath . $indexfolder . '/'. $onefile)) {
            if (!in_array(basename($onefile), array('.', '..', 'full', 'large', 'medium', 'small', 'thumb', 'resources'))) {  // File is not parent or current dir
                $folder_to_add = $indexpath . $indexfolder . '/' . $onefile;
                array_push($folderlist, $folder_to_add);
                buildfolderlist($indexpath . $indexfolder, $onefile);
            }
        }
    }
}

function convert_phpini_param($val) {  //  Function to convert php.ini parameter having postfix G,M,K to integer
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

if (!empty($folderlist)) {
    $folders_to_remove = array();
    foreach ($folderlist as $key => $onefile) { // Now remove any residual folders that do not contain image folders in the path
            if (strpos($onefile, 'images')) {  // Folder does not contain images of interest
                array_push($folders_to_remove, $onefile);
            }
    }
    $folderlist = array_diff($folderlist, $folders_to_remove);  //  Remove non-image folders from the list
    $folderlist = array_values($folderlist);
    rsort($folderlist, SORT_STRING);
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <head>
        <meta content="en-us" http-equiv="Content-Language" />
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
        <script type="text/javascript">
            var step = 1;  //  Step in process
            var tabdivs = Array();  //  The body of a tab selection
            var tabs = Array();     //  The actual tab in the header of a tab selection
            var input_notallowed = new RegExp("[<>\"%{};]", "g");                    // Characters <>"%{};
            var pathname_notallowed = new RegExp("[/\\\\?%&\\*:|\"<>\\.]", "g");     // Characters /\?%&*:|"<>.
            var newfoldername = "";
            var existingfolderpath = "";
            var aNewFolderIsDesired = false;
            var serverResponded = true;
            var filesToUpload = new Array();
            var fullresolist = new Array();
            var xmlmenu;
            var callback = function() {};
            var uploadform;
            var countsuccesses = 0;
            var countreplies = 0;

            function constructfoldername() {  // Creates upload folder name on server from selected options
                newfoldername = document.getElementById("newfolderinput").value.replace(pathname_notallowed,"");
                existingfolderpath = document.getElementById("folderselector").value;
                aNewFolderIsDesired = document.getElementById("newfoldercheckbox").checked;
                step = 1;  //  If parent folder is selected or new folder name entered, this reverts back to step 1
                prepstep2("off");  // Now a new destination folder is being chosen so must block out file upload
                prepstep3("off");  // Now a new destination folder is being chosen so must block out file resolution section
                prepstep4("off");  // Now full resolution file folder has been changed - must re-display folder name for album naming section
                if (aNewFolderIsDesired) {
                    if(existingfolderpath !== "") {  // Check if anything was typed in yet
                        document.getElementById('uploaddest').innerHTML = existingfolderpath + "/" + newfoldername + "/images/full";
                        document.getElementById('newfolderbutton').disabled = false;
                    } else {
                        document.getElementById('uploaddest').innerHTML = "&ltPlease select an existing parent folder&gt";
                        document.getElementById('newfolderbutton').disabled = true;
                    }
                } else {
                    document.getElementById('uploaddest').innerHTML = existingfolderpath + "/images/full";
                }
            }

            function performstep1(event, userid) {  // Routine to set up a new or current destination folder for uploaded images
                if (event.preventDefault) event.preventDefault(); else event.returnValue = false; // event.preventDefault() not available in MSIE
                //  Determine if we are dealing with new or existing folder situation
                if (aNewFolderIsDesired) {       //  New folder situation requires creation of new folders on server
                    if (newfoldername !== "") {  //  Make sure a new folder name was typed in
                        var folderoptionchoices = document.getElementById("folderselector").getElementsByTagName("option");
                        var newfolderexists = false;
                        for (var i = 0; i < folderoptionchoices.length; i++) {  //  Test if newly entered folder name is already on the option list
                            if ((existingfolderpath + "/" + newfoldername).toLowerCase() === folderoptionchoices[i].value.toLowerCase()) {
                                newfolderexists = true;
                                break;
                            }
                        }
                        if (!newfolderexists) {
                            var url = "<?php echo PHOTO_UPLOAD_PAGE_URL; ?>?userid=" + userid + "&step=1"
                                    + "&path=" + existingfolderpath
                                    + "&folder=" + newfoldername;
                            callback = function(return_msg) {
                                alert(return_msg);
                                if (return_msg.indexOf("unsuccessfully") == -1) {  // Only advance to next step and update displays if folder created successfully
                                    step = 2;
                                    //  Put new folder in option list
                                    var newselectorentry = document.getElementById("folderselector").appendChild(document.createElement ("option"));
                                    newselectorentry.value = existingfolderpath + "/" + newfoldername;
                                    newselectorentry.innerHTML = newselectorentry.value.replace("gallery/", "");
                                    document.getElementById('uploaddest').innerHTML = existingfolderpath + "/" + newfoldername + "/images/full";
                                    document.getElementById('filedeststep2').innerHTML = existingfolderpath + "/" + newfoldername + "/images/full";
                                    document.getElementById('filedeststep2').style.display='block';
                                    prepstep2("on");  // Now full resolution file folder has been changed - enable file upload section
                                    prepstep3("on");  // Now full resolution file folder has been changed - must rescan folders for file resolution section
                                    prepstep4("on");  // Now full resolution file folder has been changed - must re-display folder name for album naming section
                                    changetabs(null,step);
                                }
                            };
                            makeHTTPrequest(url, "GET");
                        } else {  // New folder already exists
                            alert("The entered new folder already exists");
                            document.getElementById("newfolderinput").value = "";
                            newfoldername = '';
                        }
                    } else {
                        alert("Please enter new folder name");
                    }
                } else {  //  Existing folder situation does not require creation of new folders on server
                    if (existingfolderpath !== "") {
                        step = 2;
                        alert("Proceed to STEP 2...");
                        document.getElementById('filedeststep2').innerHTML = existingfolderpath + "/images/full";
                        document.getElementById('filedeststep2').style.display='block';
                        prepstep2("on");  // Now full resolution file folder has been changed - enable file upload section
                        prepstep3("on");  // Now full resolution file folder has been changed - must rescan folders for file resolution section
                        prepstep4("on");  // Now full resolution file folder has been changed - must re-display folder name for album naming section
                        changetabs(null,step);
                    } else {
                        alert("Please select an existing file folder");
                    }
                }
            }

            function prepstep2(onoroff) {
                switch (onoroff) {  //  Set or clear all STEP 2 elements
                    case 'on':
                        document.getElementById("fileuploadcontainer").style.display = "block";
                        break;
                    case 'off':
                        document.getElementById("filedeststep2").innerHTML = "";
                        var uploadfilelist = document.getElementById("fileselector");
                        while (uploadfilelist.hasChildNodes()) uploadfilelist.removeChild(uploadfilelist.childNodes[0]);
                        document.getElementById("fileuploadcontainer").style.display = "none";
                        document.getElementById("progressbarstep2").value = 0;
                        document.getElementById("percentcompletestep2").innerHTML = "0%";
                        filesToUpload = [];
                        break;
                    default:
                        break;
                }
            }

            function performstep2(event, userid) {  // Routine to upload full resolution file to images/full folder on server
                // Settings for php.ini must be made:
                //     file_uploads = On
                //     upload_max_filesize = 12M
                //     max_file_uploads = 20
                if (document.getElementById('filedeststep2').innerHTML !== "") {
                    if (filesToUpload.length > 0) {
                        document.getElementById("progressbarstep2").style.display = "block";
                        document.getElementById("progressbarstep2").max = filesToUpload.length;
                        document.getElementById("progressbarstep2").value = 0;
                        document.getElementById("percentcompletestep2").style.display = "block";
                        document.getElementById("percentcompletestep2").innerHTML = "0%";
                        document.getElementById('addfilesbutton').disabled = true;
                        document.getElementById('removefilesbutton').disabled = true;
                        document.getElementById('uploadfilesbutton').disabled = true;
                        countreplies = 0;
                        countsuccesses = 0;
                        var url = "<?php echo PHOTO_UPLOAD_PAGE_URL; ?>";

                        document.getElementById('filebeinguploaded').innerHTML = "Initiating...";
                        for (var i = 0; i < filesToUpload.length; i++) {
                            uploadform = new FormData();
                            uploadform.append("step", 2);
                            uploadform.append("userid", userid);
                            uploadform.append("folder", newfoldername);
                            uploadform.append("path", existingfolderpath);
                            uploadform.append("hash", "");
                            uploadform.append(filesToUpload[i].name, filesToUpload[i]);

                            callback = function(return_msg) {
                                countreplies++;
                                answers = return_msg.split("|");  // answer is filename|success 0 or 1|file overwritten 0 or 1
                                anstoreport = ((answers[1] === "0") ? "Failure" : "Success")
                                        + " for " + answers[0]
                                        + ((answers[2] === "1") ? " - Already on server" : "");
                                document.getElementById('filebeinguploaded').innerHTML = anstoreport;
                                if (answers[1] === "1") countsuccesses++;
                                document.getElementById("progressbarstep2").value =  countsuccesses;
                                document.getElementById("percentcompletestep2").innerHTML = Math.round(100 * countsuccesses / filesToUpload.length) + '%';
                                if (countreplies === filesToUpload.length) {
                                    step = 3;
                                    alert("Upload attempts: " + filesToUpload.length
                                            + "\nReplies from server: " + countreplies
                                            + "\nSuccessful transfers: " + countsuccesses
                                            + "\n\nProceed to STEP 3...");
                                    document.getElementById("progressbarstep2").style.display = "none";
                                    document.getElementById("progressbarstep2").value = 0;
                                    document.getElementById("percentcompletestep2").style.display = "none";
                                    document.getElementById("percentcompletestep2").innerHTML = "0%";
                                    document.getElementById("filebeinguploaded").innerHTML = "";
                                    document.getElementById('addfilesbutton').disabled = false;
                                    document.getElementById('removefilesbutton').disabled = false;
                                    document.getElementById('uploadfilesbutton').disabled = false;
                                    prepstep3("on");
                                    changetabs(null,step);
                                }
                            };
                            makeHTTPrequest(url, "POST");
                        }
                    } else {
                        alert("You must choose at least one file");
                    }
                } else {
                    alert("You must select an upload location first");
                }
            }

            function prepstep3(onoroff) {
                switch (onoroff) {  //  Set or clear all STEP 3 elements
                    case 'on':
                        if (document.getElementById("filesourcestep3").innerHTML !== "") prepstep3("off");  //  Must clear list if list was not empty
                        var step3sourcepath = document.getElementById('filedeststep2').innerHTML;
                        document.getElementById("filesourcestep3").innerHTML = step3sourcepath;
                        document.getElementById("filesourcestep3").style.display = "block";
                        document.getElementById("resocontainer").style.display = "block";

                        var url = "<?php echo PHOTO_UPLOAD_PAGE_URL; ?>?userid=" + "<?php echo $uid; ?>" + "&step=3" + "&path="
                                + step3sourcepath + "&substep=fetchsources";
                        callback = function(return_msg) {
                            if (return_msg === "Path does not exist") {
                                alert("Cannot locate specified full resolution folder on server");
                            } else {
                                fullresolist = JSON.parse(return_msg);
                                var resotbody = document.getElementById("resotbody");
                                for (var i = 0; i < fullresolist.length; i++) {
                                    var onerow = document.createElement("tr");
                                    onerow.className = "resotableRow";
                                    resotbody.appendChild(onerow);
                                    for (var j=0; j < 5; j++) onerow.appendChild(document.createElement("td"));
                                    for (var j=0; j < 5; j++) onerow.getElementsByTagName("td")[j].className = "resotableCell";
                                    onerow.getElementsByTagName("td")[0].style.width = "300px";
                                    onerow.getElementsByTagName("td")[0].style.textAlign = "left";
                                    onerow.getElementsByTagName("td")[0].innerHTML = fullresolist[i];
                                }
                                if (fullresolist.length > 0) document.getElementById('genresosbutton').disabled = false;  // Enable button to generate files
                            }
                        };
                        makeHTTPrequest(url, "GET", "SYNC");  // Must be synchronous request since prep STEP 4 is also making a request with a different callback fxn
                        break;
                    case 'off':
                        document.getElementById("filesourcestep3").innerHTML = "";
                        var resotbody = document.getElementById("resotbody");
                        while (resotbody.hasChildNodes()) resotbody.removeChild(resotbody.childNodes[0]);
                        document.getElementById("resocontainer").style.display = "none";
                        document.getElementById("progressbarstep3").value = 0;
                        document.getElementById("percentcompletestep3").innerHTML = "0%";
                        fullresolist = [];
                        break;
                    default:
                        break;
                }
            }

            function performstep3(event, userid) {  // Routine to generate reduced resolution files from full resolution files
                var step3sourcepath = document.getElementById('filesourcestep3').innerHTML;
                if (step3sourcepath !== "") {
                    if (fullresolist.length > 0) {
                        document.getElementById("progressbarstep3").style.display = "block";
                        document.getElementById("progressbarstep3").max = fullresolist.length;
                        document.getElementById("progressbarstep3").value = 0;
                        document.getElementById("percentcompletestep3").style.display = "block";
                        document.getElementById("percentcompletestep3").innerHTML = "0%";
                        document.getElementById('genresosbutton').disabled = true;
                        countreplies = 0;
                        countsuccesses = 0;
                        for (var i = 0; i < fullresolist.length; i++) {
                            var onefile = fullresolist[i];
                            var url = "<?php echo PHOTO_UPLOAD_PAGE_URL; ?>?userid=" + "<?php echo $uid; ?>"
                                    + "&step=3" + "&path=" + step3sourcepath + "&substep=File-" + encodeURIComponent(onefile);  // Encode ampersands in filename
                            callback = function(return_msg) {
                                countreplies++;  // Counts replies from server even if response is an error state from the server
                                answers = return_msg.split("|");  // answer is filename|success large|med|small|thumb 0 or 1|overwrite large|med|small|thumb 0 or 1
                                var resotbody = document.getElementById("resotbody");
                                var rowelems = resotbody.getElementsByTagName("tr");
                                var returnfileindex = fullresolist.indexOf(answers[0]);  //  Get the index of the filename in the file list of full resolution images
                                var cellelems = rowelems[returnfileindex].getElementsByTagName("td");
                                for (var j = 1; j <= 4; j++) {
                                    // Checkmark on success with |o if overwritten
                                    cellelems[j].innerHTML = (answers[j] === "1") ? (String.fromCharCode(0x2713) + ((answers[j+4] === "1") ? '|o' : '')) : "";
                                    if (answers[j] === "1") countsuccesses++;  // Successes only accumulate for valid server replies
                                }
                                document.getElementById("progressbarstep3").value =  countreplies;
                                var percentcomplete = countreplies / fullresolist.length;
                                document.getElementById("percentcompletestep3").innerHTML = Math.round(100 * percentcomplete) + '%';
                                resotbody.scrollTop = resotbody.scrollHeight * percentcomplete - cellelems[0].parentElement.clientHeight;

                                if (countreplies === fullresolist.length) {
                                    if ((countreplies * 4) === countsuccesses) {  // This indicates all server replies were valid ones
                                        step = 4;
                                        alert("Requests sent to server: " + fullresolist.length
                                                + "\nReplies from server: " + countreplies
                                                + "\nSuccessful files generated: " + countsuccesses + " of " + (4 * countreplies) + " possible"
                                                + "\n\nProceed to STEP 4...");
                                        changetabs(null,step);
                                    } else { // Some server replies were invalid - some files did not get generated so stay on this step
                                        alert("Requests sent to server: " + fullresolist.length
                                                + "\nReplies from server: " + countreplies
                                                + "\nSuccessful files generated: " + countsuccesses + " of " + (4 * countreplies) + " possible"
                                                + "\n\nCheck table below for results");
                                    }
                                    document.getElementById("progressbarstep3").style.display = "none";
                                    document.getElementById("progressbarstep3").value = 0;
                                    document.getElementById("percentcompletestep3").style.display = "none";
                                    document.getElementById("percentcompletestep3").innerHTML = "0%";
                                }
                            };
                            makeHTTPrequest(url, "GET");
                        }
                    } else {
                        alert("No full resolution files to process");
                    }
                } else {
                    alert("You must first establish a source path for full resolution files");
                }
            }

            function prepstep4(onoroff) {
                //  xml file structure is:
                //
                //    <div id="smooth-menu"  style="background-color: #F0F0F0"  >
                //        <ul>
                //            <li>
                //                <a href="#">Top level menu item displayed</a>
                //                <ul class="second-level">
                //                    <li>
                //                        <a  title="" href="PHP code with folder name">Submenu item displayed</a>
                //                    </li>
                //                        ..
                //                        ..
                //                        ..
                //                    <li>
                //                        <a  title="" href="PHP code with folder name">Submenu item displayed</a>
                //                    </li>
                //                </ul>
                //            </li>
                //                ..
                //                ..  more top level menu items
                //                ..
                //        </ul>
                //    </div>

                switch (onoroff) {  //  Set or clear all STEP 3 elements
                    case 'on':
                        var step4sourcepath = document.getElementById('filedeststep2').innerHTML;
                        document.getElementById("filedeststep4").innerHTML = step4sourcepath;
                        document.getElementById("filedeststep4").style.display = "block";
                        document.getElementById("albumnamecontainer").style.display = "block";
                        document.getElementById('newmenuinputdiv').style.display="none";  // Clear new first level menu entry items
                        document.getElementById('newmenuinput').value="";
                        document.getElementById('addmenubutton').disabled=true;
                        document.getElementById('newmenuitemcheckbox').checked=false;
                        var url = "<?php echo PHOTO_UPLOAD_PAGE_URL; ?>?userid=" + "<?php echo $uid; ?>" + "&step=4" + "&substep=fetchalbummenu";
                        callback = function(return_msg) {
                            var parser = new DOMParser();
                            return_msg = add_or_remove_escape_chars(return_msg, "remove");
                            xmlmenu = parser.parseFromString(return_msg, "text/xml");
                            update_option_menulist("");  // Add top level menu items from xmlmenu to displayed option list
                        };
                        makeHTTPrequest(url, "GET", "SYNC");  // Must be synchronous request since prep STEP 3 is also making a request with a different callback fxn
                        break;
                    case 'off':
                        document.getElementById("filedeststep4").innerHTML = "";
                        document.getElementById("albumnamecontainer").style.display = "none";
                        break;
                    default:
                        break;
                }
            }

            function performstep4(event, userid) {  // Routine to create new albums on album submenus
                var step4sourcepath = document.getElementById('filedeststep4').innerHTML.replace("/full", "").replace(/'/g, "\\'");
                if (step4sourcepath !== "") {
                    var selectedoptions = new Array();
                    var menulist = document.getElementById("menuselector");
                    if (menulist.selectedOptions == null) {  // Firefox has no selectedOptions method
                        for (var i = 0; i < menulist.length; i++) {
                            if (menulist[i].selected) selectedoptions.push(menulist[i]);
                        }
                    } else {
                        selectedoptions = menulist.selectedOptions;
                    }
                    var newalbumname = document.getElementById("newalbumname").value.replace(input_notallowed,"");
                    var newalbumdesc = document.getElementById("newalbumdesc").value.replace(input_notallowed,"");
                    if ((selectedoptions.length > 0) && (newalbumname !== "")) {
                        for (var i = 0; i < selectedoptions.length; i++) {      // Loop through the selected top level menu option items and update xmlmenu
                            var oneoptionitemname = selectedoptions[i].innerHTML.replace(/&amp;/g, "##amp");  // InnerHTML returns &amp; for &
                            var atags = xmlmenu.getElementsByTagName("a");      // These are all of the "a" tags in the PHP album menu file
                            for (var j = 0; j < atags.length; j++) {            // Now we have only the first level "a" tags after this loop
                                // Find the top level menu node that matches the selected option menu item
                                if ((atags[j].getAttribute("href") === "#") && (atags[j].firstChild.nodeValue === oneoptionitemname)) {
                                    var albumitemparent = atags[j].nextElementSibling; // This is the "ul" second class level
                                    var submenuitem_li = xmlmenu.createElement("li");               // "li" goes under "ul" and "a" goes under "li"
                                    var submenuitem_a = xmlmenu.createElement("a");
                                    var submenuitem_atext = xmlmenu.createTextNode(add_or_remove_escape_chars(newalbumname, "remove"));
                                    submenuitem_a.appendChild(submenuitem_atext);
                                    submenuitem_a.setAttribute("title", add_or_remove_escape_chars(newalbumdesc, "remove"));
                                    var constructHref = "<" + "?php echo 'myPicShow.php?folder=" + step4sourcepath + "&userid=' . $uid; ?" + ">";
                                    constructHref = add_or_remove_escape_chars(constructHref, "remove");  // Make encoding same as rest of XML xmlmenu items
                                    submenuitem_a.setAttribute("href", constructHref);
                                    submenuitem_li.appendChild(submenuitem_a);
                                    albumitemparent.appendChild(submenuitem_li);
                                    break;  // Move on to next selected option
                                }
                            }
                        }
                        update_option_menulist();
                        var xmlmenuupload = add_or_remove_escape_chars((new XMLSerializer()).serializeToString(xmlmenu), "add");

                        var parser = new DOMParser();
                        var xmlmediagroupdata =
                                parser.parseFromString("<" + "?xml version=\"1.0\"?" + "><mediaGroup><groupInfo><custom></custom></groupInfo></mediaGroup>", "text/xml");
                        var customnode = xmlmediagroupdata.getElementsByTagName("custom")[0];
                        customnode.appendChild(xmlmediagroupdata.createElement("groupTitle")).appendChild(xmlmediagroupdata.createTextNode(newalbumname));
                        customnode.appendChild(xmlmediagroupdata.createElement("groupDescription")).appendChild(xmlmediagroupdata.createTextNode(newalbumdesc));
                        var xmlmediagroupdataupload = (new XMLSerializer()).serializeToString(xmlmediagroupdata);

                        var mediagroupdatafile = step4sourcepath.replace("/images", "");
                        var mediagroupdatafile = mediagroupdatafile.replace(/\\'/g, "'");  // Put apostrophe back for xml mediagroupdata file

                        uploadform = new FormData();
                        uploadform.append("step", 4);
                        uploadform.append("substep", "uploadxml");
                        uploadform.append("userid", userid);
                        uploadform.append("xmlmenu", new Blob([xmlmenuupload], {type: "text/xml"}), "xmlmenufile.xml");
                        uploadform.append("path", mediagroupdatafile);
                        uploadform.append("xmlmediagroupdata", new Blob([xmlmediagroupdataupload], {type: "text/xml"}), "xmlmediagroupdatafile.xml");

                        var url = "<?php echo PHOTO_UPLOAD_PAGE_URL; ?>";
                        callback = function(return_msg) {
                            answers = return_msg.split("|");  // answer is wrote menudata success 0 or 1|wrote mediaGroupData file success 0 or 1
                            alert("Wrote updated album menu to server "
                                   + ((answers[0] === "0") ? "un" : "") + "successfully"
                                   + "\n\nWrote updated mediaGroupData file server "
                                   + ((answers[1] === "0") ? "un" : "") + "successfully"
                                   + "\n\nYou may now go to the home page to view the updated menu");
                        };
                        makeHTTPrequest(url, "POST");
                    } else {
                        alert("You must select at least one top level menu items and enter an album name");
                    }
                } else {
                    alert("You must have a destination folder to associate with an album name");
                }
            }

            function addfiles(event) {  // Pertains to STEP 2 adding files to option list
                var uploadfilelist = document.getElementById("fileselector");
                var filestoadd = event.files;
                for (var i=0; i < filestoadd.length; i++) {
                    var isimageorvideo = (filestoadd[i].type.indexOf("image/") > -1) || (filestoadd[i].type.indexOf("video/") > -1);
                    var isnotonfilelist = (filesToUpload.filter(function(value) {return (value.name === filestoadd[i].name)}).length === 0);
                    if (isimageorvideo && isnotonfilelist) {
                        var addedfileoption = document.createElement("option");
                        var filetoobig = ((filestoadd[i].size > <?php echo $max_upload_size; ?>) || (filestoadd[i].size > <?php echo $max_post_size; ?>));
                        addedfileoption.innerHTML = filestoadd[i].name + (filetoobig ? " - will not upload - too large" : "");
                        addedfileoption.value = (!filetoobig ? filestoadd[i].name : "toobig");  //  If file too large, file can't be uploaded or removed from filesToUpload
                        uploadfilelist.appendChild(addedfileoption);
                        if (!filetoobig) filesToUpload.push(filestoadd[i]);
                    }
                }
                document.getElementById('uploadfilesbutton').disabled = (filesToUpload.length < 1);
            }

            function removefiles(event) {  // Pertains to STEP 2 removing files from option list
                if (event.preventDefault) event.preventDefault(); else event.returnValue = false; // event.preventDefault() not available in MSIE
                var uploadfilelist = document.getElementById("fileselector");
                var filestoremove = new Array();
                if (uploadfilelist.selectedOptions == null) {  // Firefox has no selectedOptions method
                    for (var i = 0; i < uploadfilelist.length; i++) {
                        if (uploadfilelist[i].selected) filestoremove.push(uploadfilelist[i]);
                    }
                } else {
                    filestoremove = uploadfilelist.selectedOptions;
                }
                for (var i = 0; i < filestoremove.length; i++) {  // Removes files from actual upload list
                    var thisoptionchild = filestoremove[i];
                    if (thisoptionchild.value !== "toobig") {  //  Files that are too large are not on filesToUpload list so cannot be removed from that list
                        filesToUpload.splice(filesToUpload.indexOf(filesToUpload.filter(function(value) {return (value.name === thisoptionchild.value);})[0]),1);
                    }
                }
                for (var i = 0; i < filestoremove.length; i++) {  // Removes files from option list
                    var thisoptionchild = filestoremove[i];
                    uploadfilelist.removeChild(thisoptionchild);
                    // In Chrome, filestoremove is bound to uploadfilelist.selectedOptions, so thisoptionchild is removed from filestoremove with the removeChild method
                    if (uploadfilelist.selectedOptions) i--;  // Decrement only if selectedOptions method was available for this browser
                }
                document.getElementById('uploadfilesbutton').disabled = (filesToUpload.length < 1);
            }

            function addmenuitem() {  // Pertains to STEP 4 adding a first level menu item to list
                var newmenuname = add_or_remove_escape_chars(document.getElementById("newmenuinput").value.replace(input_notallowed,""), "remove");
                if (newmenuname !== "") {
                    // First get the top "ul" tag and all its "li" children that hold the top level menu items and text nodes
                    var top_ul_node = xmlmenu.firstChild.firstChild.nextSibling;        // Top "ul" tag node
                    var menu_li_items = top_ul_node.childNodes;                         // These are the "li" and text nodes under the top "ul" tag

                    // Check to see if entered name is already listed (the firstChild of the "a" tag node is the text node for the "a" tag)
                    var top_menu_li_elements = Array.prototype.slice.call(menu_li_items);   // First convert nodelist to array so we can use filter method
                    top_menu_li_elements = top_menu_li_elements.filter(function(value) {return (value.nodeName === "li")});  // Exclude the text nodes

                    if (top_menu_li_elements.filter(function(value) {return (value.firstChild.textContent === newmenuname)}).length === 0) {  // Test if name is new
                        var topmenuitem_li = xmlmenu.createElement("li");               // Establish top level menu node items
                        var topmenuitem_litext = xmlmenu.createTextNode("");            // Text node for "li"
                        var topmenuitem_a = xmlmenu.createElement("a");                 // "a" and "ul" are siblings and are children of "li"
                        var topmenuitem_atext = xmlmenu.createTextNode(newmenuname);    // Text node for "a"
                        var topmenuitem_ul = xmlmenu.createElement("ul");
                        topmenuitem_a.setAttribute("href", "#");
                        topmenuitem_a.appendChild(topmenuitem_atext);
                        topmenuitem_ul.setAttribute("class", "second-level");

                        topmenuitem_li.appendChild(topmenuitem_a);                      // Make "a" and "ul" children of "li"
                        topmenuitem_li.appendChild(topmenuitem_ul);

                        // Find index of i on top level menu items list that should follow new entry
                        var i = 1;
                        while ((i < menu_li_items.length) && (newmenuname > menu_li_items[i].firstChild.firstChild.nodeValue)) i += 2;
                        if (i === menu_li_items.length) {
                            top_ul_node.appendChild(topmenuitem_li);                        // Put this structure under the first "ul" tag at end of "li's"
                            top_ul_node.appendChild(topmenuitem_litext);                    // Add text node as well
                        } else {
                            top_ul_node.insertBefore(topmenuitem_li, menu_li_items[i]);     // Put this structure in front of node having higher alphabetical text
                            top_ul_node.insertBefore(topmenuitem_litext, menu_li_items[i+1]);   // Add text node as well
                        }
                        update_option_menulist(add_or_remove_escape_chars(newmenuname, "add"));     // Update displayed option list from xmlmenu
                        alert("Added new top level menu item \"" + add_or_remove_escape_chars(newmenuname, "add") + "\"" );
                        document.getElementById('newmenuinputdiv').style.display="none";    // Clear new first level menu entry items
                        document.getElementById('newmenuinput').value="";
                        document.getElementById('newmenuitemcheckbox').checked=false;
                        document.getElementById('createalbumbutton').disabled=false;
                        document.getElementById("menuselector").focus();
                    } else {
                        alert("That name is already on the list - Please choose another");
                        document.getElementById("newmenuinput").focus();
                    }
                } else {
                    alert("Please enter a new menu item name");
                    document.getElementById("newmenuinput").focus();
                }
            }

            function update_option_menulist(newmenuname) {  // Pertains to STEP 4 updating the top level menu displayed option list
                // Function parameter is string representing option item that should be highlighted as selected at end of creating new option list
                var menu_li_items = xmlmenu.getElementsByTagName("ul")[0].children;
                var selectorlist = document.getElementById("menuselector");         // The displayed option list
                while (selectorlist.hasChildNodes()) selectorlist.removeChild(selectorlist.childNodes[0]);  // Empty the list
                for (var i = 0; i < menu_li_items.length; i++) {
                    var optionitem = document.createElement("option");                          // Now add new top level menu item to option list
                    var menuitemtext = menu_li_items[i].firstChild.firstChild.nodeValue;        // The firstChild of the "li" tag is the "a" tag
                    optionitem.value = add_or_remove_escape_chars(menuitemtext, "add");         //  and the firstChild of the "a" tag is "a's" text
                    optionitem.innerHTML = add_or_remove_escape_chars(menuitemtext, "add");

                    var optiontitles = "";
                    var submenutags = menu_li_items[i].children[1].getElementsByTagName("li");  //  menu_li_items[i].children[1] is second level "ul" tag
                    for (var j = 0; j < submenutags.length; j++) {
                        // Construct physical folder name from a tag's href for hover information on menu item
                        albumfolderfromhref = decodeURI(add_or_remove_escape_chars(submenutags[j].firstChild.getAttribute("href"), "add"));
                        albumfolderfromhref = albumfolderfromhref.substring(albumfolderfromhref.search("=gallery") + 9, albumfolderfromhref.search("/images&"));
                        optiontitles += submenutags[j].firstChild.firstChild.nodeValue + " (";  //  "a" tag for submenu item is under "ul" tag (firstChild)
                        optiontitles += albumfolderfromhref + ")\n";                            //  and the firstChild of the "a" tag is the text node
                    }                                                                           
                    optionitem.title = add_or_remove_escape_chars(optiontitles, "add").replace(/\\/g, "");

                    if (optionitem.value === newmenuname) optionitem.selected = true;           // Select the new item if present
                    selectorlist.appendChild(optionitem);
                }
             }

            function add_or_remove_escape_chars(inputstr, addorremove) {
                switch (addorremove) {
                    case "add":
                        inputstr = inputstr.replace(/##lt\?php/g, "<" + "?php");
                        inputstr = inputstr.replace(/\?##gt/g, "?" + ">");
                        inputstr = inputstr.replace(/##amp/g, "&");
                        break;
                    case "remove":
                        inputstr = inputstr.replace(/<\?php/g, "##lt?php");
                        inputstr = inputstr.replace(/\?>/g, "?##gt");
                        inputstr = inputstr.replace(/&/g, "##amp");
                        break;
                }
                return inputstr;
            }

            function changetabs(theclickedelement, forcetabnumber) {
                // event.preventDefault(event);  // Not needed if href of calling element is "#"
                var tabnumber = ((theclickedelement) ? theclickedelement.innerHTML.substring(5) : forcetabnumber); // Get step number from tag label or forced value
                for (var i = 0; i < tabdivs.length; i++) tabdivs[i].style.display = "none";
                tabdivs[tabnumber - 1].style.display = "block";
                for (var j = 0; j < tabs.length; j++) tabs[j].setAttribute("class", "tabheader");
                tabs[tabnumber - 1].setAttribute("class", "tabheader tabheadershaded");
                if (tabdivs[tabnumber - 1].firstChild.children[1].style.display === "none") alert("You must complete prior steps first");
                return false;  // Needed to prevent native HTML event
            }

            function makeHTTPrequest(to_server_file_url, get_or_post, synctype) {
                //  Returns true on successful receipt of response and sets global serverResponded to true
                //  Params get_or_post must be "GET" or "POST"
                //    synctype is "SYNC" or ASYNC", according to whether request will be made synchronously or asynchronously
                //    (default is asynchronous if not specified)
                var request = new XMLHttpRequest();
                serverResponded = false;
                request.onreadystatechange = function() {
                    switch (request.readyState) {
                        case 1 : break;                          // HTTP Request OPENED
                        case 2 : serverResponded = true; break;  // HTTP Request HEADERS_RECEIVED
                        case 3 : serverResponded = true; break;  // HTTP Request LOADING
                        case 4 :                                 // HTTP Request DONE
                            if (callback && ((typeof callback) === 'function')) {
                                callback(request.responseText);
                            }
                            serverResponded = true;
                            return true;
                            break;
                        default: alert("Unknown Response");
                    }
                };
                synctype = synctype || "ASYNC";
                to_server_file_url = encodeURI(to_server_file_url);
                request.open(get_or_post, to_server_file_url, (synctype !== "SYNC"));  //  Asynchronous request by default
        //        if (get_or_post === "POST") request.setRequestHeader("Content-type", "multipart/form-data", true);  // Including this messes up boundaries
                request.send((get_or_post === "GET") ? null : uploadform);
                setTimeout(function() {
                    if (!serverResponded) {
                        alert("No Response From Server");
                        return false;
                    }
                }, 40000);
            }
        </script>

        <style type="text/css">
            h2 {
                font-family: tahoma,arial,helvetica,sans-serif;
                font-size: 18px;
                font-weight: bold;
            }
            .button_example{
                border: 1px solid #d7dada; -webkit-border-radius: 5px; -moz-border-radius: 5px;border-radius: 5px;font-size:12px;font-family:tahoma, verdana, arial, sans-serif;
                padding: 4px 7px 4px 7px;
                height: 25px;
                text-decoration:none;
                display:inline-block;
                font-weight:bold;
                color: #171717;
                background-color: #f4f5f5; background-image: -webkit-gradient(linear, left top, left bottom, from(#f4f5f5), to(#dfdddd));
                background-image: -webkit-linear-gradient(top, #f4f5f5, #dfdddd);
                background-image: -moz-linear-gradient(top, #f4f5f5, #dfdddd);
                background-image: -ms-linear-gradient(top, #f4f5f5, #dfdddd);
                background-image: -o-linear-gradient(top, #f4f5f5, #dfdddd);
                background-image: linear-gradient(to bottom, #f4f5f5, #dfdddd);filter:progid:DXImageTransform.Microsoft.gradient(GradientType=0,startColorstr=#f4f5f5, endColorstr=#dfdddd);
            }
            .button_example:hover{
                border: 1px solid #516EA3;
                background-color: #d9dddd; background-image: -webkit-gradient(linear, left top, left bottom, from(#d9dddd), to(#364866));
                background-image: -webkit-linear-gradient(top, #d9dddd, #364866);
                background-image: -moz-linear-gradient(top, #d9dddd, #364866);
                background-image: -ms-linear-gradient(top, #d9dddd, #364866);
                background-image: -o-linear-gradient(top, #d9dddd, #364866);
                background-image: linear-gradient(to bottom, #d9dddd, #364866);filter:progid:DXImageTransform.Microsoft.gradient(GradientType=0,startColorstr=#d9dddd, endColorstr=#364866);
            }
            .tabbodycontainer {
                display: inline-block;
                position: relative;  /* Needed for msie */
                width: 60%;
                background-color: #FFFFFF;
                margin-right: 0px;   /* Need 0 for msie, auto for others */
                margin-top: -5px;
                border-spacing: 5px;
            }
            .tabbody {
                display: block;
                height: 475px;
                width: 100%;
                border-width: 0px 1px 1px 1px;
                border-color: black;
                border-style: solid;
                padding: 0px 10px 5px 0px;
            }
            .tabheadercontainer {
                display: inline-block;
                width: 60%;
                height: 40px;
                background-color: #FFFFFF;
                position: relative; /* Needed to get vertical alignment of table cell centered */
            }
            .tabheader {
                display: table-cell;
                border-width: 1px 1px 1px 0px;
                border-color: black;
                border-style: solid;
                border-radius: 10px 10px 0px 0px;
                width: 24%;
                height: 100%;
                float: left;
                background-color: white;
            }
            .tabheadershaded {
                background-color: #86815F;
                border-bottom-color: #FFFFFF;
            }
            .tabheaderspan {
/*                position: absolute; for chrome*/
                position: relative;
                top: 50%;
                margin-top: -8px;
                vertical-align: middle;
                text-align: center;
                width: 100px;
                margin-left: auto;
                margin-right: auto;
                font-family: Arial, Helvetica, sans-serif;
                font-size: medium;
                color: #000000;
            }
            .tableCellHead {
                width: 100%;
                height: 18px;
                padding: 5px;
                background-color: #86815F;
            }
            .auto-style4 {
                font-family: Arial, Helvetica, sans-serif;
                font-size: xx-large;
                color: #000000;
                text-align: left;
                text-decoration: none;
            }
            a.auto-style4:hover {color: red;}
            .auto-style5 {
                font-family: Arial, Helvetica, sans-serif;
                font-size: medium;
                color: #000000;
                text-align: right;
                text-decoration: none;
            }
            a.auto-style5:hover {color: red;}
            .auto-style6 {
                font-family: Arial, Helvetica, sans-serif;
                font-size: small;
                color: #000000;
                text-align: left;
                text-decoration: none;
            }
            .resotable {
                display: table;
                width: 600px;
                height: auto;
                background-color: #FFFFFF;
            }
            .resotbody {
                display: block;
                overflow: auto;
                height: 226px;
                width: 100%;
                vertical-align: middle;
            }
            .resothead {
                display: table-header-group;
                vertical-align: middle;
            }
            .resotableRow {
                height: 28px;
                display: block;
            }
            .resotableCell {
                text-align: center;
                height: 16px;
                width: 55px;
                border: 1px solid black;
                padding: 4px;
            }
            .resotableCellHead {
                width: 55px;
                color: #000000;
                background-color: #86815F;
            }
        </style>

        <script type="text/javascript">
            window.onload = function () {
                var tabbodycontainer = document.getElementById("tabbodycontainer");
                var tabheadercontainer = document.getElementById("tabheadercontainer");
                <?php
                if ($BROWSERTYPE === 'msie') {
                    echo    'var j = 0;',
                            'for (var i = 0; i < tabbodycontainer.children.length; i++) {',
                                'var obj = tabbodycontainer.children[i];',
                                'if (obj.className === "tabbody") {',
                                    'tabdivs[j] = obj;',
                                    'j++;',
                                '}',
                            '}';
                } else {
                    echo 'tabdivs = tabbodycontainer.children;';  // getElementsByClassName not supported by MSIE'
                }
                ?>
                tabs = tabheadercontainer.firstChild.children;
            }
        </script>
    </head>
    <body style="background-color: #3C391B; margin: 8px; margin-top: 2px">
        <div id="outerframe" style="border: 3px solid #86815F;  background-color:white; position: relative; margin:auto; width: 100%; top: 6px; left: 0px; color: #FFFFFF; visibility: visible;">
            <div id="fullhead" style="display: block; margin:auto; width: 99%; height: 52px; padding-left: 0px; padding-right: 0px; padding-top: 4px; padding-bottom: 4px; color: #36341A; background-color: #86815F; visibility: visible; position: relative; top: 4px; left: 0px">
                <div style="float: left; padding-left: 12px; margin-top: 12px;" >
                    <?php echo '<a class="auto-style4" href="' . FILE_PATH_PREFIX . 'myportal.php?userid=' . $uid . '"><strong>Bashkoff Family Web Site</strong></a>'; ?>
                </div>
                <div style="float: right; height: 100%; padding-right: 12px;">
                    <div style="height: 100%; vertical_align: bottom;">
                        <span class="auto-style5" style="float: right; text-align: right; position: relative; bottom: -30px;">
                        <?php
                        $fltempstr = substr(dirname(__FILE__), strrpos(dirname(__FILE__), 'gallery'));
                        if ($uid != 0) {
                            echo 'Logged in as: <a href="' . FILE_PATH_PREFIX . 'edituser.php" class="auto-style5">' . $realusername . '</a>',
                            ' | <a  href="' . FILE_PATH_PREFIX . 'myportal.php?userid=' . $uid . '" class="auto-style5">Home</a>',
                            ' | <a  href="' . FILE_PATH_PREFIX . 'index.php?logout=1" class="auto-style5">Logout</a>';
                        }
                        ?>
                        </span>
                    </div>
                </div>
            </div>
            <div id="limebar" style="margin:auto; height: 12px; width: 99%; text-align: center; padding-left: 0px; padding-right: 0px; padding-top: 0px; color: #36341A; background-color: #9D9248; visibility: visible; position: relative; top: 0px; left: 0px">
            </div>
            <div id="medialist-page" style="margin:auto; width: 99%; text-align: center; padding-bottom: 6px; color: #36341A; background-color: #FFFFFF; visibility: visible; position: relative; top: 0px; left: 0px">
                <div id="medialist-page-border" style="border: 3px solid #9D9248; height: 100%; padding: 10px; margin:auto; visibility: visible; position: relative">
                    <h2>Image File Upload Process</h2>

                    <div id="tabheadercontainer" class="tabheadercontainer"><div style="float: left; height: 100%; width: 50%;"> <!--hgt was inherit-->
                    <div class="tabheader tabheadershaded" style="border-left: 1px solid black;"><div class="tabheaderspan"><a href="#" style="color: black; text-decoration: none;" onclick="changetabs(this, 1)">Step 1</a></div></div>
                    <div class="tabheader"><div class="tabheaderspan"><a href="#" style="color: black; text-decoration: none;" onclick="changetabs(this, 2)">Step 2</a></div></div>
                    <div class="tabheader"><div class="tabheaderspan"><a href="#" style="color: black; text-decoration: none;" onclick="changetabs(this, 3)">Step 3</a></div></div>
                    <div class="tabheader"><div class="tabheaderspan"><a href="#" style="color: black; text-decoration: none;" onclick="changetabs(this, 4)">Step 4</a></div></div>
                    </div></div>

                    <div id="tabbodycontainer" class="tabbodycontainer">

                        <!--STEP 1-->
                        <div class="tabbody" style="display: block;"><div style="position: relative;">
                            <div class="auto-style5 tableCellHead"><span class="auto-style5" style="float:left;">STEP 1: Select or create a file folder on the server for your images</span></div>

                            <!--Select existing folder area-->
                            <select class="auto-style5" id="folderselector" name="pickedfolder" size="10"
                                style="position: absolute; top: 40px; left: 70px; text-align: left; width: 75%;"
                                onchange="constructfoldername();"><option id="rootfolderoption" value="gallery" disabled>&ltImage Gallery Root Folder&gt</option>
                                <?php
                                    foreach ($folderlist as $key=>$onefolder) {
                                        echo '<option value="' . $onefolder . '">' . str_replace('gallery/', '', $onefolder) . '</option>';
                                    }
                                ?>
                            </select>

                            <!--New folder checkbox-->
                            <input class="auto-style5" style="position: absolute; top: 245px; left: 70px;" type="checkbox" id="newfoldercheckbox" name="wantsnewfolder"
                               onclick="
                                   step = 1;  //  If clicked, this reverts back to step 1
                                   prepstep2('off');  // Now a new destination folder is being chosen so must block out file upload section
                                   prepstep3('off');  // Now a new destination folder is being chosen so must block out file resolution section
                                   prepstep4('off');  // Now full resolution file folder has been changed - must re-display folder name for album naming section
                                   document.getElementById('newfolderinputdiv').style.display=(this.checked ? 'block' : 'none');
                                   if (!this.checked) {
                                      document.getElementById('newfolderinput').value='';
                                      document.getElementById('uploaddest').innerHTML='';
                                      document.getElementById('folderselector').value=null;
                                      document.getElementById('filedeststep2').style.display='none';
                                      document.getElementById('rootfolderoption').disabled=true;
                                      newfoldername = '';
                                      existingfolderpath = '';
                                      aNewFolderIsDesired = false;
                                   } else {
                                      document.getElementById('newfolderinput').focus();
                                      document.getElementById('rootfolderoption').disabled=false;
                                      aNewFolderIsDesired = true;
                                   }
                               ">
                            <span class="auto-style5" style="position: absolute; top: 245px; left: 95px;">Check to create a new folder under selected folder</span>

                            <!--New folder text entry area-->
                            <div id="newfolderinputdiv" style="display: none;"><span class="auto-style5" style="position: absolute; top: 275px; left: 74px; text-align: left; width: 100%;">Enter new folder name:</span>
                            <input type="text" id="newfolderinput"  class="auto-style5" style="position: absolute; top: 273px; left: 240px; text-align: left;"
                                   oninput="constructfoldername();"></div>

                            <!--Upload destination text output-->
                            <span class="auto-style5" style="position: absolute; top: 315px; left: 30px;">Your upload destination is:</span>
                            <div class="auto-style5" id="uploaddest" style="position: absolute; top: 340px; width: 100%; text-decoration: underline; text-align: center;"></div>

                            <!--Set Up New Image Folder Button-->
                            <div style="position: absolute; top: 375px; width: 100%;">
                                <input type="button" id="newfolderbutton" class="button_example"
                                       value="Set Up Destination Image Folder"
                                       onclick="performstep1(event, <?php echo $uid; ?>);"
                                       style="font-family: sans-serif; font-size: 16px; font-weight: 200; position: relative; border: 1px solid black;">
                            </div>
                        </div></div>  <!--Close out STEP 1 table cell-->

                        <!--STEP 2-->
                        <div class="tabbody" style="display: none;"><div style="position: relative;">
                            <div class="auto-style5 tableCellHead"><span class="auto-style5" style="float:left;">STEP 2: Select and upload the image files from your computer to the server</span></div>
                            <div id="fileuploadcontainer" style="display: none;">
                                <!--Upload destination text output-->
                                <span class="auto-style5" style="position: absolute; top: 40px; left: 30px;">Destination upload location is:</span>
                                <div class="auto-style5" id="filedeststep2" style="display: none; position: absolute; top: 65px; width: 100%; text-decoration: underline; text-align: center;"></div>

                                <!--Select local files to upload area-->
                                <span class="auto-style5" style="position: absolute; top: 90px; left: 30px;">Choose local files requiring upload (maximum file size = <?php echo ini_get('post_max_size'); ?>):</span>
                                <select class="auto-style5" id="fileselector" name="pickedfile" multiple size="10"
                                    style="position: absolute; top: 115px; left: 70px; text-align: left; width: 75%;">
                                </select>

                                <!--File window buttons-->
                                <!-- label tag is wrapper for input type="file" button -->
                                <label class="button_example" for="addfilesbutton" style="position: absolute; top: 323px; left: 75px; height: 15px;
                                    font-family: sans-serif; font-size: 16px; font-weight: 200; border: 1px solid black;">Choose Files</label>
                                <input type="file" id="addfilesbutton" style="visibility: hidden;" name="files[]" multiple onchange="addfiles(this);">
                                <input type="button" id="removefilesbutton" class="button_example"
                                       style="font-family: sans-serif; font-size: 16px; font-weight: 200; border: 1px solid black;
                                       position: absolute; top: 323px; left: 200px;"
                                       value="Remove Files" onclick="removefiles(event);">
                                <input type="button" id="uploadfilesbutton" class="button_example"
                                       style="font-family: sans-serif; font-size: 16px; font-weight: 200; border: 1px solid black;
                                       position: absolute; top: 323px; left: 335px;"
                                       value="Upload Files" onclick="performstep2(event, <?php echo $uid; ?>)">

                                <!--Upload file text output-->
                                <span class="auto-style5" style="position: absolute; top: 375px; left: 30px;">Uploaded file:</span>
                                <div class="auto-style5" id="filebeinguploaded" style="position: absolute; top: 375px; left: 184px; width: 100%; text-align: left;"></div>
                                <span class="auto-style5" style="position: absolute; top: 400px; left: 30px;">File upload progress:</span>
                                <progress id="progressbarstep2" style="display: none; position: absolute; top: 402px; left: 184px;"></progress>
                                <span class="auto-style5" id="percentcompletestep2" style="display: none; position: absolute; top: 400px; left: 350px; width: 100%; text-align: left;">0%</span>
                            </div>
                        </div></div>  <!--Close out STEP 2 table cell-->

                        <!--STEP 3-->
                        <div class="tabbody" style="display: none;"><div style="position: relative;">
                            <div class="auto-style5 tableCellHead"><span class="auto-style5" style="float:left;">STEP 3: Generate reduced resolution images from full resolution images on the server</span></div>
                            <div id="resocontainer" style="display: none;">
                                <!--Upload destination text output-->
                                <span class="auto-style5" style="position: absolute; top: 40px; left: 30px;">Full resolution files source location:</span>
                                <div class="auto-style5" id="filesourcestep3" style="display: none; position: absolute; top: 65px; width: 100%; text-decoration: underline; text-align: center;"></div>

                                <!--Select local files to upload area-->
                                <span class="auto-style5" style="position: absolute; top: 95px; left: 30px;">Reduced resolution files generated ("|o" = overwritten file):</span>
                                <div style="position: absolute; clear: both; overflow: hidden; height: 360px; width: 670px;">
                                    <table class="resotable" style="position: absolute; top: 95px; left: 70px; text-align: left;">
                                        <thead class="resothead auto-style6"><tr class="resotableRow">
                                            <th class="resotableCell resotableCellHead auto-style6" style="width: 295px; text-align: left;">Filename</th>
                                            <th class="resotableCell resotableCellHead auto-style6">Large</th><th class="resotableCell resotableCellHead">Medium</th>
                                            <th class="resotableCell resotableCellHead auto-style6">Small</th><th class="resotableCell resotableCellHead">Thumb</th>
                                        </tr></thead>
                                        <tbody class="resotbody auto-style6" id="resotbody">
                                        </tbody>
                                    </table>
                                </div>
                                <span class="auto-style5" style="position: absolute; top: 395px; left: 30px;">Click to generate:</span>
                                <input type="button" id="genresosbutton" class="button_example" 
                                       style="font-family: sans-serif; font-size: 16px; font-weight: 200; border: 1px solid black;
                                       position: absolute; top: 391px; left: 160px;"
                                       value="Generate Files" onclick="performstep3(event, <?php echo $uid; ?>);">
                                <span class="auto-style5" style="position: absolute; top: 395px; left: 330px;">Progress:</span>
                                <progress id="progressbarstep3" style="display: none; position: absolute; top: 397px; left: 410px;"></progress>
                                <span class="auto-style5" id="percentcompletestep3" style="display: none; position: absolute; top: 395px; left: 575px; width: 100%; text-align: left;">0%</span>
                            </div>
                        </div></div>

                        <!--STEP 4-->
                        <div class="tabbody" style="display: none;"><div style="position: relative;">
                            <div class="auto-style5 tableCellHead"><span class="auto-style5" style="float:left;">STEP 4: Create an album name for this folder (as it will appear on the home page menu selector)</span></div>
                            <div id="albumnamecontainer" style="display: none;">
                                <!--Physical folder destination text output-->
                                <span class="auto-style5" style="position: absolute; top: 40px; left: 30px;">Physical destination image folder location is:</span>
                                <div class="auto-style5" id="filedeststep4" style="display: none; position: absolute; top: 65px; width: 100%; text-decoration: underline; text-align: center;"></div>

                                <!--Select first level menu items-->
                                <span class="auto-style5" style="position: absolute; top: 90px; left: 30px;">Choose one or more first level menu items for new album:</span>
                                <select class="auto-style5" id="menuselector" name="pickedmenu" multiple size="10"
                                    style="position: absolute; top: 115px; left: 70px; text-align: left; width: 75%;"
                                    onchange="document.getElementById('createalbumbutton').disabled=false;">
                                </select>

                                <!--New menu item checkbox-->
                                <input class="auto-style5" style="position: absolute; top: 320px; left: 70px;" type="checkbox" id="newmenuitemcheckbox" name="wantsnewmenu"
                                   onclick="
                                       document.getElementById('newmenuinputdiv').style.display=(this.checked ? 'block' : 'none');
                                       if (!this.checked) {
                                          document.getElementById('newmenuinput').value='';
                                          document.getElementById('addmenubutton').disabled=true;

                                       } else {
                                          document.getElementById('newmenuinput').focus();
                                          document.getElementById('addmenubutton').disabled=false;
                                          document.getElementById('createalbumbutton').disabled=true;
                                       }
                                   ">
                                <span class="auto-style5" style="position: absolute; top: 320px; left: 95px;">Check to create a new first level menu item</span>

                                <!--New menu item text entry area-->
                                <div id="newmenuinputdiv" style="display: none;"><span class="auto-style5" style="position: absolute; top: 350px; left: 74px; text-align: left; width: 100%;">Enter new first level menu name:</span>
                                <input type="text" id="newmenuinput"  class="auto-style5" style="position: absolute; top: 348px; left: 310px; text-align: left;"
                                       oninput="">
                                <input type="button" id="addmenubutton" class="button_example" 
                                       style="font-family: sans-serif; font-size: 16px; font-weight: 200; border: 1px solid black;
                                       position: absolute; top: 347px; left: 510px; width: 115px;"
                                       value="Put On List" disabled onclick="addmenuitem();"></div>

                                <!--New album name text entry area-->
                                <span class="auto-style5" style="position: absolute; top: 380px; left: 74px; text-align: left; width: 100%;">Enter new album name:</span>
                                <input type="text" id="newalbumname"  class="auto-style5" style="position: absolute; top: 378px; left: 310px; text-align: left;"
                                       oninput="">
                                <span class="auto-style5" style="position: absolute; top: 410px; left: 74px; text-align: left; width: 100%;">Enter album description:</span>
                                <input type="text" id="newalbumdesc"  class="auto-style5" style="position: absolute; top: 408px; left: 310px; text-align: left;"
                                       oninput="">
                                <input type="button" id="createalbumbutton" class="button_example" 
                                       style="font-family: sans-serif; font-size: 16px; font-weight: 200; border: 1px solid black;
                                       position: absolute; top: 407px; left: 510px; width: 115px;"
                                       value="Create Album" disabled onclick="performstep4(event, <?php echo $uid; ?>)">
                            </div>
                        </div></div>
                    </div>
                </div>  <!--close medialist-page-border div -->
            </div>  <!--close medialist-page div -->
        </div>  <!--close outerframe div -->
    </body> <!--close body -->
</html>