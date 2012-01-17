<?php

require_once "../Console/GetoptLong.php";

$optional = '';
$args = Console_GetoptLong::getOptions(array(
	'optional|o:i'		=> &$optional,
));

echo $optional,',(', implode(',',$args), ")\n";

