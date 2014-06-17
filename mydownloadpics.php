<?php
define('WT_SCRIPT_NAME', 'mydownloadpics.php');
require 'mysession.php';

$update = safe_POST('update');
$download = safe_POST('download');
$dllist = array();

if (($update && ($update == "Update")) || ($download && ($download == "Download"))) {  //  Form was posted to update
    $picsize = strtolower(safe_POST('picsize', array('Full', 'Large', 'Medium', 'Small', 'Thumb'), 'full'));
    $folder = safe_POST('folder');
    $columns = safe_POST('columns', array('1', '2', '3', '4', '5'), '2');
    $listview = safe_POST('listview');
    $checkall = safe_POST('checkall');
} else {  //  Direct call with URL and GET params
    $picsize = strtolower(safe_GET('picsize', array('Full', 'Large', 'Medium', 'Small', 'Thumb'), 'full'));
    $folder = safe_GET('folder');
    $columns = safe_GET('columns', array('1', '2', '3', '4', '5'), '3');
    $listview = safe_GET('listview');
    $checkall = safe_GET('checkall');
}
// Specify default values for form

$show_listview = ($listview == 'on') ? true : false;
$showallchecked = ($checkall == 'on') ? true : false;

// ************************  BEGIN = 'Build the medialist array' ************************

$filelist = (($folder) ? scandir($folder . '/' . $picsize) : array()); //  $picsize is full/large/medium/small/thumb
$resourcefile = str_replace('/images', '', $folder) . '/resources/mediaGroupData/group.xml';  //  Get Adode Lightroom Flash Player album description and title
$resourcedoc = new DOMDocument();
$resourcedoc->load($resourcefile);
$albumtitle = $resourcedoc->getElementsByTagName('groupTitle')->item(0)->nodeValue;
$ct = 0;
$totsize = 0;
if (!empty($filelist)) {
    $files_to_remove = array(".", "..");
    foreach ($filelist as $key => $onefile) {
        if (!is_dir($onefile)) {
            if (!in_array(strtolower(pathinfo($onefile, PATHINFO_EXTENSION)), array('jpg', 'bmp', 'tiff', 'gif'))) {  // File is not of an acceptable type for the list
                array_push($files_to_remove, $onefile);
            } else {  //  File is of an acceptable type for the list
                $ct++;
                $totsize += filesize($folder . '/' . $picsize . '/' . $onefile);
            }
        }
    }
    $filelist = array_diff($filelist, $files_to_remove);  //  Remove non-image files and directories from the list
    $filelist = array_values($filelist);
}

