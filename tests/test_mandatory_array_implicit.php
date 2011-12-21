<?php

require_once "../getopt_long.php";

$mandatory = array();
$args = GetOptions(array(
	'mandatory|m=s'		=> &$mandatory,
));

echo '(',implode(',',$mandatory),'),(', implode(',',$args), ")\n";

