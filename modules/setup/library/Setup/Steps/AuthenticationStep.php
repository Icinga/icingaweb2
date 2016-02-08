<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Steps;

use Exception;
use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\IcingaException;
use Icinga\Authentication\User\DbUserBackend;
use Icinga\Module\Setup\Step;

class AuthenticationStep extends Step
{
    protected $data;

    protected $dbError;

    protected $authIniError;

    protected $permIniError;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function apply()
    {
        $success = $this->createAuthenticationIni();
        if (isset($this->data['adminAccountData']['resourceConfig'])) {
            $success &= $this->createAccount();
        }

        $success &= $this->createRolesIni();
        return $success;
    }

    protected function createAuthenticationIni()
    {
        $config = array();
        $backendConfig = $this->data['backendConfig'];
        $backendName = $backendConfig['name'];
        unset($backendConfig['name']);
        $config[$backendName] = $backendConfig;
        if (isset($this->data['resourceName'])) {
            $config[$backendName]['resource'] = $this->data['resourceName'];
        }

        try {
            Config::fromArray($config)
                ->setConfigFile(Config::resolvePath('authentication.ini'))
                ->saveIni();
        } catch (Exception $e) {
            $this->authIniError = $e;
            return false;
        }

        $this->authIniError = false;
        return true;
    }

    protected function createRolesIni()
    {
        if (isset($this->data['adminAccountData']['username'])) {
            $config = array(
                'users'         => $this->data['adminAccountData']['username'],
                'permissions'   => '*'
            );

            if ($this->data['backendConfig']['backend'] === 'db') {
                $config['groups'] = mt('setup', 'Administrators', 'setup.role.name');
            }
        } else { // isset($this->data['adminAccountData']['groupname'])
            $config = array(
                'groups'        => $this->data['adminAccountData']['groupname'],
                'permissions'   => '*'
            );
        }

        try {
            Config::fromArray(array(mt('setup', 'Administrators', 'setup.role.name') => $config))
                ->setConfigFile(Config::resolvePath('roles.ini'))
                ->saveIni();
        } catch (Exception $e) {
            $this->permIniError = $e;
            return false;
        }

        $this->permIniError = false;
        return true;
    }

    protected function createAccount()
    {
        try {
            $backend = new DbUserBackend(
                ResourceFactory::createResource(new ConfigObject($this->data['adminAccountData']['resourceConfig']))
            );

            if ($backend->select()->where('user_name', $this->data['adminAccountData']['username'])->count() === 0) {
                $backend->insert('user', array(
                    'user_name' => $this->data['adminAccountData']['username'],
                    'password'  => $this->data['adminAccountData']['password'],
                    'is_active' => true
                ));
                $this->dbError = false;
            }
        } catch (Exception $e) {
            $this->dbError = $e;
            return false;
        }

        return true;
    }

