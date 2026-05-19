<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Account;

use Icinga\Authentication\User\DbUserBackend;
use Icinga\Data\Filter\Filter;
use Icinga\User;
use Icinga\Web\Form;
use Icinga\Web\Notification;

/**
 * Form for changing user passwords
 */
class ChangePasswordForm extends Form
{
    /**
     * The user backend
     *
     * @var DbUserBackend
     */
    protected $backend;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setSubmitLabel($this->translate('Update Account'));
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'password',
            'old_password',
            [
                'label'         => $this->translate('Old Password'),
                'required'      => true
            ]
        );
        $this->addElement(
            'password',
            'new_password',
            [
                'label'         => $this->translate('New Password'),
                'required'      => true
            ]
        );
        $this->addElement(
            'password',
            'new_password_confirmation',
            [
                'label'         => $this->translate('Confirm New Password'),
                'required'      => true,
                'validators'        => [
                    ['identical', false, ['new_password']]
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess()
    {
        $backend = $this->getBackend();
        $backend->update(
            $backend->getBaseTable(),
            ['password' => $this->getElement('new_password')->getValue()],
            Filter::where('user_name', $this->Auth()->getUser()->getUsername())
        );
        Notification::success($this->translate('Account updated'));
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($formData)
    {
        $valid = parent::isValid($formData);
        if (! $valid) {
            return false;
        }

        $oldPasswordEl = $this->getElement('old_password');

        if (! $this->backend->authenticate($this->Auth()->getUser(), $oldPasswordEl->getValue())) {
            $oldPasswordEl->addError($this->translate('Old password is invalid'));
            $this->markAsError();
            return false;
        }

        return true;
    }

    /**
     * Get the user backend
     *
     * @return  DbUserBackend
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Set the user backend
     *
     * @param   DbUserBackend $backend
     *
     * @return  $this
     */
    public function setBackend(DbUserBackend $backend)
    {
        $this->backend = $backend;
        return $this;
    }
}
