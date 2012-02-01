<?php

require_once "../Console/GetoptLong.php";

$help = 0;
$args = Console_GetoptLong::getOptions(array(
	'help'		=> array(
	    'var'   => &$help,
	    'help'  => "Flag option",
	),
));

// We've supplied our own help option - this should not produce any
// pregenerated help if called with the help option.

echo "OK(help=$help)\n";
