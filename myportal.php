<?php
define('WT_SCRIPT_NAME', 'myportal.php');
require 'mysession.php';

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
    die('Could not connect: ' . mysql_error());
}
mysql_select_db($dbconfig['dbname'], $con);
$qq = "UPDATE wt_session SET session_height=". 123 . ", session_type='a' WHERE session_id='" . $_COOKIE['WT_SESSION']. "';";
mysql_query($qq, $con);
mysql_close($con);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <head>
        <meta content="en-us" http-equiv="Content-Language" />
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
        <title>Bashkoff Family Website</title>
        <link rel="icon" href="http://<?php echo $_SERVER['SERVER_NAME']; ?>/favicon.png" type="image/png"></link>
        <script type="text/javascript" src="js/myMoveNode.js"></script>
        <script type="text/javascript" src="js/myGetWindowClientArea.js"></script>
        <script type="text/javascript">
            function mousealert() {
                console.log(this.document.activeElement.offsetTop);
                console.log(getAnimatedMenuHeight());
                var el = document.activeElement;
                el.scrollIntoView(true);
            }
        </script>
        <?php include 'mygalleryselectorheader.php'; ?>
        <style type="text/css">
            .auto-style4 {
                font-family: Arial, Helvetica, sans-serif;
                font-size: xx-large;
            }
            .auto-style5 {
                font-family: Arial, Helvetica, sans-serif;
                font-size: medium;
                color: #000000;
                text-align: right;
                text-decoration: none;
            }
            a.auto-style5:hover {color: red;}
            .auto-style5-white {
                font-family: Arial, Helvetica, sans-serif;
                font-size: medium;
                color: #FFFFFF;
                text-align: right;
                text-decoration: none;
            }.auto-style6 {
                text-align: center;
            }
            .auto-style7 {
                font-family: Arial, Helvetica, sans-serif;
            }
            .auto-style8 {
                font-size: small;
                color:#A04D3E;
                text-decoration: none;
            }
            .auto-style9 {
                color: #A04D3E;
            }
            .auto-style10 {
                font-family: Arial, Helvetica, sans-serif;
                color: #A04D3E;
                text-decoration: none;
            }
            .auto-style11 {
                font-family: Arial, Helvetica, sans-serif;
                color: #A04D3E;
                text-decoration: none;
                text-align: center;
            }
            .auto-style12 {
                font-family: Arial, Helvetica, sans-serif;
                color: #000000;
                text-decoration: none;
                text-align: left;
            }
        </style>
    </head>

    <body style="background-color: #3C391B">
        <?php include_once("myanalyticstracking.php") ?>
        <div id="outerframe" style="border: 3px solid #86815F; background-color:white; position: relative; margin:auto; padding:0px; height: 844px; width: 92%; z-index: 1; top: 0px; left: 0px; color: #FFFFFF; visibility: visible;">
            <!--        **********MY WEB SITE HEADER STARTS HERE -->
            <div id="fullhead" style="display: none; margin:auto; width: 99%; height: 114px; text-align: bottom; color: #36341A; background-color: #86815F; visibility: visible; position: relative; top: 6px; left: 0px">
                <div style="float: left;">
                    <img alt="" src="./myindex_files/img3.jpg"/>
                    <span class="auto-style4"><strong>Bashkoff Family Web Site</strong></span>
                </div>
                <div style="float: right;">
                    <span class="auto-style5" style="height: 100%; float: right; padding: 85px 10px 0 0;">
                        <?php
                        echo 'Logged in as: <a href="edituser.php" class="auto-style5">' . $realusername . '</a>',
                        (($canadmin) ? (' | <a href="myphotoupload.php?userid=' . $uid . '" class="auto-style5">Upload</a>') : ''),
                        ' | <a href="index.php?logout=1" class="auto-style5">Logout</a>';
                        ?>
                    </span>
                </div>
            </div>
            <div id="shorthead" style="display: none; margin:auto; width: 99%; height: 90px; font-size: 28pt; padding-left: 0px; padding-right: 0px; padding-top: 4px; color: #36341A; background-color: #86815F; visibility: visible; position: relative; top: 4px; left: 0px">
                <div style="width: 100%; height: 100%; padding-bottom: 4px; padding-right: 4px; padding-left: 4px">
                    <span style="float: left; font-weight: 900;"><strong>Bashkoff Family Web Site</strong></span>
                    <span style="float: left; margin-right: 12px; font-weight: initial;">
                        <?php
                        echo 'Logged in as: <a href="edituser.php" style="color: #000000; text-decoration: none">' . $realusername . '</a>',
                        ' | <a href="index.php?logout=1" style="color: #000000; text-decoration: none" >Logout</a>';
                        ?> 
                    </span>
                </div>
            </div>            
            <div id="limebar" style="margin:auto; height: 12px; width: 99%; text-align: center; padding-left: 0px; padding-right: 0px; padding-top: 0px; color: #36341A; background-color: #9D9248; visibility: visible; position: relative; top: 0px; left: 0px; z-index: 2;">
            </div>
            <!--        **********MY WEB SITE HEADER ENDS HERE -->
            <div id="fullcontent" style="display: none; margin:auto; width: 99%; height: 680px; text-align: center; color: #36341A; background-color: #FFFFFF; visibility: visible; position: relative; top: 0px; left: 0px; z-index: 2;">
                <div>
                    <table style="width: 100%; height: 66%; z-index: 2; left: 0px; top: 0px; position: absolute;">
                        <tr valign="top">
                            <td class="auto-style10" style="text-align: left; width: 250px; height: 8px;">Home</td>
                            <td class="auto-style10" style="text-align: left; width: 250px; height: 8px;">
                                <a href="index.php" class="auto-style10">Family Tree</a></td>
                            <td class="auto-style10" style="height: 8px;" align="center"  valign="middle">
                                <label id="Label2"><strong>Welcome to our website!</strong></label>
                            </td>
                        </tr>
                        <tr valign="top">
                            <td style="width: 248px">
                                <table cellpadding="0" cellspacing="0" class="auto-style7" style="width: 100%; height: 660px;  position: none; background-color: #F0F0F0">
                                    <tr>
                                        <td class="auto-style9" style="text-align: left; border-left: 2px solid #C0C0C0; border-top: 2px solid #C0C0C0; height: 41px; border-right-style: solid; border-right-width: 2px; border-bottom-style: solid; border-bottom-width: 2px;">
                                            <label id="photolabel"><strong>&nbsp;Photos</strong></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td valign="top">
                                            <div id="themenu" style="padding-left: 5px; padding-right: 5px; position: relative">
                                                <?php include 'myleftmargintable.php'; ?>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td style="width: 248px">
                                <table id="midmargintable" cellpadding="0" cellspacing="0" class="auto-style7" style="width: 100%; height: 100%;  position: none" >
                                    <tr>
                                        <td class="auto-style9" style="text-align: left; border-left: 2px solid #C0C0C0; border-top: 2px solid #C0C0C0; height: 41px; border-right-style: solid; border-right-width: 2px; border-bottom-style: solid; border-bottom-width: 2px; background-color: #F0F0F0;">
                                            <label id="communicatelabel"><strong>&nbsp;Communicate</strong></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-style: solid; border-color: #F0F0F0; background-color: #F0F0F0" valign="top">
                                            <div style=" width: 100%; height: 600px; background-color: #F0F0F0">
                                                <p><a href="myportal_1.php?userid=<?php echo $uid; ?>" class="auto-style8">What's up with us</a></p>
                                                <p><a href="https://mail.google.com/mail/?view=cm&fs=1&tf=1" class="auto-style8">E-mail us</a></p>
                                                <p><a href="http://groups.google.com/group/ebashkoff/" class="auto-style8">Our Blog</a></p></div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td style="text-align: center; background-color: #F0F0F0;" valign="top">
                                <img alt="" height="404" src="./myindex_files/WebCollage.jpg" width="543"/>
                            </td>
                        </tr>
                    </table>
                </div>
                <div style="position: absolute; text-align: left; bottom: 0"><a href="mywolphp.php?userid=<?php echo $uid;?>" class="auto-style5" style="color:#F0F0F0; position: absolute; text-align: left">&bull;</a></div>
            </div>
            <div id="shortcontent" style="display: none; padding: 5px; margin:auto; width: 99%; font-size: 28pt; font-weight: 900; text-align: center; color: #36341A; background-color: #F0F0F0; visibility: visible; position: relative; top: 15px; left: 0px; z-index: 2;">
                <strong>Photos</strong><hr style="height: 2px; border-top: 1px; color: #A04D3E; background-color: #A04D3E"/>
                <div id="putmenuhere" style="position: relative; width: 100%; background-color: #F0F0F0">

                </div><hr style="height: 2px; border-top: 1px; color: #A04D3E; background-color: #A04D3E"/>
                <strong>Communicate</strong><hr style="height: 2px; border-top: 1px; color: #A04D3E; background-color: #A04D3E"/>
                <div id="whatsupdiv" style="position: relative; width: 100%; background-color: #F0F0F0" >
                    <p><a href="myportal_1.php?userid=<?php echo $uid; ?>" style="font-size: 30pt; color: #A04D3E">What's up with us</a></p>
                </div>
            </div>
        </div>
        <div id="footer" style="display: none; position: relative; text-align: center; padding-top: 10px;">
            <span class="auto-style5-white">For technical support or genealogy questions, please contact <a href="mailto:bashkoff@bashkoff-family.com" class="auto-style5-white" >Eric Bashkoff</a></span> 
        </div>
        <script type="text/javascript">
            var winClientArea = getWindowClientArea();
            if (winClientArea['type'] === 'phone') {
                document.getElementById("outerframe").style.position = "absolute";
                document.getElementById("outerframe").style.width = "100%";
                movenode("themenu", "putmenuhere");
                document.getElementById("shorthead").style.display = "block";
                document.getElementById("shortcontent").style.display = "block";
                document.getElementById("smooth-menu").setAttribute("style", "background-color: #F0F0F0");
                document.getElementById("outerframe").style.height = 1200 + "px";
            } else {
                document.getElementById("fullhead").style.display = "block";
                document.getElementById("fullcontent").style.display = "block";
                document.getElementById("footer").style.display = "block";
                if (getWindowClientArea()['type'] === 'tablet') {
                    document.getElementById("outerframe").style.position = "relative";
                    document.getElementById("outerframe").style.width = "98%";
                    document.getElementById("outerframe").style.marginLeft = "auto";
                    document.getElementById("outerframe").style.marginRight = "11px";
                }
            }
//            The following allows passage of browser height and type params to the server in page myUpdateWTDBSession.php, which writes these to session table
            $.get("myUpdateWTDBSession.php",{"height":winClientArea["height"], "type":winClientArea["type"]});    
        </script>
    </body>
</html>