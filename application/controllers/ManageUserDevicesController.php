<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Web\RememberMe;
use Icinga\Web\RememberMeUserList;
use Icinga\Web\RememberMeUserDevicesList;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;

/**
 * ManageUserDevicesController
 *
 * you need 'application/sessions' permission to use this controller
 */
class ManageUserDevicesController extends CompatController
{

    public function indexAction()
    {
        $this->getTabs()
            ->add(
                'manage-user-devices',
                array(
                    'title' => $this->translate('List of users who stay logged in'),
                    'label' => $this->translate('Users'),
                    'url'   => 'manage-user-devices',
                    'data-base-target' => '_self'
                )
            );
        $this->getTabs()->activate('manage-user-devices');

        $this->assertPermission('application/sessions');

        $usersList = (new RememberMeUserList())
            ->setUsers(RememberMe::getAllUser())
            ->setUrl('manage-user-devices/devices');

        $this->addContent($usersList);
    }

    public function devicesAction()
    {
        $this->getTabs()
            ->add(
                'manage-devices',
                array(
                    'title' => $this->translate('List of devices'),
                    'label' => $this->translate('Devices'),
                    'url'   => 'manage-user-devices/devices'
                )
            );
        $this->getTabs()->activate('manage-devices');

        $name = $this->params->get('name');

        $data = (new RememberMeUserDevicesList())
            ->setDevicesList(RememberMe::getAllByUsername($name))
            ->setUsername($name)
            ->setUrl('manage-user-devices/delete');

        $this->addContent($data);
    }

    public function deleteAction()
    {
        (new RememberMe())->removeSpecific(
            $this->params->get('name'),
            $this->params->get('fingerprint')
        );

        $this->redirectNow(
            Url::fromPath('manage-user-devices/devices')
                ->addParams(['name' => $this->params->get('name')])
        );
    }
}
