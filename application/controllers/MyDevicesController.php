<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Common\Database;
use Icinga\Web\RememberMe;
use Icinga\Web\RememberMeUserDevicesList;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Compat\CompatController;
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
        try {
            $this->getDb();
        } catch (Throwable $e) {
            $hasConfigPermission =  $this->hasPermission('config/*');
            $configLink = new HtmlDocument();
            if ($hasConfigPermission) {
                $warningMessage = $this->translate(
                    'No Configuration Database selected.'
                    . 'To establish a valid database connection set the Configuration Database field.'
                );

                $configLink = new Link($this->translate('Configuration Database'), 'config/general');
            } else {
                $warningMessage = $this->translate(
                    'No Configuration Database selected.'
                    . 'You don`t have permission to change this setting. Please contact an administrator.'
                );
            }

            $this->addContent(
                new HtmlElement(
                    'div',
                    new Attributes(['class' => 'db-connection-warning']),
                    new Icon('warning'),
                    new HtmlElement(
                        'p',
                        null,
                        Text::create($warningMessage),
                    ),
                    $configLink
                )
            );

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
