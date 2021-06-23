<?php if (!defined('PmWiki')) exit();
/*
+----------------------------------------------------------------------+
| Copyright 2015 Hans Bracker. http://www.softflow.co.uk
| This program is free software; you can redistribute it and/or modify
| it under the terms of the GNU General Public License, Version 2, as
| published by the Free Software Foundation.
| http://www.gnu.org/copyleft/gpl.html
| This program is distributed in the hope that it will be useful,
| but WITHOUT ANY WARRANTY; without even the implied warranty of
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
| GNU General Public License for more details.
+----------------------------------------------------------------------+
| fox.php for PmWiki
| A form processing script for PmWiki 2.2.76 and above, and PHP 5.4 and above
| Instructions on how to use it see http://softflow.co.uk/design/FoxDocumentation/
+----------------------------------------------------------------------+
| Other contributors:
| * Nils Knappmeier (nk@knappi.org): original script adddeleteline2.php
| * John Rankin: AccessCode code from commentbox.php
| * Petko Yotov (2006): original deletelink directive
| * Patrick R. Michaud: permission check, input validation and other ideas
| * Feral: FoxFilter hook and other improvements
| * Stirling Westrup (2007): code overhaul with a rewrite of TemplateEngine
| *        and associated functions and many function improvements
+----------------------------------------------------------------------+
*/

$RecipeInfo['Fox']['Version'] = '2020-07-27';

$FmtPV['$FoxVersion'] = "'{$RecipeInfo["Fox"]["Version"]}'";

// switch on echos for debugging, can be set in config.php
SDV($FoxDebug, 0); //echo process: 1 - basic; 2 - +input, 3 - +targets, 4 - early target arrays,
					// 5 - +template, +new text, +ptvs, 6 - +full text, 7 - +marks

//in case page name is not resolved
$pagename = ResolvePageName($pagename);

// auth permission level for adding and deleting posts.
// 'edit': needs edit permission.
// 'read': needs read permission (can post to edit protected pages)
// 'ALWAYS':no permission needed, can also post to 'read' protected pages.
// $FoxPagePermissions must also be set to include page(s) as permitted targets
SDV($FoxAuth, 'edit');
SDV($FoxConfigPageFmt, "$SiteAdminGroup.FoxConfig"); //default config page for page permission patterns
SDV($FoxFormsFmt, "$SiteGroup.FoxForms"); //default page + section for Fox form templates
SDV($FoxTemplatePageFmt, "$SiteGroup.FoxTemplates#null");
SDV($EnableFoxUrlInput, false); //set to true to allow input via url parameters (php $_GET array)
SDV($EnablePostDirectives, false); //set to true to allow posting of directives of form (: :)
SDV($EnableAccessCode, false);  //set to true to enable accesscode check (needs access code form fields as well)
SDV($EnableFoxDefaultMsg, true);
//to stop fatal php timeout errors set two seconds less than php.ini: max_execution_time 30sec default
SDV($FoxProcessTimeMax, 28); //processing time limit in seconds for UpdatePages process.
SDV($FoxClearPTVFmt, 'NULL'); //string used as input to clear a PTV.
SDV($EnableFoxPTVDelete, false); //set to true to allow PTVs to be deleted by changing value for a PTV to $FoxPTVDeleteKey
SDV($FoxPTVDeleteKey, 'ERASEPTV'); //string to use for erasing (deleting) PTVs, if $EnableFoxPTVDelete = true;
SDV($FoxCheckErrorMsg, '$[Please enter valid input!]');
SDV($FoxEnablePTVRefresh, 1); //unsets page cache for PTVs, so PTVs will refresh without need for full page refresh 
SDV($FoxPTVArraySeparator, " "); //separates posted elements of a PTV array.

##security check for FoxConfig page location
if ($SiteGroup != $SiteAdminGroup)
if (PageExists('$SiteGroup.FoxConfig') && !PageExists('$SiteAdminGroup.FoxConfig'))
	echo "Security update: Fox detected page <a href='$ScriptUrl?n=$SiteGroup.FoxConfig'>$SiteGroup.FoxConfig</a><br />
	Please move the page to <a href='$ScriptUrl?n=$SiteAdminGroup.FoxConfig?action=edit'>$SiteAdminGroup.FoxConfig</a>! ";

// Search path for fox.css files, if any.
if (!isset($FarmPubDirUrl)) $FarmPubDirUrl = $PubDirUrl;
SDV($FoxPubListFmt, array (
       "pub/fox/fox.css"        => "$PubDirUrl/fox/fox.css",
       "$FarmD/pub/fox/fox.css" => "$FarmPubDirUrl/fox/fox.css" ));

# load special styles for edit/delete button and links and for message classes
foreach((array)$FoxPubListFmt as $k=>$v) {
  if (file_exists(FmtPageName($k,$pagename))) {
      $HTMLHeaderFmt['fox'][] =
      	"<link rel='stylesheet' type='text/css' href='$v' media='screen' />\n";
  		break;
  }
} //}}}

//conditionals for edit forms
$Conditions['foxpreview'] = '(boolean)$_POST["preview"]';
$Conditions['foxcheck'] = 
$Conditions['foxerror'] = 'FoxCheckErrorCond($condparm) == 1';
function FoxCheckErrorCond($arg) {
	global $FoxCheckError;
	if (!$arg) return (boolean)$FoxCheckError;
	$arg = ParseArgs($arg);
	if ($arg[''][0] != $_POST["foxname"]) return '';
	if (in_array($arg[''][1], $FoxCheckError)) return 1;
} //}}}

## (:foxform #section :) or (:foxform FormPage#section :) for loading Fox forms
## #section is retrieved from page(s) given with $FoxFormsFmt.
## $FoxFormsFmt can be array of page names. 
Markup('foxform', '>if', '/\\(:foxform\\s+(\\S.*?):\\)/i', "FoxLoadForm");
function FoxLoadForm($m) {
	global $FoxFormsFmt;
	extract($GLOBALS['MarkupToHTML']);
	$list = (is_array($FoxFormsFmt)) ? $FoxFormsFmt : array($FoxFormsFmt);
	$text = RetrieveAuthSection($pagename, $m[1], $list, 'read');
	if (!$text) return "%red%Fox form [[$args]] is missing";
	return PRR(PVSE($text));
} //}}}

//(:fox formname ....:) main form markup, starts a fox form
Markup('fox','directives','/\(:fox ([\\w]+)\\s?(.*?):\)/i',"FoxMarkup");
## Creates the HTML code for the (:fox name [placement] [parameters]:) directive
function FoxMarkup($m) {
	extract($GLOBALS['MarkupToHTML']);
	$PageUrl = PageVar($pagename, '$PageUrl');
	static $cnt = 0; $cnt++;
	$defaults = array();
	$name = $m[1];
	$args = ParseArgs($m[2]);
	if (isset($args[''][0])) $args['put'] = $args[''][0];
	$opt = array_merge($defaults, $args);
	if (isset($opt['redirect'])) { $opt['redir'] = $opt['redirect']; unset($opt['redirect']);}
	$javacheck = (isset($opt['formcheck']) ? FoxJSFormCheck($opt['formcheck']) : '');
	$out = $javacheck;
	$out.= "<form  name='$name' action='{$PageUrl}' method='post' ".
		($opt['upload']==1||$opt['uptarget'] ? "enctype='multipart/form-data'" : "").
		(isset($opt['formcheck']) ? "onsubmit=\"return checkform(this);\">" : ">").
		"<input type='hidden' name='foxpage' value='$pagename' />".
		"<input type='hidden' name='action' value='foxpost' />".
		"<input type='hidden' name='foxname' value='$name' />"; 
	foreach ($opt as $key => $val) {
		if(!is_array($val))
			$out.= "<input type='hidden' name='".$key."' value='".$val."' />";
	}
	return Keep($out);
} //}}}

//(:foxtemplate "...":) (:foxpreviewtemplate "...":)
Markup('foxtemplate','<input','/\\(:fox(display|preview|)template\\s+"(.*?)\\s*"\\s*:\\)/', "FoxTemplateMarkup");
# Creates the HTML code for (:foxtemplate "templatestring":) as hidden input form field
# $m[2] is the template string
function FoxTemplateMarkup($m) {
   $t = ($m[1]=='display' || $m[1]=='preview') ? 'preview' : '';
    return Keep('<input type="hidden" name="fox'.$t.'template" value="'.htmlspecialchars($m[2]).'"/>');
} //}}}

//(:fox-post ...:) (:fox-add ...:) (:fox-copy ...:) (:fox-replace ...:) (:fox-ptv ...:)
//(:foxpost ...:) (:foxadd ...:) (:foxcopy ...:) (:foxreplace ...:) (:foxptv ...:)
# markup for multi-page processing, can be used multipe times in form
Markup('foxpost','directives','/\(:fox-?(post|add|copy|replace|ptv|mail)\\s+(.*?):\)/i',"FoxPostMarkup");
# create hidden HTML input tags for template and target pages, for foxpost and foxcopy markup
function FoxPostMarkup($m) {
	$act = $m[1]; 
	if ($act=='post') $act = 'add'; //'post' deprecated in favour of 'add'
	$defaults = array('if'=>'', 'put'=>'','mark'=>'', 'endmark'=>'', 'ptvfields'=>'', 'ptvfmt'=>'','ptvclear'=>'','foxtemplate'=>''); //placeholders
	$put_names = array('top','bottom','prepend','append','below','above','belowform','aboveform','string','marktomark');
	$args = ParseArgs($m[2],'(?>([-\\w]+(?:\.[-\\w]+)?(?:\\w#[-.\\w]*)?(?:\\#[-.\\w]*)?)(?:=&gt;|[=:]))');
	$opt = array_merge($defaults, $args);
	$opt[''] = (array)@$opt[''];
	$opt['foxaction'] = $act;
	foreach ($opt[''] as $i => $p) {
		if ($p =='') continue;
		if (in_array($p, $put_names) && !isset($args['put']))
			$opt['put'] = $p;
		else $param[] = $p;
	}
	if (isset($param)) $opt['foxparam'] = implode(',',$param);
	if ($act=='ptv') {
		if (isset($opt['foxparam']) && !(isset($opt['target']) OR isset($opt['ptvtarget'])))
			$opt['target'] = $opt['foxparam'];
		if (isset($opt['ptvtarget'])) $opt['target'] = $opt['ptvtarget'];
	}	
	unset($opt[''],$opt['#']);
	$keys = $opt;
	foreach ($keys as $k => $v)
		if (preg_match('/(^[A-Z0-9].*)|(^\\w+?\\..+)/',$k)) unset($keys[$k]);
	unset($keys['target'], $keys['template']);
	$out = '';
	foreach ($opt as $key => $val) {
		//template=>target parameters. template in key>=val must start Upper case or numeral or contain a dot
		if (preg_match('/(^[A-Z0-9].*)|(^\\w+?\\..+)/',$key)) {
			$out .= '<input type="hidden" name=":template[]" value="'.PVSE($key).'"/>';
			$out .= '<input type="hidden" name=":target[]" value="'.PVSE($val).'"/>';
			foreach($keys as $d => $val)
				$out .= '<input type="hidden" name=":'.$d.'[]" value="'.PVSE($val).'"/>';
		}
		//target= parameter
		elseif ($key=='target') {
			$targets = explode(",", $val); //can be comma separated list
			$templates = array();
			if(isset($opt['template'])) {
				$templates = explode(",", $opt['template']); //can be comma separated list
				//use last template for any missing templates
				while( count($templates)<count($targets) )
					$templates[] = @end($templates);
			}
			//make :target[] :template[] array elements, add other keys to get proper index mapping
			foreach($targets as $i => $tgt) {
				if (!isset($templates[$i])) $templates[$i] = '';
				$out .= '<input type="hidden" name=":target[]" value="'.$tgt.'"/>';
				$out .= '<input type="hidden" name=":template[]" value="'.$templates[$i].'"/>';
				foreach($keys as $d => $v)
					$out .= '<input type="hidden" name=":'.$d.'[]" value="'.PVSE($v).'"/>';
			}
		}
	}
	return Keep($out);
} //}}}

