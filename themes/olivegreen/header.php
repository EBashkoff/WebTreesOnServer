<?php

// Header for olivegreen theme
//
// webtrees: Web based Family History software
// Copyright (C) 2013 webtrees development team.
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
// $Id: header.php 14896 2013-03-22 12:57:01Z rob $

if (!defined('WT_WEBTREES')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// This theme uses the jQuery “colorbox” plugin to display images
$this
        ->addExternalJavascript(WT_JQUERY_COLORBOX_URL)
        ->addExternalJavascript(WT_JQUERY_WHEELZOOM_URL)
        ->addInlineJavascript('activate_colorbox();')
        ->addInlineJavascript('jQuery.extend(jQuery.colorbox.settings, {title:	function(){var img_title = jQuery(this).data("title");return img_title;}});');
echo
'<!DOCTYPE html>',
 '<html ', WT_I18N::html_markup(), '>',
 '<head>',
 '<meta charset="UTF-8">',
 '<title>', htmlspecialchars($title), '</title>',
 header_links($META_DESCRIPTION, $META_ROBOTS, $META_GENERATOR, $LINK_CANONICAL),
 '<link rel="icon" href="', WT_THEME_URL, 'favicon.png" type="image/png">',
 '<link rel="stylesheet" type="text/css" href="', WT_THEME_URL, 'jquery-ui-1.10.0/jquery-ui-1.10.0.custom.css">',
 '<link rel="stylesheet" type="text/css" href="', WT_THEME_URL, 'style.css', '">';

switch ($BROWSERTYPE) {
    case 'msie':
        echo '<link type="text/css" rel="stylesheet" href="', WT_THEME_URL, $BROWSERTYPE, '.css">';
        break;
}

// Additional css files required (Only if Lightbox installed)
if (WT_USE_LIGHTBOX) {
    echo '<link rel="stylesheet" type="text/css" href="', WT_STATIC_URL, WT_MODULES_DIR, 'lightbox/css/album_page.css" media="screen">';
}

echo
'</head>',
 '<body id="body">';
$header_on_login_page = (strstr($_SERVER['PHP_SELF'], 'login.php') == 'login.php');
// begin header section
if (($view != 'simple') && (strstr($_SERVER['PHP_SELF'], 'myportal.php') !== 'myportal.php') 
        && (strstr($_SERVER['PHP_SELF'], 'mydownloadpics.php') !== 'mydownloadpics.php')
        && (strstr($_SERVER['PHP_SELF'], 'mymaps.php') !== 'mymaps.php')) {
    global $WT_IMAGES;
    echo
    '<div id="header">',
    '<div class="header_img"><img src="', WT_THEME_URL . ($header_on_login_page ? 'images/Bashkoff.png' : 'images/WebtreesBashkoff.png'), '" width="242" height="70" alt="', WT_WEBTREES, '"></div>',
    '<div class="header_search" style="position: absolute; right:0px; top: 50px">',
    '<form action="search.php" method="post">',
    '<input type="hidden" name="action" value="general">',
    '<input type="hidden" name="topsearch" value="yes">',
    '<input type="search" name="query" size="25" placeholder="', WT_I18N::translate('Search'), '" dir="auto">',
    '<input type="image" class="image" src="', $WT_IMAGES['search'], '" alt="', WT_I18N::translate('Search'), '" title="', WT_I18N::translate('Search'), '">',
    '</form>',
    '</div>',
    '<ul id="extra-menu" class="makeMenu">';
    if (WT_USER_ID) {
        echo '<li><a href="myportal.php?userid=' . WT_USER_ID . '">Home</li>';
        echo '<li><a href="edituser.php">', WT_I18N::translate('Logged in as '), ' ', getUserFullName(WT_USER_ID), '</a></li>';
        echo WT_MenuBar::getFavoritesMenu();
        echo '<li>', logout_link(), '</li>';
    } else {
        echo '<li>', login_link(), '</li> ';
    }
    echo '</ul>',
    '<div id="topMenu">',
    '<ul id="main-menu">',
    WT_MenuBar::getGedcomMenu(),
    WT_MenuBar::getMyPageMenu(),
    WT_MenuBar::getChartsMenu(),
    WT_MenuBar::getListsMenu(),
    WT_MenuBar::getCalendarMenu(),
    WT_MenuBar::getReportsMenu(),
    WT_MenuBar::getSearchMenu(),
    implode('', WT_MenuBar::getModuleMenus()),
    '</ul>', // <ul id="main-menu">
    '</div>', // <div id="topMenu">
    '</div>'; // <div id="header">
}
echo
$javascript,
 WT_FlashMessages::getHtmlMessages(), // Feedback from asynchronous actions
'<div id="content">';

