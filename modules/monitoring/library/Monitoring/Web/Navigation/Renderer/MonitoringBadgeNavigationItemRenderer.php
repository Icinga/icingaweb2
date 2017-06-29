<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Navigation\Renderer;

use Exception;
use Icinga\Authentication\Auth;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filterable;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Web\Navigation\Renderer\SummaryNavigationItemRenderer;

/**
 * Render generic dataView columns as badges in MenuItems
 *
 * Renders numeric data view column values into menu item badges, fully configurable
 * and with a caching mechanism to prevent needless requests to the same data view.
 *
 * It is possible to configure the class of the rendered badge as option 'class', the
 * column to fetch using the option 'column' and the dataView from which the columns
 * will be fetched using the option 'dataView'.
 */
class MonitoringBadgeNavigationItemRenderer extends SummaryNavigationItemRenderer
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
     * {@inheritdoc}
     */
    public function init()
    {
        // clear the outdated summary cache, since new columns are being added. Optimally all menu item are constructed
        // before any rendering is going on to avoid trashing too man old requests
        if (isset(self::$summaries[$this->dataView])) {
            unset(self::$summaries[$this->dataView]);
        }

        // add the new columns to this view
        if (! isset(self::$dataViews[$this->dataView])) {
            self::$dataViews[$this->dataView] = array();
        }

        foreach ($this->columns as $column => $title) {
            if (! array_search($column, self::$dataViews[$this->dataView])) {
                self::$dataViews[$this->dataView][] = $column;
            }
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
     * Fetch the dataview from the database or access cache
     *
     * @param   string  $view
     *
     * @return  object
     */
    protected static function summary($view)
    {
        if (! isset(self::$summaries[$view])) {
            $summary = MonitoringBackend::instance()->select()->from(
                $view,
                self::$dataViews[$view]
            );
            static::applyRestriction('monitoring/filter/objects', $summary);
            self::$summaries[$view] = $summary->fetchRow();
        }

        return self::$summaries[$view];
    }

    /**
     * {@inheritdoc}
     */
    public function getCount()
    {
        if ($this->count === null) {
            try {
                $summary = self::summary($this->getDataView());
            } catch (Exception $_) {
                $this->count = 0;
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
