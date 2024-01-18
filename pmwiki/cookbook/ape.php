<?php if (!defined('PmWiki')) exit();
/**
  Ape - Automatic embedding of video players and maps for PmWiki
  Written by (c) Petko Yotov 2014-2020    www.pmwiki.org/petko

  This text is written for PmWiki; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published
  by the Free Software Foundation version 2.
*/
$RecipeInfo['Ape']['Version'] = '20200723a';

SDVA($Ape, array(
  'dir' => "$FarmD/pub/ape",
  'dirurl' => '$FarmPubDirUrl/ape',
  'snippet' => '<script type="text/javascript" src="{dirurl}/{fname}?{mtime}"></script>',
  'scripts' => array('ape-local.js', 'ape.js'),
));

SDVA($MarkupFrameBase['posteval'], array('Ape'=>'FmtApe($out);'));

function FmtApe($out) {
  
  if(!preg_match("/<(span|div|p|dl) ([^>]|\n)*class='([^']* )?(player|map|embed)( [^']*)?'/s", $out)) return;
  
  global $HTMLFooterFmt, $Ape;
  
  foreach($Ape['scripts'] as $s) {
    if(isset($HTMLFooterFmt[$s])) continue;
    
    $path = "{$Ape['dir']}/$s";
    if(! file_exists($path)) {
      $HTMLFooterFmt[$s] = ''; 
      continue;
    }
    
    $mtime = (strpos($Ape['snippet'], '{mtime}')>0) ? filemtime($path) : '';
    
    $snippet = str_replace(
      array('{dirurl}',    '{fname}', '{mtime}'),
      array($Ape['dirurl'],   $s,      $mtime  ),
      $Ape['snippet']
    );
    
    $HTMLFooterFmt[$s] = $snippet;
  }
}

function ApeCheckCache($pagename) {
  global $PageCacheDir;
  if(IsEnabled($PageCacheDir))
    FmtApe("<span class='embed'>");
}
$PostConfig['ApeCheckCache'] = 400;


