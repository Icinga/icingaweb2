<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

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
     * Apply a restriction of the authenticated on the given filterable
     *
     * @param   string      $name       Name of the restriction
     * @param   Filterable  $filterable Filterable to restrict
     *
     * @return  Filterable  The filterable having the restriction applied
     */
    protected function applyRestriction($name, Filterable $filterable)
    {
        $filterable->applyFilter($this->getRestriction($name));
        return $filterable;
    }

    /**
     * Get a restriction of the authenticated
     *
     * @param   string $name        Name of the restriction
     *
     * @return  Filter|null         Filter object or null if the authenticated user is not restricted
     * @throws  ConfigurationError  If the restriction contains invalid filter columns
     */
    protected function getRestriction($name)
    {
        $restriction = Filter::matchAny();
        $restriction->setAllowedFilterColumns(array(
            'host_name',
            'hostgroup_name',
            'instance_name',
            'service_description',
            'servicegroup_name',
            function ($c) {
                return preg_match('/^_(?:host|service)_/i', $c);
            }
        ));
        foreach ($this->getRestrictions($name) as $filter) {
            if ($filter === '*') {
                return Filter::matchAny();
            }
            try {
                $restriction->addFilter(Filter::fromQueryString($filter));
            } catch (QueryException $e) {
                throw new ConfigurationError(
                    $this->translate(
                        'Cannot apply restriction %s using the filter %s. You can only use the following columns: %s'
                    ),
                    $name,
                    $filter,
                    implode(', ', array(
                        'instance_name',
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
        return $restriction;
    }
}

