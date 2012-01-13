<?php

require_once "../Console/GetoptLong.php";

$args = Console_GetoptLong::getOptions(array());

echo '(', implode(',',$args), ")\n";