// (:foxcheck name [match='wikiwildcardpattern'] [regex='regexpattern'] [if='condition'] [msg='error message'] :)
Markup('foxcheck','directives','/\(:fox-?(check)\\s+(.*?):\)/i',"FoxCheckMarkup");
function FoxCheckMarkup($m) {
	static $idx = 0;
	$opt = ParseArgs($m[2]); #show($opt,'opt');
	$opt[''] = (array)$opt[''];
	$opt['name'] = isset($opt['name']) ? $opt['name'] : array_shift($opt['']);
	if (!isset($opt['match']) && $opt['']) $opt['match'] = array_shift($opt['']); 
	unset($opt['#'], $opt['']);
	$out = '';
	foreach ($opt as $key => $val) {
		if ($val=='') continue;
		$out .= '<input type="hidden" name="chk_'.$key.'['.$idx.']" value="'.PHSC($val).'"/>';
	}
	$idx++;
	return Keep($out);
} //}}}

// (:foxmessage [form] [name]:) (:foxdisplay [form] [name]:)
Markup('foxdisplaymessage','directives','/\\(:fox-?(message|display|preview)s?\\s*(.*?)\\s*:\\)/',	"FoxDisplayMarkup");
function FoxDisplayMarkup($m) {
	global $FoxMsgFmt, $FoxDisplayFmt;
	extract($GLOBALS['MarkupToHTML']);
	if (!$FoxMsgFmt && !$FoxDisplayFmt) return '';
	$opt = ParseArgs($m[2]);
	$opt[''] = (array)@$opt[''];
	$form = isset($opt['form']) ? $opt['form'] : array_shift($opt['']);
	$name = isset($opt['name']) ? $opt['name'] : array_shift($opt['']);
	$msg = '';
	if($FoxMsgFmt) { 
		if (!$form && !$name) $msg = implode("\\\\\n", $FoxMsgFmt); //show all messages
		elseif ($form==@$_REQUEST['foxname']) {
			if (isset($name) ) $msg = @$FoxMsgFmt[$name]; //show error message from check 'name'
			elseif (@$opt['list']=='nocheck')  //show non-name messages
				foreach($FoxMsgFmt as $k => $v) { if (is_int($k)) $msg .= $v; }
			elseif (@$opt['list']=='check')  //show name messages
				foreach($FoxMsgFmt as $k => $v) { 
					if (is_int($k)) continue;
					$msg .= $v;
				}				
			else $msg = implode("\\\\\n", $FoxMsgFmt); //show all messages
		} 
	}
	if ($m[1]=='display' || $m[1]=='preview' || @$opt['list']=='display') 
		if (!$form || $form==@$_REQUEST['foxname']) 
			if(!is_array($FoxDisplayFmt))
				$msg = $FoxDisplayFmt;
			else if ($form==@$_REQUEST['foxname'] && isset($name))
				$msg = @$FoxDisplayFmt[$name];
	$out = MarkupToHTML($pagename, $msg); 
	//strip p tags from beginning and end, trim end space
	$out = rtrim(preg_replace("/^<p>(.*?)<\\/p>$/s","$1", $out));
	return Keep($out);
} //}}} 

# (:foxend name:)
Markup('foxendform','directives','/\(:foxend(\\s[\\w]+):\)/', "</form>");
# (:foxprepend:) and (:foxappend:) just vanish because they are only used later in  FoxInsertText
Markup('foxaprepend','directives','/\(:fox(ap|pre)pend\\s*(.*?)\\s*:\)/','');
# (:foxallow:) for permission check: if present will grant page permission
Markup('foxallow','directives','/\(:foxallow\\s*(.*?)\\s*:\)/','');
# #foxbegin# and #foxend# invisible markers used by foxdelete links and buttons
Markup('foxentry','<fulltext','/#fox(begin|end)( [-:\\w]+)?#/','');

# add FoxEditTemplate to $EditFunctions for FoxHandlePost (foxaction newedit)
if($action=='edit' && @$_REQUEST['foxtemptext']==1)
   array_unshift($EditFunctions, 'FoxEditTemplate');

## provide page text for ?action=edit&foxtemptext=1
function FoxEditTemplate($pagename, &$page, &$new) {
   if (@$new['text'] > '') return '';
   if (@$_REQUEST['foxtemptext'])  {
      if ($_SESSION["FoxTempPageText"] > '') $new['text'] = $_SESSION["FoxTempPageText"];
         return '';
   }
} //}}}

# add action foxpost
$HandleActions['foxpost'] = 'FoxHandlePost';

## Main function called with action=foxpost
function FoxHandlePost($pagename, $auth) {
   global $InputValues, $EnableFoxUrlInput, $EnableFoxDefaultMsg, $EnablePostDirectives,
   		$IsPagePosted, $FoxDebug, $FmtV, $FoxMsgFmt;
	FoxTimer($pagename, 'FoxHandlePost: begin');

   //get arguments from POST and GET
   if ($EnableFoxUrlInput==true)
   	$fx = RequestArgs();
	else $fx = RequestArgs($_POST);
	
	//get arguments from FILES	
	foreach($_FILES as $n => $upfile) { 
		if ($upfile['name']=='') continue;
		foreach($upfile as $k => $v)
			if (!$fx[$n.'_'.$k]) $fx[$n.'_'.$k] = $v;
		//save file extension
		$fx[$n.'_ext'] = end(explode(".", $upfile['name']));
	}	
	
	//store current values to redisplay, in case we abort.
	foreach ($fx as $k=>$v) {
		if (is_array($v)) foreach ($v as $kk=>$vv)
			$InputValues[$k][$kk] = htmlspecialchars($vv,ENT_NOQUOTES);
		else $InputValues[$k] = htmlspecialchars($v,ENT_NOQUOTES);
	}	

	//abort if non-permitted input, i.e. if GET input is not allowed
	if ($fx['action']!='foxpost') FoxAbort($pagename, "$[Error: input not permitted]");

	//use foxpage as abort target.
	if(isset($fx['foxpage']))
		$pagename = $fx['foxpage'];

	//initialise
	$redirname = $pagename;

	//preprocess fields with FoxFilter, which calls external filter functions
   if (isset($fx['foxfilter']))
      FoxFilter($pagename, $fx);
      
	//initialising preview
   if (isset($fx['preview']) || isset($fx['foxdisplay'])) { 
   	//see if we got a preview template
   	if ($fx['foxpreviewtemplate']) { $fx['foxtemplate'] = $fx['foxpreviewtemplate']; }
   	else if ($fx['previewtemplate']) { $fx['template'] = $fx['previewtemplate']; }
   	//for cases not called by a foxedit form
   	if (!isset($_SESSION['foxedit'][$pagename])) { 
   		$fx['foxaction'] = 'display';
   		$fx['target'] = $pagename;
   		unset($fx['redir']);
   		unset($fx[':target']);
   		unset($fx['ptvtarget']);
   	}
   }

	//DEBUG//
	if($FoxDebug==2) show($fx,'$fx early'); 

   //make foxgroup input suitable for group name, add foxgrouptitle to preserve original input
   if (isset($fx['foxgroup'])) {
   	$fx['foxgrouptitle'] = $fx['foxgroup'];
   	$fx['foxgroup'] = FoxWikiWord($fx['foxgroup']);
   }

	//sanitize posted directives and markup expressions
	if ($EnablePostDirectives==false)
		FoxDefuseMarkup($pagename, $fx);

	//do {$$var} input field replacements and process {$$(expre ...)} markup expressions
	FoxInputVarReplace($pagename, $fx);
	//DEBUG//
	if($FoxDebug>2) show($fx,'$fx after Input Var Replace'); 

	//make ptv array from ptv_ fields
	FoxPTVFields($pagename, $fx);
   
	//check form input as set by (:foxcheck ..:) markup
	FoxInputCheck($pagename, $fx);
	
	//build list of targets as array with associated parameters
	$to = FoxTargetList($pagename, $fx);
	$targets = array_keys($to);

  //check for foxnotify input to do notifications (needs FoxNotify installed)
  if (isset($fx['foxnotify'])) {
      global $FoxNotifyLists, $FoxNotifyListsGroup; 
      $FoxNotifyLists = array();
      if (is_array($fx['foxnotify'])) {
         foreach($fx['foxnotify'] as $n)
            $FoxNotifyLists[] = $FoxNotifyListsGroup.".".$n;
      }
      else $FoxNotifyLists[] = $FoxNotifyListsGroup.".".$fx['foxnotify'];
  }

	//set $redirectname for redirect, but inhibit redirect when debugging, to see the debug echos
	if (isset($fx['redir']) AND $FoxDebug==0)
	$urlfmt = FoxRedirectFmt($pagename, $targets, $fx);

	if ($fx['urlfmt']) $urlfmt = $fx['urlfmt'];

	//cancel
	if (isset($fx['cancel'])) {
		$urlfmt = (isset($fx['cancelredirect']))? FoxRedirectFmt($pagename, $targets, $fx) : '';
		Redirect($redirname, $urlfmt);
		exit; 
	}
		
   //check various genaral security restrictions (page permissions are checked later)
   //check for and possibly defuse posted markup 
   FoxSecurityCheck($pagename, $targets, $fx);

	//main page processing
	$counter = FoxProcessTargets($pagename, $fx, $to);
	
	//upload files
	if ($fx['uptarget'])
		FoxPostUpload($pagename, $fx);
	
	$xtime = FoxTimer($pagename,'FoxHandlePost: end');
	if( $EnableFoxDefaultMsg==1 && $counter>1 )
		$FoxMsgFmt[] = "$counter pages processed in $xtime seconds";

	if(!isset($urlfmt)) {
		FoxFinish($redirname, $fx, '');
	}
	else Redirect($redirname, $urlfmt);
} //}}}

