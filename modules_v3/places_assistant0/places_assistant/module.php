<?php
// Classes and libraries for module system
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
// $Id: module.php 13034 2011-12-12 04:03:13Z lukasz $

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

class places_assistant_WT_Module extends WT_Module {
	// Extend WT_Module
	public function getTitle() {
		return WT_I18N::translate('Places assistant');
	}

	// Extend WT_Module
	public function getDescription() {
		return WT_I18N::translate('The places assistant provides a split mode way to enter places names.');
	}

	// Extend WT_Module
	public function modAction($mod_action) {
		switch($mod_action) {
		case 'getdata':
			$this->getdata();
			break;
		default:
			header('HTTP/1.0 404 Not Found');
		}
	}

	public static function get_plac_label() {
		global $GEDCOM;
		$ged_id=get_id_from_gedcom($GEDCOM);

		$HEAD = find_gedcom_record('HEAD', $ged_id);
		$HEAD_PLAC = get_sub_record(1, '1 PLAC', $HEAD);
		$HEAD_PLAC_FORM = get_sub_record(1, '2 FORM', $HEAD_PLAC);
		$HEAD_PLAC_FORM = substr($HEAD_PLAC_FORM, 7);
		if (empty($HEAD_PLAC_FORM)) $HEAD_PLAC_FORM = WT_I18N::translate('City, County, State/Province, Country');
		$plac_label = explode(',', $HEAD_PLAC_FORM);
		$plac_label = array_reverse($plac_label);
		if ($HEAD_PLAC_FORM == WT_I18N::translate('City, County, State/Province, Country')) $plac_label[0] = WT_Gedcom_Tag::getLabel('CTRY');

		return $plac_label;
	}

