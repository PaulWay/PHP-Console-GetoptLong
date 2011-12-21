<?php

require_once "../getopt_long.php";

$optional = '';
$args = GetOptions(
	'optional|o'		=> &$optional,
);

echo $optional,',(', implode(',',$args), ")\n";

