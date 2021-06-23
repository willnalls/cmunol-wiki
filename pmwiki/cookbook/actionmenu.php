<?php if (!defined('PmWiki')) exit();
/**
  ActionMenu for PmWiki
  Written by (c) Petko Yotov 2011-2017
  
  This text is written for PmWiki; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 3 of the License, or
  (at your option) any later version. See pmwiki.php for full details
  and lack of warranty.

  Copyright 2011-2017 Petko Yotov www.pmwiki.org/petko
*/
# Version date
$RecipeInfo['ActionMenu']['Version'] = '20170618';

Markup('actionmenu', '<block', '/^\\(:actions (.*?):\\)/', 'FmtActionMenu');
$HandleActions['backlinks'] = "HandleSearchA";
if($action=="backlinks") $_REQUEST['q'] = $_GET['q'] = "link=$pagename";

function FmtActionMenu($m) {
  global $action, $ActionMenuFmt;
  static $actmnu_num = 0; $actmnu_num++;
  
  extract($GLOBALS["MarkupToHTML"]);
  
  $opt = ParseArgs($m[1]);
  $purl = str_replace('%', '%%', PageVar($pagename, '$PageUrl'));
  $f = <<<EOF
<form class='actionmenu' method='get' action='$purl'>%3\$s
<select name='action' onchange='var a=this.options[this.selectedIndex].value, u=this.form.getAttribute("action")+(a=="browse"? "" : "?action="+a); self.location=u;'>%1\$s</select>
<input id="actmnu_go_%2\$d" type='submit' value='$[Go]'/>%4\$s
<script type='text/javascript'><!--
document.getElementById('actmnu_go_%2\$d').style.display='none';//--></script>
</form>
EOF;
  SDV($ActionMenuFmt, $f);
  
  $options = (in_array($action, array_keys($opt))) ? '' : "<option value=''></option>";
  foreach($opt as $a=>$label) {
    if($a=='#' || $a == '') continue;
    if($a == "logout" && @$GLOBALS["AuthPw"] == "") continue;
    if($label{0}=='?') {
      if(! CondAuth($pagename, $a)) continue;
      $label = substr($label, 1);
    }
    $selected = ($a==$action)? ' selected="selected"' : '';
    $options .= "<option class='$a' value='$a'$selected>$label</option>";
  }
  return "<:block,1>" . Keep( sprintf(FmtPageName($ActionMenuFmt, $pagename), $options, $actmnu_num, @$opt[''][0], @$opt[''][1]) );
}
