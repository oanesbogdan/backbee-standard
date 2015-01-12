<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
if (!isset($_SERVER['HTTP_HOST'])) {
    exit('This script cannot be run from the CLI. Run it from a browser.');
}

if (!in_array(@$_SERVER['REMOTE_ADDR'], [
    '127.0.0.1',
    '10.0.2.2',
    '::1',
])) {
    header('HTTP/1.0 403 Forbidden');
    exit('This script is only accessible from localhost.');
}

require_once __DIR__ . '/BackBeeRequirements.php';

$step = true === isset($_POST['step']) ? intval($_POST['step']) : 1;

switch ($step) {
    case 2:
        if (false === isset($_POST['debug']) && false === isset($_POST['container_dump_directory'])) {
            $bootstrap_requirements = new BootstrapRequirements();
            $requirements = $bootstrap_requirements->getRequirements();
            break;
        } else {
            require_once dirname(__DIR__) . '/vendor/autoload.php';

            $yaml = new \Symfony\Component\Yaml\Yaml();

            $container_directory = realpath(__DIR__ . '/..') . '/cache/container';
            if (false === is_dir($container_directory)) {
                mkdir($container_directory, 755);
            }

            $bootstrap = [
                'debug'     => (bool) intval($_POST['debug']),
                'container' => [
                    'dump_directory' => $container_directory,
                    'autogenerate'   => true
                ]
            ];

            file_put_contents(dirname(__DIR__) . '/repository/Config/bootstrap.yml', $yaml->dump($bootstrap));

            $step = 3;
        }

    case 3:
        if (
            true === isset($_POST['driver'])
            && true === isset($_POST['engine'])
            && true === isset($_POST['host'])
            && true === isset($_POST['port'])
            && true === isset($_POST['dbname'])
            && true === isset($_POST['user'])
            && true === array_key_exists('password', $_POST)
            && true === isset($_POST['charset'])
            && true === isset($_POST['collation'])
        ) {

            require_once dirname(__DIR__) . '/vendor/autoload.php';

            $yaml = new \Symfony\Component\Yaml\Yaml();
            $doctrine = [
                'dbal' => [
                    'driver'    => $_POST['driver'],
                    'host'      => $_POST['host'],
                    'port'      => intval($_POST['port']),
                    'dbname'    => $_POST['dbname'],
                    'user'      => $_POST['user'],
                    'password'  => $_POST['password'],
                    'charset'   => $_POST['charset'],
                    'collation' => $_POST['collation'],
                    'defaultTableOptions' => [
                        'collate' => $_POST['collation'],
                        'engine'  => $_POST['engine'],
                        'charset' => $_POST['charset']
                    ]
                ]
            ];

            file_put_contents(dirname(__DIR__) . '/repository/Config/doctrine.yml', $yaml->dump($doctrine));

            $username = $_POST['user'];
            $password = $_POST['password'];
            $dbname = $_POST['dbname'];
            $host = $_POST['host'];
            $port = $_POST['port'];
            $charset = $_POST['charset'];
            $collation = $_POST['collation'];
            try {
                $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
            } catch (PDOException $ex) {
                try {
                    $conn = mysqli_connect($host, $username, $password, null, $port);
                    mysqli_query($conn, "create database IF NOT EXISTS `" . addslashes($dbname) . "` character set $charset collate $collation;");
                } catch (\Exception $e) {
                    echo "Failed to connect to database ", $ex->getMessage();
                    exit();
                }
            }

            $application = new \BackBuilder\BBApplication();

            try {
                $database = new BackBuilder\Installer\Database($application);
                $database->updateBackBuilderSchema();
                $database->updateBundlesSchema();
            } catch (\Exception $e) {
                echo $e->getMessage();
                exit();
            }

            $em = $application->getEntityManager();

            try {
                $response = $em->getConnection()
                   ->exec("REPLACE INTO `layout` (`uid`, `site_uid`, `label`, `path`, `data`, `created`, `modified`, `picpath`) VALUES ('0760d5a8249eb0d406a2dfc2c6f8c2c4', NULL, 'Model : 1/3 2/3', 'template4.phtml', 0x7B2274656D706C6174654C61796F757473223A5B7B227469746C65223A227A6F6E655F313333323934343035343632325F33222C226C61796F757453697A65223A7B22686569676874223A3330302C227769647468223A66616C73657D2C226772696453697A65496E666F73223A7B22636F6C5769647468223A36302C226775747465725769647468223A32307D2C226964223A224C61796F75745F5F313333323934343035343632315F32222C226C61796F7574436C617373223A22626234526573697A61626C654C61796F7574222C22616E696D617465526573697A65223A66616C73652C2273686F775469746C65223A66616C73652C22746172676574223A22236262352D6D61696E4C61796F7574526F77222C22726573697A61626C65223A747275652C227573654772696453697A65223A747275652C226772696453697A65223A342C226772696453746570223A38302C2267726964436C617373507265666978223A227370616E222C2273656C6563746564436C617373223A2273656C65637465644C61796F7574222C22706F736974696F6E223A226E6F6E65222C22616C706861436C617373223A22222C226F6D656761436C617373223A22222C2264656661756C74436F6E7461696E6572223A22236262352D6D61696E4C61796F7574526F77227D2C7B227469746C65223A227A6F6E655F313333323934343035343632325F35222C226C61796F757453697A65223A7B22686569676874223A3330302C227769647468223A66616C73657D2C226772696453697A65496E666F73223A7B22636F6C5769647468223A36302C226775747465725769647468223A32307D2C226964223A224C61796F75745F5F313333323934343035343632325F34222C226C61796F7574436C617373223A22626234526573697A61626C654C61796F7574222C22616E696D617465526573697A65223A66616C73652C2273686F775469746C65223A66616C73652C22746172676574223A22236262352D6D61696E4C61796F7574526F77222C22726573697A61626C65223A747275652C227573654772696453697A65223A747275652C226772696453697A65223A382C226772696453746570223A38302C2267726964436C617373507265666978223A227370616E222C2273656C6563746564436C617373223A2273656C65637465644C61796F7574222C22706F736974696F6E223A226E6F6E65222C22616C706861436C617373223A22222C226F6D656761436C617373223A22222C2264656661756C74436F6E7461696E6572223A22236262352D6D61696E4C61796F7574526F77227D5D2C226772696453697A65223A22227D, '2012-03-28 14:18:22', '2012-03-28 14:18:22', 'img/layouts/0760d5a8249eb0d406a2dfc2c6f8c2c4.png');
                    REPLACE INTO `layout` (`uid`, `site_uid`, `label`, `path`, `data`, `created`, `modified`, `picpath`) VALUES ('5b1d38daf71f08551b711c2a173417a5', NULL, 'Model : 2 block horizontal', 'template5.phtml', 0x7B2274656D706C6174654C61796F757473223A5B7B227469746C65223A227A6F6E655F313333323934343332323839325F37222C226C61796F757453697A65223A7B22686569676874223A3330302C227769647468223A66616C73657D2C226772696453697A65496E666F73223A7B22636F6C5769647468223A36302C226775747465725769647468223A32307D2C226964223A224C61796F75745F5F313333323934343332323839325F36222C226C61796F7574436C617373223A22626234526573697A61626C654C61796F7574222C22616E696D617465526573697A65223A66616C73652C2273686F775469746C65223A66616C73652C22746172676574223A22236262352D6D61696E4C61796F7574526F77222C22726573697A61626C65223A747275652C227573654772696453697A65223A747275652C226772696453697A65223A31322C226772696453746570223A38302C2267726964436C617373507265666978223A227370616E222C2273656C6563746564436C617373223A2273656C65637465644C61796F7574222C22706F736974696F6E223A226E6F6E65222C22616C706861436C617373223A22222C226F6D656761436C617373223A22222C2264656661756C74436F6E7461696E6572223A22236262352D6D61696E4C61796F7574526F77227D2C7B227469746C65223A227A6F6E655F313333323934343332323839335F39222C226C61796F757453697A65223A7B22686569676874223A3330302C227769647468223A66616C73657D2C226772696453697A65496E666F73223A7B22636F6C5769647468223A36302C226775747465725769647468223A32307D2C226964223A224C61796F75745F5F313333323934343332323839335F38222C226C61796F7574436C617373223A22626234526573697A61626C654C61796F7574222C22616E696D617465526573697A65223A66616C73652C2273686F775469746C65223A66616C73652C22746172676574223A22234C61796F75745F5F313333323934343332323839325F36222C22726573697A61626C65223A66616C73652C227573654772696453697A65223A747275652C226772696453697A65223A31322C226772696453746570223A38302C2267726964436C617373507265666978223A227370616E222C2273656C6563746564436C617373223A2273656C65637465644C61796F7574222C22706F736974696F6E223A226E6F6E65222C22616C706861436C617373223A22616C706861222C226F6D656761436C617373223A226F6D656761222C2274797065436C617373223A22684368696C64222C22636C6561724166746572223A312C22686569676874223A3430302C22685369626C696E67223A224C61796F75745F5F313333323934343332323839335F3130222C2264656661756C74436F6E7461696E6572223A22236262352D6D61696E4C61796F7574526F77227D2C7B227469746C65223A227A6F6E655F313333323934343332323839335F3132222C226C61796F757453697A65223A7B22686569676874223A3330302C227769647468223A66616C73657D2C226772696453697A65496E666F73223A7B22636F6C5769647468223A36302C226775747465725769647468223A32307D2C226964223A224C61796F75745F5F313333323934343332323839335F3131222C226C61796F7574436C617373223A22626234526573697A61626C654C61796F7574222C22616E696D617465526573697A65223A66616C73652C2273686F775469746C65223A66616C73652C22746172676574223A22234C61796F75745F5F313333323934343332323839325F36222C22726573697A61626C65223A66616C73652C227573654772696453697A65223A747275652C226772696453697A65223A32342C226772696453746570223A38302C2267726964436C617373507265666978223A227370616E222C2273656C6563746564436C617373223A2273656C65637465644C61796F7574222C22706F736974696F6E223A226E6F6E65222C22616C706861436C617373223A22616C706861222C226F6D656761436C617373223A226F6D656761222C2274797065436C617373223A22684368696C64222C22636C6561724166746572223A312C22686569676874223A3430302C22685369626C696E67223A224C61796F75745F5F313333323934343332323839335F38222C2264656661756C74436F6E7461696E6572223A22236262352D6D61696E4C61796F7574526F77227D5D2C226772696453697A65223A22227D, '2012-03-28 14:19:45', '2012-03-28 14:19:45', 'img/layouts/5b1d38daf71f08551b711c2a173417a5.png');
                    REPLACE INTO `layout` (`uid`, `site_uid`, `label`, `path`, `data`, `created`, `modified`, `picpath`) VALUES ('5e7fc7300ab7fc4b2fc2a9ad6997166f', NULL, 'Default template', 'template1.phtml', 0x7B2274656D706C6174654C61796F757473223A5B7B227469746C65223A22726F6F74222C226C61796F757453697A65223A7B22686569676874223A3330302C227769647468223A66616C73657D2C226772696453697A65496E666F73223A7B22636F6C5769647468223A36302C226775747465725769647468223A32307D2C226964223A22726F6F744C61796F7574222C226C61796F7574436C617373223A22626234526573697A61626C654C61796F7574222C22616E696D617465526573697A65223A66616C73652C2273686F775469746C65223A66616C73652C22746172676574223A22236262352D6D61696E4C61796F7574526F77222C22726573697A61626C65223A747275652C227573654772696453697A65223A747275652C226772696453697A65223A31322C226772696453746570223A3130302C2267726964436C617373507265666978223A227370616E222C2273656C6563746564436C617373223A2273656C65637465644C61796F7574222C2264656661756C74436F6E7461696E6572223A22236262352D6D61696E4C61796F7574526F77222C226C61796F75744D616E61676572223A5B5D7D5D7D, '2012-04-25 16:17:10', '2012-04-25 16:17:10', 'img/layouts/5e7fc7300ab7fc4b2fc2a9ad6997166f.png');
                    REPLACE INTO `layout` (`uid`, `site_uid`, `label`, `path`, `data`, `created`, `modified`, `picpath`) VALUES ('7e7d57b47beb1f326a72726dca6df9dd', NULL, 'Model : 2/3 1/3', 'template3.phtml', 0x7B2274656D706C6174654C61796F757473223A5B7B227469746C65223A227A6F6E655F313333323934343035343632325F33222C226C61796F757453697A65223A7B22686569676874223A3330302C227769647468223A66616C73657D2C226772696453697A65496E666F73223A7B22636F6C5769647468223A36302C226775747465725769647468223A32307D2C226964223A224C61796F75745F5F313333323934343035343632315F32222C226C61796F7574436C617373223A22626234526573697A61626C654C61796F7574222C22616E696D617465526573697A65223A66616C73652C2273686F775469746C65223A66616C73652C22746172676574223A22236262352D6D61696E4C61796F7574526F77222C22726573697A61626C65223A747275652C227573654772696453697A65223A747275652C226772696453697A65223A382C226772696453746570223A38302C2267726964436C617373507265666978223A227370616E222C2273656C6563746564436C617373223A2273656C65637465644C61796F7574222C22706F736974696F6E223A226E6F6E65222C22616C706861436C617373223A22222C226F6D656761436C617373223A22222C2264656661756C74436F6E7461696E6572223A22236262352D6D61696E4C61796F7574526F77227D2C7B227469746C65223A227A6F6E655F313333323934343035343632325F35222C226C61796F757453697A65223A7B22686569676874223A3330302C227769647468223A66616C73657D2C226772696453697A65496E666F73223A7B22636F6C5769647468223A36302C226775747465725769647468223A32307D2C226964223A224C61796F75745F5F313333323934343035343632325F34222C226C61796F7574436C617373223A22626234526573697A61626C654C61796F7574222C22616E696D617465526573697A65223A66616C73652C2273686F775469746C65223A66616C73652C22746172676574223A22236262352D6D61696E4C61796F7574526F77222C22726573697A61626C65223A747275652C227573654772696453697A65223A747275652C226772696453697A65223A342C226772696453746570223A38302C2267726964436C617373507265666978223A227370616E222C2273656C6563746564436C617373223A2273656C65637465644C61796F7574222C22706F736974696F6E223A226E6F6E65222C22616C706861436C617373223A22222C226F6D656761436C617373223A22222C2264656661756C74436F6E7461696E6572223A22236262352D6D61696E4C61796F7574526F77227D5D2C226772696453697A65223A22227D, '2012-03-28 14:17:12', '2012-03-28 14:17:12', 'img/layouts/7e7d57b47beb1f326a72726dca6df9dd.png');
                    REPLACE INTO `layout` (`uid`, `site_uid`, `label`, `path`, `data`, `created`, `modified`, `picpath`) VALUES ('b3fe3d6c00a143879965abfde008538f', NULL, 'Model : Two columns', 'template2.phtml', 0x7B2274656D706C6174654C61796F757473223A5B7B227469746C65223A224C61796F7574203A20313220636F6C287329222C226C61796F757453697A65223A7B22686569676874223A3330302C227769647468223A66616C73657D2C226772696453697A65496E666F73223A7B22636F6C5769647468223A36302C226775747465725769647468223A32307D2C226964223A224C61796F75745F5F313333323934333633383133395F31222C226C61796F7574436C617373223A22626234526573697A61626C654C61796F7574222C22616E696D617465526573697A65223A66616C73652C2273686F775469746C65223A66616C73652C22746172676574223A22236262352D6D61696E4C61796F7574526F77222C22726573697A61626C65223A747275652C227573654772696453697A65223A747275652C226772696453697A65223A362C226772696453746570223A38302C2267726964436C617373507265666978223A227370616E222C2273656C6563746564436C617373223A2273656C65637465644C61796F7574222C22706F736974696F6E223A226E6F6E65222C22686569676874223A3830302C2264656661756C74436F6E7461696E6572223A22236262352D6D61696E4C61796F7574526F77227D2C7B227469746C65223A224C61796F7574203A20313220636F6C287329222C226C61796F757453697A65223A7B22686569676874223A3330302C227769647468223A66616C73657D2C226772696453697A65496E666F73223A7B22636F6C5769647468223A36302C226775747465725769647468223A32307D2C226964223A224C61796F75745F5F313333323934333633383133375F30222C226C61796F7574436C617373223A22626234526573697A61626C654C61796F7574222C22616E696D617465526573697A65223A66616C73652C2273686F775469746C65223A66616C73652C22746172676574223A22236262352D6D61696E4C61796F7574526F77222C22726573697A61626C65223A747275652C227573654772696453697A65223A747275652C226772696453697A65223A362C226772696453746570223A38302C2267726964436C617373507265666978223A227370616E222C2273656C6563746564436C617373223A2273656C65637465644C61796F7574222C22706F736974696F6E223A226E6F6E65222C22686569676874223A3830302C2264656661756C74436F6E7461696E6572223A22236262352D6D61696E4C61796F7574526F77227D5D2C226772696453697A65223A22227D, '2012-03-28 14:08:37', '2012-03-28 14:08:37', 'img/layouts/b3fe3d6c00a143879965abfde008538f.png');"
                );

                $em->getConnection()->exec("CREATE TABLE IF NOT EXISTS `acl_classes` (
                    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `class_type` VARCHAR(200) NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `UNIQ_69DD750638A36066` (`class_type`)
                )
                COLLATE='utf8_general_ci'
                ENGINE=InnoDB;
                ");

                $em->getConnection()->exec("CREATE TABLE IF NOT EXISTS `acl_entries` (
                    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `class_id` INT(10) UNSIGNED NOT NULL,
                    `object_identity_id` INT(10) UNSIGNED NULL DEFAULT NULL,
                    `security_identity_id` INT(10) UNSIGNED NOT NULL,
                    `field_name` VARCHAR(50) NULL DEFAULT NULL,
                    `ace_order` SMALLINT(5) UNSIGNED NOT NULL,
                    `mask` INT(11) NOT NULL,
                    `granting` TINYINT(1) NOT NULL,
                    `granting_strategy` VARCHAR(30) NOT NULL,
                    `audit_success` TINYINT(1) NOT NULL,
                    `audit_failure` TINYINT(1) NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `UNIQ_46C8B806EA000B103D9AB4A64DEF17BCE4289BF4` (`class_id`, `object_identity_id`, `field_name`, `ace_order`),
                    INDEX `IDX_46C8B806EA000B103D9AB4A6DF9183C9` (`class_id`, `object_identity_id`, `security_identity_id`),
                    INDEX `IDX_46C8B806EA000B10` (`class_id`),
                    INDEX `IDX_46C8B8063D9AB4A6` (`object_identity_id`),
                    INDEX `IDX_46C8B806DF9183C9` (`security_identity_id`)
                )
                COLLATE='utf8_general_ci'
                ENGINE=InnoDB;
                ");

                $em->getConnection()->exec("CREATE TABLE IF NOT EXISTS `acl_object_identities` (
                    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `parent_object_identity_id` INT(10) UNSIGNED NULL DEFAULT NULL,
                    `class_id` INT(10) UNSIGNED NOT NULL,
                    `object_identifier` VARCHAR(100) NOT NULL,
                    `entries_inheriting` TINYINT(1) NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `UNIQ_9407E5494B12AD6EA000B10` (`object_identifier`, `class_id`),
                    INDEX `IDX_9407E54977FA751A` (`parent_object_identity_id`)
                )
                COLLATE='utf8_general_ci'
                ENGINE=InnoDB;
                ");

                $em->getConnection()->exec("CREATE TABLE IF NOT EXISTS `acl_object_identity_ancestors` (
                    `object_identity_id` INT(10) UNSIGNED NOT NULL,
                    `ancestor_id` INT(10) UNSIGNED NOT NULL,
                    PRIMARY KEY (`object_identity_id`, `ancestor_id`),
                    INDEX `IDX_825DE2993D9AB4A6` (`object_identity_id`),
                    INDEX `IDX_825DE299C671CEA1` (`ancestor_id`)
                )
                COLLATE='utf8_general_ci'
                ENGINE=InnoDB;
                ");

                $em->getConnection()->exec("CREATE TABLE IF NOT EXISTS `acl_security_identities` (
                    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `identifier` VARCHAR(200) NOT NULL,
                    `username` TINYINT(1) NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `UNIQ_8835EE78772E836AF85E0677` (`identifier`, `username`)
                )
                COLLATE='utf8_general_ci'
                ENGINE=InnoDB;
                ");

                $em->getConnection()->exec("CREATE TABLE IF NOT EXISTS `idx_page_content` (
                    `page_uid` VARCHAR(32) NOT NULL,
                    `content_uid` VARCHAR(32) NOT NULL,
                    PRIMARY KEY (`page_uid`, `content_uid`),
                    INDEX `IDX_PAGE` (`page_uid`),
                    INDEX `IDX_CONTENT` (`content_uid`)
                )
                COLLATE='utf8_general_ci'
                ENGINE=InnoDB;
                ");


                $em->getConnection()->exec("REPLACE INTO `user` (`id`, `login`, `password`, `activated`, `created`, `modified`) VALUES ('', 'admin', '" . md5('admin') . "', 1, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP());");
                $em->getConnection()->exec("UPDATE `user` SET `id` = 1 WHERE `login` = 'admin'");
            } catch (\Exception $e) {
                echo $e->getMessage();
                die;
            }

            $step = 4;
        }

        break;

    case 4:
        if (true === isset($_POST['site_name']) && true === isset($_POST['domain'])) {
            require_once dirname(__DIR__) . '/vendor/autoload.php';

            $yaml = new \Symfony\Component\Yaml\Yaml();

            $sites = [
                \BackBuilder\Util\String::urlize($_POST['site_name']) => [
                    'label'  => $_POST['site_name'],
                    'domain' => $_POST['domain'],
                ]
            ];

            file_put_contents(dirname(__DIR__) . '/repository/Config/sites.yml', $yaml->dump($sites));

            $application = new \BackBuilder\BBApplication();

            $em = $application->getEntityManager();

            $pagebuilder = $application->getContainer()->get('pagebuilder');

            foreach ($sites as $label => $siteconfig) {
                // Création d'un site
                if (null === $site = $em->find('BackBuilder\Site\Site', md5($label))) {
                    $site = new \BackBuilder\Site\Site(md5($label));
                    $site->setLabel($label)
                         ->setServerName($siteconfig['domain'])
                    ;
                    $em->persist($site);
                }
                // Home layout
                if (null === $layout = $em->find('BackBuilder\Site\Layout', md5('defaultlayout-' . $label))) {
                    $defaultlayout = $em->find('BackBuilder\Site\Layout', '7e7d57b47beb1f326a72726dca6df9dd');
                    $layout = new \BackBuilder\Site\Layout(md5('defaultlayout-' . $label));
                    $layout->setData($defaultlayout->getData())
                        ->setLabel('Home')
                        ->setPath('Home.twig')
                        ->setPicPath($layout->getUid() . '.png')
                        ->setSite($site)
                    ;
                    $em->persist($layout);
                }

                // Article's layout
                if (null === $articleLayout = $em->find('BackBuilder\Site\Layout', md5('articlelayout-' . $label))) {
                    $articleLayout = new BackBuilder\Site\Layout(md5('articlelayout-' . $label));
                    $articleLayout->setData('{"templateLayouts":[{"title":"Layout : 12 col(s)","layoutSize":{"height":300,"width":false},"gridSizeInfos":{"colWidth":60,"gutterWidth":20},"id":"Layout__1332943638139_1","layoutClass":"bb4ResizableLayout","animateResize":false,"showTitle":false,"target":"#bb5-mainLayoutRow","resizable":true,"useGridSize":true,"gridSize":5,"gridStep":100,"gridClassPrefix":"span","selectedClass":"bb5-layout-selected","position":"none","height":800,"defaultContainer":"#bb5-mainLayoutRow","layoutManager":[],"mainZone":true,"accept":[""],"maxentry":"0","defaultClassContent":"article"},{"title":"Nouvelle zone","layoutSize":{"height":800,"width":false},"gridSizeInfos":{"colWidth":60,"gutterWidth":20},"id":"Layout__1383430750637_1","layoutClass":"bb5-resizableLayout","animateResize":false,"showTitle":false,"target":"#bb5-mainLayoutRow","resizable":true,"useGridSize":true,"gridSize":2,"gridStep":100,"gridClassPrefix":"span","selectedClass":"bb5-layout-selected","alphaClass":"alpha","omegaClass":"omega","typeClass":"hChild","clearAfter":1,"height":800,"defaultContainer":"#bb5-mainLayoutRow","layoutManager":[],"mainZone":false,"accept":[],"maxentry":0,"defaultClassContent":null}]}')
                        ->setLabel('Article')
                        ->setPicPath($articleLayout->getUid() . '.png')
                        ->setSite($site)
                    ;
                    $em->persist($articleLayout);
                }

                // Creating site root page
                if (null === $root = $em->find('BackBuilder\NestedNode\Page', md5('root-' . $label))) {
                    $block_demo = new \BackBuilder\ClassContent\block_demo();
                    $block_demo->setState(\BackBuilder\ClassContent\AClassContent::STATE_NORMAL);
                    $block_demo->setRevision(1);
                    $home_container = new \BackBuilder\ClassContent\home\home_container();
                    $home_container->setState(\BackBuilder\ClassContent\AClassContent::STATE_NORMAL);
                    $home_container->setRevision(1);
                    $home_container->container->setState(\BackBuilder\ClassContent\AClassContent::STATE_NORMAL);
                    $home_container->container->setRevision(1);
                    $home_container->container->push($block_demo);
                    $pagebuilder->setUid(md5('root-' . $label))
                        ->setTitle('Home')
                        ->setLayout($layout)
                        ->setSite($site)
                        ->setUrl('/')
                        ->putOnlineAndHidden()
                        ->pushElement($home_container);
                    ;

                    $page = $pagebuilder->getPage();
                    $em->persist($page);
                    $em->flush($page);
                }
                // Creating mediacenter root
                if (null === $mediafolder = $em->find('BackBuilder\NestedNode\MediaFolder', md5('media'))) {
                    $mediafolder = new \BackBuilder\NestedNode\MediaFolder(md5('media'));
                    $mediafolder->setTitle('Mediacenter')->setUrl('/');
                    $em->persist($mediafolder);
                }
                $em->flush();
            }

            if (null === $em->find('BackBuilder\NestedNode\KeyWord', md5('root'))) {
                $keyword = new \BackBuilder\NestedNode\KeyWord(md5('root'));
                $keyword->setRoot($keyword);
                $keyword->setKeyWord('root');
                $em->persist($keyword);
                $em->flush();
            }

            $step = 5;
        }

        break;

    case 1:
    default:
        $BackBuilder_requirements = new BackBeeRequirements();
        $requirements = $BackBuilder_requirements->getRequirements();
}