# create url for redirections
function FoxRedirectFmt ($pagename, $targets, $fx) {
	global $EnablePathInfo, $ScriptUrl;
	$arg = ''; $anch = '';
	$redir =  ($fx['redir'])? $fx['redir'] : $fx['cancelredirect'];
	if (substr($redir,0,4)=="http") 
		$urlfmt = $redir;
	else {
		if($fx['redir']==='1') {
			$pname = end($targets);
		}
		else {
			$rr = explode("?", $redir); 
			$aa = explode("#", $rr[0]);
			if ($aa[1]) $anch = "#".$aa[1];
			$pname = FoxGroupName($pagename, $fx, $rr[0]);
			if ($rr[1]) $arg = "?".$rr[1]; 
		}
		$pname = str_replace(".","/",$pname);
		//set urlfmt for redirect
		$urlfmt = (IsEnabled($EnablePathInfo, 0) ? $ScriptUrl."/".$pname.$anch.$arg : $ScriptUrl."?n=".$pname.$anch.$arg );
	}
	return $urlfmt;	
} //}}}

## create arrays from special fields & build target array
function FoxTargetList ($pagename, &$fx) {
	global $FoxDebug; if($FoxDebug>3) echo "<br /> FoxTargetList>"; //DEBUG//

	//assign current page as target if no target is specified, but template is given. 
	//exclude this for dangerous actions to prevent overwriting form page 
	if (!isset($fx[':target']) &&  !isset($fx['target']) && !isset($fx['newedit'])) 
		if ((isset($fx['template']) && !$fx['template']==0) || isset($fx['foxtemplate']))
			if ($fx['foxaction']!='copy' && !($fx['foxaction']=='replace' && $fx['put']=='overwrite'))
				$fx['target'] = $pagename;
				
	//'display' by default has current page as target 
	if ($fx['foxaction']=='display' && !isset($fx['target'])) $fx['target'] = array($pagename);	
	
	//no template, foxtemplate or foxpreviewtemplate parameter -> unset target 
	if (isset($fx['target']) && !isset($fx['template']) && !isset($fx['foxtemplate']) && !isset($fx['foxpreviewtemplate'])) {
		unset($fx['target']);
		if (!isset($fx[':target']) && !isset($fx['newedit']))
			FoxAbort($pagename, "$[Error: no template specified!]");
	}
		
	if (isset($fx['foxcopy']) && $fx['foxcopy']===1) 
		$fx['foxaction'] = 'copy';
		
	//create upload target
	if (isset($fx['uptarget'])) 
		$fx['uptarget'] = FoxGroupName($pagename, $fx, $fx['uptarget']);
	elseif ($fx['upload']==1) {
		if (isset($fx['target'])) {
			$tgts = explode(',',$fx['target']); 
			$fx['uptarget'] = FoxGroupName($pagename, $fx, $tgts[0]);
		}
		else $fx['uptarget'] = $pagename;
	}

	//create newedit target, will be used as last element in target array
	$new_to = array();
	if (isset($fx['newedit'])) {
		$new_to[0]['target'] = $new_to[0]['fulltarget'] = FoxGroupName($pagename, $fx, $fx['newedit']);
		$new_to[0]['foxaction'] = 'newedit';
		if ($fx['template']) {
			$tempArr = explode(',',$fx['template']);
			$new_to[0]['template'] = end($tempArr);
		}
		elseif ($fx['foxtemplate']) $new_to[0]['template'] = 'foxtemplate';
		else $new_to[0]['template'] = 0;
	} 	
		
	//make arrays from keys fields which could be arrays or lists for making arrays
	//assign values to targets, csv lists: if no more value use previous one
	$csv_to = array();
	$keys = array('foxaction','target','template','put','mark','endmark',
						'foxsuccess','foxfailure','foxtemplate');
	if (isset($fx['target']) && $fx['target']!='') {
		foreach ($keys as $n) {
			if(isset($fx[$n]) && $fx[$n]!='') {
				$kp[$n] = array();
				if(is_array($fx[$n])) $kp[$n] = $fx[$n];
				elseif ($n=='target' || $n=='template' || $n=='put') 
					$kp[$n] = explode(",", $fx[$n]);
				else $kp[$n] = array($fx[$n]);
			}
		}
		unset($keys['target']);
		foreach($keys as $k) {
			if (isset($kp['target'])) {
				foreach($kp['target'] as $i => $tg) {
					$csv_to[$i]['target'] = $tg;
					if (isset($kp[$k][$i])) $csv_to[$i][$k] = $kp[$k][$i];
					elseif (isset($kp[$k][$i-1])) $csv_to[$i][$k] = $kp[$k][$i] = $kp[$k][$i-1];
				}
			}
		}
		
	}  if($FoxDebug==4) show($csv_to, 'csv_to');	
	
	if ($fx['ptvupdate']==1 && !isset($fx['ptvtarget']) && isset($fx['target']))
		$fx['ptvtarget'] = $fx['target'];
	
	//create target array from ptvtarget
	$ptv_to = array();
	if (isset($fx['ptvtarget'])) {
		$fx['ptvtarget'] = explode(',',$fx['ptvtarget']);
		foreach($fx['ptvtarget'] as $i => $t) {
			$ptv_to[$i]['target'] = $t;
			$ptv_to[$i]['foxaction'] = 'ptv';
			foreach(array('ptvfields','ptvclear','ptvfmt') as $n)
				if (isset($fx[$n])) $ptv_to[$i][$n] = $fx[$n];
		}
	} 	
		if($FoxDebug==4) show($ptv_to,'ptv_to');

	//create target array from fx[': '] fields
	$ext_to = array();
	foreach($fx as $k => $ar) {
		if ($k{0}!=':') continue;
		foreach($ar as $i => $v) { 
			$n = substr($k,1); // remove leading :
			if ($v!='')
				$ext_to[$i][$n] = $v; // set only non-empty values
			if ($n=='if' && !CondText($pagename, 'if '.$v, 'yes'))
				$ext_to[$i]['target'] = '';
		}
	}	
	//remove entries with missing target silently
	foreach ($ext_to as $i => $tg)
		if ($tg['target']=='') unset($ext_to[$i]);
	if($FoxDebug==4) show($ext_to,'ext_to');

	//merge input targets. Process 1. extended markup targets, 2. std/csv markup targets, 3. ptv targets
	$to = array_merge($ext_to, $csv_to, $ptv_to);
		if($FoxDebug==4) show($to,'$to first');
	
	//set any default parameters
	FoxTargetDefaults($fx, $to);	
		if($FoxDebug==4) show($to,'$to second');

	//make target names
	foreach($to as $i => $ar) {
		$to[$i]['target'] = FoxGroupName($pagename, $fx, $ar['target']);
		$to[$i]['fulltarget'] = $to[$i]['target'].strstr($ar['target'], '#');
		if($FoxDebug>2) echo " target=".$to[$i]['target'];
		if (isset($ar['ptvtarget'])) {
			$to[$i]['ptvtarget'] = FoxGroupName($pagename, $fx, $ar['ptvtarget']);
			if($FoxDebug>2) echo " ptvtarget=".$to[$i]['target'];
			if ($fx['ptvupdate']==1 && PageExists($to[$i]['target']))
					$to[$i]['foxaction'] = 'ptv';
		}
	}
	//sort by target name and foxaction
	usort($to, "FoxTargetSort");
	$to = array_merge($to, $new_to); //add newedit target to end
	FoxCombineTargets($to);
	//DEBUG//
		if($FoxDebug>2)	show($to,'$to target');
	return $to;
} //}}}

## sort $to array by target, then secondary by foxaction
function FoxTargetSort($a ,$b) {
	$order = array('copy','add','replace','ptv','display');
	//first sort by target name
  if ($a['target'] < $b['target']) {
    return -1;
  } elseif  ($a['target'] > $b['target']) {
    return 1;
  } else { //same target, now sort by foxaction
    return ( array_search($a['foxaction'], $order) > array_search($b['foxaction'], $order));
  }
} //}}}

## reorganise $to array
function FoxCombineTargets(&$to) {
	$newto = array();
	foreach($to as $i => $v)
		$newto[$v['target']][] = $to[$i];
	$to = $newto; unset($newto);
} //}}}

## set defaults for target array
function FoxTargetDefaults( $fx, &$to ) {
	global $FoxDebug; if ($FoxDebug==4) echo "FoxTargetDefaults>";
	foreach($to as $i => $tg) {
		//set 'template'
		if (isset($fx['foxtemplate'])) 
			if (!isset($tg['template']) && !isset($tg['foxtemplate']) && $to[$i]['foxaction']!='ptv')
				$to[$i]['template'] = 'foxtemplate'; //used by FoxLoadTemplate
		//set 'foxaction'
		if (!isset($tg['foxaction']) ) $to[$i]['foxaction'] = 'add';
		//set 'put' 
		if (!isset($tg['put']) && $to[$i]['foxaction']=='add') $to[$i]['put'] = 'bottom';
		elseif (!isset($tg['put']) && $to[$i]['foxaction']=='replace') $to[$i]['put'] = 'string';
	}
} //}}}

## check 'foxgroup' and set targetname,
function FoxGroupName($pagename, $fx, $name) {
	global $FoxDebug; if($FoxDebug) echo "<br /> FoxGroupName> ".$name; //DEBUG//
	if ($name{0}=='#') $name = PageVar($pagename,'$Name');
	if (!isset($fx['foxgroup'])) {
		$pname = MakePageName($pagename, $name);
		if (substr($pname, -1)==".") return '';
		return $pname;
	}
	else $group = $fx['foxgroup'];
	// exception: for 'escaped' target name ignore foxgroup
	if (substr($name,0)=="\\") {
		$name = str_replace("\\","",$name);
		return MakePageName($pagename, $name);
	}	else {
		$name = FoxWikiWord($name);
		return $group.'.'.$name;
	}
} //}}}

