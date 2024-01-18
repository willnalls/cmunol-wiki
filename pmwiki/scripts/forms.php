<?php if (!defined('PmWiki')) exit();
/*  Copyright 2005-2022 Patrick R. Michaud (pmichaud@pobox.com)
    This file is part of PmWiki; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.  See pmwiki.php for full details.
    
    Script maintained by Petko YOTOV www.pmwiki.org/petko
*/

# $InputAttrs are the attributes we allow in output tags
SDV($InputAttrs, array('name', 'value', 'id', 'class', 'rows', 'cols', 
  'size', 'maxlength', 'action', 'method', 'accesskey', 'tabindex', 'multiple',
  'checked', 'disabled', 'readonly', 'enctype', 'src', 'alt', 'title', 'list',
  'required', 'placeholder', 'autocomplete', 'min', 'max', 'step', 'pattern',
  'role', 'aria-label', 'aria-labelledby', 'aria-describedby',
  'aria-expanded', 'aria-pressed', 'aria-current', 'aria-hidden',
  'lang', 'formnovalidate', 'autofocus', 'accept'
  ));

# Set up formatting for text, submit, hidden, radio, etc. types
foreach(array('text', 'submit', 'hidden', 'password', 'reset', 'file',
    'image', 'email', 'url', 'tel', 'number', 'search', 'date', 'month', 
    'color', 'button') as $t) 
  SDV($InputTags[$t][':html'], "<input type='$t' \$InputFormArgs />");

foreach(array('text', 'password', 'email', 'url', 'tel', 'number', 'search', 'date', 'month') as $t) 
  SDV($InputTags[$t]['class'], "inputbox");

foreach(array('submit', 'button', 'reset', 'color') as $t) 
  SDV($InputTags[$t]['class'], "inputbutton");

foreach(array('radio', 'checkbox') as $t) 
  SDVA($InputTags[$t], array(
    ':html' => "<input type='$t' \$InputFormArgs />\$InputFormLabel",
    ':args' => array('name', 'value', 'label'),
    ':checked' => 'checked'));

# (:input form:)
SDVA($InputTags['form'], array(
  ':args' => array('action', 'method'),
  ':html' => "<form \$InputFormArgs>",
  'method' => 'post'));

# (:input end:)
SDV($InputTags['end'][':html'], '</form>');

# (:input textarea:)
SDVA($InputTags['textarea'], array(
  ':content' => array('value'),
  ':attr' => array_diff($InputAttrs, array('value')),
  ':html' => "<textarea \$InputFormArgs>\$InputFormContent</textarea>"));

# (:input image:)
SDV($InputTags['image'][':args'], array('name', 'src', 'alt'));

# (:input select:)
SDVA($InputTags['select-option'], array(
  ':args' => array('name', 'value', 'label'),
  ':content' => array('label', 'value', 'name'),
  ':attr' => array('value', 'selected'),
  ':checked' => 'selected',
  ':html' => "<option \$InputFormArgs>\$InputFormContent</option>"));
SDVA($InputTags['select'], array(
  'class' => 'inputbox',
  ':html' => "<select \$InputSelectArgs>\$InputSelectOptions</select>"));

# (:input datalist:)
SDVA($InputTags['datalist-option'], array(
  ':args' => array('id', 'value'),
  ':attr' => array('value'),
  ':html' => "<option \$InputFormArgs>"));
SDVA($InputTags['datalist'], array(
  ':html' => "<datalist \$InputSelectArgs>\$InputSelectOptions</datalist>"));

# (:input defaults?:)
SDVA($InputTags['default'], array(':fn' => 'InputDefault'));
SDVA($InputTags['defaults'], array(':fn' => 'InputDefault'));
SDVA($InputTags['pmtoken'], array(':fn' => 'InputPmToken'));

SDV($InputLabelFmt, ' <label for="$LabelFor" $LabelTitle>$LabelText</label> ');

##  (:input ...:) directives
Markup('input', 'directives',
  '/\\(:input\\s+(\\w+)(.*?):\\)/i',
  "MarkupInputForms");

##  (:input select:) has its own markup processing
Markup('input-select', '<input',
  '/\\(:input\\s+select\\s.*?:\\)(?:\\s*\\(:input\\s+select\\s.*?:\\))*/i',
  "MarkupInputForms");

