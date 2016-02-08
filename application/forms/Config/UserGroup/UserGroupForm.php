<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\UserGroup;

use Icinga\Data\Filter\Filter;
use Icinga\Forms\RepositoryForm;

class UserGroupForm extends RepositoryForm
{
    /**
     * Create and add elements to this form to insert or update a group
     *
     * @param   array   $formData   The data sent by the user
     */
    protected function createInsertElements(array $formData)
    {
        $this->addElement(
            'text',
            'group_name',
            array(
                'required'  => true,
                'label'     => $this->translate('Group Name')
            )
        );

        if ($this->shouldInsert()) {
            $this->setTitle($this->translate('Add a new group'));
            $this->setSubmitLabel($this->translate('Add'));
        } else { // $this->shouldUpdate()
            $this->setTitle(sprintf($this->translate('Edit group %s'), $this->getIdentifier()));
            $this->setSubmitLabel($this->translate('Save'));
        }
    }

    /**
     * Update a group
     *
     * @return  bool
     */
    protected function onUpdateSuccess()
    {
        if (parent::onUpdateSuccess()) {
            if (($newName = $this->getValue('group_name')) !== $this->getIdentifier()) {
                $this->getRedirectUrl()->setParam('group', $newName);
            }

            return true;
        }

        return false;
    }

    /**
     * Create and add elements to this form to delete a group
     *
     * @param   array   $formData   The data sent by the user
     */
    protected function createDeleteElements(array $formData)
    {
        $this->setTitle(sprintf($this->translate('Remove group %s?'), $this->getIdentifier()));
        $this->addDescription($this->translate(
            'Note that all users that are currently a member of this group will'
            . ' have their membership cleared automatically.'
        ));
        $this->setSubmitLabel($this->translate('Yes'));
    }

    /**
     * Create and return a filter to use when updating or deleting a group
     *
     * @return  Filter
     */
    protected function createFilter()
    {
        return Filter::where('group_name', $this->getIdentifier());
    }

    /**
     * Return a notification message to use when inserting a group
     *
     * @param   bool    $success    true or false, whether the operation was successful
     *
     * @return  string
     */
    protected function getInsertMessage($success)
    {
        if ($success) {
            return $this->translate('Group added successfully');
        } else {
            return $this->translate('Failed to add group');
        }
    }

    /**
     * Return a notification message to use when updating a group
     *
     * @param   bool    $success    true or false, whether the operation was successful
     *
     * @return  string
     */
    protected function getUpdateMessage($success)
    {
        if ($success) {
            return sprintf($this->translate('Group "%s" has been edited'), $this->getIdentifier());
        } else {
            return sprintf($this->translate('Failed to edit group "%s"'), $this->getIdentifier());
        }
    }

    /**
     * Return a notification message to use when deleting a group
     *
     * @param   bool    $success    true or false, whether the operation was successful
     *
     * @return  string
     */
    protected function getDeleteMessage($success)
    {
        if ($success) {
            return sprintf($this->translate('Group "%s" has been removed'), $this->getIdentifier());
        } else {
            return sprintf($this->translate('Failed to remove group "%s"'), $this->getIdentifier());
        }
    }
}