?>

<!doctype html>

<html lang="en">
    <head>
        <meta charset="utf-8">

        <title>BackBuilder standard installation</title>
        <!--<meta name="description" content="The HTML5 Herald">
        <meta name="author" content="SitePoint">-->

        <link rel="stylesheet" href="css/installer.css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">

        <!--[if lt IE 9]>
            <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->

        <link rel="icon" href="favicon.ico">
        <style>
            div.alert strong {
                display: inline-block;
                width: 350px;
            }

            form label {
                display: block;
            }

            div.steps-container {
                background-color: #f4f4f4;
                border: 1px solid #e1e1e1;
                border-top: 0;
                padding: 10px 20px;
            }

            div.steps-container h2 {
                border-bottom: 2px solid #e54a46;
                color: #e54a46;
                margin: 10px 0 20px 0;
                padding: 0 0 5px 0;
            }

            div.cover-header {
                padding: 3px 0 0 26px !important;
                height: 87px !important;
            }
        </style>

    </head>

    <body>

        <div id="bb5-site-wrapper">
            <div class="cover-container">
                <div class="inner cover">
                    <div class="cover-header">
                        <h1 class="masthead-brand"><img src="img/logo.png" alt="BackBuilder"> Installer</h1>
                    </div>

                    <?php if (1 === $step): ?>
                        <div class="cover-body">
                            <div class="welcome">
                                <h2 class="welcome-heading">Welcome to <span>BackBuilder Installation</span></h2>

                                <p>In order to install BackBuilder properly, we need to check if your system fulfills all the requirements.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="steps-container">

                    <?php if (2 === $step): ?>

                        <h2>Step 2 - Application bootstrap file</h2>

                        <div>
                            <?php $success = true; ?>
                            <?php foreach ($requirements as $requirement): ?>
                                <div class="alert <?php echo (true === $requirement->isOk() ? 'alert-success' : 'alert-danger'); ?>">
                                    <strong><?php echo $requirement->getTitle(); ?></strong> <?php echo (true === $requirement->isOk() ? 'OK' : $requirement->getErrorMessage()); ?>
                                </div>
                                <?php $success = $success && $requirement->isOk(); ?>
                            <?php endforeach; ?>
                        </div>

                        <div>
                            <?php if (false === $success): ?>
                                <form action="" method="POST">
                                    <input type="hidden" name="step" value="2" />
                                    <input type="submit" value="Check again" />
                                </form>
                            <?php else: ?>
                                <form action="" method="POST" role="form">
                                    <input type="hidden" name="step" value="2" />

                                    <div class="form-group">
                                        <label for="debug" style="display: inline-block; width: 150px;">Debug</label>
                                        <select name="debug">
                                            <option value="1" selected>true</option>
                                            <option value="0">false</option>
                                        </select>
                                    </div>

                                    <div class="text-right">
                                        <input type="submit" class="btn btn-primary" value="Save it and go to step 3" />
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>

                    <?php elseif (3 === $step): ?>

                        <h2>Step 3 - Database's configuration</h2>

                        <div>
                            <form action="" method="POST" role="form">
                                <input type="hidden" name="step" value="3" />

                                <div class="form-group">
                                    <label for="driver">driver</label>
                                    <input type="text" name="driver" value="pdo_mysql" required="required" />
                                </div>

                                <div class="form-group">
                                    <label for="engine">engine</label>
                                    <input type="text" name="engine" value="InnoDB" required="required" />
                                </div>

                                <div class="form-group">
                                    <label for="host">host</label>
                                    <input type="text" name="host" value="localhost" required="required" />
                                </div>

                                <div class="form-group">
                                    <label for="port">port</label>
                                    <input type="text" name="port" value="3306" required="required" />
                                </div>

                                <div class="form-group">
                                    <label for="dbname">database name</label>
                                    <input type="text" name="dbname" required="required" />
                                </div>

                                <div class="form-group">
                                    <label for="user">username</label>
                                    <input type="text" name="user" value="root" required="required" />
                                </div>

                                <div class="form-group">
                                    <label for="password">password</label>
                                    <input type="password" name="password" />
                                </div>

                                <div class="form-group">
                                    <label for="charset">charset</label>
                                    <input type="text" name="charset" value="utf8" required="required" />
                                </div>

                                <div class="form-group">
                                    <label for="collation">collation</label>
                                    <input type="text" name="collation" value="utf8_general_ci" required="required" />
                                </div>

                                <div class="text-right">
                                    <input type="submit" class="btn btn-primary" value="Save it and go to step 4" />
                                </div>
                            </form>
                        </div>

                    <?php elseif (4 === $step): ?>

                        <h2>Step 4 - Site configuration</h2>

                        <div>
                            <form action="" method="POST" role="form">
                                <input type="hidden" name="step" value="4" />

                                <div class="form-group">
                                    <label for="site_name">site_name</label>
                                    <input type="text" name="site_name" required="required" />
                                </div>

                                <div class="form-group">
                                    <label for="domain">domain</label>
                                    <input type="text" name="domain" required="required" />
                                </div>

                                <div class="text-right">
                                    <input type="submit" class="btn btn-primary" value="Save it and complete installation" />
                                </div>
                            </form>
                        </div>

                    <?php elseif (5 === $step): ?>

                        <h2>Installation completed</h2>

                        <p>Example of nginx virtual host:</p>
                        <?php $site = array_shift($sites); ?>
                        <pre>
