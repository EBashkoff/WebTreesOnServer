<?php
//  This runs the server side code to:
//  STEP 1: Set up folders for images base folder, images, full, large, medium, small, and thumb folders
//  STEP 2: Upload files from client
//  STEP 3: Create lower resolution image files from full resolution images
//  STEP 4: Alter selector menu structure for new album and create resources and mediaGroupData folders containing xml file group.xml containing album name 
//  GET params are userid=#, step=#, path=parent folder path, newfolder=new folder name, $album=album name, $albumparent=albumparent name $subsetp=step subsection type
//  
define('WT_SCRIPT_NAME', 'myphotouploadserver.php');
define('PAGE_REFERRER', 'myphotoupload.php');
define('OUTPUT_LOG_FILE','data/uploadops.log');
define('ALBUM_MENU_PHP_FILE','myleftmargintable.php');

if (!(array_key_exists('HTTP_REFERER', $_SERVER) && (strpos($_SERVER['HTTP_REFERER'], PAGE_REFERRER) >= 0))) {
    echo "*** ERROR: You should not be here ***";
    exit; 
}
require 'mysession.php';

$step = $_GET ? WT_Filter::get('step') : WT_Filter::post('step');
// Since ampersand is a legitimate filename character, this is removed from the regex for the following line
$substep = $_GET ? WT_Filter::get('substep', preg_replace(array('/&/', '/%/'), '', WT_REGEX_NOSCRIPT))
                              : WT_Filter::post('substep', preg_replace(array('/&/', '/%/'), '', WT_REGEX_NOSCRIPT));
$folderpath = $_GET ? WT_Filter::get('path') : WT_Filter::post('path');
$newfolder = $_GET ? WT_Filter::get('folder') : WT_Filter::post('folder');

