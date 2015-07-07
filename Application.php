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

namespace BackBee\Standard;

use BackBee\BBApplication;
use BackBee\Console\Console;
use BackBee\Event\Event;

use Symfony\Component\Finder\Finder;

/**
 * @author e.chau <eric.chau@lp-digital.fr>
 * @author Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 */
class Application extends BBApplication
{
    /**
     * {@inheritdoc}
     */
    public function getBaseDir()
    {
        return __DIR__;
    }

    /**
     * Set the paths to Commands in BackBee Standard application
     */
    public function getCommandDirectories()
    {
        return array_merge(
             [$this->getBaseRepository() . DIRECTORY_SEPARATOR . 'Command'],
             $this->hasContext() ? [$this->getRepository() . DIRECTORY_SEPARATOR . 'Command'] : []
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getConfigurationDir()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'repository' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR;
    }

    /**
     * Check if the CMS is installed
     */
    public static function isInstalled()
    {
        $configDirectory = __DIR__.DIRECTORY_SEPARATOR.'repository'.DIRECTORY_SEPARATOR.'Config';
        return is_file($configDirectory.DIRECTORY_SEPARATOR.'sites.yml')
            && is_file($configDirectory.DIRECTORY_SEPARATOR.'doctrine.yml')
            && is_file($configDirectory.DIRECTORY_SEPARATOR.'bootstrap.yml')
        ;
    }

    /**
     * Finds and registers Commands in BackBee Standard application
     *
     * @{inheritdoc}
     * @param BackBee\Console\Console $console An Application instance
     */
    public function registerCommands(Console $console)
    {
        parent::registerCommands($console);

        $commandNamespace = 'BackBee\Standard\Command';
        $directories = $this->getCommandDirectories();

        foreach($directories as $directory) {
            if (is_dir($directory)) {

                /* register the namespace */
                $this->getAutoloader()
                    ->register()
                    ->registerNamespace($commandNamespace, $directory)
                ;

                $files = (new Finder())->files()->name('*Command.php')->in($directory);

                foreach ($files as $file) {
                    if ($relativePath = $file->getRelativePath()) {
                        $commandNamespace .= '\\'.strtr($relativePath, '/', '\\');
                    }
                    $reflectionClass = new \ReflectionClass($commandNamespace.'\\'.$file->getBasename('.php'));
                    if (
                        $reflectionClass->isSubclassOf('BackBee\\Console\\AbstractCommand')
                        && !$reflectionClass->isAbstract()
                        && !$reflectionClass->getConstructor()->getNumberOfRequiredParameters()
                    ) {
                        $console->add($reflectionClass->newInstance());
                    }
                }
            }
        }
    }

    /**
     * Stop the current BBApplication instance.
     *
     * @{inheritdoc}
     * @return void
     */
    public function stop()
    {
        parent::stop();
        exit();
    }
}

