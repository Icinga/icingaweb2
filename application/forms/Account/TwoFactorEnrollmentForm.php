<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Account;

use Icinga\Application\ClassLoader;
use Icinga\Application\Hook\TwoFactorHook;
use Icinga\User;
use Icinga\Web\RememberMe;
use ipl\Html\Attributes;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;
use LogicException;
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

        $methodIdentifier = $this->getPopulatedValue(static::METHOD);

        // getPopulatedValue() returns the raw "Please choose" value before
        // the select element normalizes an empty string to null.
        if ($methodIdentifier === null || $methodIdentifier === '' || ! array_key_exists($methodIdentifier, $methods)) {
            return;
        }

        $twoFactor = TwoFactorHook::fromCanonicalName($methodIdentifier);

        $configFieldset = new FieldsetElement($methodIdentifier);
        $this->addElement($configFieldset);

        try {
            $twoFactor->assembleEnrollmentFormElements($this->user, $configFieldset);
        } catch (Throwable $e) {
            $this->logAndShowError(
                $e,
                $this->translate('Two-factor method "%s" failed to assemble enrollment form elements: {error}'),
                $methodIdentifier,
            );

            return;
        }

        if ($configFieldset->getName() !== $methodIdentifier) {
            throw new LogicException(sprintf(
                '%s::assembleEnrollmentFormElements() must not rename the fieldset. The name "%s" is used'
                . ' as the element key in onSuccess() to retrieve the fieldset, but it was changed to "%s".',
                $twoFactor::class,
                $methodIdentifier,
                $configFieldset->getName(),
            ));
        }

        $configFieldset->addAttributes(Attributes::create([
            'class' => 'icinga-module module-' . ClassLoader::extractModuleName($twoFactor::class),
        ]));

        $this->addElement('submit', static::SUBMIT_ENROLL, [
            'label'               => $this->translate('Enroll'),
            'data-progress-label' => $this->translate('Enrolling'),
        ]);
    }

    protected function onSuccess(): void
    {
        $methodIdentifier = $this->getValue(static::METHOD);
        $twoFactor = TwoFactorHook::fromCanonicalName($methodIdentifier);

        switch ($this->getPressedSubmitElement()?->getName()) {
            case static::SUBMIT_ENROLL:
                /** @var FieldsetElement $configFieldset */
                $configFieldset = $this->getElement($methodIdentifier);
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
                        $methodIdentifier,
                    );

                    return;
                }

                break;
            case static::SUBMIT_UNENROLL:
                try {
                    $twoFactor->unenroll($this->user);
                } catch (Throwable $e) {
                    $this->logAndShowError(
                        $e,
                        $this->translate('Could not unenroll from two-factor method "%s": {error}'),
                        $methodIdentifier,
                    );

                    return;
                }

                break;
            default:
                return;
        }

        // Revoke all remember-me cookies for the current user. On enrollment, existing
        // cookies would bypass the new 2FA requirement. On unenrollment, cookies issued
        // during the enrolled period were only granted after a successful 2FA challenge
        // and should not remain valid.
        RememberMe::removeAllByUsername($this->user->getUsername());
        $this->setRedirectUrl(Url::fromRequest());
    }
}
