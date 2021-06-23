<?php if (!defined('PmWiki')) exit();
/*  foxnotify.php, a recipe module for PmWiki and Fox (fox.php)
    Copyright 2008 Hans Bracker
    Copyright 2006 Patrick R. Michaud (pmichaud@pobox.com)
    This file is part of PmWiki; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.  See pmwiki.php for full details.

    This script enables email notifications to be sent when posts
    are made.  See Cookbook.FoxNotify for details of usage.

    Several variables set defaults for this script, set different values in config.php:
    $FoxNotifyDelay - number of seconds to wait before sending mail
        after the first post.
    $FoxNotifySquelch - minimum number of seconds between sending email
        messages to each address.  Individual "notify=" lines in
        Site.NotifyList can override this value via a custom "squelch="
        parameter.
    $FoxNotifyFile - scratchpad file used to keep track of pending emails.    
    $FoxNotifyListsGroup - name of the group holding FoxNotifyList pages.
    $FoxGeneralNotifyList - name of general notify list page.
    $FoxNotifySubjectFmt - subject line for sent messages.
    $FoxNotifyFrom - From: line for sent messages.
    $FoxRecipient - string in template or body which gets replaced with recipient's name,
        as supplied by a recipient= parameter from the FoxNotifyLists lines.
    $FoxNotifyBodyFmt - body of message to be sent.  The string '$FoxNotifyItems'
        is replaced with the list of posts in the email.
    $FoxNotifyBodyHeadingFmt - heading of body to be sent (top of body, above items).
    $FoxNotifyItemFmt - the default format for each post to be included in a notification.
    $FoxNotifyTemplatePageFmt - name of email template page.
    $FoxNotifyTimeFmt - the format for dates and times ($PostTime) 
        in notification messages.
    $FoxNotifyHeaders - any additional message headers to be sent.
    $FoxNotifyParameters - any additional parameters to be passed to PHP's
        mail() function.
    $EnableFoxNotifyHTMLEmail = true; - (default is false) email will be HTML formatted, 
             wiki markup can be used in email template and form input for email body.
*/
$RecipeInfo['FoxNotify']['Version'] = '2015-11-17';

SDV($FoxNotifyDelay, 0);
SDV($FoxNotifySquelch, 0);
SDV($FoxNotifyFile, "$WorkDir/.foxnotifylist");
SDV($FoxNotifyListsGroup, 'FoxNotifyLists');
SDV($FoxGeneralNotifyList, "$FoxNotifyListsGroup.GeneralNotifyList");
SDV($FoxNotifySubjectFmt, "$WikiTitle recent notify posts");
//example//SDV($FoxNotifyFrom, '$WikiTitle-Wiki Server<$WikiTitle-notify@example.co.uk>');
SDV($FoxRecipient, XL('Mail Recipient'));
SDV($FoxNotifyHeaders, '');
SDV($FoxNotifyParameters, '');
SDV($FoxNotifyBodyHeadingFmt, "Recent $WikiTitle posts: \n");
SDV($FoxNotifyBodyFmt, 
   "\$FoxNotifyBodyHeadingFmt\n" 
   ."\$FoxNotifyItems\n");
SDV($FoxNotifyTimeFmt, $TimeFmt);
SDV($FoxNotifyTemplatePageFmt, "$FoxNotifyListsGroup.FoxNotifyTemplates");
SDV($FoxNotifyItemFmt, 
   "* $ScriptUrl/{\$FullName} . . . \$PostTime by {\$LastModifiedBy} \n");

if (@$EnableFoxNotifyHTMLEmail) 
	$FoxNotifyHeaders = 'MIME-Version: 1.0' . "\r\n"
							 . 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
if (@$FoxNotifyFrom)
   $FoxNotifyHeaders = "From: $FoxNotifyFrom\r\n$FoxNotifyHeaders";

$EditFunctions[] = 'FoxPostNotify';
function FoxPostNotify($pagename) {
   global $IsPagePosted;
   $req = FoxRequestArgs($_POST); 
   if ($IsPagePosted) register_shutdown_function('FoxNotifyUpdate', $pagename, getcwd(), $req, '');
}

