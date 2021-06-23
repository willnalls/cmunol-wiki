<?php if (!defined('PmWiki')) exit();
/*	Copyright 2009 Hans Bracker. 
	This file is toggle.php; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published
	by the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	
	(:toggle id=divname :) creates a toggle link, which can show or hide 
	a division or other object on the page, for instance a div created with
	
	>>id=divisionname<< 
	text can be hidden/shown 
	>><< 
	
	Necessary parameters:
	
	(:toggle id=divname:) 
	Alternative: (:toggle divname:)
	Alternative with options: 
	(:toggle hide divname:)			initial hide
	(:toggle hide divname button:)	initial hide, button
	(:toggle name1 name2:)			toggle between name1 and name2
	
	Optional parameters:
	
	init=hide			hides the division initially (default is show)
	show=labelname		label of link or button when div is hidden (default is Show)
	hide=labelname		label of link or button when div is shown (default is Hide)
	label=labelname		label of link or button for both toggle states
	id2=objname			second object (div), for toggling betwen first and second object
	set=1				sets a cookie to remember toggle state
*/
# Version date
$RecipeInfo['Toggle']['Version'] = '2018-10-15';

# declare $Toggle for (:if enabled Toggle:) recipe installation check
global $Toggle; $Toggle = 1;

# retrieve cookie
global $CookiePrefix, $pagename;
$current_page_group = PageVar($pagename, '$Group');
$current_page_name = PageVar($pagename, '$Name');
$toggle_cookie_name = "{$CookiePrefix}_toggle_{$current_page_group}_{$current_page_name}";
$toggle_cookie = json_decode($_COOKIE[$toggle_cookie_name],true);

Markup('toggle', 'directives', '/\\(:toggle\\s*(.*?):\\)/i', "ToggleMarkup");
	
