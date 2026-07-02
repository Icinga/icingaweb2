<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Authentication;

use Exception;
use Icinga\Application\Config;
use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\IcingaException;
use Icinga\Web\Form;
use ipl\Web\Common\CalloutType;
use ipl\Web\Compat\DisplayFormElement;
use ipl\Web\Widget\Callout;
use LogicException;

class PasswordPolicyHelper
{
    /** @var string Default password policy class */
    public const DEFAULT_PASSWORD_POLICY = 'any';

    /** @var string INI configuration section for password policy */
    public const CONFIG_SECTION = 'security';

    /** @var string INI configuration key for password policy */
    public const CONFIG_KEY = 'password_policy';

    /**
     * Load the configured password policy, fall back to a warning if the policy configuration is invalid.
     * On success, attaches the policy validator to the given new-password form element.
     *
     * @param Form $form
     * @param string $newPasswordElementName
     * @param string|null $oldPasswordElementName Optional name of the old password form element for comparison
     *
     * @return void
     *
     * @throws LogicException If the old password element is specified but does not exist in the form
     */
    public static function apply(Form $form,
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
            $passwordPolicy = static::create();

            // getElement() may return null if the element does not exist, causing this call to fail.
            $form->getElement($newPasswordElementName)->addValidator(
                new PasswordPolicyValidator($passwordPolicy, $oldPasswordElementName)
            );
            static::addDescription($form, $passwordPolicy);
        } catch (Exception $e) {
            Logger::error(
                "Failed to instantiate configured password policy: %s\n%s",
                $e,
                IcingaException::getConfidentialTraceAsString($e),
            );

            $form->addElement(
                'note',
                'bogus',
                [
                    'decorators' => ['ViewHelper'],
                    'value' => (new DisplayFormElement(new Callout(
                        CalloutType::Error,
                        t(
                            'There was a problem loading the configured password policy. '
                            . 'Please contact your administrator.'
                        ),
                    )))->render(),
                ]
            );
        }
    }

    /**
     * Create an instance of the currently configured {@link PasswordPolicy}
     *
     * @return PasswordPolicy
     */
    public static function create(): PasswordPolicy
    {
        $canonicalName = Config::app()
            ->get(static::CONFIG_SECTION, static::CONFIG_KEY, static::DEFAULT_PASSWORD_POLICY);
        return PasswordPolicyHook::fromCanonicalName($canonicalName);
    }

    /**
     * Retrieve the description from the given password policy and add it to the form for display to the user.
     *
     * @param Form $form
     * @param PasswordPolicy $passwordPolicy
     * @return void
     */
    public static function addDescription(Form $form, PasswordPolicy $passwordPolicy): void
    {
        $description = $passwordPolicy->getDescription();

        if ($description !== null) {
            $form->addDescription($description);
        }
    }
}
