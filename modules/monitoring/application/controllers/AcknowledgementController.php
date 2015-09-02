<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Module\Monitoring\Controller;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Module\Monitoring\DataView\DataView;

class Monitoring_AcknowledgementController extends Controller
{
    /**
     * Create full report
     */
    public function indexAction()
    {
        $this->getTabs()->add(
            'acknowledgement',
            array(
                'title' => $this->translate(
                    'Show acknowledgements'
                ),
                'label' => $this->translate('Acknowledgements'),
                'url'   => Url::fromRequest()
            )
        )->extend(new DashboardAction())->activate('acknowledgement');
        $this->view->title = $this->translate('Acknowledgement');
        $this->setAutorefreshInterval(15);
        $query = $this->backend->select()->from(
            'acknowledgement',
            array(
                'acknowledgement_id',
                'instance_id',
                'entry_time',
                'object_id',
                'state',
                'author_name',
                'comment_data',
                'is_sticky',
                'persistent_comment',
                'acknowledgement_id',
                'notify_contacts',
                'end_time',
                'endpoint_object_id',
                'acknowledgement_is_service',
                'service',
                'host'
            )
        );

        $this->applyRestriction('monitoring/filter/objects', $query);
        $this->filterQuery($query);
        $this->view->acknowledgements = $query;

        $this->setupLimitControl()
            ->setupPaginationControl($this->view->acknowledgements)
            ->setupSortControl(array(
                'entry_time' => $this->translate('Entry Time'),
                'end_time' => $this->translate('End Time'),
                'state' => $this->translate('Object State'),
                'author_name' => $this->translate('Author Name')
            ), $this->view->acknowledgements);
    }

    /**
     * Apply filters on a DataView
     *
     * @param DataView  $dataView       The DataView to apply filters on
     *
     * @return DataView $dataView
     */
    protected function filterQuery(DataView $dataView)
    {
        $this->setupFilterControl($dataView);
        $this->handleFormatRequest($dataView);
        return $dataView;
    }
}
