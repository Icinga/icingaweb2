<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filterable;
use Icinga\File\Csv;
use Icinga\Web\Controller as IcingaWebController;
use Icinga\Web\Url;

/**
 * Base class for all monitoring action controller
 */
class Controller extends IcingaWebController
{
    /**
     * The backend used for this controller
     *
     * @var Backend
     */
    protected $backend;

    protected function moduleInit()
    {
        $this->backend = Backend::createBackend($this->_getParam('backend'));
        $this->view->url = Url::fromRequest();
    }

    protected function handleFormatRequest($query)
    {
        if ($this->_getParam('format') === 'sql') {
            echo '<pre>'
                . htmlspecialchars(wordwrap($query->dump()))
                . '</pre>';
            exit;
        }
        if ($this->_getParam('format') === 'json'
            || $this->_request->getHeader('Accept') === 'application/json') {
            header('Content-type: application/json');
            echo json_encode($query->getQuery()->fetchAll());
            exit;
        }
        if ($this->_getParam('format') === 'csv'
            || $this->_request->getHeader('Accept') === 'text/csv') {
            Csv::fromQuery($query)->dump();
            exit;
        }
    }

    /**
     * Apply a restriction on the given data view
     *
     * @param   string      $restriction    The name of restriction
     * @param   Filterable  $filterable     The filterable to restrict
     *
     * @return  Filterable  The filterable
     */
    protected function applyRestriction($restriction, Filterable $view)
    {
        foreach ($this->getRestrictions($restriction) as $filter) {
            $view->applyFilter(Filter::fromQueryString($filter));
        }
        return $view;
    }
}

