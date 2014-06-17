<?php
define('WT_SCRIPT_NAME', 'myportal_1.php');
require 'mysession.php';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <head>
        <meta content="en-us" http-equiv="Content-Language" />
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
        <title>Bashkoff Family Website</title>
        <link rel="icon" href="http://<?php echo $_SERVER['SERVER_NAME']; ?>/favicon.png" type="image/png"></link>
        <script src="js/myMoveNode.js"></script>
        <script src="js/myGetWindowClientArea.js"></script>
        <?php include 'mygalleryselectorheader.php'; ?>
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
        </style>
        <script type="text/javascript">
            $(document).ready(function() {
                if (getWindowClientArea()['type'] === 'phone') {
                    document.getElementById("outerframe").style.position = "absolute";
                    document.getElementById("outerframe").style.width = "100%";
                    movenode("eric", "ericphone");
                    movenode("debby", "debbyphone");
                    movenode("jessica", "jessicaphone");
                    movenode("allie", "alliephone");
                    document.getElementById("shorthead").style.display = "block";
                    document.getElementById("shortcontent").style.display = "block";
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

                var temp = document.getElementById("fullcontenttable").offsetHeight;
                document.getElementById("fullcontent").style.height = temp + "px";
                document.getElementById("outerframe").style.height = (temp + 130) + "px";
//                document.getElementById("outerframe").style.height = (document.getElementById("fullcontent").height -600) + "px";
                if (getWindowClientArea()['type'] === 'phone') {
                    var ttemp = $(document.getElementById("allie")).offset().top + $(document.getElementById("allie")).height();
                    document.getElementById("outerframe").style.height = (ttemp + 100) + "px";
                    document.getElementById("shortcontent").style.height = (ttemp - 45) + "px";
                }
            });
        </script>
    </head>
    <body style="background-color: #3C391B">
        <div id="outerframe" style="border: 3px solid #86815F; background-color:white; position: relative; margin:auto; padding:0px; height: 844px; width: 92%; top: 0px; left: 0px; color: #FFFFFF; visibility: visible;">
            <!--        **********MY WEB SITE HEADER STARTS HERE -->
            <div id="fullhead" style="display: none; margin:auto; width: 99%; height: 114px; text-align: bottom; color: #36341A; background-color: #86815F; visibility: visible; position: relative; top: 6px; left: 0px">
                <div style="float: left;">
                    <img alt="" src="./myindex_files/img3.jpg"/>
                    <?php echo '<span><a class="auto-style4" href="' . FILE_PATH_PREFIX . 'myportal.php?userid=' . $uid . '"><strong>Bashkoff Family Web Site</strong></a></span>'; ?>
                </div>
                <div style="float: right;">
                    <span class="auto-style5" style="height: 100%; float: right; padding: 85px 10px 0 0;">
                        <?php
                        echo 'Logged in as: <a href="edituser.php" class="auto-style5">' . $realusername . '</a>',
                        ' | <a  href="myportal.php?userid=' . $uid .'" class="auto-style5">Home</a>',
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
                        ' | <a  href="myportal.php?userid=' . $uid .'" style="color: #000000; text-decoration: none">Home</a>',
                        ' | <a href="index.php?logout=1" style="color: #000000; text-decoration: none" >Logout</a>';
                        ?>
                    </span>
                </div>
            </div>
            <div id="limebar" style="margin:auto; height: 12px; width: 99%; text-align: center; padding-left: 0px; padding-right: 0px; padding-top: 0px; color: #36341A; background-color: #9D9248; visibility: visible; position: relative; top: 0px; left: 0px">
            </div>
            <!--        **********MY WEB SITE HEADER ENDS HERE -->
            <div id="fullcontent" style="display: none; margin:auto; width: 99%; height: 680px; text-align: center; color: #36341A; background-color: #FFFFFF; visibility: visible; position: relative; top: 0px; left: 0px">
                <div>
                    <table id="fullcontenttable" style="width: 100%; height: 66%; left: 0px; top: 0px; position: absolute;">
                        <tr id="people" valign="top">
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
                                                <script type="text/javascript">
                                                    var links = document.getElementsByTagName('a');
                                                    for (var i = 0; i < links.length; i++) {
                                                        if (links[i].href.search("myPicShow") > -1) {
                                                            links[i].href = links[i].href + "&type=" + getWindowClientArea()['type'] + "&height=" + getWindowClientArea()['height'];
                                                        }
                                                    }
                                                </script>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td class="auto-style10" style="text-align: left; " rowspan="2" valign="top">
                                <table style="width: 100%; height: 100%; left: 0px; top: 0px; position: none;" cellpadding="4" cellspacing="10">
                                    <tr style="height: 5px">
                                        <td class="auto-style11" style="height: 8px;" colspan="2" valign="top">
                                            <label id="Label2"><strong>What's up with us</strong></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="width: 470px" valign="top"  align="left">
                                            <span class="auto-style11" >Eric</span>
                                            <div id="eric" style="display:inline; float:left; margin:5px 10px 0pt 0pt;" align="left">
                                                <img src="./myindex_files/Eric%20Pic%20With%20Tie.jpg"  alt="" height="200" width="150" style="float:left; margin: 2px 10px;"/>
                                                <span id="ericspan" style="font-family: Arial, Helvetica, sans-serif; color: #000000; text-decoration: none; text-align: left">Eric 
                                                    retired from his general surgery practice in April 2011. 
                                                    After 18 years of operating, his back trouble made it 
                                                    impossible for him to continue to operate. His patients 
                                                    have been incredibly understanding of this predicament 
                                                    and his physician partners, office manager and staff 
                                                    made this transition much easier. He will greatly miss 
                                                    his patients and performing operative procedures. He is 
                                                    now enrolled in a Masters degree program at University 
                                                    of Maryland University College studying Information 
                                                    Technology - Security, since he continues to be enamored 
                                                    with new technology and computers. He will be completing 
                                                    this program in August 2013 and is looking to join the workforce in an 
                                                    IT related field.&nbsp; He currently remains 
                                                    interested in programming, digital photography and 
                                                    swimming for exercise. On the weekends, he enjoys 
                                                    drinking craft beers, hot tubbing and travel planning. 
                                                    On the books currently is a trip to Las Vegas, in May 
                                                    2012.
                                                    <br /></span>
                                            </div>
                                        </td>
                                        <td style="width: 540px" valign="top"  align="left">
                                            <span class="auto-style11">Debby</span>
                                            <div id="debby" style="display:inline; float:left; margin:5px 10px 0pt 0pt;">
                                                <img src="./myindex_files/Debby%20Portrait.jpg" alt="" height="200" width="150" style="float:left; margin: 2px 10px;"/>
                                                <span id="debbyspan" style="font-family: Arial, Helvetica, sans-serif; color: #000000; text-decoration: none; text-align: left">For 
                                                    the early part of 2011, Debby was the basis of any form
                                                    of stability in our family, since she was the only one 
                                                    who knew what she would be doing during the latter 
                                                    part of 2011. While Jessica and Allie were 
                                                    uncertain of what schools they would be attending, 
                                                    and while Eric was entering into early retirement, Debby 
                                                    continued to do the job she loves as an Optometrist 
                                                    at James River Eye Physicians. She is extremely happy 
                                                    with her work arrangement, working 2 full days and 2 
                                                    half days each week. Her job affords her 
                                                    the flexibility to take time off and travel 
                                                    periodically, and she is particularly expert in 
                                                    planning trips; we often joke about her taking on a
                                                    second career as a travel agent. Her
                                                    next masterpiece of travel planning is a land/sea trip 
                                                    to Alaska for Eric and herself in July 2011 in 
                                                    celebration of 25 years of marriage. She is 
                                                    unquestionably an exercise buff and rarely misses a 
                                                    day at the gym while staying in excellent shape.
                                                    She is a rare example of someone who actually
                                                    has achieved an excellent ROI (return on investment) on 
                                                    home exercise equipment. She also has 
                                                    become highly skilled in the challenging art of 
                                                    cooking and baking tasty low fat food.
                                                    <br /></span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="width: 470px" valign="top"  align="left" >
                                            <span class="auto-style11" >Jessica</span>
                                            <div id="jessica" style="display:inline; float:left; margin:5px 10px 0pt 0pt;" align="left">
                                                <img src="./myindex_files/Jess%20Portrait.jpg"  alt="" height="200" width="150" style="float:left; margin: 2px 10px;"/>
                                                <span id="jessicaspan" style="font-family: Arial, Helvetica, sans-serif; color: #000000; text-decoration: none; text-align: left">
                                                    Jessica continues to amaze us with her academic achievements. 
                                                    She graduated from the University of Virginia with high honors 
                                                    and a Bachelor of Science degree in Mechanical Engineering in 2009. 
                                                    She moved to the Boston area (Waltham) and worked for BAE Systems 
                                                    designing hardware for thermal imaging systems. At work, 
                                                    she mastered using computer-aided design software and quickly 
                                                    came to realize that she wanted to further advance her education. 
                                                    She scored remarkably well on her GMAT exams and was admitted to MIT 
                                                    in a combined Engineering/MBA program called LGO (Leaders for 
                                                    Global Operations). This is a tremendous opportunity for her which 
                                                    begins in June 2011 and will last 2 years. While working in Boston, 
                                                    she engaged herself in social networking and became a very active 
                                                    member of the Boston Rotaract Club, where she met many friends and 
                                                    served previously as Membership Chair, and now as President. She 
                                                    spends much time doing volunteer work, baking, crafts, bicycle riding 
                                                    and going to the gym. She will be moving to West Haven, CT to work for 
                                                    Sikorsky Aircraft for her Internship beginning June 2012.
                                                    <br /></span>
                                            </div>
                                        </td>
                                        <td style="width: 540px" valign="top"  align="left">
                                            <span class="auto-style11">Allie</span>
                                            <div id="allie" style="display:inline; float:left; margin:5px 10px 0pt 0pt;" align="left">
                                                <img src="./myindex_files/Large-15475_9849C00-1.jpg"  alt="" height="200" width="150" style="float:left; margin: 2px 10px;"/>
                                                <span id="alliespan" style="font-family: Arial, Helvetica, sans-serif; color: #000000; text-decoration: none; text-align: left">
                                                    Allie has been tremendously busy just finishing her fall semester of
                                                    her sophomore year at Virginia Commonwealth University (VCU) in Richmond.
                                                    She transferred to VCU from George Mason University in Fairfax this year
                                                    and is really enjoying her experience at VCU.  She is on track to graduate with
                                                    a degree in social work and intends to pursue a Masters degree in social work 
                                                    later on.  In Richmond, she is volunteering and at home, she is working part-time
                                                    at The Gap in Patrick Henry Mall when she has time. Allie occupies every 
                                                    free moment being with her friends and she has a knack for effectively 
                                                    providing support for them when problems arise. Her communication skills 
                                                    are unmatched; she averages several thousand text messages per month and 
                                                    has worn out several mobile phones without developing carpal tunnel syndrome.
                                                    <br /></span>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div id="shortcontent" style="display: none; padding: 5px; margin:auto; width: 99%; font-size: 22pt; font-weight: 100; color: #36341A; background-color: #F0F0F0; visibility: visible; position: relative; top: 15px; left: 0px">
                <div id="people_small" style="text-align: center; font-size: 28pt; font-weight: 900;"><strong>What's up with us</strong>
                    <hr style="height: 3px; border-top: 1px; color: #A04D3E; background-color: #A04D3E"/></div>
                <div id="ericphone" style="text-align: left"><p style="font-size: 38pt; font-weight: 100; text-align: center">Eric</p> 
                </div>
                <div id="debbyphone" style="text-align: left; padding-top: 100px"><hr style="height: 3px; border-top: 1px; color: #A04D3E; background-color: #A04D3E"/>
                    <p style="font-size: 38pt; font-weight: 100; text-align: center"><br/>Debby</p> 
                </div>
                <div id="jessicaphone" style="text-align: left; padding-top: 100px"><hr style="height: 3px; border-top: 1px; color: #A04D3E; background-color: #A04D3E"/>
                    <p style="font-size: 38pt; font-weight: 100; text-align: center">
                        <br/>Jessica</p>
                </div>
                <div id="alliephone" style="text-align: left; padding-top: 100px"><hr style="height: 3px; border-top: 1px; color: #A04D3E; background-color: #A04D3E"/>
                    <p style="font-size: 38pt; font-weight: 100; text-align: center"><br/>Allie</p></div> 
            </div>
        </div>
        <div id="footer" style="display: none; position: relative; text-align: center; padding-top: 10px;">
            <span class="auto-style5-white">For technical support or genealogy questions, please contact <a href="mailto:bashkoff@bashkoff-family.com" class="auto-style5-white" >Eric Bashkoff</a></span> 
        </div>
    </body>
</html>