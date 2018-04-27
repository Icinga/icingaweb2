<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Data\Filterable;
use Icinga\Data\FilterColumns;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;
use Icinga\Data\Filter\FilterChain;
use Icinga\Data\Filter\FilterOr;
use Icinga\Web\Url;
use Icinga\Application\Icinga;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Notification;
use Exception;

/**
 * Filter
 */
class FilterEditor extends AbstractWidget
{
    /**
     * The filter
     *
     * @var Filter
     */
    private $filter;

    /**
     * The query to filter
     *
     * @var Filterable
     */
    protected $query;

    protected $url;

    protected $addTo;

    protected $cachedColumnSelect;

    protected $preserveParams = array();

    protected $preservedParams = array();

    protected $preservedUrl;

    protected $ignoreParams = array();

    protected $searchColumns;

    /**
     * @var string
     */
    private $selectedIdx;

    /**
     * Whether the filter control is visible
     *
     * @var bool
     */
    protected $visible = true;

    /**
     * Create a new FilterWidget
     *
     * @param Filter $filter Your filter
     */
    public function __construct($props)
    {
        if (array_key_exists('filter', $props)) {
            $this->setFilter($props['filter']);
        }
        if (array_key_exists('query', $props)) {
            $this->setQuery($props['query']);
        }
    }

    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;
        return $this;
    }

    public function getFilter()
    {
        if ($this->filter === null) {
            $this->filter = Filter::fromQueryString((string) $this->url()->getParams());
        }
        return $this->filter;
    }

    /**
     * Set columns to search in
     *
     * @param array $searchColumns
     *
     * @return $this
     */
    public function setSearchColumns(array $searchColumns = null)
    {
        $this->searchColumns = $searchColumns;
        return $this;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    protected function url()
    {
        if ($this->url === null) {
            $this->url = Url::fromRequest();
        }
        return $this->url;
    }

    protected function preservedUrl()
    {
        if ($this->preservedUrl === null) {
            $this->preservedUrl = $this->url()->with($this->preservedParams);
        }
        return $this->preservedUrl;
    }

    /**
     * Set the query to filter
     *
     * @param   Filterable  $query
     *
     * @return  $this
     */
    public function setQuery(Filterable $query)
    {
        $this->query = $query;
        return $this;
    }

    public function ignoreParams()
    {
        $this->ignoreParams = func_get_args();
        return $this;
    }

    public function preserveParams()
    {
        $this->preserveParams = func_get_args();
        return $this;
    }

    /**
     * Get whether the filter control is visible
     *
     * @return  bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * Set whether the filter control is visible
     *
     * @param   bool    $visible
     *
     * @return  $this
     */
    public function setVisible($visible)
    {
        $this->visible = (bool) $visible;

        return $this;
    }

    protected function redirectNow($url)
    {
        $response = Icinga::app()->getFrontController()->getResponse();
        $response->redirectAndExit($url);
    }

    protected function mergeRootExpression($filter, $column, $sign, $expression)
    {
        $found = false;
        if ($filter->isChain() && $filter->getOperatorName() === 'AND') {
            foreach ($filter->filters() as $f) {
                if ($f->isExpression()
                    && $f->getColumn() === $column
                    && $f->getSign() === $sign
                ) {
                    $f->setExpression($expression);
                    $found = true;
                    break;
                }
            }
        } elseif ($filter->isExpression()) {
            if ($filter->getColumn() === $column && $filter->getSign() === $sign) {
                $filter->setExpression($expression);
                $found = true;
            }
        }
        if (! $found) {
            $filter = $filter->andFilter(
                Filter::expression($column, $sign, $expression)
            );
        }
        return $filter;
    }

    protected function resetSearchColumns(Filter &$filter)
    {
        if ($filter->isChain()) {
            $filters = &$filter->filters();
            if (!($empty = empty($filters))) {
                foreach ($filters as $k => &$f) {
                    if (false === $this->resetSearchColumns($f)) {
                        unset($filters[$k]);
                    }
                }
            }
            return $empty || !empty($filters);
        }
        return $filter->isExpression() ? !(
            in_array($filter->getColumn(), $this->searchColumns)
            &&
            $filter->getSign() === '='
        ) : true;
    }

    public function handleRequest($request)
    {
        $this->setUrl($request->getUrl()->without($this->ignoreParams));
        $params = $this->url()->getParams();

        $preserve = array();
        foreach ($this->preserveParams as $key) {
            if (null !== ($value = $params->shift($key))) {
                $preserve[$key] = $value;
            }
        }
        $this->preservedParams = $preserve;

        $add    = $params->shift('addFilter');
        $remove = $params->shift('removeFilter');
        $strip  = $params->shift('stripFilter');
        $modify = $params->shift('modifyFilter');



        $search = null;
        if ($request->isPost()) {
            $search = $request->getPost('q');
        }

        if ($search === null) {
            $search = $params->shift('q');
        }

        $filter = $this->getFilter();

        if ($search !== null) {
            if (strpos($search, '=') !== false) {
                list($k, $v) = preg_split('/=/', $search);
                $filter = $this->mergeRootExpression($filter, trim($k), '=', ltrim($v));
            } else {
                if ($this->searchColumns === null && $this->query instanceof FilterColumns) {
                    $this->searchColumns = $this->query->getSearchColumns($search);
                }

                if (! empty($this->searchColumns)) {
                    if (! $this->resetSearchColumns($filter)) {
                        $filter = Filter::matchAll();
                    }
                    $filters = array();
                    $search = ltrim($search);
                    foreach ($this->searchColumns as $searchColumn) {
                        $filters[] = Filter::expression($searchColumn, '=', "*$search*");
                    }
                    $filter = $filter->andFilter(new FilterOr($filters));
                } else {
                    Notification::error(mt('monitoring', 'Cannot search here'));
                    return $this;
                }
            }

            $url = $this->url()->setQueryString(
                $filter->toQueryString()
            )->addParams($preserve);
            if ($modify) {
                $url->getParams()->add('modifyFilter');
            }
            $this->redirectNow($url);
        }

        if ($remove) {
            $redirect = $this->url();
            if ($filter->getById($remove)->isRootNode()) {
                $redirect->setQueryString('');
            } else {
                $filter->removeId($remove);
                $redirect->setQueryString($filter->toQueryString())->getParams()->add('modifyFilter');
            }
            $this->redirectNow($redirect->addParams($preserve));
        }

        if ($strip) {
            $redirect = $this->url();
            $subId = $strip . '-1';
            if ($filter->getId() === $strip) {
                $filter = $filter->getById($strip . '-1');
            } else {
                $filter->replaceById($strip, $filter->getById($strip . '-1'));
            }
            $redirect->setQueryString($filter->toQueryString())->getParams()->add('modifyFilter');
            $this->redirectNow($redirect->addParams($preserve));
        }


        if ($modify) {
            if ($request->isPost()) {
                if ($request->get('cancel') === 'Cancel') {
                    $this->redirectNow($this->preservedUrl()->without('modifyFilter'));
                }
                if ($request->get('formUID') === 'FilterEditor') {
                    $filter = $this->applyChanges($request->getPost());
                    $url = $this->url()->setQueryString($filter->toQueryString())->addParams($preserve);
                    $url->getParams()->add('modifyFilter');

                    $addFilter = $request->get('add_filter');
                    if ($addFilter !== null) {
                        $url->setParam('addFilter', $addFilter);
                    }

                    $removeFilter = $request->get('remove_filter');
                    if ($removeFilter !== null) {
                        $url->setParam('removeFilter', $removeFilter);
                    }

                    $this->redirectNow($url);
                }
            }
            $this->url()->getParams()->add('modifyFilter');
        }

        if ($add) {
            $this->addFilterToId($add);
        }

        if ($this->query !== null && $request->isGet()) {
            $this->query->applyFilter($this->getFilter());
        }

        return $this;
    }

    protected function select($name, $list, $selected, $attributes = null)
    {
        $view = $this->view();
        if ($attributes === null) {
            $attributes = '';
        } else {
            $attributes = $view->propertiesToString($attributes);
        }
        $html = sprintf(
            '<select name="%s"%s class="autosubmit">' . "\n",
            $view->escape($name),
            $attributes
        );

        foreach ($list as $k => $v) {
            $active = '';
            if ($k === $selected) {
                $active = ' selected="selected"';
            }
            $html .= sprintf(
                '  <option value="%s"%s>%s</option>' . "\n",
                $view->escape($k),
                $active,
                $view->escape($v)
            );
        }
        $html .= '</select>' . "\n\n";
        return $html;
    }

    protected function addFilterToId($id)
    {
        $this->addTo = $id;
        return $this;
    }

    protected function removeIndex($idx)
    {
        $this->selectedIdx = $idx;
        return $this;
    }

    protected function removeLink(Filter $filter)
    {
        return "<button type='submit' name='remove_filter' value='{$filter->getId()}'>"
            . $this->view()->icon('trash', t('Remove this part of your filter'))
            . '</button>';
    }

    protected function addLink(Filter $filter)
    {
        return "<button type='submit' name='add_filter' value='{$filter->getId()}'>"
            . $this->view()->icon('plus', t('Add another filter'))
            . '</button>';
    }

    protected function stripLink(Filter $filter)
    {
        return $this->view()->qlink(
            '',
            $this->preservedUrl()->with('stripFilter', $filter->getId()),
            null,
            array(
                'icon'  => 'minus',
                'title' => t('Strip this filter')
            )
        );
    }

    protected function cancelLink()
    {
        return $this->view()->qlink(
            '',
            $this->preservedUrl()->without('addFilter'),
            null,
            array(
                'icon'  => 'cancel',
                'title' => t('Cancel this operation')
            )
        );
    }

    protected function renderFilter($filter, $level = 0)
    {
        if ($level === 0 && $filter->isChain() && $filter->isEmpty()) {
            return '<ul class="datafilter"><li class="active">' . $this->renderNewFilter() . '</li></ul>';
        }

        if ($filter instanceof FilterChain) {
            return $this->renderFilterChain($filter, $level);
        } elseif ($filter instanceof FilterExpression) {
            return $this->renderFilterExpression($filter);
        } else {
            throw new ProgrammingError('Got a Filter being neither expression nor chain');
        }
    }

    protected function renderFilterChain(FilterChain $filter, $level)
    {
        $html = '<span class="handle"> </span>'
              . $this->selectOperator($filter)
              . $this->removeLink($filter)
              . ($filter->count() === 1 ? $this->stripLink($filter) : '')
              . $this->addLink($filter);

        if ($filter->isEmpty() && ! $this->addTo) {
            return $html;
        }

        $parts = array();
        foreach ($filter->filters() as $f) {
            $parts[] = '<li>' . $this->renderFilter($f, $level + 1) . '</li>';
        }

        if ($this->addTo && $this->addTo == $filter->getId()) {
            $parts[] = '<li style="background: #ffb">' . $this->renderNewFilter() .$this->cancelLink(). '</li>';
        }

        $class = $level === 0 ? ' class="datafilter"' : '';
        $html .= sprintf(
            "<ul%s>\n%s</ul>\n",
            $class,
            implode("", $parts)
        );
        return $html;
    }

    protected function renderFilterExpression(FilterExpression $filter)
    {
        if ($this->addTo && $this->addTo === $filter->getId()) {
            return
                   preg_replace(
                       '/ class="autosubmit"/',
                       ' class="autofocus"',
                       $this->selectOperator()
                   )
                  . '<ul><li>'
                  . $this->selectColumn($filter)
                  . $this->selectSign($filter)
                  . $this->text($filter)
                  . $this->removeLink($filter)
                  . $this->addLink($filter)
                  . '</li><li class="active">'
                  . $this->renderNewFilter() .$this->cancelLink()
                  . '</li></ul>'
                  ;
        } else {
            return $this->selectColumn($filter)
                 . $this->selectSign($filter)
                 . $this->text($filter)
                 . $this->removeLink($filter)
                 . $this->addLink($filter)
                 ;
        }
    }

    protected function text(Filter $filter = null)
    {
        $value = $filter === null ? '' : $filter->getExpression();
        if (is_array($value)) {
            $value = '(' . implode('|', $value) . ')';
        }
        return sprintf(
            '<input type="text" name="%s" value="%s" />',
            $this->elementId('value', $filter),
            $this->view()->escape($value)
        );
    }

    protected function renderNewFilter()
    {
        $html = $this->selectColumn()
              . $this->selectSign()
              . $this->text();

        return preg_replace(
            '/ class="autosubmit"/',
            '',
            $html
        );
    }

    protected function arrayForSelect($array, $flip = false)
    {
        $res = array();
        foreach ($array as $k => $v) {
            if (is_int($k)) {
                $res[$v] = ucwords(str_replace('_', ' ', $v));
            } elseif ($flip) {
                $res[$v] = $k;
            } else {
                $res[$k] = $v;
            }
        }
        // sort($res);
        return $res;
    }

    protected function elementId($prefix, Filter $filter = null)
    {
        if ($filter === null) {
            return $prefix . '_new_' . ($this->addTo ?: '0');
        } else {
            return $prefix . '_' . $filter->getId();
        }
    }

    protected function selectOperator(Filter $filter = null)
    {
        $ops = array(
            'AND' => 'AND',
            'OR'  => 'OR',
            'NOT' => 'NOT'
        );

        return $this->select(
            $this->elementId('operator', $filter),
            $ops,
            $filter === null ? null : $filter->getOperatorName(),
            array('style' => 'width: 5em')
        );
    }

    protected function selectSign(Filter $filter = null)
    {
        $signs = array(
            '='  => '=',
            '!=' => '!=',
            '>'  => '>',
            '<'  => '<',
            '>=' => '>=',
            '<=' => '<=',
        );

        return $this->select(
            $this->elementId('sign', $filter),
            $signs,
            $filter === null ? null : $filter->getSign(),
            array('style' => 'width: 4em')
        );
    }

    public function setColumns(array $columns = null)
    {
        $this->cachedColumnSelect = $columns ? $this->arrayForSelect($columns) : null;
        return $this;
    }

    protected function selectColumn(Filter $filter = null)
    {
        $active = $filter === null ? null : $filter->getColumn();

        if ($this->cachedColumnSelect === null && $this->query === null) {
            return sprintf(
                '<input type="text" name="%s" value="%s" />',
                $this->elementId('column', $filter),
                $this->view()->escape($active) // Escape attribute?
            );
        }

        if ($this->cachedColumnSelect === null && $this->query instanceof FilterColumns) {
            $this->cachedColumnSelect = $this->arrayForSelect($this->query->getFilterColumns(), true);
            asort($this->cachedColumnSelect);
        } elseif ($this->cachedColumnSelect === null) {
            throw new ProgrammingError('No columns set nor does the query provide any');
        }

        $cols = $this->cachedColumnSelect;
        if ($active && !isset($cols[$active])) {
            $cols[$active] = str_replace('_', ' ', ucfirst(ltrim($active, '_')));
        }

        return $this->select($this->elementId('column', $filter), $cols, $active);
    }

    protected function applyChanges($changes)
    {
        $filter = $this->filter;
        $pairs = array();
        $addTo = null;
        $add = array();
        foreach ($changes as $k => $v) {
            if (preg_match('/^(column|value|sign|operator)((?:_new)?)_([\d-]+)$/', $k, $m)) {
                if ($m[2] === '_new') {
                    if ($addTo !== null && $addTo !== $m[3]) {
                        throw new \Exception('F...U');
                    }
                    $addTo = $m[3];
                    $add[$m[1]] = $v;
                } else {
                    $pairs[$m[3]][$m[1]] = $v;
                }
            }
        }

        $operators = array();
        foreach ($pairs as $id => $fs) {
            if (array_key_exists('operator', $fs)) {
                $operators[$id] = $fs['operator'];
            } else {
                $f = $filter->getById($id);
                $f->setColumn($fs['column']);
                if ($f->getSign() !== $fs['sign']) {
                    if ($f->isRootNode()) {
                        $filter = $f->setSign($fs['sign']);
                    } else {
                        $filter->replaceById($id, $f->setSign($fs['sign']));
                    }
                }
                $f->setExpression($fs['value']);
            }
        }

        krsort($operators, version_compare(PHP_VERSION, '5.4.0') >= 0 ? SORT_NATURAL : SORT_REGULAR);
        foreach ($operators as $id => $operator) {
            $f = $filter->getById($id);
            if ($f->getOperatorName() !== $operator) {
                if ($f->isRootNode()) {
                    $filter = $f->setOperatorName($operator);
                } else {
                    $filter->replaceById($id, $f->setOperatorName($operator));
                }
            }
        }

        if ($addTo !== null) {
            if ($addTo === '0') {
                $filter = Filter::expression($add['column'], $add['sign'], $add['value']);
            } else {
                $parent = $filter->getById($addTo);
                $f = Filter::expression($add['column'], $add['sign'], $add['value']);
                if (isset($add['operator'])) {
                    switch ($add['operator']) {
                        case 'AND':
                            if ($parent->isExpression()) {
                                if ($parent->isRootNode()) {
                                    $filter = Filter::matchAll(clone $parent, $f);
                                } else {
                                    $filter = $filter->replaceById($addTo, Filter::matchAll(clone $parent, $f));
                                }
                            } else {
                                $parent->addFilter(Filter::matchAll($f));
                            }
                            break;
                        case 'OR':
                            if ($parent->isExpression()) {
                                if ($parent->isRootNode()) {
                                    $filter = Filter::matchAny(clone $parent, $f);
                                } else {
                                    $filter = $filter->replaceById($addTo, Filter::matchAny(clone $parent, $f));
                                }
                            } else {
                                $parent->addFilter(Filter::matchAny($f));
                            }
                            break;
                        case 'NOT':
                            if ($parent->isExpression()) {
                                if ($parent->isRootNode()) {
                                    $filter = Filter::not(Filter::matchAll($parent, $f));
                                } else {
                                    $filter = $filter->replaceById($addTo, Filter::not(Filter::matchAll($parent, $f)));
                                }
                            } else {
                                $parent->addFilter(Filter::not($f));
                            }
                            break;
                    }
                } else {
                    $parent->addFilter($f);
                }
            }
        }

        return $filter;
    }

    public function renderSearch()
    {
        $preservedUrl = $this->preservedUrl();

        $html = ' <form method="post" class="search inline" action="'
              . $preservedUrl
              . '"><input type="text" name="q" style="width: 8em" class="search" value="" placeholder="'
              . t('Search...')
              . '" /></form>';

        if ($this->filter->isEmpty()) {
            $title = t('Filter this list');
        } else {
            $title = t('Modify this filter');
            if (! $this->filter->isEmpty()) {
                $title .= ': ' . $this->view()->escape($this->filter);
            }
        }

        return $html
            . '<a href="'
            . $preservedUrl->with('modifyFilter', ! $preservedUrl->getParam('modifyFilter'))
            . '" aria-label="'
            . $title
            . '" title="'
            . $title
            . '">'
            . '<i aria-hidden="true" class="icon-filter"></i>'
            . '</a>';
    }

    public function render()
    {
        if (! $this->visible) {
            return '';
        }
        if (! $this->preservedUrl()->getParam('modifyFilter')) {
            return '<div class="filter">'
                . $this->renderSearch()
                . $this->view()->escape($this->shorten($this->filter, 50))
                . '</div>';
        }
        return  '<div class="filter">'
            . $this->renderSearch()
            . '<form action="'
            . Url::fromRequest()
            . '" class="editor" method="POST">'
            . '<ul class="tree"><li>'
            . $this->renderFilter($this->filter)
            . '</li></ul>'
            . '<div class="buttons">'
            . '<input type="submit" name="submit" value="Apply" />'
            . '<input type="submit" name="cancel" value="Cancel" />'
            . '</div>'
            . '<input type="hidden" name="formUID" value="FilterEditor">'
            . '</form>'
            . '</div>';
    }

    protected function shorten($string, $length)
    {
        if (strlen($string) > $length) {
            return substr($string, 0, $length) . '...';
        }
        return $string;
    }

    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            return 'ERROR in FilterEditor: ' . $e->getMessage();
        }
    }
}
