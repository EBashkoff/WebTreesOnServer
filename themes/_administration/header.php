<?php
// Header for webtrees administration theme
//
// webtrees: Web based Family History software
// Copyright (C) 2013 webtrees development team.
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
	'<link rel="icon" href="', WT_THEME_URL, 'favicon.png" type="image/png">',
	'<link rel="stylesheet" href="', WT_THEME_URL, 'jquery-ui-1.10.0/jquery-ui-1.10.0.custom.css" type="text/css">',
	'<link rel="stylesheet" href="', WT_THEME_URL, 'style.css', '" type="text/css" media="all">',
	'<meta name="robots" content="noindex,nofollow">';
	
switch ($BROWSERTYPE) {
case 'msie':
	echo '<link type="text/css" rel="stylesheet" href="', WT_THEME_URL, $BROWSERTYPE, '.css">';
	break;
}

echo
	$javascript,
	'</head>',
	'<body id="body">',
// Header
	'<div id="admin_head" class="ui-widget-content">',
	'<i class="icon-webtrees"></i>',
	'<div id="title"><a href="admin.php">', WT_I18N::translate('Administration'), '</a></div>',
	'<div id="links">',
	'<a href="index.php">', WT_I18N::translate('My page'), '</a> | ',
	logout_link(),
	'<span>';
	$language_menu=WT_MenuBar::getLanguageMenu();
		if ($language_menu) {
			echo ' | ', $language_menu->getMenuAsList();
		}
	echo '</span>';
	if (WT_USER_CAN_ACCEPT && exists_pending_change()) {
	echo ' | <li><a href="#" onclick="window.open(\'edit_changes.php\',\'_blank\', chan_window_specs); return false;" style="color:red;">', WT_I18N::translate('Pending changes'), '</a></li>';
	}
	echo '</div>',
	'<div id="info">',
	WT_WEBTREES, ' ', WT_VERSION_TEXT,
	'<br>',
	WT_I18N::translate('Current Server Time:'), ' ', format_timestamp(WT_TIMESTAMP),
	'</div>',
	'</div>',
// Side menu
	'<div id="admin_menu" class="ui-widget-content">',
	'<ul>',
	'<li><a ', (WT_SCRIPT_NAME=="admin.php" ? 'class="current" ' : ''), 'href="admin.php">', WT_I18N::translate('Administration'), '</a></li>';
if (WT_USER_IS_ADMIN) {
	echo
		'<li><ul>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_site_config.php"  ? 'class="current" ' : ''), 'href="admin_site_config.php">',  WT_I18N::translate('Site configuration'    ), '</a></li>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_site_logs.php"    ? 'class="current" ' : ''), 'href="admin_site_logs.php">',    WT_I18N::translate('Logs'                  ), '</a></li>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_site_readme.php"  ? 'class="current" ' : ''), 'href="admin_site_readme.php">',  WT_I18N::translate('README documentation'  ), '</a></li>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_site_info.php"    ? 'class="current" ' : ''), 'href="admin_site_info.php">',    WT_I18N::translate('PHP information'       ), '</a></li>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_site_access.php"  ? 'class="current" ' : ''), 'href="admin_site_access.php">',  WT_I18N::translate('Site access rules'     ), '</a></li>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_site_clean.php"   ? 'class="current" ' : ''), 'href="admin_site_clean.php">',   WT_I18N::translate('Clean up data folder'), '</a></li>',
		'</ul></li>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_trees_manage.php" ? 'class="current" ' : ''), 'href="admin_trees_manage.php">', WT_I18N::translate('Family trees'          ), '</a></li>';
} else {
	echo '<li>', WT_I18N::translate('Family trees'), '</li>';
}
echo '<li><ul>';
//-- gedcom list
foreach (WT_Tree::getAll() as $tree) {
	if (userGedcomAdmin(WT_USER_ID, $tree->tree_id)) {
		// Add a title="" element, since long tree titles are cropped
		echo
			'<li><span><a ', (WT_SCRIPT_NAME=="admin_trees_config.php" && WT_GED_ID==$tree->tree_id ? 'class="current" ' : ''), 'href="admin_trees_config.php?ged='.$tree->tree_name_url.'" title="', htmlspecialchars($tree->tree_title), '" dir="auto">', $tree->tree_title_html,
			'</a></span></li>';
	}
}
echo
	'<li><a ', (WT_SCRIPT_NAME=="admin_site_merge.php" ? 'class="current" ' : ''), 'href="admin_site_merge.php">',   WT_I18N::translate('Merge records'), '</a></li>',
	'<li><a ', (WT_SCRIPT_NAME=="admin_site_other.php" ? 'class="current" ' : ''), 'href="admin_site_other.php">',   WT_I18N::translate('Add unlinked records'), '</a></li>',
	'<li><a ', (WT_SCRIPT_NAME=="admin_trees_check.php" ? 'class="current" ' : ''), 'href="admin_trees_check.php">', WT_I18N::translate('Check for errors'), '</a></li>',
	'<li><a ', (WT_SCRIPT_NAME=="admin_site_change.php" ? 'class="current" ' : ''), 'href="admin_site_change.php">', WT_I18N::translate('Changes log'),'</a></li>',
	'<li><a href="index_edit.php?gedcom_id=-1" onclick="return modalDialog(\'index_edit.php?gedcom_id=-1'.'\', \'',  WT_I18N::translate('Set the default blocks for new family trees'), '\');">', WT_I18N::translate('Set the default blocks'), '</a></li>',
	'</ul></li>';

