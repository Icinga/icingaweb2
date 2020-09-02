<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Common\Database;
use Icinga\Web\RememberMe;
use Icinga\Web\RememberMeUserList;
use Icinga\Web\RememberMeUserDevicesList;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;

/**
 * RememberMeManageController
 *
 * you need 'manage/rememberme' permission to use this controller
 */
class ManageUserDevicesController extends CompatController
{
    use Database;

    public function init()
    {

    }

    public function indexAction()
    {
        $this->getTabs()
            ->add('manageuserdevices',
                array(
                    'title' => $this->translate('List of users who stay logged in'),
                    'label' => $this->translate('Users'),
                    'url'   => 'manageuserdevices',
                    'data-base-target' => '_self'
                )
            );
        $this->getTabs()->activate('manageuserdevices');

        $this->assertPermission('manage/rememberme');

        $usersList = (new RememberMeUserList())
            ->setUsers(RememberMe::getAllUser())
            ->setUrl('manageuserdevices/devices');

        $this->addContent($usersList);
    }

    public function devicesAction()
    {
        $this->getTabs()
            ->add('managedevices',
                array(
                    'title' => $this->translate('List of devices'),
                    'label' => $this->translate('Devices'),
                    'url'   => 'manageuserdevices/devices'
                )
            );
        $this->getTabs()->activate('managedevices');

        $name = $this->params->get('name');

        $data = (new RememberMeUserDevicesList())
            ->setDevicesList(RememberMe::getAllByUsername($name))
            ->setUsername($name)
            ->setUrl('manageuserdevices/delete');

        $this->addContent($data);
    }

    public function deleteAction()
    {
        (new RememberMe())->removeSpecific(
            $this->params->get('name'),
            $this->params->get('agent')
        );

        $this->redirectNow(
            Url::fromPath('manageuserdevices/devices')
                ->addParams(['name' => $this->params->get('name')])
        );
    }
}
