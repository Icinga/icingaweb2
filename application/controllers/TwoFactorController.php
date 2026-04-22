<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Controllers;

use Icinga\Application\Hook\TwoFactorHook;
use Icinga\Forms\Account\TwoFactorEnrollmentForm;
use ipl\Html\Contract\Form;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Compat\CompatController;

class TwoFactorController extends CompatController
{
    public function configAction(): void
    {
        $this->getTabs()->activate('two-factor');
        $this->addContent(HtmlElement::create('h1', null, Text::create('Two-Factor Authentication')));

        $enrolledMethodName = TwoFactorHook::loadEnrolled()?->getName();

        $chooseMethodForm = (new TwoFactorEnrollmentForm($enrolledMethodName !== null))
            ->populate([
                TwoFactorEnrollmentForm::TWO_FACTOR_METHOD_KEY => $enrolledMethodName
            ])
            ->on(Form::ON_SUBMIT, function (TwoFactorEnrollmentForm $form) {
                if ($redirectUrl = $form->getRedirectUrl()) {
                    $this->redirectNow($redirectUrl);
                }
            })
            ->handleRequest($this->getServerRequest());

        $this->addContent($chooseMethodForm);
    }

    public function init(): void
    {
        $this->getTabs()
            ->add('account', [
                'title' => $this->translate('Update your account'),
                'label' => $this->translate('My Account'),
                'url'   => 'account'
            ])
            ->add('navigation', [
                'title' => $this->translate('List and configure your own navigation items'),
                'label' => $this->translate('Navigation'),
                'url'   => 'navigation'
            ])
            ->add('devices', [
                'title' => $this->translate('List of devices you are logged in'),
                'label' => $this->translate('My Devices'),
                'url'   => 'my-devices'
            ])
            ->add('two-factor', [
                'title' => $this->translate('Configure two-factor authentication'),
                'label' => $this->translate('Two-Factor Auth'),
                'url'   => 'two-factor/config',
            ]);
    }
}
