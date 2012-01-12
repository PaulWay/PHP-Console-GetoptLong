<?php

require_once "../Console/GetoptLong.php";

$mandatory = '';
$args = Console_GetoptLong::GetOptions(array(
	'mandatory|m=s'		=> &$mandatory,
));

echo $mandatory,',(', implode(',',$args), ")\n";

