<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application\Installation;

use Exception;
use PDOException;
use Icinga\Web\Setup\Step;
use Icinga\Web\Setup\DbTool;
use Icinga\Application\Icinga;
use Icinga\Application\Platform;
use Icinga\Exception\InstallException;

class DatabaseStep extends Step
{
    protected $data;

    protected $error;

    protected $messages;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->messages = array();
    }

    public function apply()
    {
        $resourceConfig = $this->data['resourceConfig'];
        if (isset($this->data['adminName'])) {
            $resourceConfig['username'] = $this->data['adminName'];
            if (isset($this->data['adminPassword'])) {
                $resourceConfig['password'] = $this->data['adminPassword'];
            }
        }

        $db = new DbTool($resourceConfig);

        try {
            if ($resourceConfig['db'] === 'mysql') {
                $this->setupMysqlDatabase($db);
            } elseif ($resourceConfig['db'] === 'pgsql') {
                $this->setupPgsqlDatabase($db);
            }
        } catch (Exception $e) {
            $this->error = $e;
            throw new InstallException();
        }

        $this->error = false;
        return true;
    }

    protected function setupMysqlDatabase(DbTool $db)
    {
        try {
            $db->connectToDb();
            $this->log(
                t('Successfully connected to existing database "%s"...'),
                $this->data['resourceConfig']['dbname']
            );
        } catch (PDOException $e) {
            $db->connectToHost();
            $this->log(t('Creating new database "%s"...'), $this->data['resourceConfig']['dbname']);
            $db->exec('CREATE DATABASE ' . $db->quoteIdentifier($this->data['resourceConfig']['dbname']));
            $db->reconnect($this->data['resourceConfig']['dbname']);
        }

        if ($db->hasLogin($this->data['resourceConfig']['username'])) {
            $this->log(t('Login "%s" already exists...'), $this->data['resourceConfig']['username']);
        } else {
            $this->log(t('Creating login "%s"...'), $this->data['resourceConfig']['username']);
            $db->addLogin($this->data['resourceConfig']['username'], $this->data['resourceConfig']['password']);
        }

        if (array_search('account', $db->listTables()) !== false) {
            $this->log(t('Database schema already exists...'));
        } else {
            $this->log(t('Creating database schema...'));
            $db->import(Icinga::app()->getApplicationDir() . '/../etc/schema/mysql.sql');
        }

        $privileges = array('SELECT', 'INSERT', 'UPDATE', 'DELETE', 'EXECUTE', 'CREATE TEMPORARY TABLES');
        if ($db->checkPrivileges(array_merge($privileges, array('GRANT OPTION')))) {
            $this->log(t('Granting required privileges to login "%s"...'), $this->data['resourceConfig']['username']);
            $db->exec(sprintf(
                "GRANT %s ON %s.* TO %s@%s",
                join(',', $privileges),
                $db->quoteIdentifier($this->data['resourceConfig']['dbname']),
                $db->quoteIdentifier($this->data['resourceConfig']['username']),
                $db->quoteIdentifier(Platform::getFqdn())
            ));
        } else {
            $this->log(
                t('Required privileges were already granted to login "%s".'),
                $this->data['resourceConfig']['username']
            );
        }
    }

    protected function setupPgsqlDatabase(DbTool $db)
    {
        try {
            $db->connectToDb();
            $this->log(
                t('Successfully connected to existing database "%s"...'),
                $this->data['resourceConfig']['dbname']
            );
        } catch (PDOException $e) {
            $db->connectToHost();
            $this->log(t('Creating new database "%s"...'), $this->data['resourceConfig']['dbname']);
            $db->exec('CREATE DATABASE ' . $db->quoteIdentifier($this->data['resourceConfig']['dbname']));
            $db->reconnect($this->data['resourceConfig']['dbname']);
        }

        if ($db->hasLogin($this->data['resourceConfig']['username'])) {
            $this->log(t('Login "%s" already exists...'), $this->data['resourceConfig']['username']);
        } else {
            $this->log(t('Creating login "%s"...'), $this->data['resourceConfig']['username']);
            $db->addLogin($this->data['resourceConfig']['username'], $this->data['resourceConfig']['password']);
        }

        if (array_search('account', $db->listTables()) !== false) {
            $this->log(t('Database schema already exists...'));
        } else {
            $this->log(t('Creating database schema...'));
            $db->import(Icinga::app()->getApplicationDir() . '/../etc/schema/pgsql.sql');
        }

        $privileges = array('SELECT', 'INSERT', 'UPDATE', 'DELETE');
        if ($db->checkPrivileges(array_merge($privileges, array('GRANT OPTION')))) {
            $this->log(t('Granting required privileges to login "%s"...'), $this->data['resourceConfig']['username']);
            $db->exec(sprintf(
                "GRANT %s ON TABLE account TO %s",
                join(',', $privileges),
                $db->quoteIdentifier($this->data['resourceConfig']['username'])
            ));
            $db->exec(sprintf(
                "GRANT %s ON TABLE preference TO %s",
                join(',', $privileges),
                $db->quoteIdentifier($this->data['resourceConfig']['username'])
            ));
        } else {
            $this->log(
                t('Required privileges were already granted to login "%s".'),
                $this->data['resourceConfig']['username']
            );
        }
    }

    public function getSummary()
    {
        $resourceConfig = $this->data['resourceConfig'];
        if (isset($this->data['adminName'])) {
            $resourceConfig['username'] = $this->data['adminName'];
            if (isset($this->data['adminPassword'])) {
                $resourceConfig['password'] = $this->data['adminPassword'];
            }
        }

        $db = new DbTool($resourceConfig);

        try {
            $db->connectToDb();
            if (array_search('account', $db->listTables()) === false) {
                $message = sprintf(
                    t(
                        'The database user "%s" will be used to setup the missing'
                        . ' schema required by Icinga Web 2 in database "%s".'
                    ),
                    $resourceConfig['username'],
                    $resourceConfig['dbname']
                );
            } else {
                $message = sprintf(
                    t('The database "%s" already seems to be fully set up. No action required.'),
                    $resourceConfig['dbname']
                );
            }
        } catch (PDOException $e) {
            try {
                $db->connectToHost();
                if ($db->hasLogin($this->data['resourceConfig']['username'])) {
                    $message = sprintf(
                        t(
                            'The database user "%s" will be used to create the missing '
                            . 'database "%s" with the schema required by Icinga Web 2.'
                        ),
                        $resourceConfig['username'],
                        $resourceConfig['dbname']
                    );
                } else {
                    $message = sprintf(
                        t(
                            'The database user "%s" will be used to create the missing database "%s" '
                            . 'with the schema required by Icinga Web 2 and a new login called "%s".'
                        ),
                        $resourceConfig['username'],
                        $resourceConfig['dbname'],
                        $this->data['resourceConfig']['username']
                    );
                }
            } catch (Exception $ex) {
                $message = t(
                    'No connection to database host possible. You\'ll need to setup the'
                    . ' database with the schema required by Icinga Web 2 manually.'
                );
            }
        }

        return '<h2>' . t('Database Setup') . '</h2><p>' . $message . '</p>';
    }

    public function getReport()
    {
        if ($this->error === false) {
            return '<p>' . join('</p><p>', $this->messages) . '</p>'
                . '<p>' . t('The database has been fully set up!') . '</p>';
        } elseif ($this->error !== null) {
            $message = t('Failed to fully setup the database. An error occured:');
            return '<p>' . join('</p><p>', $this->messages) . '</p>'
                . '<p class="error">' . $message . '</p><p>' . $this->error->getMessage() . '</p>';
        }

        return '';
    }

    protected function log()
    {
        $this->messages[] = call_user_func_array('sprintf', func_get_args());
    }
}
