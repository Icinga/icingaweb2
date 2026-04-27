<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Account;

use Icinga\Application\Hook\TwoFactorHook;
use Icinga\Authentication\TwoFactor;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class TwoFactorEnrollmentForm extends CompatForm
{
    use CsrfCounterMeasure;
    use FormUid;

    public const TWO_FACTOR_METHOD_KEY = 'twofactor_method';

    /** @var string The submit button to enroll into a 2FA method */
    protected const SUBMIT_ENROLL = 'twofactor_submit_enroll';

    /** @var string The submit button to unenroll from a 2FA method */
    protected const SUBMIT_UNENROLL = 'twofactor_submit_unenroll';

    public function __construct(
        /** @var bool Whether the user is already enrolled into a 2FA method */
        protected bool $isEnrolled
    ) {
        $this->setAttribute('name', 'form_2fa_enrollment');
    }

    protected function assemble(): void
    {
        $this->addCsrfCounterMeasure(Session::getSession()->getId());
        $this->addElement($this->createUidElement());

        $this->addElement('select', static::TWO_FACTOR_METHOD_KEY, [
            'label'    => '2FA Method',
            'class'    => 'autosubmit',
            'disabled' => $this->isEnrolled,
            'ignored'  => true,
            'options'  => array_merge(
                ['' => sprintf(' - %s - ', $this->translate('Please choose'))],
                array_combine(
                    array_map(fn(TwoFactor $method) => $method->getName(), TwoFactorHook::all()),
                    array_map(fn(TwoFactor $method) => $method->getDisplayName(), TwoFactorHook::all())
                )
            )
        ]);

        if ($this->isEnrolled) {
            $this->addElement('submit', static::SUBMIT_UNENROLL, [
                'label'               => $this->translate('Unenroll'),
                'data-progress-label' => $this->translate('Unenrolling')
            ]);

            return;
        }

        if ($twoFactor = TwoFactorHook::fromName($this->getPopulatedValue(static::TWO_FACTOR_METHOD_KEY) ?? '')) {
            $twoFactor->assembleEnrollmentFormElements($this);
        }

        $this->addElement('submit', static::SUBMIT_ENROLL, [
            'label'               => $this->translate('Enroll'),
            'data-progress-label' => $this->translate('Enrolling')
        ]);
    }

    protected function onSuccess(): void
    {
        $twoFactor = TwoFactorHook::fromName($this->getValue(static::TWO_FACTOR_METHOD_KEY) ?? '');

        switch ($this->getPressedSubmitElement()?->getName()) {
            case static::SUBMIT_ENROLL:
                if (! $twoFactor->enroll($this)) {
                    Notification::error($this->translate('The verification failed. Please try again.'));

                    // Don't redirect in this case, as the user might want to try again.
                    return;
                }
                Notification::success(sprintf(
                    $this->translate("Successfully enrolled in 2FA method '%s'."),
                    $twoFactor->getDisplayName()
                ));
                $this->setRedirectUrl(Url::fromRequest());

                break;
            case static::SUBMIT_UNENROLL:
                $twoFactor->unenroll();
                Notification::success(sprintf(
                    $this->translate("Successfully unenrolled from 2FA method '%s'."),
                    $twoFactor->getDisplayName()
                ));
                $this->setRedirectUrl(Url::fromRequest());
                break;
        }
    }
}
