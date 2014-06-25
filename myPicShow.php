<?php
//  This runs the galleria photo viewer for all pics on the website
//  GET params are folder=gallery/.../images; userid=#; type=computer, phone, tablet; height=# pixels 
define('WT_SCRIPT_NAME', 'myPicShow.php');

require 'mysession.php';

$folder = safe_GET('folder');
$folder = ($folder) ? $folder : 'gallery/2001%20Fall/images';
$resourcefile = str_replace('/images', '', $folder) . '/resources/mediaGroupData/group.xml';  //  Get Adode Lightroom Flash Player album description and title
$resourcedoc = new DOMDocument();
$resourcedoc->load($resourcefile);
$albumtitle = $resourcedoc->getElementsByTagName('groupTitle')->item(0)->nodeValue;
$albumdescription = $resourcedoc->getElementsByTagName('groupDescription')->item(0)->nodeValue;
//echo 'Resource Folder: ' . $resourcefile . ', Album title: ' . $albumtitle . ', Album description: ' . $albumdescription . '<br>';

//$devicetype = safe_GET('type');
//$deviceheight = safe_GET('height');
$picsize = ($devicetype == 'phone') ? 'small' : 'medium';  //  Set resoltion for phone and all others
$medialist = (($folder) ? scandir($folder . '/' . $picsize) : array()); //  $picsize is full/large/medium/small/thumb

