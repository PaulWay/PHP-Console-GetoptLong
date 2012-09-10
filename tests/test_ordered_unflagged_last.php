<?php

require_once "../Console/GetoptLong.php";

$last = '';
$args = Console_GetoptLong::getOptions(array(
	'last|l|_-1=s'  => &$last,
));

echo "$last,(", implode(',',$args), ")\n";