if (($update && ($update == "Update")) || ($download && ($download == "Download"))) {
    $dllist = array_fill(0, $ct, 'off');
    foreach ($_POST as $a_dlitem => $vall) {
        $tempstr = substr($a_dlitem, 0, 2);
        if ($tempstr == 'dl') {
            $tempstr = substr($a_dlitem, 2);
            $dllist[(int) $tempstr] = $vall;
        }
    }
}
if ($download && ($download == "Download")) {
    $files_to_dl = array();
    foreach ($dllist as $ind => $vall) { //  $dllist contains on/off values for index positions of files in $filelist
        if ($vall == 'on') {
            array_push($files_to_dl, $filelist[$ind]);
        }
    }
    if (count($files_to_dl) === 0) {
        echo '<script type="text/javascript"> alert("You must select at least one file to download")</script>';
    } elseif (count($files_to_dl) === 1) {
        dl_file_resumable($folder . '/' . $picsize . '/' . $files_to_dl[0]);
    } else {  //  zip the files together
        $folderpieces = explode('/', $folder);
        if ((count($folderpieces) >= 3) && ($folderpieces[count($folderpieces) - 1] == 'images')) { // $folder name must have at least 3 subfolders and we need the second to last one 
            $zipfilename = $folderpieces[count($folderpieces) - 2];
        } else {
            $zipfilename = 'Zipped Photos';
        }
        $zipfilename = './' . $zipfilename . '.zip';
        $filebundle = new ZipArchive();
        if ($filebundle) {
            $filebundle->open($zipfilename, ZipArchive::CREATE);
            foreach ($files_to_dl as $afile) {
//                echo 'zip filename: ' . $zipfilename . ' added -> ' . $afile . '<br>';
                $filebundle->addFile($folder . '/' . $picsize . '/' . $afile, $afile);
            }
            $filebundle->close();
            dl_file_resumable(substr($zipfilename, 2));
        }
        if (file_exists($zipfilename)) {
            unlink($zipfilename);
        }
    }
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <head>
        <meta content="en-us" http-equiv="Content-Language" />
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
        <link rel="stylesheet" type="text/css" href="themes/olivegreen/style.css"/>
        <script type="text/javascript" src="js/jquery-1.9.1.js"></script>
        <script type="text/javascript"  src="js/myDivPopupImage.js"></script>
        <script type="text/javascript">
            var tstate = false;
            var tstatethumb = true;
            function toggle(count) {
                tstate = !tstate;
                for (var i = 0; i < count; i++) {
                    var tempstr = "dl" + i.toString();
                    var an_element = document.getElementsByName(tempstr)[0];
                    an_element.checked = tstate;
                }
            }

            function togglethumbs(count) {
                tstatethumb = !tstatethumb;
                for (var j = 0; j < count; j++) {
                    var onethumb = document.getElementById('thumbimage' + j);
                    var onefileinfo = document.getElementById('fileinfo' + j);
                    onethumb.style.display = ((tstatethumb) ? "block" : "none");
                    onefileinfo.style.width = ((tstatethumb) ? "66%" : "95%");
                }
            }
        </script>
        <style type="text/css">
            h2 {
                font-family: tahoma,arial,helvetica,sans-serif;
                font-size: 18px;
                font-weight: bold;
            }
            #table {
                display: table;
                /*height: 8em;*/
                width:100%;
                background-color: #9D9248;
            }
            .tableRow {
                display: table-row;
            }
            .tableCell1 {
                display: table-cell;
                height: 7em;
                vertical-align: top;
            }
            .tableCell2 {
                display: table-cell;
                height: 1em;
                vertical-align: bottom;
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
            .auto-style6 {
                font-family: Arial, Helvetica, sans-serif;
                font-size: <?php echo ($BROWSERTYPE == 'chrome') ? 'small' : 'x-small'; ?>;
                color: #000000;
                text-align: left;
                text-decoration: none;
            }
        </style>
    </head>
    <body style="background-color: #3C391B; margin: 8px; margin-top: 2px">
        <div id="outerframe" style="border: 3px solid #86815F;  background-color:white; position: relative; margin:auto; width: 100%; top: 6px; left: 0px; color: #FFFFFF; visibility: visible;">
            <div id="fullhead" style="display: block; margin:auto; width: 99%; height: 52px; text-align: center; padding-left: 0px; padding-right: 0px; padding-top: 4px; padding-bottom: 4px; color: #36341A; background-color: #86815F; visibility: visible; position: relative; top: 4px; left: 0px">
                <div style="float: left; padding-left: 12px; margin-top: 12px;" >
                    <?php echo '<a class="auto-style4" href="' . FILE_PATH_PREFIX . 'myportal.php?userid=' . $uid . '"><strong>Bashkoff Family Web Site</strong></a>'; ?>
                </div>
                <div style="float: right; height: 100%; padding-right: 12px;">
                    <div style="height: 50%; vertical_align: top;">
                        <span class="auto-style5" style="float: right; text-align: right">
                        <?php
                        $fltempstr = substr(dirname(__FILE__), strrpos(dirname(__FILE__), 'gallery'));
                        if ($uid != 0) {
                            echo 'Logged in as: <a href="' . FILE_PATH_PREFIX . 'edituser.php" class="auto-style5">' . $realusername . '</a>',
                            ' | <a  href="' . FILE_PATH_PREFIX . 'myportal.php?userid=' . $uid . '" class="auto-style5">Home</a>',
                            ' | <a  href="' . FILE_PATH_PREFIX . 'myPicShow.php?folder=' . $folder . '&userid=' . $uid . '"' . ' class="auto-style5">Album</a>',
                            ' | <a  href="' . FILE_PATH_PREFIX . 'mymaps.php?folder=' . $folder . '&userid=' . $uid . '"' . ' class="auto-style5">Map</a>',
                            ' | <a  href="' . FILE_PATH_PREFIX . 'index.php?logout=1" class="auto-style5">Logout</a>';
                        }
                        ?>
                        </span>
                    </div>
                    <div style="height: 50%; vertical_align: bottom">
                        <span class="auto-style5" style="float: right; text-align: right"><?php echo $albumtitle; ?></span>
                    </div>
                </div>
            </div>
            <div id="limebar" style="margin:auto; height: 12px; width: 99%; text-align: center; padding-left: 0px; padding-right: 0px; padding-top: 0px; color: #36341A; background-color: #9D9248; visibility: visible; position: relative; top: 0px; left: 0px">
            </div>

            <div id="medialist-page" style="margin:auto; width: 99%; text-align: center; padding-bottom: 6px; color: #36341A; background-color: #FFFFFF; visibility: visible; position: relative; top: 0px; left: 0px">
                <div id="medialist-page-border" onmouseover="demagnifypic()" style="border: 3px solid #9D9248; margin:auto; visibility: visible; position: relative">

                    <h2>Select Download Files in <?php echo $albumtitle; ?></h2>

                    <!--************************  BEGIN = Build the input form ************************-->

                    <form <?php echo 'name="dlform" action="' . WT_SCRIPT_NAME . '?userid=' . $uid . '" method="POST"'; ?>>

                        <input type="hidden" name="folder" value="<?php echo $folder; ?>">
                        <input type="hidden" name="userid" value="<?php echo $uid; ?>">
                        <table width="400px"  align="center" >
                            <tr>
                                <td class="descriptionbox wrap width25">File Resolution:<br>
                                </td>
                                <td class="optionbox wrap width25">
                                    <select name="picsize">
                                        <?php
                                        foreach (array('Full', 'Large', 'Medium', 'Small', 'Thumb') as $selectEntry) {
                                            echo '<option value="', $selectEntry, '"';
                                            if (strtolower($selectEntry) == $picsize)
                                                echo ' selected="selected"';
                                            echo '>', $selectEntry, '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr><tr>
                                <td class="descriptionbox wrap width25">Columns per Page<br>
                                </td>
                                <td class="optionbox wrap width25">
                                    <select name="columns">
                                        <?php
                                        foreach (array('1', '2', '3', '4', '5') as $selectEntry) {
                                            echo '<option value="', $selectEntry, '"';
                                            if ($selectEntry == $columns)
                                                echo ' selected="selected"';
                                            echo '>', $selectEntry, '</option>';
                                        }
                                        ?>
                                    </select></td>
                            </tr><tr>
                                <td class="descriptionbox wrap width25">Show List View<br>
                                </td>
                                <td class="optionbox wrap width25">
                                    <input type="checkbox" id="listview" name="listview"
                                    <?php
                                    if ($show_listview) {
                                        echo 'checked="on"';
                                    }
                                    ?>>
                                </td>
                            </tr><tr>
                                <td class="descriptionbox wrap width25">Show Thumbnails<br>
                                </td>
                                <td class="optionbox wrap width25">
                                    <?php echo '<input type="checkbox" id="thumbnail" name="thumbnail" checked="on" onclick="togglethumbs(' . $ct . ')">'; ?>
                                </td>
                            </tr><tr>
                                <td class="descriptionbox wrap width25">Toggle Select All/None<br>
                                </td>
                                <td class="optionbox wrap width25">
                                    <input type="checkbox" id="checkall" name="checkall" onclick="toggle(<?php echo $ct; ?>)"
                                    <?php
                                    if ($showallchecked) {
                                        echo 'checked="on"';
                                    }
                                    ?>>
                                </td>

                            </tr><tr>
                                <td class="descriptionbox wrap width25">
                                </td>
                                <td class="optionbox wrap width25">
                                    <input type="submit" name="update" value="Update">
                                    <input type="submit" name="download" value="Download">
                                </td>
                            </tr></table>

                            <?php
                            // ************************  END = 'Build the input form' ************************
                            // ************************  BEGIN = 'Print the medialist array' ************************

                            echo '<div class="auto-style6" style="position: relative; color: black; padding-top: 10px; margin-left: auto; margin-right: auto; text-align: center;">',
                                 'Total images found: ', $ct, ', Total Size: ' . $totsize . ' Bytes';
                            echo ((!$show_listview) ? ' -- Hover over thumbnail to enlarge image' : '');
                            echo '<br><br>';
                            $i = 0;
                            if ($ct > 0) {
                                echo '<table id="dlfilematrixtable" align="center"'
                                    . (($show_listview) ? '' : ' style="position: relative; table-layout: fixed; width: ' . (19 * $columns) .'%; border-collapse: collapse;"') . '><tr>';
                                if ($show_listview) {  // Show a table header at beginning of file list
                                    echo '<td class="descriptionbox"><b>#</b></td>';
                                    echo '<td class="descriptionbox"><b>Title</b></td>';
                                    echo '<td class="descriptionbox"><b>File Name</b></td>';
                                    echo '<td class="descriptionbox"<b>File Type</b></td>';
                                    echo '<td class="descriptionbox"><b>File Size</b></td>';
                                    echo '<td class="descriptionbox"><b>Check to Download</b></td>';
                                    echo '</tr><tr>';
                                }
                                foreach ($filelist as $onefile) {  // begin looping through the media
                                    $exif = exif_read_data(str_replace('\\', '/', $folder) . '/thumb/' . $onefile, 'EXIF', 0);  //  Read EXIF from thumb size images regardless of $picsize
                                    if ($exif) {
                                        $exiffiletype = (!empty($exif['MimeType'])) ? $exif['MimeType'] : 'No EXIF MimeType';
                                        $exifdescription = (!empty($exif['ImageDescription'])) ? $exif['ImageDescription'] : ' ';
                                    } else {
                                        $exiffiletype = 'No EXIF MimeType';
                                        $exifdescription = ' ';
                                    }

                                    $sizepic = getimagesize(str_replace('\\', '/', $folder) . '/thumb/' . $onefile, $picinfo);  //  This is how we get IPTC info
                                    if (is_array($picinfo) && array_key_exists("APP13", $picinfo)) {
                                        $iptc = iptcparse($picinfo["APP13"]);
                                        if ($iptc) {
                                            $iptcdesc = (key_exists("2#120", $iptc) ? $iptc["2#120"][0] : '');
                                            $iptccapt = (key_exists("2#005", $iptc) ? $iptc["2#005"][0] : '');
                                            if ($iptccapt) {  //
                                                $exifdescription = preg_replace("/[^a-zA-Z0-9-!.,'\s]/", '', $iptccapt);
                                            } else {
                                                $exifdescription = ($exifdescription) ? preg_replace("/[^a-zA-Z0-9-!.,'\s]/", '', $exifdescription) : preg_replace("/[^a-zA-Z0-9-!.,'\s]/", '', $iptcdesc);
                                            }
                                        }
                                    }

                                    $exifdescription = (trim($exifdescription)) ? $exifdescription : 'No Title';

                                    $realfilesize = filesize($folder . '/' . $picsize . '/' . $onefile);
                                    if (!$show_listview) {  //  Show detailed file list
                                        echo '<td style="border: 2px solid white; background-color: #9D9248; width: 15%;" onmouseover="demagnifypic()">';
                                        //-- Thumbnail field
                                        $tempthumbfile = str_replace('\\', '/', $folder) . '/thumb/' . $onefile;
                                        $tempsmallfile = str_replace('/thumb/','/small/',$tempthumbfile);
                                        echo '<div id="thumbimage' . $i . '" style="display: inline-block; position: relative; height: 96px; float: left;">';
                                        echo '<div style="position: absolute; top: 0%;"><img src="' . $tempthumbfile . '" ';
                                            echo 'style="max-height: 96px; max-width: 96px;" ';
                                            echo 'onmouseover="magnifypic(\''.$tempsmallfile.'\', this, event)" ';
                                            echo 'onmouseout="demagnifypic()" ';
                                            echo 'alt="No Thumbnail"></div></div>';  //  End thumbnail div
                                        // Show file details
                                        echo '<div class="tableCell1 auto-style6" id="fileinfo'
                                            . $i . '" style="display: inline-block; width: ' . (($BROWSERTYPE === 'chrome') ? '66%' : '58%' )
                                            . '; float: right;">';  //  Begin file info div
                                        echo '<span style="margin-left: 4px;"><b>' . $exifdescription . '</b></span><br>';
                                        echo '<span style="margin-left: 4px;">File Name: ' . $onefile . '</span><br>';
                                        echo '<span style="margin-left: 4px;">File Type: ' . $exiffiletype . '</span><br>';
                                        echo '<span style="margin-left: 4px;">File Size: ' . $realfilesize . '</span><br>';
                                        echo '<input style="position: relative;" type="checkbox" name="dl' . $i . '"';
                                        if (key_exists($i, $dllist) && ($dllist[$i] == 'on')) {
                                            echo 'checked="on"';
                                        }
                                        echo '><---Download';
                                        echo '</div>';  //  End file info div
                                        echo '</td>';   //  Close cell containing thumbnail and file info
                                        if (($i % $columns) == ($columns - 1))
                                            echo '</tr><tr>';
                                    } else { //  List view details
                                        echo '<td class="optionbox" style="color: black;">' . ($i + 1) . '</td>';
                                        echo '<td class="optionbox" style="color: black;">' . $exifdescription . '</td>';
                                        echo '<td class="optionbox" style="color: black;">' . $onefile . '</td>';
                                        echo '<td class="optionbox" style="color: black;">' . $exiffiletype . '</td>';
                                        echo '<td class="optionbox" style="color: black;">' . $realfilesize . '</td>';
                                        echo '<td class="optionbox" style="color: black;""><input type="checkbox" name="dl' . $i . '"';
                                        if (key_exists($i, $dllist) && ($dllist[$i] == 'on')) {
                                            echo 'checked="on"';
                                        }
                                        echo '><---Download';
                                        echo '</td>';
                                        echo '</tr><tr>';
                                    }
                                    $i++;
                                } // end media loop
                                echo '</tr>';

                                echo '</table><br>';
                            }
                            echo '</div>';  // close div containing total size and file matrix
                            ?>
                            <div id="lowerdownloadbutton" class="optionbox wrap width25" style="margin-left: auto; margin-right: auto; margin-bottom: 10px;"  align="center">
                                <input style="align: center;" type="submit" name="download" value="Download">
                            </div>
                            <script type="text/javascript">document.getElementById("lowerdownloadbutton").style.width=document.getElementById("dlfilematrixtable").clientWidth-25;</script>
                        </form> <!--close form containing top selector table and file matrix-->
                    </div> <!--close medialist-page-border div-->
                </div> <!--close medialist-page div-->
        </div> <!--close outerframe div-->
    </body> <!--close body-->
</html>
<?php
// Below function from: http://us3.php.net/fread#84115 Edward Jaramilla
function dl_file_resumable($file, $is_resume = TRUE) {
    //First, see if the file exists
    if (!is_file($file)) {
        die("<b>404 File not found!</b>");
    }

    //Gather relevent info about file
    $size = filesize($file);
    $fileinfo = pathinfo($file);

    //workaround for IE filename bug with multiple periods / multiple dots in filename
    //that adds square brackets to filename - eg. setup.abc.exe becomes setup[1].abc.exe
    $filename = (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) ?
            preg_replace('/\./', '%2e', $fileinfo['basename'], substr_count($fileinfo['basename'], '.') - 1) :
            $fileinfo['basename'];

    $file_extension = strtolower($fileinfo['extension']);

    //This will set the Content-Type to the appropriate setting for the file
    switch ($file_extension) {
        case 'jpg': $ctype = 'image/jpeg';
            break;
        case 'gif': $ctype = 'image/gif';
            break;
        case 'tiff': $ctype = 'image/tiff';
            break;
        case 'bmp': $ctype = 'image/bmp';
            break;
        case 'exe': $ctype = 'application/octet-stream';
            break;
        case 'zip': $ctype = 'application/zip';
            break;
        case 'mp3': $ctype = 'audio/mpeg';
            break;
        case 'mpg': $ctype = 'video/mpeg';
            break;
        case 'avi': $ctype = 'video/x-msvideo';
            break;
        case 'zip': $ctype = 'application/zip';
            break;
        default: $ctype = 'application/force-download';
    }
    //echo 'File name in downloader:' . $file . '<br>';
    //check if http_range is sent by browser (or download manager)
    if ($is_resume && isset($_SERVER['HTTP_RANGE'])) {
        list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);

        if ($size_unit == 'bytes') {
            //multiple ranges could be specified at the same time, but for simplicity only serve the first range
            //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
            list($range, $extra_ranges) = explode(',', $range_orig, 2);
        } else {
            $range = '-';
        }
    } else {
        $range = '-';
    }

    //figure out download piece from range (if set)
    list($seek_start, $seek_end) = explode('-', $range, 2);

    //set start and end based on range (if set), else set defaults
    //also check for invalid ranges.
    $seek_end = (empty($seek_end)) ? ($size - 1) : min(abs(intval($seek_end)), ($size - 1));
    $seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)), 0);

    //add headers if resumable
    if ($is_resume) {
        //Only send partial content header if downloading a piece of the file (IE workaround)
        if ($seek_start > 0 || $seek_end < ($size - 1)) {
            header('HTTP/1.1 206 Partial Content');
        }

        header('Accept-Ranges: bytes');
        header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $size);
    }

    //headers for IE Bugs (is this necessary?)
    //header("Cache-Control: cache, must-revalidate");  
    //header("Pragma: public");

    header('Content-Type: ' . $ctype);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . ($seek_end - $seek_start + 1));

    //open the file
    $fp = fopen($file, 'rb');
    //seek to start of missing part
    fseek($fp, $seek_start);

    //start buffered download
    while (!feof($fp)) {
        //reset time limit for big files
        set_time_limit(0);
        print(fread($fp, 1024 * 8));
        flush();
        ob_flush();
    }

    fclose($fp);
}
?>

