<?php

require_once "../Console/GetoptLong.php";

$mandatory = array();
$args = Console_GetoptLong::getOptions(array(
	'mandatory|m=s'		=> &$mandatory,
));

echo '(',implode(',',$mandatory),'),(', implode(',',$args), ")\n";