## processing of target pages for all foxactions
function FoxProcessTargets($pagename, $fx, $to) { 
	global $FoxDebug; if($FoxDebug) echo "<br/><b> FoxProcessTargets></b> "; //DEBUG//
	global $FoxAuth, $EnableBlocklist, $FoxMsgFmt, $EnableFoxDefaultMsg, $ScriptUrl, $FmtV, 
			$IsPagePosted, $Now, $ChangeSummary, $EditFunctions, $FoxExcludeEditFunctions, 
			$EnablePost, $FoxDisplayFmt, $InputValues;
	$counter = 0;
	$tcount = count($to);
	if($tcount==0) FoxAbort($pagename, "$[Error: no target specified!]");
	
	//process all target pages ($to target array built by function FoxTargetList)
	// key $tn is targetname, value $tacts is array of action parameter sets
	foreach ($to as $tn => $tacts) {
		if ($tn=='') FoxAbort($pagename, "$[Error: no target specified or target not found!]");
		StopWatch('FoxProcessTargets: begin $tn');
		//echo $counter;
		//process fox action sets for each target page 
		// tg is array of parameters for target action, idx is index number of action in process
		foreach ($tacts as $idx => $tg) {
			//init
			$tg['t_count'] = $tcount;
			$tg['t_idx'] = $counter; //set a target page index, used by FoxValue to get 
			$act = $tg['foxaction'];
			//DEBUG
			if($FoxDebug>1) echo "<br/>$tn ACTION $idx>".$act." ";			
			if($FoxDebug==4) show($tg, 'targ');
			
			//check target page permission for the fox action and set $permit flag
			$permit = FoxPagePermission($pagename, $act, $tn, $fx);			
		
			//email notify only, no text save to target. Needs foxnotify.php installed.
			//target is page with list of email addresses. template is page with email template.
			//email list target needs to be in FoxNotifyListsGroup
			if ($act == 'mail') {	
					if(function_exists('FoxNotifyUpdate')) {
						global $FoxNotifyLists, $FoxNotifyListsGroup, $FoxNotifyTemplatePageFmt;
						if (PageVar($tn, '$Group') != $FoxNotifyListsGroup) 
							$FoxMsgFmt[] = "Error: Target list is not in the group for FoxNotifyLists"; 
						else {
							$IsPagePosted = 1;
							$FoxNotifyTemplatePageFmt = MakePageName($pagename, $tg['template']);
							$FoxNotifyLists[] = $FoxNotifyListsGroup.".".PageVar($tn, '$Name');
							array_splice($EditFunctions, array_search('FoxPostNotify', $EditFunctions ), 1); //remove from EditFunctions, otherwise possible multiple mailings
							#FoxNotifyUpdate($pagename, getcwd(), $fx, $tg); //use register_shutdown_function, to do mailing at end
							$counter++; 
							if (isset($tg['foxsuccess'])) $FoxMsgFmt[] = $tg['foxsuccess'];
							elseif (isset($fx['foxsuccess'])) $FoxMsgFmt[] = $fx['foxsuccess'];
							elseif ($EnableFoxDefaultMsg==1)	$FoxMsgFmt[] = "$[Successful post to] [[$tn(?action=browse)]]";
							register_shutdown_function('FoxNotifyUpdate', $pagename, getcwd(), $fx, $tg);
						}
					}
					else $FoxMsgFmt[] = "Error: Could not send mail. FoxNotify is not installed!";
					continue 2; //next target page
				}
				
			//get template, skip for 'ptv'
			if ($act!='ptv') {
				$template = FoxLoadTemplate($pagename, $fx, $tg);
					if($FoxDebug>4) echo "<pre><b>TEMPL=</b><br/>".$template."</pre>";//DEBUG//
				//do var replacements on template, skip for 'copy'
				if ($template && $act!='copy')
					$template = FoxTemplateEngine($tn, $template, $fx, $tg, '','FoxProcessTargets');
			}
			
			//display only, no text save to target. Needs (:foxdisplay...:) as page location for output
			if ($act == 'display') {
				$FoxDisplayFmt = $template;
			}
			
			//preview for foxedit
			if (isset($fx['preview']) && isset($_SESSION['foxedit'][$pagename])) { 
				$FoxDisplayFmt = '';
				if($template && ($act=='add' || $act=='replace')) {
					$InputValues['text'] = $template;
					$FoxDisplayFmt = $template;
				}
				if(function_exists(FoxHandleEdit))
					FoxHandleEdit($pagename);
				continue 2; //next target page
			}	
			
			//newedit
			if ($act == 'newedit') {
				FoxNewEdit($pagename, $template, $fx, $tg); 
			//ends process
			}
			
			//add, replace, ptv, copy: load page file for saving
			if ($act=='add' || $act=='replace' || $act=='ptv' || $act=='copy') {
				//open page for first of foxactions
				if ($idx==0) {
					Lock(2);
					$page = RetrieveAuthPage($tn, $FoxAuth, true);
					//DEBUG
						if($FoxDebug) echo "<i><b>LOAD PAGE</b></i> $tn <br/>";
					if (!$page) Abort("?cannot read $pagename");
					$pagetext = $text = isset($page['text']) ? $page['text'] : '';
					$pagetext = trim($pagetext);
				} //end open page
				
				$toptxt = $bottxt = '';
				
				//extract anchored sections
				if (strstr($tg['fulltarget'],'#')) {
					$alltext = $text;
					$section = FoxTextSection($text, $tg['fulltarget']);
					//break off this page process if specified target section is not found
					if (!empty($section)) { 
						$text = $section['text'];
						$tpos = $section['pos'];
						$toptxt = substr($alltext, 0, $tpos);
						$bottxt = substr($alltext, $tpos + strlen($text));						
					} else {
						$FoxMsgFmt[] = "$[Error: could not find target section on] $tn";
						$permit = false; //do not allow text modification, as we have no target section
					}
				}
				$text = trim($text);
				
				//modify the text
				if ($permit == true) switch ($act) {
					case 'add' :    $text = FoxAddText( $pagename, $text, $template, $fx, $tg ); break;
					case 'replace': $text = FoxReplaceText( $pagename, $text, $template, $fx, $tg ); break;
					case 'ptv':     $text = FoxPTVAddUpdate($pagename, $text, $fx, $tg ); break;
					case 'copy' : 	$text = "\n".$template."\n"; break;
				}
				$text = trim($text);
				//recombine text sections
				if (!$toptxt=='') $text = trim($toptxt)."\n".$text;
				if (!$bottxt=='') $text .= "\n".trim($bottxt);

			} //end add, replace, ptv, copy

			//save target page after last of foxactions
			if ($idx==count($tacts)-1) {
				//DEBUG
						if($FoxDebug) echo "<br/><i><b>SAVE PAGE</b></i> $tn <br/>";
				//if we got changes, save page
				if ($text!=$pagetext) {
					$new = $page;
					$new['text'] = rtrim($text); 
					//reduce $EditFunctions
					if ($act == 'copy')
						$EditFunctions = array('SaveAttributes','PostPage','PostRecentChanges');
					else {
						SDVA($FoxExcludeEditFunctions, array('MergeSimulEdits','EditTemplate','RestorePage',
								'AutoCreateTargets','PreviewPage','RequireAuthor'));
						foreach($EditFunctions as $k => $fn)
							if (in_array($fn, $FoxExcludeEditFunctions)) unset($EditFunctions[$k]);	
					}
					//abort process if $FoxProcessTimeMax is exceeded, to avoid php fatal error on timeout.
					FoxTimer($pagename, "FoxProcessTargets: $tn");
					$IsPagePosted = 0;
					$new['csum'] = $ChangeSummary;
		  			if ($ChangeSummary) $new["csum:$Now"] = $ChangeSummary;
		  		
					if (@$fx['foxnosave']!=1 && @$fx['foxnosave']!='on')
					$IsPagePosted = UpdatePage($tn, $page, $new, $EditFunctions);
				}
				Lock(0);
				if ($IsPagePosted==1) {
					$counter++;
					if (isset($tg['foxsuccess'])) $FoxMsgFmt[] = $tg['foxsuccess'];
					elseif (isset($fx['foxsuccess'])) $FoxMsgFmt[] = $fx['foxsuccess'];
					elseif ($EnableFoxDefaultMsg==1)	$FoxMsgFmt[] = "$[Successful post to] [[$tn(?action=browse)]]";
				}
				else {
					if (isset($tg['foxfailure'])) $FoxMsgFmt[] = $tg['foxfailure'];
					elseif (isset($fx['foxfailure'])) $FoxMsgFmt[] = $fx['foxfailure'];
					elseif($EnableFoxDefaultMsg==1) $FoxMsgFmt[] = "$[Nothing posted to] $tn ";		
				}
				unset($IsPagePosted);
			} //end save target page after last foxaction	
		} //end of foxaction process loop	(for one page)	
	} //end of page process loop
	return $counter;
} //}}}

## get template from template page, #section, or (:foxtemplate 'string':)
function FoxLoadTemplate($pagename, $fx, $tg) {
	$tplname = $tg['template'];
	global $FoxDebug; if($FoxDebug) echo " FoxLoadTemplate> ".$tplname."<br>"; //DEBUG//
	switch($tplname) {
		//first check if no template is wanted
		case '0' : return '';
		case 'foxtemplate' :
			if ($fx['foxtemplate']=='' || $fx['foxtemplate']=='NULL' ) return '';
			else return Fox_htmlspecialchars_decode($fx['foxtemplate']);
		default :
			//check if foxtemplate is set as array
			if (!$tg['foxtemplate']=='') return Fox_htmlspecialchars_decode($tg['foxtemplate']); 
			//if tplname starts with # assume template is section on current page 
			if ($tplname{0}=='#') $tplname = $pagename.$tplname;
			$tplpage = MakePageName($pagename, $tplname);
			if ($tplpage)
				$page = ReadPage($tplpage, READPAGE_CURRENT);
			//TextSection will process any section passed, or the whole page
			if (isset($page['text']))
				$template = trim(TextSection($page['text'], $tplname),"\r\n");
	}
	if (!isset($template) && $tplpage)
		FoxAbort($pagename, "$[Error: Template page] $tplname $[is missing!]");
	if (!isset($template))
		FoxAbort($pagename, "$[Error: Template is missing!]");
	return $template;
} //}}}

## add processed template text at position defined with put, foxmark or mark
function FoxAddText( $pn, $text, $template, $fx, $tg ) {
	global $FoxMsgFmt, $FoxDebug; if($FoxDebug) echo " FoxAddText>"; //DEBUG//
	//get array with mark & form positions
	$ms = FoxSetMarks($pn, $text, $fx, $tg);
	$mark = (isset($ms[0]['mark']) ? $ms[0]['mark'] : '');
	$pre = $aft = ''; $err=0;
	//calculate section position and length
	switch ($ms['put']) {
		case '#top' : //legacy, next
		case 'top'   :  $pos = 0; $aft = "\n"; break;
		case '#bottom' : //legacy, next
		case 'bottom':  $pos = strlen($text); $pre = "\n"; break;
		case 'above' :  if (!isset($ms['Mpos'])) { $FoxMsgFmt[] = "$[Error: Found no mark to add above!]"; return $text; }
							 else { $pos = $ms['Mpos']; $aft = "\n"; break; }
		case 'below' :  if (!isset($ms['Mpos']))  { $FoxMsgFmt[] = "$[Error: Found no mark to add below!]"; return $text; }
							 else { $pos = $ms['Mpos'] + $ms['Mlen']; $pre = "\n"; break; }
		case '#append' : //legacy, next
		case 'aboveform': if (!isset($ms['Fpos'])) { $FoxMsgFmt[] = "$[Error: Found no form to add above!]"; return $text; }
							 else { $pos = $ms['Fpos']; $aft = "\n"; break; }
		case '#prepend' : //legacy, next
		case 'belowform': if (!isset($ms['Fpos'])) { $FoxMsgFmt[] = "$[Error: Found no form to add below!]"; return $text; }
							 else { $pos = $ms['Fpos'] + $ms['Flen']; $pre = "\n"; break; }
		case 'insert' : if (!isset($ms['Mpos'])) { $FoxMsgFmt[] = "$[Error: Found no mark to insert after!]"; return $text; }
							 else { $pos = $ms['Mpos'] + $ms['Mlen']; break; }
		case 'insertbefore' : if (!isset($ms['Mpos'])) { $FoxMsgFmt[] = "$[Error: Found no mark to insert before!]"; return $text; }
							 else { $pos = $ms['Mpos']; break; }
		default: $FoxMsgFmt[] = "$[Error:] '{$ms['put']}' $[is not a valid option with 'add'!] "; return $text;
	}
	//DEBUG//
		if($FoxDebug>4) echo "<pre><b>ADD NEW TEXT=</b><br/>".$template."</pre>";//DEBUG//	
	//add line breaks as needed
	$temp =  $pre.$template.$aft;
	// do string insert or repacement
	$text = substr_replace($text, $temp, $pos, 0);
		//DEBUG//
		if($FoxDebug>5) echo "<pre><b>FULL TEXT=</b><br/>".$text."</pre>";//DEBUG//
	return $text;
} //}}}

