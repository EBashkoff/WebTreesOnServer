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
    .div-centerstyle {
        margin-left: auto;
        margin-right: auto;
        width: 625px;
    }
</style>

<script type="text/javascript">
    $(document).ready(function() {
        document.getElementById("body").style.display = "block";  //  Display was blanked on loading webtrees default login page - now unblank it        
        if (document.getElementById("outerframe")) document.getElementById("outerframe").style.display = "none";
        document.getElementById("header").style.display = "none";
        document.getElementById("footer").style.color = "white";
        document.getElementById("footer").getElementsByTagName("a")[0].style.color = "white";
        document.getElementsByTagName("body")[0].style.backgroundColor = "#3C391B";
        document.getElementById("fullcontent").appendChild(document.getElementById("login-register-page"));
        var confirmElement = document.getElementsByClassName("confirm")[0];
        if (confirmElement) document.getElementById("confirm_placeholder").appendChild(confirmElement.parentNode.removeChild(confirmElement));
        var flashmessagesElement = document.getElementById("flash-messages");
        if (flashmessagesElement) document.getElementById("register-text").appendChild(flashmessagesElement.parentNode.removeChild(flashmessagesElement));
        
        if (getWindowClientArea()['type'] === 'phone') {
            document.getElementById("outerframe_reg_page").style.position = "absolute";
            document.getElementById("outerframe_reg_page").style.width = "100%";
            document.getElementById("outerframe_reg_page").style.height = 1000 + "px";
            document.getElementById("shorthead").style.display = "block";
            document.getElementById("shorthead").style.height = "60px";
            document.getElementById("fullcontent").style.display = "block";
            document.getElementById("register-form").setAttribute("style", "font-size: 40px");
            document.getElementById("register-form").children[1].setAttribute("style", "font-size: 40px");
            document.getElementById("register-form").style.width = "90%";
            var inputelements = document.getElementsByTagName("input");
            for (var i = 0; i < inputelements.length; i++) {
                inputelements[i].setAttribute("style", "font-size: 40px");
                if (inputelements[i].getAttribute("type")!== "submit") {
                inputelements[i].style.width="50%";
                } else if (inputelements[i].getAttribute("type") === "submit") {
                    inputelements[i].style.height="100px";
                    inputelements[i].style.padding="10px";
                }
            }
            document.getElementsByTagName("textarea")[0].parentNode.setAttribute("style", "display: none");  //  Turn off comments section for phone
        } else {
            document.getElementById("fullhead").style.display = "block";
            document.getElementById("fullcontent").style.display = "block";
            document.getElementById("footer").style.display = "block";
            if (getWindowClientArea()['type'] === 'tablet') {
                document.getElementById("outerframe_reg_page").style.position = "relative";
                document.getElementById("outerframe_reg_page").style.width = "98%";
                document.getElementById("outerframe_reg_page").style.marginLeft = "auto";
                document.getElementById("outerframe_reg_page").style.marginRight = "11px";
            }
        }
    });
</script>
<div id="outerframe_reg_page" style="border: 3px solid #86815F; background-color:white; position: relative; margin:auto; padding:0px; height: 760px; width: 92%; top: 0px; left: 0px; color: #FFFFFF; visibility: visible;">
    <!--        **********MY WEB SITE HEADER STARTS HERE -->
    <div id="fullhead" style="display: none; margin:auto; width: 99%; height: 114px; text-align: bottom; color: #36341A; background-color: #86815F; visibility: visible; position: relative; top: 6px; left: 0px">
        <div style="width: 100%; height: 80%; margin-bottom: 0px">
            <img alt="" src="./myindex_files/img3.jpg"/>
            <span class="auto-style4"><strong>Bashkoff Family Web Site</strong></span>
            <span class="auto-style5" style="height: 100%; float: right; padding: 85px 10px 0 0;">Please register below</span>
        </div>
    </div>
    <div id="shorthead" style="display: none; margin:auto; width: 99%; height: 60px; font-size: 28pt; padding-left: 0px; padding-right: 0px; padding-top: 4px; color: #36341A; background-color: #86815F; visibility: visible; position: relative; top: 4px; left: 0px">
        <div style="width: 100%; height: 100%; padding-bottom: 4px; padding-right: 4px; padding-left: 4px">
            <span style="float: left; font-weight: 900;"><strong>Bashkoff Family Web Site</strong></span>
            <span style="float: right; margin-right: 12px; font-weight: initial;">Please register below</span>
        </div>
    </div>
    <div id="limebar" style="margin:auto; height: 12px; width: 99%; text-align: center; padding-left: 0px; padding-right: 0px; padding-top: 0px; color: #36341A; background-color: #9D9248; visibility: visible; position: relative; top: 0px; left: 0px">
    </div>
    <!--        **********MY WEB SITE HEADER ENDS HERE -->
    <div id="fullcontent" style="display: none; margin:auto; width: 99%; height: auto; text-align: center; color: #36341A; background-color: #FFFFFF; visibility: visible; position: relative; top: 0px; left: 0px">
        <div id="confirm_placeholder" class="div-centerstyle" style="text-align: left;"></div>
        <div id="flashmessages_placeholder" class="div-centerstyle"></div>
    </div>
    <div  class="div-centerstyle" style="width: 270px; text-align: center">
        <img src="themes/olivegreen/images/comodo_secure_76x26_transp.png" alt="SSL" width="76" height="26" style="margin-top: 5px;"><br>
        <span style="float: right; color: black; font-weight:bold; font-size:7pt">Your information is secure with this SSL Certificate</span>
    </div>
</div>