    public function getSummary()
    {
        $pageTitle = '<h2>' . mt('setup', 'Authentication', 'setup.page.title') . '</h2>';
        $backendTitle = '<h3>' . mt('setup', 'Authentication Backend', 'setup.page.title') . '</h3>';
        $adminTitle = '<h3>' . mt('setup', 'Administration', 'setup.page.title') . '</h3>';

        $authType = $this->data['backendConfig']['backend'];
        $backendDesc = '<p>' . sprintf(
            mt('setup', 'Users will authenticate using %s.', 'setup.summary.auth'),
            $authType === 'db' ? mt('setup', 'a database', 'setup.summary.auth.type') : (
                $authType === 'ldap' || $authType === 'msldap' ? 'LDAP' : (
                    mt('setup', 'webserver authentication', 'setup.summary.auth.type')
                )
            )
        ) . '</p>';

        $backendHtml = ''
            . '<table>'
            . '<tbody>'
            . '<tr>'
            . '<td><strong>' . t('Backend Name') . '</strong></td>'
            . '<td>' . $this->data['backendConfig']['name'] . '</td>'
            . '</tr>'
            . ($authType === 'ldap' || $authType === 'msldap' ? (
                '<tr>'
                . '<td><strong>' . mt('setup', 'User Object Class') . '</strong></td>'
                . '<td>' . ($authType === 'msldap' ? 'user' : $this->data['backendConfig']['user_class']) . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . mt('setup', 'Custom Filter') . '</strong></td>'
                . '<td>' . (trim($this->data['backendConfig']['filter']) ?: t('None', 'auth.ldap.filter')) . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . mt('setup', 'User Name Attribute') . '</strong></td>'
                . '<td>' . ($authType === 'msldap'
                    ? 'sAMAccountName'
                    : $this->data['backendConfig']['user_name_attribute']) . '</td>'
                . '</tr>'
            ) : ($authType === 'external' ? (
                '<tr>'
                . '<td><strong>' . t('Filter Pattern') . '</strong></td>'
                . '<td>' . $this->data['backendConfig']['strip_username_regexp'] . '</td>'
                . '</tr>'
            ) : ''))
            . '</tbody>'
            . '</table>';

        if (isset($this->data['adminAccountData']['username'])) {
            $adminHtml = '<p>' . (isset($this->data['adminAccountData']['resourceConfig']) ? sprintf(
                mt('setup', 'Administrative rights will initially be granted to a new account called "%s".'),
                $this->data['adminAccountData']['username']
            ) : sprintf(
                mt('setup', 'Administrative rights will initially be granted to an existing account called "%s".'),
                $this->data['adminAccountData']['username']
            )) . '</p>';
        } else { // isset($this->data['adminAccountData']['groupname'])
            $adminHtml = '<p>' . sprintf(
                mt('setup', 'Administrative rights will initially be granted to members of the user group "%s".'),
                $this->data['adminAccountData']['groupname']
            ) . '</p>';
        }

        return $pageTitle . '<div class="topic">' . $backendDesc . $backendTitle . $backendHtml . '</div>'
            . '<div class="topic">' . $adminTitle . $adminHtml . '</div>';
    }

    public function getReport()
    {
        $report = array();

        if ($this->authIniError === false) {
            $report[] = sprintf(
                mt('setup', 'Authentication configuration has been successfully written to: %s'),
                Config::resolvePath('authentication.ini')
            );
        } elseif ($this->authIniError !== null) {
            $report[] = sprintf(
                mt('setup', 'Authentication configuration could not be written to: %s. An error occured:'),
                Config::resolvePath('authentication.ini')
            );
            $report[] = sprintf(mt('setup', 'ERROR: %s'), IcingaException::describe($this->authIniError));
        }

        if ($this->dbError === false) {
            $report[] = sprintf(
                mt('setup', 'Account "%s" has been successfully created.'),
                $this->data['adminAccountData']['username']
            );
        } elseif ($this->dbError !== null) {
            $report[] = sprintf(
                mt('setup', 'Unable to create account "%s". An error occured:'),
                $this->data['adminAccountData']['username']
            );
            $report[] = sprintf(mt('setup', 'ERROR: %s'), IcingaException::describe($this->dbError));
        }

        if ($this->permIniError === false) {
            $report[] = isset($this->data['adminAccountData']['username']) ? sprintf(
                mt('setup', 'Account "%s" has been successfully defined as initial administrator.'),
                $this->data['adminAccountData']['username']
            ) : sprintf(
                mt('setup', 'The members of the user group "%s" were successfully defined as initial administrators.'),
                $this->data['adminAccountData']['groupname']
            );
        } elseif ($this->permIniError !== null) {
            $report[] = isset($this->data['adminAccountData']['username']) ? sprintf(
                mt('setup', 'Unable to define account "%s" as initial administrator. An error occured:'),
                $this->data['adminAccountData']['username']
            ) : sprintf(
                mt(
                    'setup',
                    'Unable to define the members of the user group "%s" as initial administrators. An error occured:'
                ),
                $this->data['adminAccountData']['groupname']
            );
            $report[] = sprintf(mt('setup', 'ERROR: %s'), IcingaException::describe($this->permIniError));
        }

        return $report;
    }
}
