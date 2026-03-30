<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Authentication;

use Icinga\Application\Config;
use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Web\Form;
use ipl\Web\Common\CalloutType;
use ipl\Web\Compat\DisplayFormElement;
use ipl\Web\Widget\Callout;
use LogicException;
use Throwable;

/**
 * Helper class for password policy configuration
 *
 * Allows for loading and applying the configured password policy. The password policy
 * is loaded from the application configuration and attached to the given form element.
 * The description of the policy is also added to the form. In case of an error,
 * a warning is displayed to the user.
 */
class PasswordPolicyHelper
{
    /**
     * Apply the configured password policy to the given form element
     *
     * Load the configured password policy, fall back to a warning if the policy
     * configuration is invalid. The description of the policy is also added to the form.
     * On success, attaches the policy validator to the given new-password form element.
     *
     * @param Form $form The form containing the elements and to attach the elements to
     * @param string $newPasswordElementName Name of the new password form element
     * @param ?string $oldPasswordElementName Optional name of the old password form
     *   element for comparison
     *
     * @return void
     *
     * @throws LogicException If the old password element is specified but does not exist in the form
     */
    public static function apply(
        Form $form,
        string $newPasswordElementName,
        ?string $oldPasswordElementName = null
    ): void {
        if ($oldPasswordElementName !== null && $form->getElement($oldPasswordElementName) === null) {
            throw new LogicException(sprintf(
                t('Form element "%s" was specified but does not exist in the form'),
                $oldPasswordElementName
            ));
        }

        try {
            $passwordPolicy = PasswordPolicyHook::loadConfigured(Config::app());
        } catch (Throwable $e) {
            Logger::error("%s\n%s", $e, IcingaException::getConfidentialTraceAsString($e));
            static::addError($form);

            return;
        }

        $form->getElement($newPasswordElementName)->addValidator(
            new PasswordPolicyValidator($passwordPolicy, $oldPasswordElementName)
        );
        static::addDescription($form, $passwordPolicy);
    }

    /**
     * Add the password policy description to the form as an info callout
     *
     * @param Form $form The form to attach the description callout to
     * @param PasswordPolicy $passwordPolicy The policy to retrieve the description from
     *
     * @return void
     */
    public static function addDescription(Form $form, PasswordPolicy $passwordPolicy): void
    {
        try {
            $description = $passwordPolicy->getDescription();
        } catch (Throwable $e) {
            Logger::error("%s\n%s", $e, IcingaException::getConfidentialTraceAsString($e));

            return;
        }

        if ($description === null) {
            return;
        }

        $form->addElement('note', 'policy-note-callout', [
            'decorators' => ['ViewHelper'],
            'value'      => (new DisplayFormElement(
                new Callout(CalloutType::Info, $description, t('Password requirements')),
            ))->render(),
        ]);
    }

    /**
     * Add a password policy load-error callout to the form
     *
     * @param Form $form The form to attach the error callout to
     * @param bool $forAdmin Whether the error message targets an administrator
     *
     * @return void
     */
    public static function addError(Form $form, bool $forAdmin = false): void
    {
        $errorMessage = $forAdmin
            ? t('There was a problem loading the configured password policy.')
            : t('There was a problem loading the configured password policy. Please contact your administrator.');

        $form->addElement('note', 'policy-error-callout', [
            'decorators' => ['ViewHelper'],
            'value'      => (new DisplayFormElement(new Callout(CalloutType::Error, $errorMessage)))->render(),
        ]);
    }
}
