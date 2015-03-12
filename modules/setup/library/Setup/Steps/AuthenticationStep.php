<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Steps;

use Exception;
use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Authentication\Backend\DbUserBackend;
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

        $success &= $this->defineInitialAdmin();
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

    protected function defineInitialAdmin()
    {
        $config = array();
        $config['admins'] = array(
            'users'         => $this->data['adminAccountData']['username'],
            'permissions'   => '*'
        );

        try {
            Config::fromArray($config)
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

            if (array_search($this->data['adminAccountData']['username'], $backend->listUsers()) === false) {
                $backend->addUser(
                    $this->data['adminAccountData']['username'],
                    $this->data['adminAccountData']['password']
                );
            }
        } catch (Exception $e) {
            $this->dbError = $e;
            return false;
        }

        $this->dbError = false;
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
                $authType === 'ldap' ? 'LDAP' : mt('setup', 'webserver authentication', 'setup.summary.auth.type')
            )
        ) . '</p>';

        $backendHtml = ''
            . '<table>'
            . '<tbody>'
            . '<tr>'
            . '<td><strong>' . t('Backend Name') . '</strong></td>'
            . '<td>' . $this->data['backendConfig']['name'] . '</td>'
            . '</tr>'
            . ($authType === 'ldap' ? (
                '<tr>'
                . '<td><strong>' . mt('setup', 'User Object Class') . '</strong></td>'
                . '<td>' . $this->data['backendConfig']['user_class'] . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . mt('setup', 'Custom Filter') . '</strong></td>'
                . '<td>' . trim($this->data['backendConfig']['filter']) ?: t('None', 'auth.ldap.filter') . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . mt('setup', 'User Name Attribute') . '</strong></td>'
                . '<td>' . $this->data['backendConfig']['user_name_attribute'] . '</td>'
                . '</tr>'
            ) : ($authType === 'external' ? (
                '<tr>'
                . '<td><strong>' . t('Filter Pattern') . '</strong></td>'
                . '<td>' . $this->data['backendConfig']['strip_username_regexp'] . '</td>'
                . '</tr>'
            ) : ''))
            . '</tbody>'
            . '</table>';

        $adminHtml = '<p>' . (isset($this->data['adminAccountData']['resourceConfig']) ? sprintf(
            mt('setup', 'Administrative rights will initially be granted to a new account called "%s".'),
            $this->data['adminAccountData']['username']
        ) : sprintf(
            mt('setup', 'Administrative rights will initially be granted to an existing account called "%s".'),
            $this->data['adminAccountData']['username']
        )) . '</p>';

        return $pageTitle . '<div class="topic">' . $backendDesc . $backendTitle . $backendHtml . '</div>'
            . '<div class="topic">' . $adminTitle . $adminHtml . '</div>';
    }

    public function getReport()
    {
        $report = '';
        if ($this->authIniError === false) {
            $message = mt('setup', 'Authentication configuration has been successfully written to: %s');
            $report .= '<p>' . sprintf($message, Config::resolvePath('authentication.ini')) . '</p>';
        } elseif ($this->authIniError !== null) {
            $message = mt('setup', 'Authentication configuration could not be written to: %s; An error occured:');
            $report .= '<p class="error">' . sprintf($message, Config::resolvePath('authentication.ini')) . '</p>'
                . '<p>' . $this->authIniError->getMessage() . '</p>';
        }

        if ($this->dbError === false) {
            $message = mt('setup', 'Account "%s" has been successfully created.');
            $report .= '<p>' . sprintf($message, $this->data['adminAccountData']['username']) . '</p>';
        } elseif ($this->dbError !== null) {
            $message = mt('setup', 'Unable to create account "%s". An error occured:');
            $report .= '<p class="error">' . sprintf($message, $this->data['adminAccountData']['username']) . '</p>'
                . '<p>' . $this->dbError->getMessage() . '</p>';
        }

        if ($this->permIniError === false) {
            $message = mt('setup', 'Account "%s" has been successfully defined as initial administrator.');
            $report .= '<p>' . sprintf($message, $this->data['adminAccountData']['username']) . '</p>';
        } elseif ($this->permIniError !== null) {
            $message = mt('setup', 'Unable to define account "%s" as initial administrator. An error occured:');
            $report .= '<p class="error">' . sprintf($message, $this->data['adminAccountData']['username']) . '</p>'
                . '<p>' . $this->permIniError->getMessage() . '</p>';
        }

        return $report;
    }
}
