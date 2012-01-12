<?php

require_once "../Console/GetoptLong.php";

$args = Console_GetoptLong::GetOptions(array());

echo '(', implode(',',$args), ")\n";

