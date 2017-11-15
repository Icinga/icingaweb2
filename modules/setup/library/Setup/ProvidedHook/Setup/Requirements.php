<?php

namespace Icinga\Module\Setup\ProvidedHook\Setup;

use Icinga\Application\Icinga;
use Icinga\Module\Setup\Hook\RequirementsHook;
use Icinga\Module\Setup\Requirement\OSRequirement;
use Icinga\Module\Setup\Requirement\ClassRequirement;
use Icinga\Module\Setup\Requirement\PhpConfigRequirement;
use Icinga\Module\Setup\Requirement\PhpModuleRequirement;
use Icinga\Module\Setup\Requirement\PhpVersionRequirement;
use Icinga\Module\Setup\Requirement\ConfigDirectoryRequirement;
use Icinga\Module\Setup\RequirementSet;

class Requirements extends RequirementsHook
{
    const MIN_PHP_VERSION = '5.3.2';

    protected static $SETS = array(
        'base',
        'mysql',
        'pgsql',
    );

    public function base()
    {
        $set = new RequirementSet();

        $set->add(new PhpVersionRequirement(array(
            'condition'     => array('>=', self::MIN_PHP_VERSION),
            'description'   => sprintf(mt(
                'setup',
                'Running Icinga Web 2 requires PHP version %s.'
            ), self::MIN_PHP_VERSION)
        )));

        $set->add(new PhpConfigRequirement(array(
            'condition'     => array('date.timezone', true),
            'title'         => mt('setup', 'Default Timezone'),
            'description'   => sprintf(
                mt('setup', 'It is required that a default timezone has been set using date.timezone in %s.'),
                php_ini_loaded_file() ?: 'php.ini'
            ),
        )));

        $set->add(new OSRequirement(array(
            'optional'      => true,
            'condition'     => 'linux',
            'description'   => mt(
                'setup',
                'Icinga Web 2 is developed for and tested on Linux. While we cannot'
                . ' guarantee they will, other platforms may also perform as well.'
            )
        )));

        $set->add(new ConfigDirectoryRequirement(array(
            'condition'     => Icinga::app()->getConfigDir(),
            'description'   => mt(
                'setup',
                'The Icinga Web 2 configuration directory defaults to "/etc/icingaweb2", if' .
                ' not explicitly set in the environment variable "ICINGAWEB_CONFIGDIR".'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'condition'     => 'OpenSSL',
            'description'   => mt(
                'setup',
                'The PHP module for OpenSSL is required to generate cryptographically safe password salts.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'JSON',
            'description'   => mt(
                'setup',
                'The JSON module for PHP is required for various export functionalities as well as APIs.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'LDAP',
            'description'   => mt(
                'setup',
                'If you\'d like to authenticate users using LDAP the corresponding PHP module is required.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'INTL',
            'description'   => mt(
                'setup',
                'If you want your users to benefit from language, timezone and date/time'
                . ' format negotiation, the INTL module for PHP is required.'
            )
        )));

        // TODO(6172): Remove this requirement once we do not ship dompdf with Icinga Web 2 anymore
        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'DOM',
            'description'   => mt(
                'setup',
                'To be able to export views and reports to PDF, the DOM module for PHP is required.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'GD',
            'description'   => mt(
                'setup',
                'In case you want views being exported to PDF, you\'ll need the GD extension for PHP.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'Imagick',
            'description'   => mt(
                'setup',
                'In case you want graphs being exported to PDF as well, you\'ll need the ImageMagick extension for PHP.'
            )
        )));

        return $set;
    }

    public function mysql()
    {
        $set = new RequirementSet(true);

        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'pdo_mysql',
            'alias'         => 'PDO-MySQL',
            'description'   => mt(
                'setup',
                'To store users or preferences in a MySQL database the PDO-MySQL module for PHP is required.'
            )
        )));

        $set->add(new ClassRequirement(array(
            'optional'      => true,
            'condition'     => 'Zend_Db_Adapter_Pdo_Mysql',
            'alias'         => mt('setup', 'Zend database adapter for MySQL'),
            'description'   => mt(
                'setup',
                'The Zend database adapter for MySQL is required to access a MySQL database.'
            ),
            'textAvailable' => mt(
                'setup',
                'The Zend database adapter for MySQL is available.',
                'setup.requirement.class'
            ),
            'textMissing'   => mt(
                'setup',
                'The Zend database adapter for MySQL is missing.',
                'setup.requirement.class'
            )
        )));

        return $set;
    }

    public function pgsql()
    {
        $set = new RequirementSet(true);
        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'pdo_pgsql',
            'alias'         => 'PDO-PostgreSQL',
            'description'   => mt(
                'setup',
                'To store users or preferences in a PostgreSQL database the PDO-PostgreSQL module for PHP is required.'
            )
        )));
        $set->add(new ClassRequirement(array(
            'optional'      => true,
            'condition'     => 'Zend_Db_Adapter_Pdo_Pgsql',
            'alias'         => mt('setup', 'Zend database adapter for PostgreSQL'),
            'description'   => mt(
                'setup',
                'The Zend database adapter for PostgreSQL is required to access a PostgreSQL database.'
            ),
            'textAvailable' => mt(
                'setup',
                'The Zend database adapter for PostgreSQL is available.',
                'setup.requirement.class'
            ),
            'textMissing'   => mt(
                'setup',
                'The Zend database adapter for PostgreSQL is missing.',
                'setup.requirement.class'
            )
        )));

        return $set;
    }

    public function getRequirements()
    {
        $set = new RequirementSet();

        foreach (static::$SETS as $name) {
            $set->merge($this->$name());
        }

        return $set;
    }
}
