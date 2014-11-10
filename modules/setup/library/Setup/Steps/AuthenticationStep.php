<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Setup\Steps;

use Exception;
use Zend_Config;
use Icinga\Application\Config;
use Icinga\File\Ini\IniWriter;
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
            $writer = new IniWriter(array(
                'config'    => new Zend_Config($config),
                'filename'  => Config::resolvePath('authentication.ini'),
                'filemode'  => octdec($this->data['fileMode'])
            ));
            $writer->write();
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
            'permission'    => '*'
        );

        try {
            $writer = new IniWriter(array(
                'config'    => new Zend_Config($config),
                'filename'  => Config::resolvePath('permissions.ini'),
                'filemode'  => octdec($this->data['fileMode'])
            ));
            $writer->write();
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
                ResourceFactory::createResource(new Zend_Config($this->data['adminAccountData']['resourceConfig']))
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
        $pageTitle = '<h2>' . t('Authentication') . '</h2>';
        $backendTitle = '<h3>' . t('Backend Configuration') . '</h3>';
        $adminTitle = '<h3>' . t('Initial Administrative Account') . '</h3>';

        $authType = $this->data['backendConfig']['backend'];
        $backendDesc = '<p>' . sprintf(
            t('Users will authenticate using %s.', 'setup.summary.auth'),
            $authType === 'db' ? t('a database', 'setup.summary.auth.type') : (
                $authType === 'ldap' ? 'LDAP' : t('webserver authentication', 'setup.summary.auth.type')
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
                . '<td><strong>' . t('User Object Class') . '</strong></td>'
                . '<td>' . $this->data['backendConfig']['user_class'] . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . t('User Name Attribute') . '</strong></td>'
                . '<td>' . $this->data['backendConfig']['user_name_attribute'] . '</td>'
                . '</tr>'
            ) : ($authType === 'autologin' ? (
                '<tr>'
                . '<td><strong>' . t('Filter Pattern') . '</strong></td>'
                . '<td>' . $this->data['backendConfig']['strip_username_regexp'] . '</td>'
                . '</tr>'
            ) : ''))
            . '</tbody>'
            . '</table>';

        $adminHtml = '<p>' . (isset($this->data['adminAccountData']['resourceConfig']) ? sprintf(
            t('Administrative rights will initially be granted to a new account called "%s".'),
            $this->data['adminAccountData']['username']
        ) : sprintf(
            t('Administrative rights will initially be granted to an existing account called "%s".'),
            $this->data['adminAccountData']['username']
        )) . '</p>';

        return $pageTitle . '<div class="topic">' . $backendDesc . $backendTitle . $backendHtml . '</div>'
            . '<div class="topic">' . $adminTitle . $adminHtml . '</div>';
    }

    public function getReport()
    {
        $report = '';
        if ($this->authIniError === false) {
            $message = t('Authentication configuration has been successfully written to: %s');
            $report .= '<p>' . sprintf($message, Config::resolvePath('authentication.ini')) . '</p>';
        } elseif ($this->authIniError !== null) {
            $message = t('Authentication configuration could not be written to: %s; An error occured:');
            $report .= '<p class="error">' . sprintf($message, Config::resolvePath('authentication.ini')) . '</p>'
                . '<p>' . $this->authIniError->getMessage() . '</p>';
        }

        if ($this->dbError === false) {
            $message = t('Account "%s" has been successfully created.');
            $report .= '<p>' . sprintf($message, $this->data['adminAccountData']['username']) . '</p>';
        } elseif ($this->dbError !== null) {
            $message = t('Unable to create account "%s". An error occured:');
            $report .= '<p class="error">' . sprintf($message, $this->data['adminAccountData']['username']) . '</p>'
                . '<p>' . $this->dbError->getMessage() . '</p>';
        }

        if ($this->permIniError === false) {
            $message = t('Account "%s" has been successfully defined as initial administrator.');
            $report .= '<p>' . sprintf($message, $this->data['adminAccountData']['username']) . '</p>';
        } elseif ($this->permIniError !== null) {
            $message = t('Unable to define account "%s" as initial administrator. An error occured:');
            $report .= '<p class="error">' . sprintf($message, $this->data['adminAccountData']['username']) . '</p>'
                . '<p>' . $this->permIniError->getMessage() . '</p>';
        }

        return $report;
    }
}
