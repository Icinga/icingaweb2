<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Account;

use Icinga\Application\Hook\TwoFactorHook;
use Icinga\User;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;
use Throwable;

/**
 * Form for enrolling in and unenrolling from a two-factor authentication method
 */
class TwoFactorEnrollmentForm extends CompatForm
{
    use CsrfCounterMeasure;
    use FormUid;

    /** @var string Form field name for the selected 2FA method */
    public const METHOD = 'twofactor_method';

    /** @var string The submit button to enroll into a 2FA method */
    protected const SUBMIT_ENROLL = 'submit_twofactor_enroll';

    /** @var string The submit button to unenroll from a 2FA method */
    protected const SUBMIT_UNENROLL = 'submit_twofactor_unenroll';

    /** @var bool Whether the user is already enrolled in a 2FA method */
    protected bool $enrolled;

    /**
     * Create a new TwoFactorEnrollmentForm
     *
     * @param User $user The user to enroll
     * @param ?string $enrolledMethodName The canonical name of the method the user is
     *   currently enrolled in
     */
    public function __construct(
        protected User $user,
        ?string $enrolledMethodName = null,
    ) {
        $this->enrolled = $enrolledMethodName !== null;

        $this->setAttribute('name', 'form_twofactor_enrollment');
        $this->populate([static::METHOD => $enrolledMethodName]);
    }

    protected function assemble(): void
    {
        $this->addCsrfCounterMeasure();
        $this->addElement($this->createUidElement());

        $methods = iterator_to_array(TwoFactorHook::yieldMethods());
        $this->addElement('select', static::METHOD, [
            'label'        => $this->translate('2FA Method'),
            'class'        => 'autosubmit',
            'disabled'     => $this->enrolled,
            'options'      => $methods,
            'pleaseChoose' => true,
            'required'     => true,
        ]);

        if ($this->enrolled) {
            $this->addElement('submit', static::SUBMIT_UNENROLL, [
                'label'               => $this->translate('Unenroll'),
                'data-progress-label' => $this->translate('Unenrolling'),
            ]);

            return;
        }

        $method = $this->getPopulatedValue(static::METHOD);

        // getPopulatedValue() returns the raw "Please choose" value before
        // the select element normalizes an empty string to null.
        if ($method === null || $method === '' || ! array_key_exists($method, $methods)) {
            return;
        }

        $twoFactor = TwoFactorHook::fromName($method);

        $configFieldset = new FieldsetElement($twoFactor->getName());
        $this->addElement($configFieldset);

        try {
            $twoFactor->assembleEnrollmentFormElements($this->user, $configFieldset);
        } catch (Throwable $e) {
            $this->logAndShowError(
                $e,
                $this->translate('Two-factor method "%s" failed to assemble enrollment form elements: {error}'),
                $twoFactor->getName(),
            );

            return;
        }

        $this->addElement('submit', static::SUBMIT_ENROLL, [
            'label'               => $this->translate('Enroll'),
            'data-progress-label' => $this->translate('Enrolling'),
        ]);
    }

    protected function onSuccess(): void
    {
        $twoFactor = TwoFactorHook::fromName($this->getValue(static::METHOD));

        switch ($this->getPressedSubmitElement()?->getName()) {
            case static::SUBMIT_ENROLL:
                /** @var FieldsetElement $configFieldset */
                $configFieldset = $this->getElement($twoFactor->getName());
                try {
                    if (! $twoFactor->enroll($this->user, $configFieldset)) {
                        $this->onError();

                        // Don't redirect in this case, as the user might want to try again.
                        return;
                    }
                } catch (Throwable $e) {
                    $this->logAndShowError(
                        $e,
                        $this->translate('Could not enroll in two-factor method "%s": {error}'),
                        $twoFactor->getName(),
                    );

                    return;
                }
                $this->setRedirectUrl(Url::fromRequest());

                break;
            case static::SUBMIT_UNENROLL:
                try {
                    $twoFactor->unenroll($this->user);
                } catch (Throwable $e) {
                    $this->logAndShowError(
                        $e,
                        $this->translate('Could not unenroll from two-factor method "%s": {error}'),
                        $twoFactor->getName(),
                    );

                    return;
                }
                $this->setRedirectUrl(Url::fromRequest());

                break;
        }
    }
}