## replace text with processed template text at position defined by put, or mark and endmark
function FoxReplaceText( $pn, $text, $template, $fx, $tg ) {
	global $FoxDebug, $FoxMsgFmt; if($FoxDebug)	echo " FoxReplaceText>"; //DEBUG//
	//get array with mark & form positions
	$ms = FoxSetMarks($pn, $text, $fx, $tg);
	$mark = (isset($ms[0]['mark']) ? $ms[0]['mark'] : '');
	switch ($ms['put']) {
		case 'string' : if (!isset($ms['Mpos'])) { $FoxMsgFmt[]="$[Error: No string to find!]"; break; }
							else $text = substr_replace($text, $template, $ms['Mpos'], $ms['Mlen']); break;
		case 'all': $tlen = strlen($template);
						$i = 0; $icnt = count($ms[0]['pos']);
						while($i < $icnt) {
							$ipos = $ms[0]['pos'][$i] + $i*($tlen-$ms[0]['len']);
							$text = substr_replace($text, $template, $ipos, $ms[0]['len']);
							$i++;
						} break;
		case 'allplus' : $text = str_replace($mark, $template, $text); break;
		case 'regex' : $text = preg_replace("/$mark/", $template, $text); break;
		case 'marktomark': if ( $ms['Npos'] < $ms['Mpos'] ) {$FoxMsgFmt[]="$[Error: could not find endmark!]"; break;}
							else { $ipos = $ms['Mpos'] + $ms['Mlen']; $ilen = $ms['Npos'] - $ipos;
							$text = substr_replace($text, $template, $ipos, $ilen); break; }
		case 'overwrite' : $text = $template; break;
		default: $FoxMsgFmt[] = "$[Error:] '{$ms['put']}' $[is not a valid option with 'replace'!] ";
	}
	//DEBUG//
	if($FoxDebug>5) echo "<pre>FULL TEXT=".$text."</pre>";//DEBUG//
	return $text;
} //}}}

## claculate and set mark positions, excluding positions in fox forms and overlappings
function FoxSetMarks($pn, $text, $fx, $tg) {
	global $FoxDebug; if($FoxDebug>6) echo "<br>FoxSetMarks>"; //DEBUG//
	$ms = array(); $mk = array();
	$ms['put'] = $tg['put'];
	$mk[0] = isset($tg['mark']) ? $tg['mark'] : '';
	$mk[1] = isset($tg['endmark']) ? $tg['endmark'] : '';
	$formname = $fx['foxname'];
	//set foxmark
	if (isset($fx['foxmark']))      $foxmark = " ".$fx['foxmark'];
	elseif (isset($fx['foxplace'])) $foxmark = " ".$fx['foxplace']; //legacy keyword
	else $foxmark = '';
	//check for foxmarks, it overrides any other put setting
	$foxmarks = array(
		"(:foxappend {$formname}{$foxmark}:)"  => 'above',
		"(:foxprepend {$formname}{$foxmark}:)" => 'below');
	foreach($foxmarks as $pat=>$v)
		if (strpos($text, $pat)) { $ms['put'] = $v; $mk[0] = $pat; break; }
			//DEBUG//
			if($FoxDebug>6) echo " MARK=".$mk[0]." PLACE=".$ms['put'];//DEBUG//
	#if ($mk[0]=='') return $ms;
	//calculate any mark positions
	$marks = array();
	foreach($mk as $i => $m) {
		$ms[$i]['mark'] = $m;
		$ms[$i]['len'] = strlen($m);
		if ($mk[$i]=='') continue;
		$mv = array();
		$pat = preg_quote( $mk[$i],'/');
		if(preg_match_all("/$pat/", $text, $match, PREG_OFFSET_CAPTURE)) {
			foreach($match[0] as $k => $mark) {
				$mv[$k] = array( $mark[1], $mark[1]+strlen($mark[0]) );
			}
		}
		$marks[$i] = $mv;
	}
		//DEBUG//
		if($FoxDebug>6) { echo "<pre>\$fx mv "; print_r($mv); echo "\$marks "; print_r($marks); echo "</pre>"; }
	//get any form positions and exclude marks found in any forms
	if (preg_match_all("/(\\(:fox\\s+([\\w]+)(?: *\\n)?)(.*?)(\\(:foxend \\2:\\))/s", $text, $matches, PREG_OFFSET_CAPTURE)) {
		$forms = array();
		foreach((array)$matches[0] as $i => $frm ) {
			//build forms array: [0] = form start pos, [1] = form end pos
			$forms[$i][0] = $frm[1];
			$forms[$i][1] = $frm[1] + strlen($frm[0]);
			//calculate form position of calling form
			if ($formname == $matches[2][$i][0]) {
				$ms['Fpos'] = $frm[1];
				$ms['Flen'] = strlen($frm[0]);
			}
		}
		// add end-of-text dummy to help calculate positions near end of text
		$txe = strlen($text)+1;
		$forms[] = array( $txe, $txe);
	}
	//calculate all positions outside forms
	if(!isset($forms)) $forms = '';
	foreach($marks as $m=>$mm) {
		$mk = FoxExcludeFormPos($mm, $forms);
		foreach ($mk as $i=>$item) {
			$ms[$m]['pos'][$i] = $item[0];
		}
	}
	//exclude formoverlapping mark to endmark positions
	if(isset($ms[1]['pos'])) {
		foreach($ms[0]['pos'] as $m=>$mm) {
			foreach($ms[1]['pos'] as $n=>$nn) {
				if ($mm>$nn) continue;
				$nms[] = array($mm,$nn);
			}
		}
		$mk2 = FoxExcludeFormPos($nms, $forms);
		foreach ($mk2 as $i=>$item) {
			$ms[0]['pos'][$i] = $item[0];
			$ms[1]['pos'][$i] = $item[1];
		}
	}
	//set mark position
	if (isset($ms[0]['pos'][0])) {
			$ms['Mpos'] = $ms[0]['pos'][0];
			$ms['Mlen'] = $ms[0]['len'];
	}
	//set endmark position
	if (isset($ms[1]['pos'])) {
		foreach($ms[1]['pos'] as $i => $v) {
			$ms['Npos'] = $v; break;
		}
		$ms['Nlen'] = $ms[1]['len'];
	}
	$ms['Tlen'] = strlen($text);
		//DEBUG//
		if($FoxDebug>6) { echo "<pre>\$ms "; print_r($ms); echo "</pre>"; }
	return $ms;
} //}}}

## exclude mark positions inside fox forms
function FoxExcludeFormPos($marks, $forms) {
	$mark = array();
	foreach($marks as $k=>$m) {
		if ($forms=='') { $mark[$k] = array($m[0],$m[1]); continue; }
		foreach($forms as $d=>$f) {
			if ($f[0]>$m[0] && $f[0]<$m[1]) continue 2;
			if ($m[1]<$f[0]) { $mark[] = array($m[0],$m[1]); continue 2; }
			if ($m[1]<$f[1]) continue 2;
		}
	}
	return $mark;
} //}}}

## modified function TextSection to return section and position as well
function FoxTextSection($text, $sections, $args = NULL) {
	$args = (array)$args;
	$npat = '[[:alpha:]][-\\w*]*';
	if (!preg_match("/#($npat)?(\\.\\.)?(#($npat)?)?/", $sections, $match))
		return $text;
	@list($x, $aa, $dots, $b, $bb) = $match;
	if (!$dots && !$b) $bb = $npat;
	if ($aa) {
		$pos = strpos($text, "[[#$aa]]"); if ($pos === false) return false;
		if (@$args['anchors']) 
			while ($pos > 0 && $text[$pos-1] != "\n") $pos--;
		else $pos += strlen("[[#$aa]]");
		$text = substr($text, $pos);
	}
	if ($bb) {
		$text = preg_replace("/(\n)[^\n]*\\[\\[#$bb\\]\\].*$/s", '$1', $text, 1);
	}
	$tsections = array('text' => $text, 'pos' => $pos);
	return $tsections;
} //}}}

## variable substitutions of template
## see also notes on FoxDocumentation/TemplateMarkup
function FoxTemplateEngine($pn, $template, $fx, $tg, $linekeyseed=NULL, $caller=NULL) {
	global $FoxDebug; if($FoxDebug) echo " FoxTemplateEngine> "; //DEBUG//
	global $EnablePostDirectives, $FoxFxSafeKeys;
	if($template=="") return '';
	// create the data to be added, from template and variables
	$string = $template;
	// handle the {$$name[]} variables.
	$result = array();
	$parts = explode('{[foxsection]}',$string);
	foreach($parts as $section) {
		//find all occurences of {$$name[]}
		if( preg_match_all('/\\{\\$\\$([A-Za-z][-_:.\\w]*)\\[\\]\\}/',$section, $matches)) {
			$names = array_unique($matches[1]);
			$max   = 0;
			$keys  = array();
			$vals  = array();
			foreach($names as $i=>$var) {
				//get value
				$val  = (array)$fx[$var];
				$max  = max($max,count($val));
				$keys[$i] = '{$$'.$var.'[]}';
				$vals[$i] = $val;
			}
			$reps = array();
			for($i=0; $i < $max; $i++) {
				foreach((array)$vals as $k=>$val)
					$reps[$i][$k] = $val[$i];
			}
			//if more than one target page, map vars to target index
			if ($tg['t_count']>1) {
				$result[] = str_replace($keys, $reps[$tg['t_idx']], $section);
			//for one target build repeated sections
			} else for ($i=0; $i < $max; $i++) {
				$result[] = str_replace($keys,$reps[$i],$section);
			}
		}
		else  $result[] = $section;
	}

	// replace {$$var}, {$$var[num]} and {$$(func...)} markup.
	$result = FoxTemplateVarReplace($pn, $fx, $tg, $result);

	//replace {$$$...} with {$$...} for posting of forms with replacement vars
	$result = str_replace('{$$$','{$$',$result);
	
	//replace {=$$....} and {PageName=$$...} with {=$....} and {PageName=$...} for template posting
	$result = preg_replace('/(\\{\\*|!?[-\\w.\\/\\x80-\\xff]*)=\\$(\\$:?\\w+\\})/',"$1=$2",$result);
	
	//replace \n by newlines
	$result = preg_replace('/\\\\n/',"\n",$result);

	//create a unique linekeyseed, if necessary
	if ($linekeyseed==NULL)  {
		$time1 = date('ymd-His', time() - date('Z'));
		$linekeyseed = $time1.'-'.rand(0,100000);
		#$linekeyseed = time().'a'.rand(0,100000);
	}
	foreach ($result as $index => $entry) {
		//skip if delete link already exists
		if (preg_match("/\\{\\[foxdel([^]]+)FullName\\}\\s*\\]\\}/", $entry)) continue;
		$linekey = $linekeyseed.''.$index;
		//adding linekey + pagename to any foxdelete markup for unique id
		if (preg_match("/button/", $entry)) {
			// Add linekey to delete button for line delete
			$entry = str_replace( '{[foxdelline button', "{[foxdelline button $linekey {\$FullName} ", $entry );
			// Add linekey to delete button for range delete
			$entry = str_replace( '{[foxdelrange button', "{[foxdelrange button $linekey {\$FullName} ", $entry );		
		} else {
			// Add linekey to delete link for line delete
			$entry = str_replace( '{[foxdelline', "{[foxdelline $linekey {\$FullName} ", $entry  );
			// Add linekey to delete link for range delete
			$entry = str_replace( '{[foxdelrange', "{[foxdelrange $linekey {\$FullName} ", $entry );
		}
		//Add line-key to delete range begin marker
		$entry = str_replace( '#foxbegin#', "#foxbegin $linekey#", $entry );
		// Add line-key to delete range end marker
		$entry = str_replace( '#foxend#', "#foxend $linekey#", $entry );
		$result[$index] = $entry;
	}
	return implode("\n",$result);
} //}}}

