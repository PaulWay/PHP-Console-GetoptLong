<?php

require_once "../Console/GetoptLong.php";

$flag = 0;
$mandatory = '';
$optional = '';
$increment = 0;
$negatable = 0;
$args = Console_GetoptLong::getOptions(array(
	'flag'		=> array(
	    'var'   => &$flag,
	    'help'  => "Flag option",
	),
	'mandatory|m=i' => array(
	    'var'   => &$mandatory,
	    'help'  => "Mandatory option",
	),
	'optional|o:i' => array(
	    'var'   => &$optional,
	    'help'  => "Optional option",
	),
	'increment|i+' => array(
	    'var'   => &$increment,
	    'help'  => "Incrementing option",
	),
	'negatable|n!' => array(
	    'var'   => &$negatable,
	    'help'  => "Negatable option",
	),
));

echo "$flag,$mandatory,$optional,$increment,$negatable,(", implode(',',$args), ")\n";

