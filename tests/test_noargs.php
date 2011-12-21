<?php

require_once "../getopt_long.php";

$args = GetOptions();

echo '(', implode(',',$args), ")\n";

