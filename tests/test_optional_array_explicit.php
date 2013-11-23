<?php

require_once "../Console/GetoptLong.php";

$optional = '';
$args = Console_GetoptLong::getOptions(array(
	'optional|o:s@' => &$optional,
));

// If $optional is still '', implode won't work; show this separately
$opt_result = '';
if (is_array($optional)) {
    $opt_result = '(' .implode(',',$optional) . ')';
} else {
    $opt_result = $optional;
}
echo $opt_result,',(', implode(',',$args), ")\n";