##  (:input datalist:) has its own markup processing
Markup('input-datalist', '<input',
  '/\\(:input\\s+datalist\\s.*?:\\)(?:\\s*\\(:input\\s+datalist\\s.*?:\\))*/i',
  "MarkupInputForms");

function MarkupInputForms($m) {
  extract($GLOBALS["MarkupToHTML"]); # get $pagename, $markupid
  switch ($markupid) {
    case 'input': 
      return InputMarkup($pagename, $m[1], $m[2]);
    case 'input-select': 
      return InputSelect($pagename, 'select', $m[0]);
    case 'input-datalist': 
      return InputSelect($pagename, 'datalist', $m[0]);
    case 'e_preview': 
      return isset($GLOBALS['FmtV']['$PreviewText']) 
        ? Keep($GLOBALS['FmtV']['$PreviewText']): '';
  }
}

##  The 'input+sp' rule combines multiple (:input select ... :)
##  into a single markup line (to avoid split line effects)
Markup('input+sp', '<split', 
  '/(\\(:input\\s+(select|datalist)\\s(?>.*?:\\)))\\s+(?=\\(:input\\s)/', '$1');

##  InputToHTML performs standard processing on (:input ...:) arguments,
##  and returns the formatted HTML string.
function InputToHTML($pagename, $type, $args, &$opt) {
  global $InputTags, $InputAttrs, $InputValues, $FmtV, $KeepToken,
    $InputFocusLevel, $InputFocusId, $InputFocusFmt, $HTMLFooterFmt,
    $EnableInputDataAttr, $InputLabelFmt;
  if (!@$InputTags[$type]) return "(:input $type $args:)";
  ##  get input arguments
  if (!is_array($args)) $args = ParseArgs($args, '(?>([\\w-]+)[:=])');
  ##  convert any positional arguments to named arguments
  $posnames = @$InputTags[$type][':args'];
  if (!$posnames) $posnames = array('name', 'value');
  while (count($posnames) > 0 && @$args[''] && count($args['']) > 0) {
    $n = array_shift($posnames);
    if (!isset($args[$n])) $args[$n] = array_shift($args['']);
  }
  
  
  ##  merge defaults for input type with arguments
  $opt = array_merge($InputTags[$type], $args);
  ## www.w3.org/TR/html4/types
  if (isset($opt['id'])) $opt['id'] = preg_replace('/[^-A-Za-z0-9:_.]+/', '_', $opt['id']);
  ##  convert any remaining positional args to flags
  foreach ((array)@$opt[''] as $a) 
    { $a = strtolower($a); if ( preg_match('/^\\w+$/', $a) && !isset($opt[$a])) $opt[$a] = $a; }
  if (isset($opt['name'])) {
    $opt['name'] = preg_replace('/^\\$:/', 'ptv_', @$opt['name']);
    $opt['name'] = preg_replace('/[^-A-Za-z0-9:_.\\[\\]]+/', '_', $opt['name']);
    $name = $opt['name'];
    ##  set control values from $InputValues array
    ##  radio, checkbox, select, etc. require a flag of some sort,
    ##  others just set 'value'
    if (isset($InputValues[$name])) {
      $checked = @$opt[':checked'];
      if ($checked) {
        $opt[$checked] = in_array(@$opt['value'], (array)$InputValues[$name])
                         ? $checked : false;
      } else if (!isset($opt['value'])) $opt['value'] = $InputValues[$name];
    }
    if ( (strpos($name, 'ptv_') === 0) && !isset($opt['value']) ) {
      # $DefaultUnsetPageTextVars, $DefaultEmptyPageTextVars with wildcards
      $default = PageTextVar($pagename, substr($name, 4));
      if ($default !== '') $opt['value'] = $default;
    }
  }
  ##  build $InputFormContent
  $FmtV['$InputFormContent'] = '';
  foreach((array)@$opt[':content'] as $a)
    if (isset($opt[$a])) { 
      $FmtV['$InputFormContent'] = is_array($opt[$a]) ? $opt[$a][0]: $opt[$a];
      break; 
    }
  ##  hash and store any "secure" values
  if (@$opt['secure'] == '#') $opt['secure'] = rand();
  if (@$opt['secure'] > '') {
    $md5 = md5($opt['secure'] . $opt['value']);
    pm_session_start(); 
    $_SESSION['forms'][$md5] = $opt['value'];
    $opt['value'] = $md5;
  }
  ## labels for checkbox and radio
  $FmtV['$InputFormLabel'] = '';
  if (isset($opt['label']) && strpos($InputTags[$type][':html'], '$InputFormLabel')!==false) {
    static $labelcnt = 0;
    if (!isset($opt['id'])) $opt['id'] = "lbl_". (++$labelcnt);
    $FmtV['$LabelTitle'] = isset($opt['title']) ? " title='".str_replace("'", '&#39;', $opt['title'])."'" : '';
    $FmtV['$LabelFor'] = $opt['id'];
    $FmtV['$LabelText'] = $opt['label'];
    $FmtV['$InputFormLabel'] = FmtPageName($InputLabelFmt, $pagename);
  }
  ##  handle focus=# option
  if (@$opt['focus']) {
    unset($opt['focus']);
    $opt['autofocus'] = 'autofocus';
  }
  ##  build $InputFormArgs from $opt
  $attrlist = (isset($opt[':attr'])) ? $opt[':attr'] : $InputAttrs;
  if (IsEnabled($EnableInputDataAttr, 1)) {
    $dataattr = preg_grep('/^data-[-a-z]+$/', array_keys($opt));
    $attrlist = array_merge($attrlist, $dataattr);
  }
  $attr = array();
  foreach ($attrlist as $a) {
    if (!isset($opt[$a]) || $opt[$a]===false) continue;
    if (is_array($opt[$a])) $opt[$a] = $opt[$a][0];
    if (strpos($opt[$a], $KeepToken)!== false) # multiline textarea/hidden fields
      $opt[$a] = Keep(str_replace("'", '&#39;', MarkupRestore($opt[$a]) ));
    $attr[] = "$a='".str_replace("'", '&#39;', $opt[$a])."'";
  }
  $FmtV['$InputFormArgs'] = implode(' ', $attr);
  return FmtPageName($opt[':html'], $pagename);
}