if ($uid != 0) {  //  Here is where the work of this program begins
    $numsuccesses = 0;
    $numfailures = 0;
    $filelog = fopen(OUTPUT_LOG_FILE,'a');
    date_default_timezone_set('America/New_York');

    switch ($step) {
        case 1: {
            if ($newfolder !== '') {
                if (is_dir($folderpath)) {
                    $numsuccesses = ((
                        @mkdir($folderpath . '/' . $newfolder) &&
                        @mkdir($folderpath . '/' . $newfolder . '/images') &&
                        @mkdir($folderpath . '/' . $newfolder . '/images/full') &&
                        @mkdir($folderpath . '/' . $newfolder . '/images/large') &&
                        @mkdir($folderpath . '/' . $newfolder . '/images/medium') &&
                        @mkdir($folderpath . '/' . $newfolder . '/images/small') &&
                        @mkdir($folderpath . '/' . $newfolder . '/images/thumb')                            
                        ) ? 1 : 0);
                    fwrite($filelog, date(DATE_RFC1036) . '--> Image file structure set up ' . (($numsuccesses == 1) ? '' : 'un') . 'successfully for ' . $newfolder . '.' . "'\r\n");
                    echo 'Image file structure set up ' . (($numsuccesses == 1) ? '' : 'un') . 'successfully for ' . $newfolder . '.' . "\n";
                    echo (($numsuccesses == 1) ? 'Proceed to STEP 2...' : 'Retry STEP 1...');                    
                } else {
                    fwrite($filelog, date(DATE_RFC1036) . '--> Parent folder path not found.  No action taken.' . "'\r\n");
                    echo 'Parent folder path not found.  No action taken.';
                }
            } else {
                fwrite($filelog, date(DATE_RFC1036) . '--> No new folder specified to server.  No action taken.' . "'\r\n");
                echo 'No new folder specified to server.  No action taken.';
            }
            break;
        }
        case 2: {
            $success = !is_null($_FILES);
            $overwritten = false;
            $uploadedfile = "No File Received";
            $ftype = '';
            if ($success) {
                while (count($_FILES) > 0) {
                    $uploadfilepost = array_pop($_FILES);
                    $uploadedfile = $uploadfilepost['name'];
                    $ftype = $uploadfilepost['type'];
                    $ourfiledest = $folderpath . (($newfolder === '') ? '' : '/') . $newfolder . '/images/full/' . $uploadedfile;
                    $overwritten = file_exists($ourfiledest);
                    $success = @move_uploaded_file($uploadfilepost['tmp_name'], $ourfiledest);
                    echo $uploadedfile . '|' . (($success) ? '1' : '0') . '|' . (($overwritten) ? '1' : '0') . '|' . $ftype;
                    fwrite($filelog, date(DATE_RFC1036) . '--> Uploaded: ' . $uploadedfile . ' to ' . $ourfiledest . (($overwritten) ? ' - overwritten' : '') . "'\r\n");
                }
            } else {
                fwrite($filelog, date(DATE_RFC1036) . '--> Did not upload: ' . $uploadedfile . "'\r\n");                
            }
            break;
        }
        case 3: {
            if ($substep === 'fetchsources') {
                $fullresolist = @scandir($folderpath);
                if ($fullresolist) {
                    $files_to_remove = array(".", "..");
                    foreach ($fullresolist as $key => $onefile) {  // Cycle through all files
                        if (!is_dir($onefile)) {  // Add non image files to remove list
                            if (!in_array(strtolower(pathinfo($onefile, PATHINFO_EXTENSION)), array('jpg', 'bmp', 'tiff', 'gif'))) {  // File is not of an acceptable type for the list
                                array_push($files_to_remove, $onefile);
                            }
                        }
                    }
                    $fullresolist = array_diff($fullresolist, $files_to_remove);  //  Remove non-image files and directories from the list
                    $fullresolist = array_values($fullresolist);
                    echo json_encode($fullresolist);
                    fwrite($filelog, date(DATE_RFC1036) . '--> Retrieved ' . count($fullresolist) . ' full resolution files from folder: ' . $folderpath . "'\r\n");              
                } else {
                    echo 'Path does not exist';
                    fwrite($filelog, date(DATE_RFC1036) . '--> Could not find full resolution file folder for generating reduced resolution files' . "'\r\n"); 
                }
            } else if (substr($substep,0,5) === 'File-') {  // We are converting this full resolution file to lower resolutions
                $onefile = urldecode(substr($substep,5));
                $resos = array("large", "medium", "small", "thumb");
                $overwrite = array();
                $reportstring = $onefile;
                $srcfile = $folderpath . '/' . $onefile;  // This contains name of full resolution file         
                foreach ($resos as $key=>$onereso) {
                    $destfile = str_replace('/full', '/' . $onereso, $folderpath) . '/' . $onefile;
                    $overwrite[$key] = file_exists($destfile);
                    $result = resize_image($srcfile, $destfile, $onereso);  // False result on failure
                    $overwrite[$key] = $overwrite[$key] && $result;         // If result failed then force overwrite to false
                    $reportstring .=  (($result) ? '|1' : '|0');
                }
                for ($k = 0; $k < count($resos); $k++) $reportstring .= (($overwrite[$k]) ? '|1' : '|0');
                echo $reportstring;  // report is filename|success large|med|small|thumb 0 or 1|overwrite large|med|small|thumb 0 or 1
                fwrite($filelog, date(DATE_RFC1036) . '--> Generated reduced resolution files for: ' . $reportstring . "'\r\n"); 
            }
            break;            
        }
        case 4: {
            if ($substep === 'fetchalbummenu') {  //  Get album menu from PHP file myleftmargintable.php and send to client
                $menufile = file_get_contents(ALBUM_MENU_PHP_FILE); 
                if ($menufile) {
                    echo $menufile;
                    fwrite($filelog, date(DATE_RFC1036) . '--> Retrieved PHP menu file from server' . "'\r\n");              
                } else {
                    echo 'Failed to read PHP menu file';
                    fwrite($filelog, date(DATE_RFC1036) . '--> Failed to read PHP menu file from server' . "'\r\n"); 
                }
            } else if ($substep === 'uploadxml') {  // Get the menu and mediaGroupData xml files to write to the server
                if (!is_null($_FILES)) {
                    copy(ALBUM_MENU_PHP_FILE, str_replace('.php', '_1.php', ALBUM_MENU_PHP_FILE)); 
                    if (($_FILES['xmlmenu']) && (@move_uploaded_file($_FILES['xmlmenu']['tmp_name'], ALBUM_MENU_PHP_FILE))) {
                        echo '1|';
                        fwrite($filelog, date(DATE_RFC1036) . '--> Wrote PHP menu file to server' . "'\r\n");                          
                    } else {
                        copy(str_replace('.php', '_1.php', ALBUM_MENU_PHP_FILE), ALBUM_MENU_PHP_FILE);                    
                        echo '0|';
                        fwrite($filelog, date(DATE_RFC1036) . '--> Failed to write PHP menu file to server' . "'\r\n");                            
                    }
                    
                    @mkdir($folderpath . '/resources');
                    @mkdir($folderpath . '/resources/mediaGroupData');                    
                    if (($_FILES['xmlmediagroupdata']) && (@move_uploaded_file($_FILES['xmlmediagroupdata']['tmp_name'], $folderpath . '/resources/mediaGroupData/group.xml'))) {
                        echo '1';
                        fwrite($filelog, date(DATE_RFC1036) . '--> Wrote PHP mediagroupdata file to server at: ' . $folderpath . '/resources/mediaGroupData/group.xml' . "'\r\n");                        
                    } else {
                        echo '0';
                        fwrite($filelog, date(DATE_RFC1036) . '--> Failed to write PHP mediagroupdata file to server' . "'\r\n");                            
                    }
                } else {
                    echo '0|0';
                    fwrite($filelog, date(DATE_RFC1036) . '--> No menu data received from client to process - No action taken' . "'\r\n");   
                }
            }            
            break;
        }
        default: {
            fwrite($filelog, date(DATE_RFC1036) . "--> Step number invalid. No processing carried out" . "'\r\n");
            echo 'Step number invalid. No processing carried out.';
            exit;
        }
    }
} else {
    echo 'HTTP/1.0 403 Forbidden - Administrator Not Logged In';
    exit;
}

