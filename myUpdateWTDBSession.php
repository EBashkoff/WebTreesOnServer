<?php
define('WT_SCRIPT_NAME', 'myUpdateWTDBSession.php');
define('MY_DBSERVER', ($_SERVER['SERVER_NAME'] == 'localhost') ? WT_WEBTREES_SITENAME : 'localhost');
define('FILE_PATH_PREFIX', "./");

include './includes/session.php';

if (!isset($WT_SESSION) || (getUserId() == 0)) {
    echo 'HTTP/1.0 403 Forbidden';
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
////  Make connection to database
$con = mysql_connect(MY_DBSERVER . ':' . $dbconfig['dbport'], $dbconfig['dbuser'], $dbconfig['dbpass']);
if (!$con) {
    die('Could not connect to update browser parameters: ' . mysql_error());
}
mysql_select_db($dbconfig['dbname'], $con);
$qq = "UPDATE wt_session SET session_height=". safe_GET('height') . ", session_type='" . safe_GET('type') . "' WHERE session_id='" . $_COOKIE[WT_SESSION_NAME]. "';";
mysql_query($qq, $con);
mysql_close($con);

?>
<head></head>
<body>test</body>




