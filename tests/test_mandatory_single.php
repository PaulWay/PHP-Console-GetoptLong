<?php

require_once "../getopt_long.php";

$mandatory = '';
$args = GetOptions(
	'mandatory|m'		=> &$mandatory,
);

echo $mandatory,',(', implode(',',$args), ")\n";

