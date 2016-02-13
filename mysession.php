<?php
define('WT_REGEX_NOSCRIPT', '[^<>"&%{};]*');
define('MY_DBSERVER', 'localhost');
define('FILE_PATH_PREFIX', "./");
define('HTACCESS_PREFIX_CHARS', '<FilesMatch "\.(gif|png|jpe?g)$">' . "\r\nOrder Deny,Allow\r\nDeny from all\r\nAllow from ");
define('HTACCESS_POSTFIX_CHARS', "\r\n</FilesMatch>");

require_once 'library/WT/Filter.php';  # For safe GET and POST used throughout project

$BROWSERTYPE = get_BROWSERTYPE();
$sidfromcookie = $_COOKIE['WT_SESSION'];
$uidfromget = WT_Filter::get('userid') ?: WT_Filter::post('userid');
if (!$sidfromcookie) {
    echo "*** ERROR: Your browser's cookies must be enabled to use this site ***";
    exit;
}

// Get connection to webtrees database
if (file_exists(FILE_PATH_PREFIX . 'data/config.ini.php')) {
    $dbconfig = parse_ini_file(FILE_PATH_PREFIX . 'data/config.ini.php');   // Database connection params

    if (!is_array($dbconfig)) { // Invalid/unreadable config file?
        header('Location: ' . FILE_PATH_PREFIX . 'site-unavailable.php');
        exit;
    }
} else {                       // Database file does not exist
    header('Location: ' . FILE_PATH_PREFIX . 'site-unavailable.php');
    exit;
}
//  Make connection to database
$con = mysql_connect(MY_DBSERVER . ':' . $dbconfig['dbport'], $dbconfig['dbuser'], $dbconfig['dbpass']);
if (!$con) {
    die('Could not connect: ' . mysql_error());
}
mysql_select_db($dbconfig['dbname'], $con);
$qq = "SELECT session_id, user_id, session_type, session_height FROM wt_session WHERE user_id='"
        . $uidfromget . "' AND session_id='" . $sidfromcookie . "';";
$res = mysql_query($qq, $con);
if ($res) { //  There exists a user with the user id given in the GET parameter and having the matching session cookie
        $row = mysql_fetch_array($res);
        $uid = $row['user_id'];
        $devicetype = $row['session_type'];
        $deviceheight = $row['session_height'];
        
        $qq = "SELECT setting_value FROM wt_user_setting WHERE user_id =" . strval($uid) . " AND setting_name='canadmin';";  // Get administrator status from DB
        $res1 = mysql_query($qq, $con);
        if ($res1) {
            $row1 = mysql_fetch_array($res1);
            $canadmin = ($row1['setting_value'] === '1');
        } else {
            $canadmin = false;
        }

        $qq = "SELECT real_name FROM wt_user WHERE user_id =" . strval($uid) . ";";  // Get real user name from DB
        $res1 = mysql_query($qq, $con);
        if ($res1) {
            $row1 = mysql_fetch_array($res1);
            $realusername = $row1['real_name'];
        } else {
            echo "*** ERROR: No user with this user ID stored in webtrees ***";
            mysql_close($con);
            exit;
        }

// Use connection to webtrees database so we can update HTACCESS file in gallery subdirectory giving access to permitted IP's
//      HTACESSS file need to be in gallery subdirectory and needs to look like:
//              <FilesMatch "\.(gif|png|jpe?g)$">
//                  Order Allow,Deny
//                  Allow from 70.192.211.216 74.110.119.142
//              </FilesMatch>
//
    $qq = "SELECT ip_address FROM wt_session WHERE user_id > 0";    // Only collect IP's from DB where user is logged on
    $res = mysql_query($qq, $con);
    if ($res) {
        $ipaddresslist = '';
        while ($row = mysql_fetch_array($res)) {    // Traverse rows to collect IP's into a string
            $ipaddresslist .= $row['ip_address'] . ' ';
        }
        mysql_close($con);
        $fileht = fopen(FILE_PATH_PREFIX . 'gallery/.htaccess', 'w');
        if (!fwrite($fileht, HTACCESS_PREFIX_CHARS . ((strlen($ipaddresslist) == 0 ) ? 'none' : $ipaddresslist) . HTACCESS_POSTFIX_CHARS)) {    // Write HTACCESS file content
            echo "*** ERROR: Cannot write to HTACCESS file ***";
            fclose($fileht);
            exit;
        }
    } else {
        echo "*** ERROR: No user IP addresses stored in webtrees database ***";
        mysql_close($con);
        exit;
    }
} else {
    $uid=0;
    echo "*** ERROR: No session ID stored in webtrees ***";
    mysql_close($con);
    exit;
}

if ($uid < 1) { // If no user id is returned from the session table, then cannot access pages
    echo 'HTTP/1.0 403 Forbidden';
    exit;
}

function get_BROWSERTYPE() {
    // Determine browser type
    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT']='';
    }
    // TODO: Browser sniffing is bad.  We should use capability detection.
    if (stristr($_SERVER['HTTP_USER_AGENT'], 'Opera')) {
            return 'opera';
    } elseif (stristr($_SERVER['HTTP_USER_AGENT'], 'KHTML')) {
            return 'chrome';
    } elseif (stristr($_SERVER['HTTP_USER_AGENT'], 'Gecko')) {
            return 'mozilla';
    } elseif (stristr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            return 'msie';
    } else {
            return 'other';
    }
}
?>
