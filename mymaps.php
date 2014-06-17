<?php
define('WT_SCRIPT_NAME', 'mymaps.php'); 
require 'mysession.php';

$update = safe_POST('update');
$folder = safe_GET('folder');
$shorthead = safe_GET('shorthead');

// ************************  BEGIN = 'Build the filelist array' ************************

$filelist = (($folder) ? scandir($folder . '/thumb') : array());

$resourcefile = str_replace('/images', '', $folder) . '/resources/mediaGroupData/group.xml';  //  Get Adode Lightroom Flash Player album description and title
$resourcedoc = new DOMDocument();
$resourcedoc->load($resourcefile);
$albumtitle = $resourcedoc->getElementsByTagName('groupTitle')->item(0)->nodeValue;
$ct = 0;
$geotaggedfiles = array();
if (!empty($filelist)) {
    $files_to_remove = array(".", "..");
    foreach ($filelist as $key => $onefile) {
        if (!is_dir($onefile)) {
            if (!in_array(strtolower(pathinfo($onefile, PATHINFO_EXTENSION)), array('jpg', 'bmp', 'tiff', 'gif'))) {  // File is not of an acceptable type for the list
                array_push($files_to_remove, $onefile);
            } else {  //  File is of an acceptable type for the list
                $exif = exif_read_data(str_replace('\\', '/', $folder) . '/thumb/' . $onefile, 'EXIF', 0);  //  Read EXIF from thumb size images regardless of $picsize      
                if ($exif) {
                    $exiffiletype = (!empty($exif['MimeType'])) ? $exif['MimeType'] : 'No EXIF MimeType';
                    $exifdescription = (!empty($exif['ImageDescription'])) ? $exif['ImageDescription'] : ' ';
                    $result = photo_getGPS($exif);
                } else {
                    $exiffiletype = 'No EXIF MimeType';
                    $exifdescription = ' ';
                    $result = null;
                }

                $sizepic = getimagesize(str_replace('\\', '/', $folder) . '/thumb/' . $onefile, $picinfo);  //  This is how we get IPTC info
                if (is_array($picinfo)) {
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
                if ($exif) {
                    if ($result) {
                        $geotaggedfiles[$onefile] = array(
                            'latitude'=>round($result['latitude'],6),
                            'longitude'=>round($result['longitude'],6),
                            'description'=>$exifdescription,
                            'filename'=>$onefile,
                            'filetype'=>$exiffiletype,
                            'orientation'=>(($sizepic[0]>$sizepic[1]) ? 'landscape' : 'portrait'));
                        $ct++;
                    }
                } 
            }
        }
    }
    $filelist = array_diff($filelist, $files_to_remove);  //  Remove non-image files and directories from the list
    $filelist = array_values($filelist);
    
    $json_of_geotaggedfiles = json_encode($geotaggedfiles);
}
?>
<!--<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">-->
<!DOCTYPE html>
    <head>
        <meta content="en-us" http-equiv="Content-Language" />
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
        <link rel="stylesheet" href="js/jquerysmooth/css/smoothness/jquery-ui.min.css"/>
        <link rel="stylesheet" type="text/css" href="themes/olivegreen/style.css"/>
        <script type="text/javascript" src="js/modernizr.custom-2.6.2.js"></script>
        <!--<script type="text/javascript" src="js/webtrees-1.4.1.js"></script>-->
        <script type="text/javascript" src="js/jquery-1.9.1.js"></script>
        <script type="text/javascript" src="js/jquery-ui-1.10.0.js"></script>
        <script type="text/javascript" src="js/myGetWindowClientArea.js"></script>
        <!--Load progress bar below-->
        <script type="text/javascript">
            $( document ).ready(function() {
                $("#progressbar").progressbar({value: false});
            });
        </script>

        <style type="text/css">
            h2 {
                font-family: tahoma,arial,helvetica,sans-serif;
                font-size: 18px;
                font-weight: bold;
            }
            #table {
                display: table;
                height: 8em;
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
                font-size: small;
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
            .button-style {
               height: 25px;
               width: 25px;
               font-size: 15px;
               font-weight: bold;
            }
            a.auto-style4:hover {color: red;}
            a.auto-style5:hover {color: red;}
            a.auto-style6:hover {color: #FF00FF;}
            a.auto-style6 {color: #000000;}
        </style>
<!--        Make sure in loading GoogleMaps API, libraries=geometry is specified since this is used by call to spherical.computeDistanceBetween-->
<!--        Also make sure simple API key is specified as shown--> 
        <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBkrg103gMb81QFZs9uon0WSTdgBrXFVNg&v=3.exp&libraries=geometry&sensor=false"></script>
        <script type="text/javascript" src="js/myGoogleLabel.js"></script>

        <script type="text/javascript">
            var RADIUSOFEARTH = 3959;      // In miles
            var MINCLUSTERDIMENSION = 1;   //  In miles
            var PERCENTAGEOFDIAGONAL = 2;  //  Percentage of map diagonal distance determines radius of cluster
            var infoWindows = new Array();
            var labels = new Array();
            var googlemarkers = new Array();
            var geotaggedfiles = new Array();
            var bounds = new google.maps.LatLngBounds();
            var map;
            var slideshowctr;

            if (!Array.prototype.indexOf) {  // This section is needed for MSIE which cannot handle indexOf function without this
              Array.prototype.indexOf = function (searchElement , fromIndex) {
                var i,
                    pivot = (fromIndex) ? fromIndex : 0,
                    length;

                if (!this) {
                  throw new TypeError();
                }

                length = this.length;

                if (length === 0 || pivot >= length) {
                  return -1;
                }

                if (pivot < 0) {
                  pivot = length - Math.abs(pivot);
                }

                for (i = pivot; i < length; i++) {
                  if (this[i] === searchElement) {
                    return i;
                  }
                }
                return -1;
              };
            }

            function initialize() {
                var initialload = true;
                var zoomaction = false;
                var panaction = false;
                var mapOptions = {
                    zoom: 7,
                    center: new google.maps.LatLng(39.109, -96.550),
                    scaleControl: true,
                    zoomControl: true,
                    streetViewControl: false,
                    minZoom: 2,
                    zoomControlOptions: {
                        style: google.maps.ZoomControlStyle.SMALL,
                        position: google.maps.ControlPosition.TOP_RIGHT
                    },
                    panControl: false,
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                }
                if (document.getElementById('map-canvas') !== null) {
                    map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
                } else {
        //            return;
                }
                geotaggedfiles = <?php echo $json_of_geotaggedfiles ?>;

                // The following section is a workaround for Object.keys(geotaggedfiles).length which does not work in MSIE
                Object.size = function(obj) {
                    var size = 0, key;
                    for (key in obj) {
                        if (obj.hasOwnProperty(key)) size++;
                    }
                    return size;
                };

                // Below statement requires code above as workaround for MSIE
                if (Object.size(geotaggedfiles) === 0) {
                    takedownprogressoverlay();
                    return;
                }
                generatemarkersfromfiles(initialload, 20/5280);  //  Initial cluster radius in miles

                google.maps.event.addListener(map, "zoom_changed", function(event) {
                    zoomaction = true;
                    if (!initialload) {
                        generatemarkersfromfiles(initialload, calculateclusterradius());
                        document.getElementById("mymappageheading").innerHTML = "Total geocoded images found: "
                            + <?php echo sizeof($geotaggedfiles); ?>
                            + ", Zoom level: " + map.getZoom() + ", Cluster Radius: " + Math.round(calculateclusterradius()*100)/100 + " miles"
                            + " -- Click on tag to show images";
                    }
                });

                google.maps.event.addListener(map, "center_changed", function() {
                    panaction = true;
                });

                google.maps.event.addListener(map, "tilesloaded", function() {
                   if (initialload) takedownprogressoverlay();
                   if ((!zoomaction) && (!panaction)) {
                       adjustwindowsize(true);
                       zoomaction = false;
                       panaction = false;
                   } else {
                       adjustwindowsize(initialload);
                   }
                   initialload = false;
                   var winClientArea = getWindowClientArea();  //  The following removes the listeners for the thumbnails
                   if (winClientArea['type'] === 'phone') {
                       var imgTagsOnRight = document.getElementById("filelistonright").getElementsByTagName("img");
                       for (var oneImgIndex = 0; oneImgIndex < imgTagsOnRight.length; oneImgIndex++) {
                           imgTagsOnRight[oneImgIndex].onmouseover = function() {null;};
                           imgTagsOnRight[oneImgIndex].onmouseout = function() {null;};
                       }
                   }
                });
            }

            function generatemarkersfromfiles(initialload, clusterradius) {
                if (!initialload) {
                    for (i = 0; i < googlemarkers.length; i++) {
                        googlemarkers[i].setMap(null);
                        labels[i].setMap(null);
                    }
                }

                // Empty arrays
                infoWindows.length = 0;
                labels.length = 0;
                googlemarkers.length = 0;

                var tempgtfiles = new Array();
                for (var onetaggedfile in geotaggedfiles) {
                    geotaggedfiles[onetaggedfile]['indexintogooglemarkers'] = -1;
                    tempgtfiles.push(onetaggedfile);
                }

                googlemarkerscount = 0;
                comparearraycounter = tempgtfiles.length;

                for (var onetaggedfile in geotaggedfiles) {
                    if(geotaggedfiles[onetaggedfile]['indexintogooglemarkers'] == -1) { // Only look at files not compared yet
                        if (comparearraycounter > 0) {  // Remove this file from file compare list
                            //  MSIE cannot handle indexOf method without Array.prototype.indexOf section above
                            tempgtfiles.splice(tempgtfiles.indexOf(onetaggedfile),1);
                            comparearraycounter--;
                        }
                        var markerLatLng = new google.maps.LatLng(geotaggedfiles[onetaggedfile]["latitude"],geotaggedfiles[onetaggedfile]["longitude"]);
                        var filearray = new Array();  // marker will have one filename initially
                        filearray.push(onetaggedfile);
                        var marker = new google.maps.Marker({
                            position: markerLatLng,
                            map: map,
                            icon: 'themes/olivegreen/images/icon6s.png',
                            animation: null,
                            filenames: filearray,
                            numberoffiles: 1,
                            zIndex: 1,
                            title: geotaggedfiles[onetaggedfile]["description"] + "\n"
                                + "Filename: " + geotaggedfiles[onetaggedfile]["filename"] + "\n"
                                + "Latitude: " + ((geotaggedfiles[onetaggedfile]["latitude"] > 0.0) ? "N " : "S ") + Math.abs(geotaggedfiles[onetaggedfile]["latitude"]) + "\n"
                                + "Longitude: " + ((geotaggedfiles[onetaggedfile]["longitude"] > 0.0) ? "E " : "W ") + Math.abs(geotaggedfiles[onetaggedfile]["longitude"])
                        });

                        //  Loop through remaining filenames to compare locations; if similar then add to filename list in marker
                        for (i = 0;  i < comparearraycounter; i++ ) {
                            filenametocompare = tempgtfiles[i];
                            //  alert(i + ": File: " + onetaggedfile + ", Compared to: " + filenametocompare);
                            var latLngToCompare = new google.maps.LatLng(geotaggedfiles[filenametocompare]["latitude"],geotaggedfiles[filenametocompare]["longitude"]);
                            if (google.maps.geometry.spherical.computeDistanceBetween(markerLatLng,latLngToCompare, RADIUSOFEARTH) <= clusterradius) {  // lat/longs are close
                                // alert("In Range");
                                marker.setPosition(null);
                                filearray.push(filenametocompare);
                                marker['filenames'] = filearray;
                                marker['numberoffiles'] = marker['numberoffiles'] + 1;
                                marker.setTitle("");
                                if (marker['numberoffiles'] > 9) {
                                    marker.setIcon('themes/olivegreen/images/icon6m.png');
                                } else if (marker['numberoffiles'] > 99) {
                                    marker.setIcon('themes/olivegreen/images/icon6l.png');
                                }
                                geotaggedfiles[filenametocompare]['indexintogooglemarkers'] = googlemarkerscount;  // Mark file in file source array as accounted for
                                tempgtfiles.splice(tempgtfiles.indexOf(filenametocompare),1);   //  Remove file from compare list
                                comparearraycounter--;
                                i--;  //  Decrement i since array element was removed
                            }  else {
        //                        alert("Not In Range");
                            }
                        }  //  End inner compare loop
                        googlemarkers.push(marker);
                        geotaggedfiles[onetaggedfile]['indexintogooglemarkers'] = googlemarkerscount;
                        googlemarkerscount++;
                   }  //  End if
               }  //  End out compare loop

                for (i = 0; i < googlemarkerscount; i++) {  //  Loop through markers to find clustered files
                    if (googlemarkers[i]['numberoffiles'] > 1) {  //  This is a marker with multiple files
                        var boundszone = new google.maps.LatLngBounds();
                        var concatenatedfilenames = "Filenames: \n";
                        for (j = 0; j < googlemarkers[i]['numberoffiles']; j++) {
                            fileonmarkerlist = googlemarkers[i]['filenames'][j];
                            concatenatedfilenames += fileonmarkerlist + "\n";
                            var latLngInZone = new google.maps.LatLng(geotaggedfiles[fileonmarkerlist]['latitude'],geotaggedfiles[fileonmarkerlist]['longitude']);
                            boundszone.extend(latLngInZone);
                        }
                        googlemarkers[i].setPosition(boundszone.getCenter());  //  Calculate center point of this zone of files and put it in group marker
                        googlemarkers[i].setTitle(concatenatedfilenames);
                    }
                }

        //       var bounds = new google.maps.LatLngBounds();
                for (i = 0; i < googlemarkerscount; i++) {  //  Loop through all markers to place them on map
                    //Below code adds label to marker
                    var label = new Label({map: map}, ((googlemarkers[i]['numberoffiles']<10) ? 'small' : 'large'));
                    label.bindTo('position', googlemarkers[i], 'position');
                    label.bindTo('text', googlemarkers[i], 'numberoffiles');
                    label.bindTo('zindex',googlemarkers[i], 'zIndex');
                    labels.push(label);

                    // Below code creates infowindow pop-up; first create content string
                    slideshowctr = 0;
                    var filenm = googlemarkers[i]['filenames'][0]  // First file name for this marker
                    var contentstring = "<div class=\"info-window\" id=\"slideshowdiv\" style=\"overflow: hidden; width: 230px;#####\">";
                    contentstring += "<div style=\"height: 30px\">";
                    contentstring += "<div style=\"float:left;\"><input type=\"button\" class=\"button-style\" name=\"bback\" value=\"<<\" onclick=\"displayslide(event, " + i + ");\">";
                    contentstring += "<input type=\"button\" class=\"button-style\" name=\"back\" value=\"<\" onclick=\"displayslide(event, " + i + ");\">";
                    contentstring += "<input type=\"button\" class=\"button-style\" name=\"forward\" value=\">\" onclick=\"displayslide(event, " + i + ");\">";
                    contentstring += "<input type=\"button\" class=\"button-style\" name=\"fforward\" value=\">>\" onclick=\"displayslide(event, " + i + ");\"></div>";
                    contentstring += "<span id=\"slideshowctrtxt\" style=\"float:right; font-weight: bold;\">1/" + googlemarkers[i]['numberoffiles'] + "</span>";
                    contentstring += "</div>";
                    contentstring += "<div id=\"slideshowimagediv\" style=\"border:1px solid black; padding: 4px; padding-bottom: 0px;\" align=center>";
                    if (geotaggedfiles[filenm]['orientation']==='landscape')
                        contentstring += "<img src=\"<?php echo $folder . '/small/'; ?>"  + filenm + "\" alt=\"No Image\" width=220px></div>";
                    if (geotaggedfiles[filenm]['orientation']==='portrait')
                        contentstring += "<img src=\"<?php echo $folder . '/small/'; ?>"  + filenm + "\" alt=\"No Image\" height=146px></div>";
                    contentstring += "<div id=\"slideshowcaption\" style=\"white-space:nowrap; overflow:hidden; text-overflow: ellipsis;\"><b>" + geotaggedfiles[filenm]["description"] + "</b><br>Filename: " + filenm + "</div>";
                    contentstring += "</div>";

                    var infoWindow = new google.maps.InfoWindow({
                        content: contentstring
                    });
                    infoWindows[i] = infoWindow;

                    google.maps.event.addListener(googlemarkers[i], 'click', function() {
                        for (j = 0; j < googlemarkerscount; j++) {
                            infoWindows[j].close();
                        }
                        infoWindows[googlemarkers.indexOf(this)].open(map, this);
                    });
                    bounds.extend(googlemarkers[i].getPosition());  //  Update region for all markers
                }
            }

            function calculateclusterradius() {
                var mapbounds = new google.maps.LatLngBounds();
                mapbounds = map.getBounds();
                var mapsizediagonal = Math.abs(google.maps.geometry.spherical.computeDistanceBetween(mapbounds.getNorthEast(), mapbounds.getSouthWest(), RADIUSOFEARTH));
                return (mapsizediagonal * PERCENTAGEOFDIAGONAL / 100);
            }

            function getBoundsHeightWidth(bnds) {
                var southEast = new google.maps.LatLng(bnds.getSouthWest().lat(), bnds.getNorthEast().lng());
                var ht = Math.abs(google.maps.geometry.spherical.computeDistanceBetween(bnds.getNorthEast(), southEast, RADIUSOFEARTH));
                var wid = Math.abs(google.maps.geometry.spherical.computeDistanceBetween(bnds.getSouthWest(), southEast, RADIUSOFEARTH));
                return {height: ht, width: wid};
            }

            function bouncemarker(filenm) {
                demagnifypic();
                panaction = true;
                temp = googlemarkers[geotaggedfiles[filenm]['indexintogooglemarkers']];
                temp.setAnimation(google.maps.Animation.BOUNCE);
                setTimeout(function() {
                    temp.setAnimation(null);
                },2000);
                map.panTo(temp.getPosition());
             }

            function takedownprogressoverlay() {
                $("#progressbar").progressbar("destroy");
                var ele = document.getElementById("progressbarparent");
                ele.style.height = "0px";
                ele.style.paddingTop = "0px";
                document.getElementById("outerframe").style.visibility = "visible";
                document.getElementById("progressbarparent").style.visibility = "hidden";
                document.getElementById("progressbartext").style.visibility = "hidden";
                document.getElementById("progressbar").style.visibility = "hidden";
                document.getElementsByTagName("body")[0].setAttribute("style", "background-color: #3C391B");
        //        document.getElementById("content").setAttribute("style", "padding-top: 7px");
            }

            function adjustwindowsize(adjustbounds) {
                var screenwidth = $(window).width();
                var screenheight = $(window).height();
                if ("<?php echo $shorthead; ?>" === "small") screenheight = screenheight + 36;
                if (document.getElementById("medialist-page-border")) {
                    document.getElementById("medialist-page-border").style.height=(screenheight - 102) + "px";
                    document.getElementById("filelistonright").style.height=(screenheight - 219) + "px";
                    document.getElementById("map-canvas").parentNode.style.height=(screenheight - 192) + "px";
                }
                document.getElementById("outerframe").style.height=(screenheight - (("<?php echo $shorthead; ?>" === "small") ? 48 : 22)) + "px";
                document.getElementById("outerframe").style.width=(screenwidth - 28) + "px";
                if (adjustbounds && map && bounds) map.fitBounds(bounds);  //  Set map view appropriate to all markers only on windowsize
            }

            function displayslide(event, markernumber) {
                 var totalfilenum = googlemarkers[markernumber]['numberoffiles'];
                 event = event || window.event; panaction = true;
                 var srcEle = event.srcElement || event.target;
                 var showdirection = srcEle.name;
                 if (showdirection === 'bback') slideshowctr = 0;
                 if (showdirection === 'back') slideshowctr += totalfilenum - 1;
                 if (showdirection === 'forward') slideshowctr++;
                 if (showdirection === 'fforward') slideshowctr = totalfilenum - 1;
                 slideshowctr %= totalfilenum;
                 var thisfile = googlemarkers[markernumber]['filenames'][slideshowctr];  //  This file's name

                 document.getElementById("slideshowctrtxt").innerHTML = (slideshowctr + 1) + "/" + totalfilenum;
                 var slideshowimagedivelement = document.getElementById("slideshowimagediv");
                 slideshowimagedivelement.style.display = "none";
                 if (slideshowimagedivelement.hasChildNodes()) {
                     var tempEle = slideshowimagedivelement.getElementsByTagName("img")[0];
                     if ("<?php echo $BROWSERTYPE; ?>" === "msie")  {  //IE browser
                         slideshowimagedivelement.removeChild(tempEle);
                     } else {  //  Firefox, Chrome and others
                         tempEle.remove();
                     }
                 }
                 var oImg = slideshowimagedivelement.appendChild(document.createElement("img"));
                 oImg.setAttribute("src", <?php echo '"' . $folder . '/small/"'; ?> + thisfile);
                 oImg.setAttribute("alt", "No Image");
                 if (geotaggedfiles[thisfile]['orientation']==='portrait')  oImg.setAttribute("height", "145px");
                 if (geotaggedfiles[thisfile]['orientation']==='landscape') oImg.setAttribute("width", "220px");
                 document.getElementById("slideshowcaption").innerHTML = "<b>" + geotaggedfiles[thisfile]["description"] + "</b><br>Filename: " + thisfile;
                 oImg.onload = function() {
                     slideshowimagedivelement.style.display = "block";
                 }
            }

        //Communicates to the web browser to run the initialize function when the web page loads
        google.maps.event.addDomListener(window, 'load', initialize);

        $(window).resize(function () {adjustwindowsize(true)});

        </script>
        <script type="text/javascript" src="js/myDivPopupImage.js"></script>
<!--    </head>-->
    <body style="background-color: white;">
        <div id="progressbarparent" style="position: fixed; visibility: visible; text-align:center; padding-top: 100px; width: 100%; height: 100%; z-index:100" >
            <div id="progressbar" style="visibility: visible; border: none; background-color: #3C391B; margin-right: auto; margin-left: auto; width:400px; "></div>
            <br><span id="progressbartext" style="visibility: visible; color: black;">Calculating clusters from image GPS coordinates...</span>
        </div>
        <div id="outerframe" style="visibility: hidden; border: 3px solid #86815F;  background-color:white; position: relative; margin:auto; z-index: 1; top: 0px; left: 0px; color: #FFFFFF;">
            <script type="text/javascript">
                adjustwindowsize(false);
            </script>
            <div id="fullhead" style="display: <?php echo (!$shorthead ? 'block' : 'none'); ?>; margin:auto; width: 99%; height: 52px; text-align: center; padding-left: 0px; padding-right: 0px; padding-top: 4px; padding-bottom: 4px; color: #36341A; background-color: #86815F; position: relative; top: 4px; left: 0px">
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
                            ' | <a  href="' . FILE_PATH_PREFIX . 'myPicShow.php?folder=' . $folder . '&userid=' . $uid . '" class="auto-style5">Album</a>',
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
            <div id="shorthead" style="display: <?php echo (($shorthead=='small') ? 'block' : 'none'); ?>; margin: auto; width: 99%; height: 34px; font-size: 28pt; padding-left: 0px; padding-right: 0px; padding-top: 4px; color: #36341A; background-color: #86815F; position: relative; top: 4px; left: 0px">
                <div style="width: 100%; height: 100%; padding-bottom: 4px; padding-right: 4px; padding-left: 4px">
                    <span class="auto-style5small" style="float: left; font-weight: 900;"><?php echo $albumtitle; ?></span>
                    <span class="auto-style5small" style="float: right; margin-right: 12px; font-weight: initial;">
                        <?php
                        $fltempstr = substr(dirname(__FILE__), strrpos(dirname(__FILE__), 'gallery'));
                        if ($uid != 0) {
                            echo 'Logged in as: <a href="' . FILE_PATH_PREFIX . 'edituser.php" class="auto-style5small">' . $usrnm . '</a>',
                            ' | <a  href="' . FILE_PATH_PREFIX . 'myportal.php?userid=' . $uid . '" class="auto-style5small">Home</a>',
                            ' | <a  href="' . FILE_PATH_PREFIX . 'myPicShow.php?folder=' . $folder . '&userid=' . $uid . '&album=' . $albumtitle . '" class="auto-style5small">Album</a>',
                            ' | <a  href="' . FILE_PATH_PREFIX . 'index.php?logout=1" class="auto-style5small">Logout</a>';
                        }
                        ?>
                    </span>
                </div>
            </div>
            <div id="limebar" style="margin:auto; height: <?php echo (($shorthead=='small') ? '4' : '8'); ?>px; width: 99%; text-align: center; padding-left: 0px; padding-right: 0px; padding-top: 0px; color: #36341A; background-color: #9D9248; position: relative; top: 0px; left: 0px">
            </div>

            <div id="medialist-page" style="margin:auto; width: 99%; text-align: center; padding-bottom: 6px; color: #36341A; background-color: #FFFFFF; position: relative; top: 0px; left: 0px">
                <div id="medialist-page-border" onmouseover="demagnifypic()" style="border: 3px solid #9D9248; margin:auto; position: relative">

                    <h2>Map for <?php echo $albumtitle; ?> Images</h2>
                    <!--Below title <p></p> will be modified in javascript-->
                    <div align="center" style="color: black"><p id="mymappageheading"><br></p>
                    <?php
                    $i = 0;
                    if ($ct > 0) {
                        echo '<div id="map-canvas-parent" align="center"><table width="100%" border="0" ><tr>';
                        //  Actual Google Map goes here
                        echo '<td valign="top"  width="70%"><div id="map_pane" style="border: 1px solid gray; color: black; width: 100%; height: 600px; position: relative; background-color: rgb(229, 227, 223); overflow: hidden; -webkit-transform: translateZ(0);">';
                        echo '<div id="map-canvas" style="width: 100%; height: 100%;"></div>';
                        echo '</div></td>';
                        echo '<td valign="top" width="30%"><div style="height: 603px">';
                        echo '<div align="center" width=100% height=28px; style="white-space: nowrap; font-size: small; padding-top: 5px; padding-bottom: 5px; border: 1px solid black;">Click on thumbnail or image title to locate on map</div>';
                        echo '<div id="filelistonright" style="overflow-y: auto; height: 95%; border: 1px solid black; border-top: 0px"><table width=100% style="border-collapse: collapse"><tr>'; //  Start file list table
                        foreach ($geotaggedfiles as $onefile=>$marker) {  // begin looping through the GPS tagged image files
                            $tempthumbfile = str_replace('\\', '/', $folder) . '/thumb/' . $onefile;
                            $tempsmallfile = str_replace('/thumb/','/small/',$tempthumbfile);
                            echo '<td onmouseover="demagnifypic()" style="border: 2px solid white; background-color: #9D9248">';
                            echo '<div align="center">';
                            echo '<img src="' . $tempthumbfile . '" onclick="bouncemarker(\''. $onefile . '\')" ';
                                echo 'style="max-width: 96px; max-height: 96px;" ';
                                echo 'alt="No Thumbnail" ';
                                echo 'onmouseover="magnifypic(\'' . $tempsmallfile . '\', this, event)" ';
                                echo 'onmouseout="demagnifypic()"';
                                echo '>';
                            echo '</div>';
                            echo '</td>';

                            echo '<td class="list_value_wrap" onmouseover="demagnifypic()" style="border:2px solid white; background-color: #9D9248">';
                            // Show file details
                            echo '<div class="tableCell1" align="left">';
                            echo '<a class="auto-style6" onclick="bouncemarker(\''. $onefile . '\')">' . $geotaggedfiles[$onefile]['description'] . '</a><br>';
                            echo 'File Name: ' . $geotaggedfiles[$onefile]['filename'] . '<br>';
                            echo 'File Type: ' . $geotaggedfiles[$onefile]['filetype'] . '<br>';
                            echo 'Latitude: '.(($geotaggedfiles[$onefile]['latitude']>0.0) ? 'N ' : 'S ').abs($geotaggedfiles[$onefile]['latitude']).'<br>';
                            echo 'Longitude: '.(($geotaggedfiles[$onefile]['longitude']>0.0) ? 'E ' : 'W ').abs($geotaggedfiles[$onefile]['longitude']);

                            echo '</div>';  // close div cell, close div row, close div table
                            echo '</td>';
                            echo '</tr><tr>';
                            $i++;
                        } // end tagged files loop
                        echo '</tr>';

                        echo '</table></div><br>';  //  Done with file list table
                        echo '</tr></table>';  //  Done with map/list table
                    } else {
                        echo '<div align="center" style="color: black"><p>Sorry, there are no geotagged images in this folder</p>';
                    }
                    echo '</div>';
                    ?>
                    </div></br></br></br><!--close div containing total size and file matrix-->
                </div><!--close medialist-page-border div-->
            </div><!--close medialist-page div-->
        </div><!--close outerframe div-->
    </body> <!--close body-->
</html>
<?php
function photo_getGPS($exif) {
    //get the Hemisphere multiplier
    if (array_key_exists("GPSLongitude",$exif) && array_key_exists("GPSLatitude",$exif)) {
        $LatM = 1;
        $LongM = 1;
        if ($exif["GPSLatitudeRef"] == 'S') {
            $LatM = -1;
        }
        if ($exif["GPSLongitudeRef"] == 'W') {
            $LongM = -1;
        }

        //get the GPS data
        $gps['LatDegree'] = $exif["GPSLatitude"][0];
        $gps['LatMinute'] = $exif["GPSLatitude"][1];
        $gps['LatgSeconds'] = $exif["GPSLatitude"][2];
        $gps['LongDegree'] = $exif["GPSLongitude"][0];
        $gps['LongMinute'] = $exif["GPSLongitude"][1];
        $gps['LongSeconds'] = $exif["GPSLongitude"][2];

        //convert strings to numbers
        foreach ($gps as $key => $value)     {
            $pos = strpos($value, '/');
            if ($pos !== false)     {
                $temp = explode('/', $value);
                $gps[$key] = $temp[0] / $temp[1];
            }
        }

        //calculate the decimal degree
        $result['latitude'] = $LatM * ($gps['LatDegree'] + ($gps['LatMinute'] / 60) + ($gps['LatgSeconds'] / 3600));
        $result['longitude'] = $LongM * ($gps['LongDegree'] + ($gps['LongMinute'] / 60) + ($gps['LongSeconds'] / 3600));
    } else {
        $result = null;
    }

    return $result;
}
?>

