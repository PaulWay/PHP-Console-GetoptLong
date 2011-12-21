<?php

require_once "../getopt_long.php";

$args = GetOptions(array());

echo '(', implode(',',$args), ")\n";

