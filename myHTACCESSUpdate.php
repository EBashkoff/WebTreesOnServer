<?php
define('MY_DBSERVER', ($_SERVER['SERVER_NAME'] == 'localhost') ? WT_WEBTREES_SITENAME : 'localhost');
define('FILE_PATH_PREFIX', './');
define('HTACCESS_PREFIX_CHARS', '<FilesMatch "\.(gif|png|jpe?g)$">' . "\r\nOrder Deny,Allow\r\nDeny from all\r\nAllow from ");
define('HTACCESS_POSTFIX_CHARS', "\r\n</FilesMatch>");

// Get connection to webtrees database so we can update HTACCESS file in gallery subdirectory giving access to permitted IP's
//      HTACESSS file need to be in gallery subdirectory and need to look like:
//              <FilesMatch "\.(gif|png|jpe?g)$">
//                  Order Allow,Deny
//                  Allow from 70.192.211.216 74.110.119.142
//              </FilesMatch>
//
if (file_exists(FILE_PATH_PREFIX . 'data/config.ini.php')) {
    $dbconfig = parse_ini_file(FILE_PATH_PREFIX . 'data/config.ini.php');   // Database connection params

    if (!is_array($dbconfig)) { // Invalid/unreadable config file?
        header('Location: ' . FILE_PATH_PREFIX . 'site-unavailable.php');
        exit;
    }
} else {                        // Database file does not exist
    header('Location: ' . FILE_PATH_PREFIX . 'site-unavailable.php');
    exit;
}
//  Make connection to database
$con = mysql_connect(MY_DBSERVER . ':' . $dbconfig['dbport'], $dbconfig['dbuser'], $dbconfig['dbpass']);
if (!$con) {
    die('Could not connect: ' . mysql_error());
}
mysql_select_db($dbconfig['dbname'], $con);
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
?>
