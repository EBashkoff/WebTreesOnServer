<?php
// Update the database schema from version 13 to 14
// - delete old config settings
//
// The script should assume that it can be interrupted at
// any point, and be able to continue by re-running the script.
// Fatal errors, however, should be allowed to throw exceptions,
// which will be caught by the framework.
// It shouldn't do anything that might take more than a few
// seconds, for systems with low timeout values.
//
// webtrees: Web based Family History software
// Copyright (C) 2011 Greg Roach
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
// $Id: db_schema_13_14.php 14276 2012-09-14 15:39:40Z greg $

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

// Remove the i_isdead column
try {
	self::exec("ALTER TABLE `##individuals` DROP i_isdead");
} catch (PDOException $ex) {
	// Already done this?
}

// Update the version to indicate success
WT_Site::preference($schema_name, $next_version);

