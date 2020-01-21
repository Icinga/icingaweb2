<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Controller;

use Exception;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\CheckNowCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteCommentCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteDowntimeCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ObjectsCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\RemoveAcknowledgementCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ToggleObjectFeaturesCommandForm;
use Icinga\Module\Monitoring\Hook\DetailviewExtensionHook;
use Icinga\Module\Monitoring\Hook\ObjectDetailsTabHook;
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
     * List of visible hooked tabs
     *
     * @var ObjectDetailsTabHook[]
     */
    protected $tabHooks = [];

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
            $this->view->graphers = Hook::all('grapher');
        }
    }

    /**
     * Show a host or service
     */
    public function showAction()
    {
        $this->setAutorefreshInterval(10);
        $this->setupQuickActionForms();
        $auth = $this->Auth();
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

        $this->view->extensionsHtml = array();
        foreach (Hook::all('Monitoring\DetailviewExtension') as $hook) {
            /** @var DetailviewExtensionHook $hook */

            try {
                $html = $hook->setView($this->view)->getHtmlForObject($this->object);
            } catch (Exception $e) {
                $html = $this->view->escape($e->getMessage());
            }

            if ($html) {
                $module = $this->view->escape($hook->getModule()->getName());
                $this->view->extensionsHtml[] =
                    '<div class="icinga-module module-' . $module . '" data-icinga-module="' . $module . '">'
                    . $html
                    . '</div>';
            }
        }
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
     * Show the content of a custom tab
     */
    public function tabhookAction()
    {
        $hookName = $this->params->get('hook');
        $this->getTabs()->activate($hookName);

        $hook = $this->tabHooks[$hookName];

        $this->view->header = $hook->getHeader($this->object, $this->getRequest());
        $this->view->content = $hook->getContent($this->object, $this->getRequest());
        $this->view->object = $this->object;
        $this->render('object/detail-tabhook', null, true);
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
        $this->setupQuickActionForms();
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
            $payload['vars'] = $this->object->customvars;

            if ($this->hasPermission('*') || ! $this->hasPermission('no-monitoring/contacts')) {
                $payload['contacts'] = $this->object->contacts->fetchPairs();
                $payload['contact_groups'] = $this->object->contactgroups->fetchPairs();
            } else {
                $payload['contacts'] = [];
                $payload['contact_groups'] = [];
            }

            $groupName = $this->object->getType() . 'groups';
            $payload[$groupName] = $this->object->$groupName;
            $this->getResponse()->json()
                ->setSuccessData($payload)
                ->setAutoSanitize()
                ->sendResponse();
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

        /** @var ObjectDetailsTabHook $hook */
        foreach (Hook::all('Monitoring\\ObjectDetailsTab') as $hook) {
            $hookName = $hook->getName();
            if ($hook->shouldBeShown($object, $this->Auth())) {
                $this->tabHooks[$hookName] = $hook;
                $tabs->add($hookName, [
                    'label' => $hook->getLabel(),
                    'url'       => $isService ? 'monitoring/service/tabhook' : 'monitoring/host/tabhook',
                    'urlParams' => $params + [ 'hook' => $hookName ]
                ]);
            }
        }

        $tabs->extend(new DashboardAction())->extend(new MenuAction());
    }

    /**
     * Create quick action forms and pass them to the view
     */
    protected function setupQuickActionForms()
    {
        $auth = $this->Auth();
        if ($auth->hasPermission('monitoring/command/schedule-check')
            || ($auth->hasPermission('monitoring/command/schedule-check/active-only')
                && $this->object->active_checks_enabled
            )
        ) {
            $this->view->checkNowForm = $checkNowForm = new CheckNowCommandForm();
            $checkNowForm
                ->setObjects($this->object)
                ->handleRequest();
        }
        if (! in_array((int) $this->object->state, array(0, 99))
            && $this->object->acknowledged
            && $auth->hasPermission('monitoring/command/remove-acknowledgement')
        ) {
            $this->view->removeAckForm = $removeAckForm = new RemoveAcknowledgementCommandForm();
            $removeAckForm
                ->setObjects($this->object)
                ->handleRequest();
        }
    }
}
