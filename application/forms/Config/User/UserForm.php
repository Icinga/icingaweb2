<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\User;

use Icinga\Data\Filter\Filter;
use Icinga\Forms\RepositoryForm;

class UserForm extends RepositoryForm
{
    /**
     * Create and add elements to this form to insert or update a user
     *
     * @param   array   $formData   The data sent by the user
     */
    protected function createInsertElements(array $formData)
    {
        $this->addElement(
            'checkbox',
            'is_active',
            array(
                'required'      => true,
                'value'         => true,
                'label'         => $this->translate('Active'),
                'description'   => $this->translate('Prevents the user from logging in if unchecked')
            )
        );
        $this->addElement(
            'text',
            'user_name',
            array(
                'required'  => true,
                'label'     => $this->translate('Username')
            )
        );
        $this->addElement(
            'password',
            'password',
            array(
                'required'  => true,
                'label'     => $this->translate('Password')
            )
        );

        $this->setTitle($this->translate('Add a new user'));
        $this->setSubmitLabel($this->translate('Add'));
    }

    /**
     * Create and add elements to this form to update a user
     *
     * @param   array   $formData   The data sent by the user
     */
    protected function createUpdateElements(array $formData)
    {
        $this->createInsertElements($formData);

        $this->addElement(
            'password',
            'password',
            array(
                'description'   => $this->translate('Leave empty for not updating the user\'s password'),
                'label'         => $this->translate('Password'),
            )
        );

        $this->setTitle(sprintf($this->translate('Edit user %s'), $this->getIdentifier()));
        $this->setSubmitLabel($this->translate('Save'));
    }

    /**
     * Update a user
     *
     * @return  bool
     */
    protected function onUpdateSuccess()
    {
        if (parent::onUpdateSuccess()) {
            if (($newName = $this->getValue('user_name')) !== $this->getIdentifier()) {
                $this->getRedirectUrl()->setParam('user', $newName);
            }

            return true;
        }

        return false;
    }

    /**
     * Retrieve all form element values
     *
     * Strips off the password if null or the empty string.
     *
     * @param   bool    $suppressArrayNotation
     *
     * @return  array
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues($suppressArrayNotation);
        if (! $values['password']) {
            unset($values['password']);
        }

        return $values;
    }

    /**
     * Create and add elements to this form to delete a user
     *
     * @param   array   $formData   The data sent by the user
     */
    protected function createDeleteElements(array $formData)
    {
        $this->setTitle(sprintf($this->translate('Remove user %s?'), $this->getIdentifier()));
        $this->setSubmitLabel($this->translate('Yes'));
    }

    /**
     * Create and return a filter to use when updating or deleting a user
     *
     * @return  Filter
     */
    protected function createFilter()
    {
        return Filter::where('user_name', $this->getIdentifier());
    }

    /**
     * Return a notification message to use when inserting a user
     *
     * @param   bool    $success    true or false, whether the operation was successful
     *
     * @return  string
     */
    protected function getInsertMessage($success)
    {
        if ($success) {
            return $this->translate('User added successfully');
        } else {
            return $this->translate('Failed to add user');
        }
    }

    /**
     * Return a notification message to use when updating a user
     *
     * @param   bool    $success    true or false, whether the operation was successful
     *
     * @return  string
     */
    protected function getUpdateMessage($success)
    {
        if ($success) {
            return sprintf($this->translate('User "%s" has been edited'), $this->getIdentifier());
        } else {
            return sprintf($this->translate('Failed to edit user "%s"'), $this->getIdentifier());
        }
    }

    /**
     * Return a notification message to use when deleting a user
     *
     * @param   bool    $success    true or false, whether the operation was successful
     *
     * @return  string
     */
    protected function getDeleteMessage($success)
    {
        if ($success) {
            return sprintf($this->translate('User "%s" has been removed'), $this->getIdentifier());
        } else {
            return sprintf($this->translate('Failed to remove user "%s"'), $this->getIdentifier());
        }
    }
}
