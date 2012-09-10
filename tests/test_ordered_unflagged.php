<?php

require_once "../Console/GetoptLong.php";

$first = '';
$second = '';
$args = Console_GetoptLong::getOptions(array(
	'first|f|_1=s'  => &$first,
	'second|s|_2:s' => &$second,
));

echo "$first,$second,(", implode(',',$args), ")\n";

