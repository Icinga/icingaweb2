<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Account;

use Icinga\Application\Hook\TwoFactorHook;
use Icinga\Authentication\TwoFactor;
use Icinga\Web\Session;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;

class TwoFactorEnrollmentForm extends CompatForm
{
    use CsrfCounterMeasure;
    use FormUid;

    public const TWO_FACTOR_METHOD_KEY = 'twofactor_method';

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
            'label'      => '2FA Method',
            'class'      => 'autosubmit',
            'disabled'   => $this->isEnrolled,
            'ignored'    => true,
            'options'    => array_merge(
                ['' => sprintf(' - %s - ', $this->translate('Please choose'))],
                array_combine(
                    array_map(fn(TwoFactor $method) => $method->getName(), TwoFactorHook::all()),
                    array_map(fn(TwoFactor $method) => $method->getDisplayName(), TwoFactorHook::all())
                )
            )
        ]);

        if ($twoFactor = TwoFactorHook::fromName($this->getPopulatedValue(static::TWO_FACTOR_METHOD_KEY) ?? '')) {
            $twoFactor->assembleEnrollmentForm($this);
        }
    }

    protected function onSuccess(): void
    {
        if ($twoFactor = TwoFactorHook::fromName($this->getValue(static::TWO_FACTOR_METHOD_KEY) ?? '')) {
            $twoFactor->onSuccessEnrollmentForm($this);
        }
    }
}
