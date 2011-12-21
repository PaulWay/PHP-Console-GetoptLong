<?php

require_once "../getopt_long.php";

$verbose = 0;
$args = GetOptions(array(
	'verbose|v'		=> &$verbose,
));

echo $verbose,',(', implode(',',$args), ")\n";

