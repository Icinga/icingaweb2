<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteDowntimeCommandForm;
use Icinga\Module\Monitoring\Command\Object\DeleteDowntimeCommand;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;

/**
 * Display detailed information about a downtime
 */
class Monitoring_DowntimeController extends Controller
{
    /**
     * The fetched downtime
     *
     * @var stdClass
     */
    protected $downtime;

    /**
     * If the downtime is a service or not
     *
     * @var boolean
     */
    protected $isService;

    /**
     * Fetch the downtime matching the given id and add tabs
     */
    public function init()
    {
        $downtimeId = $this->params->getRequired('downtime_id');

        $this->downtime = $this->backend->select()->from('downtime', array(
            'id'              => 'downtime_internal_id',
            'objecttype'      => 'downtime_objecttype',
            'comment'         => 'downtime_comment',
            'author_name'     => 'downtime_author_name',
            'start'           => 'downtime_start',
            'scheduled_start' => 'downtime_scheduled_start',
            'scheduled_end'   => 'downtime_scheduled_end',
            'end'             => 'downtime_end',
            'duration'        => 'downtime_duration',
            'is_flexible'     => 'downtime_is_flexible',
            'is_fixed'        => 'downtime_is_fixed',
            'is_in_effect'    => 'downtime_is_in_effect',
            'entry_time'      => 'downtime_entry_time',
            'host_state'      => 'downtime_host_state',
            'service_state'   => 'downtime_service_state',
            'host_name',
            'host',
            'service',
            'service_description',
            'host_display_name',
            'service_display_name'
        ))->where('downtime_internal_id', $downtimeId)->getQuery()->fetchRow();

        if ($this->downtime === false) {
            $this->httpNotFound($this->translate('Downtime not found'));
        }

        if (isset($this->downtime->service_description)) {
            $this->isService = true;
        } else {
            $this->isService = false;
        }

        $this->getTabs()
            ->add(
                'downtime',
                array(
                    'title' => $this->translate(
                        'Display detailed information about a downtime.'
                    ),
                    'icon' => 'plug',
                    'label' => $this->translate('Downtime'),
                    'url'   =>'monitoring/downtimes/show'
                )
            )->activate('downtime')->extend(new DashboardAction());
    }

    /**
     * Display the detail view for a downtime
     */
    public function showAction()
    {
        $this->view->downtime = $this->downtime;
        $this->view->isService = $this->isService;
        $this->view->stateName = isset($this->downtime->service_description) ?
                Service::getStateText($this->downtime->service_state) :
                Host::getStateText($this->downtime->host_state);
        $this->view->listAllLink = Url::fromPath('monitoring/list/downtimes');
        $this->view->showHostLink = Url::fromPath('monitoring/host/show')
                ->setParam('host', $this->downtime->host);
        $this->view->showServiceLink = Url::fromPath('monitoring/service/show')
                ->setParam('host', $this->downtime->host)
                ->setParam('service', $this->downtime->service_description);
        if ($this->hasPermission('monitoring/command/downtime/delete')) {
            $this->view->delDowntimeForm = $this->createDelDowntimeForm();
            $this->view->delDowntimeForm->populate(
                array(
                    'redirect' => Url::fromPath('monitoring/list/downtimes'),
                    'downtime_id' => $this->downtime->id,
                    'downtime_is_service' => $this->isService
                )
            );
        }
    }

    /**
     * Receive DeleteDowntimeCommandForm post from other controller
     */
    public function removeAction()
    {
        $this->assertHttpMethod('POST');
        $this->createDelDowntimeForm();
    }

    /**
     * Create a command form to delete a single comment
     *
     * @return DeleteDowntimeCommandForm
     */
    private function createDelDowntimeForm()
    {
        $this->assertPermission('monitoring/command/downtime/delete');
        $delDowntimeForm = new DeleteDowntimeCommandForm();
        $delDowntimeForm->setAction(
            Url::fromPath('monitoring/downtime/show')
                ->setParam('downtime_id', $this->downtime->id)
        );
        $delDowntimeForm->handleRequest();
        return $delDowntimeForm;
    }
}
