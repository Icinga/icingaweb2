<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Protocol\File\FileReader;
use Icinga\Web\Controller;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\MenuAction;
use Icinga\Web\Widget\Tabextension\OutputFormat;

/**
 * Application wide controller for various listing actions
 */
class ListController extends Controller
{
    /**
     * Add title tab
     *
     * @param string $action
     */
    protected function addTitleTab($action)
    {
        $this->getTabs()->add($action, array(
            'label' => ucfirst($action),
            'url'   => Url::fromPath('list/' . str_replace(' ', '', $action))
        ))->extend(new OutputFormat())->extend(new DashboardAction())->extend(new MenuAction())->activate($action);
    }

    /**
     * Display the application log
     */
    public function applicationlogAction()
    {
        $this->assertPermission('application/log');

        if (! Logger::writesToFile()) {
            $this->httpNotFound('Page not found');
        }

        $this->addTitleTab('application log');

        $resource = new FileReader(new ConfigObject(array(
            'filename'  => Config::app()->get('logging', 'file'),
            'fields'    => '/(?<!.)(?<datetime>[0-9]{4}(?:-[0-9]{2}){2}'    // date
                . 'T[0-9]{2}(?::[0-9]{2}){2}(?:[\+\-][0-9]{2}:[0-9]{2})?)'  // time
                . ' - (?<loglevel>[A-Za-z]+) - (?<message>.*)(?!.)/msS'     // loglevel, message
        )));
        $this->view->logData = $resource->select()->order('DESC');

        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->logData);
    }
}