##   check if we need to do notifications
if ($action != 'edit' && $action!='foxpost' && $action!='foxdelete' && $action!='comment') 
		FoxNotifyActionCheck($pagename);

function FoxNotifyActionCheck($pagename) {
  global $FoxNotifyFile, $Now, $LastModTime;
  $nfp = @fopen($FoxNotifyFile, 'r');
  if (!$nfp) return;
  $nextevent = fgets($nfp);
  fclose($nfp);
  if ($Now < $nextevent && $LastModTime < filemtime($FoxNotifyFile)) return;
  register_shutdown_function('FoxNotifyUpdate', $pagename, getcwd(), '', '');
}

## handle mailing of notifications
function FoxNotifyUpdate($pagename, $dir='', $fx, $targ) { 	
  global $FoxDebug, $FoxNotifyList, $FoxNotifyLists, $FoxGeneralNotifyList, $FoxNotifyFile, $IsPagePosted,
    $FmtV, $FoxNotifyTimeFmt, $FoxNotifyItemFmt, $FoxNotifyTemplatePageFmt, $FoxNotifyListsGroup,
    $FoxNotifySquelch, $FoxNotifyDelay, $Now, $SearchPatterns, $WikiTitle, $FoxRecipient,
    $FoxNotifySubjectFmt, $FoxNotifyBodyFmt, $FoxNotifyBodyHeadingFmt, $FoxNotifyHeaders, 
    $FoxNotifyParameters, $EnableFoxNotifyHTMLEmail, $WikiTitle;
  if($FoxDebug>4) echo "FOXNOTIFY (mail)><br>";
  $abort = ignore_user_abort(true);
  if ($dir) { flush(); chdir($dir); }
  $GLOBALS['EnableRedirect'] = 0;

  ##   Read in the current notify configuration, merge notify lines from all list pages
  $fnlines = (array)@$FoxNotifyList;
  $FoxNotifyLists[] = $FoxGeneralNotifyList; 
  foreach($FoxNotifyLists as $fnl) {
     $pn = FmtPageName($fnl, $pagename);
     $npage = ReadPage($pn, READPAGE_CURRENT);
     preg_match_all('/^[\s*:#->]*(notify[:=].*)/m', $npage['text'], $nline);
     $nline = $nline[1];
     $fnlines = array_merge($fnlines, $nline);
     if ($fnlines=="") return;
  }
  ##   make sure other processes are locked out
  Lock(2);  
  ##   let's load the current .notifylist table
  $nfile = FmtPageName($FoxNotifyFile, $pagename);
  $nfp = @fopen($nfile, 'r');
  if ($nfp) {
    ##   get our current squelch and delay timestamps
    clearstatcache();
    $sz = filesize($nfile);
    list($nextevent, $firstpost) = explode(' ', rtrim(fgets($nfp, $sz)));
    ##   restore our notify array
    $notify = unserialize(fgets($nfp, $sz));
    fclose($nfp);
  }  
  if (!is_array($notify)) $notify = array();

  ##   get newly posted page information
  if ($IsPagePosted) { 
     $page = ReadPage($pagename, READPAGE_CURRENT);
     $FmtV['$PostTime'] = strftime($FoxNotifyTimeFmt, $Now);
     if ($firstpost < 1) $firstpost = $Now;
     // retrieve formats from template
     $tpname = FmtPageName($FoxNotifyTemplatePageFmt, $pagename);
     if(PageExists($tpname)) {
     		$tpage = ReadPage($tpname, READPAGE_CURRENT);
     		$tsubject = trim(TextSection($tpage['text'], '#subject'),"\r\n");
     		$tbodyheading = trim(TextSection($tpage['text'], '#heading'),"\r\n");
     }
    if(!$tsubject) $tsubject = $FoxNotifySubjectFmt;
     $tsubject = FmtPageName($tsubject, $pagename);
     $subject = FoxTemplateEngine($pagename, $tsubject, $fx, $targ,'', 'FoxNotify');
     if($tbodyheading) $FoxNotifyBodyHeadingFmt = $tbodyheading;
  }
  foreach($fnlines as $n) {
    $opt = ParseArgs($n);
    $mailto = preg_split('/[\s,]+/', $opt['notify']);
    if (isset($fx['foxsendto']) && in_array('foxsendto', $mailto)) {
    	$skey = array_search('foxsendto', $mailto);
    	$mailto[$skey] = $fx['foxsendto'];
	}
	if (!$mailto) continue;
    if ($opt['recipient'])
      foreach($mailto as $m) $toName[$m] = $opt['recipient'];
    if ($opt['squelch']) 
      foreach($mailto as $m) $squelch[$m] = $opt['squelch'];
    if (!$IsPagePosted) continue;
    if ($opt['link']) {
      $link = MakePageName($pagename, $opt['link']);
      if (!preg_match("/(^|,)$link(,|$)/i", $page['targets'])) continue;
    }
    $pats = @(array)$SearchPatterns[$opt['list']];
    if ($opt['group']) $pats[] = FixGlob($opt['group'], '$1$2.*');
    if ($opt['name']) $pats[] = FixGlob($opt['name'], '$1*.$2');
    if ($pats && !MatchPageNames($pagename, $pats)) continue;
    if ($opt['trail']) {
      $trail = ReadTrail($pagename, $opt['trail']);
      for ($i=0; $i<count($trail); $i++) 
        if ($trail[$i]['pagename'] == $pagename) break;
      if ($i >= count($trail)) continue;
    }
   
   //try to load template from template page
   $tpl = @$opt['template'];
   if (!$tpl) $tpl = @$opt['format'];
   if (!$tpl) $tpl = '#default';
  	if($tpage) $titem = trim(TextSection($tpage['text'], $tpl),"\r\n");
   // replace any {$$var} with values from input fields
   global $EnablePostDirectives; $EnablePostDirectives = 1;
   if(@$_GET['csum']) $_POST['csum'] = $_GET['csum'];
   $titem = FoxTemplateEngine($pagename, $titem, $fx, $targ,'', 'FoxNotify');
   if(!$titem) $titem = $FoxNotifyItemFmt;
   $item = urlencode(FmtPageName($titem, $pagename));
   
   foreach ($mailto as $m) 
       $notify[$m][] = $item;
  }
  $nnow = time();
  if ($nnow < $firstpost + $FoxNotifyDelay) 
     $nextevent = $firstpost + $FoxNotifyDelay;
  else {
     $firstpost = 0;
     $nextevent = $nnow + 86400;
     $mailto = array_keys($notify);
     $body = FmtPageName($FoxNotifyBodyFmt, $pagename);
     $headers = FmtPageName($FoxNotifyHeaders, $pagename);
     foreach ($mailto as $m) {
        $msquelch = @$notify[$m]['lastmail'] +
                    ((@$squelch[$m]) ? $squelch[$m] : $FoxNotifySquelch);
        if ($nnow < $msquelch) {
           if ($msquelch < $nextevent && count($notify[$m])>1)
              $nextevent = $msquelch;
        continue;
        }
        unset($notify[$m]['lastmail']);
        if (!$notify[$m]) { unset($notify[$m]); continue; }

        $item = urldecode(implode("\n", $notify[$m]));
        $mbody = str_replace('$FoxNotifyItems', $item, $body);
        if($toName[$m]) 
           $mbody = str_replace($FoxRecipient, $toName[$m], $mbody);
		  if (@$EnableFoxNotifyHTMLEmail)         
        		$mbody = MarkupToHTML($pagename, $mbody);
        if ($FoxNotifyParameters)
           mail($m, $subject, $mbody, $headers, $FoxNotifyParameters);
        else 
           mail($m, $subject, $mbody, $headers);
        $notify[$m] = array('lastmail' => $nnow);
     }
  }
  ##   save the updated notify status
  $nfp = @fopen($nfile, "w");
  if($nfp) { 
     fputs($nfp, "$nextevent $firstpost\n");
     fputs($nfp, serialize($notify) . "\n");
     fclose($nfp);
  }
  Lock(0);
  return true;
}

/// EOF