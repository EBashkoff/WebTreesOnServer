<?php
/**
 * PDF Report Generator
 *
 * used by the SAX parser to generate PDF reports from the XML report file.
 *
 * webtrees: Web based Family History software
 * Copyright (C) 2011 webtrees development team.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id: class_pedigree.xml 11124 2011-03-15 20:20:18Z lukasz $
 * @link http://www.adobe.com/devnet/pdf/
 * @link http://www.tcpdf.org
 *
 */

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

/**
 *
 * @todo more then one generation of desendants
 *
 */
define('WT_CLASS_REPORTPEDIGREE_PHP', '');

require_once WT_ROOT.'includes/functions/functions_date.php';
require_once WT_ROOT.'includes/functions/functions_places.php';
require_once WT_ROOT.'library/tcpdf/tcpdf.php';

/**
 * Main Report Pedigree on single Page Class for PDF
 *
 * @subpackage Reports
 */
class ReportPedigree /*{ //*/  extends TCPDF {
  protected $connectStyle=array('parents'=>array('width'=>.2,  'cap' => 'round', 'join'=>'round', 'color'=>array(0,   0,  80)),  // line to parents
				'spouse' =>array('width'=>.2,  'cap' => 'round', 'join'=>'round', 'color'=>array(0,   0,  80)),  // line to spouse
				'sibling'=>array('width'=>.15, 'cap' => 'butt',  'join'=>'round', 'color'=>array(80,  80, 160)), // line to sibling
				'nextGen'=>array('width'=>.25, 'cap' => 'round', 'join'=>'round', 'color'=>array(0,   0,  80)));
  /**
   * Sizes of boxes for different persons
   * array( string boxsize => array(scale factor width, scale factor height ))
   */
  protected $boxSizes= array();

  /**
   * 4-dimeninal array with points for box of persons
   *
   * this array is set in function setOrientation()
   *
   * array('full|full_ref|child_full|child_full_ref|child_short|child_short_ref' =>
   *		 array('leave|inside|child'=>array(array(p1), array(p2), ... , array(pN))))
   */
  protected $pointsBorder=array();
  protected $borderStyle=array('M'=>array('width'=>.1, 'cap' => 'round', 'join'=>'round', 'color'=>array(64,  64, 192)),
				   'F'=>array('width'=>.1, 'cap' => 'round', 'join'=>'round', 'color'=>array(192, 66, 66)),
				   'U'=>array('width'=>.1, 'cap' => 'round', 'join'=>'round', 'color'=>array(66,  66, 66)),
				   'E'=>array('width'=>.1, 'cap' => 'round', 'join'=>'round', 'color'=>array(127,127, 127)));
  protected $fillColor = array('M'=>array(240, 240, 255),
				   'F'=>array(255, 240, 240),
				   'U'=>array(240, 240, 240),
				   'E'=>array(255, 255, 255));

  protected $FontSize=4.9;
  protected $yBrim=20;					   //  | page borders
  protected $xBrim=20;					   //  | 
  protected $spaceCompress=0.08;			 // additional space after compression
  protected $connectPos=.6;				  // parents to children connection (0=child 1=parents)  |
  protected $connectChildNoDisplay=.25;	  // line for siblings which are not displayed		   | relativ to 
											 // (from  $connectChildNoDisplay to  $connectPos)	  |  $xSpace/$ySpace
  protected $radius=0.5;					 // radius of edges for the connection lines			|
  protected $headerHeight=5;				 // height of header
  protected $xSpace=null;					//  |
  protected $xWidth=null;					//  | different for 
  protected $ySpace=null;					//  | 'portrait' and 'landscape'
  protected $yWidth=null;					//  |

  /**
   * scaling factor of person box in direction of same generation
   * an array 'DISPLAYTYPE'=> size where DISPLAYTYPE is 'full' or 'short'
   * used for display of siblings
   */
  protected $scaleSameGen=array();
  /**
   * scaling factor of person box in direction of next generation
   * used for double displayed persons set in function setOrientation()
   */
  protected $scaleNextGen=null;

  protected $maxSpaceNoDisplay=.2;				 // maximum space between lines when siblings are not displayed
  protected $addSpaceSiblings=0;				   // additional space between not displayed siblings and ancestors

  protected $allIndividuals= array();	// all indiviuals which apears in pedigree tree array('gedcomXREF'=>class::Person)
  /**
   * array (integer=> string)
   * hold 'gedrom xref' of all ancestors or
   * 'REFTO_gedcom xref' if individual is twice in ancestors array(int=>string 'XREF')
   * father of $ancestors[$i] is $ancestors[$i*2], mother is $ancestors[$i*2+1]
   * if equal null then parent of $ancestors[floor($i/2)] don't exists   
   */
  protected $ancestors	 = array();
  protected $ancestorsRev  = array();	// array(string 'XREF' => integer)
  protected $families   = array();	   // all families, in $families[$i] is the primary child family of $ancestors[$i] 
  protected $children   = array();				 // 2-dim children of all families array(famNr=>array(ChildIDs, ...))
  protected $genShow=1;							// how many Generation are shown
  protected $descendants=array();
  protected $scaleDescen='full';
  protected $spouses=array();

  protected $positions  = array();				 // positions of direct ancestors of first person array(int number => float position)
  protected $leaves	 = array();				 // persons with no ancestors			  array ( =>)
  protected $leavesSort = array();				 // 
  protected $leavePositions=array();			   // positions of leaves, same style as $positions
  protected $EventsShow = array();				 // 1-dim array to store which events are displayed	array (ID=> true|false)
  protected $showSiblings=true;					// should the siblings displayed in case of enough space in the tree
  protected $showSpouses =false;				   // should the spouses of the first person displayed
  protected $displaySiblings=array();			  // 1-dim array how the siblings of each family are displayed array( int number => false|'full'|'short')

  protected $output='PDF';						 // type of output 'PDF'|'HTML'
  protected $pdf = null;						   // pdf file class TCPDF
  protected $pageorient=null;

