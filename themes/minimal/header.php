<?php
// Header for Minimal theme
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
	->addInlineJavascript('
		jQuery.extend(jQuery.colorbox.settings, {
			title:	function(){
					var img_title = jQuery(this).data("title");
					return img_title;
			}
		});
	');
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
//case 'chrome': uncomment when chrome.css file needs to be added, or add others as needed
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

// begin header section
if ($view!='simple') {
	echo '<div id="header">';
	echo '<ul class="makeMenu">';
	echo '<li class="title" dir="auto">', WT_TREE_TITLE ,'</li>';
	echo '<div>';
	if (WT_USER_ID) {
		echo '<li><a href="edituser.php">', getUserFullName(WT_USER_ID), '</a></li> <li>', logout_link(), '</li>';
		if (WT_USER_CAN_ACCEPT && exists_pending_change()) {
			echo ' <li><a href="#" onclick="window.open(\'edit_changes.php\',\'_blank\',chan_window_specs); return false;" style="color:red;">', WT_I18N::translate('Pending changes'), '</a></li>';
		}
	} else {
		echo '<li>', login_link(), '</li> ';
	}
	$menu=WT_MenuBar::getFavoritesMenu();
	if ($menu) {
		echo $menu->getMenuAsList();
	}
	$menu=WT_MenuBar::getLanguageMenu();
	if ($menu) {
		echo $menu->getMenuAsList();
	}
	$menu=WT_MenuBar::getThemeMenu();
	if ($menu) {
		echo $menu->getMenuAsList();
	}
	echo
		'<li><form style="display:inline;" action="search.php" method="post">',
		'<input type="hidden" name="action" value="general">',
		'<input type="hidden" name="topsearch" value="yes">',
		'<input type="search" name="query" size="20" placeholder="', WT_I18N::translate('Search'), '" dir="auto">',
		'</form></li></div>',
		'</ul>';
	$menu_items=array(
		WT_MenuBar::getGedcomMenu(),
		WT_MenuBar::getMyPageMenu(),
		WT_MenuBar::getChartsMenu(),
		WT_MenuBar::getListsMenu(),
		WT_MenuBar::getCalendarMenu(),
		WT_MenuBar::getReportsMenu(),
		WT_MenuBar::getSearchMenu(),
	);
	foreach (WT_MenuBar::getModuleMenus() as $menu) {
		$menu_items[]=$menu;
	}
	// Print the menu bar
	echo
		'<div id="topMenu">',
		'<ul id="main-menu">';
	foreach ($menu_items as $menu) {
		if ($menu) {
			echo $menu->getMenuAsList();
		}
	}
	unset($menu_items, $menu);
	echo
		'</ul>',  // <ul id="main-menu">
		'</div>', // <div id="topMenu">
		'</div>'; // <div id="header">
}
echo
	$javascript,
	WT_FlashMessages::getHtmlMessages(), // Feedback from asynchronous actions
	'<div id="content">';