# all in one function
function ToggleMarkup($m) {
	global $HTMLFooterFmt, $HTMLStylesFmt, $ToggleConfig, $ToggleLinks, $UploadUrlFmt, $UploadPrefixFmt, $toggle_cookie_name, $toggle_cookie;
	extract($GLOBALS['MarkupToHTML']);
	SDVA($ToggleConfig, array(
		'init' => 'show',			// initial state of element (visible)
		'show' => XL("Show"),		// link text 'Show'
		'hide' => XL("Hide"),		// link text 'Hide'
		'ttshow' => XL("Show"),		// tooltip text 'Show'
		'tthide' => XL("Hide"),		// tooltip text 'Hide'
		'id' => '',					// no default div name
		'id2' => '',				// no default div2 name
		'group' => '',				// no default group (class) name
		'display' => 'block',		// default to display:block;
		'set' => false,				// set no cookie to remember toggle state
		'printhidden' => true, 		// hidden divs get printed
		'nojs' => 0,				// in no jsbrowser links are not shown, initial hidden divs are shown
	));	 

	$HTMLStylesFmt['toggle'] = 
		" @media print { .toggle { display: none; } } \n" . 
		".toggle img { border: none; } \n";
	 
	# javascript for toggling and cookie setting
	$HTMLFooterFmt['toggleobj'] = "
<script type='text/javascript'><!--
	window.toggleData = { };
	window.toggleData.toggle_cookie_name = '{$toggle_cookie_name}';
	
	function toggleObj(id_of_element_to_toggle) {
		// Retrieve the Toggle state/data for the specified element.
		var T = window.toggleData[id_of_element_to_toggle];
		
		// If we're *showing* an element that's part of a defined group,
		// hide all the elements of the group first (including the specified
		// element itself, which will be re-shown immediately below).
		if (T.group != '' && T.new_state_to_toggle_to == 'show') {
			// Get all elements of the given class.
			document.querySelectorAll(`.\${T.group}`).forEach(function(element_in_group) { setToggleState(element_in_group, 'hide') });
		}	
		
		// Set the new state of the element.
		setToggleState(document.getElementById(id_of_element_to_toggle), T.new_state_to_toggle_to);
		
		// Toggle the alternate element, if any.
		// (T.new_state_to_toggle_to has now been reversed, by the line above.)
		if (T.id_of_alternate_element != '') setToggleState(document.getElementById(T.id_of_alternate_element), T.new_state_to_toggle_to, T.display);
	}
	
	function setToggleState(element, state, display = null) {
		// Retrieve the Toggle state/data for the specified element (if any).
		var T_e = window.toggleData[element.id];
		
		// Update the element's display.
		element.style.display = (state == 'show') ? (T_e ? T_e.display : display) : 'none';

		// If the element has an entry in the saved data 
		// (i.e. if it has a toggle element of its own),
		// update that saved data, and also update the toggle link/button.
		if (T_e) {
			// Set the new state, and update the saved data for the element.
			T_e.new_state_to_toggle_to = (state == 'show') ? 'hide' : 'show';			

			// Adjust the toggle link for the element.
			var label = (state == 'show') ? T_e.toggle_link_label_in_visible_state : T_e.toggle_link_label_in_hidden_state;
			var tooltip = (state == 'show') ? T_e.toggle_link_tooltip_in_visible_state : T_e.toggle_link_tooltip_in_hidden_state;
			document.getElementById(`\${element.id}-tog`).innerHTML = 
				(T_e.is_button == 1) ?
					`<input type='button' class='inputbutton togglebutton' value='\${label}' onclick=\"javascript:toggleObj('\${element.id}')\" />` : 
				`<a class='togglelink' title='\${tooltip}' href=\"javascript:toggleObj('\${element.id}')\">\${label}</a>`;
		
			// If cookie setting is enabled, save the new state in a cookie.
			if (T_e.set_cookie == 1) updateToggleCookie(element.id, state);
		}
	}
	
	function updateToggleCookie(element_id, state) {
		// Retrieve...
		var toggleCookieName = window.toggleData.toggle_cookie_name;
		var toggleCookieNameRegex = new RegExp(`\${toggleCookieName}=([^;]+)`);
		var toggleCookieData = document.cookie.match(toggleCookieNameRegex);
		var toggleElementStates = toggleCookieData ? JSON.parse(toggleCookieData[1]) : { };
		
		// Modify...
		toggleElementStates[element_id] = state;
		
		// Store.
 		document.cookie = `\${toggleCookieName}=\${JSON.stringify(toggleElementStates)}; path=/`;
	}	
--></script>\n";
	
	$opt = ParseArgs($m[1]); 

	// Get parameters without keys.
	if (is_array($opt[''])) {
		while (count($opt['']) > 0) {
			$parameter = array_shift($opt['']);
			if($parameter == 'button') $opt['button'] = 1;
			elseif($parameter == 'hide') $opt['init'] = 'hide';
			elseif($parameter == 'show') $opt['init'] = 'show';
			elseif(!isset($opt['id'])) $opt['id'] = $parameter;
			elseif(!isset($opt['id2'])) $opt['id2'] = $parameter;		 
		}
	}
	
	// Fill in un-specified parameters with defaults.
	$opt = array_merge($ToggleConfig, $opt);

	// Retrieve the ids of both the primary and (if specified) alternate elements.
	// (the 'div' options are for backwards compatibility with ShowHide and ToggleLink recipes)
//	The first version of these 2 lines is for PHP 5.3+; the second, for earlier versions
// 	$id = $opt['div'] ?: $opt['id'];
// 	$id2 = $opt['div2'] ?: $opt['id2'];
	$id = $opt['div'] ? $opt['div'] : $opt['id'];
	$id2 = $opt['div2'] ? $opt['div2'] : $opt['id2'];
	if ($id == '') return "//!Error:// no object id specified!"; 

	// Verify that the ids of both elements do not contain special characters
	// which are forbidden in CSS identifiers. (Among other things, this forbids
	// quotation marks, which prevents Javascript injection attacks via ids.)
	$CSS_forbidden_characters_regex = "/[!\\\"#$%&'()*+,\\.\\/\\:;<=>?@[\\\\\]^`{|}~]/";
	if (preg_match($CSS_forbidden_characters_regex, $id) ||
		preg_match($CSS_forbidden_characters_regex, $id2))
		return Keep("<span style='color:red; font-weight:bold;'>Invalid ID specified for element!</span>");
	
	// Value for the 'display' CSS property. Affects both the element and the alternate element.
	$display = $opt['display'];
	
	// Set labels for (both states of) the toggle link/button.
	$labels = array();
//	The first version of these 2 lines is for PHP 5.3+; the second, for earlier versions
// 	$labels['show'] = $opt['label'] ?: ($opt['lshow'] ?: $opt['show']);
// 	$labels['hide'] = $opt['label'] ?: ($opt['lhide'] ?: $opt['hide']);
	$labels['show'] = $opt['label'] ? $opt['label'] : ($opt['lshow'] ? $opt['lshow'] : $opt['show']);
	$labels['hide'] = $opt['label'] ? $opt['label'] : ($opt['lhide'] ? $opt['lhide'] : $opt['hide']);

	// Transform label text into image tags, if appropriate.
	// (since we won't be putting the label text through pmwiki markup 
	//  processing, which would normally process image attach links)
	// (but maybe we should? TODO: investigate this)
	// Also encode apostrophes (for non-images).
	$ipat = "/\.png|\.gif|\.jpg|\.jpeg|\.ico/";
	foreach($labels as $k => $val) {
		if(preg_match($ipat, $val)) {
			// Check for image, make image tag
			$prefix = (strstr($val, '/')) ? '/' : $UploadPrefixFmt; 
			$path = FmtPageName($UploadUrlFmt.$prefix, $pagename);
			$labels[$k] = "<img src=$path/$val title={$opt['tt'.$k]}&nbsp;$id />";
			$opt['button'] = '';
		} else {
			// Apostrophe encoding
			$labels[$k] = str_replace("'","&rsquo;",$val);
		}
	}
	
	// If the element is part of a defined group, then hide it, unless it's explicitly set to be initially-shown.
 	if ($opt['group'] != '' && $opt['init'] != 'show')
 		$opt['init'] = 'hide';

	// If set=1 (i.e. cookie setting enabled), then check if a cookie is set;
	// if it is, read the element's initial state from the cookie.
	if($opt['set'] == 1)
	//	The first version of this line is for PHP 5.3+; the second, for earlier versions
// 		$opt['init'] = $toggle_cookie[$id] ?: $opt['init'];
		$opt['init'] = $toggle_cookie[$id] ? $toggle_cookie[$id] : $opt['init'];
	
	/* OPTION RETRIEVAL ENDS; NOW PROCESSING BEGINS */

	// Set initial state, and update labels and target state 
	// (for when user clicks the toggle/link button).
	$display_property_value = ($opt['init'] == 'show') ? $display : 'none';
	if ($toggle_cookie[$id2] == false) $alternate_element_display_property_value = ($opt['init'] == 'show') ? 'none' : $display;
	$label = ($opt['init'] == 'show') ? $labels['hide'] : $labels['show'];
	$tooltip = ($opt['init'] == 'show') ? $opt['tthide'] : $opt['ttshow'];
	$state_to_toggle_to = ($opt['init'] == 'show') ? 'hide' : 'show';

	// Open script block.
	$HTMLFooterFmt[] = "<script type='text/javascript'><!--\n";

	// Set initial state of element.
	$HTMLFooterFmt[] = "	if (element = document.getElementById('{$id}')) { element.style.display = '{$display_property_value}'; }\n";
	$HTMLStylesFmt[] = "#{$id} { display: {$display_property_value}; } \n";
	
	// Set initial state of alternate element.
	if ($id2) { 
		$HTMLFooterFmt[] = "	if (element = document.getElementById('{$id2}')) { element.style.display = '{$alternate_element_display_property_value}'; }\n";
		$HTMLStylesFmt[] = " #{$id2} { display: {$alternate_element_display_property_value}; } \n";
	}
	
	// Set separate styles for print view, if 'printhidden' option is set.
	if ($opt['printhidden'] == 1) { 
		$HTMLStylesFmt[] = "@media print{ #{$id} { display: {$display}; } } \n";
		if ($id2)
			$HTMLStylesFmt[] = "@media print { #{$id2} { display: {$display}; } } \n";
	}

	// Save the Toggle state/data for this element.
	$HTMLFooterFmt[] = 
	"	window.toggleData['{$id}'] = { 
		'new_state_to_toggle_to': '{$state_to_toggle_to}',
		'toggle_link_label_in_hidden_state': '{$labels['show']}',
		'toggle_link_label_in_visible_state': '{$labels['hide']}',
		'toggle_link_tooltip_in_hidden_state': '{$opt['ttshow']}',
		'toggle_link_tooltip_in_visible_state': '{$opt['tthide']}',
		'id_of_alternate_element': '{$id2}',
		'display': '{$display}',
		'is_button': '{$opt['button']}',
		'group': '{$opt['group']}',
		'set_cookie': '{$opt['set']}'
		};\n";
	
	// Close script block.
	$HTMLFooterFmt[] = "--></script>\n";

	// Construct toggle link or button (later it is modified with javascript).
	$out = "<span id='{$id}-tog' class='toggle'>";
	if ($opt['button'] == 1) {
		$out .= 
			($opt['nojs'] == 0) ? 
			"<input type='button' class='inputbutton togglebutton' value='{$label}' onclick=\"javascript:toggleObj('{$id}')\" />" :
 			"<input type='button' class='inputbutton togglebutton' value='{$label}' />";
	} else {
		$out .= 
			($opt['nojs'] == 0) ? 
			"<a class='togglelink' title='{$tooltip}' href=\"javascript:toggleObj('{$id}')\">{$label}</a>" : 
			"<a class='togglelink' title='{$tooltip}'>{$label}</a>";
	}
	$out .= "</span>";
	return Keep($out);
}
#EOF