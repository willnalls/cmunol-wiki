<?php /*>*/ if (!defined('PmWiki')) exit();
/*  Copyright 2007-2016 by D.Faure (dominique.faure@gmail.com) and
    Patrick R. Michaud (pmichaud@pobox.com)
    Thanks to Steve Levithan for the unvaluable regex trick
    (http://blog.stevenlevithan.com/archives/mimic-atomic-groups/).
    This file would surely made be part of PmWiki; you can redistribute
    it and/or modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2 of the
    License, or (at your option) any later version.

    This script extends the original MarkupExpressions recipe (core built-
    in since version 2.2.0-beta43) essentially by enabling *real*
    expression nesting (ie. able to handle arithmetic operations). It also
    defines various extra functions ranging from basic math to advanced
    string manipulation.

    The operations defined by this recipe include:
      add, sub, mul, div, mod - arithmetic operators
      rev                     - reverse string
      rot13                   - rotate characters
      urlencode, urldecode    - url formatting
      reg_replace             - regexp find/replace
      wikiword                - build a wikiword from a string
      test                    - evaluate a wiki condition
      if                      - conditionally returns its arguments
      sprintf                 - string formatting
      nomarkup                - keep text from a markup string
      unaccent                - accents removal (utf-8 compliant)

    The original "ftime" expression is also patched to enable some of the
    format specifier missing on Win32 platforms.

    See http://www.pmwiki.org/wiki/Cookbook/MarkupExprPlus for more info.
*/
$RecipeInfo['MarkupExprPlus']['Version'] = '2019-11-12';
SDV($MEP_Cfg['mepfile'], basename(__FILE__));

if (! IsEnabled($EnableMarkupExpressions, 1)) return;

SDVA($MarkupExpr, array(
  'add' => 'MEP_arith("+", $params)',
  'sub' => 'MEP_arith("-", $params)',
  'mul' => 'MEP_arith("*", $params)',
  'div' => 'MEP_arith("/", $params)',
  'mod' => '0 + ($args[0] % $args[1])',
  'rev' => 'strrev($args[0])',
  'urlencode' => 'rawurlencode($args[0])',
  'urldecode' => 'rawurldecode($args[0])',
  'rot13' => 'str_rot13($args[0])',
  'wikiword' => 'PPRA($GLOBALS["MakePageNamePatterns"], preg_replace_callback($rpat, "cb_expandkpv", $params))',
  'reg_replace' => 'MEP_reg_replace($args[0], $args[1], $args[2])',
  'test' => 'CondText($pagename, "if " . $params, "TRUE") ? "1" : "0"',
  'if' => '($args[0]) ? $args[1] : (($args[2]) ? $args[2] : "")',
  'sprintf' => 'call_user_func_array("sprintf", $args)',
  'nomarkup' => 'preg_replace("/(<[^>]+>|\r\n?|\n\r?)/", "",'
                           . ' MarkupToHTML($pagename, $args[0], array("escape" => 0)))',
  'unaccent' => 'MEP_unaccent($args[0])',
));

function MEP_nums($params) {
  $params = preg_split('/\\s+/', $params);
  $args = array();
  foreach ($params as $p) {
    list($d) = sscanf($p, "%g");
    if (is_numeric($d)) $args[] = $d;
  }
  return $args;
}

##  MEP_arith handles {(add ...)}, {(sub ...)}, {(mul ...)} and {(div ...)}
##  expressions.
##
function MEP_arith($op, $params) {
  $expr = implode(")$op(", MEP_nums($params));
  return $expr ? eval("return 0 + ($expr);") : '';
}

