<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Controllers;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Common\Database;
use Icinga\Exception\IcingaException;
use Icinga\Web\RememberMe;
use Icinga\Web\RememberMeUserDevicesList;
use ipl\Web\Common\CalloutType;
use ipl\Web\Compat\CompatController;
use ipl\Web\Widget\Callout;
use Throwable;

/**
 * MyDevicesController
 *
 * this controller shows you all the devices you are logged in
 */
class MyDevicesController extends CompatController
{
    use Database;

    public function init()
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
            ])
            ->activate('devices');
    }

    public function indexAction()
    {
        $name = $this->auth->getUser()->getUsername();
        if (! Config::app()->get('global', 'config_resource')) {
            if ($this->hasPermission('config/general')) {
                $errorMessage = $this->translate(
                    'To establish a valid database connection set the configuration'
                    . ' Database field in the Application Settings.'
                );
            } else {
                $errorMessage = $this->translate(
                    'You do not have permission to change this setting. Please contact an administrator.'
                );
            }

            if ($this->getRequest()->isApiRequest() || $this->params->get('format') === 'json') {
                $this->getResponse()->setHttpResponseCode(500);
                $this->getResponse()->json()
                    ->setErrorMessage($errorMessage)
                    ->sendResponse();
            }

            $this->addContent(new Callout(
                CalloutType::Error,
                $errorMessage,
                $this->translate('The configuration database has not been configured'),
            ));

            return;
        }

        try {
            $deviceList = RememberMe::getAllByUsername($name);
        } catch (Throwable $e) {
            Logger::error("%s\n%s", $e, IcingaException::getConfidentialTraceAsString($e));
            // Rethrowing would delegate API requests to ErrorController, but it does not handle format=json.
            if ($this->getRequest()->isApiRequest() || $this->params->get('format') === 'json') {
                $this->getResponse()->setHttpResponseCode(500);
                $this->getResponse()->json()
                    ->setErrorMessage($e->getMessage())
                    ->sendResponse();
            }

            $this->addContent(new Callout(
                CalloutType::Error,
                sprintf($this->translate('Please check the log for details: %s'), $e->getMessage()),
                $this->translate('Failed to load devices'),
            ));

            return;
        }

        $data = (new RememberMeUserDevicesList())
            ->setDevicesList($deviceList)
            ->setUsername($name)
            ->setUrl('my-devices/delete');

        $this->addContent($data);
    }

    public function deleteAction()
    {
        (new RememberMe())->remove($this->params->getRequired('fingerprint'));

        $this->redirectNow('my-devices');
    }
}
