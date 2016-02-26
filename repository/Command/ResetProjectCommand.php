<?php

/*
 * Copyright (c) 2011-2016 Lp digital system
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

namespace BackBee\Standard\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

use BackBee\Console\AbstractCommand;

/**
 * Reset project command
 * @author Bogdan Oanes <bogdan.oanes@lp-digital.fr>
 */
class ResetProjectCommand extends AbstractCommand
{
    const USERNAME = 'admins';
    const USER_PASSWORD = 'admins';
    const USER_EMAIL = 'admins@lp-digital.fr';

    private $app;
    private $entyMgr;

    private $input;
    private $output;

    private $siteLabel;
    private $siteDomain;

    protected function configure()
    {
        $this
            ->setName('reset:project')
            ->setDescription('Reset project to basic installation.')
            # BE user
            ->addOption('username', null, InputOption::VALUE_OPTIONAL, '', self::USERNAME)
            ->addOption('user_password', null, InputOption::VALUE_OPTIONAL, '', self::USER_PASSWORD)
            ->addOption('user_email', null, InputOption::VALUE_OPTIONAL, '', self::USER_EMAIL)
            # DB connection settings
            ->addOption('driver', null, InputOption::VALUE_OPTIONAL)
            ->addOption('engine', null, InputOption::VALUE_OPTIONAL)
            ->addOption('host', null, InputOption::VALUE_OPTIONAL)
            ->addOption('port', null, InputOption::VALUE_OPTIONAL)
            ->addOption('dbname', null, InputOption::VALUE_OPTIONAL)
            ->addOption('user', null, InputOption::VALUE_OPTIONAL)
            ->addOption('password', null, InputOption::VALUE_OPTIONAL)
            # Site domain and label
            ->addOption('site_name', null, InputOption::VALUE_OPTIONAL)
            ->addOption('domain', null, InputOption::VALUE_OPTIONAL)
            # Fake data insertions
            ->addOption('article-limit', null, InputOption::VALUE_OPTIONAL)
            ->addOption('category-limit', null, InputOption::VALUE_OPTIONAL)
            ->addOption('no-image', null, InputOption::VALUE_OPTIONAL)
            ->addOption('tmp-dir', null, InputOption::VALUE_OPTIONAL)
        ;
    }

    protected function init(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->app = $this->getApplication()->getApplication();
        $this->entyMgr = $this->app->getEntityManager();

        # Get site label & domain from arguments
        $this->siteLabel = $this->input->getOption('site_name');
        $this->siteDomain = ($this->input->getOption('domain') && !strstr($this->input->getOption('domain'), 'http://'))
                                ? 'http://' . $this->input->getOption('domain')
                                : '';

        if ($this->siteLabel && $this->siteDomain) {
            return $this;
        }

        # Init website domain and label from sites.yml file
        $sitesConf = $this->app->getConfig()->getSitesConfig();
        if (is_array($sitesConf) && !empty($sitesConf)) {
            $firstWebsiteConf = reset($sitesConf);

            $this->siteLabel = $this->siteLabel ? $this->siteLabel : $firstWebsiteConf['label'];
            $this->siteDomain = $this->siteDomain ? $this->siteDomain : $firstWebsiteConf['domain'];
            $this->siteDomain = !strstr($this->siteDomain, 'http://') ? 'http://' . $this->siteDomain : $this->siteDomain;
        }

        return $this;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    { 
        $this
            ->init($input, $output)
            ->deleteTablesProcess()
            ->deleteInstallOkFile()
            ->installStep3Process()
            ->installStep4Process()
            ->insertFakeDataProcess($output)
        ;
    }

    protected function deleteTablesProcess()
    {
        # Get all table names to delete them after
        $tableNames = $this->entyMgr->getConnection()->getSchemaManager()->listTableNames();
        if (!$tableNames) {
            $this->output->writeln('<comment>No tables found in database.</comment>');
            return $this;
        }

        # Build query to drop all table
        $dropTablesQuery = "SET FOREIGN_KEY_CHECKS=0; "
                            . "DROP TABLE `" . implode('`, `', $tableNames) . "`;";
        $this->entyMgr->getConnection()->executeQuery($dropTablesQuery);

        $this->output->writeln('<info>The following tables were droped: ' . implode(', ', $tableNames) . '</info>');

        return $this;
    }

    protected function deleteInstallOkFile() 
    {
        # Delete INSTALL_OK file in order to redo step 3&4 from install.php script
        if (file_exists(getcwd() . '/public/INSTALL_OK')) {
            unlink(getcwd() . '/public/INSTALL_OK');
        }

        return $this;
    }    

    protected function installStep3Process()
    {
        # Post param for CURL on install.php
        $postVars = [
            'step' => 3,
            'username' => $this->input->getOption('username'),
            'user_email' => $this->input->getOption('user_email'),
            'user_password' => $this->input->getOption('user_password'),
            'user_re-password' => $this->input->getOption('user_password'),

            'driver' => $this->input->getOption('driver'),
            'engine' => $this->input->getOption('engine'),
            'host' => $this->input->getOption('host'),
            'port' => $this->input->getOption('port'),
            'dbname' => $this->input->getOption('dbname'),
            'user' => $this->input->getOption('user'),
            'password' => $this->input->getOption('password')
        ];

        # Get doctrine configurations, if are not present in command attributes, to send them in post with CURL
        $doctrineConf = $this->app->getConfig()->getDoctrineConfig();
        if (!is_array($doctrineConf)) {
            $this->output->writeln('<error>Doctrine configurations not found.</error>');
            return $this;
        }

        array_walk_recursive($doctrineConf, function($value, $key) use (&$postVars) {
            (array_key_exists($key, $postVars) && !$postVars[$key]) ? ($postVars[$key] = $value) : '';
        });

        # Reinstall database (install.php step 3)
        $this->doCurlWithPost($postVars);

        return $this;
    }

    protected function installStep4Process()
    {
        # Post param for CURL on install.php
        $postVars = [
            'step' => 4,
            'site_name' => $this->siteLabel,
            'domain' => $this->siteDomain
        ];

        # Rebuild db entries (install.php step 4)
        $this->doCurlWithPost($postVars);

        return $this;
    }

    protected function insertFakeDataProcess($output)
    {
        $command = $this->getApplication()->find('fake:data:generate');
        
        $input = new ArrayInput(
            [
                '--article-limit' => $this->input->getOption('article-limit'),
                '--category-limit' => $this->input->getOption('category-limit'),
                '--no-image' => $this->input->getOption('no-image'),
                '--tmp-dir' => $this->input->getOption('tmp-dir')
            ]
        );

        # Run insert fake data command
        $returnCode = $command->run($input, $output);
        
        if ($returnCode !== 0) {
             $this->output->writeln('<error>Error on calling insert fake data command </error>');
        }

        return $this;
    }

    protected function doCurlWithPost($postVars = []) 
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $this->siteDomain . '/install.php');
        curl_setopt($ch,CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $postVars);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,3);
        curl_setopt($ch,CURLOPT_TIMEOUT, 20);

        if (curl_exec($ch) === false) {
            $this->output->writeln('<error>Curl error on install.php: step ' . $postVars["step"] . '</error>');
        } else {
            $this->output->writeln('<info>Curl success on install.php: step ' . $postVars["step"] . '</info>');
        }

        curl_close($ch);
    }     
}