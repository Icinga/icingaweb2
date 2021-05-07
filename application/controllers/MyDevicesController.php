<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Common\Database;
use Icinga\Web\UserAgent;
use Icinga\Web\RememberMe;
use Icinga\Web\RememberMeUserDevicesList;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;

/**
 * RememberMeUserDevicesController
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
                    'url'   => 'mydevices'
                )
            );

        $this->getTabs()->activate('devices');
    }

    public function indexAction()
    {
        $name = $this->auth->getUser()->getUsername();

        $data = (new RememberMeUserDevicesList())
        ->setDevicesList(RememberMe::getAllByUsername($name))
        ->setUsername($name)
        ->setUrl('mydevices/delete');

        $this->addContent($data);
    }

    public function deleteAction()
    {
        (new RememberMe())->removeSpecific(
            $this->auth->getUser()->getUsername(),
            $this->params->get('agent')
        );

        $this->redirectNow(
            Url::fromPath('mydevices/')
                ->addParams(['name' => $this->auth->getUser()->getUsername()])
        );
    }
}
