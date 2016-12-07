<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Controller;

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\CheckNowCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteCommentCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteDowntimeCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ObjectsCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\RemoveAcknowledgementCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ToggleObjectFeaturesCommandForm;
use Icinga\Web\Hook;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\MenuAction;

/**
 * Base class for the host and service controller
 */
abstract class MonitoredObjectController extends Controller
{
    /**
     * The requested host or service
     *
     * @var \Icinga\Module\Monitoring\Object\Host|\Icinga\Module\Monitoring\Object\Host
     */
    protected $object;

    /**
     * URL to redirect to after a command was handled
     *
     * @var string
     */
    protected $commandRedirectUrl;

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Controller\ActionController For the method documentation.
     */
    public function prepareInit()
    {
        parent::prepareInit();
        if (Hook::has('ticket')) {
            $this->view->tickets = Hook::first('ticket');
        }
        if (Hook::has('grapher')) {
            $this->view->grapher = Hook::first('grapher');
        }
    }

    /**
     * Show a host or service
     */
    public function showAction()
    {
        $this->setAutorefreshInterval(10);
        $auth = $this->Auth();
        if ($auth->hasPermission('monitoring/command/schedule-check')) {
            $checkNowForm = new CheckNowCommandForm();
            $checkNowForm
                ->setObjects($this->object)
                ->handleRequest();
            $this->view->checkNowForm = $checkNowForm;
        }
        if (! in_array((int) $this->object->state, array(0, 99))) {
            if ((bool) $this->object->acknowledged) {
                if ($auth->hasPermission('monitoring/command/remove-acknowledgement')) {
                    $removeAckForm = new RemoveAcknowledgementCommandForm();
                    $removeAckForm
                        ->setObjects($this->object)
                        ->handleRequest();
                    $this->view->removeAckForm = $removeAckForm;
                }
            }
        }
        $this->object->populate();
        $this->handleFormatRequest();
        $toggleFeaturesForm = new ToggleObjectFeaturesCommandForm(array(
            'backend'   => $this->backend,
            'objects'   => $this->object
        ));
        $toggleFeaturesForm
            ->load($this->object)
            ->handleRequest();
        $this->view->toggleFeaturesForm = $toggleFeaturesForm;
        if (! empty($this->object->comments) && $auth->hasPermission('monitoring/command/comment/delete')) {
            $delCommentForm = new DeleteCommentCommandForm();
            $delCommentForm->handleRequest();
            $this->view->delCommentForm = $delCommentForm;
        }
        if (! empty($this->object->downtimes) && $auth->hasPermission('monitoring/command/downtime/delete')) {
            $delDowntimeForm = new DeleteDowntimeCommandForm();
            $delDowntimeForm->handleRequest();
            $this->view->delDowntimeForm = $delDowntimeForm;
        }
        $this->view->showInstance = $this->backend->select()->from('instance')->count() > 1;
        $this->view->object = $this->object;
    }

    /**
     * Show the history for a host or service
     */
    public function historyAction()
    {
        $this->getTabs()->activate('history');
        $this->view->history = $this->object->fetchEventHistory()->eventhistory;
        $this->applyRestriction('monitoring/filter/objects', $this->view->history);

        $this->setupLimitControl(50);
        $this->setupPaginationControl($this->view->history, 50);
        $this->view->object = $this->object;
        $this->render('object/detail-history', null, true);
    }

    /**
     * Handle a command form
     *
     * @param   ObjectsCommandForm $form
     *
     * @return  ObjectsCommandForm
     */
    protected function handleCommandForm(ObjectsCommandForm $form)
    {
        $form
            ->setBackend($this->backend)
            ->setObjects($this->object)
            ->setRedirectUrl(Url::fromPath($this->commandRedirectUrl)->setParams($this->params))
            ->handleRequest();
        $this->view->form = $form;
        $this->view->object = $this->object;
        $this->view->tabs->remove('dashboard');
        $this->view->tabs->remove('menu-entry');
        $this->_helper->viewRenderer('partials/command/object-command-form', null, true);
        return $form;
    }

    /**
     * Export to JSON if requested
     */
    protected function handleFormatRequest($query = null)
    {
        if ($this->params->get('format') === 'json'
            || $this->getRequest()->getHeader('Accept') === 'application/json'
        ) {
            $payload = (array) $this->object->properties;
            $payload += array(
                'contacts'          => $this->object->contacts->fetchPairs(),
                'contact_groups'    => $this->object->contactgroups->fetchPairs(),
                'vars'              => $this->object->customvars
            );
            $groupName = $this->object->getType() . 'groups';
            $payload[$groupName] = $this->object->$groupName;
            $this->getResponse()->json()->setSuccessData($payload)->sendResponse();
        }
    }

    /**
     * Acknowledge a problem
     */
    abstract public function acknowledgeProblemAction();

    /**
     * Add a comment
     */
    abstract public function addCommentAction();

    /**
     * Reschedule a check
     */
    abstract public function rescheduleCheckAction();

    /**
     * Schedule a downtime
     */
    abstract public function scheduleDowntimeAction();

    /**
     * Create tabs
     */
    protected function createTabs()
    {
        $tabs = $this->getTabs();
        $object = $this->object;
        if ($object->getType() === $object::TYPE_HOST) {
            $isService = false;
            $params = array(
                'host' => $object->getName()
            );
            if ($this->params->has('service')) {
                $params['service'] = $this->params->get('service');
            }
        } else {
            $isService = true;
            $params = array(
                'host'      => $object->getHost()->getName(),
                'service'   => $object->getName()
            );
        }
        $tabs->add(
            'host',
            array(
                'title'     => sprintf(
                    $this->translate('Show detailed information for host %s'),
                    $isService ? $object->getHost()->getName() : $object->getName()
                ),
                'label'     => $this->translate('Host'),
                'url'       => 'monitoring/host/show',
                'urlParams' => $params
            )
        );
        if ($isService || $this->params->has('service')) {
            $tabs->add(
                'service',
                array(
                    'title'     => sprintf(
                        $this->translate('Show detailed information for service %s on host %s'),
                        $isService ? $object->getName() : $this->params->get('service'),
                        $isService ? $object->getHost()->getName() : $object->getName()
                    ),
                    'label'     => $this->translate('Service'),
                    'url'       => 'monitoring/service/show',
                    'urlParams' => $params
                )
            );
        }
        $tabs->add(
            'services',
            array(
                'title'     => sprintf(
                    $this->translate('List all services on host %s'),
                    $isService ? $object->getHost()->getName() : $object->getName()
                ),
                'label'     => $this->translate('Services'),
                'url'       => 'monitoring/host/services',
                'urlParams' => $params
            )
        );
        if ($this->backend->hasQuery('eventhistory')) {
            $tabs->add(
                'history',
                array(
                    'title'     => $isService
                        ? sprintf(
                            $this->translate('Show all event records of service %s on host %s'),
                            $object->getName(),
                            $object->getHost()->getName()
                        )
                        : sprintf($this->translate('Show all event records of host %s'), $object->getName())
                    ,
                    'label'     => $this->translate('History'),
                    'url'       => $isService ? 'monitoring/service/history' : 'monitoring/host/history',
                    'urlParams' => $params
                )
            );
        }
        $tabs->extend(new DashboardAction())->extend(new MenuAction());
    }
}
