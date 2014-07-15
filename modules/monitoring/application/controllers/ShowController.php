<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Benchmark;
use Icinga\Web\Hook;
use Icinga\Web\Widget\Tabs;
use Icinga\Web\Widget\Tabextension\OutputFormat;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Module\Monitoring\Backend;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Object\AbstractObject;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\Service;

/**
 * Class Monitoring_ShowController
 *
 * Actions for show context
 */
class Monitoring_ShowController extends Controller
{
    /**
     * @var Backend
     */
    protected $backend;

    protected $grapher;

    /**
     * Initialize the controller
     */
    public function init()
    {
        if ($this->getRequest()->getActionName() === 'host') {
            $this->view->object = new Host($this->params);
        } elseif ($this->getRequest()->getActionName() === 'service') {
            $this->view->object = new Service($this->params);
        } else {
            // TODO: Well... this could be done better
            $this->view->object = AbstractObject::fromParams($this->params);
        }
        if (Hook::has('ticket')) {
            $this->view->tickets = Hook::first('ticket');
        }
        if (Hook::has('grapher')) {
            $this->grapher = Hook::first('grapher');
        }

        $this->createTabs();
    }

    /**
     * Service overview
     */
    public function serviceAction()
    {
        $o = $this->view->object;
        $this->setAutorefreshInterval(10);
        $this->view->title = $o->service_description
            . ' on ' . $o->host_name;
        $this->getTabs()->activate('service');
        $o->populate();
        if ($this->grapher && $this->grapher->hasGraph($o->host_name, $o->service_description)) {
            $this->view->grapherHtml = $this->grapher->getPreviewImage($o->host_name, $o->service_description);
        }
    }

    /**
     * Host overview
     */
    public function hostAction()
    {
        $o = $this->view->object;
        $this->setAutorefreshInterval(10);
        $this->getTabs()->activate('host');
        $this->view->title = $o->host_name;
        $o->populate();
        if ($this->grapher && $this->grapher->hasGraph($o->host_name)) {
            $this->view->grapherHtml = $this->grapher->getPreviewImage($o->host_name);
        }
    }

    public function historyAction()
    {
        $this->getTabs()->activate('history');
        //$this->view->object->populate();
        $this->view->object->fetchEventHistory();
        $this->handleFormatRequest($this->view->object->eventhistory);
        $this->view->history = $this->view->object->eventhistory
            ->paginate($this->params->get('limit', 50));
    }

    public function servicesAction()
    {
        $this->getTabs()->activate('services');
        $this->_setParam('service', '');
        // TODO: This used to be a hack and still is. Modifying query string here.
        $_SERVER['QUERY_STRING'] = (string) $this->params->without('service')->set('limit', '');
        $this->view->services = $this->view->action('services', 'list', 'monitoring', array(
            'view'  => 'compact',
            'sort'  => 'service_description',
        ));
    }

    /**
     * Creating tabs for this controller
     * @return Tabs
     */
    protected function createTabs()
    {
        if (($object = $this->view->object) === null) {
            return;
        }

        $tabs = $this->getTabs();
        $params = array(
            'host' => $object->host_name,
        );
        if ($object instanceof Service) {
            $params['service'] = $object->service_description;
        } elseif ($service = $this->_getParam('service')) {
            $params['service'] = $service;
        }
        $tabs->add(
            'host',
            array(
                'title'     => 'Host',
                'icon'      => 'img/icons/host.png',
                'url'       => 'monitoring/show/host',
                'urlParams' => $params,
            )
        );
        if (isset($params['service'])) {
            $tabs->add(
                'service',
                array(
                    'title'     => 'Service',
                    'icon'      => 'img/icons/service.png',
                    'url'       => 'monitoring/show/service',
                    'urlParams' => $params,
                )
            );
        }
        $tabs->add(
            'services',
            array(
                'title'     => 'Services',
                'icon'      => 'img/icons/service.png',
                'url'       => 'monitoring/show/services',
                'urlParams' => $params,
            )
        );
        $tabs->add(
            'history',
            array(
                'title'     => 'History',
                'icon'      => 'img/icons/history.png',
                'url'       => 'monitoring/show/history',
                'urlParams' => $params,
            )
        );
        $tabs->extend(new OutputFormat())
            ->extend(new DashboardAction());
    }
}
