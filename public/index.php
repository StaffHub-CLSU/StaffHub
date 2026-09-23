<?php

declare(strict_types=1);

/**
 * Front controller — the only PHP file the web server should execute
 * for application routes (static assets are served directly from public/).
 */

$app = require dirname(__DIR__) . '/bootstrap.php';
$app->run();