if (!IsEnabled($EnableExprOriginalFtime, 0)) {
  $MarkupExpr['ftime'] = 'MEP_ftime($args, $argp)';

  function MEP_strftime($fmt, $ts = null, $lc = null, $gm = false) {
    if (!$ts) $ts = time();
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      $date = $gm ? 'gmdate' : 'date';
      $map = array(
      '%C' => sprintf("%02d", $date("Y", $ts) / 100),
      '%D' => '%m/%d/%y',
      '%e' => sprintf("%' 2d", $date("j", $ts)),
      '%g' => sprintf("%02d", $date("o", $ts) % 100),
      '%G' => $date("o", $ts),
      '%h' => '%b',
      '%n' => "\n",
      '%r' => $date("h:i:s", $ts) . " %p",
      '%R' => $date("H:i", $ts),
      '%t' => "\t",
      '%T' => '%H:%M:%S',
      '%u' => ($w = $date("w", $ts)) ? $w : 7,
      '%V' => $date("W", $ts),
      '%z' => substr($date("O", $ts), 0, 3) . ":" . substr($date("O", $ts), 3),
      );
      $fmt = str_replace(array_keys($map), array_values($map), $fmt);
    }
    ##  make sure we have %F available for ISO dates
    $fmt = str_replace(array('%F', '%s'), array('%Y-%m-%d', $ts), $fmt);
    $strftime = $gm ? 'gmstrftime' : 'strftime';
    if (isset($lc)) {
       $oldlc = setlocale(LC_ALL, '0');
       setlocale(LC_ALL, $lc);
    }
    $ret = $strftime($fmt, $ts);
    if (isset($lc)) setlocale(LC_ALL, $oldlc);
    return $ret;
  }

  ##  MEP_ftime handles {(ftime ...)} expressions.
  ##
  function MEP_ftime($args, $argp = NULL) {
    global $TimeFmt, $Now, $FTimeFmt;
    ## get the format string
    if (@$argp['fmt']) $fmt = $argp['fmt'];
    elseif (strpos(@$args[0], '%') !== false) $fmt = array_shift($args);
    elseif (strpos(@$args[1], '%') !== false) list($fmt) = array_splice($args, 1, 1);
    else { SDV($FTimeFmt, $TimeFmt); $fmt = $FTimeFmt; }
    ## determine the timestamp
    if (isset($argp['when'])) list($time, $x) = DRange($argp['when']);
    elseif (@$args[0] > '') list($time, $x) = DRange(array_shift($args));
    else $time = $Now;
    ## get the locale
    $locale = isset($argp['lc']) ? $argp['lc'] : array_shift($args);
    return MEP_strftime($fmt, $time, $locale);
  }
}

##  MEP_reg_replace handles {(reg_replace /regexp/opt replacement string)}
##
function MEP_reg_replace($pat, $repl, $str) {
  if (!preg_match('/^(.)([^\\1]*)(\\1)(\\w*)$/', $pat, $m)) return $str;
  $m[4] = str_replace('e', '', $m[4]);
  return preg_replace(implode('', array_slice($m, 1)), $repl, $str);
}

##  MEP_unaccent_utf8 handles {(unaccent string)}
##
function MEP_unaccent($s) {
  global $MEP_Encoding, $Charset;
  SDV($MEP_Encoding, $Charset);
  if (strpos($s = htmlentities($s, ENT_QUOTES, $MEP_Encoding), '&') !== false)
    $s = html_entity_decode(preg_replace('/&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);/i', '$1', $s), ENT_QUOTES, $MEP_Encoding);
  return $s;
}

## The esotheric stuff starts here...

if (IsEnabled($EnableExprMultiline, 0)) {
  SDVA($MEP_Cfg, array(
    'MarkupRe' => '/\\{(\\(\\w+\\b.*?\\))\\}/s',
    'KeepRe' => '/([\'"])(.*?)\\1/s',
    'FuncRe' => '/\((?=((\\w+)(\\s[^()]*)?))\1\)/s',
    'ParseArgs' => 'MEP_ParseArgs',
  ));

  ## Multiline variant
  function MEP_ParseArgs($x, $optpat = '(?>(\\w+)[:=])') {
    $z = array();
    preg_match_all("/($optpat|[-+])?(\"[^\"]*\"|'[^']*'|\\S+)/s",
      $x, $terms, PREG_SET_ORDER);
    foreach ($terms as $t) {
      $v = preg_replace('/^([\'"])?(.*)\\1$/', '$2', $t[3]);
      if ($t[2]) { $z['#'][] = $t[2]; $z[$t[2]] = $v; }
      else { $z['#'][] = $t[1]; $z[$t[1]][] = $v; }
      $z['#'][] = $v;
    }
    return $z;
  }

  $MarkupTable['{(']['pat'] = $MEP_Cfg['MarkupRe'];
  $MarkupTable['{(']['rep'] = "MarkupMarkupExpressionPlus";
}