if (WT_USER_IS_ADMIN) {
	echo
		'<li><a ', (WT_SCRIPT_NAME=="admin_users.php" && safe_GET('action')!="cleanup"&& safe_GET('action')!="createform" ? 'class="current" ' : ''), 'href="admin_users.php">',
		WT_I18N::translate('Users'),
		'</a></li>',
		'<li><ul>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_users.php" && safe_GET('action')=="createform" ? 'class="current" ' : ''), 'href="admin_users.php?action=createform">', WT_I18N::translate('Add a new user'), '</a></li>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_users_bulk.php" ? 'class="current" ' : ''), 'href="admin_users_bulk.php">', WT_I18N::translate('Send broadcast messages'), '</a>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_users.php" && safe_GET('action')=="cleanup" ? 'class="current" ' : ''), 'href="admin_users.php?action=cleanup">', WT_I18N::translate('Delete inactive users'), '</a></li>',
		'<li><a href="index_edit.php?user_id=-1" onclick="return modalDialog(\'index_edit.php?user_id=-1'.'\', \'', WT_I18N::translate('Set the default blocks for new users'), '\');">', WT_I18N::translate('Set the default blocks'), '</a></li>',
		
		
		'</ul></li>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_media.php" ? 'class="current" ' : ''), 'href="admin_media.php">', WT_I18N::translate('Media'), '</a></li>',
		'<li><ul>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_media_upload.php" ? 'class="current" ' : ''), 'href="admin_media_upload.php">', WT_I18N::translate('Upload media files'), '</a></li>',
		'</ul></li>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_modules.php" ? 'class="current" ' : ''), 'href="admin_modules.php">',
		WT_I18N::translate('Modules'),
		'</a></li>',
		'<li><ul>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_module_menus.php"   ? 'class="current" ' : ''), 'href="admin_module_menus.php">',   WT_I18N::translate('Menus'  ), '</a></li>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_module_tabs.php"    ? 'class="current" ' : ''), 'href="admin_module_tabs.php">',    WT_I18N::translate('Tabs'   ), '</a></li>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_module_blocks.php"  ? 'class="current" ' : ''), 'href="admin_module_blocks.php">',  WT_I18N::translate('Blocks' ), '</a></li>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_module_sidebar.php" ? 'class="current" ' : ''), 'href="admin_module_sidebar.php">', WT_I18N::translate('Sidebar'), '</a></li>',
		'<li><a ', (WT_SCRIPT_NAME=="admin_module_reports.php" ? 'class="current" ' : ''), 'href="admin_module_reports.php">', WT_I18N::translate('Reports'), '</a></li>',
		'</ul></li>';
	foreach (WT_Module::getActiveModules(true) as $module) {
		if ($module instanceof WT_Module_Config) {
			echo '<li><span><a ', (WT_SCRIPT_NAME=="module.php" && safe_GET('mod')==$module->getName() ? 'class="current" ' : ''), 'href="', $module->getConfigLink(), '">', $module->getTitle(), '</a></span></li>';
		}
	}
}
echo
	'</ul>',
	'</div>',
	'<div id="admin_content" class="ui-widget-content">',
	WT_FlashMessages::getHtmlMessages(); // Feedback from asynchronous actions;