## fields to be ignored in initial variable replacements
SDVA($FoxFxSafeKeys, array(
	'n','foxpage','action','foxaction','foxname','post', 'put',
	'foxfields',':foxaction',':fulltarget',':put',':foxfields',
	'foxtemplate', 'foxpreviewtemplate', 'foxdisplaytemplate',
));

## input field var replacements, exclude fields we know are not variables
function FoxInputVarReplace($pn, &$fx) {
	global $FoxDebug; if($FoxDebug) echo "<br/> INPUT-VarRep> ";//DEBUG//
	global $FoxFxSafeKeys;
	$fx_check = $fx;
	foreach ($fx_check as $val) {
		foreach ($FoxFxSafeKeys as $key) {
			 if (array_key_exists($key, $fx_check)) {
				  unset($fx_check[$key]);
			 }
		}
	}
	foreach($fx_check as $key => $value) {
		if(is_array($value)) {
			foreach($value as $i=>$val) {
				if (strstr($val, '{$$')) {
						if($FoxDebug>3) echo "<pre>IN-arr> ".$key."[".$i."] = ".$val."</pre>";//DEBUG//
					$fx[$key][$i] = FoxVarReplace($pn,$fx,'',$val);
				}
			}
		}
		else if (strstr($value, '{$$')) {
				if($FoxDebug>3) echo "<pre>IN-str> ".$key." = ".$value."</pre>";//DEBUG//
			$fx[$key] = FoxVarReplace($pn,$fx,'',$value);
		}
	}
} //}}}

## replace any {$$var} or {$$(func...)} in $arg using values from $fx
function FoxTemplateVarReplace($pn, $fx, $tg, $args) {
	global $FoxDebug; if($FoxDebug) echo "<br /> TEMPLATE-VarRep> "; //DEBUG//
	if( is_array($args) ) {
		$data = array( 'more' => false, 'pn' => $pn, 'fx' => $fx, 'tg' => $tg );
		array_walk_recursive( $args, 'FoxVarRepRecursive' , $data );
	} else FoxVarReplace($pn, $fx, $tg, $args);
	return $args;
} //}}}

function FoxVarRepRecursive(&$v, $k, &$d) {
	global $FoxMaxIterations; if($FoxDebug>4) echo "FoxVarRepRecursive>";
	if ($d['tg']=='') return;
	SDV($FoxMaxIterations, 100);
	static $cnt = 0;
	FoxVarReplace($d['pn'], $d['fx'], $d['tg'], $v);
	$cnt++;
	$maxcnt = $FoxMaxIterations + $d['tg']['t_idx'];
	if( $cnt >= $maxcnt )
		FoxAbort( $d['fx']['foxpage'], "$[Error: max iterations exceeded while replacing variables!]" );
} //}}}

## replaces variables by checking pattern and if success returns value
function FoxVarReplace($pn, $fx, $tg, &$str) {
	global $Now, $FoxDebug; if($FoxDebug>4) echo "<pre><b>VREP=</b><br/>".$str."</pre>"; //DEBUG//
	# {$$:ptv}
	$str = preg_replace('/\\$\\$:/', '$$ptv_', $str);  
	# {$$var}
	$str = preg_replace_callback('/\\{\\$\\$([a-z][-_\\w]*)\\}/i', 
					function($m) use($fx,$tg) { return FoxValue($fx,$tg,$m[0],$m[1]);}, $str);
	# {$$var[]}
	$str = preg_replace_callback('/\\{\\$\\$([a-z][-_\\w]*)\\[\\s*([a-z0-9]+)\\s*\\]\\}/i', 
					function($m) use($fx,$tg) { return FoxValue($fx,$tg,$m[0],$m[1],$m[2]);}, $str);
	# {=$pagevar}
	$str = preg_replace_callback('/\\{(\\*|!?[-\\w.\\/\\x80-\\xff]*)\\=(\\$:?\\w+)\\}/', 
					function($m) use($pn) { return PVSE(PageVar($pn, $m[2], $m[1]));}, $str); 
	# {$$(date)}
	$str = preg_replace_callback('/\\{\\$\\$\\(date[:\\s]+(.*?)\\)\\}/', 
					function($m) { return date($m[1]);}, $str); 
	# {$$(timestamp)}
	$str = preg_replace('/\\{\\$\\$\\(timestamp\\)\\}/', $Now, $str);
	# {$$(expr)}
	$str = preg_replace_callback('/\\{\\$\\$(\\(\\w+\\b.*?\\))\\}/', 
					function($m) use($pn) { return MarkupExpression($pn, $m[1]);}, $str);
	if($FoxDebug>4) echo "<pre><b>VNEW=</b><br/>".$str."</pre>"; //DEBUG//
	return $str;
} //}}}

# looks up a field name and returns its value for {$$field} and {$$field[num]}
# either strings or arrays. The final optional parameter is the value of num. If
# num isn't given, and the field is an array, it uses the value of $tg['t_idx']
# (index of targetpage process) if it exists, or else 0.
# In the InputVarReplace process if the field is an array, a comma-separated list of
# the array elements will be returned (not just the first array element)
function FoxValue($fx, $tg, $fullvar, $var, $index=NULL) {
	global $FoxDebug; if($FoxDebug>2) { if(is_null($index)) echo "<pre>VALUE(".$var.")="; else echo "<pre>VALUE(".$var."[".$index."])=";}//DEBUG//
	$fti = 'none';
	if ($tg && $tg['t_count']>1) $fti =  $tg['t_idx'];
	if (array_key_exists($var, $fx) ) {
		$val = $fx[$var];
		if(is_array($val) ) {
			if ($fti==='none' && is_null($idx))
				$val = implode(',',$val);
			elseif (is_null($idx) )
				$val = $val[$fti];
			else
				$val = $val[$idx];
		}
		//DEBUG//
		if($GLOBALS['FoxDebug']>2) { 
			if (is_null($idx) && $fti==='none') echo "<pre>VALUE(".$var.")=".$val."</pre>";
			else if (is_null($idx)) echo "<pre>VALUE(".$var."[".$fti."])=".$val."</pre>";
			else echo "<pre>VALUE(".$var."[".$idx."])=".$val."</pre>";
		} //DEBUG
		return $val;
	}
	//var is no key name: if action 'add' return empty, otherwise full var string
	if(isset($tg['foxaction']) &&  $tg['foxaction']=='add') $fullvar = '';
	return $fullvar;
} //}}}

## get arguments from POST or GET
function FoxRequestArgs ($fx = NULL) {
	if (is_null($fx)) $fx = array_merge($_GET, $_POST);
	foreach ($fx as $key=>$val) {
    	if(is_array($val))
   		foreach($val as $k=>$v) {
    			$fx[$key][$k] = str_replace("\r",'',stripmagic($v));
   		}
		else $fx[$key] = str_replace("\r",'',stripmagic($val));
	}
	return $fx;
} //}}}

## call external filter functions
SDV($FoxFilterFunctions, array());
function FoxFilter($pagename, &$fx) {
	global $FoxDebug; if($FoxDebug) echo " FoxFilter> "; //DEBUG//
	global $FoxFilterFunctions;
	//get filter keynames
	$fx['foxfilter'] = preg_split("/[\s,|]+/", $fx['foxfilter'], -1, PREG_SPLIT_NO_EMPTY);
	foreach($fx['foxfilter'] as $f) {
		$ffn = $FoxFilterFunctions[$f];
		if (function_exists($ffn) ) {
			// use specific filter
			if(is_callable($ffn, false, $callable_name)) {
				$fx = $callable_name($pagename, $fx);
				if(!$fx) Redirect($pagename); // Filter is telling us to abort;
			}
		}
	}
} //}}}

## create NAME fields from ptv_NAME fields and add NAME to ptvfields array
function FoxPTVFields($pagename, &$fx) {
	global $FoxDebug, $FoxPTVArraySeparator;
	if($FoxDebug) echo "<br/> FoxPTVFields><br/>"; //DEBUG//
	//strip ptv_ and make name fields
	if($fx['ptvfields'])
		$fx['ptvfields'] = explode(",",$fx['ptvfields']);
	foreach($fx as $n => $v) {
		if(substr($n,0,4)=="ptv_") {
			$n = substr($n,4);
			$fx[$n] = $v;
			$fx['ptvfields'][] = $n;
		}
	}
	if(is_array($fx['ptvfields']))
		$fx['ptvfields'] = array_unique($fx['ptvfields']);
	//flatten input values as arrays for ptvs
	if (is_array(@$fx['ptvfields'])) { 
		foreach($fx['ptvfields'] as $n) { 
			if (is_array($fx[$n])) { if($FoxDebug)	echo "<pre>[".$n."] => ";
				foreach($fx[$n] as $k => $v) {
					if(preg_match('/[\s\']/',$v)) $fx[$n][$k] = '"'.$v.'"'; //doublequote text with spaces or single quotes
				}
				$fx[$n] = implode($FoxPTVArraySeparator, $fx[$n]); //array input gets converted to text
			if($FoxDebug) echo $fx[$n]."</pre>";
			}	
		}
	} 
} //}}}

