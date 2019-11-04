<?php

/**
 * crontab * * * * *
 */

set_time_limit(0);
require_once __DIR__."/../htdocs/app_api.php";

crontab_run_one("crontab","run_ourimg_queue");

(new QBus_Dispatcher())->run();