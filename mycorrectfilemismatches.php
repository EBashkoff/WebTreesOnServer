<?php
define('WT_SCRIPT_NAME', 'mycorrectfilemismatches.php');
define('FILE_MISMATCH_PAGE_URL', 'mycorrectfilemismatchesserver.php');
define('GALLERY_PATH_PREFIX', "gallery");
require 'mysession.php';

if (!$canadmin) {
    echo '***  ERROR: You must be an administrator to enter this area of the site  ***<br>';
    echo '*** Click the BACK button in your browser to return to the previous page ***<br>';
    exit;
}

$submit = safe_POST('update');

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

if (!empty($folderlist)) {
    $folders_to_remove = array();
    foreach ($folderlist as $key => $onefile) { // Now remove any residual folders that do not contain image folders in the path
            if (!strpos($onefile, 'images')) {  // Folder does not contain images of interest
                array_push($folders_to_remove, $onefile);
            }
    }
    $folderlist = array_diff($folderlist, $folders_to_remove);  //  Remove non-image folders from the list
}
$ct = count($folderlist);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <head>
        <meta content="en-us" http-equiv="Content-Language" />
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
        <link rel="stylesheet" type="text/css" href="themes/olivegreen/style.css"/>
        <script type="text/javascript"  src="js/myDivPopupImage.js"></script>
        <script type="text/javascript">
            var tselection = false;
            var tmatches = false;
            var serverResponded = false;

            function toggleSelections() {
                tselection = !tselection;
                var tableelem = document.getElementById("dlfilematrixtable");
                var rowelems = tableelem.getElementsByTagName("tr");
                for (var i = 2; i < rowelems.length; i++) {
                    if (rowelems[i].style.display !== "none") {
                       rowelems[i].getElementsByTagName("input")[0].checked = tselection;
                    }
                }
            }

            function toggleMatches() {
                tmatches = !tmatches;
                var tableelem = document.getElementById("dlfilematrixtable");
                var rowelems = tableelem.getElementsByTagName("tr");
                for (var i = 2; i < rowelems.length; i++) { 
                    var tdelems = rowelems[i].getElementsByTagName("td");
                    for (var j = 0; j < tdelems.length; j++) {
                        if ((tdelems[j].id === "mismatchedcell") && (tdelems[j].innerHTML > 0)) {
                            rowelems[i].style.display = "table-row";
                            break;
                        } else {
                            rowelems[i].style.display = ((tmatches) ? "none" : "table-row");                   
                        }
                    }
                }
            }

            function makeHTTPrequest(event, to_server_file_url) {
                if (event.preventDefault) event.preventDefault(); else event.returnValue = false; // event.preventDefault() not available in MSIE
                var folderlist = Array();
                var tableele = document.getElementById("dlfilematrixtable");
                var roweles = tableele.getElementsByTagName("tr");
                for (var i = 2; i < roweles.length; i++) {
                    if (roweles[i].getElementsByTagName("input")[0].checked) {
                        folderlist.push(roweles[i].getElementsByTagName("td")[1].innerHTML);
                    }
                }
                var str_json = "json_string=" + JSON.stringify(folderlist);
                request = new XMLHttpRequest();
                serverResponded = false;
                request.open("POST", to_server_file_url);
                request.setRequestHeader("Content-type", "application/x-www-form-urlencoded", true);
                request.send(str_json);
                setTimeout(function() {if (!serverResponded) alert("No Response From Server"); return false;}, 4000);
                request.onreadystatechange = function() {
                    var tablecaption = document.getElementById("tablecaption");
                    switch (request.readyState) {
                        case 1 : tablecaption.innerHTML = "HTTP Request OPENED"; break;
                        case 2 : tablecaption.innerHTML = "HTTP Request HEADERS_RECEIVED"; serverResponded = true; break;
                        case 3 : tablecaption.innerHTML = "HTTP Request LOADING"; serverResponded = true; break;
                        case 4 : tablecaption.innerHTML = "HTTP Request DONE"; alert(request.responseText); serverResponded = true; break;
                        default: alert("Unknown Response");    
                    }
                };
            }
        </script>

        <style type="text/css">
            h2 {
                font-family: tahoma,arial,helvetica,sans-serif;
                font-size: 18px;
                font-weight: bold;
            }
            .table {
                display: table;
                width: 80%;        
                background-color: #9D9248;
            }
            .tableRow {
                display: table-row;
                height: 16px;
            }
            .tableCell {
                text-align: center;
                font-size: 12px;
                white-space: normal;
                color: black;
                border: 1px solid white;
            }
            .tableCell.head
            {
                font-weight: bold;
                background-color: #86815F;
            }
            .auto-style4 {
                font-family: Arial, Helvetica, sans-serif;
                font-size: xx-large;
                color: #000000;
                text-align: left;
                text-decoration: none;
            }
            .auto-style5 {
                font-family: Arial, Helvetica, sans-serif;
                font-size: medium;
                color: #000000;
                text-align: right;
                text-decoration: none;
            }
        </style>
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
                <div id="medialist-page-border" style="border: 3px solid #9D9248; margin:auto; visibility: visible; position: relative">

                    <h2>Image File Upload Process</h2>

                    <!--************************  BEGIN = Build the input form ************************-->

                    <form name="dlform" action="" method="POST" onsubmit="makeHTTPrequest(event, '<?php echo FILE_MISMATCH_PAGE_URL . '?userid=' . $uid ?>')">
                        <input type="hidden" name="userid" value="<?php echo $uid; ?>">
                        <table width="400px"  align="center" >
                            <tr>
                                <td class="descriptionbox wrap width25">Show Only Mismatches<br></td>
                                <td class="optionbox wrap width25" >
                                    <input type="checkbox" id="displaymismatches" onclick="toggleMatches()">
                                </td>
                            </tr><tr>
                                <td class="descriptionbox wrap width25">Toggle Select All/None<br></td>
                                <td class="optionbox wrap width25">
                                    <input type="checkbox" id="checkall" onclick="toggleSelections()">
                                </td>
                            </tr><tr>
                                <td class="descriptionbox wrap width25">Correct File Mismatches</td>
                                <td class="optionbox wrap width25">
                                    <input type="submit" name="update" value="Submit">
                                </td>
                            </tr></table>

                        <?php
            // ************************  END = 'Build the input form' ************************
            // ************************  BEGIN = 'Print the medialist array' ************************

                        echo '<div align="center" style="color: black; padding-top: 10px;">';
                        echo '<span id="tablecaption">Total image folders found: ' . count($folderlist) . '</span><br><br>';
                        $i = 0;
                        if ($ct > 0) {
                            echo '<table class="table" id="dlfilematrixtable" style="cellpadding:0px; cellspacing: 0px;">',
                                '<tr class="tableRow">',
                                    '<td class="head tableCell" style="width: 35px" rowspan="2">#</td>',
                                    '<td class="head tableCell" style="width: 600px" rowspan="2">Folder Name</td>',
                                    '<td class="head tableCell" style="width: 350px" rowspan="2">Album Name</td>',
                                    '<td class="head tableCell" style="width: 175px;" colspan="5">Number of Images</th>',
                                    '<td class="head tableCell" style="width: 140px;" colspan="4">Number of Mismatches</td>',
                                    '<td class="head tableCell" style="width: 40px" rowspan="2">Select</td>',
                                '</tr>',
                                '<tr class="tableRow">',
                                    '<td class="head tableCell" style="width: 35px">F</td>',
                                    '<td class="head tableCell" style="width: 35px">L</td>',
                                    '<td class="head tableCell" style="width: 35px">M</td>',
                                    '<td class="head tableCell" style="width: 35px">S</td>',
                                    '<td class="head tableCell" style="width: 35px">T</td>',
                                    '<td class="head tableCell" style="width: 35px">L</td>',
                                    '<td class="head tableCell" style="width: 35px">M</td>',
                                    '<td class="head tableCell" style="width: 35px">S</td>',
                                    '<td class="head tableCell" style="width: 35px">T</td>',
                                '</tr>';

                            $image_matrix = Array();
                            $numfiles = Array();
                            foreach ($folderlist as $onefile) {   //  Begin looping through the media
                                $resourcefile = str_replace('/images', '', $onefile) . '/resources/mediaGroupData/group.xml';  //  Get Adode Lightroom Flash Player album description and title
                                if (file_exists($resourcefile)) {
                                    $resourcedoc = new DOMDocument();
                                    @$resourcedoc->load($resourcefile);
                                    $albumtitle = @$resourcedoc->getElementsByTagName('groupTitle')->item(0)->nodeValue;
                                } else {
                                    $albumtitle = '';
                                }
                                echo '<tr class="tableRow">';     //  Open matrix row
                                echo '<td class="tableCell">' . ($i + 1) . '</td>';
                                echo '<td class="tableCell" style="text-align: left; padding-left: 4px;">' . $onefile . '</td>';
                                echo '<td class="tableCell" style="text-align: left; padding-left: 4px;">' . $albumtitle . '</td>';
                                foreach (array('full', 'large', 'medium', 'small', 'thumb') as $subfolder) {  //  Get the individual images in each size folder
                                    $image_matrix[$subfolder] = array_filter(scandir($onefile . '/'. $subfolder), 'test_if_img_file');  //  Just get the images from the folder
                                    $numfiles[$subfolder] = count($image_matrix[$subfolder]);
                                    echo '<td class="tableCell">' . $numfiles[$subfolder] . '</td>';
                                }
                                foreach (array('large', 'medium', 'small', 'thumb') as $subfolder) {  //  Get the individual images in each size folder
                                    $num_files_mismatched = $numfiles['full'] - count(array_intersect($image_matrix['full'], $image_matrix[$subfolder]));
                                    echo '<td class="tableCell" id="mismatchedcell">' . $num_files_mismatched . '</td>';
                                }
                                echo '<td class="tableCell"><input type="checkbox"></td>';
                                echo '</tr>';      //  Close row
                                $i++;
                            } // end media loop
                            echo '</table><br>';   //  Close table
                        }
                        echo '</div>'; // close div containing total size and file matrix
                        ?>
                    </form> <!--close form containing top selector table and file matrix-->
                </div> <!--close medialist-page-border div-->
            </div> <!--close medialist-page div-->
        </div> <!--close outerframe div-->
    </body>
</html>
<?php
    function test_if_img_file($flnm) {
        return in_array(strtolower(pathinfo($flnm, PATHINFO_EXTENSION)), array('jpg', 'bmp', 'tiff', 'gif'));
    }
?>