$ct = 0;
$totsize = 0;
if (!empty($medialist)) {
    $files_to_remove = array(".", "..");
    foreach ($medialist as $key => $media) {
        if (!is_dir($media)) {
            if (!in_array(strtolower(pathinfo($media, PATHINFO_EXTENSION)), array('jpg', 'bmp', 'tiff', 'gif', 'mov', 'mp4', 'avi'))) {  // File is not of an acceptable type for the list
                array_push($files_to_remove, $media);
            } else {  //  File is of an acceptable type for the list
                $ct++;
                $totsize += filesize($folder . '/' . $picsize . '/' . $media);
            }
//            echo $media . ' - ';
//            echo filesize($folder.'/full/'.$media); 
//            $exif = exif_read_data($folder.'/full/'.$media,'EXIF',0);
//            $ifd0 = exif_read_data($folder.'/full/'.$media,'IFD0',0);
//            if ($exif) {
//                echo '-----------------EXIF<br>';
//                foreach ($exif as $key => $section) {
//                    if (!is_array($section)) {
//                        echo $key . '=' . $section . '<br>';
//                    }
//                    else {
//                        foreach ($section as $name => $val) {
//                            echo "***** $key.$name: $val<br />\n";
//                        }
//                    }    
//                }
//            }    
//            if ($ifd0) {
//                echo '-----------------IFD0<br>';
//                foreach ($ifd0 as $key => $section) {
//                    if (!is_array($section)) {
//                        echo $key . '=' . $section . '<br>';
//                    }
////                    else {
////                        foreach ($section as $name => $val) {
////                            echo "****** $key.$name: $val<br />\n";
////                        }
////                    }    
//                }
//            }   
        }
    }
    $medialist = array_diff($medialist, $files_to_remove);  //  Remove non-image files and directories from the list
    $medialist = array_values($medialist);
    $galleriaarray = array();
    $tmpmedia = array();

    foreach ($medialist as $media) {
        if (!in_array(strtolower(pathinfo($media, PATHINFO_EXTENSION)), array('mov', 'mp4', 'avi'))) {  // Not a video
            $correctedfullthumbname = str_replace('\\', '/', $folder) . '/thumb/' . $media;
            if ($devicetype !== 'phone') {
                $tmpmedia['thumb'] = $correctedfullthumbname;
            }
            $tmpmedia['image'] = $folder . '/' . $picsize . '/' . $media;
            $tmpmedia['big'] = $folder . '/large/' . $media;
            $exifdescription = "";
            $exif = exif_read_data($correctedfullthumbname, 'EXIF', 0);  //  Read EXIF from thumb size images regardless of $picsize      
            if ($exif) {
                $exifdescription = (!empty($exif['ImageDescription'])) ? $exif['ImageDescription'] : ' ';
            } else {
                $exifdescription = ' ';
            }
            $tmpmedia['title'] = $exifdescription;
            $sizepic = getimagesize($correctedfullthumbname, $picinfo);  //  This is how we get IPTC info
            if (is_array($picinfo) && array_key_exists("APP13", $picinfo)) {
                $iptc = iptcparse($picinfo["APP13"]);
                if ($iptc) {
                    $iptcdesc = (key_exists("2#120", $iptc) ? $iptc["2#120"][0] : '');
                    $iptccapt = (key_exists("2#005", $iptc) ? $iptc["2#005"][0] : '');
                    if ($iptccapt) {  //
                        $tmpmedia['title'] = preg_replace("/[^a-zA-Z0-9-!.,'\/\s]/", '', $iptccapt);  //  Allowed characters are specified and / is escaped with \
                        $tmpmedia['description'] = ($exifdescription) ? preg_replace("/[^a-zA-Z0-9-!.,'\/\s]/", '', $exifdescription) : preg_replace("/[^a-zA-Z0-9-!.,'\/\s]/", '', $iptcdesc);
                    } else {
                        $tmpmedia['description'] = " ";
                        $tmpmedia['title'] = ($exifdescription) ? preg_replace("/[^a-zA-Z0-9-!.,'\/\s]/", '', $exifdescription) : preg_replace("/[^a-zA-Z0-9-!.,'\/\s]/", '', $iptcdesc);
                    }
                }
            }
        } else {  //  We are dealing with a video file for youTube
            $tmpmedia['video'] = 'http://www.youtube.com/watch?v=' . pathinfo($media, PATHINFO_FILENAME);
            if ($devicetype !== 'phone') {
                $tmpmedia['thumb'] = 'http://img.youtube.com/vi/' . pathinfo($media, PATHINFO_FILENAME) . '/default.jpg';
            }
        }

        $galleriaarray[] = $tmpmedia;
//        foreach ($tmpmedia as  $key=>$val) {
//            echo 'tmpmedia - ' . $key . '=> ' . $val . '<br>';
//        }
        $tmpmedia = array();
    }
    $galleriadata = json_encode($galleriaarray);
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
    <head>
        <meta content="en-us" http-equiv="Content-Language" />
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
        <script src="js/jquery-1.9.1.js"></script>
        <script src="js/galleria/galleria-1.3.5.min.js"></script>
        <link rel="stylesheet" href="js/galleria/themes/azur/galleria.azur.css"></link>
        <script src="js/galleria/themes/azur/galleria.azur.min.js"></script>
        <title>Bashkoff Family Website</title>
        <style type="text/css">
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
            .auto-style5small {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 18pt; font-weight: 900; 
                color: #000000;
                text-align: right;
                text-decoration: none;
            }
            a.auto-style4:hover {color: red;}
            a.auto-style5:hover {color: red;}
        </style>
        <style>
            #galleria{ height: 100%; background: #FFFFFF } 
        </style>
    </head>

    <body style="overflow: hidden; background-color: #3C391B">
        <div id="outerframe" style="border: 3px solid #86815F;  background-color:white; position: relative; margin:auto; padding-bottom:4px; z-index: 1; top: 0px; left: 0px; color: #FFFFFF; visibility: visible;">
            <script type="text/javascript">
                function adjustwindowsize(screenwidth, screenheight) {
                    if (document.getElementById("galleria"))
                        document.getElementById("galleria").style.height=(screenheight - (("<?php echo $devicetype; ?>" != 'computer') ? (("<?php echo $devicetype; ?>" === 'phone') ? 55 : 52) : 102)) + "px";
                    document.getElementById("outerframe").style.height=(screenheight - (("<?php echo $devicetype; ?>" != 'computer') ? (("<?php echo $devicetype; ?>" === 'phone') ? 10 : 9) : 30)) + "px";
                    document.getElementById("outerframe").style.width=(screenwidth - (("<?php echo $devicetype; ?>" != 'computer') ? (("<?php echo $devicetype; ?>" === 'phone') ? 10 : 9) : 32)) + "px";
                }
                adjustwindowsize($(window).width(), $(window).height());
            </script>
            <div id="fullhead" style="display: block; margin:auto; width: 99%; height: 52px; padding-left: 0px; padding-right: 0px; padding-top: 4px; padding-bottom: 4px; color: #36341A; background-color: #86815F; visibility: visible; position: relative; top: 4px; left: 0px">
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
                            ' | <a  href="' . FILE_PATH_PREFIX . 'mydownloadpics.php?folder=' . $folder . '&userid=' . $uid . '" class="auto-style5">Download</a>',
                            ' | <a  href="' . FILE_PATH_PREFIX . 'mymaps.php?folder=' . $folder . '&userid=' . $uid . '" class="auto-style5">Map</a>',
                            ' | <a  href="' . FILE_PATH_PREFIX . 'index.php?logout=1" class="auto-style5">Logout</a>';
                        }
                        ?>
                        </span>
                    </div>
                    <div style="height: 50%; vertical_align: bottom">
                        <span class="auto-style5" style="float: right; text-align: right" title="<?php echo $albumdescription; ?>"><?php echo $albumtitle; ?></span>
                    </div>
                </div>
            </div>
            <div id="shorthead" style="display: none; margin: auto; width: 99%; height: 34px; font-size: 28pt; padding-left: 0px; padding-right: 0px; padding-top: 4px; color: #36341A; background-color: #86815F; visibility: visible; position: relative; top: 4px; left: 0px">
                <div style="width: 100%; height: 100%; padding-bottom: 4px; padding-right: 4px; padding-left: 4px">
                    <span class="auto-style5small" style="float: left; font-weight: 900;"><?php echo $albumtitle; ?></span>
                    <span class="auto-style5small" style="float: right; margin-right: 12px; font-weight: initial;">
                        <?php
                        $fltempstr = substr(dirname(__FILE__), strrpos(dirname(__FILE__), 'gallery'));
                        if ($uid != 0) {
                            echo 'Logged in as: <a href="' . FILE_PATH_PREFIX . 'edituser.php" class="auto-style5small">' . $realusername . '</a>',
                            ' | <a  href="' . FILE_PATH_PREFIX . 'myportal.php?userid=' . $uid . '" class="auto-style5small">Home</a>',
                            ' | <a  href="' . FILE_PATH_PREFIX . 'mymaps.php?folder=' . $folder . '&userid=' . $uid . '&shorthead=small" class="auto-style5small">Map</a>',
                            ' | <a  href="' . FILE_PATH_PREFIX . 'index.php?logout=1" class="auto-style5small">Logout</a>';
                        }
                        ?>                        
                    </span>
                </div>                            
            </div>         
            <div id="limebar" style="margin:auto; width: 99%; text-align: center; padding-left: 0px; padding-right: 0px; padding-top: 0px; color: #36341A; background-color: #9D9248; visibility: visible; position: relative; top: 0px; left: 0px; z-index: 2;">
            </div>
            <script type="text/javascript">
                if (<?php echo '"' . $devicetype . '"'; ?> !== "computer") {
                    document.getElementById("fullhead").style.display = "none";
                    document.getElementById("shorthead").style.display = "block";
                    document.getElementById("limebar").style.height = "4px";
                    document.getElementById("outerframe").style.position = "absolute";
                    document.getElementById("outerframe").style.width = "99%";
                    if (<?php echo '"' . $devicetype . '"'; ?> === "tablet") {
                        document.getElementById("outerframe").style.height = "99%";
                    }
                } else {
                    document.getElementById("shorthead").style.display = "none";
                    document.getElementById("fullhead").style.display = "block";
                    document.getElementById("limebar").style.height = "12px";
                }
            </script>
            <div id="galleria" align="center" style="margin:auto; width: 99%; height: 100%; text-align: center; padding-left: 0px; padding-right: 0px; padding-top: 0px; color: #36341A; background-color: #FFFFFF; visibility: visible; position: relative; top: 0px; left: 0px; z-index: 2;">
                <script>
                    adjustwindowsize($(window).width(), $(window).height());
                    var data = <?php echo $galleriadata; ?>

                    Galleria.configure({
                        imageTimeout: 960000,
                        debug: false,
                        imageCrop: 'height',
                        dummy: 'themes/olivegreen/images/silhouette_unknown.png',
                        lightbox: <?php echo (($devicetype === 'computer') ? 'true' : 'false'); ?>,
                        fullscreenDoubleTap: <?php echo (($devicetype == 'phone') ? 'true' : 'false'); ?>,
                        thumbnails: <?php echo (($devicetype !== 'phone') ? '"lazy"' : 'false'); ?>,
                        extend: function() {
                            <?php echo (($devicetype !== 'phone') ? 'this.lazyLoadChunks(10);' : ''); ?>
                            this.setPlaytime(3000);
                            this.attachKeyboard({
                                left: this.prev,
                                right: this.next
                            });
                        }
                    });
                    Galleria.run('#galleria', {
                        dataSource: data
                    });
                    $(window).resize(function () {adjustwindowsize($(window).width(), $(window).height());});
                </script>
            </div>
        </div>
    </body>
</html>