	public static function setup_place_subfields($element_id) {
		global $controller, $WT_PLACES_SETUP, $WT_IMAGES;

		if (!empty($WT_PLACES_SETUP)) return;
		$WT_PLACES_SETUP = true;

		$header='';
		$header='if (document.createStyleSheet) {
				document.createStyleSheet("'.WT_MODULES_DIR.'places_assistant/_css/dropdown.css"); // For Internet Explorer
			} else {
				jQuery("head").append(\'<link rel="stylesheet" href="'.WT_MODULES_DIR.'places_assistant/_css/dropdown.css" type="text/css">\');
			}';
		$controller->addInlineJavaScript($header, WT_Controller_Base::JS_PRIORITY_LOW)
			->addExternalJavaScript(WT_STATIC_URL.WT_MODULES_DIR.'places_assistant/_js/getobject.js')
			->addExternalJavaScript(WT_STATIC_URL.WT_MODULES_DIR.'places_assistant/_js/modomt.js')
			->addExternalJavaScript(WT_STATIC_URL.WT_MODULES_DIR.'places_assistant/_js/xmlextras.js')
			->addExternalJavaScript(WT_STATIC_URL.WT_MODULES_DIR.'places_assistant/_js/acdropdown.js')
			->addExternalJavaScript(WT_STATIC_URL.WT_MODULES_DIR.'places_assistant/_js/strings.js');

		$plac_label = self::get_plac_label();
		?>
		<script type="text/javascript">
		<!--
		var element_id = '<?php echo $element_id; ?>';
		function http_loadmap(ctry) {
			// meaningless request?
			if (ctry=='' || ctry=='???') return;
			// already loaded ?
			if (document.getElementsByName(ctry)[0]) return;
			// load data into HTML tag <div id='mapdata'> ... </div>
			document.getElementById('mapdata').innerHTML = '';
			// get mapfile from server
			http_request = XmlHttp.create();
			// 1. user map
			mapfile = WT_MODULES_DIR+'places_assistant/'+ctry+'/'+ctry+'.extra.htm';
			http_request.open('GET', mapfile, false); http_request.send(null);
			if (http_request.status == 200) {
				document.getElementById('mapdata').innerHTML += http_request.responseText;
			} else {
			// 2. localized map
				mapfile = WT_MODULES_DIR+'places_assistant/'+ctry+'/'+ctry+'.<?php echo WT_LOCALE; ?>.htm';
				http_request.open('GET', mapfile, false); http_request.send(null);
				if (http_request.status == 200) {
					document.getElementById('mapdata').innerHTML += http_request.responseText;
				} else {
			// 3. default map
					mapfile = WT_MODULES_DIR+'places_assistant/'+ctry+'/'+ctry+'.htm';
					http_request.open('GET', mapfile, false); http_request.send(null);
					// load data into HTML tag <div id='mapdata'> ... </div>
					if (http_request.status == 200) {
						document.getElementById('mapdata').innerHTML += http_request.responseText;
					}
				}
			}
		}
		// called to refresh field PLAC after any subfield change
		function updatewholeplace(place_tag) {
			place_value='';
			for (p=0; p<<?php echo count($plac_label); ?>; p++) {
				place_subtag=place_tag+'_'+p;
				if (document.getElementById(place_subtag)) {
					// clear data after opening bracket : Wales (WLS) ==> Wales
					subtagval = document.getElementById(place_subtag).value;
					cut = subtagval.indexOf(' (');
					if (cut>1) subtagval = subtagval.substring(0,cut);
					if (p>0) place_value = subtagval+', '+place_value;
					else place_value = subtagval;
				}
			}
			document.getElementById(place_tag).value = place_value;
		}
		// called to refresh subfields after any field PLAC change
		function splitplace(place_tag) {
			element_id = place_tag;
			place_value = document.getElementById(place_tag).value;
			var place_array=place_value.split(',');
			var len=place_array.length;
			for (p=0; p<len; p++) {
				q=len-p-1;
				place_subtag=place_tag+'_'+p;
				if (document.getElementById(place_subtag)) {
					//alert(place_subtag+':'+place_array[q]);
					document.getElementById(place_subtag).value=trim(place_array[q]);
				}
			}
			//document.getElementById(place_tag+'_0').focus();
			if (document.getElementsByName(place_tag+'_PLAC_CTRY')) {
				elt=document.getElementsByName(place_tag+'_PLAC_CTRY')[0];
				ctry=elt.value.toUpperCase();
				//alert(elt.value.charCodeAt(0)+'\n'+elt.value.charCodeAt(1));
				if (elt.value=='\u05d9\u05e9\u05e8\u05d0\u05dc') ctry='ISR'; // Israel hebrew name
				else if (ctry.length==3) elt.value=ctry;
				if (ctry=='') ctry='???';
				<?php foreach (WT_Stats::get_all_countries() as $country_code=>$country_name) { ?>
				else if (ctry=='<?php echo utf8_strtoupper(addslashes($country_name)); ?>') ctry='<?php echo $country_code; ?>';
				<?php } ?>
				else if (ctry.length!=3) ctry=ctry.substr(0,3);
				pdir=WT_MODULES_DIR+'places_assistant/'+ctry+'/';
				// select current country in the list
				sel=document.getElementsByName(place_tag+'_PLAC_CTRY_select')[0];
				for (i=0;i<sel.length;++i) if (sel.options[i].value==ctry) sel.options[i].selected=true;
				// refresh country flag
				var img=document.getElementsByName(place_tag+'_PLAC_CTRY_flag')[0];
				var ctryFlag = WT_MODULES_DIR+'places_assistant/flags/'+ctry+'.png';
				if (ctry=='???') ctryFlag = WT_MODULES_DIR+'places_assistant/flags/blank.png';
				img.src=ctryFlag;
				img.alt=ctry;
				img.title=ctry;
				// load html map file from server
				http_loadmap(ctry);
				// refresh country image
				img=document.getElementsByName(place_tag+'_PLAC_CTRY_img')[0];
				if (document.getElementsByName(ctry)[0]) {
					img.src=pdir+ctry+'.png';
					img.alt=ctry;
					img.title=ctry;
					img.useMap='#'+ctry;
				}
				else {
					img.src='images/pix1.gif'; // show image only if mapname exists
					document.getElementsByName(place_tag+'_PLAC_CTRY_div')[0].style.height='auto';
				}
				// refresh state image
				/**img=document.getElementsByName(place_tag+'_PLAC_STAE_auto')[0];
				img.alt=ctry;
				img.title=ctry;**/
				stae=document.getElementsByName(place_tag+'_PLAC_STAE')[0].value;
				stae=strclean(stae);
				stae=ctry+'_'+stae;
				img=document.getElementsByName(place_tag+'_PLAC_STAE_img')[0];
				if (document.getElementsByName(stae)[0]) {
					img.src=pdir+stae+'.png';
					img.alt=stae;
					img.title=stae;
					img.useMap='#'+stae;
				}
				else {
					img.src='images/pix1.gif'; // show image only if mapname exists
					document.getElementsByName(place_tag+'_PLAC_STAE_div')[0].style.height='auto';
				}
				// refresh county image
				/**img=document.getElementsByName(place_tag+'_PLAC_CNTY_auto')[0];
				img.alt=stae;
				img.title=stae;**/
				cnty=document.getElementsByName(place_tag+'_PLAC_CNTY')[0].value;
				cnty=strclean(cnty);
				cnty=stae+'_'+cnty;
				img=document.getElementsByName(place_tag+'_PLAC_CNTY_img')[0];
				if (document.getElementsByName(cnty)[0]) {
					img.src=pdir+cnty+'.png';
					img.alt=cnty;
					img.title=cnty;
					img.useMap='#'+cnty;
				}
				else {
					img.src='images/pix1.gif'; // show image only if mapname exists
					document.getElementsByName(place_tag+'_PLAC_CNTY_div')[0].style.height='auto';
				}
				// refresh city image
				/**img=document.getElementsByName(place_tag+'_PLAC_CITY_auto')[0];
				img.alt=cnty;
				img.title=cnty;**/
			}
		}
		// called when clicking on +/- PLAC button
		function toggleplace(place_tag) {
			var ronly=document.getElementById(place_tag).readOnly;
			document.getElementById(place_tag).readOnly=1-ronly;
			if (ronly) {
				document.getElementById(place_tag+'_pop').style.display='inline';
				updatewholeplace(place_tag);
			}
			else {
				document.getElementById(place_tag+'_pop').style.display='none';
				splitplace(place_tag);
			}
		}
		// called when selecting a new country in country list
		function setPlaceCountry(txt, eid) {
			element_id=eid;
			document.getElementsByName(eid+'_PLAC_CTRY_div')[0].style.height='32px';
			document.getElementsByName(eid+'_PLAC_STAE_div')[0].style.height='32px';
			document.getElementsByName(eid+'_PLAC_CNTY_div')[0].style.height='32px';
			document.getElementsByName(eid+'_PLAC_CTRY')[0].value=txt;
			updatewholeplace(eid);
			splitplace(eid);
		}
		// called when clicking on a new state/region on country map
		function setPlaceState(txt) {
			if (txt!='') {
				document.getElementsByName(element_id+'_PLAC_STAE_div')[0].style.height='32px';
				document.getElementsByName(element_id+'_PLAC_CNTY_div')[0].style.height='32px';
			}
			div=document.getElementsByName(element_id+'_PLAC_CTRY_div')[0];
			if (div.style.height!='auto') { div.style.height='auto'; return; } else div.style.height='32px';
			document.getElementsByName(element_id+'_PLAC_STAE_div')[0].style.height='auto';
			p=txt.indexOf(' ('); if (1<p) txt=txt.substring(0,p); // remove code (XX)
			if (txt.length) document.getElementsByName(element_id+'_PLAC_STAE')[0].value=txt;
			updatewholeplace(element_id);
			splitplace(element_id);
		}
		// called when clicking on a new county on state map
		function setPlaceCounty(txt) {
			document.getElementsByName(element_id+'_PLAC_CNTY_div')[0].style.height='32px';
			div=document.getElementsByName(element_id+'_PLAC_STAE_div')[0];
			if (div.style.height!='auto') { div.style.height='auto'; return; } else div.style.height='32px';
			document.getElementsByName(element_id+'_PLAC_CNTY_div')[0].style.height='auto';
			p=txt.indexOf(' ('); if (1<p) txt=txt.substring(0,p); // remove code (XX)
			if (txt.length) document.getElementsByName(element_id+'_PLAC_CNTY')[0].value=txt;
			updatewholeplace(element_id);
			splitplace(element_id);
		}
		// called when clicking on a new city on county map
		function setPlaceCity(txt) {
			div=document.getElementsByName(element_id+'_PLAC_CNTY_div')[0];
			if (div.style.height!='auto') { div.style.height='auto'; return; } else div.style.height='32px';
			if (txt.length) document.getElementsByName(element_id+'_PLAC_CITY')[0].value=txt;
			updatewholeplace(element_id);
			splitplace(element_id);
		}
		//-->
		</script>
		<?php
	}

