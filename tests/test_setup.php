<?php

require_once "../Console/GetoptLong.php";

// This rather strange setup tests the error conditions that GetoptLong
// generates on bad option description configuration.

$variable = '';
$args = Console_GetoptLong::getOptions(array(
	$_SERVER['argv'][1]	=> &$variable,
));

print "OK\n";

