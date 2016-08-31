<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Controllers;

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteDowntimeCommandForm;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\MenuAction;

/**
 * Display detailed information about a downtime
 */
class DowntimeController extends Controller
{
    /**
     * The fetched downtime
     *
     * @var object
     */
    protected $downtime;

    /**
     * Fetch the downtime matching the given id and add tabs
     */
    public function init()
    {
        $downtimeId = $this->params->getRequired('downtime_id');

        $query = $this->backend->select()->from('downtime', array(
            'id'              => 'downtime_internal_id',
            'objecttype'      => 'object_type',
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
            'name'            => 'downtime_name',
            'host_state',
            'service_state',
            'host_name',
            'service_description',
            'host_display_name',
            'service_display_name'
        ))->where('downtime_internal_id', $downtimeId);
        $this->applyRestriction('monitoring/filter/objects', $query);

        if (false === $this->downtime = $query->fetchRow()) {
            $this->httpNotFound($this->translate('Downtime not found'));
        }

        $this->getTabs()->add(
            'downtime',
            array(

                'icon'  => 'plug',
                'label' => $this->translate('Downtime'),
                'title' => $this->translate('Display detailed information about a downtime.'),
                'url'   =>'monitoring/downtimes/show'
            )
        )->activate('downtime')->extend(new DashboardAction())->extend(new MenuAction());
    }

    /**
     * Display the detail view for a downtime
     */
    public function showAction()
    {
        $isService = isset($this->downtime->service_description);
        $this->view->downtime = $this->downtime;
        $this->view->isService = $isService;
        $this->view->listAllLink = Url::fromPath('monitoring/list/downtimes');
        $this->view->showHostLink = Url::fromPath('monitoring/host/show')->setParam('host', $this->downtime->host_name);
        $this->view->showServiceLink = Url::fromPath('monitoring/service/show')
                ->setParam('host', $this->downtime->host_name)
                ->setParam('service', $this->downtime->service_description);
        $this->view->stateName = $isService ? Service::getStateText($this->downtime->service_state)
            : Host::getStateText($this->downtime->host_state);

        if ($this->hasPermission('monitoring/command/downtime/delete')) {
            $form = new DeleteDowntimeCommandForm();
            $form
                ->populate(array(
                    'downtime_id'           => $this->downtime->id,
                    'downtime_is_service'   => $isService,
                    'downtime_name'         => $this->downtime->name,
                    'redirect'              => Url::fromPath('monitoring/list/downtimes'),
                ))
                ->handleRequest();
            $this->view->delDowntimeForm = $form;
        }
    }
}