if (IsEnabled($EnableExprVarManip, 0)) {
  SDVA($MEP_Cfg, array(
    'MarkupRe' => '/\\{(\\(\\w+\\b.*?\\))\\}/',
    'KeepRe' => '/([\'"])(.*?)\\1/',
    'FuncRe' => '/\((?=((\\w+)(\\s[^()]*)?))\1\)/',
    'ParseArgs' => 'ParseArgs',
    'httpvars' => '{$?|!@^~var}1',
  ));

  DisableMarkup('{(');
  Markup('{(+', isset($MarkupTable[$MEP_Cfg['httpvars']]) ? '<'.$MEP_Cfg['httpvars'] : '<{$var}',
    $MEP_Cfg['MarkupRe'], "MarkupMarkupExpressionPlus");
  SDVA($MarkupExpr, array(
    'set' =>  'MEP_setvar($pagename, true,  @$args[0], @$args[1], $argp)',
    'setq' => 'MEP_setvar($pagename, false, @$args[0], @$args[1], $argp)',
  ));

  ##  MEP_setvar handles {(set ...)}/{(setq ...)} expressions.
  ##
  function MEP_setvar($pagename, $parm, $arg0, $arg1, $argp = NULL) {
    global $FmtPV;
    $n = (@$argp['var']) ? $argp['var'] : $arg0;
    $v = (@$argp['value']) ? $argp['value'] : $arg1;
    if ($n) $FmtPV["\${$n}"] = "'" . str_replace("'", "\\'", $v) . "'";
    return $parm ? $v : '';
  }
}

function MarkupMarkupExpressionPlus($m) {
    extract($GLOBALS['MarkupToHTML']);
    return MarkupExpressionPlus($pagename, $m[1]);
}

if(IsEnabled($EnableExprMultiline, 0) || IsEnabled($EnableExprVarManip, 0)) {
  ##  Replace PV's, PTV's with their values.
  ##
  function MEP_parse_vars($pagename, $markupName, $expr) {
    global $MarkupTable;
    $pat = $MarkupTable[$markupName]['pat'];
    $rep = $MarkupTable[$markupName]['rep'];
    while (preg_match($pat, $expr))
      $expr = is_callable($rep)
        ? preg_replace_callback($pat, $rep, $expr)
        : preg_replace($pat, $rep, $expr);
    return $expr;
  }

  function MEP_cb_keep_m2_p($m) { return Keep($m[2],'P'); }

  function MarkupExpressionPlus($pagename, $expr) {
    global $EnableExprVarManip, $MarkupTable,
           $KeepToken, $KPV, $MarkupExpr, $MEP_Cfg;
    $rpat = "/$KeepToken(\\d+P)$KeepToken/";

    if (IsEnabled($EnableExprVarManip, 0)) {
      if (isset($MarkupTable[$MEP_Cfg['httpvars']]))
        $expr = MEP_parse_vars($pagename, $MEP_Cfg['httpvars'], $expr);
      $expr = MEP_parse_vars($pagename, '{$var}', $expr);
    }
    $expr = preg_replace_callback($MEP_Cfg['KeepRe'], "MEP_cb_keep_m2_p", $expr);
    #$expr = PPRE('/\\(\\W/', "Keep(\$m[0],'P')", $expr);

    while (preg_match($MEP_Cfg['FuncRe'], $expr, $match)) {
      list($repl, $dummy, $func, $params) = $match;
      $code = @$MarkupExpr[$func];
      ##  if not a valid function, save this string as-is and exit
      if (!$code) break;
      ##  if the code uses '$params', we just evaluate directly
      if (strpos($code, '$params') !== false) {
        $out = eval("return ({$code});");
        if ($expr == $repl) { $expr = $out; break; }
        $expr = str_replace($repl, $out, $expr);
        continue;
      }
      ##  otherwise, we parse arguments into $args before evaluating
      $argp = $MEP_Cfg['ParseArgs']($params);
      $x = $argp['#']; $args = array();
      while ($x) {
        list($k, $v) = array_splice($x, 0, 2);
        if ($k == '' || $k == '+' || $k == '-')
          $args[] = $k.preg_replace_callback($rpat, 'cb_expandkpv', $v);

      }
      ##  fix any quoted arguments
      foreach ($argp as $k => $v)
        if (!is_array($v)) $argp[$k] = preg_replace_callback($rpat, 'cb_expandkpv', $v);
      $out = eval("return ({$code});");
      if ($expr == $repl) { $expr = $out; break; }
      $expr = str_replace($repl, Keep($out, 'P'), $expr);
    }
    return preg_replace_callback($rpat, 'cb_expandkpv', $expr);
  }

}

# restores kept/protected strings
if (! function_exists('cb_expandkpv')) {
  function cb_expandkpv($m) { return $GLOBALS['KPV'][$m[1]]; }
}

# cache $rrep
$CallbackFunctions['return $GLOBALS["KPV"][$m[1]];'] = 'cb_expandkpv';