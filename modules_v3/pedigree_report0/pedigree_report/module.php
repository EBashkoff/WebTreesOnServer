<?php
// Classes and libraries for module system
//
// webtrees: Web based Family History software
// Copyright (C) 2011 webtrees development team.
//
// Derived from PhpGedView
// Copyright (C) 2010 John Finlay
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
// $Id: module.php 12993 2011-12-05 18:13:17Z greg $

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

class pedigree_report_WT_Module extends WT_Module implements WT_Module_Report {
	// Extend class WT_Module
	public function getTitle() {
		// This text also appears in the .XML file - update both together
		return /* I18N: Name of a report */ WT_I18N::translate('Pedigree');
	}

	// Extend class WT_Module
	public function getDescription() {
		// This text also appears in the .XML file - update both together
		return /* I18N: Description of the "Pedigree" module */ WT_I18N::translate('A report of an individual’s ancestors, formatted as a tree.');
	}

	// Extend class WT_Module
	public function defaultAccessLevel() {
		return WT_PRIV_PUBLIC;
	}

	// Implement WT_Module_Report - a module can provide many reports
	public function getReportMenus() {
		global $controller;
		require_once WT_ROOT.WT_MODULES_DIR.$this->getName().'/class_pedigree.php';

		$menus=array();
		$menu=new WT_Menu(
			$this->getTitle(),
			'reportengine.php?ged='.WT_GEDURL.'&amp;action=setup&amp;report='.WT_MODULES_DIR.$this->getName().'/report.xml&amp;pid='.$controller->getSignificantIndividual()->getXref(),
			'menu-report-'.$this->getName()
		);
		$menu->addIcon('place');
		$menu->addClass('submenuitem', 'submenuitem_hover', 'submenu', 'icon_small_reports');
		$menus[]=$menu;

		$menu=new WT_Menu(
			$this->getTitle().' - '.WT_I18N::translate('Single page'),
			'reportengine.php?ged='.urlencode(WT_GEDCOM).'&amp;action=setup&amp;report='.WT_MODULES_DIR.$this->getName().'/report_singlepage.xml&amp;pid='.$controller->getSignificantIndividual()->getXref(),
			'menu-report-single'.$this->getName()
		);
		$menu->addClass("submenuitem", "submenuitem_hover", "submenu", "icon_small_reports");
		$menu->addIcon('pedigree');
		$menus[]=$menu;

		return $menus;
	}
}
