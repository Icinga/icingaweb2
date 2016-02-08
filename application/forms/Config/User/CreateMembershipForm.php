<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\User;

use Exception;
use Icinga\Application\Logger;
use Icinga\Data\DataArray\ArrayDatasource;
use Icinga\Web\Form;
use Icinga\Web\Notification;

/**
 * Form for creating one or more group memberships
 */
class CreateMembershipForm extends Form
{
    /**
     * The user group backends to fetch groups from
     *
     * Each backend must implement the Icinga\Data\Extensible and Icinga\Data\Selectable interface.
     *
     * @var array
     */
    protected $backends;

    /**
     * The username to create memberships for
     *
     * @var string
     */
    protected $userName;

    /**
     * Set the user group backends to fetch groups from
     *
     * @param   array   $backends
     *
     * @return  $this
     */
    public function setBackends($backends)
    {
        $this->backends = $backends;
        return $this;
    }

    /**
     * Set the username to create memberships for
     *
     * @param   string  $userName
     *
     * @return  $this
     */
    public function setUsername($userName)
    {
        $this->userName = $userName;
        return $this;
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData   The data sent by the user
     */
    public function createElements(array $formData)
    {
        $query = $this->createDataSource()->select()->from('group', array('group_name', 'backend_name'));

        $options = array();
        foreach ($query as $row) {
            $options[$row->backend_name . ';' . $row->group_name] = $row->group_name . ' (' . $row->backend_name . ')';
        }

        $this->addElement(
            'multiselect',
            'groups',
            array(
                'required'      => true,
                'multiOptions'  => $options,
                'label'         => $this->translate('Groups'),
                'description'   => sprintf(
                    $this->translate('Select one or more groups where to add %s as member'),
                    $this->userName
                ),
                'class'         => 'grant-permissions'
            )
        );

        $this->setTitle(sprintf($this->translate('Create memberships for %s'), $this->userName));
        $this->setSubmitLabel($this->translate('Create'));
    }

    /**
     * Instantly redirect back in case the user is already a member of all groups
     */
    public function onRequest()
    {
        if ($this->createDataSource()->select()->from('group')->count() === 0) {
            Notification::info(sprintf($this->translate('User %s is already a member of all groups'), $this->userName));
            $this->getResponse()->redirectAndExit($this->getRedirectUrl());
        }
    }

    /**
     * Create the memberships for the user
     *
     * @return  bool
     */
    public function onSuccess()
    {
        $backendMap = array();
        foreach ($this->backends as $backend) {
            $backendMap[$backend->getName()] = $backend;
        }

        $single = null;
        foreach ($this->getValue('groups') as $backendAndGroup) {
            list($backendName, $groupName) = explode(';', $backendAndGroup, 2);
            try {
                $backendMap[$backendName]->insert(
                    'group_membership',
                    array(
                        'group_name'    => $groupName,
                        'user_name'     => $this->userName
                    )
                );
            } catch (Exception $e) {
                Notification::error(sprintf(
                    $this->translate('Failed to add "%s" as group member for "%s"'),
                    $this->userName,
                    $groupName
                ));
                $this->error($e->getMessage());
                return false;
            }

            $single = $single === null;
        }

        if ($single) {
            Notification::success(
                sprintf($this->translate('Membership for group %s created successfully'), $groupName)
            );
        } else {
            Notification::success($this->translate('Memberships created successfully'));
        }

        return true;
    }

    /**
     * Create and return a data source to fetch all groups from all backends where the user is not already a member of
     *
     * @return  ArrayDatasource
     */
    protected function createDataSource()
    {
        $groups = $failures = array();
        foreach ($this->backends as $backend) {
            try {
                $memberships = $backend
                    ->select()
                    ->from('group_membership', array('group_name'))
                    ->where('user_name', $this->userName)
                    ->fetchColumn();
                foreach ($backend->select(array('group_name')) as $row) {
                    if (! in_array($row->group_name, $memberships)) { // TODO(jom): Apply this as native query filter
                        $row->backend_name = $backend->getName();
                        $groups[] = $row;
                    }
                }
            } catch (Exception $e) {
                $failures[] = array($backend->getName(), $e);
            }
        }

        if (empty($groups) && !empty($failures)) {
            // In case there are only failures, throw the very first exception again
            throw $failures[0][1];
        } elseif (! empty($failures)) {
            foreach ($failures as $failure) {
                Logger::error($failure[1]);
                Notification::warning(sprintf(
                    $this->translate('Failed to fetch any groups from backend %s. Please check your log'),
                    $failure[0]
                ));
            }
        }

        return new ArrayDatasource($groups);
    }
}