	/**
	 * creates PLAC input subfields (Country, District ...) according to Gedcom HEAD>PLACE>FORM
	 *
	 * data split/copy is done locally by javascript functions
	 *
	 * @param string $element_id id of PLAC input element in the form
	 */
	public static function print_place_subfields($element_id) {
		global $iso3166, $WT_IMAGES;

		
		$plac_label = self::get_plac_label();
		$countries = WT_Stats::get_all_countries();
		uasort($countries, 'utf8_strcasecmp');

		echo '<div id="mapdata"></div>';

		$cols=40;
		echo '<a href="javascript:;" onclick="expand_layer(\'', $element_id, '_div\'); toggleplace(\'', $element_id, '\'); return false;">',
			 '<img id="', $element_id, '_div_img" src="', $WT_IMAGES['plus'], '" width="11" height="11" alt="" title="', 
			 WT_I18N::translate('Show'), '">&nbsp;</a>';
		echo help_link('SPLIT_PLACES','places_assistant');
		echo '<div id="', $element_id, '_div" style="display: none; border-width:thin; border-style:none; padding:0px">';
		// subtags creation : _0 _1 _2 etc...
		$icountry=-1;
		$istate=-1;
		$icounty=-1;
		$icity=-1;
		for ($i=0; $i<count($plac_label); $i++) {
			$subtagid=$element_id.'_'.$i;
			$subtagname=$element_id.'_'.$i;
			$plac_label[$i]=trim($plac_label[$i]);
			if (in_array(utf8_strtolower($plac_label[$i]), array('country', 'pays', 'land', 'zeme', 'ülke', 'país', 'ország', 'nazione', 'kraj', 'maa', utf8_strtolower(WT_Gedcom_Tag::getLabel('CTRY'))))) {
				$cols='8';
				$subtagname=$element_id.'_PLAC_CTRY';
				$icountry=$i;
				$istate=$i+1;
				$icounty=$i+2;
				$icity=$i+3;
			} else $cols=40;
			if ($i==$istate) $subtagname=$element_id.'_PLAC_STAE';
			if ($i==$icounty) $subtagname=$element_id.'_PLAC_CNTY';
			if ($i==$icity) $subtagname=$element_id.'_PLAC_CITY';
			echo '<small>';
			// Translate certain tags.  The should be specified in english, as the gedcom file format is english.
			switch (strtolower($plac_label[$i])) {
			case 'country':  echo WT_I18N::translate('Country'); break;
			case 'state':    echo WT_I18N::translate('State'); break;
			case 'province': echo WT_I18N::translate('Province'); break;
			case 'county':   echo WT_I18N::translate('County'); break;
			case 'city':     echo WT_I18N::translate('City'); break;
			case 'parish':   echo WT_I18N::translate('Parish'); break;
			default:         echo $plac_label[$i]; break;
			}
			echo '</small><br>';
			echo '<input type="text\" id="', $subtagid, '" name="', $subtagname, '" value="" size="', $cols, '"';
			echo 'onblur="updatewholeplace(\'', $element_id, '\'); splitplace(\'', $element_id, '\');"';
			echo 'onchange="updatewholeplace(\'', $element_id, '\'); splitplace(\'', $element_id, '\');"';
			echo 'onmouseout="updatewholeplace(\'', $element_id, '\'); splitplace(\'', $element_id, '\');"';
			if ($icountry<$i and $i<=$icity) {
				echo ' acdropdown="true" autocomplete_list="url:', WT_SERVER_NAME.WT_SCRIPT_PATH, 'module.php?mod=places_assistant&amp;mod_action=getdata&amp;localized=', WT_LOCALE, '&amp;field=', $subtagname, '&amp;s=[S]" autocomplete="off" autocomplete_matchbegin="false"';
			}
			echo '>';
			// country selector
			if ($i==$icountry) {
				echo ' <img id="', $element_id, '_PLAC_CTRY_flag" name="', $element_id, '_PLAC_CTRY_flag" src="', WT_MODULES_DIR, 'places_assistant/flags/blank.png" class="brightflag border1" style="vertical-align:middle" alt=""> ';
				echo '<select id="', $subtagid, '_select" name="', $subtagname, '_select" class="submenuitem" style="height:100%;"';
				echo 'onchange="setPlaceCountry(this.value, \'', $element_id, '\');"';
				echo ' >';
				echo '<option value="???">??? : ', WT_I18N::translate('???'), '</option>';
				foreach ($countries as $country_code=>$country_name) {
					if ($country_code!='???') {
						$txt=$country_code.' : '.$country_name;
						if (utf8_strlen($txt)>40) $txt = utf8_substr($txt, 0, 40).WT_I18N::translate('…');
						echo '<option value="', $country_code, '">', $txt, '</option>';
					}
				}
				echo '</select>';
			} else {
				print_specialchar_link($subtagid, false);
			}
			// clickable map
			if ($i<$icountry or $i>$icounty) echo '<br>';
			else echo '<div id="', $subtagname, '_div" name="', $subtagname, '_div" style="overflow:hidden; height:32px; width:auto; border-width:thin; border-style:none;"><img name="', $subtagname, '_img" src="', $WT_IMAGES['spacer'], '" usemap="usemap" alt="" title="" style="height:inherit; width:inherit;"></div>';
		}
		echo '</div>';
	}

