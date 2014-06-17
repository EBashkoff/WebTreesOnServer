<?php
//	Controller for the compact chart
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
// $Id: Compact.php 14923 2013-03-27 07:51:38Z greg $

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

class WT_Controller_Compact extends WT_Controller_Chart {
	// Data for the view
	public $show_thumbs=false;

	// Date for the controller
	private $treeid=array();

	public function __construct() {
		parent::__construct();

		// Extract the request parameters
		$this->show_thumbs=safe_GET_bool('show_thumbs');

		if ($this->root && $this->root->canDisplayName()) {
			$this->setPageTitle(
				/* I18N: %s is an individual’s name */
			WT_I18N::translate('Compact tree of %s', $this->root->getFullName())
		);
		} else {
			$this->setPageTitle(WT_I18N::translate('Compact tree'));
		}
		$this->treeid=ancestry_array($this->rootid, 5);
	}
	
	function sosa_person($n) {
		global $SHOW_HIGHLIGHT_IMAGES;

		$indi=WT_Person::getInstance($this->treeid[$n]);

		if ($indi && $indi->canDisplayName()) {
			$name=$indi->getFullName();
			$addname=$indi->getAddName();

			if ($this->show_thumbs && $SHOW_HIGHLIGHT_IMAGES) {
				$html=$indi->displayImage();
			}

			$html .= '<a class="name1" href="'.$indi->getHtmlUrl().'">';
			$html .= $name;
			if ($addname) $html .= '<br>' . $addname;
			$html .= '</a>';
			$html .= '<br>';
			if ($indi->canDisplayDetails()) {
				$html.='<div class="details1">'.$indi->getLifeSpan().'</div>';
			}
		} else {
			// Empty box
			$html = '&nbsp;';
		}

		// -- box color
		$isF='';
		if ($n==1) {
			if ($indi && $indi->getSex()=='F') {
				$isF='F';
			}
		} elseif ($n%2) {
			$isF='F';
		}

		// -- box size
		if ($n==1) {
			return '<td class="person_box'.$isF.' person_box_template" style="text-align:center; vertical-align:top;">'.$html.'</td>';
		} else {
			return '<td class="person_box'.$isF.' person_box_template" style="text-align:center; vertical-align:top;" width="15%">'.$html.'</td>';
		}
	}

	function sosa_arrow($n, $arrow_dir) {
		global $TEXT_DIRECTION;

		$pid = $this->treeid[$n];

		$arrow_swap = array("l"=>"0", "r"=>"1", "u"=>"2", "d"=>"3");

		$arrow_dir = substr($arrow_dir,0,1);
		if ($TEXT_DIRECTION=="rtl") {
			if ($arrow_dir=="l") {
				$arrow_dir="r";
			} elseif ($arrow_dir=="r") {
				$arrow_dir="l";
			}
		}

		if ($pid) {
			$indi=WT_Person::getInstance($pid);
			$title=WT_I18N::translate('Compact tree of %s', $indi->getFullName());
			$text = '<a class="icon-'.$arrow_dir.'arrow" title="'.strip_tags($title).'" href="?rootid='.$pid;
			if ($this->show_thumbs) $text .= "&amp;show_thumbs=".$this->show_thumbs;
			$text .= "\"></a>";
		} else {
			$text = '<i class="icon-'.$arrow_dir.'arrow"></i>';
		}

		return $text;
	}
}