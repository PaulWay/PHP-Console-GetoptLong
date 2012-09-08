<?php

require_once "../Console/GetoptLong.php";

$first = '';
$second = '';
$last = '';
$args = Console_GetoptLong::getOptions(array(
	'first|f|_1=s'  => &$first,
	'second|s|_2:s' => &$second,
	'last|l|_-1:s'  => &$last,
));

echo "$first,$second,$last,(", implode(',',$args), ")\n";

