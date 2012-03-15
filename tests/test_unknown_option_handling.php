<?php

require_once "../Console/GetoptLong.php";

// We have a couple of different modes, so we're going to cheat slightly and
// pick that off the command line as the first word; then we process the
// entire command line including the mode and that just gets chucked into the
// return value from getOptions...

$mode = $_SERVER['argv'][1];
Console_GetoptLong::setUnknownOptionHandling($mode);

$mandatory = '';
$args = Console_GetoptLong::getOptions(array(
    'mandatory|m=s' => &$mandatory,
));

echo $mandatory, ',(', implode(',', $args), ")\n";
