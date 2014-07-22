<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Widget;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;
use Icinga\Data\Filter\FilterChain;
use Icinga\Web\Url;
use Icinga\Exception\ProgrammingError;

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

    protected function select($name, $list, $selected, $attributes = null)
    {
        $view = $this->view();
        if ($attributes === null) {
            $attributes = '';
        } else {
            $attributes = $view->propertiesToString($attributes);
        }
        $html = '<select name="' . $view->escape($name) . '"' . $attributes . ' class="autosubmit">' . "\n";

        asort($list);
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

        $removeUrl = clone $url;
        $removeUrl->setParam('removeFilter', $idx);
        $removeLink = ' <a href="' . $removeUrl . '" title="'
             . $view->escape(t('Click to remove this part of your filter'))
             . '">' . $view->icon('remove.png') .  '</a>';

        /*
        // Temporarilly removed, not implemented yet
        $addUrl = clone($url);
        $addUrl->setParam('addToId', $idx);
        $addLink = ' <a href="' . $addUrl . '" title="'
             . $view->escape(t('Click to add another operator below this one'))
             . '">' . t('Operator') .  ' (&, !, |)</a>';
        $addLink .= ' <a href="' . $addUrl . '" title="'
             . $view->escape(t('Click to add a filter expression to this operator'))
             . '">' . t('Expression') .  ' (=, &lt;, &gt;, &lt;=, &gt;=)</a>';
        */
        $selectedIndex = ($idx === $this->selectedIdx ? ' -&lt;--' : '');
        $selectIndex = ' <a href="' . $markUrl . '">o</a>';

        if ($filter instanceof FilterChain) {
            $parts = array();
            $i = 0;

            foreach ($filter->filters() as $f) {
                $i++;
                $parts[] = $this->renderFilter($f, $level + 1);
            }

            $op = $this->select(
                'operator_' . $filter->getId(),
                array(
                    'OR'  => 'OR',
                    'AND' => 'AND',
                    'NOT' => 'NOT'
                ),
                $filter->getOperatorName(),
                array('style' => 'width: 5em')
            ) . $removeLink; // Disabled: . ' ' . t('Add') . ': ' . $addLink;
            $html .= '<span class="handle"> </span>';

            if ($level === 0) {
                $html .= $op;
                 if (! empty($parts)) {
                     $html .= '<ul class="datafilter"><li>'
                         . implode('</li><li>', $parts)
                         . '</li></ul>';
                 }
            } else {
                $html .= $op . "<ul>\n <li>\n" . implode("</li>\n <li>", $parts) . "</li>\n</ul>\n";
            }
            return $html;
        }

        if ($filter instanceof FilterExpression) {
            $u = $url->without($filter->getColumn());
        } else {
           throw new ProgrammingError('Got a Filter being neither expression nor chain');
        }
        $value = $filter->getExpression();
        if (is_array($value)) {
            $value = '(' . implode('|', $value) . ')';
        }
        $html .=  $this->selectColumn($filter) . ' '
               . $this->selectSign($filter)
               . ' <input type="text" name="'
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

    protected function selectSign($filter)
    {
        $name = 'sign_' . $filter->getId();
        $signs = array(
            '=' => '=',
            '>' => '>',
            '<' => '<',
            '>=' => '>=',
            '<=' => '<=',
        );

        return $this->select(
            $name,
            $signs,
            $filter->getSign(),
            array('style' => 'width: 4em')
        );
    }
    protected function selectColumn($filter)
    {
        $name = 'column_' . $filter->getId();
        $cols = $this->arrayForSelect($this->query->getColumns());
        $active = $filter->getColumn();
        $seen = false;
        foreach ($cols as $k => & $v) {
            $v = str_replace('_', ' ', ucfirst($v));
            if ($k === $active) {
                $seen = true;
            }
        }

        if (!$seen) {
            $cols[$active] = str_replace('_', ' ', ucfirst(ltrim($active, '_')));
        }

        if ($this->query === null) {
            return sprintf(
                '<input type="text" name="%s" value="%s" />',
                $name,
                $filter->getColumn()
            );
        } else {
            return $this->select(
                $name,
                $cols,
                $active
            ); 
        }
    }

    public function render()
    {
        return '<h3>'
              . t('Modify this filter')
              . '</h3>'
              . '<form action="'
              . Url::fromRequest()
              . '" class="filterEditor" method="POST">'
              . '<ul class="tree"><li>'
              . $this->renderFilter($this->filter)
              . '</li></ul><br /><input type="submit" name="submit" value="Apply" />'
              . '</form>';
    }
}
