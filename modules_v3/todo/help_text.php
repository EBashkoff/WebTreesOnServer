<?php
// Module help text.
//
// This file is included from the application help_text.php script.
// It simply needs to set $title and $text for the help topic $help_topic
//
// webtrees: Web based Family History software
// Copyright (C) 2011 webtrees development team.
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
// $Id: help_text.php 11881 2011-06-22 09:57:57Z greg $

if (!defined('WT_WEBTREES') || !defined('WT_SCRIPT_NAME') || WT_SCRIPT_NAME!='help_text.php') {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

switch ($help) {
case 'todo':
	$title=WT_I18N::translate('Research tasks');
	$text=WT_I18N::translate('Research tasks are special events, added to individuals in your family tree, which identify the need for further research.  You can use them as a reminder to check facts against more reliable sources, to obtain documents or photographs, to resolve conflicting information, etc.').
		'</p><p class="ui-state-highlight">'.
		WT_I18N::translate('To create new research tasks, you must first add “research task” to the list of facts and events in the family tree’s preferences.').
		'</p><p class="ui-state-highlight">'.
		WT_I18N::translate('Research tasks are stored using the custom GEDCOM tag “_TODO”.  Other genealogy applications may not recognise this tag.').
		'</p>';
	break;
}
