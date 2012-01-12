<?php

require_once "../Console/GetoptLong.php";

$increment = 0;
$args = Console_GetoptLong::GetOptions(array(
	'increment|i+'		=> &$increment,
));

echo $increment,',(', implode(',',$args), ")\n";