##  InputMarkup handles the (:input ...:) directive.  It either
##  calls any function given by the :fn element of the corresponding
##  tag, or else just returns the result of InputToHTML().
function InputMarkup($pagename, $type, $args) {
  global $InputTags;
  $fn = @$InputTags[$type][':fn'];
  if ($fn) return $fn($pagename, $type, $args);
  return Keep(InputToHTML($pagename, $type, $args, $opt));
}


##  (:input default:) directive.
function InputDefault($pagename, $type, $args) {
  global $InputValues, $PageTextVarPatterns, $PCache;
  $args = ParseArgs($args);
  $args[''] = (array)@$args[''];
  $name = (isset($args['name'])) ? $args['name'] : array_shift($args['']);
  $name = $name ? preg_replace('/^\\$:/', 'ptv_', $name) : '';
  $value = (isset($args['value'])) ? $args['value'] : $args[''];
  if (!isset($InputValues[$name])) $InputValues[$name] = $value;
  if (@$args['request']) {
    $req = RequestArgs();
    foreach($req as $k => $v) {
      if (is_array($v)) {
        foreach($v as $vk=>$vv) {
          if (is_numeric($vk)) $InputValues["{$k}[]"][] = PHSC($vv, ENT_NOQUOTES);
          else $InputValues["{$k}[{$vk}]"] = PHSC($vv, ENT_NOQUOTES);
        }
      }
      else {
        if (!isset($InputValues[$k])) 
          $InputValues[$k] = PHSC($v, ENT_NOQUOTES);
      }
    }
  }
  $sources = @$args['source'];
  if ($sources) {
    foreach(explode(',', $sources) as $source) {
      $source = MakePageName($pagename, $source);
      if (!PageExists($source)) continue;
      $page = RetrieveAuthPage($source, 'read', false, READPAGE_CURRENT);
      if (! $page || ! isset($page['text'])) continue;
      foreach((array)$PageTextVarPatterns as $pat)
        if (preg_match_all($pat, IsEnabled($PCache[$source]['=preview'], $page['text']), 
          $match, PREG_SET_ORDER))
          foreach($match as $m)
#           if (!isset($InputValues['ptv_'.$m[2]])) PITS:01337
              $InputValues['ptv_'.$m[2]] = 
                PHSC(Qualify($source, $m[3]), ENT_NOQUOTES);
      break;
    }
  }
  
  return '';
}