## add & update page text variables
function FoxPTVAddUpdate($pagename, $text, $fx, $tg ) {
	global $PageTextVarPatterns, $EnablePostDirectives, $FoxPTVArraySeparator,
				$FoxDebug, $FoxClearPTVFmt, $EnableFoxPTVDelete, $FoxPTVDeleteKey, $FoxMsgFmt;
	if ($FoxDebug) echo " FoxPTVAddUpdate>"; //DEBUG//
	//PTVs to check
	if ($tg['ptvfields']) {
		$ptvs = (is_array($tg['ptvfields'])) ? $tg['ptvfields'] : explode(',', $tg['ptvfields']);
		foreach ($ptvs as $n)	$update[$n] = $fx[$n]; //use ptvfields list to check for PTVs
	}	else $update = $fx; //use all fields to check for PTVs
	$ptvclear = (isset($tg['ptvclear'])) ? explode(",", $tg['ptvclear']) : array(); //array of input fields which will clear PTVs if empty
	$pageptvs = array(); //to build array of PTV names in page
	//look through PTV patterns and replace matches
	foreach ($PageTextVarPatterns as $pat) {
		if (!preg_match_all($pat, $text, $match, PREG_SET_ORDER)) continue;
		foreach ($match as $m) //$m[0]=all, $m[1]=beforevalue, $m[2]=name, $m[3]=value, $m[4]=aftervalue	
		{   
			$k = $pageptvs[] = $m[2];
			if (!array_key_exists($k, $update)) continue;
			$v = (isset($update[$k])) ? $update[$k] : ''; //new value
			if ($m[3]==$v) continue; //no change on this PTV
			if ($v=='' && !($ptvclear[0]==1 || in_array($k, $ptvclear))) continue; //empty input gets ignored, unless ptvclear is set to 1 or to ptv field names
			if ($v==$FoxClearPTVFmt) $v = '';  // 'NULL' or other special string clears ptv
			if (is_array($v)) $v = implode($FoxPTVArraySeparator, $v); //array input gets converted to text
			if ($EnablePostDirectives==false) $v = FoxDefuseItem($v);//prevent posting of directives & markup expressions
			if ($FoxDebug>4)	echo "<pre> ".$k."=>".$v."</pre>"; //new ptv name=value
			if (!preg_match('/s[eimu]*$/', $pat)) $v = str_replace("\n", ' ', $v); //for any inline pattern replace newlines with spaces
			if (strstr($m[4],'[[#')) { $v = trim($v); $m[4] = "\n".$m[4];} //preserve linebreak before ending anchor
			if ($EnableFoxPTVDelete==1 && $v==$FoxPTVDeleteKey) $text = str_replace($m[0], '', $text);//erasing ptv
			else $text = str_replace($m[0], $m[1].$v.$m[4], $text); //update ptv in text
			$GLOBALS['PCache'][$tg['target']]['=p_'.$k] = $v; //update ptv in $PCache
		}
	}
	//add any new ptvs named in ptvfields and do not exist in ptvtarget page
	if ($ptvs) {
		$ptvfmt = isset($tg['ptvfmt']) ? $tg['ptvfmt'] : 'hidden';
		$newptvs = array_diff($ptvs, $pageptvs );
		foreach ($newptvs as $k) {
			$v = $update[$k];
			if (is_array($v))	$v = implode($FoxPTVArraySeparator,$v);
			switch ($ptvfmt) {
				case 'text' :    $text = $text."\n$k: $v\n"; break;           //add as text: val 
				case 'deflist' : $text = $text."\n: $k : $v\n"; break;        //add as definition list
				case 'section' : $text = $text."\n[[#".$k."]]\n$v\n[[#".$k."end]]\n"; break;  //add as anchor section
				case 'extra' :   $text = $text."\n(::$k:\n$v\n::)\n"; break;  //add as extra hidden PTV 
				case 'hidden' :  $text = $text."\n(:$k: $v:)\n"; break;       //add as hidden PTV
				default : $FoxMsgFmt[] = "$[Error: cannot recognise PTV format] $ptvfmt";
			}
			$GLOBALS['PCache'][$tg['target']]['=p_'.$k] = $v;  //add ptv to $PCache
		}
	}
	return $text;
} //}}}

## check page posting permissions
function FoxPagePermission($pagename, $act, $tn,  $fx) {
	global $FoxDebug; if($FoxDebug)	echo "<br/> FoxPagePermission for <i>$act</i>> $tn <br>";
	global $FoxMsgFmt, $FoxConfigPageFmt, $FoxPagePermissions;
	if ($act=='display') return true; //display is allowed
	if (!$act) { $FoxMsgFmt[] = "ERROR ($tn): Unknown action: $act . Cannot proceed!"; return;}
	// get name patterns from FoxConfig page
	$Name = PageVar($pagename, '$Name');
	$Group = PageVar($pagename, '$Group');
	$config = FmtPageName($FoxConfigPageFmt, $pagename);
	if (PageExists($config)) {
		$cfpage = ReadPage($config, READPAGE_CURRENT);
		if ($cfpage) {
			$text = $cfpage['text'];
			if(preg_match_all("/^\\s*([\\*\\w][^\\s:]*):\\s*(.*)/m", $text, $matches, PREG_SET_ORDER))
				foreach($matches as $m)
					$FoxPagePermissions[$m[1]] = $m[2];
		}
	}
	//show($FoxPagePermissions, 'PagePermissions');
	// name check for $act against $FoxPagePermissions
	$pnames = array();
	if(is_array($FoxPagePermissions))
		foreach($FoxPagePermissions as $n => $t) {
			if(strstr($t,'-'.$act)||strstr($t,'none')) { $pnames[$n]='-'.$n; continue; }
			if(strstr($t,$act)||strstr($t,'all')) $pnames[$n]=$n;
		}
	$pnames = FmtPageName(implode(',',$pnames),$pagename);
	//if no permitted names exclude all pages
	if($pnames=='') $pnames = '-*.*';
	$px = MatchPageNames($tn,$pnames); 
	$namecheck = (boolean)MatchPageNames($tn,$pnames);
	// string check against string patterns
	$strcheck = 0;
	if(PageExists($tn)) { 
		$page = ReadPage($tn, READPAGE_CURRENT);
		$strcheck = (boolean)( preg_match("/\\(:fox(prepend|append|allow)/", $page['text']) //allow on page which got special markup
			OR preg_match("/\\(:fox ".$fx['foxname']." /", $page['text']) ); //allow on page which got the form
	}
	if($namecheck==0 && $strcheck==0) {
		//if ($FoxDebug) echo "Permission denied to $act on $tn ";
		$FoxMsgFmt[] = "PERMISSION DENIED to $act on $tn!";
		return false;
	}
	else return true;
} //}}}

## check access code, captcha, new page exists, and required fields
## this runs before individual page processing
function FoxSecurityCheck($pagename, $targets, &$fx) {
	global $FoxDebug; if($FoxDebug) echo "<br/>FoxSecurityCheck><br>";
	global $FoxNameFmt, $FoxMsgFmt, $EnableAccessCode, $EnablePostCaptchaRequired, $EnablePost;
	//if preview
	if ($fx['preview']) return '';
	
	//if enabled check for access code match
	if($EnableAccessCode AND (!(isset($fx['access'])&&($fx['access']==$fx['accesscode'])))) {
		FoxAbort($pagename, "$[Error: Missing or wrong Access Code!]");
	}
	//if enabled check for Captcha code (captcha.php is required)
	if($EnablePostCaptchaRequired AND !IsCaptcha()) {
		FoxAbort($pagename, "$[Error: Missing or wrong Captcha Code!]");
	}
	//check pagecheck: if pagecheck page names exists already
	if(isset($fx['pagecheck'])) {
		$check = explode(',',$fx['pagecheck']);
		$stop = 0;
		// pagecheck=1 checks all target pages
		if($check[0]==1) $check = $targets;
		foreach ($check as $pt) {
			$page = MakePageName($pagename, $pt);
			if($pagename==$page) { $FoxMsgFmt[] = "$[Error: You are not allowed to post to this page!]"; $stop=1; continue;}
			if(PageExists($page) AND in_array($page, $targets)) { 
				$FoxMsgFmt[] = "$[Page] [[$pt]] $[exists already. Please choose another page name!]"; $stop=1; continue;}
		}
		if ($stop==1) FoxAbort($pagename,"");
	}
	//check for 'post' and 'cancel' from submit button 
	if ( !isset($fx['post']) AND !isset($fx['postdraft']) AND !isset($fx['cancel']) AND !isset($fx['preview']) ) {
		 FoxAbort($pagename, "$[Error: No text or missing post!]");
	}
} //}}}

## defuse posting of directives (:...:) and expressions {(...)} by rendering as code
## check only relevant input fields
function FoxDefuseMarkup($pagename, &$fx ) {
	global $EnablePostDirectives, $FoxFxSafeKeys;
	$fx_check = $fx;
	unset($fx_check['foxtemplate']);
	foreach ($fx_check as $val) {
		foreach ($FoxFxSafeKeys as $key) {
			if (array_key_exists($key, $fx_check)) {
				unset($fx_check[$key]);
			}
		}
	}
	array_walk_recursive( $fx_check, 'FoxDefuseItem' );
	$fx = array_merge($fx, $fx_check);
} //}}}

## defuse by rendering as code any markup directives and markup expressions
function FoxDefuseItem( &$item ) {
	global $FoxDebug; if($FoxDebug>2) echo " DEFUSE> <code>$item</code>"; //DEBUG//
	if (is_array($item)) return $item;
	if (!preg_match("/\\(:|\\{\\(/", $item)) return $item;
	// render {(..)} and (:...:) as code by using HTML character codes
	$item = preg_replace("/\\{(\\(\\w+\\b.*?\\))\\}/", "&#123;$1&#125;", $item); 
	$item = str_replace("(:", "(&#x3a;", $item);
	$item = str_replace(":)", "&#x3a;)", $item);
	//undo for markup directives wrapped in [@...@] or [=...=]
	if (preg_match_all("/(\\[[@|=])[^\\]]*(\\(&#x3a;)(.*?[@|=]\\])/s", $item, $mp)) {
		foreach($mp[0] as $i => $v) {
			$v = str_replace("(&#x3a;","(:",$v);
			$v = str_replace("&#x3a;)",":)",$v);
			$item = str_replace( $mp[0][$i], $v, $item);
		}
	}
	//undo for markup expressions wrapped in [@...@] or [=...=]
	if (preg_match_all("/(\\[[@|=])[^\\]]*(&#123;\\(.*?\\)&#125;)(.*?[@|=]\\])/s", $item, $mp)) {
		foreach($mp[0] as $i => $v) {
			$v = str_replace("&#123;(","{(",$v);
			$v = str_replace(")&#125;",")}",$v);
			$item = str_replace( $mp[0][$i], $v, $item);
		}		
	}
} //}}}

## make a WikiWord out of a string
function FoxWikiWord($str) {
	global $FoxDebug; if($FoxDebug) echo " FoxWikiWord> "; //DEBUG//
	global $MakePageNamePatterns;
	$str = preg_replace('/[#?].*$/', '', $str);
	$nm = $str;
	foreach($MakePageNamePatterns as $pat => $rep) {
		if(is_callable($rep) && $rep != '_') $nm = preg_replace_callback($pat,$rep,$nm);
		else $nm = preg_replace($pat,$rep,$nm);
	}
	return $nm;
} //}}}

