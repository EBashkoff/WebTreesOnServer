<script type="text/javascript" src="js/jquery-1.9.1.js"></script>
<script src="js/myGetWindowClientArea.js"></script>
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
    .auto-style5small {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 18pt; font-weight: 900; 
        color: #000000;
        text-align: right;
        text-decoration: none;
    }
    .div-centerstyle {
        margin-left: auto;
        margin-right: auto;
        width: 625px;
    }
</style>

<script type="text/javascript">
    $(document).ready(function() {
        document.getElementById("body").style.display = "block";  //  Display was blanked on loading webtrees default login page - now unblank it
        document.getElementById("header").style.display = "none";
        document.getElementById("footer").style.color = "white";
        document.getElementById("footer").getElementsByTagName("a")[0].style.color = "white";
        document.getElementsByTagName("body")[0].style.backgroundColor = "#3C391B";
        document.getElementsByTagName("body")[0].style.paddingTop = "8px";
        var confirmElement = document.getElementsByClassName("confirm")[0];  // This div places the response for password request in the modifed page
        if (confirmElement) document.getElementById("confirm_placeholder").appendChild(confirmElement.parentNode.removeChild(confirmElement));
        if (document.getElementById("login-form")) {  //  Check to make sure this is login form and not registration form            
            document.getElementById("login_form_placeholder").appendChild(document.getElementById("login-text"));    
            document.getElementById("login_form_placeholder").appendChild(document.getElementById("login-form"));    
            document.getElementById("lost_request_placeholder").appendChild(document.getElementById("new_passwd_form"));
        }
        if (getWindowClientArea()['type'] === 'phone') {
            document.getElementById("outerframe").style.position = "absolute";
            document.getElementById("outerframe").style.width = "100%";
            document.getElementById("outerframe").style.height = 1350 + "px";
            document.getElementById("shorthead").style.display = "block";
            document.getElementById("shorthead").style.height = "60px";
            document.getElementById("login-text").style.fontSize = "20pt"; 
            document.getElementById("fullcontent").style.display = "block";
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
    });
</script>
<div id="outerframe" style="border: 3px solid #86815F; background-color:white; position: relative; margin:auto; padding:0px; height: 700px; width: 92%; top: 0px; left: 0px; color: #FFFFFF; visibility: visible;">
    <!--        **********MY WEB SITE HEADER STARTS HERE -->
    <div id="fullhead" style="display: none; margin:auto; width: 99%; height: 114px; text-align: bottom; color: #36341A; background-color: #86815F; visibility: visible; position: relative; top: 6px; left: 0px">
        <div style="width: 100%; height: 80%; margin-bottom: 0px">
            <img alt="" src="./myindex_files/img3.jpg"/>
            <span class="auto-style4"><strong>Bashkoff Family Web Site</strong></span>
            <span class="auto-style5" style="height: 100%; float: right; padding: 85px 10px 0 0;">Please log in below</span>
        </div>
    </div>
    <div id="shorthead" style="display: none; margin:auto; width: 99%; height: 60px; font-size: 28pt; padding-left: 0px; padding-right: 0px; padding-top: 4px; color: #36341A; background-color: #86815F; visibility: visible; position: relative; top: 4px; left: 0px">
        <div style="width: 100%; height: 100%; padding-bottom: 4px; padding-right: 4px; padding-left: 4px">
            <span style="float: left; font-weight: 900;"><strong>Bashkoff Family Web Site</strong></span>
            <span style="float: right; margin-right: 12px; font-weight: initial;">Please log in below</span>
        </div>
    </div>
    <div id="limebar" style="margin:auto; height: 12px; width: 99%; text-align: center; padding-left: 0px; padding-right: 0px; padding-top: 0px; color: #36341A; background-color: #9D9248; visibility: visible; position: relative; top: 0px; left: 0px">
    </div>
    <!--        **********MY WEB SITE HEADER ENDS HERE -->
    <div id="fullcontent" style="display: none; margin:auto; width: 99%; height: auto; text-align: center; color: #36341A; background-color: #FFFFFF; visibility: visible; position: relative; top: 0px; left: 0px">
        <div id="confirm_placeholder" class="div-centerstyle"></div>
        <div id="login_form_placeholder" class="div-centerstyle"></div>
        <div id="lost_request_placeholder" class="div-centerstyle"></div>
        <div  class="div-centerstyle" style="width: 250px;">
            <img src="themes/olivegreen/images/comodo_secure_113x59_transp.png" alt="SSL Certificate" width="113" height="59" style="margin-top: 5px;"><br>
            <span style="font-weight:bold; font-size:7pt">Your login is secure with this SSL Certificate</span><br>
        </div>
    </div>
</div>