##  (:input select ...:) is special, because we need to process a bunch of
##  them as a single unit.
function InputSelect($pagename, $type, $markup) {
  global $InputTags, $InputAttrs, $FmtV;
  preg_match_all('/\\(:input\\s+\\S+\\s+(.*?):\\)/', $markup, $match);
  $selectopt = (array)$InputTags[$type];
  $opt = $selectopt;
  $optionshtml = '';
  $optiontype = isset($InputTags["$type-option"]) 
                ? "$type-option" : "select-option";
  foreach($match[1] as $args) {
    $optionshtml .= InputToHTML($pagename, $optiontype, $args, $oo);
    $opt = array_merge($opt, $oo);
  }
  $attrlist = array_diff($InputAttrs, array('value'));
  $attr = array();
  foreach($attrlist as $a) {
    if (!isset($opt[$a]) || $opt[$a]===false) continue;
    $attr[] = "$a='".str_replace("'", '&#39;', $opt[$a])."'";
  }
  $FmtV['$InputSelectArgs'] = implode(' ', $attr);
  $FmtV['$InputSelectOptions'] = $optionshtml;
  return Keep(FmtPageName($selectopt[':html'], $pagename));
}

##  (:input pmtoken:) helper
function InputPmToken($pagename, $type, $args) {
  global $FmtV;
  $token = pmtoken();
  return "<input type='hidden' name='{$FmtV['$TokenName']}' value='$token' />";
}

function InputActionForm($pagename, $type, $args) {
  global $InputAttrs;
  $args = ParseArgs($args);
  if (@$args['pagename']) $pagename = $args['pagename'];
  $opt = NULL;
  $html = InputToHTML($pagename, $type, $args, $opt);
  foreach(preg_grep('/^[\\w$]/', array_keys($args)) as $k) {
    if (is_array($args[$k]) || in_array($k, $InputAttrs)) continue;
    if ($k == 'n' || $k == 'pagename') continue;
    $html .= "<input type='hidden' name='$k' value='{$args[$k]}' />";
  }
  return Keep($html);
}


## RequestArgs is used to extract values from controls (typically
## in $_GET and $_POST).
function RequestArgs($req = NULL) {
  if (is_null($req)) $req = array_merge($_GET, $_POST);
  foreach ($req as $k => $v) {
    if (is_array($v)) $req[$k] = RequestArgs($v);
    else $req[$k] = stripmagic($req[$k]);
  }
  return $req;
}


## Form-based authorization prompts (for use with PmWikiAuth)
SDVA($InputTags['auth_form'], array(
  ':html' => "<form \$InputFormArgs>\$PostVars",
  'action' => str_replace("'", '%37', stripmagic($_SERVER['REQUEST_URI'])),
  'method' => 'post',
  'name' => 'authform'));
SDV($AuthPromptFmt, array(&$PageStartFmt, 'page:$SiteGroup.AuthForm',
  &$PageEndFmt));

## PITS:01188, these should exist in "browse" mode
## NOTE: also defined in prefs.php
XLSDV('en', array(
  'ak_save' => 's',
  'ak_saveedit' => 'u',
  'ak_preview' => 'p',
  'ak_textedit' => ',',
  'e_rows' => '23',
  'e_cols' => '60'));

## The section below handles specialized EditForm pages.
## We don't bother to load it if we're not editing.

if ($action != 'edit') return;

pmtoken();

SDV($PageEditForm, '$SiteGroup.EditForm');
SDV($PageEditFmt, '$EditForm');
if (@$_REQUEST['editform']) {
  $PageEditForm=$_REQUEST['editform'];
  $PageEditFmt='$EditForm';
}
$Conditions['e_preview'] = '(boolean)$_REQUEST["preview"]';

# (:e_preview:) displays the preview of formatted text.
Markup('e_preview', 'directives',
  '/^\\(:e_preview:\\)/', "MarkupInputForms");