function resize_image($srcfile, $destfile, $resolution) {  // Resizes $srcfile to specified resolution and outputs to $destfile
    $filelog1 = fopen("temp.log",'a');
    if ($srcfile && file_exists($srcfile)) {
        $resolevels = array("large"=>"1024", "medium"=>"720", "small"=>"640", "thumb"=>"96");
        $qualitylevels = array("large"=>"90", "medium"=>"90", "small"=>"80", "thumb"=>"90");
        $imgtype = strtolower(pathinfo($srcfile, PATHINFO_EXTENSION));
        if (($imgtype === 'jpg') || ($imgtype === 'png')) {  // Only process pngs or jpegs
            $srcimg = (($imgtype === 'jpg') ? @imagecreatefromjpeg($srcfile) : @imagecreatefrompng($srcfile));
            if (!$srcimg) return false;          // On failure of creating image, return false
            $srcsize = @getimagesize($srcfile, $iptcinfo);                  // Returns width, height, and IPTC info
            if (!$srcsize) return false;         // On failure of getting image size, return false
            if (($srcsize[0] < 1) || ($srcsize[1] < 1))return false;    //  For zero denominators, return false
            $landscape = ($srcsize[0] > $srcsize[1]);
            
            //  First check if larger source image dimension is already smaller than destination image dimension
            //    If so, then just copy source file to destination
            if (max([$srcsize[0], $srcsize[1]]) < $resolevels[$resolution]) {  // This means that source file is already small enough
                if (!copy($srcfile, $destfile)) return false;  // Copy full res file to lower res folder and on failure of copy, return false            
            } else {                                           // Source file needs to be made smaller
                $dest_x = (($landscape) ? $resolevels[$resolution] : $srcsize[0] * $resolevels[$resolution] / $srcsize[1]);
                $dest_y = (($landscape) ? $srcsize[1] * $resolevels[$resolution] / $srcsize[0] : $resolevels[$resolution]);
                $dstimg = imagecreatetruecolor($dest_x, $dest_y);
                
                // On failure of resampling image, return false
                if (!@imagecopyresampled($dstimg, $srcimg, 0, 0, 0, 0, $dest_x, $dest_y, $srcsize[0], $srcsize[1])) return false;
                
                //  header('content-type: image/' . $imgtype);  // Output header for image only needed if image is sent to browser
                
                // On failure of creating output file, return false, last param of function is image quality on scale of 1 to 100
                if ($imgtype === 'jpg') {
                    $rslt = @imagejpeg($dstimg, $destfile, $qualitylevels[$resolution]);
                    transferIptcExif2File($srcfile, $destfile);                   
                } else
                    $rslt = @imagepng($dstimg, $destfile, $qualitylevels[$resolution]);
                if (!$rslt) return false;
                if (filesize($destfile) > filesize($srcfile)) {    // Test if resampled image is larger than original; if so, just copy source file over destination file
                    if (!copy($srcfile, $destfile)) return false;  // On failure of copy, return false 
                }
                @imagedestroy($dstimg);  // Release memory
            }
            @imagedestroy($srcimg);  // Release memory
            return true;
        } else return false;
    } else return false;    
}

