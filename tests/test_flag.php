<?php

require_once "../Console/GetoptLong.php";

$verbose = 0;
$args = Console_GetoptLong::getOptions(array(
	'verbose|v'		=> &$verbose,
));

echo $verbose,',(', implode(',',$args), ")\n";

