<?php

require_once "../Console/GetoptLong.php";

$increment = 0;
$args = Console_GetoptLong::getOptions(array(
	'increment|i+'		=> &$increment,
));

echo $increment,',(', implode(',',$args), ")\n";

