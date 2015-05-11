<?php

set_time_limit(0);
date_default_timezone_set('Europe/Rome');

use EndelWar\Spammer\Application\SpammerApplication;

$spammer = new SpammerApplication('Spammer', '0.1.0');
$spammer->run();
