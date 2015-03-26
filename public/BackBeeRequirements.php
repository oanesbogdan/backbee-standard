<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class Requirement
{
    /**
     * @var mixed
     */
    private $expected;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $errorMessage;

    /**
     * Requirement's constructor
     */
    public function __construct($expected, $value, $title, $errorMessage)
    {
        $this->expected = $expected;
        $this->value = $value;
        $this->title = $title;
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return boolean
     */
    public function isOk()
    {
        return $this->expected === $this->value;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class BackBeeRequirements
{
    const REQUIRED_PHP_VERSION = '5.4.0';

    /**
     * @return array
     */
    public function getRequirements()
    {
        $requirements = [];

        $requirements[] = new Requirement(
            true,
            version_compare(phpversion(), self::REQUIRED_PHP_VERSION, '>='),
            'Version of PHP - required >= ' . self::REQUIRED_PHP_VERSION,
            'Your version of PHP is not compatible with BackBee. You must upgrade your PHP.'
        );

        $requirements[] = new Requirement(
            true,
            is_dir(realpath(__DIR__ . '/../vendor/composer')),
            'Dependencies installation',
            'You have to install BackBee\'s dependencies by running `composer.phar install` (https://getcomposer.org/)'
        );

        $cacheDirectory = realpath(__DIR__ . '/..') . '/cache';
        $requirements[] = new Requirement(
            true,
            is_dir($cacheDirectory) && is_writable($cacheDirectory) && is_readable($cacheDirectory),
            'Cache folder - readable and writable',
            "BackBee expected cache directory at `$cacheDirectory`; this directory must be readable and writable"
        );

        $logDirectory = realpath(__DIR__ . '/..') . '/log';
        $requirements[] = new Requirement(
            true,
            is_dir($logDirectory) && is_writable($logDirectory) && is_readable($logDirectory),
            'Log folder - readable and writable',
            "BackBee expected log directory at `$logDirectory`; this directory must be readable and writable"
        );

        return $requirements;
    }
}

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class BootstrapRequirements
{
    /**
     * @return array
     */
    public function getRequirements()
    {
        $requirements = [];

        $configDirectory = dirname(__DIR__) . '/repository/Config';
        $requirements[] = new Requirement(
            true,
            is_dir($configDirectory) && is_readable($configDirectory) && is_writable($configDirectory),
            'Project config directory - writable and readable',
            "BackBee expected project config directory at `$configDirectory`; this directory must be readable and writable"
        );

        return $requirements;
    }
}
