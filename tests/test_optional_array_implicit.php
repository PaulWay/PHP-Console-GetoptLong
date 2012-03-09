<?php

require_once "../Console/GetoptLong.php";

$optional = '';
$args = Console_GetoptLong::getOptions(array(
	'optional|o:s@' => &$optional,
));

echo '(' .implode(',',$optional) . '),(', implode(',',$args), ")\n";
