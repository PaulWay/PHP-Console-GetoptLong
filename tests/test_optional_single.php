<?php

require_once "../getopt_long.php";

$optional = '';
$args = GetOptions(array(
	'optional|o:s'		=> &$optional,
));

echo $optional,',(', implode(',',$args), ")\n";

