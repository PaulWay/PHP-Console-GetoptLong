<?php

require_once "../Console/GetoptLong.php";

$flag = 'ok';
$args = Console_GetoptLong::getOptions(array(
	'flag|f!'		=> &$flag,
));

echo $flag,',(', implode(',',$args), ")\n";

