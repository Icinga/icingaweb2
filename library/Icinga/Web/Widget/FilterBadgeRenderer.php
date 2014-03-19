<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Widget;

use Icinga\Filter\Query\Tree;
use Icinga\Filter\Query\Node;
use Icinga\Module\Monitoring\Filter\UrlViewFilter;
use Icinga\Web\Url;

use Zend_View_Abstract;

/**
 * A renderer for filter badges that allow to disable specific filters
 */
class FilterBadgeRenderer implements Widget
{
    private $tree;
    /**
     * @var Url
     */
    private $baseUrl;
    private $conjunctionCellar = '';
    private $urlFilter;

    private $tpl =<<<'EOT'
<div class="btn-group">
  <a title="Click To Remove" class="btn btn-default btn-xs dropdown-toggle" href="{{REMOVE_FILTER}}" data-icinga-target="self">
    {{FILTER_SUM}}
  </a>
  {{SUBLIST}}
</div>
EOT;

    private $subTpl =<<<'EOT'
  <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">
    <span class="caret"></span>
  </button>
  <ul class="dropdown-menu" role="menu">
    {{SUBFILTER_LIST}}
  </ul>
EOT;

    private $subItemTpl =<<<'EOT'
    <li><a title="Click To Remove" href="{{REMOVE_FILTER}}"  data-icinga-target="self">{{FILTER_TEXT}}</a></li>
EOT;


    /**
     * Create a new badge renderer for this tree
     *
     * @param Tree $tree
     */
    public function __construct(Tree $tree)
    {
        $this->tree = $tree;
    }

    private function getSummarizedText($node)
    {

        if (count($node->right) === 1) {
            $value = $node->right[0];
        } else {
            $value = join(',', $node->right);
            if (strlen($value) > 15) {
                $value = substr($value, 0, 13) . '..';
            }
        }
        return $this->conjunctionCellar . ' '. ucfirst($node->left) . $node->operator . $value ;
    }

    private function getSubEntries(Node $node)
    {
        $liItems = "";
        $basePath = $this->baseUrl->getAbsoluteUrl();
        $allParams = $this->baseUrl->getParams();

        foreach ($node->right as $value) {
            $newTree = $this->tree->createCopy();
            $affectedNode = $newTree->findNode($node);
            $affectedNode->right = array_diff($affectedNode->right, array($value));
            $url = $this->urlFilter->fromTree($newTree);
            $url = $basePath . (empty($allParams) ? '?' : '&') . $url;

            $liItem = str_replace('{{REMOVE_FILTER}}', $url, $this->subItemTpl);
            $liItem = str_replace('{{FILTER_TEXT}}', ucfirst($node->left) . $node->operator . $value, $liItem);
            $liItems .= $liItem;
        }

        return str_replace('{{SUBFILTER_LIST}}', $liItems, $this->subTpl);
    }

    /**
     * Create a removable badge from a query tree node
     *
     * @param Node $node        The node to create the badge for
     * @return string           The html for the badge
     */
    private function nodeToBadge(Node $node)
    {
        $basePath = $this->baseUrl->getAbsoluteUrl();
        $allParams = $this->baseUrl->getParams();

        if ($node->type === Node::TYPE_OPERATOR) {

            $newTree = $this->tree->withoutNode($node);
            $url = $this->urlFilter->fromTree($newTree);
            $url = $basePath . (empty($allParams) ? '?' : '&') . $url;
            $sumText = $this->getSummarizedText($node);

            $tpl = str_replace('{{FILTER_SUM}}', $sumText, $this->tpl);
            $tpl = str_replace('{{REMOVE_FILTER}}', $url, $tpl);
            if (count($node->right) > 1) {
                $tpl = str_replace('{{SUBLIST}}', $this->getSubEntries($node), $tpl);
            } else {
                $tpl = str_replace('{{SUBLIST}}', '', $tpl);
            }
            return  $tpl;
        }
        $result = '';
        $result .= $this->nodeToBadge($node->left);
        $this->conjunctionCellar = $node->type;
        $result .= $this->nodeToBadge($node->right);

        return $result;

    }

    /**
     * Initialize $this->baseUrl with an Url instance containing all non-filter parameter
     */
    private function buildBaseUrl()
    {
        $baseUrl = Url::fromRequest();
        foreach ($baseUrl->getParams() as $key => $param) {
            $translated = preg_replace('/[^0-9A-Za-z_]{1,2}$/', '', $key);
            if ($this->tree->hasNodeWithAttribute($translated) === true) {
                $baseUrl->removeKey($key);
            }
        }
        $this->baseUrl = $baseUrl;
    }

    /**
     * Renders this widget via the given view and returns the
     * HTML as a string
     *
     * @param \Zend_View_Abstract $view
     * @return string
     */
    public function render(Zend_View_Abstract $view)
    {
        $this->urlFilter = new UrlViewFilter();
        if ($this->tree->root == null) {
            return '';
        }
        $this->buildBaseUrl();
        return $this->nodeToBadge(Tree::normalizeTree($this->tree->root));
    }
}
