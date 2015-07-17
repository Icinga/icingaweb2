<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring;

use Icinga\Exception\ConfigurationError;
use Icinga\Exception\QueryException;
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
     * @param   Filterable  $view           The filterable to restrict
     *
     * @return  Filterable  The filterable
     */
    protected function applyRestriction($restriction, Filterable $view)
    {
        $restrictions = Filter::matchAny();
        $restrictions->setAllowedFilterColumns(array(
            'host_name',
            'hostgroup_name',
            'service_description',
            'servicegroup_name',
            function ($c) {
                return preg_match('/^_(?:host|service)_/', $c);
            }
        ));

        foreach ($this->getRestrictions($restriction) as $filter) {
            try {
                $restrictions->addFilter(Filter::fromQueryString($filter));
            } catch (QueryException $e) {
                throw new ConfigurationError(
                    $this->translate(
                        'Cannot apply restriction %s using the filter %s. You can only use the following columns: %s'
                    ),
                    $restriction,
                    $filter,
                    implode(', ', array(
                        'host_name',
                        'hostgroup_name',
                        'service_description',
                        'servicegroup_name',
                        '_(host|service)_<customvar-name>'
                    )),
                    $e
                );
            }
        }

        $view->applyFilter($restrictions);
        return $view;
    }
}