# example of nginx virtual host for BackBuilder project
server {
    listen 80;

    server_name <?php echo $site['domain']; ?>;
    root <?php echo __DIR__ . '/'; ?>;

    error_log /var/log/nginx/<?php echo \BackBuilder\Util\String::urlize($site['label']); ?>.error.log;
    access_log /var/log/nginx/<?php echo \BackBuilder\Util\String::urlize($site['label']); ?>.access.log;

    location ~ /resources/(.*) {
        alias <?php echo dirname(__DIR__) . '/'; ?>;
        try_files /BackBuilder/Resources/$1 /repository/Resources/$1 break;
    }

    location ~ /(css|fonts|img)/(.*) {
        try_files $uri @rewriteapp;
    }

    location @rewriteapp {
        rewrite ^(.*)$ /index.php last;
    }

    location ~ ^/(config|index)\.php(/|$) {
        fastcgi_pass unix:/var/run/php5-fpm.sock;
        include fastcgi_params;
    }
}</pre>
                    <p>Example of apache2 virtual host:</p>
                    <pre>
# example of apache2 virtual host for BackBuilder project
&lt;VirtualHost *:80&gt;
    ServerName <?php echo $site['domain']; ?>

    DocumentRoot <?php echo __DIR__ . '/'; ?>

    RewriteEngine On

    RewriteCond %{DOCUMENT_ROOT}/../repository/Resources/$1 -f
    RewriteRule ^/resources/(.*)$ %{DOCUMENT_ROOT}/../repository/Resources/$1 [L]

    RewriteCond %{DOCUMENT_ROOT}/../BackBuilder/Resources/$1 -f
    RewriteRule ^/resources/(.*)$ %{DOCUMENT_ROOT}/../BackBuilder/Resources/$1 [L]

    RewriteCond %{DOCUMENT_ROOT}/../repository/Data/Storage/$1/$2.$4 -f
    RewriteRule ^/images/([a-f0-9]{3})/([a-f0-9]{29})/(.*)\.([^\.]+)$ %{DOCUMENT_ROOT}/../repository/Data/Storage/$1/$2.$4 [L]

    RewriteCond %{DOCUMENT_ROOT}/../repository/Data/Media/$1/$2.$4 -f
    RewriteRule ^/images/([a-f0-9]{3})/([a-f0-9]{29})/(.*)\.([^\.]+)$ %{DOCUMENT_ROOT}/../repository/Data/Media/$1/$2.$4 [L]

    RewriteCond %{DOCUMENT_ROOT}/../repository/Data/Storage/$1 -f
    RewriteRule ^images/(.*)$ %{DOCUMENT_ROOT}/../repository/Data/Storage/$1 [L]

    RewriteCond %{DOCUMENT_ROOT}/../repository/Data/Media/$1 -f
    RewriteRule ^images/(.*)$ %{DOCUMENT_ROOT}/../repository/Data/Media/$1 [L]
