<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Data\Filter\Filter;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteDowntimesCommandForm;
use Icinga\Web\Url;

/**
 * Display detailed information about a downtime
 */
class Monitoring_DowntimesController extends Controller
{
    /**
     * The fetched downtimes
     *
     * @var array
     */
    protected $downtimes;

    /**
     * A filter matching all current downtimes
     *
     * @var Filter
     */
    protected $filter;

    /**
     * Fetch all downtimes matching the current filter and add tabs
     *
     * @throws Zend_Controller_Action_Exception
     */
    public function init()
    {
        $this->filter = Filter::fromQueryString(str_replace(
            'downtime_id',
            'downtime_internal_id',
            (string)$this->params
        ));
        $this->downtimes = $this->backend->select()->from('downtime', array(
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
        ))->addFilter($this->filter)->getQuery()->fetchAll();

        if (false === $this->downtimes) {
            throw new Zend_Controller_Action_Exception(
                $this->translate('Downtime not found')
            );
        }
        
        $this->getTabs()->add(
            'downtimes',
            array(
                'title' => $this->translate(
                    'Display detailed information about multiple downtimes.'
                ),
                'icon'  => 'plug',
                'label' => $this->translate('Downtimes') . sprintf(' (%d)', count($this->downtimes)),
                'url'   =>'monitoring/downtimes/show'
            )
        )->activate('downtimes');
        
        foreach ($this->downtimes as $downtime) {
            if (isset($downtime->service_description)) {
                $downtime->isService = true;
            } else {
                $downtime->isService = false;
            }
            
            if ($downtime->isService) {
                $downtime->stateText = Service::getStateText($downtime->service_state);
            } else {
                $downtime->stateText = Host::getStateText($downtime->host_state);
            }
        }
    }

    /**
     * Display the detail view for a downtime list
     */
    public function showAction()
    {
        $this->view->downtimes = $this->downtimes;
        $this->view->listAllLink = Url::fromPath('monitoring/list/downtimes')
                ->setQueryString($this->filter->toQueryString());
        $this->view->removeAllLink = Url::fromPath('monitoring/downtimes/delete-all')
                ->setParams($this->params);
    }

    /**
     * Display the form for removing a downtime list
     */
    public function deleteAllAction()
    {
        $this->assertPermission('monitoring/command/downtime/delete');
        $this->view->downtimes = $this->downtimes;
        $this->view->listAllLink = Url::fromPath('monitoring/list/downtimes')
                ->setQueryString($this->filter->toQueryString());
        $delDowntimeForm = new DeleteDowntimesCommandForm();
        $delDowntimeForm->setTitle($this->view->translate('Remove all Downtimes'));
        $delDowntimeForm->addDescription(sprintf(
            $this->translate('Confirm removal of %d downtimes.'),
            count($this->downtimes)
        ));
        $delDowntimeForm->setRedirectUrl(Url::fromPath('monitoring/list/downtimes'));
        $delDowntimeForm->setDowntimes($this->downtimes)->handleRequest();
        $this->view->delDowntimeForm = $delDowntimeForm;
    }
}