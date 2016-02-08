<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Steps;

use Exception;
use Icinga\Application\Config;
use Icinga\Authentication\UserGroup\DbUserGroupBackend;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\IcingaException;
use Icinga\Module\Setup\Step;

class UserGroupStep extends Step
{
    protected $data;

    protected $groupError;

    protected $memberError;

    protected $groupIniError;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function apply()
    {
        $success = $this->createGroupsIni();
        if (isset($this->data['resourceConfig'])) {
            $success &= $this->createUserGroup();
            if ($success) {
                $success &= $this->createMembership();
            }
        }

        return $success;
    }

    protected function createGroupsIni()
    {
        $config = array();
        if (isset($this->data['groupConfig'])) {
            $backendConfig = $this->data['groupConfig'];
            $backendName = $backendConfig['name'];
            unset($backendConfig['name']);
            $config[$backendName] = $backendConfig;
        } else {
            $backendConfig = array(
                'backend'   => $this->data['backendConfig']['backend'], // "db" or "msldap"
                'resource'  => $this->data['resourceName']
            );

            if ($backendConfig['backend'] === 'msldap') {
                $backendConfig['user_backend'] = $this->data['backendConfig']['name'];
            }

            $config[$this->data['backendConfig']['name']] = $backendConfig;
        }

        try {
            Config::fromArray($config)
                ->setConfigFile(Config::resolvePath('groups.ini'))
                ->saveIni();
        } catch (Exception $e) {
            $this->groupIniError = $e;
            return false;
        }

        $this->groupIniError = false;
        return true;
    }

    protected function createUserGroup()
    {
        try {
            $backend = new DbUserGroupBackend(
                ResourceFactory::createResource(new ConfigObject($this->data['resourceConfig']))
            );

            $groupName = mt('setup', 'Administrators', 'setup.role.name');
            if ($backend->select()->where('group_name', $groupName)->count() === 0) {
                $backend->insert('group', array(
                    'group_name'    => $groupName
                ));
                $this->groupError = false;
            }
        } catch (Exception $e) {
            $this->groupError = $e;
            return false;
        }

        return true;
    }

    protected function createMembership()
    {
        try {
            $backend = new DbUserGroupBackend(
                ResourceFactory::createResource(new ConfigObject($this->data['resourceConfig']))
            );

            $groupName = mt('setup', 'Administrators', 'setup.role.name');
            $userName = $this->data['username'];
            if ($backend
                ->select()
                ->from('group_membership')
                ->where('group_name', $groupName)
                ->where('user_name', $userName)
                ->count() === 0
            ) {
                $backend->insert('group_membership', array(
                    'group_name'    => $groupName,
                    'user_name'     => $userName
                ));
                $this->memberError = false;
            }
        } catch (Exception $e) {
            $this->memberError = $e;
            return false;
        }

        return true;
    }

    public function getSummary()
    {
        if (! isset($this->data['groupConfig'])) {
            return; // It's not necessary to show the user something he didn't configure..
        }

        $pageTitle = '<h2>' . mt('setup', 'User Groups', 'setup.page.title') . '</h2>';
        $backendTitle = '<h3>' . mt('setup', 'User Group Backend', 'setup.page.title') . '</h3>';

        $backendHtml = ''
            . '<table>'
            . '<tbody>'
            . '<tr>'
            . '<td><strong>' . t('Backend Name') . '</strong></td>'
            . '<td>' . $this->data['groupConfig']['name'] . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td><strong>' . mt('setup', 'Group Object Class') . '</strong></td>'
            . '<td>' . $this->data['groupConfig']['group_class'] . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td><strong>' . mt('setup', 'Custom Filter') . '</strong></td>'
            . '<td>' . (trim($this->data['groupConfig']['group_filter']) ?: t('None', 'auth.ldap.filter')) . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td><strong>' . mt('setup', 'Group Name Attribute') . '</strong></td>'
            . '<td>' . $this->data['groupConfig']['group_name_attribute'] . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td><strong>' . mt('setup', 'Group Member Attribute') . '</strong></td>'
            . '<td>' . $this->data['groupConfig']['group_member_attribute'] . '</td>'
            . '</tr>'
            . '</tbody>'
            . '</table>';

        return $pageTitle . '<div class="topic">' . $backendTitle . $backendHtml . '</div>';
    }

    public function getReport()
    {
        $report = array();

        if ($this->groupIniError === false) {
            $report[] = sprintf(
                mt('setup', 'User Group Backend configuration has been successfully written to: %s'),
                Config::resolvePath('groups.ini')
            );
        } elseif ($this->groupIniError !== null) {
            $report[] = sprintf(
                mt('setup', 'User Group Backend configuration could not be written to: %s. An error occured:'),
                Config::resolvePath('groups.ini')
            );
            $report[] = sprintf(mt('setup', 'ERROR: %s'), IcingaException::describe($this->groupIniError));
        }

        if ($this->groupError === false) {
            $report[] = sprintf(
                mt('setup', 'User Group "%s" has been successfully created.'),
                mt('setup', 'Administrators', 'setup.role.name')
            );
        } elseif ($this->groupError !== null) {
            $report[] = sprintf(
                mt('setup', 'Unable to create user group "%s". An error occured:'),
                mt('setup', 'Administrators', 'setup.role.name')
            );
            $report[] = sprintf(mt('setup', 'ERROR: %s'), IcingaException::describe($this->groupError));
        }

        if ($this->memberError === false) {
            $report[] = sprintf(
                mt('setup', 'Account "%s" has been successfully added as member to user group "%s".'),
                $this->data['username'],
                mt('setup', 'Administrators', 'setup.role.name')
            );
        } elseif ($this->memberError !== null) {
            $report[] = sprintf(
                mt('setup', 'Unable to add account "%s" as member to user group "%s". An error occured:'),
                $this->data['username'],
                mt('setup', 'Administrators', 'setup.role.name')
            );
            $report[] = sprintf(mt('setup', 'ERROR: %s'), IcingaException::describe($this->memberError));
        }

        return $report;
    }
}
