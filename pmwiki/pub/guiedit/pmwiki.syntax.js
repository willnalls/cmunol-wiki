/*  PmSyntax: Syntax highlighting for PmWiki markup
    Copyright 2021-2022 Petko Yotov https://www.pmwiki.org/support
    This file is part of PmWiki; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.  See pmwiki.php for full details.

    This file provides Javascript functions for syntax highlighting of
    PmWiki markup, to be used in the PmWiki documentation, and optionally
    in the edit form.
*/

(function(){
  var KeepToken = "\034\034";
  var restoreRX = new RegExp(KeepToken+'(\\d+)'+KeepToken, 'g');
  var special = /[#!*?&+|,()[\]{}\/\^<>=]+|\.\.+|--+|\s-+/g;
  var Kept = new RegExp('^' + KeepToken+'(\\d+)'+KeepToken + '$', '');
  try  {
    var storage = window.localStorage;
  }
  catch(e) { // localStorage may be disabled if cookies are disabled
    var storage = {
      setItem: function(){},
      getItem: function(){ return null; },
      removeItem: function(){}
    }
  }

  var log = console.log;
  function aE(el, ev, fn) {
    if(typeof el == 'string') el = dqsa(el);
    var ell = el.length;
    for(var i=0; i<ell; i++) el[i].addEventListener(ev, fn);
  }
  function dqs(str)  { return document.querySelector(str); }
  function dqsa(str) { return document.querySelectorAll(str); }
  function tap(q, fn) { aE(q, 'click', fn); };
  function PHSC(x) { return x.replace(/[&]/g, '&amp;').replace(/[<]/g, '&lt;').replace(/[>]/g, '&gt;'); }
  function span(cname, text, escaped) {
    if(text==='') return '';
    if(!escaped) text = PHSC(text);
    return "<span class='pm"+cname.replace(/^\*/, 'tag ')
     .split(/[ _]+/g).join(' pm')+"'>" + text + "</span>";
  }
  var hrx = [ // rule_name, [*=!]classname|function, [container_rx], rx
    ['_begin'],
    ['external', 'external', /%hlt +([-\w+]+) *% *\[@([\s\S]*?)@\]/g],
    ['external2', 'external', /%hlt +([-\w+]+) *% *@@ *\[=([\s\S]*?)=\] *@@/g],
    ['pmhlt', 'external', /%(pm)hlt *% *\[@([\s\S]*?)@\]/g],
    ['pmhlt2', 'external', /%(pm)hlt *% *@@ *\[=([\s\S]*?)=\] *@@/g],
    ['preserve', '=escaped', /\[([@=])[\s\S]*?\1\]/g, /^(\[[@=])([\s\S]*)([@=]\])$/],
    ['joinline', '*bullet', /\\+(\n|$)/g],

    // variables
    ['pagevar', 'var', /\{([-\w\/.]+|[*=<>])?\$[$:]?\w+\}/g],
    ['nestvar', 'string', /\{(\034\034\d+\034\034)\$[$:]?\w+\}/g],
    ['phpvar',  'var', /\$((Enable|Fmt|Upload)\w+|\w+(Fmt|Functions?|Patterns?|Dirs?|Url)|FarmD|pagename)\b/g],
    ['i18n', 'string', /\$\[.*?\]/g],

    // markup expressions
    ['mx', '!mx', /(\{\([-\w]+)(.*?)(\)\})/g],

    // page text vars, can be empty or multiline
    ['ptv0', '=meta>punct', /(\(:\w[-\w]*)(:)( *:\))/g, /:/],
    ['ptv1', '=meta>punct', /(\(:\w[\w-]*)(:[^\)][\s\S]*?)(:\))/g, /:/],

    // core meta directives
    ['comment', '=comment', /(\(:comment)(.*?)(:\))/gi],
    ['skin',  '*meta', /\(:no(left|right|title|action|(group)?(header|footer)) *:\)/gi ],
    ['meta0', '*meta', /\(:(no)?((link|space)wikiwords|linebreaks|toc) *:\)/gi],
    ['meta1', '*meta', /\(:(else\d*|if\d*|if\d*end|nl) *:\)/gi],
    ['meta2', '=meta', /(\(:(?:title|description|keywords))(.*?)(:\))/gi],
    ['meta3', '=meta>keyword>*attr>*keyword',
      /(\(:(?:(?:else\d*)?if\d*))(.*?)(:\))/ig,
      /\b(expr|e_preview|enabled|auth(id)?|name|group|true|false|attachments|date|equal|match|exists|ontrail)\b/g,
      special, /[[\]()]+/g ],
    ['tmpl', '!meta>=keyword', /(\(:template[^\S\r\n]+)(\S.*?)(:\))/g,
      /^([ !]*)(each|first|last|defaults?|requires?|none)/],
    ['rdir', '!meta', /(\(:redirect)(.*?)(:\))/g],

    // urls can have percents so before wikistyle (populated by InterMap)
    ['ttip', '=escaped', /(\[\[)(.*?\S)(?= *(?:\||\]\]))/g,  /(")(.*)(")$/ ], // tooltop
    ['link0', '=escaped', /\[\[.*?\S(?= *(?:\||\]\]))/g, /(\()(.*?)(\))/g],// hidden
    ['_url'],

    // wikistyles
    ['ws0', '*meta', /%%|^>><</gm],
    ['ws1', '!meta', /(^>>\w[-\w]*)(.*?)(<<)/gm],
    ['ws2', '!meta', /(%(?:define|apply)=\w+)(.*?)(%)/gi],
    ['ws3', '!meta', /(%\w[-\w]*)(.*?)(%)/g],

    // directives, forms
    ['form', '!directive>keyword', /(\(:input[^\S\r\n]+)(\S.*?)(:\))/g,
      /^((pm)?form|text(area)?|radio|checkbox|select|email|tel|number|default|submit|reset|hidden|password|search|url|date|month|color|datalist|file|image|reset|button|e_\w+|captcha|pmtoken|end)/],
    ['dir0', '*directive', /\(:\w[-\w]* *:\)/g], // simple
    ['dir1', '!directive', /(\(:\w[-\w]*)(.*?)(:\))/g], // with attributes

    // inline
    ['link', 'punct', /(\[\[[\#!~]?|([#+][^\S\r\n]*)?\]\])/g], // link

    // list item, initial space, indent, linebreak
    ['bullet', '*bullet', /^([^\S\r\n]*([*#]+)[^\S\r\n]*|-+[<>][^\S\r\n]*|[^\S\r\n]+)/mg],

    ['QA', '*heading', /^([QA]:|-{4,})/mg], //Q:/A:, horizontal rule
    ['prop', 'meta',   /^[A-Z][-_a-zA-Z0-9]*:/mgi], // property, or start of line PTV

    // inline punctuation; entity
    ['time',  '=mx>string>var',   /(@)([\d-]{10}T\d\d:\d\d(?::\d\d)?)(Z)/g, /^[\d-]+/, /[\d:]+$/],
    ['punct',  'punct',   /('[\^_+-]|[\^_+-]'|\{[+-]+|[+-]+\}|\[[+-]+|[+-]+\]|@@|'''''|'''|''|->|~~~~?)/g],
    ['entity', 'string',  /[&]\#?\w+;/g],

    // simple tables
    ['tablecapt', '=table', /^(\|\|!)(.+)(!\|\|)$/mg],
    ['tablerow',  '!table', /^\|\|.*\|\|.*$/mg, /((?:\|\|)+)(!?)/g],
    ['tableattr', '!table', /^(\|\|)(.*)($)/mg],

    // wikitrails
    ['trail1', '=url', /(<<?\|)(.*?)(\|>>?)/g],
    ['trail2', '=url', /(\^\|)(.*?)(\|\^)/g],

    ['pipe', 'punct', /\|/g], // inline, after trails

    // may contain inline markup
    ['deflist', '=bullet', /^([:]+)(.*?)([:])/mg],
    ['heading', '=heading', /^(!{1,6})(.*)($)/mg],

    ['cleanup', PHSC, /[<>&]+/g],// raw HTML/XSS
    ['restore', '.restore', restoreRX],
    ['_end']
  ];
  var custom_hrx = {}, sorted_hrx = [];
  var hl = hrx.length;
  for(var i=0; i<hl; i++) {
    custom_hrx[ hrx[i][0] ] = [];
    custom_hrx[ '>'+hrx[i][0] ] = [];
  }

  function PmHi(text){
    var KPV = [];
    function Restore(all, n) { return KPV[parseInt(n)]; }
    function keep0(text) {
      if(text === '') return '';
      KPV.push(text.replace(restoreRX, Restore));
      return KeepToken+(KPV.length-1)+KeepToken;
    }
    function Keep(text, cname) {
      if(!text) return '';
      text = span(cname, text);
      return keep0(text);
    }
    function Keep5(parts, cname) {
      var mode = cname.charAt(0);
      var attr = parts[4] || mode == '!' ? true:false;
      var out = '';
      if(parts[0]) out += span('tag', parts[0]);
      if(parts[1]) out += attr ? hattr(parts[1]) : PHSC(parts[1]);
      if(parts[2]) out += span('tag', parts[2]);
      if(parts[3]) out += mode == '!' ? hattr(parts[3]) : PHSC(parts[3]);
      if(parts[4]) out += span('tag', parts[4]);
      if(!out) return '';
      else out = span(cname.slice(1), out, true);
      return keep0(out);
    }
    function hattr(attr) {
      if(! attr) return '';
      attr = attr.toString()
      .replace(/(['"])(.*?)\1/g, function(a){ return Keep(a, 'value'); })
      .replace(/((?:\$:?)?[-\w]+|^)([:=])(\S+)/g, function(a, attr, op, val){
        if(! val.match(Kept)) val = span('value', val);
        if(attr) attr = span('attr', attr);
        return keep0(attr + op + val);
      })
      .replace(/(\()(\w+)/g, function(a, attr, expr){
        return Keep(attr, '*attr')+Keep(expr, 'tag');
      })
      .replace(special, function(a){ return Keep(a, '*attr'); });
      return PHSC(attr);
    }
    function external(lang, code) {
      if(lang.match(/^pm(wiki)?$/i))
        return keep0(PmHi(code));
      if (! externalLangs
        || lang == 'plaintext'
        || ! lang.match(externalLangs)
      ) return keep0(PHSC(code));
      try {
        var x = hljs.highlight(code, {language:lang, ignoreIllegals:true});
        return keep0('<code class="hljs language-'+lang+'">'+x.value+'</code>');
      }
      catch(e) {
        return keep0(PHSC(code));
      }
    }

    function PmHi1(text, rule){
      var r = rule[0], s = rule[1];
      if(r == '.restore') return text.replace(s, Restore);
      if(typeof r == 'string' && r.indexOf('external')===0) {
        var b = r.match(/[>]([-\w+]+)/);
        return text.replace(s, function(a, a1, a2){
          var lang = b? b[1] : a1;
          var code = b? a1 : a2;
          if(!code.match(/\S/)) return a;
          return a.replace(code, external(lang.toLowerCase(), code));
        });
      }
      if(!!rule[2]) {
        var m = (typeof r == 'function') ? false : r.split(/[>]/g);
        if(m && m.length>1) { // parent>nested
          r = m[0];
          return text.replace(s, function(a){
            var b = Array.from(arguments).slice(1, -2);
            var j = b[4]? 3:1;
            var ml = m.length;
            for(var i=1; i<ml; i++) {
              if(rule[i+1]) b[j] = PmHi1(b[j], [m[i], rule[i+1]]);
            }
            return Keep5(b, r);
          });
        }
        else { // one classname, return match only_in_container
          return text.replace(s, function(a){
            return PmHi1(a, [r, rule[2]]);
          });
        }
      }
      if(typeof r == 'function') text = text.replace(s, r);
      else text = text.replace(s, function(a){
        var b = Array.from(arguments).slice(1, -2);
        if(r.match(/^[=!]/)) return Keep5(b, r);
        else return Keep(a, r);
      });
      return text;
    }
    var sl = sorted_hrx.length;
    for(var i=0; i<sl; i++) {
      var rule = sorted_hrx[i];
      if(rule.length<2)  continue; // _begin, _end
      text = PmHi1(text, rule);
    }
    return text;
  }

  function PmHiEl(el){
    el.innerHTML = PmHi(el.textContent);
    el.classList.add('pmhlt');
  }

  function PmHiAll(){
    var hlt = dqsa('.hlt.pm, .hlt.pmwiki, .highlight.pm, .highlight.pmwiki');
    var hl = hlt.length;
    for(var i=0; i< hl; i++) hlt[i].className = 'pmhlt';
    var pm = dqsa('table.markup td.markup1 > pre, '
      + '.pmhlt pre, .pmhlt + pre, .pmhlt code, pre.pmhlt, code.pmhlt');
    if(! pm.length) return;
    pm.forEach(PmHiEl);
    tap('.toggle-pmhlt', toggleStyles);
  }

  function toggleStyles(e) {
    e.preventDefault();
    var c1 = 'pmhlt', c2 = 'pmhlt-disabled';
    var x = dqsa('.'+c1+',.'+c2);
    var xl = x.length;
    for(var i=0; i<xl; i++) {
      x[i].classList.toggle(c1);
      x[i].classList.toggle(c2);
    }
  }

  function str2rx(str) {
    if(typeof str.flags == 'string') return str; // regexp
    if(typeof str != 'string') {
      log("Not a string", str);
      return false;
    }
    var a = str.match(/^\/(.*)\/([gimsyu]*)$/);
    var rx = false
    try {
      if(a) {
        rx = new RegExp(a[1], a[2]);
      }
      else rx = new RegExp(str, 'g');
    }
    catch(e) {
      log('Could not create RegExp.', str);
    }
    if(rx) {
      if(rx.source.match(/\[(\\s\\S)|(\\S\\s)\]/)) {
        PreserveMultilineArray.push(rx.source);
      }
      return rx;
    }
  }

  var _script, PreserveMultilineArray = [], PreserveMultiline = false;
  function sortRX(){
    _script = dqs('script[src*="pmwiki.syntax.js"]');
    var cm = (window.PmSyntaxCustomMarkup)? window.PmSyntaxCustomMarkup : [];
    var imaps =  [_script.dataset.imap];
    var custom = _script.dataset.custom;
    if(custom) {
      try {
        var list = JSON.parse(_script.dataset.custom);
      }
      catch(e) {
        log("Parsing custom rules failed.", _script.dataset.custom);
        var list = [];
      }
      var ll = list.length;
      for(var i=0; i<ll; i++) {
        var rule = list[i];
        if(typeof rule == 'string') rule = rule.trim().split(/\s{2,}/g);
        if(rule[0]=='InterMap') {
          imaps.push(rule[1]);
          continue;
        }
        var rl = rule.length;
        for(var j=2; j<rl; j++) {
          var r = str2rx(rule[j]);
          if(r) rule[j] = r;
        }
        cm.push(rule);
      }
    }
    var uec = '<>"{}|\\\\^`()[\\]\'';
    cm.push(['>_url', 'url', new RegExp(
      '\\b(' +imaps.join('|')+ ')[^\\s'+uec+']*[^\\s.,?!'+uec+']', 'g'
    )]);
    var cml = cm.length;
    for(var i=0; i<cml; i++) {
      var key = cm[i][0].replace(/^</, '');
      if(custom_hrx.hasOwnProperty(key)) custom_hrx[key].push(cm[i].slice(1));
      else log('No rule name to attach to.', cm[i]);
    }
    sorted_hrx = [];
    var hrxl = hrx.length;
    for(var i=0; i<hrxl; i++) {
      var key = hrx[i][0];
      var keys = [key, '>'+key];
      for(var k=0; k<2; k++) {
        if(k) sorted_hrx.push(hrx[i].slice(1));
        var kk = keys[k];
        var chl = custom_hrx[kk].length;
        for(var j=0; j<chl; j++) {
          sorted_hrx.push(custom_hrx[kk][j]);
        }
      }
    }
    if(PreserveMultilineArray.length) {
      PreserveMultiline = new RegExp('('+PreserveMultilineArray.join('|')+')', 'g');
    }
  }

  function initEditForm(){
    if(!_script || !_script.dataset.mode.match(/^[23]$/)) return;
    var text = dqs('#wikiedit textarea#text');
    if(!text) return;
               
    var defaultEnabled = _script.dataset.mode == '3'? 1: 0;

    var lastTextParts = [];
    var lastTextContent = false;
    var hltEnabled = false;
    var EnableStopwatch = storage.getItem('EnablePmSyntaxStopwatch')? true:false;
    var resizeObserver;

    function average(arr) {
      return arr.reduce(function(a, b){ return a+b; }, 0) / arr.length;
    }
    var perf = [];
    var t0 = 0;
    
    function stopwatch(end) {
      if(!EnableStopwatch) return;
      var t1 = performance.now();
      if(!end) {
        t0 = t1;
        return;
      }
      var dt = t1 - t0;
      perf.push(dt);
      var avg = average(perf).toFixed(2);
      if(perf.length%10 == 0) console.log(`PmSyntax ${dt} ms, ${avg} ms average from ${perf.length}.`);
    }
  
    function updatePre() {
      if(!hltEnabled) return;
      var tc = text.value;
      if(tc===lastTextContent) return;

      stopwatch();
      var parts = splitParts(tc);
      if(!parts.length) parts = [''];
      
      if(lastTextParts.length==0) {
        var out = parts.map(mapHi).join('') + '\n';
        htext.insertAdjacentHTML('afterbegin', out);
        var juststarted = 1;
      }
      else {
        var diff = diffParts(parts);
        var spans = htext.children;
        for(var i=0; i<diff.count; i++) {
          htext.removeChild(spans[diff.first]);
        }
        if(diff.add) {
          if(spans[diff.first]) spans[diff.first].insertAdjacentHTML('beforebegin', diff.add);
          else if(spans[diff.first-1]) spans[diff.first-1].insertAdjacentHTML('afterend', diff.add);
          else htext.insertAdjacentHTML('afterbegin', diff.add);
        }
      }
      lastTextContent = tc;
      lastTextParts = parts;
      textScrolled();
      stopwatch(true);
      if(juststarted) perf = [];
    }
    
    function mapHi(x) {
      if(x!=='' && x.charAt(0) !== '\n') x = PmHi(x);
      return '<span>'+x+'</span>';
    }
    
    function splitParts(value) {
      var kept = [];
      function keep(a, a1){
        kept.push(restore(a1));
        return '\034\034' + (kept.length - 1) + '\034\034';
      };
      function restore(str) {
        return str.replace(/\034\034(\d+)\034\034/g, function(a, a1){
          return kept[parseInt(a1)];
        });
      }
      // keep escaped blocks
      value = value
        .replace(/(\[([@=])[\s\S]*?\2\]|\(:[-\w]+ *:(?!\))[\s\S]*?:\)|\\\n)/g, keep);
      if(PreserveMultiline) {
        value = value.replace(PreserveMultiline, keep);
      }
      
      var parts = value.split(/(\n+)/).filter(Boolean);
      for(var i=0; i<parts.length; i++) {
        parts[i] = restore(parts[i]);
      }
      return parts;
    }
    function diffParts(parts) {
      var ll = lastTextParts.slice(0), pp = parts.slice(0), firstchanged = 0;
      while(ll.length && pp.length) {
        if(ll[0] !== pp[0]) break;
        ll.shift(); pp.shift();
        firstchanged++;
      }
      ll.reverse(); pp.reverse();
      while(ll.length && pp.length) {
        if(ll[0] !== pp[0]) break;
        ll.shift(); pp.shift();
      }
      var add = pp.reverse().map(mapHi).join('');
      return {first:firstchanged, count:ll.length, newcount:pp.length, add:add};
    }
    
    function textScrolled() {
      if(!hltEnabled) return;
      if(ignoreTextScrolled) return;

      if(ignorePreScrolled) clearTimeout(ignorePreScrolled-1);
      ignorePreScrolled = 1 + setTimeout(nullIPS, 100);
      htext.scrollTop = text.scrollTop;
      htext.scrollLeft = text.scrollLeft;
    }
    var ignoreTextScrolled = false, ignorePreScrolled = false;
    function preScrolled() { // browser's in-page search
      if(!hltEnabled) return;
      if(ignorePreScrolled) return;
      if(ignoreTextScrolled) clearTimeout(ignoreTextScrolled-1);
      ignoreTextScrolled = 1 + setTimeout(nullITS, 100);
      text.scrollTop = htext.scrollTop;
      text.scrollLeft = htext.scrollLeft;
    }
    function nullITS(){ignoreTextScrolled = false;}
    function nullIPS(){ignorePreScrolled = false;}
    function dragstart(e) { this.classList.add('dragging'); }
    function dragend(e) { this.classList.remove('dragging'); }

    function resizePre() {
      if(!hltEnabled) return;
      var rect = text.getBoundingClientRect();
      var w = rect.width + 'px', h = rect.height + 'px';
      htext.style.width = w;
      htext.style.height = h;
      textScrolled();
    }

    function initPre() {
      text.insertAdjacentHTML('beforebegin', '<div id="hwrap"><div id="htext" class="pmhlt"></div></div>');
      hwrap.appendChild(text);
      updatePre();
      resizePre();

      htext.inert = true;
      htext.style.textAlign =  window.getComputedStyle(text, null).getPropertyValue('text-align');
      htext.addEventListener('scroll', preScrolled);
      
      text.addEventListener('scroll', textScrolled);
      text.addEventListener('dragstart', dragstart);
      text.addEventListener('dragend', dragend);

      resizeObserver = new ResizeObserver(resizePre);
      resizeObserver.observe(text);
    }
    
    function storeEnabled(enabled) {
      if(enabled != defaultEnabled) storage.setItem('EnableHighlight', enabled);
      else storage.removeItem('EnableHighlight');
    }

    function EnableHighlight() {
      if(chk_hlt.classList.contains('pmhlt')) {
        hltEnabled = true;
        storeEnabled(1);
        updatePre();
        resizePre();
        text.addEventListener('input', updatePre);
      }
      else {
        hltEnabled = false;
        lastTextContent = false;
        storeEnabled(0);
        text.removeEventListener('input', updatePre);
      }
    }

    function initToggle(x){
      var form = text.closest('form');
      form.insertAdjacentHTML('afterbegin', '<code id="chk_hlt">'
        + '<span class="pmpunct">[[</span><span class="pmurl">'
        + _script.dataset.label
        + '</span><span class="pmpunct">]]</span>'
        + '</code>');

      initPre();
      var enabled = parseInt(storage.getItem('EnableHighlight') || defaultEnabled);
      if(enabled) {
        chk_hlt.classList.add('pmhlt');
        EnableHighlight();
      }
      tap([chk_hlt], function(e){
        this.classList.toggle('pmhlt');
        EnableHighlight();
      });
    }
    initToggle();
  }

  var externalLangs = false;
  function initExtLangs() {
    if(typeof hljs == 'undefined') return;
    var langs = hljs.listLanguages();
    var aliases = langs.slice(0);
    var ll = langs.length
    for(var i=0; i<ll; i++) {
      var l = hljs.getLanguage(langs[i]);
      if(l.aliases) aliases = aliases.concat(l.aliases);
    }
    externalLangs = new RegExp('^('+aliases.join('|').replace(/[+]/g, '\\+')+')$', 'i');
  }

  document.addEventListener('DOMContentLoaded', function(){
    sortRX();
    initExtLangs();
    PmHiAll();
    initEditForm();
  });
})();

