<?php

require_once "../Console/GetoptLong.php";

// This rather strange setup tests the error conditions that GetoptLong
// generates on bad option description configuration.

$variable = '';
$othervar = '';
$optDesc = array(
	$_SERVER['argv'][1]	=> &$variable,
);
if (count($_SERVER['argv']) > 2) {
    $optDesc[$_SERVER['argv'][2]] = &$othervar;
}
$args = Console_GetoptLong::getOptions($optDesc);

print "OK\n";

