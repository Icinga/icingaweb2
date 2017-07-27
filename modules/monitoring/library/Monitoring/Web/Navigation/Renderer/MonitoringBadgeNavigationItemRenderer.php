<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Navigation\Renderer;

use Exception;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filterable;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Web\Navigation\Renderer\BadgeNavigationItemRenderer;

/**
 * Render generic DataView columns as badges in menu items
 *
 * It is possible to configure the class of the rendered badge as option 'class', the
 * columns to fetch using the option 'columns' and the DataView from which the columns
 * will be fetched using the option 'dataview'.
 */
class MonitoringBadgeNavigationItemRenderer extends BadgeNavigationItemRenderer
{
    /**
     * Cached count
     *
     * @var int
     */
    protected $count;

    /**
     * Caches the responses for all executed summaries
     *
     * @var array
     */
    protected static $summaries = array();

    /**
     * Accumulates all needed columns for a view to allow fetching the needed columns in
     * one single query
     *
     * @var array
     */
    protected static $dataViews = array();

    /**
     * The dataview referred to by the navigation item
     *
     * @var string
     */
    protected $dataView;

    /**
     * The columns and titles displayed in the badge
     *
     * @var array
     */
    protected $columns;

    /**
     * Set the dataview referred to by the navigation item
     *
     * @param   string  $dataView
     *
     * @return  $this
     */
    public function setDataView($dataView)
    {
        $this->dataView = $dataView;
        return $this;
    }

    /**
     * Return the dataview referred to by the navigation item
     *
     * @return  string
     */
    public function getDataView()
    {
        return $this->dataView;
    }

    /**
     * Set the columns and titles displayed in the badge
     *
     * @param   array   $columns
     *
     * @return  $this
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Return the columns and titles displayed in the badge
     *
     * @return  array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Apply a restriction on the given data view
     *
     * @param   string      $restriction    The name of restriction
     * @param   Filterable  $filterable     The filterable to restrict
     *
     * @return  Filterable  The filterable
     */
    protected static function applyRestriction($restriction, Filterable $filterable)
    {
        $restrictions = Filter::matchAny();
        foreach (Auth::getInstance()->getRestrictions($restriction) as $filter) {
            $restrictions->addFilter(Filter::fromQueryString($filter));
        }
        $filterable->applyFilter($restrictions);
        return $filterable;
    }

    /**
     * Fetch the dataview from the database
     *
     * @return  object
     */
    protected function fetchDataView()
    {
        $summary = MonitoringBackend::instance()->select()->from(
            $this->getDataView(),
            array_keys($this->getColumns())
        );
        static::applyRestriction('monitoring/filter/objects', $summary);
        return $summary->fetchRow();
    }

    /**
     * {@inheritdoc}
     */
    public function getCount()
    {
        if ($this->count === null) {
            try {
                $summary = $this->fetchDataView();
            } catch (Exception $e) {
                Logger::debug($e);
                $this->count = 1;
                $this->state = static::STATE_UNKNOWN;
                $this->title = $e->getMessage();
                return $this->count;
            }
            $count = 0;
            $titles = array();
            foreach ($this->getColumns() as $column => $title) {
                if (isset($summary->$column) && $summary->$column > 0) {
                    $titles[] = sprintf($title, $summary->$column);
                    $count += $summary->$column;
                }
            }
            $this->count = $count;
            $this->title = implode('. ', $titles);
        }

        return $this->count;
    }
}
