<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Steps;

use Exception;
use PDOException;
use Icinga\Exception\IcingaException;
use Icinga\Module\Setup\Step;
use Icinga\Module\Setup\Utils\DbTool;
use Icinga\Module\Setup\Exception\SetupException;

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
                mt('setup', 'Successfully connected to existing database "%s"...'),
                $this->data['resourceConfig']['dbname']
            );
        } catch (PDOException $_) {
            $db->connectToHost();
            $this->log(mt('setup', 'Creating new database "%s"...'), $this->data['resourceConfig']['dbname']);
            $db->exec('CREATE DATABASE ' . $db->quoteIdentifier($this->data['resourceConfig']['dbname']));
            $db->reconnect($this->data['resourceConfig']['dbname']);
        }

        if (array_search(reset($this->data['tables']), $db->listTables(), true) !== false) {
            $this->log(mt('setup', 'Database schema already exists...'));
        } else {
            $this->log(mt('setup', 'Creating database schema...'));
            $db->import($this->data['schemaPath'] . '/mysql.schema.sql');
        }

        if ($db->hasLogin($this->data['resourceConfig']['username'])) {
            $this->log(mt('setup', 'Login "%s" already exists...'), $this->data['resourceConfig']['username']);
        } else {
            $this->log(mt('setup', 'Creating login "%s"...'), $this->data['resourceConfig']['username']);
            $db->addLogin($this->data['resourceConfig']['username'], $this->data['resourceConfig']['password']);
        }

        $username = $this->data['resourceConfig']['username'];
        if ($db->checkPrivileges($this->data['privileges'], $this->data['tables'], $username)) {
            $this->log(
                mt('setup', 'Required privileges were already granted to login "%s".'),
                $this->data['resourceConfig']['username']
            );
        } else {
            $this->log(
                mt('setup', 'Granting required privileges to login "%s"...'),
                $this->data['resourceConfig']['username']
            );
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
                mt('setup', 'Successfully connected to existing database "%s"...'),
                $this->data['resourceConfig']['dbname']
            );
        } catch (PDOException $_) {
            $db->connectToHost();
            $this->log(mt('setup', 'Creating new database "%s"...'), $this->data['resourceConfig']['dbname']);
            $db->exec(sprintf(
                "CREATE DATABASE %s WITH ENCODING 'UTF-8'",
                $db->quoteIdentifier($this->data['resourceConfig']['dbname'])
            ));
            $db->reconnect($this->data['resourceConfig']['dbname']);
        }

        if (array_search(reset($this->data['tables']), $db->listTables(), true) !== false) {
            $this->log(mt('setup', 'Database schema already exists...'));
        } else {
            $this->log(mt('setup', 'Creating database schema...'));
            $db->import($this->data['schemaPath'] . '/pgsql.schema.sql');
        }

        if ($db->hasLogin($this->data['resourceConfig']['username'])) {
            $this->log(mt('setup', 'Login "%s" already exists...'), $this->data['resourceConfig']['username']);
        } else {
            $this->log(mt('setup', 'Creating login "%s"...'), $this->data['resourceConfig']['username']);
            $db->addLogin($this->data['resourceConfig']['username'], $this->data['resourceConfig']['password']);
        }

        $username = $this->data['resourceConfig']['username'];
        if ($db->checkPrivileges($this->data['privileges'], $this->data['tables'], $username)) {
            $this->log(
                mt('setup', 'Required privileges were already granted to login "%s".'),
                $this->data['resourceConfig']['username']
            );
        } else {
            $this->log(
                mt('setup', 'Granting required privileges to login "%s"...'),
                $this->data['resourceConfig']['username']
            );
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
            if (array_search(reset($this->data['tables']), $db->listTables(), true) === false) {
                if ($resourceConfig['username'] !== $this->data['resourceConfig']['username']) {
                    $message = sprintf(
                        mt(
                            'setup',
                            'The database user "%s" will be used to setup the missing schema required by Icinga'
                            . ' Web 2 in database "%s" and to grant access to it to a new login called "%s".'
                        ),
                        $resourceConfig['username'],
                        $resourceConfig['dbname'],
                        $this->data['resourceConfig']['username']
                    );
                } else {
                    $message = sprintf(
                        mt(
                            'setup',
                            'The database user "%s" will be used to setup the missing'
                            . ' schema required by Icinga Web 2 in database "%s".'
                        ),
                        $resourceConfig['username'],
                        $resourceConfig['dbname']
                    );
                }
            } else {
                $message = sprintf(
                    mt('setup', 'The database "%s" already seems to be fully set up. No action required.'),
                    $resourceConfig['dbname']
                );
            }
        } catch (PDOException $_) {
            try {
                $db->connectToHost();
                if ($resourceConfig['username'] !== $this->data['resourceConfig']['username']) {
                    if ($db->hasLogin($this->data['resourceConfig']['username'])) {
                        $message = sprintf(
                            mt(
                                'setup',
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
                            mt(
                                'setup',
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
                        mt(
                            'setup',
                            'The database user "%s" will be used to create the missing'
                            . ' database "%s" with the schema required by Icinga Web 2.'
                        ),
                        $resourceConfig['username'],
                        $resourceConfig['dbname']
                    );
                }
            } catch (Exception $_) {
                $message = mt(
                    'setup',
                    'No connection to database host possible. You\'ll need to setup the'
                    . ' database with the schema required by Icinga Web 2 manually.'
                );
            }
        }

        return '<h2>' . mt('setup', 'Database Setup', 'setup.page.title') . '</h2><p>' . $message . '</p>';
    }

    public function getReport()
    {
        if ($this->error === false) {
            $report = $this->messages;
            $report[] = mt('setup', 'The database has been fully set up!');
            return $report;
        } elseif ($this->error !== null) {
            $report = $this->messages;
            $report[] = mt('setup', 'Failed to fully setup the database. An error occured:');
            $report[] = sprintf(mt('setup', 'ERROR: %s'), IcingaException::describe($this->error));
            return $report;
        }
    }

    protected function log()
    {
        $this->messages[] = call_user_func_array('sprintf', func_get_args());
    }
}
