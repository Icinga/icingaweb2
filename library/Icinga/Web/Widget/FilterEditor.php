<?php

namespace Icinga\Web\Widget;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;
use Icinga\Data\Filter\FilterChain;
use Icinga\Web\Url;

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

    protected $query;

    /**
     * @var string
     */
    private $selectedIdx;

    /**
     * Create a new FilterWidget
     *
     * @param Filter $filter Your filter
     */
    public function __construct($props)
    {
        $this->filter = $props['filter'];
        if (array_key_exists('query', $props)) {
            $this->query = $props['query'];
        }
    }

    protected function select($name, $list, $selected)
    {
        $view = $this->view();
        $html = '<select name="' . $view->escape($name) . '">';
        foreach ($list as $k => $v) {
            $active = '';
            if ($k === $selected) {
                $active = ' selected="selected"';
            }
            $html .= sprintf(
                '<option value="%s"%s>%s</option>',
                $view->escape($k),
                $active,
                $v
            );
        }
        $html .= '</select>';
        return $html;
    }

    public function markIndex($idx)
    {
        $this->selectedIdx = $idx;
        return $this;
    }

    public function removeIndex($idx)
    {
        $this->selectedIdx = $idx;
        return $this;
    }

    protected function renderFilter($filter, $level = 0)
    {
        $html = '';
        $url = Url::fromRequest();

        $view = $this->view();
        $idx = $filter->getId();
        $markUrl = clone($url);
        $markUrl->setParam('fIdx', $idx);

        $removeUrl = clone($url);
        $removeUrl->setParam('removeId', $idx);
        $removeLink = ' <a href="' . $removeUrl . '" title="'
             . $view->escape(t('Click to remove this part of your filter'))
             . '">' . $view->icon('remove.png') .  '</a>';

        $addUrl = clone($url);
        $addUrl->setParam('addToId', $idx);
        $addLink = ' <a href="' . $addUrl . '" title="'
             . $view->escape(t('Click to add... filter'))
             . '">' . $view->icon('create.png') .  '</a>';


        $selectedIndex = ($idx === $this->selectedIdx ? ' -&lt;--' : '');
        $selectIndex = ' <a href="' . $markUrl . '">o</a>';

        if ($filter instanceof FilterChain) {
            $parts = array();
            $i = 0;

            foreach ($filter->filters() as $f) {
                $i++;
                $parts[] = $this->renderFilter($f, $level + 1);
            }

            if (empty($parts)) {
                return $html;
            }
            $op = $this->select(
                'operator',
                array(
                    'OR'  => 'OR',
                    'AND' => 'AND',
                    'NOT' => 'NOT'
                ),
                $filter->getOperatorName()
            ) . $addLink . $removeLink;
             $html .= '<span class="handle"> </span>';

            if ($level === 0) {
                $html .= $op
                 . '<ul class="datafilter"><li>'
                 . implode('</li><li>', $parts)
                 . '</li></ul>';
            } else {
                $html .= $op . '<ul><li>' . implode('</li><li>', $parts) . '</li></ul>';
            }
            return $html;

        } elseif ($filter instanceof FilterExpression) {
            $u = $url->without($filter->getColumn());
        } else {
           throw new \Exception('WTF');
        }
        $value = $filter->getExpression();
        if (is_array($value)) {
            $value = implode('|', $value);
        }
        $html .=  $this->selectColumn($filter) . ' = <input type="text" name="'
               . 'value_' . $idx
               . '" value="'
               . $value
               . '" /> ' . $removeLink;

        return $html;
    }

    protected function arrayForSelect($array)
    {
        $res = array();
        foreach ($array as $k => $v) {
            if (is_int($k)) {
                $res[$v] = $v;
            } else {
                $res[$k] = $v;
            }
        }
        // sort($res);
        return $res;
    }

    protected function selectColumn($filter)
    {
        $name = 'column_' . $filter->getId();

        if ($this->query === null) {
            return sprintf(
                '<input type="text" name="%s" value="%s" />',
                $name,
                $filter->getColumn()
            );
        } else {
            return $this->select(
                $name,
                $this->arrayForSelect($this->query->getColumns()),
                $filter->getColumn()
            ); 
        }
    }

    public function render()
    {
        return '<form action="'
              . Url::fromRequest()->without('modifyFilter')
              . '">'
              . $this->renderFilter($this->filter)
              . '<input type="submit" name="submit" value="Apply" />'
              . '</form>';
    }
}