# If we didn't load guiedit.php, then set (:e_guibuttons:) to
# simply be empty.
Markup('e_guibuttons', 'directives', '/\\(:e_guibuttons:\\)/', '');

# Prevent (:e_preview:) and (:e_guibuttons:) from 
# participating in text rendering step.
SDV($SaveAttrPatterns['/\\(:e_(preview|guibuttons):\\)/'], ' ');

$TextScrollTop = intval(@$_REQUEST['textScrollTop']);
SDVA($InputTags['e_form'], array(
  ':html' => "<form action='{\$PageUrl}?action=edit' method='post'
    \$InputFormArgs><input type='hidden' name='action' value='edit' 
    /><input type='hidden' name='n' value='{\$FullName}' 
    /><input type='hidden' name='basetime' value='\$EditBaseTime' 
    /><input type='hidden' name='\$TokenName' value='\$TokenValue' 
    /><input type='hidden' name='textScrollTop' id='textScrollTop' value='$TextScrollTop'
    />"));
SDVA($InputTags['e_textarea'], array(
  ':html' => "<textarea \$InputFormArgs>\$EditText</textarea>\$IncludedPages",
  'name' => 'text', 'id' => 'text', 'accesskey' => XL('ak_textedit'),
  'rows' => XL('e_rows'), 'cols' => XL('e_cols')));
SDVA($InputTags['e_author'], array(
  ':html' => "<input type='text' \$InputFormArgs />",
  'placeholder' => PHSC(XL('Author'), ENT_QUOTES),
  'name' => 'author', 'value' => $Author));
SDVA($InputTags['e_changesummary'], array(
  ':html' => "<input type='text' \$InputFormArgs />",
  'name' => 'csum', 'size' => '60', 'maxlength' => '100',
  'placeholder' => PHSC(XL('Summary'), ENT_QUOTES),
  'value' => PHSC(stripmagic(@$_POST['csum']), ENT_QUOTES)));
SDVA($InputTags['e_minorcheckbox'], array(
  ':html' => "<input type='checkbox' \$InputFormArgs />\$InputFormLabel",
  'name' => 'diffclass', 'value' => 'minor'));
if (@$_POST['diffclass']=='minor') 
  SDV($InputTags['e_minorcheckbox']['checked'], 'checked');
SDVA($InputTags['e_savebutton'], array(
  ':html' => "<input type='submit' \$InputFormArgs />",
  'name' => 'post', 'value' => ' '.XL('Save').' ', 
  'accesskey' => XL('ak_save')));
SDVA($InputTags['e_saveeditbutton'], array(
  ':html' => "<input type='submit' \$InputFormArgs />",
  'name' => 'postedit', 'value' => ' '.XL('Save and edit').' ',
  'accesskey' => XL('ak_saveedit')));
SDVA($InputTags['e_savedraftbutton'], array(':html' => ''));
SDVA($InputTags['e_previewbutton'], array(
  ':html' => "<input type='submit' \$InputFormArgs />",
  'name' => 'preview', 'value' => ' '.XL('Preview').' ', 
  'accesskey' => XL('ak_preview')));
SDVA($InputTags['e_cancelbutton'], array(
  ':html' => "<input type='submit' \$InputFormArgs />",
  'name' => 'cancel', 'value' => ' '.XL('Cancel').' ',
  'formnovalidate' => 'formnovalidate'));
SDVA($InputTags['e_resetbutton'], array(
  ':html' => "<input type='reset' \$InputFormArgs />",
  'value' => ' '.XL('Reset').' '));

if(IsEnabled($EnablePostAuthorRequired))
  $InputTags['e_author']['required'] = 'required';

if(IsEnabled($EnableNotSavedWarning, 1)) {
  $is_preview = @$_REQUEST['preview'] ? 'class="preview"' : '';
  $InputTags['e_form'][':html'] .=
    "<input type='hidden' id='EnableNotSavedWarning'
      value=\"$[Content was modified, but not saved!]\" $is_preview />";
}

if(IsEnabled($EnableEditAutoText)) {
  $InputTags['e_form'][':html'] .=
    "<input type='hidden' id='EnableEditAutoText' />";
}
