<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;
use Icinga\Data\Filter\FilterChain;
use Icinga\Web\Url;

/**
 * Filter
 */
class FilterWidget extends AbstractWidget
{
    /**
     * The filter
     *
     * @var Filter
     */
    private $filter;

    /**
     * The domain of the filter, set in the data-icinga-filter-domain attribute
     * @var string
     */
    private $domain;

    /**
     * Create a new FilterWidget
     *
     * @param Filter $filter Your filter
     */
    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }

    protected function renderFilter($filter, $level = 0)
    {
        $html = '';
        $url = Url::fromRequest();
        if ($filter instanceof FilterChain) {
            if ($level === 0) {
                $op = '</li><li>)' . $filter->getOperatorName() . ' (';
            } else {
                $op = '</li><li>) ' . $filter->getOperatorName() . ' ( ';
            }
            $parts = array();
            foreach ($filter->filters() as $f) {
                $parts[] = $this->renderFilter($f, $level + 1);
            }
            if (empty($parts)) {
                return $html;
            }
            if ($level === 0) {
                $html .= '<ul class="datafilter"><li>( ' . implode($op, $parts) . ' )</li></ul>';
            } else {
                $html .= '<ul><li>( ' . implode($op, $parts) . ' )</li></ul>';
            }
            return $html;
        } elseif ($filter instanceof FilterExpression) {
            $u = $url->without($filter->getColumn());
        } else {
            $u = $url . '--';
        }
        $html .= '<a href="' . $url . '" title="'
               . $this->view()->escape(t('Click to remove this part of your filter'))
               . '">' . $filter . '</a> ';
        return $html;
    }

    public function render()
    {
        $url = Url::fromRequest();
        $view = $this->view();
        $html = ' <form method="post" class="inline" action="'
              . $url
              . '"><input type="text" name="q" style="width: 8em" class="search" value="" placeholder="'
              . t('Add filter...')
              . '" /></form>';


        // $html .= $this->renderFilter($this->filter);

        $editorUrl = clone $url;
        $editorUrl->setParam('modifyFilter', true);
        if ($this->filter->isEmpty()) {
            $title = t('Filter this list');
            $txt = $view->icon('plus');
            $remove = '';
        } else {
            $txt = t('Filtered');
            $title = t('Modify this filter');
            $remove = ' <a href="'
                . Url::fromRequest()->setParams(array())
                . '" title="'
                . t('Remove this filter')
                . '">'
                . $view->icon('cancel')
                . '</a>';
        }
        $filter = $this->filter->isEmpty() ? '' : ': ' . $this->filter;
        $html .= ($filter ? '<p>' : ' ')
               . '<a href="' . $editorUrl . '" title="' . $title . '">'
               . $txt
               . '</a>'
               . $this->shorten($filter, 72)
               . $remove
               . ($filter ? '</p>' : '');

        return $html;
    }

    protected function shorten($string, $length)
    {
        if (strlen($string) > $length) {
            return substr($string, 0, $length) . '...';
        }
        return $string;
    }
}