function transferIptcExif2File($srcfile, $destfile) {
    // Function transfers EXIF (APP1) and IPTC (APP13) from $srcfile and adds it to $destfile
    // Existing segments exclusive of APP1 and APP13 in $destfile are preserved and existing 
    //   APP1 and APP13 segments in $destfile are overwritten
    // JPEG file has format 0xFFD8 + [APP0] + [APP1] + ... [APP15] + <image data> where [APPi] are optional
    // Segment APPi (where i=0x0 to 0xF) has format 0xFFEi + 0xMM + 0xLL + <data> (where 0xMM is 
    //   most significant 8 bits of (strlen(<data>) + 2) and 0xLL is the least significant 8 bits 
    //   of (strlen(<data>) + 2)  

    if (file_exists($srcfile) && file_exists($destfile)) {
        $srcsize = @getimagesize($srcfile, $imageinfo);
        
        // Prepare EXIF data bytes from source file
        $exifdata = (is_array($imageinfo) && key_exists("APP1", $imageinfo)) ? $imageinfo['APP1'] : null;
        if ($exifdata) {
            $exiflength = strlen($exifdata) + 2;  // $exifdata does not have the length prefix - add 2 for that
            if ($exiflength > 0xFFFF) return false;
            // Construct EXIF segment
            $exifdata = chr(0xFF) . chr(0xE1) . chr(($exiflength >> 8) & 0xFF) . chr($exiflength & 0xFF) . $exifdata;
        }
        // Prepare IPTC data bytes from source file
        $iptcdata = (is_array($imageinfo) && key_exists("APP13", $imageinfo)) ? $imageinfo['APP13'] : null;
        if ($iptcdata) {
            $iptclength = strlen($iptcdata) + 2;  // $iptcdata does not have the length prefix - add 2 for that        
            if ($iptclength > 0xFFFF) return false;
            // Construct IPTC segment
            $iptcdata = chr(0xFF) . chr(0xED) . chr(($iptclength >> 8) & 0xFF) . chr($iptclength & 0xFF) . $iptcdata;
        }                  

        $destfilecontent = @file_get_contents($destfile);
        if (!$destfilecontent) return false;
        if (strlen($destfilecontent) > 0) {
            $destfilecontent = substr($destfilecontent, 2); // First 2 bytes in JPEG file are always 0xFFD8
            $portiontoadd = chr(0xFF) . chr(0xD8);          // Variable accumulates new & original IPTC application segments

            $exifadded = !$exifdata;  // Flags to indicate we added EXIF and IPTC data can be set if there is no data to add
            $iptcadded = !$iptcdata;

            // Scan through application segments already existing in destination file
            while ((substr($destfilecontent, 0, 2) & 0xFFF0) === 0xFFE0) {
                $segmentlen = (substr($destfilecontent, 2, 2) & 0xFFFF);
                $iptcsegmentnumber = (substr($destfilecontent, 1, 1) & 0x0F);   // Last 4 bits of second byte is IPTC segment #
                if ($segmentlen <= 2) return false;
                $thisexistingsegment = substr($destfilecontent, 0, $segmentlen + 2);    // This is the entire data segment

                if ((1 <= $iptcsegmentnumber) && (!$exifadded)) {               // Add the new EXIF segment here
                    $portiontoadd .= $exifdata;
                    $exifadded = true;                                          // This will prevent EXIF from being added again
                    if (1 === $iptcsegmentnumber) $thisexistingsegment = '';    // Don't add original segment if adding a new one
                }

                if ((13 <= $iptcsegmentnumber) && (!$iptcadded)) {              // Add the new IPTC segment here
                    $portiontoadd .= $iptcdata;
                    $iptcadded = true;                                          // This will prevent IPTC from being added again
                    if (13 === $iptcsegmentnumber) $thisexistingsegment = '';   // Don't add original segment if adding a new one                       
                }

                $portiontoadd .= $thisexistingsegment;                          // Add the preexisting segment here
                $destfilecontent = substr($destfilecontent, $segmentlen + 2);
            }
            if (!$exifadded) $portiontoadd .= $exifdata;  //  Add EXIF data if not added already
            if (!$iptcadded) $portiontoadd .= $iptcdata;  //  Add IPTC data if not added already
            $outputfile = fopen($destfile, 'w');
            if ($outputfile) return fwrite($outputfile, $portiontoadd . $destfilecontent); else return false;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function test_if_img_file($flnm) {  //  Returns true if $flnm has an image extension
    return in_array(strtolower(pathinfo($flnm, PATHINFO_EXTENSION)), array('jpg', 'bmp', 'tiff', 'gif'));
}

function test_if_file_matches($flnm) {  //  Returns true if cleaned $flnm equals cleaned reference image from "full" subfolder
    GLOBAL $cleanedReferenceImg;
    return ($cleanedReferenceImg === preg_replace('/[^A-Za-z0-9]/', '', $flnm));
}
?>
