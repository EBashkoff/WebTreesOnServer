<?php
// ********** Send Wake-on-LAN packet to client
// ***        Pre-stored parameters in data/WOL.ini folder

define('WT_SCRIPT_NAME', 'mywolphp.php');

require 'mysession.php';

define('DEFAULT_PORT', 32767);                             //  Default port
define('IP_STR_LOCAL', "192.168.1.255");                   //  Default local IP address for subnets
define('IP_STR_WAN', "woodlake.hopto.org");                //  Default local IP address for internet
// define('MAC_ID__STR', "00:1a:4d:58:1e:04");             //  Default MAC ID for target computer
define('DEFAULT_TELNET_PORT', 23);                         //  Default Telnet port

if ((isset($_POST['Wakeupbutton'])) && ($_POST['Wakeupbutton'] === 'Wake Up')) {
    $macidnm = $_POST['targetmac'];
    $defaultportnm = (isset($_POST['defaultport']));
    $portnm = ($defaultportnm ? '32767' : $_POST['targetport']);
    $ipstrnm = $_POST['targetip'];
    $outputmsg = WakeOnLan($ipstrnm, $macidnm, (int) $portnm);
    if (strlen(trim($outputmsg)) == 0) {
        $outputmsg = 'Wake-on-LAN packet sent to IP address ' . $ipstrnm . ', Port ' . $portnm . ', MAC ID: ' . $macidnm;
    }
} else {
    $postedcomputer = false;
    $wolinifile = parse_ini_file('data/WOL.ini', true);
    foreach ($wolinifile as $computer => $params) {
        if ((isset($_POST[$computer])) && ($computer == $_POST[$computer])) {
            $macidnm = $params['macid'];
            $defaultportnm = ($params['usedefaultport']);
            $portnm = ($defaultportnm ? '32767' : $params['port']);
            $ipstrnm = $params['ipaddress'];
            $outputmsg = 'Loaded Parameters as Requested';
            $postedcomputer = true;
        }
    }
}
if (!isset($_POST['Wakeupbutton']) && !$postedcomputer) {
    $ipstrnm = '';
    $macidnm = '';
    $portnm = '';
    $defaultportnm = null;
    $outputmsg = '';
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <head>
        <?php include 'mygalleryselectorheader.php'; ?>
        <meta content="en-us" http-equiv="Content-Language" />
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
        <title>Bashkoff Family Website</title>
        <link rel="icon" href="http://<?php echo $_SERVER['SERVER_NAME']; ?>/favicon.png" type="image/png"></link>
        <style type="text/css">
            .auto-style4 {
                font-family: Arial, Helvetica, sans-serif;
                font-size: xx-large;
                color: black;
                text-decoration: none;
            }
            a.auto-style4:hover {color: red;}
            .auto-style5 {
                font-family: Arial, Helvetica, sans-serif;
                font-size: medium;
                color: #000000;
                text-align: right;
                text-decoration: none;
            }
            a.auto-style5:hover {color: red;}
            .auto-style7 {
                text-align: right;
            }
            .auto-style8 {
                text-align: left;
            }
            .auto-style9 {
                border: 1px solid #3C391B;
                font-family: Arial, Helvetica, sans-serif;
                font-size: small;
                color: #000000;
                text-align: right;
            }
            #layer2 {
                position: relative;
                top: -83px;
                left: 0px;
                width: 578px;
            }
            .auto-style10 {
                font-family: Arial, Helvetica, sans-serif;
                font-size: x-large;
                position: relative;
                color: #000000;
            }
        </style> 
        <script type="text/javascript">
            function toggle(fixedtextid, toggletextid, fixedtext1, fixedtext2) {
                var ele = document.getElementById(toggletextid);
                var text = document.getElementById(fixedtextid);
                if (ele.style.display == "block") {
                    ele.style.display = "none";
                    text.innerHTML = fixedtext1;
                }
                else {
                    ele.style.display = "block";
                    text.innerHTML = fixedtext2;
                }
            }
        </script>
    </head>

    <body style="background-color: #3C391B">
        <div id="outerframe" style="border: 3px solid #86815F;  background-color:white; position: relative; margin:auto; padding:0px; width: 92%; height: 680px; z-index: 1; top: 0px; left: 0px; color: #FFFFFF; visibility: visible;">

            <!--        **********MY WEB SITE HEADER STARTS HERE -->
            <div id="fullhead" style="margin:auto; width: 99%; height: 114px; text-align: bottom; color: #36341A; background-color: #86815F; visibility: visible; position: relative; top: 6px; left: 0px">
                <div style="float: left;">
                    <img alt="" src="./myindex_files/img3.jpg"/>
                    <?php echo '<span><a class="auto-style4" href="' . FILE_PATH_PREFIX . 'myportal.php?userid=' . $uid . '"><strong>Bashkoff Family Web Site</strong></a></span>'; ?>
                </div>
                <div style="float: right;">
                    <span class="auto-style5" style="height: 100%; float: right; padding: 85px 10px 0 0;">
                        <?php
                        echo 'Logged in as: <a href="edituser.php" class="auto-style5">' . $realusername . '</a>',
                        ' | <a  href="myportal.php?userid=' . $uid . '" class="auto-style5">Home</a>',
                        ' | <a href="index.php?logout=1" class="auto-style5">Logout</a>';
                        ?>
                    </span>
                </div>
            </div>
            <div id="limebar" style="margin:auto; height: 12px; width: 99%; text-align: center; padding-left: 0px; padding-right: 0px; padding-top: 0px; color: #36341A; background-color: #9D9248; visibility: visible; position: relative; top: 0px; left: 0px; z-index: 2;">
            </div>
            <!--        **********MY WEB SITE HEADER ENDS HERE -->

            <div style="height: 138px; width: 100%; margin:auto; text-align: center; position: relative; top: -20px; left: 0px;">
                <br></br><span class="auto-style10">Wake on LAN: Please enter your parameters below</span></div>

            <div style="height: 50px; width: 100%; margin:auto; text-align: center; position: relative; top: -115px; left: 0px;">
                <br></br>
                <span class="auto-style10" style="color: red;"><?php echo $outputmsg ?></span></div>
            <div id="layer2" style="position: relative; z-index: 2; margin: auto auto 0px auto; visibility: visible; height: 239px;">
                <?php echo '<form method="post" action="' . WT_SCRIPT_NAME . '?userid=' . $uid . '">'; ?>
                    <input name="userid" type="hidden" value="<?php echo $uid; ?>"/>
                    <div>
                        <table cellpadding="5" cellspacing="20" class="auto-style9" style="z-index: 1; top: 0px; background-color: #86815F; position: none;">
                            <tr>
                                <td style="width: 584px">Target computer's IP address or hostname:</td>
                                <td style="width: 167px">
                                    <input name="targetip" style="width: 252px" type="text" value ="<?php print($ipstrnm); ?>"/></td>
                            </tr>
                            <tr>
                                <td style="width: 584px">Target computer's MAC ID:</td>
                                <td style="width: 167px">
                                    <input name="targetmac" style="width: 252px" type="text" value ="<?php print($macidnm); ?>"/></td>
                            </tr>
                            <tr>
                                <td style="width: 584px">Port over which to transmit Magic Packet: </td>
                                <td style="width: 167px">
                                    <input name="targetport" style="text-align: left; width: 252px" type="text" value="<?php print($portnm); ?>"/></td>
                            </tr>
                            <tr>
                                <td style="width: 584px" align="right">Use default port:</td>
                                <td style="width: 167px" align ="left">
                                    <input name="defaultport" type="checkbox" value="checked"
                                           <?php print($defaultportnm ? 'checked' : ''); ?>/></td>
                            </tr>
                            <tr>
                                <td class="auto-style8" valign="bottom" style="width: 584px">
                                    <a id="fileopts" href="javascript:toggle('fileopts','toggletext','Saved Settings','Saved-Settings')">Saved Settings</a>
                                </td>
                                <td class="auto-style7" valign="bottom" style="width: 167px">
                                    <input name="Wakeupbutton" type="submit" value="Wake Up"/></td>
                            </tr>
                        </table>
                    </div>
                    <div>
                        <div style="text-align: center">
                            <div class="auto-style5" id="toggletext" style="display: none">
                                <table cellpadding="5" cellspacing="20" class="auto-style9" style="z-index: 1; left: -118px; top: 0px; background-color: #86815F; position: none;">
                                    <tr>
                                        <td style="width: 751px; text-align: center">
                                            <?php
                                            $wolinifile = parse_ini_file('data/WOL.ini', true);
                                            foreach ($wolinifile as $computer => $params) {
                                                echo '<input type="submit" name="' . $computer . '" value="' . $computer . '"/>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php echo '</form>'; ?>
            </div>
            <hr style="z-index: 1; left: 4px; top: 215px; position: absolute; height: 1px; width: 99%">
        </div>
        <div id="footer" align="center">
            <span class="auto-style5" style="color: #000000">For technical support or genealogy questions, please contact <a href="mailto:bashkoff@bashkoff-family.com" class="auto-style5" style="color: #000000">Eric Bashkoff</a></span> 
        </div>
    </body>
</html>
<?php

//  The target computer's NIC settings must be set in the Device Manager under the "properties" settings for
//    the NIC.  Under the Power Management tab, the checkbox stating "Allow this Device to Bring the Computer out of
//    Standby" must be selected, and under the Advanced tab, the Wake-On Lan Capabilities must be set to "Magic Packet."
//
//  The wake-on-LAN (WOL) function works by sending a "Magic Packet" composed of 6 bytes of 0xFF followed by the 6
//    byte MAC ID of the target network interface card repeated 16 times.  This is sent as a Datagram packet using UDP 
//    protocol to the target IP address using any available port.  If sent from a computer within the same subnet, this
//    IP address should be directed at xxx.xxx.xxx.255, where the x's represent the subnet address; i.e., 192.168.1.155.
//    If sent over the internet, the IP address should be set to the WAN hostname or WAN IP address of the home router.
//    
//  For WOL within the subnet, the router port forwarding settings do not need to be modified.  The packet's
//    destination IP address must be xxx.xxx.xxx.255 regardless of the IP address of the target computer.  The 
//    packet's destination IP address cannot be set to the actual IP address of the target IP address in order for 
//    the target computer to be turned on.  This is because the packet with be "broadcast" over the subnet when the
//    255 postfix is on the destination IP address.
//
//  For WOL over the internet, the IP address may be provided as a hostname or an IP address.  The router's settings
//    must be modified to allow port forwarding on the port declared in this program's parameters.  This is done
//    by going to the Advanced --> Port Forwardng Rules and adding a new Protocol with a new application name and this new 
//    server port number, specifying the UDP protocol with "Any" Source port and one Destination port with this port number.
//    Then, go to the Firewall Setting --> Port Forwarding and add a new port forwarding rule using the named application
//    and the IP address to forward to should be set as the local IP address of the target computer. WAN Connection Type
//    should be set to "All Broadband Devices" and "Forward to Port" should be set to "Same as Incoming Port"
//  
//  Note that the WOL over the internet will only work if the turned off computer's IP address is still in the ARP table
//    in the router.  This can be checked with the Advanced --> ARP Table.  If the IP address is absent from the table,
//    it can be restored to the ARP Table (only as long as the router receives power) by allowing Telnet access to the
//    router under Advanced -> Local Administration and checking the Promary Telnet Port checkbox.  THen go to 
//    Start --> Run --> cmd --> Telnet <router LAN IP address>.  Enter the Username and Password for the router, then
//    type "shell" then "arp -s <computer IP address> <computer MAC ID>"  The addition of this IP address to the ARP
//    table can then be checked from the Advanced --> ARP Table function in the router.  Type exit --> exit to leave Telnet.
//    This ARP Table will only keep this IP address for as long as the router remains powered.

function WakeOnLan($ipStr = '', $macStr = '', $port = DEFAULT_PORT) {

    //          Example: WakeOnLan("192.168.0.255","00:1a:4d:58:1e:04",9)
    //          Example: WakeOnLan("192.168.0.255","00:1A:4D:58:1E:04",9)
    //          Example: WakeOnLan("woodlake.hopto.org","00:1a:4d:58:1e:04",9)
    //          Returns a String message about the results of the sending process

    if (($port <= 0) || ($port > 65535)) {
        return "*** ERROR: Invalid port number ***";
    }

    $ipStr = (empty($ipStr)) ? IP_STR_WAN : $ipStr;                             //  For use via internet
    //          $ipStr = (empty($ipStr)) ? IP_STR_LOCAL : $ipStr;               //  For use within subnet
    //          $macStr = (empty($macStr)) ? MAC_ID__STR : strtoupper($macStr);           //  The target computer's MAC ID
    $macStr = strtoupper($macStr);                                              //  The target computer's MAC ID

    $macBytes = 'null';
    if (preg_match("/^([0-9a-f]{2}([-:]|$)){6}$/i", $macStr)) {
        $macBytes = preg_split("/[-:]/", $macStr);

        $bytes = str_repeat(chr(255), 6);
        $temp = '';
        for ($i = 0; $i < 6; $i++)
            $temp .= chr(hexdec($macBytes[$i]));

        $temp = str_repeat($temp, 16);
        $bytes .= $temp;
        if (!(preg_match("^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$^", $ipStr))) {
            $ipStr1 = gethostbyname($ipStr);
            if ($ipStr === $ipStr1) {
                return "*** ERROR: Could not resolve the provided hostname ***";
            } else {
                $ipStr = $ipStr1;
            }
        }
        if (preg_match("^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$^", $ipStr)) {  //  IP address is in a valid format
            if (function_exists('socket_create')) {
                $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                if (!$socket) {
                    return "*** ERROR: " . socket_strerror(socket_last_error()) . "***";
                }
                if (!socket_connect($socket, $ipStr, $port)) {
                    socket_close($socket);
                    return "*** ERROR: " . socket_strerror(socket_last_error()) . "***";
                }
                if (!socket_write($socket, $bytes, strlen($bytes))) {
                    return "*** ERROR: " . socket_strerror(socket_last_error()) . "***";
                } else {
                    return '';
                }
            } else {
                return "*** ERROR: This version of PHP does not support sockets ***";
            }
        } else {
            return "*** ERROR: Invalid IP address format specified ***";
        }
    } else {
        return "*** ERROR: Invalid MAC ID specified ***";
    }
}  //  End of WakeOnLan function
?>
