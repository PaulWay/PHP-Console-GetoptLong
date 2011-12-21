<?php

require_once "../getopt_long.php";

$mandatory = '';
$args = GetOptions(array(
	'mandatory|m=s@'		=> &$mandatory,
));

echo '(',implode(',',$mandatory),'),(', implode(',',$args), ")\n";

