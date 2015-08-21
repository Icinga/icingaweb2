<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Menu;

use Icinga\Authentication\Auth;
use Icinga\Data\ConfigObject;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filterable;
use Icinga\Web\Menu;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Web\Menu\BadgeMenuItemRenderer;

/**
 * Render generic dataView columns as badges in MenuItems
 *
 * Renders numeric data view column values into menu item badges, fully configurable
 * and with a caching mechanism to prevent needless requests to the same data view
 */
class MonitoringBadgeMenuItemRenderer extends BadgeMenuItemRenderer
{
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
     * The data view displayed by this menu item
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
     * The titles that will be used to render this menu item tooltip
     *
     * @var String[]
     */
    protected $titles;

    /**
     * The class of the badge element
     *
     * @var string
     */
    protected $state;

    /**
     * Create a new instance of ColumnMenuItemRenderer
     *
     * It is possible to configure the class of the rendered badge as option 'class', the column
     * to fetch using the option 'column' and the dataView from which the columns will be
     * fetched using the option 'dataView'.
     *
     * @param $configuration ConfigObject   The configuration to use
     */
    public function __construct(ConfigObject $configuration)
    {
        parent::__construct($configuration);

        $this->columns  = $configuration->get('columns');
        $this->state    = $configuration->get('state');
        $this->dataView = $configuration->get('dataView');

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
            $this->titles[$column] = $title;
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
     * Fetch the response from the database or access cache
     *
     * @param $view
     *
     * @return null
     * @throws \Icinga\Exception\ConfigurationError
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
        return isset(self::$summaries[$view]) ? self::$summaries[$view] : null;
    }

    /**
     * Defines the color of the badge
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * The amount of items to display in the badge
     *
     * @return int
     */
    public function getCount()
    {
        $sum = self::summary($this->dataView);
        $count = 0;

        foreach ($this->columns as $col => $title) {
            if (isset($sum->$col)) {
                $count += $sum->$col;
            }
        }
        return $count;
    }

    /**
     * The tooltip title
     *
     * @return string
     */
    public function getTitle()
    {
        $titles = array();
        $sum = $this->summary($this->dataView);
        foreach ($this->columns as $column => $value) {
            if (isset($sum->$column) && $sum->$column > 0) {
                $titles[] = sprintf($this->titles[$column], $sum->$column);
            }
        }
        return implode(', ', $titles);
    }
}