## newedit opens page in the edit form, it can only run as last page process
function FoxNewEdit($pagename, $template, $fx, $tg) {
	global $FoxDebug; if($FoxDebug) echo " FoxNewEdit> "; //DEBUG//
	if(PageExists($tg['target'])) Redirect($tg['target']); //jump to existing page
	$urlfmt = '$PageUrl?action=edit';
	if ($template) {
		//merging fields and template, put into Session var for use with ?action=edit&foxtemptext=1
		@session_start();
		$_SESSION["FoxTempPageText"] = $template;
		//add special template marker before redirecting to edit
		$urlfmt.= '&foxtemptext=1';
	}
	Redirect($tg['target'], $urlfmt); // open new page to edit
} //}}}

## upload files
function FoxPostUpload($pagename, $fx, $auth='upload') {
	global $FoxDebug; if ($FoxDebug) echo " FoxPostUpload>"; 
	global $UploadVerifyFunction, $UploadDir, $UploadPrefix, $UploadPrefixFmt, 
		   	 $LastModFile, $EnableUploadVersions, $Now, $FoxMsgFmt, $FmtV;
	$uptarget = $fx['uptarget'];
	if (function_exists('MakeUploadPrefix'))
		$upprefix = MakeUploadPrefix($uptarget);
	else $upprefix = FmtPageName("$UploadPrefixFmt", $uptarget);
	$dirpath = $UploadDir.$upprefix;

	$page = RetrieveAuthPage($uptarget, $auth, true, READPAGE_CURRENT);
	if (!$page) FoxAbort($pagename, "?cannot upload to $uptarget");
	foreach($_FILES as $n => $upfile) { 
		$upname = $upfile['name'];
		if ($upname=='') continue; 
		// check for new upload filename
		if ($fx[$n.'_name']) $upname = $fx[$n.'_name'];
		$upname = MakeUploadName($uptarget, $upname);
		if (!function_exists($UploadVerifyFunction))
			FoxAbort($pagename, '?no UploadVerifyFunction available');
		$filepath = $dirpath.'/'.$upname; 
		$result = $UploadVerifyFunction($uptarget, $upfile, $filepath);
		if ($result=='') {
			$filedir = preg_replace('#/[^/]*$#','',$filepath);
			mkdirp($filedir);
			if (IsEnabled($EnableUploadVersions, 0))
				@rename($filepath, "$filepath, $Now");
			if (!move_uploaded_file($upfile['tmp_name'], $filepath))
				{ FoxAbort($pagename, "?cannot move uploaded file to $filepath"); return; }
			fixperms($filepath, 0444);
			if ($LastModFile) { touch($LastModFile); fixperms($LastModFile); }
			$result = "upresult=success";
		}
		# process results for message
		$re = explode('&',substr($result,9));
		# special cases: 
		if($re[0]=='badtype' OR $re[0]=='toobigext') {
			global $upext, $upmax;
			$r1 = explode('=',$re[1]);
			$upext = $r1[1];
			$r2 = explode('=',$re[2]);
			$upmax = $r2[1];
		}
		$result = $re[0];
		$FoxMsgFmt[] = "$[UL$result] $upname";
	}
} //}}}

# last
function FoxFinish($pagename, $fx, $msg) {
	StopWatch('FoxFinish start');
	global $InputValues, $FoxMsgFmt;
	// wipe out input values, so there's no redisplay
	if (isset($fx['keepinput'])) { //keep values for selected input fields
		$keep = explode(',', $fx['keepinput']);
		if ($fx['keepinput']!=1)  {
			foreach($InputValues as $i => $v) {
				if (in_array($i, $keep)) continue;
				unset($GLOBALS['InputValues'][$i]);
			}
		}
	} else //wipe all
		foreach($InputValues as $i => $v)
			unset($GLOBALS['InputValues'][$i]);
	HandleDispatch($pagename,'browse',$msg);
	exit;
} //}}}

## abort by displaying error message and returning to page
function FoxAbort($pagename, $msg) {
	global $InputValues, $FoxMsgFmt, $MessagesFmt;
	$FoxMsgFmt[] = $msg;
	$MessagesFmt[] = "<div class='wikimessage'>$msg</div>"; //legacy using (:messages:) markup
	HandleDispatch($pagename,'browse');
	exit;
} //}}}

# FoxTimer aborts if $FoxProcessTimeMax is exceeded, returns process time,
# sets entries in $StopWatch array, displayed with config settings:
# $EnableDiag = 1;
# $HTMLFooterFmt['stopwatch'] = 'function:StopWatchHTML 1'; //function is in scripts/diag.php
function FoxTimer($pagename, $x) {
	global $FoxProcessTimeMax, $StopWatch;
	static $wstart = 0;
	$wtime = strtok(microtime(), ' ') + strtok('');
	if (!$wstart) $wstart = $wtime;
	$wtime = $wtime-$wstart;
	$StopWatch[] = sprintf("%04.2f %s", $wtime, $x);
	$xtime = sprintf("%04.2f %s", $wtime, '');
	if($xtime>$FoxProcessTimeMax)
		FoxAbort($pagename, "$[Error: processing stopped before maximum script timeout.] $[Page process:] $xtime sec");
	return $xtime;
} //}}}

## validation check for form input as set by (:foxcheck ... :) markup
function FoxInputCheck($pagename, $fx) {
	global $FoxDebug; if($FoxDebug) echo " FoxInputCheck> "; //DEBUG//
	global $FoxCheckError, $FoxCheckErrorMsg, $FoxMsgFmt, $FoxClearPTVFmt;
	if (isset($fx['cancel'])) return '';
	$check = array();
	foreach($fx as $k => $ar) {
		if (substr($k,0,4)!='chk_') continue;
		if (!is_array($ar)) continue;
		foreach($ar as $i => $v) {
			$n = substr($k,4); // remove leading 'chk_'
			if ($v=='') continue; // set only non-empty values
			if ($n=='name') {
				$nms = explode(',',$v);
				foreach($nms as $j => $nm)
					$check[$i]['names'][$j] = $nm; 
			} else $check[$i][$n] = $v;
		}
	}
	if($FoxDebug>4) show($check,'check');
	$FoxCheckError = array();
	foreach($check as $i => $opt) {
		foreach($opt['names'] as $n) {
			if ($opt['empty']==1 && ($fx[$n]=='' || $fx[$n]==$FoxClearPTVFmt)) continue;
			if (!isset($opt['match'])) $opt['match'] = "?*";
			$pat = (isset($opt['regex'])) ? $opt['regex'] : ".+";
			list($inclp, $exclp) = GlobToPCRE($opt['match']); 
			if ( $inclp && !preg_match("/$inclp/is", $fx[$n]) 
						 || $exclp && preg_match("/$exclp/is", $fx[$n])
						 || !preg_match("/$pat/is", $fx[$n])) {
				$FoxMsgFmt[$n] = isset($opt['msg']) ? $opt['msg']
											: "$[Invalid parameter:] $n";
				$FoxCheckError[] = $n;
			}
			if (@$opt['if'] && !CondText($pagename, 'if '.$opt['if'], 'yes')) {
				$FoxMsgFmt[$n] = isset($opt['msg']) ? $opt['msg']
											: "$[Input condition failed]";
				$FoxCheckError[] = $n;
			}
		}
	}
   $errmsg = ($fx['foxcheckmsg']) ? $fx['foxcheckmsg'] : $FoxCheckErrorMsg;
   //avoid abort or call to foxedit when preview
   if (isset($fx['preview'])) unset($FoxCheckError);
   
   if ($FoxCheckError) { 
   	if ($_SESSION['foxedit'][$pagename]) FoxHandleEdit($pagename);	
   	else FoxAbort($pagename, $errmsg);
	}
} //}}}

## build javascript for simple validation that required fields have values
function FoxJSFormCheck($formcheck) {
	$reqfields = preg_split("/[\s,|]+/", $formcheck, -1, PREG_SPLIT_NO_EMPTY);
	$out = "
	<script type='text/javascript' language='JavaScript1.2'><!--
		function checkform ( form ) {
		";
	foreach($reqfields as $required) {
		$out .=
		"if (form.$required && form.$required.value == \"\") {
			  window.alert( 'Entry in field \"$required\" is required!' );
			  form.$required.focus();
			  return false ;
			}
		";
	}
	$out .=
	"return true; }
	--></script>";
	return $out;
} //}}}

## provide {$AccessCode} page variable:
$FmtPV['$AccessCode'] = rand(100, 999);

## add page variable {$FoxPostCount}, counts message items per page
$FmtPV['$FoxPostCount'] = 'FoxStringCount($pn,"#foxbegin")';
function FoxStringCount($pagename,$find) {
	$page = ReadPage($pagename, READPAGE_CURRENT);
	$n = substr_count($page['text'], $find);
//   if ($n==0) return '';  #suppressing 0
	return $n;
}

## use like (:input default $:MyPTV[] {(foxfixptv $:MyPTV)} :)
## for fixing CSV PTV data to space separated data for use in (:input default ...:)
$MarkupExpr['foxfixptv'] = 'FoxFixPTV($pagename, $args[0])';
function FoxFixPTV($pn, $ptv) {  
	$p = PageTextVar($pn, substr($ptv,2));
	preg_match_all('/(\\"[^\\"]+\\")|[^,\\s]+/',$p,$m);
	$a = $m[0];
  if(!is_array($a)) return '';
  foreach($a as $k => $v)	$a[$k] = PHSC(trim($v,', '),ENT_NOQUOTES);  
	return implode(" ",$a);
}

## helper function for php 4 which has no array_walk_recursive function
if (!function_exists('array_walk_recursive')) {
	function array_walk_recursive(&$input, $funcname, $userdata = "") {
		if (!is_callable($funcname) || !is_array($input)) return false;
		foreach ($input as $key => $value) {
			if (is_array($input[$key]))
				array_walk_recursive($input[$key], $funcname, $userdata);
			else {
				$saved_value = $value;
				if (!empty($userdata)) $funcname($value, $key, $userdata);
				else $funcname($value, $key);
				if ($value != $saved_value) $input[$key] = $value;
			}
		}
	return true;
	}
} //}}}

## decode htmlspecialchars
function Fox_htmlspecialchars_decode($str, $style=ENT_COMPAT) {
	if ($style === ENT_COMPAT) $str = str_replace('&quot;','\"',$str);
	if ($style === ENT_QUOTES) $str = str_replace('&#039;','\'',$str);
	$str = str_replace('&lt;','<',$str);
	$str = str_replace('&gt;','>',$str);
	$str = str_replace('&amp;','&',$str);
	return $str;	
}

# debug helper function to echo preformatted array with optional label
if (!function_exists('show')) {
     function show($arr,$lbl=''){echo "<br /><pre><b>$lbl</b> ";print_r($arr);echo "</pre>";} 
} //}}}

///EOF