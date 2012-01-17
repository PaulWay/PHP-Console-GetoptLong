<?php

require_once "../Console/GetoptLong.php";

$mandatory = '';
$args = Console_GetoptLong::getOptions(array(
	'mandatory|m=s@'		=> &$mandatory,
));

// If $mandatory is still '', implode won't work; show this separately
$mand_result = '';
if (is_array($mandatory)) {
    $mand_result = '(' .implode(',',$mandatory) . ')';
} else {
    $mand_result = $mandatory;
}
echo $mand_result,',(', implode(',',$args), ")\n";

