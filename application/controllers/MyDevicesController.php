<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Common\Database;
use Icinga\Web\Notification;
use Icinga\Web\RememberMe;
use Icinga\Web\RememberMeUserDevicesList;
use ipl\Web\Compat\CompatController;

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
            ->add(
                'account',
                array(
                    'title' => $this->translate('Update your account'),
                    'label' => $this->translate('My Account'),
                    'url'   => 'account'
                )
            )
            ->add(
                'navigation',
                array(
                    'title'     => $this->translate('List and configure your own navigation items'),
                    'label'     => $this->translate('Navigation'),
                    'url'       => 'navigation'
                )
            )
            ->add(
                'devices',
                array(
                    'title' => $this->translate('List of devices you are logged in'),
                    'label' => $this->translate('My Devices'),
                    'url'   => 'my-devices'
                )
            )->activate('devices');
    }

    public function indexAction()
    {
        $name = $this->auth->getUser()->getUsername();

        $data = (new RememberMeUserDevicesList())
            ->setDevicesList(RememberMe::getAllByUsername($name))
            ->setUsername($name)
            ->setUrl('my-devices/delete');

        $this->addContent($data);

        if (! $this->hasDb()) {
            Notification::warning(
                $this->translate("Users can't stay logged in without a database configuration backend")
            );
        }
    }

    public function deleteAction()
    {
        (new RememberMe())->remove($this->params->getRequired('fingerprint'));

        $this->redirectNow('my-devices');
    }
}
