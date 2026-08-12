<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Controllers;

use Icinga\Application\Logger;
use Icinga\Common\Database;
use Icinga\Web\RememberMe;
use Icinga\Web\RememberMeUserDevicesList;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Common\CalloutType;
use ipl\Web\Compat\CompatController;
use ipl\Web\Widget\Callout;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
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
        try {
            $this->getDb();
        } catch (Throwable $e) {
            Logger::error("%s\n%s", $e, $e->getTraceAsString());
            if ($this->hasPermission('config/*')) {
                $errorMessage = $this->translate(
                    'To establish a valid database connection set the Configuration'
                    . ' Database field in the Application Settings.'
                );
            } else {
                $errorMessage = $this->translate(
                    'You don`t have permission to change this setting. Please contact an administrator.'
                );
            }

            $this->addContent(new Callout(
                CalloutType::Error,
                $errorMessage,
                $this->translate('The configuration database has not been configured'),
            ));

            return;
        }

        $name = $this->auth->getUser()->getUsername();

        $data = (new RememberMeUserDevicesList())
            ->setDevicesList(RememberMe::getAllByUsername($name))
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
