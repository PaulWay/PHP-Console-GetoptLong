<?php

require_once "../Console/GetoptLong.php";

$mandatory = '';
$args = Console_GetoptLong::getOptions(array(
	'mandatory|m=i'		=> &$mandatory,
));

echo $mandatory,',(', implode(',',$args), ")\n";

