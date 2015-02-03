<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
        if ( ! in_array((int) $this->object->state, array(0, 99))) {
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
        if (count($this->object->comments) > 0 && $auth->hasPermission('monitoring/command/comment/delete')) {
            $delCommentForm = new DeleteCommentCommandForm();
            $delCommentForm
                ->setObjects($this->object)
                ->handleRequest();
            $this->view->delCommentForm = $delCommentForm;
        }
        if (count($this->object->downtimes > 0) && $auth->hasPermission('monitoring/command/downtime/delete')) {
            $delDowntimeForm = new DeleteDowntimeCommandForm();
            $delDowntimeForm
                ->setObjects($this->object)
                ->handleRequest();
            $this->view->delDowntimeForm = $delDowntimeForm;
        }
        $toggleFeaturesForm = new ToggleObjectFeaturesCommandForm();
        $toggleFeaturesForm
            ->load($this->object)
            ->setObjects($this->object)
            ->handleRequest();
        $this->view->toggleFeaturesForm = $toggleFeaturesForm;
        $this->view->object = $this->object->populate();
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
            ->setObjects($this->object)
            ->setRedirectUrl(Url::fromPath($this->commandRedirectUrl)->setParams($this->params))
            ->handleRequest();
        $this->view->form = $form;
        $this->view->object = $this->object;
        $this->view->tabs->remove('dashboard');
        $this->_helper->viewRenderer('partials/command/object-command-form', null, true);
        return $form;
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
     * Delete a comment
     */
    public function deleteCommentAction()
    {
        $this->assertHttpMethod('POST');
        $this->assertPermission('monitoring/command/comment/delete');
        $this->handleCommandForm(new DeleteCommentCommandForm());
    }

    /**
     * Delete a downtime
     */
    public function deleteDowntimeAction()
    {
        $this->assertHttpMethod('POST');
        $this->assertPermission('monitoring/command/downtime/delete');
        $this->handleCommandForm(new DeleteDowntimeCommandForm());
    }

    /**
     * Create tabs
     */
    protected function createTabs()
    {
        $tabs = $this->getTabs();
        $object = $this->object;
        if ($object->getType() === $object::TYPE_HOST) {
            $params = array(
                'host' => $object->getName()
            );
        } else {
            $params = array(
                'host'      => $object->getHost()->getName(),
                'service'   => $object->getName()
            );
        }
        $tabs->add(
            'host',
            array(
                'title'     => $this->translate('Host'),
                'icon'      => 'host',
                'url'       => 'monitoring/host/show',
                'urlParams' => $params
            )
        );
        if (isset($params['service'])) {
            $tabs->add(
                'service',
                array(
                    'title'     => $this->translate('Service'),
                    'icon'      => 'service',
                    'url'       => 'monitoring/service/show',
                    'urlParams' => $params
                )
            );
        }
        $tabs->add(
            'services',
            array(
                'title'     => $this->translate('Services'),
                'icon'      => 'services',
                'url'       => 'monitoring/show/services',
                'urlParams' => $params
            )
        );
        if ($this->backend->hasQuery('eventHistory')) {
            $tabs->add(
                'history',
                array(
                    'title'     => $this->translate('History'),
                    'icon'      => 'rewind',
                    'url'       => 'monitoring/show/history',
                    'urlParams' => $params
                )
            );
        }
        $tabs->extend(new DashboardAction());
    }
}
