<?php

if (!is_file($autoloadFile = __DIR__.'/../vendor/autoload.php')) {
    throw new \LogicException('Could not find autoload.php in vendor/. Did you run "composer install --dev"?');
}

require $autoloadFile;

$context = null;
$environment = null;

$application = new \BackBuilder\BBApplication($context, $environment);
$application->start();