	/**
	 * displays maps in place Hierarchy
	 * @param string $level - level of place Hierarchy
	 * @param string $parent - parent of place Hierarchy
	 */
	public static function display_map($level=0, $parent=array()) {
		if ($level>=1 && $level<=3) {
			$country = $parent[0];
			if ($country == "\xD7\x99\xD7\xA9\xD7\xA8\xD7\x90\xD7\x9C") $country = 'ISR'; // Israel hebrew name
			$country = utf8_strtoupper($country);
			if (strlen($country)!=3) {
				// search country code using current language countries table
				// TODO: use translations from all languages
				foreach (WT_Stats::get_all_countries() as $country_code=>$country_name) {
					if (utf8_strtoupper(WT_I18N::translate($country_name)) == $country) {
						$country = $country_code;
						break;
					}
				}
			}
			$mapname = $country;
			$areaname = $parent[0];
			$imgfile = WT_MODULES_DIR.'places_assistant/'.$country.'/'.$mapname.'.png';
			$mapfile = WT_MODULES_DIR.'places_assistant/'.$country.'/'.$country.'.'.WT_LOCALE.'.htm';
			if (!file_exists($mapfile)) $mapfile = WT_MODULES_DIR.'places_assistant/'.$country.'/'.$country.'.htm';
			if ($level>1) {
				$state = $parent[1];
				$mapname .= '_'.$state;
				if ($level>2) {
					$county = $parent[2];
					$mapname .= '_'.$county;
					$areaname = str_replace("'", "\'", $parent[2]);
				}
				else {
					$areaname = str_replace("'", "\'", $parent[1]);
				}
				// Transform certain two-byte UTF-8 letters with diacritics
				// to their 1-byte ASCII analogues without diacritics
				$mapname = str_replace(array('Ę', 'Ó', 'Ą', 'Ś', 'Ł', 'Ż', 'Ź', 'Ć', 'Ń', 'Š', 'Œ', 'Ž', 'š', 'œ', 'ž', 'Ÿ', '¥', 'µ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'ę', 'ó', 'ą', 'ś', 'ł', 'ż', 'ź', 'ć', 'ń', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ'), array('E', 'O', 'A', 'S', 'L', 'Z', 'Z', 'C', 'N','S', 'O', 'Z', 's', 'o', 'z', 'Y', 'Y', 'u', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'e', 'o', 'a', 's', 'l', 'z', 'z', 'c', 'n', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y'), $mapname);
				// Transform apostrophes and blanks to dashes
				$mapname = str_replace(array("'", ' '), '-', $mapname);
				$imgfile = WT_MODULES_DIR.'places_assistant/'.$country.'/'.$mapname.'.png';
			}
			if (file_exists($imgfile) and file_exists($mapfile)) {
				echo '<table class="width90"><tr><td class="center">';
				$placelist=get_place_list($parent, $level);
				// -- sort the array
				$placelist = array_unique($placelist);
				uasort($placelist, 'utf8_strcasecmp');
				include ($mapfile);
				echo '<img src="', $imgfile, '" usemap="#', $mapname, '" alt="', $areaname, '" title="', $areaname, '">';
				?>
				<script type="text/javascript" src="js/strings.js"></script>
				<script type="text/javascript">
				<!--
				//copy php array into js array
				var places_accept = new Array(<?php foreach ($placelist as $key => $value) echo "'", str_replace("'", "\'", $value), "', "; echo "''"; ?>)
				Array.prototype.in_array = function(val) {
					for (var i in this) {
						if (this[i] == val) return true;
					}
					return false;
				}
				function setPlaceState(txt) {
					if (txt=='') return;
					// search full text [California (CA)]
					var search = txt;
					if (places_accept.in_array(search)) return(location.href = '?level=2<?php echo '&parent[0]=', urlencode($parent[0]), '&parent[1]='; ?>'+search);
					// search without optional code [California]
					txt = txt.replace(/(\/)/,' ('); // case: finnish/swedish ==> finnish (swedish)
					p=txt.indexOf(' (');
					if (p>1) search=txt.substring(0, p);
					else return;
					if (places_accept.in_array(search)) return(location.href = '?level=2<?php echo '&parent[0]=', urlencode($parent[0]), '&parent[1]='; ?>'+search);
					// search with code only [CA]
					search=txt.substring(p+2);
					p=search.indexOf(')');
					if (p>1) search=search.substring(0, p);
					if (places_accept.in_array(search)) return(location.href = '?level=2<?php echo '&parent[0]=', urlencode($parent[0]), '&parent[1]='; ?>'+search);
				}
				function setPlaceCounty(txt) {
					if (txt=='') return;
					var search = txt;
					if (places_accept.in_array(search)) return(location.href = '?level=3<?php echo '&parent[0]=', urlencode($parent[0]), '&parent[1]=', urlencode(@$parent[1]), '&parent[2]='; ?>'+search);
					txt = txt.replace(/(\/)/,' (');
					p=txt.indexOf(' (');
					if (p>1) search=txt.substring(0, p);
					else return;
					if (places_accept.in_array(search)) return(location.href = '?level=3<?php echo '&parent[0]=', urlencode($parent[0]), '&parent[1]=', urlencode(@$parent[1]), '&parent[2]='; ?>'+search);
					search=txt.substring(p+2);
					p=search.indexOf(')');
					if (p>1) search=search.substring(0, p);
					if (places_accept.in_array(search)) return(location.href = '?level=3<?php echo '&parent[0]=', urlencode($parent[0]), '&parent[1]=', urlencode(@$parent[1]), '&parent[2]='; ?>'+search);
				}
				function setPlaceCity(txt) {
					if (txt=='') return;
					var search = txt;
					if (places_accept.in_array(search)) return(location.href = '?level=4<?php echo '&parent[0]=', urlencode($parent[0]), '&parent[1]=', urlencode(@$parent[1]), '&parent[2]=', urlencode(@$parent[2]), '&parent[3]='; ?>'+search);
					txt = txt.replace(/(\/)/,' (');
					p=txt.indexOf(' (');
					if (p>1) search=txt.substring(0, p);
					else return;
					if (places_accept.in_array(search)) return(location.href = '?level=4<?php echo '&parent[0]=', urlencode($parent[0]), '&parent[1]=', urlencode(@$parent[1]), '&parent[2]=', urlencode(@$parent[2]), '&parent[3]='; ?>'+search);
					search=txt.substring(p+2);
					p=search.indexOf(')');
					if (p>1) search=search.substring(0, p);
					if (places_accept.in_array(search)) return(location.href = '?level=4<?php echo '&parent[0]=', urlencode($parent[0]), '&parent[1]=', urlencode(@$parent[1]), '&parent[2]=', urlencode(@$parent[2]), '&parent[3]='; ?>'+search);
				}
				//-->
				</script>
				<?php
				echo '</td><td style="margin-left:30; vertical-align: top;">';
			}
		}
	}
	
	private function getdata() {
		$localized=safe_GET('localized');
		$field=safe_GET('field');
		//echo $field.'|';
		$ctry=safe_GET('ctry', '[A-Za-z._ \'-]+');
		$stae=safe_GET('stae', '[A-Za-z._ \'-]+');
		$cnty=safe_GET('cnty', '[A-Za-z._ \'-]+');
		$city=safe_GET('city', '[A-Za-z._ \'-]+');
		if (empty($ctry)) return;

		$mapname='';
		if (strpos($field, 'PLAC_STAE')!==false) $mapname=$ctry;
		if (strpos($field, 'PLAC_CNTY')!==false) $mapname=$ctry.'_'.$stae;
		if (strpos($field, 'PLAC_CITY')!==false) $mapname=$ctry.'_'.$stae.'_'.$cnty;
		//echo $mapname.'|';
		if (empty($mapname)) return;
		$data = '';
		// user map file
		$filename=WT_MODULES_DIR.'places_assistant/'.$ctry.'/'.$ctry.'.extra.htm';
		$data .= @file_get_contents($filename);
		// localized map file
		$filename=WT_MODULES_DIR.'places_assistant/'.$ctry.'/'.$ctry.'.'.$localized.'.htm';
		$data .= @file_get_contents($filename);
		// default map file
		$filename=WT_MODULES_DIR.'places_assistant/'.$ctry.'/'.$ctry.'.htm';
		$data .= @file_get_contents($filename);
		// remove HTML comments
		$data = str_replace("\r", '',$data);
		$data = preg_replace("/<!--.*?-->\n/is", '', $data);
		// search <map id='...' ...>...</map>
		$p = strpos($data, '<map id="'.$mapname.'"');
		// map not found : use txt file
		if ($p === false) {
			$filename=WT_MODULES_DIR.'places_assistant/'.$ctry.'/'.$mapname.'.txt';
			$data = @file_get_contents($filename);
			$data = str_replace("\r", '',$data);
			$data = preg_replace("/<!--.*?-->\n/is", '', $data);
			$data = str_replace("\n", '|',$data);
			$data = trim($data, '|');
			echo $data;
			exit;
		}
		$data = substr($data, $p);
		$p = strpos($data, '</map>');
		if ($p === false) {
			return;
		}
		$data = substr($data, 0, $p);
		// match : alt='text'
		if (strpos($field, 'PLAC_STAE')!==false) {
			$found = preg_match_all("/setPlaceState\('([^']+)'\)/", $data, $match, PREG_PATTERN_ORDER);
		} elseif (strpos($field, 'PLAC_CNTY')!==false) {
			$found = preg_match_all("/setPlaceCounty\('([^']+)'\)/", $data, $match, PREG_PATTERN_ORDER);
		} elseif (strpos($field, 'PLAC_CITY')!==false) {
			$found = preg_match_all("/setPlaceCity\('([^']+)'\)/", $data, $match, PREG_PATTERN_ORDER);
		}
		if (!$found) {
			$found = preg_match_all('/alt="([^"]+)"/', $data, $match, PREG_PATTERN_ORDER);
		}
		if (!$found) {
			return;
		}
		// sort results
		$resu = $match[1];
		sort($resu);
		$resu = array_unique($resu);
		// add separator
		$data = '';
		foreach ($resu as $k=>$v) {
			if ($v!='default') {
				$data.=$v.'|';
			}
		}
		//$data = str_replace("\n", '|',$data);
		$data = trim($data,'|');
		echo $data;
	}
}
