<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Account;

use Icinga\Authentication\Auth;
use Icinga\Authentication\PasswordPolicyHelper;
use Icinga\Authentication\User\DbUserBackend;
use Icinga\Data\Filter\Filter;
use Icinga\Web\Notification;
use ipl\Validator\CallbackValidator;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;
use SensitiveParameter;

/**
 * Form for changing user passwords
 */
class ChangePasswordForm extends CompatForm
{
    use CsrfCounterMeasure;
    use FormUid;

    public const OLD_PASSWORD_ELEMENT_NAME = 'old_password';

    public const NEW_PASSWORD_ELEMENT_NAME = 'new_password';

    /**
     * Create a new ChangePasswordForm
     *
     * @param DbUserBackend $backend The user backend to work with
     */
    public function __construct(
        protected DbUserBackend $backend,
    ) {
        $this->setAttribute('name', 'form_change_password');
    }

    protected function assemble(): void
    {
        $this->addCsrfCounterMeasure();
        $this->addElement($this->createUidElement());

        $this->addElement('password', static::OLD_PASSWORD_ELEMENT_NAME, [
            'label'    => $this->translate('Old Password'),
            'required' => true
        ]);

        $this->addElement('password', static::NEW_PASSWORD_ELEMENT_NAME, [
            'label'    => $this->translate('New Password'),
            'required' => true
        ]);

        PasswordPolicyHelper::apply(
            $this,
            static::NEW_PASSWORD_ELEMENT_NAME,
            static::OLD_PASSWORD_ELEMENT_NAME,
        );

        $this->addElement('password', static::NEW_PASSWORD_ELEMENT_NAME . '_confirmation', [
            'label'      => $this->translate('Confirm New Password'),
            'required'   => true,
            'validators' => [new CallbackValidator(
                function (#[SensitiveParameter] mixed $value, CallbackValidator $validator): bool {
                    if (! hash_equals($this->getValue(static::NEW_PASSWORD_ELEMENT_NAME), $value)) {
                        $validator->addMessage($this->translate('The passwords do not match'));

                        return false;
                    }

                    return true;
                }
            )],
        ]);

        $this->addElement('submit', 'submit', ['label' => $this->translate('Update Account')]);
    }

    protected function onSuccess(): void
    {
        $this->backend->update(
            $this->backend->getBaseTable(),
            ['password' => $this->getElement(static::NEW_PASSWORD_ELEMENT_NAME)->getValue()],
            Filter::where('user_name', Auth::getInstance()->getUser()->getUsername())
        );
        Notification::success($this->translate('Account updated'));
    }

    /**
     * Check whether the form is valid
     *
     * Extends the parent check by authenticating the old password against the
     * configured backend, and adds an element-level error message if it fails.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if (! parent::isValid()) {
            return false;
        }

        $oldPasswordEl = $this->getElement(static::OLD_PASSWORD_ELEMENT_NAME);

        if (! $this->backend->authenticate(Auth::getInstance()->getUser(), $oldPasswordEl->getValue())) {
            $oldPasswordEl->addMessage($this->translate('Old password is invalid'));

            return false;
        }

        return true;
    }
}
