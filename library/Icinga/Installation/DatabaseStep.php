<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Installation;

use Exception;
use PDOException;
use Icinga\Web\Setup\Step;
use Icinga\Web\Setup\DbTool;
use Icinga\Application\Icinga;
use Icinga\Exception\SetupException;

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
            throw new SetupException();
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
        } catch (PDOException $_) {
            $db->connectToHost();
            $this->log(t('Creating new database "%s"...'), $this->data['resourceConfig']['dbname']);
            $db->exec('CREATE DATABASE ' . $db->quoteIdentifier($this->data['resourceConfig']['dbname']));
            $db->reconnect($this->data['resourceConfig']['dbname']);
        }

        if (array_search(key($this->data['tables']), $db->listTables()) !== false) {
            $this->log(t('Database schema already exists...'));
        } else {
            $this->log(t('Creating database schema...'));
            $db->import(Icinga::app()->getApplicationDir() . '/../etc/schema/mysql.schema.sql');
        }

        if ($db->hasLogin($this->data['resourceConfig']['username'])) {
            $this->log(t('Login "%s" already exists...'), $this->data['resourceConfig']['username']);
        } else {
            $this->log(t('Creating login "%s"...'), $this->data['resourceConfig']['username']);
            $db->addLogin($this->data['resourceConfig']['username'], $this->data['resourceConfig']['password']);
        }

        $username = $this->data['resourceConfig']['username'];
        if ($db->checkPrivileges($this->data['privileges'], $this->data['tables'], $username)) {
            $this->log(
                t('Required privileges were already granted to login "%s".'),
                $this->data['resourceConfig']['username']
            );
        } else {
            $this->log(t('Granting required privileges to login "%s"...'), $this->data['resourceConfig']['username']);
            $db->grantPrivileges(
                $this->data['privileges'],
                $this->data['tables'],
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
        } catch (PDOException $_) {
            $db->connectToHost();
            $this->log(t('Creating new database "%s"...'), $this->data['resourceConfig']['dbname']);
            $db->exec(sprintf(
                "CREATE DATABASE %s WITH ENCODING 'UTF-8'",
                $db->quoteIdentifier($this->data['resourceConfig']['dbname'])
            ));
            $db->reconnect($this->data['resourceConfig']['dbname']);
        }

        if (array_search(key($this->data['tables']), $db->listTables()) !== false) {
            $this->log(t('Database schema already exists...'));
        } else {
            $this->log(t('Creating database schema...'));
            $db->import(Icinga::app()->getApplicationDir() . '/../etc/schema/pgsql.schema.sql');
        }

        if ($db->hasLogin($this->data['resourceConfig']['username'])) {
            $this->log(t('Login "%s" already exists...'), $this->data['resourceConfig']['username']);
        } else {
            $this->log(t('Creating login "%s"...'), $this->data['resourceConfig']['username']);
            $db->addLogin($this->data['resourceConfig']['username'], $this->data['resourceConfig']['password']);
        }

        $username = $this->data['resourceConfig']['username'];
        if ($db->checkPrivileges($this->data['privileges'], $this->data['tables'], $username)) {
            $this->log(
                t('Required privileges were already granted to login "%s".'),
                $this->data['resourceConfig']['username']
            );
        } else {
            $this->log(t('Granting required privileges to login "%s"...'), $this->data['resourceConfig']['username']);
            $db->grantPrivileges(
                $this->data['privileges'],
                $this->data['tables'],
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
            if (array_search(key($this->data['tables']), $db->listTables()) === false) {
                if ($resourceConfig['username'] !== $this->data['resourceConfig']['username']) {
                    $message = sprintf(
                        t(
                            'The database user "%s" will be used to setup the missing schema required by Icinga'
                            . ' Web 2 in database "%s" and to grant access to it to a new login called "%s".'
                        ),
                        $resourceConfig['username'],
                        $resourceConfig['dbname'],
                        $this->data['resourceConfig']['username']
                    );
                } else {
                    $message = sprintf(
                        t(
                            'The database user "%s" will be used to setup the missing'
                            . ' schema required by Icinga Web 2 in database "%s".'
                        ),
                        $resourceConfig['username'],
                        $resourceConfig['dbname']
                    );
                }
            } else {
                $message = sprintf(
                    t('The database "%s" already seems to be fully set up. No action required.'),
                    $resourceConfig['dbname']
                );
            }
        } catch (PDOException $_) {
            try {
                $db->connectToHost();
                if ($resourceConfig['username'] !== $this->data['resourceConfig']['username']) {
                    if ($db->hasLogin($this->data['resourceConfig']['username'])) {
                        $message = sprintf(
                            t(
                                'The database user "%s" will be used to create the missing database'
                                . ' "%s" with the schema required by Icinga Web 2 and to grant'
                                . ' access to it to an existing login called "%s".'
                            ),
                            $resourceConfig['username'],
                            $resourceConfig['dbname'],
                            $this->data['resourceConfig']['username']
                        );
                    } else {
                        $message = sprintf(
                            t(
                                'The database user "%s" will be used to create the missing database'
                                . ' "%s" with the schema required by Icinga Web 2 and to grant'
                                . ' access to it to a new login called "%s".'
                            ),
                            $resourceConfig['username'],
                            $resourceConfig['dbname'],
                            $this->data['resourceConfig']['username']
                        );
                    }
                } else {
                    $message = sprintf(
                        t(
                            'The database user "%s" will be used to create the missing'
                            . ' database "%s" with the schema required by Icinga Web 2.'
                        ),
                        $resourceConfig['username'],
                        $resourceConfig['dbname']
                    );
                }
            } catch (Exception $_) {
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
    }

    protected function log()
    {
        $this->messages[] = call_user_func_array('sprintf', func_get_args());
    }
}
