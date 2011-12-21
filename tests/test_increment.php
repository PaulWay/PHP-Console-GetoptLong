<?php

require_once "../getopt_long.php";

$increment = 0;
$args = GetOptions(array(
	'increment|i+'		=> &$increment,
));

echo $increment,',(', implode(',',$args), ")\n";

