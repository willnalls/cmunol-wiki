<?php if(!defined('PmWiki')) exit();

/*  Copyright 2006 Mateusz Czaplinski (mateusz@czaplinski.pl)
    This file is templates.php; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.  


    This script implements parametrised templates in PmWiki. It
    introduces new (:template:) markup for including pages and
    providing parameters. Parameters then can be used with the
    {{{parameter-name|default value}}} markup.

    To use this script, simply copy it into the cookbook/ directory
    and add the following line to config.php (or a per-page/per-group
    customization file).

        include_once('cookbook/templates.php');

    From then on, the markup described will be available in your pages.
    Additionally, there will be some other markup available -- namely
    (:set-parameters:) and (:unset-parameters:) -- but that should not
    be used directly.

    For more details, visit http://pmwiki.org/wiki/Cookbook/Templates
    
    Updated for PHP 5.5-7.2 by Petko Yotov pmwiki.org/petko

*/
$RecipeInfo['Templates']['Version'] = '20191107';

## Markups used internally.
$Parameters_ms = '\(:set-parameters(.*?):\)';
$Parameters_mg = '{{{([^|]*?)(\|(.*?))?}}}';
$Parameters_mu = '\(:unset-parameters:\)';

## Arguments to be passed to the (:include:) directive.
$Parameters_skip = array('line','lines','para','paras','site');

## Used internally. Template parameters stack.
$Parameters_stack = array();
#$Parameters_stack = array( array( 
#	'foo' => 'foo-hardcoded', 
#	'nodef' => 'nodef-hardcoded'
#) );

Markup(
	'parameters',
	'>include',	## or where?
	"/($Parameters_mg|$Parameters_ms|$Parameters_mu)/",
	"ParametersParse"
);

Markup(
	'template',
	'<include',
	"/\(:template(.*?):\)/",
	"Template"
);

## This function is required in place of 3 separate markups,
## as it allows for passing information between the
## parameter-setting markup and the parameter-retrieving markup.
function ParametersParse( $m ) {
	global $Parameters_ms, $Parameters_mg, $Parameters_mu;
	$text = $m[1];

	if( preg_match( "/$Parameters_ms/", $text, $match ) )
		return ParametersSet( $match[1] );
	else
	if( preg_match( "/$Parameters_mg/", $text, $match ) )
		return ParametersGet( $match[1], $match[3] );
	else
	if( preg_match( "/$Parameters_mu/", $text ) )
		return ParametersUnset();
}

#$foo=0;

function ParametersGet( $param, $default ) {
	global $Parameters_stack;

	## simulating 'array_top()'
	$Parameters = array_slice( $Parameters_stack, -1 );
	$Parameters = $Parameters[0];

#	global $foo;
#	return "foo=($foo)";

	$param = $Parameters[$param];
	if( $param )
		return $param;
	else
		return $default;


#	return "p=($param) d=($default)";
}

function ParametersSet( $params ) {
	global $Parameters_stack;

	$params = ParseArgs($params);
	unset($params['']);
	unset($params['#']);
	unset($params['-']);
	unset($params['+']);
	array_push( $Parameters_stack, $params );

#	global $foo;
#	$foo++;
#	return '(set)';
}

function ParametersUnset() {
	global $Parameters_stack;
	array_pop( $Parameters_stack );

#	return '(unset)';
}


## Warning: may wrongly parse "" in templates!!!
function Template( $m ) {
	global $Parameters_skip;

	$set = ''; $pass = '';

	$args = ParseArgs($m[1]);
	while( count($args['#'])>0 ) {
		$k = array_shift($args['#']); $v = array_shift($args['#']);
		if( $k=='' )
			$pass .= "$v ";
		else if( in_array( $k, $Parameters_skip ) )
			$pass .= "$k='$v' ";
		else
			$set .= "$k='$v' ";
	}

#	return "(template pass[$pass] set[$set])";
	return "(:set-parameters $set:)(:include $pass:)(:unset-parameters:)";
}



