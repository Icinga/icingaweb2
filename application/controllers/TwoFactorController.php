<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Controllers;

use Icinga\Application\Hook\TwoFactorHook;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Authentication\Auth;
use Icinga\Forms\Account\TwoFactorEnrollmentForm;
use Icinga\Web\Session;
use ipl\Web\Common\CalloutType;
use ipl\Web\Widget\Callout;
use Throwable;
use ipl\Html\Contract\Form;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Compat\CompatController;

/**
 * Two-factor authentication configuration
 */
class TwoFactorController extends CompatController
{
    public function init(): void
    {
        $this->getTabs()
            ->add('account', [
                'title' => $this->translate('Update your account'),
                'label' => $this->translate('My Account'),
                'url'   => 'account',
            ])
            ->add('navigation', [
                'title' => $this->translate('List and configure your own navigation items'),
                'label' => $this->translate('Navigation'),
                'url'   => 'navigation',
            ])
            ->add('devices', [
                'title' => $this->translate('List of devices you are logged in'),
                'label' => $this->translate('My Devices'),
                'url'   => 'my-devices',
            ])
            ->add('two-factor', [
                'title' => $this->translate('Configure two-factor authentication'),
                'label' => $this->translate('Two-Factor Auth'),
                'url'   => 'two-factor/config',
            ]);
    }

    /**
     * Render the two-factor authentication configuration page
     *
     * Shows an informational notice when no two-factor method is registered. Otherwise,
     * renders {@see TwoFactorEnrollmentForm}, letting the user enroll in or unenroll from
     * registered two-factor methods.
     *
     * @return void
     */
    public function configAction(): void
    {
        $this->getTabs()->activate('two-factor');
        $this->addContent(HtmlElement::create('h1', null, Text::create($this->translate('Two-Factor Authentication'))));

        if (TwoFactorHook::all() === []) {
            $this->addContent(new Callout(CalloutType::Info, $this->translate(
                'No two-factor authentication method is available. Enable a module that provides'
                . ' an implementation to configure two-factor authentication for your account.',
            )));

            return;
        }

        $user = Auth::getInstance()->getUser();

        try {
            $enrolledMethodName = TwoFactorHook::loadEnrolled($user)?->getName();
        } catch (Throwable $e) {
            Logger::error("%s\n%s", $e->getMessage(), IcingaException::getConfidentialTraceAsString($e));
            $this->addContent(new Callout(CalloutType::Error, sprintf(
                $this->translate('Two-factor authentication is currently unavailable: %s. Contact your administrator.'),
                $e->getMessage(),
            )));

            return;
        }

        $enrollmentForm = (new TwoFactorEnrollmentForm($user, $enrolledMethodName))
            ->setCsrfCounterMeasureId(Session::getSession()->getId())
            ->on(Form::ON_SUBMIT, function (TwoFactorEnrollmentForm $form) {
                if ($redirectUrl = $form->getRedirectUrl()) {
                    $this->redirectNow($redirectUrl);
                }
            })
            ->handleRequest($this->getServerRequest());

        $this->addContent($enrollmentForm);
    }
}
