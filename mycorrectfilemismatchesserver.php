<?php
//  This runs the server side code to correct the image name mismatches on the server.  The array of folders is passed from mycorrectfilemismatches.php as POST data
//  GET params are userid=# 

define('WT_SCRIPT_NAME', 'mycorrectfilemismatchesserver.php');
define('PAGE_REFERRER', 'mycorrectfilemismatches.php');
define('OUTPUT_LOG_FILE','data/filemismatch.log'); 

if (!(array_key_exists('HTTP_REFERER', $_SERVER) && (strpos($_SERVER['HTTP_REFERER'], PAGE_REFERRER) >= 0))) {
    echo "*** ERROR: You should not be here ***";
    exit; 
}
require 'mysession.php';

$data = json_decode($_POST['json_string']);  //  This is the data sent to the server from mycorrectfilemismatches.php containing the image folders to look at
   
if ($uid != 0) {  //  Here is where the work of this program begins
    $image_matrix = Array();
    $numfiles = Array();
    $intersection = Array();
    $imgsThatAreDiffFull = Array();
    $imgsThatAreDiffSmaller = Array();
    $imgsCandidate = Array();
    $cleanedReferenceImg = '';
    $numsuccesses = 0;
    $numfailures = 0;
    $filelog = fopen(OUTPUT_LOG_FILE,'a');

    date_default_timezone_set('America/New_York');

    foreach ($data as $onealbumfolder) {  //  Cycle through each photo album folder
        fwrite($filelog, date(DATE_RFC1036) . ' ------> Analyzing: ' . $onealbumfolder . ' <------'. "\r\n");
        fwrite($filelog, date(DATE_RFC1036) . ' ----> Number of files in FLMST subfolders: ');
        foreach (array('full', 'large', 'medium', 'small', 'thumb') as $subfolder) {  //  Get the individual images in each size folder into $imagematrix
            $image_matrix[$subfolder] = array_filter(scandir($onealbumfolder . '/'. $subfolder), 'test_if_img_file');  //  Just get images from the folder, not other files
            $numfiles[$subfolder] = count($image_matrix[$subfolder]);
            fwrite($filelog, $numfiles[$subfolder] . '  ');           
        }
        fwrite($filelog, "\r\n");
        foreach (array('large', 'medium', 'small', 'thumb') as $subfolder) {  //  Get the individual images in each size folder
            $intersection = array_intersect($image_matrix['full'], $image_matrix[$subfolder]);
            $imgsThatAreDiffFull = array_diff($image_matrix['full'], $intersection);            //  These are the images that are in 'full' and not in this subfolder
            $imgsThatAreDiffSmaller = array_diff($image_matrix[$subfolder], $intersection);     //  These are the images that are in this subfolder but not in 'full'
            fwrite($filelog, date(DATE_RFC1036) . ' ----> Number of mismatches to F in LMST subfolders (Full[' . $subfolder . ']): ');
            fwrite($filelog, count($imgsThatAreDiffFull) . '[' . count($imgsThatAreDiffSmaller) . ']' . "\r\n");                
            foreach ($imgsThatAreDiffFull as $key=>$onereferenceimg) {  //  Now cycle through the reference image file names in 'full' and check for possible matches in this subfolder
                fwrite($filelog, date(DATE_RFC1036) . sprintf(" -->      Mismatch in 'full  ' is: %-30s \r\n",$onereferenceimg));
                $cleanedReferenceImg = preg_replace('/[^A-Za-z0-9]/', '', $onereferenceimg);
                fwrite($filelog, date(DATE_RFC1036) . ' -->  full img cleaned: ' . $cleanedReferenceImg . "\r\n");
                foreach ($imgsThatAreDiffSmaller as $value) {
                    fwrite($filelog, date(DATE_RFC1036) . ' -->  ' . $subfolder . ' img cleaned: ' . preg_replace('/[^A-Za-z0-9]/', '', $value) . "\r\n");
                }
                $imgsCandidate = array_filter($imgsThatAreDiffSmaller, 'test_if_file_matches');
                foreach ($imgsCandidate as $oneCandidate) {
                    fwrite($filelog, date(DATE_RFC1036) . sprintf(" --> Closest match in '%-6s' is: %-30s \r\n", $subfolder, $oneCandidate));
                }
                if (count($imgsCandidate) == 1) {  // This means there is only one close match between the reference full image and the subfolder images
                    $finalfile = $onereferenceimg; // The reference file name in 'full' becomes the new filename in the subfolder
                    $origfile = $onealbumfolder . '/' . $subfolder . '/' . array_pop($imgsCandidate);  //  Pop off the only file candidate in the subfolder
                    if (rename($origfile, $onealbumfolder . '/' . $subfolder . '/' . $finalfile)) {
                        fwrite($filelog, date(DATE_RFC1036) . " ************ Renamed '" . $origfile . "' to '" . $finalfile . "'\r\n");
                        $numsuccesses++;
                    } else {
                        fwrite($filelog, date(DATE_RFC1036) . " ************ COULD NOT RENAME '" . $origfile . "'\r\n");
                        $numfailures++;
                    }
                }
            }
        }
        fwrite($filelog, "\r\n");
    }
    echo 'Completed analyzing image files' . "\n\n" . 'Number of files renamed successfully: ' . $numsuccesses . "\n";
    echo 'Number of files that could not be renamed: ' . $numfailures . "\n\n" . 'See log file '. OUTPUT_LOG_FILE . ' on server for details';
} else {
    echo 'HTTP/1.0 403 Forbidden - Administrator Not Logged In';
    exit;
}

function test_if_img_file($flnm) {  //  Returns true if $flnm has an image extension
    return in_array(strtolower(pathinfo($flnm, PATHINFO_EXTENSION)), array('jpg', 'bmp', 'tiff', 'gif'));
}

function test_if_file_matches($flnm) {  //  Returns true if cleaned $flnm equals cleaned reference image from "full" subfolder
    GLOBAL $cleanedReferenceImg;
    return ($cleanedReferenceImg === preg_replace('/[^A-Za-z0-9]/', '', $flnm));
}
?>
