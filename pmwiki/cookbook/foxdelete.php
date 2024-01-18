<?php if (!defined('PmWiki')) exit();

/* foxdelete.php  Copyright Hans Bracker 2014.
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published
   by the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
   Needs PmWiki 2.2.56+
*/
$RecipeInfo['FoxDelete']['Version'] = '2019-11-11';

SDV($EnableFoxPageDelete, true); #enable general page deletes
SDV($EnableFoxPostDelete, true); #enable general deletes for ranges and lines

# add action foxdelete
$HandleActions['foxdelete'] = 'FoxHandleDelete';

function FoxHandleDelete($pagename) {
	#echo $pagename; exit;
	global $FoxAuth,$FoxPageDeleteKey,$EnableFoxPageDelete,$EnableFoxPostDelete, $ChangeSummary, 
		$Now, $EditFunctions, $IsPagePosted, $FoxMsgFmt;
	$args = FoxRequestArgs(); //fetch GET or POST arguments
	$key = $args['key'];  # Retrieve the delete-key
	if ($key=='') FoxAbort($pagename, "ERROR: Delete key is missing. ");
	$target = (isset($args['target'])) ? $args['target'] : $pagename;
	# delete permission set by $FoxAuth
	$page = RetrieveAuthPage($target, $FoxAuth, true);
	#check page permissions for 'pagedelete' or 'delete'
	if (($key==$target && FoxPagePermission($pagename, 'pagedelete', $target, $page, '') == false)
		OR FoxPagePermission($pagename, 'delete', $target, $page, '') == false)
			FoxAbort($pagename, "Deleting page [[$target]] aborted");
	#check general permission
	if (($key!=$target && $EnableFoxPostDelete==false) OR ($key==$target && $EnableFoxPageDelete==false)) 
		FoxAbort($pagename, "Deletion not enabled! ");
	if (!$page) FoxAbort($pagename, "ERROR: Cannot read $page! ");
	$new = $page; 
	# trim text and add newline so the following regexes also work for the last line
	$text = rtrim($page['text'])."\n";
	$old = $text; 
	# Remove the line containing the delete statement with the provided key
	$text = preg_replace('/^.*\\{\\[foxdelline(| button)? '.$key.'.*\\n/m',"",$text); 
	# Remove the range containing the delrange statement with the provided key 
	$text = preg_replace('/#foxbegin '.$key.'#.*?\\{\\[foxdelrange(| ?button) '.$key.' .*?#foxend '.$key.'#.*?\n/s',"",$text);
	#delete entire page by posting delete keyword as defined for PmWiki, normally 'delete'
	SDV($FoxPageDeleteKey, 'delete');
	if ($key==$target && $EnableFoxPageDelete==true) $text = $FoxPageDeleteKey;
	# if nothing changed abort
	if($old==$text)	FoxAbort($pagename, "ERROR: Delete action was unsuccessful! ");
	# Remove the added newline character (or any whitespace from the end)
	$text = rtrim($text);
	#remove unnecessary edit functions before saving
	unset($EditFunctions['EditTemplate'], $EditFunctions['RestorePage'],
			$EditFunctions['AutoCreateTargets'], $EditFunctions['PreviewPage']); 
	# save page
	$new['text'] = $text;
	$new['csum'] = $ChangeSummary;
  	if ($ChangeSummary) $new["csum:$Now"] = $ChangeSummary;
	$IsPagePosted = UpdatePage($target, $page, $new, $EditFunctions);
	# set up page redirection, cater for deletelink ($_GET)
	if(@$args['base']) $pagename = $args['base'];
	Redirect($pagename);	
} //}}}


Markup('foxdelete','directives','/\{\[foxdel(line|range|page)\\s?(|button)\\s*(.*?)\\s*\]}/',
		"FoxDeleteMarkup");
# Creates the HTML code for delete links {[foxdelline]}, {[foxdelrange]}
# and delete buttons {[foxdelline button]} and {[foxdelrange button]}
function FoxDeleteMarkup($m) {	
	global $ScriptUrl, $EnablePathInfo, $EnableFoxPageDelete,$EnableFoxPostDelete, $EnableFoxDeleteMsg, $FoxDeleteMsg, $FoxDeleteSummaryMsg;
	extract($GLOBALS['MarkupToHTML']);
	SDV($EnableFoxDeleteMsg, false); //set to true to enable post delete confirmation
	SDV($FoxDeleteMsg, '$[Please confirm: Do you want to delete this post?]');
	SDV($FoxDelPageMsg, '$[Please confirm: Do you want to delete the page]');	
	SDV($FoxDeleteSummaryMsg, '$[Post deleted]'); //if you don't want a summary message, set it to ''
	SDV($FoxDelPageSummaryMsg, '$[Page deleted]');
	$range = $m[1]; $type = $m[2];
	$opt = ParseArgs($m[3]);
	$par = (array)@$opt[''];
	if($par[0]=='button') { $type = 'button';	array_shift($par); }
	if($range=='page') {
		if($EnableFoxPageDelete==false) return; #generate no links for page delete
		if (isset($opt['target'])) $target = $opt['target'];
		else $target = (empty($par))? $pagename : array_shift($par);
		$key = $target = FoxGroupName($pagename,'',$target);
		$summary = $FoxDelPageSummaryMsg;
	} else {
		if($EnableFoxPostDelete==false) return; #generate no links for post and line delete
		$key = array_shift($par);
		$target = (isset($opt['target'])) ? $opt['target'] : array_shift($par);
		$summary = $FoxDeleteSummaryMsg;
	}
	$label = (isset($opt['label'])) ? $opt['label'] : array_shift($par);
	# tooltip for delete link
	if (isset($opt['tooltip'])) $tooltip = "title='{$opt['tooltip']}'";
	else if (isset($opt['title'])) $tooltip = "title='{$opt['title']}'";
	else $tooltip = '';
	if ($target=="") $target = $pagename;
	$TargetPageUrl = PUE(($EnablePathInfo)
			? "$ScriptUrl/$target"
         : "$ScriptUrl?n=$target");
	# javascript delete message dialogue
	if($EnableFoxDeleteMsg==true && $range!='page')
		$onclick = "onclick='return confirm(\"{$FoxDeleteMsg}\")'";
	else $onclick = "";
	if($range=='page') {
		$onclick = "onclick='return confirm(\"{$FoxDelPageMsg} ".$target." ?\")'";
		$tooltip = (isset($opt['tooltip'])) ? "title='{$opt['tooltip']}'" : "title='{$target}'";
	}
	if($label=='')
		$label = ($range=='page')? '$[Delete Page]' : '$[Delete]';
	#construct HTML output
	if($type=='button') {
	# delete button	
	$out = FmtPageName("<form class='foxdelbutton' action='{$TargetPageUrl}' method='post'>
			<input type='hidden' name='n' value='$target' />
			<input type='hidden' name='base' value='$pagename' />
			<input type='hidden' name='action' value='foxdelete' />
			<input type='hidden' name='csum' value='$summary' />
			<input type='hidden' name='key' value='$key' />
			<input type='submit' name='doit' value='{$label}' class='inputbutton' {$onclick}/>
			</form>", $target);
	}
	else {
		# delete link which works with and without javascript:
		$out = FmtPageName("<a class='foxdellink' $tooltip href='$TargetPageUrl?action=foxdelete&amp;key=$key&amp;target=$target&amp;base=$pagename&amp;csum=$summary' rel='nofollow'
			{$onclick}>{$label}</a>",$target);
	}
	return Keep($out);
} //}}}