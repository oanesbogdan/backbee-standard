<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee Standard Edition.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee Standard Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee Standard Edition. If not, see <http://www.gnu.org/licenses/>.
 */

if (!is_file($autoloadFile = __DIR__.'/../vendor/autoload.php')) {
    echo('<p>BackBee could not find composer autoloader. Did you install and run "composer install --no-dev" command?</p>');
    throw new \LogicException('Could not find autoload.php in vendor/. Did you run "composer install --no-dev"?');
}

require $autoloadFile;

$context = null;
$environment = null;



/**
 * After installation you can delete this check
 * and only keep $application->start();
 */
if (BackBee\Standard\Application::isInstalled()) {
    $application = new \BackBee\Standard\Application($context, $environment);
    $application->start();
}else {
    header('Location: install.php');
}