&lt;/VirtualHost&gt;
                    </pre>

                    <p class="text-center">
                        <a href="<?php echo 1 === preg_match('#^http#', $site['domain']) ? $site['domain'] : 'http://' . $site['domain']; ?>" class="btn btn-success btn-lg" target="_blank">
                            <strong>Runs <?php echo ucfirst($site['label']); ?></strong>
                        </a>
                    </p>

                    <?php else: ?>

                        <div>
                            <h2>Step 1 - Requirements checks</h2>

                            <?php $success = true; ?>
                            <?php foreach ($requirements as $requirement): ?>
                                <div class="alert <?php echo (true === $requirement->isOk() ? 'alert-success' : 'alert-danger'); ?>">
                                    <strong><?php echo $requirement->getTitle(); ?></strong> <?php echo (true === $requirement->isOk() ? 'OK' : $requirement->getErrorMessage()); ?>
                                </div>
                                <?php $success = $success && $requirement->isOk(); ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="text-right">
                            <?php if (true === $success): ?>
                                <form action="" method="POST">
                                    <input type="hidden" name="step" value="2" />
                                    <input type="submit" class="btn btn-primary" value="Go to step 2" />
                                </form>
                            <?php else: ?>
                                <strong>Resolve issues listed above and go to step 2</strong>
                            <?php endif; ?>
                        </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>

    </body>
</html>
