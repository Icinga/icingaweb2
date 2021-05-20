<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Web\RememberMe;
use Icinga\Web\RememberMeUserDevicesList;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;

/**
 * MyDevicesController
 *
 * this controller shows you all the devices you are logged in
 */
class MyDevicesController extends CompatController
{
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
    }

    public function deleteAction()
    {
        (new RememberMe())->removeSpecific($this->params->get('fingerprint'));

        $this->redirectNow(
            Url::fromPath('my-devices/')->addParams(['name' => $this->auth->getUser()->getUsername()])
        );
    }
}
