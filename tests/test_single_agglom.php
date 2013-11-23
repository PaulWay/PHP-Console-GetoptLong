<?php

require_once "../Console/GetoptLong.php";

$ack = 0;
$bee = 0;
$cog = 0;
$args = Console_GetoptLong::getOptions(array(
	'ack|a' => &$ack,
	'bee|b' => &$bee,
	'cog|c' => &$cog,
));

echo "$ack,$bee,$cog,(", implode(',',$args), ")\n";