  /* ************************************************************************ */
  /**
   *
   */
  public function __construct() {
	global $controller, $vars, $output, $SHOW_HIGHLIGHT_IMAGES, $SHOW_EMPTY_BOXES, $DEBUG;

	$allset=true;
	foreach (array('pid','fonts','maxgen','pageorient','compress','showSiblings',
		   'showSpouses','boxStyle','connectLineWidth',
		   'SHOW_HIGHLIGHT_IMAGES','SHOW_EMPTY_BOXES') as $a) {
	  if(isset($vars[$a])) {
	if (isset($vars[$a]['id']) && $vars[$a]['id']) {
	  $$a=$vars[$a]['id'];
	} else {
	  $$a=false;
	}
	  } else {
	if (!isset($$a)) {
	  $allset=false;
	}
	  }
	}								   // foreach (array('pid','fonts','maxgen','pageorient', ...
	$DEBUG = isset($vars['DEBUG']) ? (int) $vars['DEBUG']['id']	: 0;
	if (!$allset) {
		$controller
			->setPageTitle($title)
			->pageHeader(WT_I18N::translate('Pedigree Chart').' - '.WT_I18N::translate('Single page'));
		echo "<h2>not all var set </h2>";
		if ($DEBUG) {
			echo "<pre>vars=\n";
			print_r($vars);
			echo "</pre>\n";
			echo "compress=". $compress . " showSiblings=". ($showSiblings ? "yes":"no") . "<br>\n";
		}
		exit;
	} else {							// if (!$allset) {
		unset ($allset);
	}								   // else if (!$allset) {
	if ($SHOW_EMPTY_BOXES){$compress='none';}
	$this->showSpouses=$showSpouses;
	$this->create($pid, $maxgen, $pageorient,$showSiblings, $compress, $fonts, $boxStyle, $connectLineWidth);
	return $this;
  }									 // function setup () {
  /* ************************************************************************ */
  /**
   *
   *
   */
  public function setup () {
	return new ReportPedigree ();
  }
  /* ************************************************************************ */
  /**
   *
   *
   */
  protected function create ($pid, $maxgen, $pageorient,$showSiblings, $compress, $fonts, $boxStyle, $connectLineWidth) {
	global $output;
	$this->boxStyle=$boxStyle;
	$this->compress=$compress;
	$this->connectLineWidth=$connectLineWidth;
	$this->setOrientation($pageorient);
	$this->showSiblings=$showSiblings;
	$this->fonts=$fonts;

	if ($this->getAllPersons($pid, $maxgen)) {
	  $this->initPDF($fonts);
	  $this->calcLeavePositions();
	  $this->calcnoLeavesPositions();
	  if ($compress=="full") {
		$this->compressPositions($output);
		// if the compression changes the display mode of siblings the compression is not total
		// the code will be executed again
		$this->compressPositions($output);
	  }								 // if ($compress) {
	  if ($this->showSpouses != 'none') {
		$this->getDescendants($pid, $output);
	  }								 // if ($this->showSpouses != 'none') {
	  $this->setPageSizeandTitle($output);
	  $this->displayPersons($output);
	  $this->displayEventInfo($output);
//	  $this->pageBorder($output);
	  if ($output=="PDF") {
		$this->closePdf();
	  }								 // if ($output=="PDF") {
	} else {							// if ($pedigree->getAllPersons ...
	  $controller
		->setPageTitle(WT_I18N::translate('Pedigree Chart').' - '.WT_I18N::translate('Single page'))
		->pageHeader();
	  echo 'internal ERROR pedigree_single no person';
	  exit;
	}								   // else if ($pedigree->getAllPersons ...
  }									 // function create ( ...
  /* ************************************************************************ */
  /**
   * Calculation of the position of children. Implements individum of direct 
   * ancestor and also of siblings
   *
   * global variables 
   * $SHOW_EMPTY_BOXES,
   *
   * @param int $famNr			 number of family
   * @param int $father			number of father
   * @param int $mother			number of mother
   * @param int $child=-1		  which child of family (-1 ancestor of first person)
   * @return $position or null if $position of father and mother are not set
   */
  protected function calcChildPos($famNr, $father, $mother, $child=-1) {
	global $SHOW_EMPTY_BOXES;
	
	if (isset($this->positions[$father]) && isset($this->positions[$mother])) {
	  if ($this->showSiblings && isset($this->children) && isset($this->children[$famNr]) && count($this->children[$famNr])>2) {
	$nrSibl=(count($this->children[$famNr])-1);
	$diffParents=$this->positions[$mother] - $this->positions[$father];
	if ($child == -1) {
	  $nr=$this->children[$famNr][$nrSibl];
	} else {						// if ($child == -1) {
	  $nr=$child;
	}							   // else if ($child == -1) {

	if ($diffParents > 1.01) {
	  foreach ($this->scaleSameGen as $type=>$size) {
		$space=$size;
		if (($diffParents - (1-$size)) > ($nrSibl-.5)*$size) {
		  $this->displaySiblings[$famNr]=$type;
		  $offset=($diffParents-$space*($nrSibl-1)-(1-$size))*0.5;
		  if ($nr == $this->children[$famNr][$nrSibl]) {
		$offset+=(1-$size)/2;
		  }						 // if ($nr == $this->children[$famNr][$nrSibl]) {
		  if ($nr > $this->children[$famNr][$nrSibl]) {
		$offset+=(1-$size);
		  }						 // if ($nr > $this->children[$famNr][$nrSibl]) {
		  return  $this->positions[$father] + $space*($nr) +$offset;
		}						   // if (($diffParents - (1-$size)) > ($nrSibl-1)*$size) {
	  }							 // foreach ($this->scaleSameGen as $type=>$size) {	  
	}							   // if ($diffParents > 1) {
	$this->displaySiblings[$famNr]=false;
	$space=($diffParents-2*$this->addSpaceSiblings)/($nrSibl+2);
	if ($space > $this->maxSpaceNoDisplay){
	  $space=$this->maxSpaceNoDisplay;
	}							   // if ($space > $this->maxSpaceNoDisplay){
	if ($nr==$this->children[$famNr][$nrSibl]) {
	  $add=$this->addSpaceSiblings;
	} elseif ($nr < $this->children[$famNr][$nrSibl]) { // if ($nr == ...
	  $add=0;//-$this->addSpaceSiblings;
	} else {						// elseif ($nr < ...
	  $add=2*$this->addSpaceSiblings;
	}							   // else elseif ($nr < ...
	return $this->positions[$father] + $space*$nr + ($diffParents-$space*($nrSibl-1)-2*$this->addSpaceSiblings)*0.5+$add;
	  } else {						  // if ($this->showSiblings && isset($this->children) && ...
	return ($this->positions[$father] + $this->positions[$mother])/2;
	  }								 // else if ($this->showSiblings && isset($this->children) && ...
	} elseif (isset($this->positions[$father])){// if (isset($this->positions[$father]) && ...
	  if ($this->compress=='none') {
	$g=$this->genShow-floor(log($famNr)/log(2))-2;
	return $this->positions[$father]+.5*(1<<$g);
	  }								 // if (
	  return $this->positions[$father];
	} else {							// elseif (isset($this->positions[$father]))
	  if ($this->compress=='none') {
	$g=$this->genShow-floor(log($famNr)/log(2))-2;
	return $this->positions[$mother]-.5*(1<<$g);
	  }								 // if ($
	  return $this->positions[$mother];
	}								   // else elseif (isset($this->positions[$mother]))
	return null;
  }									 // function calcChildPos(
  /* ************************************************************************ */
  /**
   * Calculate the positions of persons with no ancestors (leaves)
   * values stored in $this->leavePositions also the variables
   * $this->positions	   positions of the persons
   * $this->leavesSort	  array($index			  => ${number of person}) 
   * $this->leavesSortR	 array(${number of person} => $index) 
   *
   * @return nothing
   */
  protected function calcLeavePositions () {
	global $output, $DEBUG;
	if (!isset ($this->positions) || $this->positions==null) {
	  $offset=.5;
	  for ($i=(1<<($this->genShow))/2;$i< (1<<($this->genShow)); ++$i ) {
	if (isset($this->ancestors[$i]) && $this->ancestors[$i]!=null && !$this->isLeave($i)) {
	  $this->leavesSort[]=$i;
	}							 // if if (isset($this->ancestors[$i]) && ...
	  }							   // for ($i=(1<<($maxgen))/2;$i< (1<<($maxgen)); ++$i ) {
	  if (isset($this->leavesSort)) {
	if ($output=='HTML' && $DEBUG&4) {echo 'leaves='; print_r($this->leaves); echo "<br>\n"; }
	if ($output=='HTML' && $DEBUG&4) {echo 'unsorted leavesSort='; print_r($this->leavesSort); echo "<br>\n"; }

	sort($this->leavesSort);
	if ($output=='HTML' && $DEBUG&4) {echo 'leavesSort='; print_r($this->leavesSort); echo "<br>\n"; }
	for ($i=0; $i < count($this->leavesSort);++$i) {
	  $j=$this->leavesSort[$i];
	  while ($this->ancestors[$j]==null ) {$j=$j/2;}
	  $this->positions[$j]=$i+$offset;
	  $this->leavesSortR[$i]=$j;
	}							 // for ($i=0; $i < count($this->leavesSort);++$i) {
	if ($output=='HTML' && $DEBUG&4) {echo 'positions='; print_r($this->positions); echo "<br>\n"; }
	if ($output=='HTML' && $DEBUG&4) {echo 'leavesSortR='; print_r($this->leavesSortR); echo "<br>\n"; }
	  }							   // if (isset($this->leavesSort)) {
	  
	  
	  if ($this->compress != 'none') {
	$this->leavePositions=$this->positions;
	  } else {						  // if ($this->compress != 'none') {
	$dummy=array(); // XXXXX todo optimise
	$nrBoxes=(1<<($this->genShow));
	foreach($this->positions as $key=>$value) {
	  if ($key >= $nrBoxes/2) {
		$dummy[$key]=$key-$nrBoxes/2+$offset;
	  } else {
		$g=floor(log($key)/log(2))+1;
		$dummy[$key]=($key-(1<<($g))/2)*(1<<($this->genShow-$g)) // space between two IND in actual generation
		  +(1<<($this->genShow-$g))/2.0-0.5					  // position of first person in actual generation
		  +$offset;
	  }
	}
	$this->leavePositions=$this->positions;
	$this->leavePositions=$dummy;
	$this->positions=$dummy;
	if ($output=='HTML' && $DEBUG&4) {echo 'dummy='; print_r($dummy); echo "<br>\n"; }

	  }								 // else if ($this->compress != 'none') {
	} else {							// if (!isset ($this->positions) || $this->positions==null) {
	  // XXX TODO
	}								   // else if (!isset ($this->positions) || $this->positions==null) {
  }									 // function calcLeavePositions () {
  /* ************************************************************************ */
  /**
   * Calculate the positions of all persons
   * for these calculation the positions of persons with no ancestors should already set 
   *
   * @return nothing
   */
  protected function calcnoLeavesPositions () {
	for ($i=(1<<($this->genShow))/2 -1 ;$i>=0; --$i ) {
	  if ($this->isInPedigree($i, false) && !preg_match('/^REFTO_/', $this->ancestors[$i]) && !isset($this->positions[$i])) {
	$this->positions[$i]=$this->calcChildPos($i, 2*$i, 2*$i+1); 
	  }								 // if ($this->isInPedigree($i, false) && ...
	}								   // for ($i=(1<<($showGen))/2 -1 ;$i>=0; --$i ) {
  }									 // function function calcnoLeavesPositions () {
  /* ************************************************************************ */
  /**
   * Compress positions of ancestors
   * the variables $this->positions and $this->leavePositions must be set and
   * the smallest position must be the first in the array 
   *
   * @param string $output	type of output (in case of 'HTML' additional DEBUG output posible)
   * @return nothing
   */
  protected function compressPositions ($output) {
	global $DEBUG;
	$PosOffset=0;			   // offset to old $positions
	$MaxPosition=array();

	foreach ($this->leavePositions as $key=>$value) {
	  $aGen=floor(log($key)/log(2))+1;
	  if (!isset($lastKey)) { // $lastKey up to now not defined
	$lastKey=$key;   $lastGen=$aGen;
	$positions_compr[$key]=true;
	$MaxPosition[$aGen]=$value;
	for($i=$key/2,$g=$aGen-1; $i >1 && isset($this->positions[$i]) && 
		  $this->positions[$i]== $this->positions[$i*2];$i/=2, --$g) {
	  $MaxPosition[$g]=$value;
	}							   // for($i=$key/2,$g=$aGen-1; $i >1 && isset($this->positions[$i]) && ..
	continue;
	  }								 // if (!isset($lastKey)) {
	  if (abs($key - $lastKey) > $aGen){
	$h=0;
	if ($aGen < $lastGen) {
	  $h=$this->positions[$lastKey>>($lastGen-$aGen)]-$this->positions[$key]+ 1 +$this->spaceCompress -$PosOffset;
	  if (isset($this->displaySiblings[$key-1]) ) {
		$i=$key-1;
		$last_per=$this->calcChildPos($i, 2*$i, 2*$i+1, count($this->children[$i])-2);
		if ($this->displaySiblings[$i] == false) {
		  $h+=max($last_per-$this->positions[$i]-0.5 ,0);
		} else {					// if ($this->displaySiblings[$i] == false) {
		  if ($last_per != $this->positions[$i]) {
		$h+=$last_per-$this->positions[$i] - (1-$this->scaleSameGen[$this->displaySiblings[$i]])/2;
		  }						 // if ($last_per != $this->positions[$i]) {
		}						   // else if ($this->displaySiblings[$i] == false) {
	  }							 // if (isset($this->displaySiblings[$key-1]) ) {
	  $PosOffset+=$h;
	} else {						  // if ($aGen < $lastGen) {
	  // get the number ($i) and generation ($g) for which MaxPositions is allready set
	  for ($i=$key,$g=$aGen;$i>2 && !isset($MaxPosition[$g]); $i/=2,--$g) {
	  }
	  unset($diff);
	  $diff=array();
	  $loopControl=($this->positions[$i] <= $this->positions[floor($i/2)]);
	  $gStart=$g;
	  for (;$i>2 && $loopControl || ($g==$gStart && $this->positions[$i] -$MaxPosition[$g] > 1); $i/=2,--$g) {
		// this special loop control is nescesary so code is executed one time more
		$loopControl=($this->positions[$i] <= $this->positions[floor($i/2)]);

		if (isset($this->displaySiblings[$i])) {
		  $first_child_positions=$this->calcChildPos($i, 2*$i, 2*$i+1, 0);
		  if ($this->displaySiblings[$i] == false) {
		$first_child_positions=min($first_child_positions+.5, $this->calcChildPos($i, 2*$i, 2*$i+1));
		  }						 // if ($this->displaySiblings[$i] == false) {		  
		} else {					// if (isset($this->displaySiblings[$i])) {
		  $first_child_positions=$this->positions[$i];
		}						   // else if (isset($this->displaySiblings[$i])) {
		$diff[$i]=-($first_child_positions + $PosOffset -$MaxPosition[$g]-(1+$this->spaceCompress));

		if ($this->positions[$key]+$PosOffset+$diff[$i] <0.5) {  // all positions should be >0
		  $diff[$i]=-($this->positions[$key]+$PosOffset)+0.5;
		}						   // if ($positions[$key]+$PosOffset+$diff[$i] < 0) {
	  }							 // for (;$i>2 && $loopControl || ($g==$gStart &&

	  if (count($diff) && isset($MaxPosition[$aGen-1]) && $this->positions[$key]+$PosOffset+
		  max($diff)-$MaxPosition[$aGen-1] < 0.499 + $this->spaceCompress) {
		unset ($diff);
		$diff[0]=-($this->positions[$key]+$PosOffset-$MaxPosition[$aGen-1]-0.5-$this->spaceCompress);
	  }							 // if (count($diff) && isset($MaxPosition[$aGen-1]) && ...
	  if (count($diff)) {
		$PosOffset+=max($diff);
	  }							 // if (count($diff)) {
	}							   // else if ($aGen < $lastGen) {
	  }								 // if (abs($key - $lastKey) > $aGen){

	  if (isset($MaxPosition[$aGen])) {
	$MaxPosition[$aGen]=max($MaxPosition[$aGen],$value+$PosOffset);
	  } else {						  // if (isset($MaxPosition[$aGen])) {
	$MaxPosition[$aGen]= $value+$PosOffset;
	  }								 // else if (isset($MaxPosition[$aGen])) {

	  $this->positions[$key]+=$PosOffset;
	  $this->leavePositions[$key]+=$PosOffset;
	  $positions_compr[$key]=true;
	  for ($i=$key; $i>1 && ((($i %2) == 1 && isset($this->ancestors[$i-1])) // exists woman and husband 
				 ||(isset($this->families[$i/2]) && //family exists
				  !($this->families[$i/2]->getWife()->getXref() && $this->families[$i/2]->getHusband()->getXref())));
	   $i/=2) {
	if (!isset($positions_compr[$i/2])) {
	  if ($this->families[$i/2]->getWife()->getXref() && $this->families[$i/2]->getHusband()->getXref()) {
		$this->positions[$i/2]=$this->calcChildPos($i/2, $i-1, $i);
	  } else {					  // else elseif ($families[$i/2]->getHusband()->getXref()) {
		$this->positions[$i/2]=$this->positions[$i];
	  }							 // else if ($families[$i/2]->getWife()->getXref() && $families[$i/2]->getHusband()->getXref()) {
	  $positions_compr[$i/2]= true;//$this->positions[$i/2];
	}							   // if (!isset($positions_compr[$i/2])) {
	  }								 // for ($i=$key; $i > 1 && ...
	  for ($i=$aGen-1; $i > 0 && isset($positions_compr[$key>>($aGen-$i)]); --$i) {
	$p=$this->positions[$key>>($aGen-$i)];//+$PosOffset;
	$MaxPosition[$i]=isset($MaxPosition[$i])? max($MaxPosition[$i], $p) :$p ;
	  }								 // for ($i=$aGen-1; $i > 0; --$i) {  
	  $lastKey=$key;
	  $lastGen=$aGen;
	}								   // foreach ($this->leavePositions as $key=>$value) {
  }									 // function compressPositions ( ...
  /* ************************************************************************ */
  /**
   * show page borders 
   * @param string $output 
   */
  private function pageBorder ($output) {
	if ($output =='PDF') {
	  $size=$this->pdf->getPageDimensions();
	  $this->pdf->PolyLine(array($this->xBrim,			 $this->yBrim,
				 $size['wk']-$this->xBrim, $this->yBrim,
				 $size['wk']-$this->xBrim, $size['hk']-$this->yBrim,
				 $this->xBrim,			 $size['hk']-$this->yBrim,
				 $this->xBrim,			 $this->yBrim,
				 $this->xBrim,			 $this->yBrim+$this->headerHeight,
				 $size['wk']-$this->xBrim, $this->yBrim+$this->headerHeight
				 ),'D');
	  return;
	}								   // if ($output =='PDF') {
  }									 // function pageBorder ($output) {
  /* ************************************************************************ */
  /**
   * Connect two different persons in TCPDF file
   * connection style is stored in the array $this->connectStyle
   * the line thickness increases with decreasing generation if $this->connectLineWidth is > 0.0
   *
   * @param string $how	style of connection 'sibling'|'parents'|'nextGen'
   * @param float $x1	  first x-position
   * @param float $y1	  first y-position
   * @param float $x2	  second x-position
   * @param float $y2	  second y-position
   * @param int gen		generation default=0
   */
  protected function connectPersons ($how, $x1, $y1, $x2, $y2, $gen=0) {
	$circ=0.35;//maximal 0.5
	$w1=min($this->connectPos, $this->connectChildNoDisplay, $this->radius)*max($this->xSpace,$this->ySpace)*$this->connectLineWidth;
	if ($how=='sibling') {
	  $w1*=.5;
	} else {							// else if ($how=='sibling') {
	  $w1*=(1- 0.4*($gen/(max(1,$this->genShow-1)))); // line thickness depends on generation firstGen=100% lastGen=60%
	}								   // else if ($how=='sibling') {
	$w2=$w1*.5;

	$mirror=false;
	$rotate=false;
	if ($this->pageorient == 'portrait') {
	  $h=($y2-$y1);
	  $dG=($x2-$x1);
	} else {							// if ($this->pageorient == 'portrait') {
	  $rotate=true;
	  $h=($x2-$x1);
	  $dG=($y1-$y2);
	}								   // else if ($this->pageorient == 'portrait') {
	if ($how=='spouse') {
	  $dG=($this->connectPos)*(($this->pageorient=='portrait')?$this->xSpace:$this->ySpace);
	}								   // if ($how=='spouse') {
	$l=2*min($this->connectPos, $this->connectChildNoDisplay, $this->radius)*$dG;
	$d1=$this->connectPos*$dG-$l*0.5;
	$d2=$dG-($d1+$l);
	if ($h < 0) {
	  $mirror=true;
	  $h=-$h;
	}								   // if ($h < 0) {

	if ($how=='spouse') {
	  $w1=$w2;
	  $circ*=2.0;
	  $points=array(array(0,-$w1,					 $d2,-$w1,				   $d2,-$w1) // Line 1
			,array($d2+$circ*($l+$w2),-$w1,	$d2+$l+$w2,$l-$w1+$w2-$circ*($l+$w2),	  $d2+$l+$w2,$l-$w1+$w2) //C2
			,array($d2+$l+$w2,$l-$w1+$w2,	  $d2+$l+$w2,$h-$l+$w1-$w2,   $d2+$l+$w2,$h-$l+$w1-$w2) //L3
			,array($d2+$l+$w2,$h-$l+$w1-$w2+$circ*($l+$w2),  $d2+$circ*($l+$w2),$h+$w1,   $d2,$h+$w1)  //C4
			,array($d2,$h+$w1,				 0,$h+$w1,				   0,$h+$w1)	//L5
			,array(0,$h+$w1,				   0,$h-$w1,				   0,$h-$w1)	//L6
			,array(0,$h-$w1,				   $d2,$h-$w1,				 $d2,$h-$w1)  //L7
			,array($d2+$circ*($l-$w2),$h-$w1,  $d2+$l-$w2,$h-$l-$w1+$w2+$circ*($l-$w2),	   $d2+$l-$w2,$h-$l-$w1+$w2 )  //C8
			,array($d2+$l-$w2,$h-$l-$w2,	   $d2+$l-$w2,$l-$w2+$w1,	  $d2+$l-$w2,$l-$w2+$w1 )  //L9
			,array($d2+$l-$w2,$l-$w2+$w1-$circ*($l-$w2),	  $d2+$circ*($l-$w2),$w1,					$d2,$w1)  //C10
			,array($d2,$w1,					0,$w1,					  0,$w1)  //L11
			,array(0,$w1,					  0,0,						0,0)  //L12
			);
	} else {							// if ($how=='spouse') {
	  if (abs($h) >= $l+$w1-$w2) {
	$points=
	  array(array( 0,-$w1,								 $d1,-$w1,										 $d1,-$w1) // Line 1
		  ,array($d1+$circ*($w2+$w2+$l),-$w1,			$d1+($w2+$w2+$l)*0.5,(-$w2-$w2+$l)*(0.5-$circ),   $d1+($w2+$w2+$l)*0.5,(-$w2-$w2+$l)*0.5)	 //C2 
		  ,array($d1+($w2+$w2+$l)*0.5,(-$w2-$w2+$l)*0.5, $d1+($w2+$w2+$l)*0.5,(-$w2-$w2+$l)*0.5, 	  $d1+($w2+$w2+$l)*0.5,$h-$w2-($l-$w2-$w2)*0.5)//L3 
		  ,array($d1+($w2+$w2+$l)*0.5,$h-$w2-($l-$w2-$w2)*(0.5-$circ),   $d1+$l+$circ*($w2+$w2-$l),$h-$w2, $d1+$l,$h-$w2) //C4
		  ,array($d1+$l,$h-$w2,						  $d1+$l,$h-$w2,									$d1+$l+$d2,$h-$w2) //line 5
		  ,array($d1+$l+$d2,$h-$w2,					  $d1+$l+$d2,$h-$w2,								$d1+$l+$d2,$h+$w2) //line 6
		  ,array($d1+$l,$h+$w2,						  $d1+$l,$h+$w2,									$d1+$l,$h+$w2)  //line 7
		  ,array($d1+$l-$circ*($l+$w1+$w2),$h+$w2,	   $d1+($l-$w2-$w2)*.5,$h+$w2-($l+$w2+$w2)*(0.5-$circ), $d1+($l-$w2-$w2)*.5,$h+$w2-($l+$w2+$w2)*.5)// C8
		  ,array($d1+($l-$w2-$w2)*.5,($l-$w2-$w2)*.5,	$d1+($l-$w2-$w2)*.5,$w1-$w2+($l-$w2-$w2)*.5,	  $d1+($l-$w2-$w2)*.5,$w1-$w2-$w2+($l)*.5) //L9
		  ,array($d1+($l-$w2-$w2)*.5,$w1-(4*$w2-$l)*(0.5-$circ),  $d1+$circ*($l-$w2-$w2),$w1,			  $d1+0,$w1)  //c 10
		  ,array($d1,$w1,								$d1+0,$w1,										0,$w1)	  //Line 11
		  ,array(0,$w1,								  0,$w1,											0,-$w1)	  //line 12			 
		  );
	  } else {							// if (abs($h) >= $l+$w1-$w2) {
	if ($h == 0) {
	  $w2=$w1;
	}								 // if ($h == 0) {
	$a1=0;	  $b1=-$w1;
	$a2=$d1;	$b2=-$w2+$h;
	$a3=$dG-$d2;$b3=$w2+$h;
	$a4=$dG;	$b4=$w1;
	$points=array(array($a1,$b1, $a2,$b1, $a2,$b1) // L
			  ,array($a2+$circ*($a3),$b1, .5*($a3+$a2),.5*($b2+$b1), .5*($a3+$a2),.5*($b2+$b1)) // C
			  ,array(.5*($a3+$a2),.5*($b2+$b1), $a3+$circ*($a2-$a3),$b2, $a3,$b2) // C
			  ,array($a3,$b2, $a4,$b2, $a4,$b2) // L
			  ,array($a4,$b2, $a4,$b3, $a4,$b3) // L
			  ,array($a4,$b3, $a3,$b3, $a3,$b3) // L
			  ,array($a3+$circ*($a2-$a3),$b3, 0.5*($a2+$a3),0.5*($b4+$b3), 0.5*($a2+$a3),0.5*($b4+$b3)) // C
			  ,array(0.5*($a2+$a3),0.5*($b4+$b3), $a2+$circ*($a3-$a2),$b4, $a2,$b4) // C
			  ,array($a2,$b4, $a1,$b4, $a1,$b4) // L
			  ,array($a1,$b4, $a1,$b1, $a1,$b1) // L
			  );
	  }								 // else if (abs($h) >= $l+$w1-$w2) {
	}								   // else if ($how=='spouse') {
	$this->pdf->StartTransform();
	$this->pdf->Translate($x1,$y1);
	if ($how=='spouse') {
	  $this->pageorient == 'portrait'? $this->pdf->MirrorH(0):$this->pdf->MirrorV(0);
	}								   // if ($how=='spouse') {
	if ($mirror) {
	  if ($this->pageorient == 'portrait') {
	$this->pdf->MirrorV(0);
	  } else {						  // if ($this->pageorient == 'portrait') {
	$this->pdf->MirrorH(0);
	  }								 // else if ($this->pageorient == 'portrait') {
	}								   // if ($mirror){
	if ($rotate) {
	  $this->pdf->Rotate(90,0,0);
	}								   // if ($rotate){
	$this->pdf->Polycurve(0,-$w1, $points,'DF', $this->connectStyle[$how], array(255));
	$this->pdf->StopTransform();
  }									 // function connectPersons ($how, $x1, $y1, $x2, $y2) {
  /* ************************************************************************ */
  /**
   * Display info about used images of events
   *
   * @param string $output	type of output
   * @return nothing
   */
  private function displayEventInfo ($output) {
	if ($output == 'PDF') {
	  $html=$this->getEventImg('all', null);
	  /* get number of lines the tcpdf function getNumLines works not for html text
	   * here we can count the <br> tags
	   */
	  $h=count(preg_split('<br>', $html))-1;
	  if ($this->pageorient == 'portrait') {
	$y_pos=$this->yBrim+$this->headerHeight;
	  } else {						  // if ($this->pageorient == 'portrait') {
	$y_pos=$this->yBrim+$this->yOffset+$this->pageHeightAdd+
	  ($this->yWidth+$this->ySpace)*($this->genShow)-2.2*$h;  // XXX Why 2.2 times $h
	  }								 // else if ($this->pageorient == 'portrait') {
	  $x_pos=$this->genShow>2 ? $this->xBrim : $this->xBrim-12;
	  $this->pdf->MultiCell ($this->xWidth, $h, $html, 0, 'L', 0, 1, $x_pos, $y_pos, true,
				 0, true, false, $h);
	} else {							// if ($output == 'PDF') {
	  echo $this->getEventImg('all', null) .'<br>';
	  echo timestamp_to_gedcom_date(mktime(0,0,0,date("m"),date("d"),date("Y")))->Display(). '<br></center>';
	}								   // else if ($output == 'PDF') {
  }									 // function displayEventInfo () {
  /* ************************************************************************ */
  /**
   * Display all Persons
   *
   *
   * @param string $output	type of output
   * @return nothing
   */
  protected function displayPersons($output) {
	global $SHOW_EMPTY_BOXES;
	if ($output=='PDF' && count($this->descendants) > 0) {
	  foreach (array_reverse($this->descendants, false) as $key=>$fam) {
	if ($this->pageorient=='portrait'?$this->spouses[$key]['PosOffset'][1] < 0:
		$this->spouses[$key]['PosOffset'][0] < 0) {
	  $rearange=true;
	} else {						// if ($this->pageorient=='portrait'? ...
	  $rearange=false;
	}							   // else if ($this->pageorient=='portrait'? ...
	$nrChilds=count($fam['Cids']);
	if ($this->pageorient=='portrait') {
	  $MainxPos	  = $this->xBrim+$this->xOffset;
	  $MainyPos	  = $this->yBrim+$this->yOffset+$this->positions[1]*($this->ySpace+$this->yWidth);
	  $SpousexPos	= $MainxPos+$this->spouses[$key]['PosOffset'][0];
	  $SpouseyPos	= $MainyPos+$this->spouses[$key]['PosOffset'][1];	
	  $SpousexOffset = -$this->xWidth;
	  $SpouseyOffset = 0;
	  
	  $xspa = 0;
	  $yspa = ($rearange ? -1:1)*($this->ySpace+$this->yWidth*$this->scaleSameGen[$this->scaleDescen]);
	  $descendantXOffset= -$this->xSpace*(1-$this->connectPos);
	  $descendantYOffset= $this->spouses[$key]['OffsetChildren'][1]+
		  0.5*($SpouseyPos-$MainyPos-$this->spouses[$key]['OffsetChildren'][1]-($nrChilds-1)*$yspa);
	  $lineXLenght	  = $this->xSpace*($this->connectPos);
	  $lineYLength	  = 0;
	} else {						// if ($this->pageorient=='portrait') {
	  $MainxPos	  = $this->xBrim+$this->xOffset+$this->positions[1]*($this->xSpace+$this->xWidth);
	  $MainyPos	  = $this->yBrim+$this->yOffset+($this->genShow)   *($this->ySpace+$this->yWidth);
	  $SpousexPos	= $MainxPos+$this->spouses[$key]['PosOffset'][0];
	  $SpouseyPos	= $MainyPos+$this->spouses[$key]['PosOffset'][1];
	  $SpousexOffset = 0;
	  $SpouseyOffset = -$this->scaleNextGen*$this->yWidth;

	  $xspa = ($rearange ? -1:1)*($this->xSpace+$this->xWidth*$this->scaleSameGen[$this->scaleDescen]);
	  $yspa = 0;
	  $descendantYOffset= $this->ySpace*(1-$this->connectPos);
	  $descendantXOffset= $this->spouses[$key]['OffsetChildren'][0]+
		  0.5*($SpousexPos-$MainxPos-$this->spouses[$key]['OffsetChildren'][0]-($nrChilds-1)*$xspa);
	  $lineXLenght	  = 0;
	  $lineYLength	  = $this->ySpace*($this->connectPos);
	}							   // else if ($this->pageorient=='portrait') {
	$xWidth=$this->xWidth;
	if ($this->showSpouses=='spouse-family') {
	  for ($i=0; $i < /*1; ++$i) {//*/ $nrChilds; ++$i) {
		$p=$rearange ? $nrChilds-$i-1 :$i;
		$this->connectPersons('parents', $descendantXOffset+$MainxPos+($xspa)*$p-$lineXLenght, $MainyPos+($yspa)*$p+$descendantYOffset+$lineYLength,
				  $descendantXOffset+$MainxPos+($xspa)*$p, $MainyPos+($yspa)*$p+$descendantYOffset, 0);
		if (isset($this->descendants[$key]['Cids'][$i])) {
			$this->pdfPerson('descandes_'.$this->scaleDescen,$descendantXOffset+$MainxPos+($xspa)*$p-$lineXLenght+$SpousexOffset,
				 $MainyPos+($yspa)*$p+$descendantYOffset+$lineYLength,
				 $this->descendants[$key]['Cids'][$i]);
		}
	  }							 // for ($i=0; $i < $nrChilds; ++$i) {
	}							   // if ($this->showSpouses=='spouse-famlily') {
	$this->connectPersons('spouse', $MainxPos, $MainyPos, $SpousexPos, $SpouseyPos, 0);
	$this->pdfPerson('spouse_full',  $SpousexPos, $SpouseyPos+$SpouseyOffset, $this->spouses[$key]['Id'], '', $key);
	  }								 // foreach (array_reverse($this->descendants, false) as $key=>$fam) {
	}								   // if ($output=='PDF' && count($this->descendants) > 0) {

	for ($i=1;$i< (1<<($this->genShow)); ++$i ) {
	  if (isset($this->ancestors[$i]) && $this->ancestors[$i]!=null ) {
	$aGen=floor(log($i)/log(2));
	if ($output == 'PDF') {
	  if ($this->pageorient == 'portrait') {
		$a=$this->xBrim+$this->xOffset+ ($this->xWidth+$this->xSpace) * $aGen;
		$b=$this->yBrim+$this->yOffset+ ($this->yWidth+$this->ySpace) * $this->positions[$i];
	  } else {				// if ($this->pageorient == 'portrait') {
		$a=$this->xBrim+$this->xOffset+ ($this->xWidth+$this->xSpace) * $this->positions[$i];
		$b=$this->yBrim+$this->yOffset+ ($this->yWidth+$this->ySpace) * ($this->genShow-$aGen-1) +$this->ySpace;
	  }							 // else if ($this->pageorient == 'portrait') {
	  if ($this->showSiblings && isset($this->children[$i]) && count($this->children[$i]) >2 ) {
		$n=(count($this->children[$i])-1);
		if (isset($this->positions[$i*2]) && isset($this->positions[$i*2+1])) {
		  for ($j=0; $j < $n; ++$j) {
		if ($j != $this->children[$i][$n]) {
		  if ($this->pageorient == 'portrait') {
			$pX=$a;
			$pY=$this->calcChildPos($i, 2*$i, 2*$i+1, $j) * ($this->yWidth+$this->ySpace) + $this->yBrim+$this->yOffset;
			$lY=$pY;
			$lX2=$a+$this->xWidth+$this->xSpace*$this->connectPos;
			$lY2=$pY;
		  } else {			  // if ($this->pageorient == 'portrait') {
			$pX=$this->calcChildPos($i, 2*$i, 2*$i+1, $j) * ($this->xWidth+$this->xSpace) + $this->xBrim +$this->xOffset;
			$pY=$b-($this->yWidth*(1-$this->scaleNextGen));
			$lX=$pX;
			$lX2=$pX;
			$lY2=$b-$this->ySpace*$this->connectPos;
		  }					 // else if ($this->pageorient == 'portrait') {
		  $dispaySibl=false;
		  if (isset($this->displaySiblings[$i]) && $this->displaySiblings[$i] !=false) {
			$dispaySibl=true;
			$boxSize='child_'.$this->displaySiblings[$i];
			if ($this->isInPedigree($this->children[$i][$j], true)) {
			  $boxSize.='_ref';
			}				   // if (($this->isInPedigree($this->children[$i][$j], true)) {
			if ($this->pageorient == 'portrait') {
			  $lX=$a+$this->xWidth;
			} else {			// if ($this->pageorient == 'portrait') {
			  $lY=$b;
			}				   // else if ($this->pageorient == 'portrait') {
		  } else {			  // if (isset($this->displaySiblings[$i]) && $this->displaySiblings[$i] !=false) {
			if ($this->pageorient == 'portrait') {
			  $lX=$a+$this->xWidth+$this->xSpace*$this->connectChildNoDisplay;
			} else {			// if ($this->pageorient == 'portrait') {
			  $lY=$b-$this->ySpace*$this->connectChildNoDisplay;
			}				   // else if ($this->pageorient == 'portrait') {
		  }					 // else if (isset($this->displaySiblings[$i]) && $this->displaySiblings[$i] !=false) {
		  $this->connectPersons('sibling', $lX, $lY, $lX2, $lY2, $aGen);
		  if ($dispaySibl) {
			$this->pdfPerson($boxSize, $pX, $pY, $this->children[$i][$j], sprintf ("%d%c. ", $i, $j+97));
		  }					 // if ($dispaySibl) {
		}					   // if ($j != $this->children[$i][$n]) {
		  }						 // for ($j=0; $j < $n; ++$j) {
		}						   // if (isset(positions[$i*2] && ...
	  }							 // if ($this->showSiblings && isset($this->children[$i]) && count($this->children[$i]) > 2 ) {
	  if ($this->pageorient == 'portrait') {
		if ($aGen < $this->genShow ) {
		  foreach (array($i*2, $i*2+1) as $j) {
		if(isset($this->positions[$j])) {
		  $this->connectPersons('parents', $a+$this->xWidth, $b, $a+$this->xWidth+$this->xSpace,
					   $this->yBrim+$this->yOffset+$this->positions[$j]*($this->yWidth+$this->ySpace),$aGen);
		}					   // if(isset($this->positions[$i*2])) {
		  }						 // foreach (array($i*2, $i*2+1) as $j) {
		}						   // if (floor(log($i)/log(2))< $maxgen) {
		if ($aGen == $this->genShow-1 && !$this->isLeave($i)) {
		  $this->connectPersons('nextGen',$a+$this->xWidth, $b, $a+$this->xWidth+$this->xSpace*$this->connectPos, $b, $aGen);
		}						   // if ($aGen == $maxgen-1 && ...
		if (preg_match('/^REFTO_/',$this->ancestors[$i]) &&
		isset($this->families[$this->GedcomRefToNumber($this->ancestors[$i])])) {
		  if ($this->TwoParents($this->GedcomRefToNumber($this->ancestors[$i]))) {
		$this->connectPersons('parents',$a+$this->xWidth*($this->scaleNextGen), $b,
					 $a+$this->xWidth+$this->xSpace*$this->connectChildNoDisplay, $b-$this->yWidth/3.,$aGen);
		$this->connectPersons('parents',$a+$this->xWidth*($this->scaleNextGen), $b,
					 $a+$this->xWidth+$this->xSpace*$this->connectChildNoDisplay, $b+$this->yWidth/3., $aGen);
		  } else {				  // if ($this->TwoParents($this->GedcomRefToNumber($this->ancestors[$i]))) {
		$this->connectPersons('parents',$a+$this->xWidth*($this->scaleNextGen), $b,
					 $a+$this->xWidth+$this->xSpace*$this->connectChildNoDisplay, $b, $aGen);
		  }						 // else if ($this->TwoParents($this->GedcomRefToNumber($this->ancestors[$i]))) {
		}						   // if (preg_match('/^REFTO_/',$this->ancestors[$i]) && ...
	  } else {					  // if ($this->pageorient == 'portrait') {
		if ($aGen< $this->genShow) {
		  foreach (array($i*2, $i*2+1) as $j) {
		if(isset($this->positions[$j])) {
		  $this->connectPersons('parents', $a, $b, $this->xBrim+$this->xOffset+($this->xWidth+$this->xSpace)*$this->positions[$j], $b-$this->ySpace, $aGen);
		}					   // if(isset($this->positions[$i*2])) {
		  }						 // foreach (array($i*2, $i*2+1) as $j) {
		}						   // if ($aGen< $this->genShow) {
		if ($aGen == $this->genShow-1 && !$this->isLeave($i)) { 
		  $this->connectPersons('nextGen',$a, $b, $a, $b-$this->ySpace*$this->connectPos, $aGen);
		}						   // if ($aGen == $this->genShow-1 && !$this->isLeave($i)) {
		if (preg_match('/^REFTO_/',$this->ancestors[$i]) &&
		isset($this->families[$this->GedcomRefToNumber($this->ancestors[$i])])) {
		  if ($this->TwoParents($this->GedcomRefToNumber($this->ancestors[$i]))) {
		$this->connectPersons('parents',$a, $b+($this->yWidth*(1-$this->scaleNextGen)),
					 $a-$this->xWidth/3., $b-$this->ySpace*$this->connectChildNoDisplay, $aGen);
		$this->connectPersons('parents',$a, $b+($this->yWidth*(1-$this->scaleNextGen)),
					 $a+$this->xWidth/3., $b-$this->ySpace*$this->connectChildNoDisplay, $aGen);
		  } else {				  // if ($this->TwoParents($this->GedcomRefToNumber($this->ancestors[$i]))) {
		$this->connectPersons('parents',$a, $b+($this->yWidth*(1-$this->scaleNextGen)),
					 $a, $b-$this->ySpace*$this->connectChildNoDisplay, $aGen);
		  }						 // else if ($this->TwoParents($this->GedcomRefToNumber($this->ancestors[$i]))) {
		}						   // if (preg_match('/^REFTO_/',$this->ancestors[$i]) &&
	  }							 // else if ($this->pageorient == 'portrait') {
	  if ($SHOW_EMPTY_BOXES && $aGen < $this->genShow-1 && ! $this->TwoParents($i)) {
		if (!preg_match('/^REFTO_/',$this->ancestors[$i])) {
		  $fullDist=(1<<(($this->genShow-$aGen-1)))/2.0*
		(($this->pageorient=='portrait')?($this->yWidth+$this->ySpace):$this->xWidth+$this->xSpace);
		  for ($g=$this->genShow-2; $g >= $aGen; --$g) {   // not set generation start with largest because of overlap
		$firstAnc=isset($this->families[floor($i)])&&$this->families[floor($i)]->getHusband()->getXref() ? (1<<($g-$aGen+1))/2 : 0;
		$lastAnc =isset($this->families[floor($i)])&&$this->families[floor($i)]->getWife()->getXref() ? (1<<($g-$aGen+1))/2 : (1<<($g-$aGen+1));
		for ($n=$firstAnc;$n < $lastAnc; ++$n) {	  // individuals per generation
		  if ($this->pageorient=='portrait') {		// order of condition and loops optimised for reading not for speed
			$perDist=(1<<(($this->genShow-$g-1)))*($this->yWidth+$this->ySpace)/2.0;
			$xoff=($this->xWidth+$this->xSpace)*($g-$aGen+1);
			$yoff=-$this->yWidth/2.-$fullDist+($n+.5)*$perDist;
			$xoffL=$xoff;
			$yoffL=-$fullDist+2*floor($n/2.)*$perDist+$perDist;
			$xoffLen=$this->xSpace;
			$yoffLen=$perDist/2. * ($n%2? -1:1);
		  } else {			  // if ($this->pageorient=='portrait') {
			$perDist=(1<<(($this->genShow-$g-1)))*($this->xWidth+$this->xSpace)/2.0;
			$xoff=-$this->xWidth/2.-$fullDist+($n+.5)*$perDist;
			$yoff=-($this->yWidth+$this->ySpace)*($g-$aGen+1);
			$xoffL=-$fullDist+2*floor($n/2.)*$perDist+$perDist + $perDist*($n%2? 0.5:-0.5);
			$yoffL=$yoff+($this->yWidth+$this->ySpace);
			$xoffLen=$perDist/2. * ($n%2? 1:-1);
			$yoffLen=$this->ySpace;
		  }					 // else if ($this->pageorient=='portrait') {
		  $this->connectPersons('parents',$a+$xoffL-$xoffLen, $b+$yoffL, $a+$xoffL, $b+$yoffL-$yoffLen, $g);
		  $this->personBorder('full', $a+$xoff , $b+$yoff, 'E', $i);
		}					   // for ($n=$firstAnc;$n < $lastAnc; ++$n) {
		  }						 // for ($g=$aGen; $g < $this->genShow-1; ++$g) {
		} else {					// if (!preg_match('/^REFTO_/',$this->ancestors[$i])) {
		  // XXX todo	how to handle this case ???
		}						   // else if (!preg_match('/^REFTO_/',$this->ancestors[$i])) {
	  }							 // if ($SHOW_EMPTY_BOXES && $aGen < $this->genShow-1 && ...
	  if (preg_match('/^REFTO_/', $this->ancestors[$i])) {
		$ref	 = substr($this->ancestors[$i],6);
		$boxsize = 'full_ref';
	  } else {					  // if (preg_match('/^REFTO_/', $this->ancestors[$i])) {
		$ref	 = $this->ancestors[$i];
		$boxsize = 'full';
	  }							 // else if (preg_match('/^REFTO_/', $this->ancestors[$i])) {
	  $this->pdfPerson($boxsize, $a, $b, $ref, $i . ". ");
	}							   // if ($output == 'PDF') {

	if ($output=='HTML') {
	  if (!preg_match('/^REFTO_/', $this->ancestors[$i])) {
		$id=$this->ancestors[$i];
		$how='full';
	  } else {					  // if (!preg_match('/^REFTO_/', $this->ancestors[$i])) {
		$id=substr($this->ancestors[$i],6);
		$how=false;
	  }							 // else if (!preg_match('/^REFTO_/', $this->ancestors[$i])) {
	  $this->printPersonHtml($how, $id, $i, '');
	  if ($this->showSiblings && isset($this->children[$i]) && count($this->children[$i])>2) {
		$n=(count($this->children[$i])-1);
		for ($j=0; $j < $n; ++$j) {
		  if ($j != $this->children[$i][$n]) {
		if ($this->isInPedigree($this->children[$i][$j], true)) {
		  $how="double";
		} else {				// if ($this->isInPedigree($this->children[$i][$j], true)) {
		  $how="full";
		}					   // else if ($this->isInPedigree($this->children[$i][$j], true)) {
		$this->printPersonHtml($how, $this->children[$i][$j] ,$i,sprintf('%c',$j+97));
		  }						 // if ($j != $this->children[$i][$n]) {
		}						   // for ($j=0; $j < $n; ++$j) {
	  }							 // if ($this->showSiblings && isset($this->children[$i]) && count($this->children[$i])>2) {
	}							   // if ($output=='HTML') {
	  }								 // if (isset($this->ancestors[$i]) && $this->ancestors[$i]!=null ) {
	}								   // for ($i=1;$i< (1<<($maxgen)); ++$i ) {

	if ($output=='HTML') {
	  echo '</table><br>';
	}								   // if ($output=='HTML') {
  }									 // function displayPersons() {
  /* ************************************************************************ */
  /**
   * get the persons from the database
   *
   * @param string $pid			  ID of first person
   * @param int $maxgen			  number of generations
   * @return true of false
   */
  protected function getAllPersons ($pid, $maxgen) {
	if ($this->getFirstPerson($pid) != null) {
	  $this->getPersons($maxgen);
	  return true;
	} else {						   // if ($this->getFirstPerson($pid) != null) {
	  return false;
	}								  // else if ($this->getFirstPerson($pid) != null) {
  }
  /* ************************************************************************ */
  /**
   * Create HTML output for Image of an Event
   *
   * global variables:
   *  $FontSize, $EventsShow
   *
   * @parm $event		which event one of birt|deat (not case sensitive) 
   * @parm $person=null  which person
   * @return string	  Html code of image or empty string
   */
  private function getEventImg($event, $person=null) {

	if ($person != null) {
	  if (strtolower($event) == 'birt') {
	if ($person->getAllEventDates('CHR') ||
		$person->getAllEventPlaces('CHR'))  { $event='chr'; }
	if ($person->getAllEventDates('BIRT') ||
		$person->getAllEventPlaces('BIRT')) { $event='birt'; }
	elseif ($event != 'chr')				{ $event=''; }
	  } elseif (strtolower($event) == 'deat') {
	if ($person->getAllEventDates('BURI') ||
		$person->getAllEventPlaces('BURI')) { $event='buri'; }
	if ($person->getAllEventDates('DEAT') ||
		$person->getAllEventPlaces('DEAT')) { $event='deat'; }
	  }								 // elseif (strtolower($event) == 'deat') {
	} else {							// if ($person != null) {
	}								   // else if ($person != null) {
	$pedigree_module=new pedigree_report_WT_Module();
	$imgs =array(0=>WT_MODULES_DIR.$pedigree_module->getName().'/images/birth.png',
		 1=>WT_MODULES_DIR.$pedigree_module->getName().'/images/chris.png',
		 2=>WT_MODULES_DIR.$pedigree_module->getName().'/images/marr.png',
		 3=>WT_MODULES_DIR.$pedigree_module->getName().'/images/death.png',
		 4=>WT_MODULES_DIR.$pedigree_module->getName().'/images/buri.png' );
	$names=array(0=>WT_Gedcom_Tag::getLabel('BIRT'),
		 1=>WT_Gedcom_Tag::getLabel('CHR'),
		 2=>WT_Gedcom_Tag::getLabel('MARR'),
		 3=>WT_Gedcom_Tag::getLabel('DEAT'),
		 4=>WT_Gedcom_Tag::getLabel('BURI'));
	if (strtolower($event) == 'birt') {$i=0;}
	if (strtolower($event) == 'chr')  {$i=1;}
	if (strtolower($event) == 'marr') {$i=2;}
	if (strtolower($event) == 'deat') {$i=3;}
	if (strtolower($event) == 'buri') {$i=4;}
	if (isset($i)) {
	  $this->EventsShow[$i]=true;	 // which images are used
	  $imgSize=getimagesize($imgs[$i]);
	  return '<img src="'.$imgs[$i].'" height="' .(0.8*$this->FontSize) . '" width="'.
	($this->FontSize*.8)*$imgSize[0]/$imgSize[1].'" >&nbsp;' ;
	}								   // if (isset($i)) {
	if (strtolower($event) == 'all')  {
	  $str='<b></b>';//='<table>';
	  for($i=0;$i < count($imgs);++$i) {
	if(isset($this->EventsShow[$i])) {  // only print used images
	  $imgSize=getimagesize($imgs[$i]);
	  $str.='<img alt="birth" src="'.$imgs[$i].'" height="' . (0.8*$this->FontSize) . '" width="'.
		($this->FontSize*.8)*$imgSize[0]/$imgSize[1]. '" > &nbsp; ' . $names[$i] .'<br>';
	}							   // if(isset($this->EventsShow[$i])) {
	  }								 // for($i=0;$i < count($imgs);++$i)
	  return $str;
	}								   // if (strtolower($event) == 'all')  {
	return '';
  }									 // function getEventIMG($event, ...
  /* ************************************************************************ */
  /**
   * get the first person
   * 
   * set the variables
   * $this->ancestors	   array ()
   * $this->ancestorsRev	array ()
   * $this->allIndividuals  array ()
   *
   * @param string $pid			  ID of first person
   * @return WT_Person::getInstance($pid)
   */
  protected function getFirstPerson($pid) {
	$this->allIndividuals[$pid]=WT_Person::getInstance($pid);
	if ($this->allIndividuals[$pid]) {
	  $this->ancestors[1]=$pid;
	  $this->ancestorsRev[$pid]=1;
	  return $this->allIndividuals[$pid];
	}								   // if ($this->individuals[$pid]) {
	return null;
  }									 // function getFirstPersons($pid) {
  /* ************************************************************************ */
  /**
   * get descendants of a person and also calculates positions of spouses
   * 
   * set the variables
   *
   * @param string $pid			  ID of person
   * @param string $output
   * @param int	$generation=1	number of generation (not jet implemented)
   *
   * @return 
   */
  function getDescendants ($pid, $output, $generations=1) {
	$ownFams=$this->allIndividuals[$pid]->getSpouseFamilies();
	$showDescendants=false;

	$l_spouces=array(); // array(int => gedcom ID)
	if ($ownFams) {
	  $rearange=false;
	  if ($output=='PDF' && count($ownFams) > 0) {
	if (count($ownFams)>=2) {
	  $rearange=true;
	}							   // if (count($ownFams)==2) {
	  }								 // if ($output=='PDF' && count($ownFams) > 0) {
	  $sex=$this->allIndividuals[$pid]->getSex();
	  $famNr=1;
	  $this->positions[0]=0;
	  foreach ($ownFams as $FId => $item) {
	$children_array=array();
	foreach ($item->getChildren() as $child) {
		$children_array[]=$child->getXref();
	}
	$this->descendants[$FId]=array('Fid'=>$FId, 'Cids'=>$children_array, 'Fam'=>$item);
	$Sid=($sex=='M') ?$item->getWife()->getXref() : $item->getHusband()->getXref();
	if (!isset($this->allIndividuals[$Sid])) {
	  $this->allIndividuals[$Sid]= ($sex=='M') ? $item->getWife() : $item->getHusband();
	}							   // if (!isset($this->allIndividuals[$Sid])) {
	$l_families[$famNr]=$FId;
	$this->spouses[$FId]=array('Person'=>$this->allIndividuals[$Sid], 'Id'	=>$Sid);
	if ($this->showSpouses != 'none') {
	  if ($rearange) {
		if ($famNr==1) {
		  if ($this->pageorient=='portrait') {
		$factor=$this->ySpace/$this->yWidth;
		  } else {				  // if ($this->pageorient=='portrait') {
		$factor=$this->xSpace/$this->xWidth;
		  }						 // else if ($this->pageorient=='portrait') {
		  $nr=-(1-$this->scaleNextGen+$factor)/($this->scaleNextGen) - 1 ;
		} else {					// if ($famNr==1) {
		  $nr=$famNr-1;
		}						   // else if ($famNr==1) {
	  } else {					  // if ($rearange) {
		$nr=$famNr;
	  }							 // else if ($rearange) {
	  $siblingsSpace=0;
	  if (isset($this->children[1]) && count($this->children[1])>1) {
		if ($nr < 0) {
		  $siblingsSpace=$this->calcChildPos(1,2,3,0)-$this->calcChildPos(1,2,3);
		} else {					// if ($nr < 0) {
		  $siblingsSpace=$this->calcChildPos(1,2,3,count($this->children[1])-2)-$this->calcChildPos(1,2,3);
		}						   // else if ($nr < 0) {
	  }							 // if (isset($this->children[1]) && count($this->children[1])>1) {
	  $nrChilds=count($this->descendants[$FId]['Cids']);
	  if ($this->pageorient=='portrait') {
		$xpos = 0;
		if ($famNr<=2) {
		  $ypos = $nr* ($this->yWidth+$this->ySpace) - ($rearange&&$famNr==1? ($nr-1)*($this->ySpace):0);
		} else {					// if ($famNr<=2) {
		  $ypos=$this->spouses[$l_families[$famNr-1]]['PosOffset'][1]+($this->ySpace+$this->yWidth);
		}						   // else if ($famNr<=2) {
		if ($this->showSpouses=='spouse-family' && count($this->descendants[$FId]['Cids'])>1) {
		  $dummy= ($nr< 0 ? -1:1)* ($nrChilds-1)*($this->ySpace+$this->yWidth*$this->scaleSameGen[$this->scaleDescen]);
		  $ypos+=$dummy;
		  $showDescendants=true;
		}						   // if ($this->showSpouses=='spouse-famlily' && count($fam['Cids'])>1) {
		if (abs($ypos) < (abs($siblingsSpace)+.5)*($this->yWidth+$this->ySpace)) {
		  if ($showDescendants) {
		$ypos+=$siblingsSpace*($this->yWidth+$this->ySpace) -$dummy;
		  } else {				  // if ($showDescendants) {
		$ypos+=$siblingsSpace*($this->yWidth+$this->ySpace); 
		  }						 // else if ($showDescendants) {
		}						   // if (abs($ypos) < abs($siblingsSpace)*($this->yWidth+$this->ySpace)) {
		$dummy=$this->positions[1]+$ypos/($this->ySpace+$this->yWidth);
		if ($dummy < 0.5) {
		  $this->yOffset=$this->headerHeight-($dummy)*($this->ySpace+$this->yWidth)+$this->yWidth/2.;
		}						   // if ($dummy < 0.5) {
		if ($dummy > $this->positions[0]) {
		  $this->positions[0]=$dummy;
		}						   // if ($dummy > $this->positions[0]) {
	  } else {					  // if ($this->pageorient=='portrait') {
		if ($famNr <= 2) {
		  $xpos = $nr* ($this->xWidth)+$this->xSpace - ($rearange&&$famNr==1? ($nr)*($this->xSpace):0);
		} else {					// if ($famNr <= 2) {
		  $xpos=$this->spouses[$l_families[$famNr-1]]['PosOffset'][0]+($this->xSpace+$this->xWidth);
		}						   // else if ($famNr <= 2) {
		if ($this->showSpouses=='spouse-family' && count($this->descendants[$FId]['Cids'])>1) {
		  $dummy= ($nr< 0 ? -1:1)* ($nrChilds-1)*($this->xSpace+$this->xWidth*$this->scaleSameGen[$this->scaleDescen]);
		  $xpos+=$dummy;
		  $showDescendants=true;
		}						   // if ($this->showSpouses=='spouse-famlily' && count($fam['Cids'])>1) {
		if (abs($xpos) < (abs($siblingsSpace)+.5)*($this->xWidth+$this->xSpace)) {
		  if ($showDescendants) {
		$xpos+=$siblingsSpace*($this->xWidth+$this->xSpace) -$dummy;
		  } else {				  // if ($showDescendants) {
		$xpos+=$siblingsSpace*($this->xWidth+$this->xSpace); 
		  }						 // else if ($showDescendants) {
		}						   // if (abs($ypos) < abs($siblingsSpace)*($this->yWidth+$this->ySpace)) {
		$ypos = 0;
		$dummy=$this->positions[1]+$xpos/($this->xSpace+$this->xWidth);
		if ($dummy < 0.5) { 
		  $this->xOffset=-($dummy)*($this->xSpace+$this->xWidth)+$this->xWidth/2.;
		}							 // if ($dummy < 0.5) {
		if ($dummy > $this->positions[0]) {
		  $this->positions[0]=$dummy;
		}							 // if ($dummy > $this->positions[0]) {
	  }							   // else if ($this->pageorient=='portrait') {
	  $this->spouses[$FId]['PosOffset']=array($xpos,$ypos);
	  if ($famNr <=2) {
		$this->spouses[$FId]['OffsetChildren']=array(0,0);
	  } else {
		$this->spouses[$FId]['OffsetChildren']=$this->spouses[$l_families[$famNr-1]]['PosOffset'];
	  }
	}							   // if ($this->showSpouses != 'none') {
	foreach ($this->descendants[$FId]['Cids'] as $Cid) {
	  $this->allIndividuals[$Cid]=WT_Person::getInstance($Cid);
	  $showDescendants=true;
	}							   // foreach ($this->descendants[$FId]['Cids'] as $Cid) {
	++$famNr;
	  }							   // foreach ($ownFams as $FId => $item) {
	  if (!$showDescendants && $this->showSpouses=='spouse-family') {
	if ($this->pageorient=='portrait') {
	  $this->xOffset-=$this->xWidth;
	} else {					  // if ($this->pageorient=='portrait') {
	}							 // else if ($this->pageorient=='portrait') {
	  }							   // if (!$showDescendants && $this->showSpouses=='spouse-family') {
	}								 // if ($ownFams) {
	return ;
  }								   // function getDescendants ($pid) {
  /* ************************************************************************ */
  /**
   * get the persons from the database
   *
   * set the variables
   * $this->genShow		how many generation where found mayby smaller than parameter $maxgen
   * $this->ancestors	  array ()
   * $this->ancestorsRev   array ()
   * $this->allIndividuals array ()
   * $this->leaves		 array ()
   * $this->leavesSort	 array ()
   *
   * @param int $maxgen		   number of generations
   */
  protected function getPersons($maxgen) {
	for ($i=1;$i< (1<<($maxgen)); ++$i) {
	  if ($this->ancestors[$i] !=null && !preg_match('/^REFTO_/',$this->ancestors[$i])) {
	$this->families[$i]=$this->allIndividuals[$this->ancestors[$i]]->getPrimaryChildFamily();
	$this->genShow=max(floor(log($i)/log(2))+1,$this->genShow);
	if ($this->families[$i]!=null) {
	  if ($this->families[$i]->getHusband()) {	  // Father exists
		$id=$this->families[$i]->getHusband()->getXref();
		if (!$this->isInPedigree($id, true)) {
		  if (!isset($this->allIndividuals[$id])) {
		$this->allIndividuals[$id]=WT_Person::getInstance($id);
		  }						 // if (!isset($this->allIndividuals[$id])) {
		  $this->ancestors[2*$i]=$id;
		  $this->ancestorsRev[$id]=2*$i;
		} else {					// if (!$this->isInPedigree($id, true)) {
		  $this->ancestors[2*$i]='REFTO_'.$id;
		}						   // else if (!$this->isInPedigree($id, true)) {
	  } else {					  // if ($this->families[$i]->getHusband()->getXref()) {
		$this->ancestors[2*$i]=null;
	  }							 // else if ($this->families[$i]->getHusband() {

	  if ($this->families[$i]->getWife()) {	 // Mother exists
		$id=$this->families[$i]->getWife()->getXref();
		if (!$this->isInPedigree($id, true)) {
		  if (!isset($this->allIndividuals[$id])) {
		$this->allIndividuals[$id]=WT_Person::getInstance($id);
		  }
		  $this->ancestors[2*$i+1]=$id;
		  $this->ancestorsRev[$id]=2*$i+1;
		} else {					// if (!$this->isInPedigree($id, true)) {
		  $this->ancestors[2*$i+1]='REFTO_'.$id;
		}						   // else if (!$this->isInPedigree($id, true)) {
	  } else {					// if ($this->families[$i]->getWife()->getXref()) {
		$this->ancestors[$i*2+1]=null;
	  }						   // else if ($this->families[$i]->getWife() {
	  if ($this->showSiblings) {
		$children_array=array();
		foreach ($this->families[$i]->getChildren() as $child) {
			$children_array[]=$child->getXref();
		}
		$this->children[$i]=$children_array;
		if (count($this->children[$i]) > 1) {
		  $j=0;
		  foreach ($this->children[$i] as $key=>$cId) {
		if ($this->isInPedigree($cId, true)) {
		  if ($cId == $this->ancestors[$i]) {
			$this->children[$i][]=$j;
		  }					 // if ($cId == $this->ancestors[$i]) {
		} else {				// if ($this->isInPedigree($cId,true)) {
		  $this->allIndividuals[$cId]=WT_Person::getInstance($cId);
		}					   // else if ($this->isInPedigree($cId,true)) {
		++$j;
		  }						 // foreach ($children as $key=>$cId) {
		}						   // if (count($children) > 1) {
	  }							 // if ($this->showSiblings) {
	} else {						// if ($this->families[$i]!=null) {
	  $this->ancestors[$i*2]=null;
	  $this->ancestors[$i*2+1]=null;
	  $this->leaves[$i]=floor(log($i)/log(2))+1;
	  $this->leavesSort[]= ($i*(1<<($maxgen-$this->leaves[$i])));
	}							   // else if ($this->families[$i]!=null) {
	  } else {						  // if ($this->ancestors[$i]!=null && ...
	$this->ancestors[$i*2]=null;
	$this->ancestors[$i*2+1]=null;
	  }								 // else if ($this->ancestors[$i]!=null && ...
	  if (preg_match('/^REFTO_/',$this->ancestors[$i])) {
	$this->leaves[$i]=floor(log($i)/log(2))+1;
	$this->leavesSort[]= ($i*(1<<($maxgen-$this->leaves[$i])));
	  }								 // if (preg_match('/^REFTO_/',$this->ancestors[$i])) {
	}								   // for ($i=1;$i< (2<<($maxgen))/2; ++$i) {
  }									 // function getPersons($gen) {
  /* ************************************************************************ */
  /**
   * initialise PDF file
   *
   * @param string $fonts
   * @return TCPDF class instance
   */
  protected function initPDF($fonts) {
	global $l;

	// create new PDF document
	$pdf = new TCPDF('P', 'mm', 'a0', true, 'UTF-8', false); 
	// set document information
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetAuthor('webtrees');
	$pdf->SetTitle('webtrees Pedigree '.$this->underlinestar($this->allIndividuals[$this->ancestors[1]]->getFullName()));
	//set margins
	$pdf->SetMargins($this->xBrim, $this->yBrim, $this->xBrim);
	// remove default header/footer
	$pdf->setPrintHeader(false);
	//  $pdf->SetHeaderMargin(10);
	//  $pdf->SetHeaderData("", 10, 'a', 'STRING');
	$pdf->setPrintFooter(false);
	//set auto page breaks
	$pdf->SetAutoPageBreak(FALSE, $this->yBrim);
	//set image scale factor
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 
	//set some language-dependent strings
	$pdf->setLanguageArray($l); 
	// set font
	$pdf->SetFont($fonts, '', $this->FontSize);
	// add a page
	$pdf->AddPage();
	$this->pdf=$pdf;
  }								 // function initPDF ($fonts) {
  /* ************************************************************************ */
  /**
   * check if number in tree coresponts to a person with no ancestors 
   *
   * @param string $nr
   * @return boolean 
   */
  protected function isLeave($nr) {
	if (isset($this->leaves[floor($nr)])) {
	  return true;
	}								   // if (isset($this->leaves[$nr])) {
	return false;
  }									 // function isLeave($nr) {
  /* ************************************************************************ */
  /**
   * check if for person with number $nr two parents set in the database
   *
   * @param integer $nr
   * @return boolean 
   */
  protected function TwoParents($nr) {
	if (!isset($this->ancestors[floor($nr)])) {
	  return false;
	}								   // if (!isset($this->ancestors[floor($nr)])) {
	if ($this->isLeave($nr)) {
	  return false;
	}								   // if ($this->isLeave($nr)) {
	if (isset($this->families[$nr]) && !($this->families[$nr]->getHusband() && $this->families[$nr]->getWife())) {
	  return false;
	}								   // if (isset($this->families[$nr]) && ...
	return true;
  }									 // function isLeave($nr) {
  /* ************************************************************************ */
  /**
   * Close PDF file and create output file
   */
  protected function closePdf () {
	// reset pointer to the last page
	$this->pdf->lastPage();
	//Close and output PDF document
	$this->pdf->Output($this->allIndividuals[$this->ancestors[1]]. '_' . $this->genShow . '-gen.pdf', 'I');
  }									 // function closePdf () {
  /* ************************************************************************ */
  /**
   * Format age of parents in HTML
   *
   * global variables:
   * $SHOW_PARENTS_AGE;
   * 
   * @param string $pid		 string child ID
   * @return string with HTML code
   */
  public function parents_age($pid) {
	global $SHOW_PARENTS_AGE;

	$html='';
	if ($SHOW_PARENTS_AGE) {
	  $person=WT_Person::getInstance($pid);
	  $families=$person->getChildFamilies();
	  // Multiple sets of parents (e.g. adoption) cause complications, so ignore.
	  $birth_date=$person->getBirthDate();
	  if ($birth_date->isOK() && count($families)==1) {
	$family=current($families);
	// Allow for same-sex parents
	foreach (array($family->getHusband(), $family->getWife()) as $parent) {
	  if ($parent && $age=WT_Date::GetAgeYears($parent->getBirthDate(), $person->getBirthDate())) {
		$html.=$this->sexImage($parent->getSex()).$age;
	  }							 // if ($parent && ...
	}							   // foreach (array($family->getHusband(), ... 
	  }								 // if ($birth_date->isOK() && count($families)==1) {
	}								   // if ($SHOW_PARENTS_AGE) {
	return $html;
  }									 // function parents_age($pid) {
  /* ************************************************************************ */
  /**
   * Display a Person in an TCPDF::MultiCell
   *
   * global variables:
   *  $PEDIGREE_SHOW_GENDER
   *  $DEBUG
   *
   *
   * @param string $boxSize	  Size of Box		 /(spouse|descendant|child_)?(full|short)_(ref)?/
   * @param float  $x			x Position of Box
   * @param float  $y			y Position of Box
   * @param string $id		   person id from GEDCOM File
   * @param string $nr=""		number relativ to first person
   * @param string $Fid		  FamilyId neaded for boxsize spouse
   * @return empty
   */
  protected function pdfPerson($boxSize, $x, $y, $id, $nr='', $Fid='') {
	global $SHOW_HIGHLIGHT_IMAGES, $PEDIGREE_SHOW_GENDER;
	global $DEBUG;
	
	$xWidth=$this->xWidth*$this->boxSizes[$boxSize][$this->pageorient == 'portrait'?0:1];
	$yWidth=$this->yWidth*$this->boxSizes[$boxSize][$this->pageorient == 'portrait'?1:0];
	if (!isset ($this->allIndividuals[$id])) {
	  return;
	}
	$l_person=$this->allIndividuals[$id];

	if ($DEBUG & 32) {
	  if ($this->pageorient=='portrait') {
	$debugstr=" y=". (($y-$this->yBrim-$this->yOffset)/($this->yWidth+$this->ySpace)-0.5);
	  } else {						  // if ($this->pageorient=='portrait') {
	$debugstr=" x=". (($x-$this->xBrim-$this->xOffset)/($this->xWidth+$this->xSpace)-0.5);
	  }								 // else if ($this->pageorient=='portrait') {
	} else {							// if ($DEBUG & 32) {
	  $debugstr='';
	}								   // else if ($DEBUG & 32) {
	if ($this->pageorient=='portrait') {
	  $y-=($yWidth/2.0);
	} else {							// if ($this->pageorient=='portrait') {
	  $x-=($xWidth/2.0);
	  if (preg_match('/short|ref/', $boxSize) && !preg_match('/spouse/', $boxSize)) { // check for better regex
	$y+=($this->yWidth-$yWidth);
	  }								 // if (preg_match('/short|ref/', $boxSize)) {
	}								   // else if ($this->pageorient=='portrait') {
	// sex information
	$sex=$l_person->getSex();
	if ($PEDIGREE_SHOW_GENDER) {
	  $imgSex=$this->sexImage($sex);
	} else {							// if ($PEDIGREE_SHOW_GENDER) {
	  $imgSex='';
	}								   // else if ($PEDIGREE_SHOW_GENDER) {
	// marriage information displayed in case of person is female and no child
	if (!preg_match('/child/',$boxSize) &&
	((isset($this->families[floor($nr/2)]) && ($sex == 'F')) ||
	 (preg_match('/spouse/', $boxSize) && isset($this->descendants[$Fid]['Fam'])))) {
	  $i=floor($nr/2);
	  if (preg_match('/spouse/', $boxSize)) {
	$fam=$this->descendants[$Fid]['Fam'];
	  }	else {
	$fam=$this->families[$i];
	  }
	  $mdate=$fam->getMarriageDate()->Display();
	  $mplace=get_place_short($fam->getMarriagePlace()) ;
	  $what=(strlen($mdate) > 7 ? 2:0)  /* XXXX why >7 ?? */ + (strlen($mplace)>0?1:0);
	  if ($what) {
	$marrstr=$this->getEventImg('marr',$l_person).$mdate.($what%2 && $what&2 ? ' - ' : '') . $mplace;
	  } else {						  // if ($what) {
	$marrstr='';
	  }								 // else if ($what) {
	} else {							// if (!preg_match('/child/',$boxSize) && ...
	  $marrstr='';
	}								   // else if (!preg_match('/child/',$boxSize) && ... 
	if (preg_match('/full/', $boxSize) && ! preg_match('/ref/', $boxSize)) {		/// XXX not efficent one preg_match enought??
	  //Birth information
	  $bdate=$l_person->getBirthDate();
	  $Dstr=$bdate!=null ? $bdate->Display().' ' :'';
	  $Pstr=get_place_short($l_person->getBirthPlace());
	  $what=(strlen($Dstr) > 7 ? 2:0)  /* XXXX why >7 ?? */ + (strlen($Pstr)>0?1:0);
	  $birth=$this->getEventImg('birt', $l_person) . $Dstr . $this->parents_age($id) .
	($what%2 && $what &2 ? ' - ':'') . // date AND place exists
	($what % 2 ? $Pstr : '') . ($what >0 ? '; ':'');
	  // Death information
	  if ($l_person->isDead()) {
	$ddate=$l_person->getDeathDate();
	$Dstr=$bdate!=null ? $ddate->Display().' ' :'';
	$Pstr=get_place_short($l_person->getDeathPlace());
	$age=WT_Date::GetAgeYears($bdate, $ddate);
	$what=(strlen($Dstr) > 7 ? 2:0)  /* XXXX why >7 ?? */ + (strlen($Pstr)>0?1:0);
	$img=$this->getEventImg('deat', $l_person);
	$death=$img . ($what > 0 ?	// date OR place exists
			   ($what & 2 ?   // date exists
			$Dstr .($age ? '('.WT_I18N::translate('Age').' '.$age.') ':''):'') .
			   ($what%2 && $what &2 ? ' - ':'') . // date AND place exists
			   $Pstr: WT_I18N::translate('yes') ). ';';
	  } else {						  // if ($l_person->isDead()) {
	$death='';
	  }								 // else if ($l_person->isDead()) {
	  if (preg_match('/child/',$boxSize)) {
	if ($this->pageorient=='portrait') {
	  $x+=($this->xWidth-$xWidth);
	} else {						// if ($this->pageorient=='portrait') {
	  $y+=($this->yWidth-$yWidth);
	}							   // else if ($this->pageorient=='portrait') {
	  }								 // if (preg_match('/child/',$boxSize)) {	  
	  $this->personBorder($boxSize, $x, $y, $sex, $nr);

	  if ($SHOW_HIGHLIGHT_IMAGES && ($a=$l_person->findHighlightedMedia()) && (($xWidth/$yWidth) > 3)) {
	$media=WT_Media::getInstance($a['mid']);
	$img=$media->getThumbnail();
	 if (!file_exists($img)) {
	$img=get_media_firewall_path($img);
	 }
	 if (!file_exists($img)) {
	break;
	 }
	$space=min($this->xSpace,$this->ySpace)*0.5;			 /// XXX not a nice style to set
	$imgSize=findImageSize($img);
	$s=min($xWidth,$yWidth)/max(1,$imgSize[1]/$imgSize[0]);
	$this->pdf->Image($img, $x+$space, $y+$space, $s-2*$space,($s-2*$space)*$imgSize[1]/$imgSize[0]);
	$xWidth-=$s;
	$x+=$s;
	  }								 // if ($SHOW_HIGHLIGHT_IMAGES && ($a= ...

	  $html='<font size="+1"><b>'.$nr . $this->underlinestar($l_person->getFullName()) .
	'</b></font>'. $imgSex .' ' . '<br>'.
	$birth . $death . $marrstr. $debugstr;
	  $this->showHtmlInPDF($x-.5 ,$y+.5, $xWidth, $yWidth, $html);
	} else {							// if (preg_match('/full/', $boxSize) && ! preg_match('/ref/', $boxSize)) {
	  //Birth information
	  $Dstr=$l_person->getBirthYear();
	  $Pstr='';
	  $what=(isset($Dstr) ? 2:0);
	  $birth=$this->getEventImg('birt', $l_person) . $Dstr . $this->parents_age($id) .
	($what%2 && $what &2 ? ' - ':'') . // date AND place exists
	($what % 2 ? $Pstr : '') . ($what >0 ? '; ':'');
	  //Death information
	  if ($l_person->isDead()) {
	$Dstr=$l_person->getDeathYear();
	$Pstr='';
	$age=WT_Date::GetAgeYears($l_person->getBirthDate(), $l_person->getDeathDate());
	$what=(isset($Dstr)? 2:0);
	$img=$this->getEventImg('deat', $l_person);
	$death=$img . ($what > 0 ?	// date OR place exists
			   ($what & 2 ?   // date exists
			$Dstr .($age ? ' ('.WT_I18N::translate('Age').' '.$age.') ':''):'') .
			   ($what%2 && $what &2 ? ' - ':'') . // date AND place exists
			   $Pstr: WT_I18N::translate('yes') );
	  } else {						  // if ($l_person->isDead()) {
	$death='';
	  }								 // else if ($l_person->isDead()) {	  
	  if ($this->pageorient=='portrait') {
	if (preg_match('/child/', $boxSize)) {
	  $x+=($this->xWidth-$xWidth);
	}							   // if (preg_match('/child/', $boxSize)) {
	$xWidth*=$this->scaleNextGen;
	  } else {						  // if ($this->pageorient=='portrait') {
	if (preg_match('/child_.*_ref/', $boxSize)) {
	  $y-=$yWidth*(1-$this->boxSizes[$boxSize][1]);
	}							   // if (preg_match('/child_.*_ref/', $boxSize)) {
	  }								 // else if ($this->pageorient=='portrait') {
	  $ref=false;
	  if (preg_match('/ref/', $boxSize)) {$ref=true;}
	  $html='<font size="+1"><b>'.$nr . $this->underlinestar($l_person->getFullName()) .
	'</b></font> '. (!$ref ? $imgSex :'').
	'<br>' .  (preg_match('/short_ref/',$boxSize)? '':
	($birth || $death? $birth . $death . '<br>': '') .
	(isset($marrstr) && strlen($marrstr)>0? $marrstr . '<br>': '')) .
	($ref ? WT_I18N::translate('see person %s', $this->GedcomRefToNumber($id)) : '') . $debugstr;
	  $this->personBorder($boxSize, $x, $y, $sex, $nr);
	  $this->showHtmlInPDF($x-.5, $y+.6, $xWidth, $yWidth, $html);
	}								   // else if (preg_match('/full/', $boxSize) && ! preg_match('/ref/', $boxSize)) {
  }									 // function pdfPerson($boxSize, $x, $y, ...
  /* ************************************************************************ */
  /**
   * Display box in which the person is displayed
   *
   *
   * @todo this info
   */
  protected function personBorder($boxSize, $x, $y, $sex, $nr) {
	$xWidth=$this->xWidth*$this->boxSizes[$boxSize][$this->pageorient == 'portrait'?0:1];
	$yWidth=$this->yWidth*$this->boxSizes[$boxSize][$this->pageorient == 'portrait'?1:0];
	if (preg_match("/^child/i", $boxSize)) {
	  $type='child';
	} else {							// if (preg_match("/^child/i", $boxSize)) {
	  if ($this->isLeave($nr)) {
	$type='leave';
	  } else {						  // if ($this->isLeave($nr)) {
	$type='inside';
	  }								 // else if ($this->isLeave($nr)) {
	}								   // else if (preg_match("/^child/i", $boxSize)) {
	if ($sex=='E') {
	  $type='empty';
	}								   // if ($sex=='E') {
	$this->pdf->StartTransform();
	$this->pdf->Translate($x,1.0*$y+$yWidth*0.5);
	if ($this->pageorient=='landscape') {
	  $this->pdf->Rotate(90, $xWidth*0.5,0);
	  $this->pdf->Scale(100.0*$yWidth/$xWidth,100.0*$xWidth/$yWidth, $xWidth*0.5,0);
	}								   // if ($this->pageorient=='landscape') {
	$this->pdf->Polycurve(0,0, $this->pointsBorder[$boxSize][$type],'DF', $this->borderStyle[$sex], $this->fillColor[$sex]);
	$this->pdf->MirrorV(0);
	$this->pdf->Polycurve(0,0, $this->pointsBorder[$boxSize][$type],'DF', $this->borderStyle[$sex], $this->fillColor[$sex]);
	$this->pdf->StopTransform();
  }									 // function personBorder($how, $x, $y,  $xWidth, $yWidth, $sex, $id) {
  /* ************************************************************************ */
  /**
   * HTML output from a person
   * 
   * global variables:
   * $DEBUG 
   *
   *
   * @param string $how		'full'|'ref'
   * @param string $ref		GEDCOM xref
   * @param int $i			 number of person
   * @param string $child=''   string additionaly displayed for siblings
   *
   * @return nothing
   */
  protected function printPersonHtml($how, $ref, $i, $child='') {
	global $DEBUG;

	$l_person=$this->allIndividuals[$ref];
	echo '<tr><td class="list_value_wrap" align="';
	echo ($child ? 'right':'left');
	echo '">'. $i. $child. '</td>';
	echo '<td class="list_value_wrap" align="center">'.(floor(log($i)/log(2))+1).'</td>';
	if ($how =="full") {
	  $date=$l_person->getBirthDate();
	  echo '<td class="list_value_wrap" align="left">'. $l_person->getFullName(). $l_person->getSexImage(). 
	'</td><td class="list_value_wrap"></td>';
	  if (!$child) {
	$rowspan=(isset($this->families[$i]) && count($this->children[$i])>1?
		  (count($this->children[$i])-1):1);
	$nrChilds=(isset($this->families[$i]) && count($this->children[$i])>=1?
		   max(count($this->children[$i])-1,1):'&nbsp;');
	
	echo '<td valign="middle" class="list_value_wrap" align="center" rowspan="'.$rowspan . '">'.$nrChilds.'</td>';
	  }
	  echo '<td class="list_value_wrap" align="left">'.$this->getEventImg('birt',$l_person). $date->Display().
	'</td><td class="list_value_wrap" align="left">'.get_place_short($l_person->getBirthPlace()).'</td>'.
	"\n";
	  echo '<td class="list_value_wrap" align="left">'.$this->parents_age($ref). '</td>';
	  if ($l_person->isDead()) {
	$date=$l_person->getDeathDate();
	echo '<td class="list_value_wrap" align="left">'. $this->getEventImg('deat', $l_person). $date->Display().
	  '</td><td class="list_value_wrap" align="left">' .get_place_short($l_person->getDeathPlace()).'</td>';
	  } else {						  // if ($l_person[$i]->isDead()) {
	echo '<td></td><td></td>';
	  }
	} else {							// if ($how=="full") {
	  echo '<td class="list_value_wrap">' . $l_person->getFullName() . $l_person->getSexImage(). '</td>';
	  echo '<td class="list_value_wrap" align="left">'.$this->ancestorsRev[$ref] . '</td>' ;
	  echo '<td colspan="2" class="list_value_wrap" align="left">' . $this->getEventImg('birt', $l_person). $l_person->getBirthYear() . '</td>';
	  echo '<td colspan="1" class="list_value_wrap" align="center">' . $this->parents_age($ref) . '</td>';
	  echo '<td colspan="2" class="list_value_wrap" align="left">' . $this->getEventImg('deat', $l_person). $l_person->getDeathYear() . '</td>';
	}								   // else if ($how=="full") {
	if ($DEBUG) {
	  echo '<td>d='. $DEBUG.'</td>';
	  if ($DEBUG & 8) {
	if (isset($this->leaves[$i]) && $this->leaves[$i]) {
	  echo "<td>No Parents $this->leaves[$i]/$this->genShow i=". ($i*(2<<($this->genShow-$this->leaves[$i]))/2). "</td>";
	} else {						// if (isset($this->leaves[$i]) && $this->leaves[$i]) {
	  echo "<td></td>";
	}							   // else if (isset($this->leaves[$i]) && $this->leaves[$i]) {
	  }								 // if ($DEBUG & 8) {
	  if ($DEBUG & 128) {
	if (isset($this->children[$i]) && !$child) {
	  $n=(count($this->children[$i])-1);
	  echo '<td>'  . ($this->children[$i][$n]) .'/'. $n .  ' ; ';
	  print_r($this->children[$i]);
	  echo '</td>';
	} else {						// if (isset($this->children[$i]) && !$child) {
	  echo "<td></td>";
	}							   // else if (isset($this->children[$i]) && !$child) {
	  }								 // if ($DEBUG & 128) {
	  if ($DEBUG & 16) {
	if (isset($this->positions[$i])) {
	  echo '<td>p='.$this->positions[$i].'</td>'."\n";
	} else {						// if (isset($this->positions[$i])) {
	  echo '<td align="right"><font color="red">no y</td>'."\n";
	}							   // else if (isset($this->positions[$i])) {
	  }								 // if ($DEBUG & 16) {
	}								   // if ($DEBUG)
	echo "</tr>\n";
	return;
  }									 // function printPersonHtml
  /* ************************************************************************ */
  /**
   * Set page orientation<br>
   * In this function set the orientation of the pedigree chart. This includes 
   * the sizes of the boxes and spaces for the persons. Also the style of of the
   * boxes depends on the orientation and will set here.
   *
   * @param string $pageorient
   * @return empty
   */
  protected function setOrientation($pageorient) {
	$this->pageorient=$pageorient;
	if ($pageorient == 'portrait') {
	  $this->xSpace=4.;
	  $this->xWidth=47.;
	  $this->ySpace=1.5;
	  $this->yWidth=14.;
	  $this->scaleSameGen=array('full'=>0.8,'short'=>.6);
	  $this->scaleNextGen=0.9;
	  $this->yOffset=$this->headerHeight;
	  switch($this->showSpouses) {
	  case 'spouse-family':
	$this->xOffset=$this->xSpace+$this->xWidth;
	break;
	  case 'show_spouses':
	$this->xOffset=$this->xSpace;
	break;
	  default:
	$this->xOffset=0;
	  }								 // switch($this->showSpouses) {
	  $this->pageHeightAdd=0;
	} else {							// if ($pageorient == 'portrait') {
	  $this->xSpace=1.5;
	  $this->xWidth=21.;
	  $this->ySpace=4.;
	  $this->yWidth=25.;
	  $this->scaleNextGen=0.85;
	  $this->scaleSameGen=array('full'=>0.9,'short'=>.75);
	  $this->yOffset=$this->headerHeight;
	  $this->xOffset=0;
	  switch($this->showSpouses) {
	  case 'spouse-family':
	$this->pageHeightAdd=$this->ySpace+$this->yWidth;
	break;
	  case 'show_spouses':
	$this->pageHeightAdd=$this->ySpace;
	break;
	  default:
	$this->pageHeightAdd=0;
	  }								 // switch($this->showSpouses) {
	}								   // else if ($pageorient == 'portrait') {
	$this->boxSizes=
	  array('full'=>array(1.0,1.0),'full_ref'=>array($this->scaleNextGen,1.0)
		,'spouse_short'   =>array($this->scaleNextGen,$this->scaleSameGen['full'])
		,'spouse_full'	=>array($this->scaleNextGen,1.0)
		,'descandes_full' =>array(1.0,$this->scaleSameGen['full'])
		,'descandes_short'=>array(1.0,$this->scaleSameGen['short'])
		,'child_full'	 =>array($this->scaleNextGen,$this->scaleSameGen['full'])
		,'child_full_ref' =>array($this->scaleNextGen+($this->scaleNextGen-1)*.5,$this->scaleSameGen['full'])
		,'child_short'	=>array($this->scaleNextGen,$this->scaleSameGen['short'])
		,'child_short_ref'=>array($this->scaleNextGen+($this->scaleNextGen-1)*.5,$this->scaleSameGen['short'])
		);
	$wS=min($this->xSpace, $this->ySpace)/4;

	foreach ($this->boxSizes as $key=>$value) {
	  $H=$this->yWidth*$value[$pageorient == 'portrait'?1:0]*0.5; // points are mirrored
	  $L=$this->xWidth*$value[$pageorient == 'portrait'?0:1];
	  $wH=$H/3;
	  $wL=$L/6;
	  switch($this->boxStyle){
	  case 'normal':
	$this->pointsBorder[$key]=
	  array('empty' =>array(array(0,0,	0,$H,  0,$H)
				,array(0,$H,  $L,$H, $L,$H)
				,array($L,$H, $L,0,  $L,0)),
		'inside'=>array(array(0,0,		   0,$H-$wS,	 0,$H-$wS)
				,array(0,$H-$wS/2,   $wS/2,$H,	 $wS, $H)
				,array($wS,$H,	   $wS/2,$H,	 $L-$wS,$H)
				,array($L-$wS/2,$H,  $L,$H-$wS/2,  $L,$H-$wS)				
				,array($L,$H-$wS,	$L,0,		 $L,0)));
	$this->pointsBorder[$key]['leave']=$this->pointsBorder[$key]['inside'];
	$this->pointsBorder[$key]['child']=$this->pointsBorder[$key]['inside'];
	break;
	  default:
	$this->pointsBorder[$key]=
	  array('empty' =>array(array(0,0,	0,$H,   0,$H)
				,array(0,$H,  $L,$H,  $L,$H)
				,array($L,$H, $L,0,   $L,0)),
		'leave' =>array(array(-$wS,-$wH,		  -$wS,-$H+$wS,	  0,-$H)
				,array($wS,-$H-$wS,	   $L/2-$wS,-$H-$wS,  $L/2,-$H)
				,array($L/2+$wS,-$H-$wS,  $L-$wS,-$H-$wS,	$L,-$H)
				,array($L+$wS,-$H+$wS,	$L,0,			  $L+2*$wS,0)),
		'inside'=>array(array(0,-$wH,			 0,-$H+$wH,		 -$wS,-$H-$wS)
				,array($wL,-$H,		   $L-$wL,-$H,		$L+$wS,-$H-$wS)
				,array($L,-$H+$wH,		$L,+$wH,		   $L,0)),
		'child'=>array(array(0,-$wH,			  -$wS,-$H+$wS,	  0,-$H)
				   ,array($wS,-$H-$wS/2,	  $L-$wS,-$H-$wS/2,  $L,-$H)
				   ,array($L+$wS,-$H+$wS,	 $L,+$wH,		   $L,0)));
	if ($L > 2*$H) {
	  $this->pointsBorder[$key]['leave']=
		array(array(-$wS,-$wH,		   -$wS,-$H+$wS,	   0,-$H)
		  ,array($wS,-$H-$wS,		$L/3-$wS,-$H-$wS,   $L/3,-$H)
		  ,array($L/3+$wS,-$H-$wS,   $L*2/3-$wS,-$H-$wS, $L*2/3,-$H)
		  ,array($L*2/3+$wS,-$H-$wS, $L-$wS,-$H-$wS,	 $L,-$H)
		  ,array($L+$wS,-$H+$wS,	 $L,0,			   $L+2*$wS,0));
	}							   // if ($L > 2*$H) {
	  }								 // switch($this->boxStyle){
	}								   // foreach ($how as $key=>$value) {  
  }									 // function setOrientation($pageorient) {
  /* ************************************************************************ */
  /**
   * Set page size and title
   *
   * global variables:
   * $SHOW_EMPTY_BOXES
   *
   * @param string $output		 type of output
   * @return empty
   */
  protected function setPageSizeandTitle ($output) {
	global $controller, $SHOW_EMPTY_BOXES;
	if ($output=='PDF') {
	  if ($SHOW_EMPTY_BOXES) {
		$max = (1<<($this->genShow-1));
	  } else {						  // if ($SHOW_EMPTY_BOXES) {
		$max=max($this->positions)+.5;
	  }								 // else if ($SHOW_EMPTY_BOXES) {
	  if ($this->pageorient == 'portrait') {
		$h=2*$this->yBrim+$this->yOffset+($max)*($this->yWidth+$this->ySpace);
		$w=2*$this->xBrim+$this->xOffset+($this->genShow)*($this->xWidth+$this->xSpace);
		$dO='L';
		$dX=$this->xBrim;
		$dY=max($this->yBrim+($max)*($this->yWidth+$this->ySpace)-$this->FontSize/2.5,0)+$this->headerHeight+2;
	  } else {						 // if ($pageorient == 'portrait') {
		$h=2*$this->yBrim+$this->headerHeight+($this->genShow)*($this->yWidth+$this->ySpace)+$this->pageHeightAdd;
		$w=2*$this->xBrim+$this->xOffset+($max)*($this->xWidth+$this->xSpace);
		$dO='R';
		$dX=$this->xBrim+($max-1)*($this->xWidth+$this->xSpace)-$this->xSpace;
		$dY=$this->yBrim+$this->genShow* ($this->yWidth+$this->ySpace)+$this->headerHeight-$this->FontSize/2.5+$this->pageHeightAdd+2;
	  }								 // else if ($pageorient == 'portrait') {
	  if ($w>$h) $orient='L'; else $orient='P';
	  $this->pdf->setPageFormat(array($w, $h),$orient);
	  //actual date
	  $this->pdf->MultiCell ($this->xWidth, $this->FontSize/2.5,
				 timestamp_to_gedcom_date(mktime(0,0,0,date("m"),date("d"),date("Y")))->Display(),
				 0, $dO, 0, 0, $dX, $dY, true, 0,true,false);
	  //title
	  $this->pdf->MultiCell ($w ,$this->FontSize*2, "<h2>".
				 WT_I18N::translate('%1$s: %2$d Generation Pedigree Chart', $this->underlinestar($this->allIndividuals[$this->ancestors[1]]->getFullName()), $this->genShow). 
				 "</h2>",
				 0, 'C', 0, 0, 0, $this->yBrim, true, 0, true, false, $this->FontSize*2);
	} else {							// if ($output=="PDF") {
	  $this->FontSize=10;
	  $controller
			->setPageTitle(WT_I18N::translate('Pedigree Chart').' - '.WT_I18N::translate('Single page'))
			->pageHeader();
	  echo '<br><center> <table border="0">'."\n";
	  echo ' <tr><th class="list_label">&nbsp;</th>
  <th class="list_label">'.WT_I18N::translate('Generation').'</th>
  <th class="list_label">'.WT_I18N::translate('Name').'</th>
  <th class="list_label">'.WT_I18N::translate('See person').'</th>
  <th class="list_label">'.WT_I18N::translate('Siblings').'</th>
  <th class="list_label" colspan="3">'.WT_Gedcom_Tag::getLabel('BIRT').'</th>
  <th class="list_label" colspan="2">'.WT_Gedcom_Tag::getLabel('DEAT').'</th>
 </tr>'."\n";
	}								   // else if ($output=="PDF") {
  }									 // function setPageSizeandTitle ($pageorient) {
  /* ************************************************************************ */
  /**
   * create HTML output of sexImage in a way which TCPDF can handle 
   *
   * @param string $sex
   * @return string with HTML code
   */
  public function sexImage($sex) {
	if ($sex=="M") {
	  $imgSex=' <img src="'.WT_THEME_DIR.'images/sex_m_9x9.png" alt="M" align="bottom" height="'.(0.9*$this->FontSize).'" >';
	} elseif ($sex=="F") {			  // if ($sex=="M") {
	  $imgSex=' <img src="'.WT_THEME_DIR.'images/sex_f_9x9.png" alt="F" align="bottom" height="'.(0.9*$this->FontSize).'" >';
	} else {							// elseif ($sex=="F") {
	  $imgSex=' <img src="'.WT_THEME_DIR.'images/sex_u_9x9.png" alt="U" align="bottom" height="'.(0.9*$this->FontSize).'" >';
	}								   // else  elseif ($sex=="F") {
	return $imgSex;
  }									 // function sexImage($sex) {
  /* ************************************************************************ */
  /**
   * display html code in TCPDF file
   * if the text don't fit to the box, the font size is reduced
   *
   * @todo check if font scaling changes the number of lines
   *
   * @param float $x	   x-Position of text
   * @param float $y	   y-Position of text
   * @param float $xWidth  length of text box
   * @param float $yWidth  height of text box
   * @param string $html   html code do display

   * @return float scaling factor
   */
  protected function showHtmlInPDF($x, $y, $xWidth, $yWidth, $html) {
// int MultiCell( float $w, float $h, string $txt, [mixed $border = 0], [string $align = 'J'], [int $fill = 0], [int $ln = 1], [float $x = ''], [float $y = ''],
//	[boolean $reseth = true], [int $stretch = 0], [boolean $ishtml = false], [boolean $autopadding = true], [float $maxh = 0])
	$this->pdf->startTransaction();
	$this->pdf->MultiCell($xWidth, 0, $html, 0, 'L', 0, 1, $x, $y, true, 0, true, true);
	$newY=$this->pdf->getY();
	if ($newY-$y <= $yWidth ) { // check if text fits into box
	  $this->pdf->commitTransaction(true);
	  return 1.0;
	} else {							// if ($newY-$y <= $yWidth ) {
	  $this->pdf->rollbackTransaction(true);
	  $scale=$yWidth/($newY-$y);
	  $this->pdf->SetFont($this->fonts, '', $this->FontSize*$scale);
	  $this->pdf->MultiCell($xWidth, 0, $html, 0, 'L', 0, 1, $x, $y, true, 0, true, true);
	  $this->pdf->SetFont($this->fonts, '', $this->FontSize);
	  return $scale;
	}								   // else if ($newY-$y <= $yWidth ) {
  }									 // function showHtmlInPDF($x, $y, $xWidth, $yWidth, $html) {
  /* ************************************************************************ */
  /**
   * Replace CSS class "starredname" by simple "<u></u>" which TCPDF can handle
   *
   * @param string $name
   * @return string with HTML code
   */
  public function underlinestar($name) {
	return preg_replace('#<span class="starredname">([^<]*)</span>#i','<u>\1</u>', $name); 
  }									 // function underlinestar($name) {
  /* ************************************************************************ */
  /**
   * check if number or Gedcom Ref is already in pedigree tree definded
   *
   * @param string $index
   * @param bool isReverse
   * @return bool
   */
  public function isInPedigree($index, $isReverse=false) {
	global $GEDCOM_ID_PREFIX;
	if ($GEDCOM_ID_PREFIX && preg_match ('/^'.$GEDCOM_ID_PREFIX.'/', $index)) {
	  if (isset($this->ancestorsRev[$index])) {
	return true;
	  }								 // if (isset($this->ancestorsRev[$index])) {
	} else {							// if ($GEDCOM_ID_PREFIX && preg_match ('/^'.$GEDCOM_ID_PREFIX.'/', $index)) {
	  if (isset($this->ancestors[$index])) {
	return true;
	  }								 // if (isset($this->ancestors[$index])) {
	}								   // else if (preg_match ('/^'.$GEDCOM_ID_PREFIX.'/', $index)) {
	return false;
  }									 // function isInPedigree($index, $isReverse=false) {
  /* ************************************************************************ */
  /**
   * convert Gedcom Ref to number in pedigree tree
   *
   * @param mixed $ref
   * @return int
   */
  public function GedcomRefToNumber($ref) {
	if (preg_match ('/^REFTO_/', $ref)) {
	  return $this->ancestorsRev[substr($ref,6)];
	} else {							// if (preg_match ('/^REFTO_/', $ref)) {
	  return $this->ancestorsRev[$ref];
	}								   // else if (preg_match ('/^REFTO_/', $ref)) {
	return false;
  }									 // function GedcomRefToNumber($ref) {
  /* ************************************************************************ */
}
?>