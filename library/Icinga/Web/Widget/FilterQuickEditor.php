<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterChain;
use Icinga\Data\Filter\FilterExpression;
use Icinga\Web\Url;
use Icinga\Web\UrlParams;
use Icinga\Web\View;
use stdClass;

/**
 * A set of links for alternating the current filter quickly
 */
class FilterQuickEditor extends AbstractWidget
{
    /**
     * The managed filter expression sets
     *
     * @var stdClass[]
     */
    protected $expressionSets = array();

    /**
     * Factory
     *
     * @param   array   $expressionSets     The expression set to add
     *
     * @return  static
     */
    public static function create(array $expressionSets = array())
    {
        $new = new static();
        foreach ($expressionSets as $expressionSet) {
            call_user_func_array(array($new, 'addToggle'), $expressionSet);
        }
        return $new;
    }

    /**
     * Add a toggleable filter expression set
     *
     * @param   FilterExpression|FilterExpression[] $expressionSet  The expression set to add
     * @param   string                              $textOn         Text saying that this expressions will be included
     * @param   string                              $textOff        Text saying that this expressions will be excluded
     *
     * @return  $this
     */
    public function addToggle($expressionSet, $textOn, $textOff)
    {
        $this->expressionSets[] = (object) array(
            'filter'    => is_array($expressionSet) ? $expressionSet : array($expressionSet),
            'on'        => $textOn,
            'off'       => $textOff
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $currentUrl = Url::fromRequest();
        $currentFilter = Filter::fromQueryString((string) $currentUrl->getParams());
        /** @var View $view */
        $view = $this->view();
        $links = array();

        foreach ($this->expressionSets as $expressionSet) {
            $alternatedUrl = clone $currentUrl;
            if ($this->isExpressionInFilter($expressionSet->filter, $currentFilter)) {
                $newFilter = $this->getFilterWithoutExpression($currentFilter, $expressionSet->filter);
                $links[] = $view->qlink(
                    $expressionSet->off,
                    $alternatedUrl->setParams(
                        $newFilter === null
                            ? new UrlParams()
                            : UrlParams::fromQueryString($newFilter->toQueryString())
                    )
                );
            } else {
                $links[] = $view->qlink(
                    $expressionSet->on,
                    $alternatedUrl->setParams(UrlParams::fromQueryString(
                        Filter::chain('AND', $expressionSet->filter)->andFilter(clone $currentFilter)->toQueryString()
                    ))
                );
            }
        }

        return '<div class="filter">' . implode('&emsp;', $links) . '</div>';
    }

    /**
     * Return whether the given filter is equal to or contains at least one of the given expressions
     *
     * @param   FilterExpression[]  $expressionSet
     * @param   Filter              $filter
     *
     * @return  bool
     */
    protected function isExpressionInFilter(array $expressionSet, Filter $filter)
    {
        if ($filter->isExpression()) {
            $filterQueryString = $filter->toQueryString();
            foreach ($expressionSet as $expression) {
                if ($filterQueryString === $expression->toQueryString()) {
                    return true;
                }
            }
            return false;
        }

        /** @var FilterChain $filter */
        foreach ($filter->filters() as $subFilter) {
            if ($this->isExpressionInFilter($expressionSet, $subFilter)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return a deep copy of the given filter with the given filter expressions removed
     * or null if the filter consists of only the expressions
     *
     * @param   Filter              $filter
     * @param   FilterExpression[]  $expressionSet
     *
     * @return  Filter|null
     */
    protected function getFilterWithoutExpression(Filter $filter, array $expressionSet)
    {
        if ($filter->isExpression()) {
            $filterQueryString = $filter->toQueryString();
            foreach ($expressionSet as $expression) {
                if ($filterQueryString === $expression->toQueryString()) {
                    return null;
                }
            }
            return clone $filter;
        }

        /** @var FilterChain $filter */
        $clone = Filter::chain($filter->getOperatorName());
        foreach ($filter->filters() as $subFilter) {
            $filteredFilter = $this->getFilterWithoutExpression($subFilter, $expressionSet);
            if ($filteredFilter !== null) {
                $clone->addFilter($filteredFilter);
            }
        }
        return $clone->isEmpty() && ! $filter->isEmpty() ? null : $clone;
    }
}
