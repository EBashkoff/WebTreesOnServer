<?php

// Footer for webtrees theme
//
// webtrees: Web based Family History software
// Copyright (C) 2012 webtrees development team.
//
// Derived from PhpGedView
// Copyright (C) 2002 to 2009  PGV Development Team.  All rights reserved.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// $Id: footer.php 13642 2012-03-24 13:06:08Z greg $

if (!defined('WT_WEBTREES')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

echo '</div>'; // <div id="content">

//if (1===0) {
if ($view != 'simple') {
    echo '<div id="footer">';
    echo contact_links();
    if (strstr($_SERVER['PHP_SELF'], 'login.php') != 'login.php') {
        echo '<p class="logo">';
        echo '<a href="', WT_WEBTREES_URL, '" target="_blank" class="icon-webtrees" title="', WT_WEBTREES, ' ', WT_VERSION_TEXT, '"></a>';
        echo '</p>';
    }
    if (WT_DEBUG || get_gedcom_setting(WT_GED_ID, 'SHOW_STATS')) {
        echo execution_stats();
    }
    if (exists_pending_change()) {
        echo '<a href="#" onclick="window.open(\'edit_changes.php\', \'_blank\', chan_window_specs); return false;">';
        echo '<p class="error center">', WT_I18N::translate('There are pending changes for you to moderate.'), '</p>';
        echo '</a>';
    }
    echo '</div>'; // <div id="footer">
}

if (strpos($_SERVER['PHP_SELF'], 'login.php')) { ?>
    <script src="js/myGetWindowClientArea.js"></script>
    <script type="text/javascript">
        if (document.getElementsByTagName("b")[0]) {
            var getlogintext = document.getElementsByTagName("b")[0];
            getlogintext.innerHTML = getlogintext.innerHTML.replace("this Genealogy website", "the Bashkoff Family Website");
        }
        if (getWindowClientArea()["type"] == "phone") {
            var headelementinlogin = document.getElementById("header");
            if (headelementinlogin) {
                headelementinlogin.style.display = "none";
                document.getElementById("login-form").setAttribute("style", "font-size: 40px");
                document.getElementById("login-page").setAttribute("style", "font-size: 40px");
                document.getElementById("footer").setAttribute("style", "padding-right: 10px"); 
                document.getElementById("footer").setAttribute("style", "font-size: 40px");
                document.getElementById("login-form").style.width = "90%";
                document.getElementById("new_passwd_form").setAttribute("style", "font-size: 40px");
                document.getElementById("new_passwd_form").children[1].setAttribute("style", "font-size: 40px");
                document.getElementById("new_passwd_form").style.width = "90%";
                var inputelements = document.getElementsByTagName("input");
                for (var i = 0; i < inputelements.length; i++) {
                    inputelements[i].setAttribute("style", "font-size: 40px");
                    if (inputelements[i].getAttribute("type")!== "submit") {
                        inputelements[i].style.width="90%";
                    } else if (inputelements[i].getAttribute("type") === "submit") {
                        inputelements[i].style.height="100px";
                        inputelements[i].style.padding="10px";
                    }
                }    
                var labelelements = document.getElementsByTagName("label");
                for (i = 0; i < labelelements.length; i++) {
                    labelelements[i].style.textAlign="center";
                }
            }
        }
    </script>
    <?php ;
}
