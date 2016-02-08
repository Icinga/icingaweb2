<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\UserGroup;

use Exception;
use Icinga\Data\Extensible;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Selectable;
use Icinga\Exception\NotFoundError;
use Icinga\Web\Form;
use Icinga\Web\Notification;

/**
 * Form for adding one or more group members
 */
class AddMemberForm extends Form
{
    /**
     * The data source to fetch users from
     *
     * @var Selectable
     */
    protected $ds;

    /**
     * The user group backend to use
     *
     * @var Extensible
     */
    protected $backend;

    /**
     * The group to add members for
     *
     * @var string
     */
    protected $groupName;

    /**
     * Set the data source to fetch users from
     *
     * @param   Selectable  $ds
     *
     * @return  $this
     */
    public function setDataSource(Selectable $ds)
    {
        $this->ds = $ds;
        return $this;
    }

    /**
     * Set the user group backend to use
     *
     * @param   Extensible  $backend
     *
     * @return  $this
     */
    public function setBackend(Extensible $backend)
    {
        $this->backend = $backend;
        return $this;
    }

    /**
     * Set the group to add members for
     *
     * @param   string  $groupName
     *
     * @return  $this
     */
    public function setGroupName($groupName)
    {
        $this->groupName = $groupName;
        return $this;
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData   The data sent by the user
     */
    public function createElements(array $formData)
    {
        // TODO(jom): Fetching already existing members to prevent the user from mistakenly creating duplicate
        // memberships (no matter whether the data source permits it or not, a member does never need to be
        // added more than once) should be kept at backend level (GroupController::fetchUsers) but this does
        // not work currently as our ldap protocol stuff is unable to handle our filter implementation..
        $members = $this->backend
            ->select()
            ->from('group_membership', array('user_name'))
            ->where('group_name', $this->groupName)
            ->fetchColumn();
        $filter = empty($members) ? Filter::matchAll() : Filter::not(Filter::where('user_name', $members));

        $users = $this->ds->select()->from('user', array('user_name'))->applyFilter($filter)->fetchColumn();
        if (! empty($users)) {
            $this->addElement(
                'multiselect',
                'user_name',
                array(
                    'multiOptions'  => array_combine($users, $users),
                    'label'         => $this->translate('Backend Users'),
                    'description'   => $this->translate(
                        'Select one or more users (fetched from your user backends) to add as group member'
                    ),
                    'class'         => 'grant-permissions'
                )
            );
        }

        $this->addElement(
            'textarea',
            'users',
            array(
                'required'      => empty($users),
                'label'         => $this->translate('Users'),
                'description'   => $this->translate(
                    'Provide one or more usernames separated by comma to add as group member'
                )
            )
        );

        $this->setTitle(sprintf($this->translate('Add members for group %s'), $this->groupName));
        $this->setSubmitLabel($this->translate('Add'));
    }

    /**
     * Insert the members for the group
     *
     * @return  bool
     */
    public function onSuccess()
    {
        $userNames = $this->getValue('user_name') ?: array();
        if (($users = $this->getValue('users'))) {
            $userNames = array_merge($userNames, array_map('trim', explode(',', $users)));
        }

        if (empty($userNames)) {
            $this->info($this->translate(
                'Please provide at least one username, either by choosing one '
                . 'in the list or by manually typing one in the text box below'
            ));
            return false;
        }

        $single = null;
        foreach ($userNames as $userName) {
            try {
                $this->backend->insert(
                    'group_membership',
                    array(
                        'group_name'    => $this->groupName,
                        'user_name'     => $userName
                    )
                );
            } catch (NotFoundError $e) {
                throw $e; // Trigger 404, the group name is initially accessed as GET parameter
            } catch (Exception $e) {
                Notification::error(sprintf(
                    $this->translate('Failed to add "%s" as group member for "%s"'),
                    $userName,
                    $this->groupName
                ));
                $this->error($e->getMessage());
                return false;
            }

            $single = $single === null;
        }

        if ($single) {
            Notification::success(sprintf($this->translate('Group member "%s" added successfully'), $userName));
        } else {
            Notification::success($this->translate('Group members added successfully'));
        }

        return true;
    }
}
