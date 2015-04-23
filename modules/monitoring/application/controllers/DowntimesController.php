<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Data\Filter\Filter;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteDowntimeCommandForm;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;


/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Display detailed information about a downtime
 */
class Monitoring_DowntimesController extends Controller
{
    protected $downtimes;
    
    protected $filter;
    
    /**
     * Add tabs
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
        $this->getTabs()
            ->add(
                'downtimes',
                array(
                    'title' => $this->translate(
                        'Display detailed information about multiple downtimes.'
                    ),
                    'icon' => 'plug',
                    'label' => $this->translate('Downtimes'),
                    'url'   =>'monitoring/downtimes/show'
                )
        )->activate('downtimes')->extend(new DashboardAction());
        
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
    
    public function showAction()
    {
        if (false === $this->downtimes) {
            return;
        }
        $this->view->downtimes = $this->downtimes;
        $this->view->listAllLink = Url::fromPath('monitoring/list/downtimes')
                ->setQueryString($this->filter->toQueryString());
        $this->view->removeAllLink = Url::fromPath('monitoring/downtimes/removeAll')
                ->setParams($this->params);
    }
    
    public function removeAllAction()
    {
        $delDowntimeForm = new DeleteDowntimeCommandForm();
        $delDowntimeForm->setObjects($this->downtimes);
        $delDowntimeForm->populate(
            array(
                'downtime_id' => $this->downtime->id, 
                'redirect' => Url::fromPath('monitoring/list/downtimes')
            )
        );
        if (! $this->isService) {
            $delDowntimeForm->setAction(
                $this->view->url('monitoring/host/delete-downtime',
                array('host' => $this->downtime->host_name))
            );
        } else {
            $delDowntimeForm->setAction(
                $this->view->url('monitoring/service/delete-downtime', array(
                    'host'      => $this->downtime->host_name,
                    'service'   => $this->downtime->service_description
                ))
            );
        }
        return $delDowntimeForm;
    }
}
