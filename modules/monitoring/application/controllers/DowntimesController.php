<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Controllers;

use Icinga\Data\Filter\Filter;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteDowntimesCommandForm;
use Icinga\Web\Url;

/**
 * Display detailed information about downtimes
 */
class DowntimesController extends Controller
{
    /**
     * The downtimes view
     *
     * @var \Icinga\Module\Monitoring\DataView\Downtime
     */
    protected $downtimes;

    /**
     * Filter from request
     *
     * @var Filter
     */
    protected $filter;

    /**
     * Fetch all downtimes matching the current filter and add tabs
     */
    public function init()
    {
        $this->filter = Filter::fromQueryString(str_replace(
            'downtime_id',
            'downtime_internal_id',
            (string) $this->params
        ));
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
        ))->addFilter($this->filter);
        $this->applyRestriction('monitoring/filter/objects', $query);

        $this->downtimes = $query;

        $this->getTabs()->add(
            'downtimes',
            array(
                'icon'  => 'plug',
                'label' => $this->translate('Downtimes') . sprintf(' (%d)', $query->count()),
                'title' => $this->translate('Display detailed information about multiple downtimes.'),
                'url'   =>'monitoring/downtimes/show'
            )
        )->activate('downtimes');
    }

    /**
     * Display the detail view for a downtime list
     */
    public function showAction()
    {
        $this->view->downtimes = $this->downtimes;
        $this->view->listAllLink = Url::fromPath('monitoring/list/downtimes')
            ->setQueryString($this->filter->toQueryString());
        $this->view->removeAllLink = Url::fromPath('monitoring/downtimes/delete-all')->setParams($this->params);
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
            $this->downtimes->count()
        ));
        $delDowntimeForm->setRedirectUrl(Url::fromPath('monitoring/list/downtimes'));
        $delDowntimeForm->setDowntimes($this->downtimes->fetchAll())->handleRequest();
        $this->view->delAllDowntimeForm = $delDowntimeForm;
    }
}
