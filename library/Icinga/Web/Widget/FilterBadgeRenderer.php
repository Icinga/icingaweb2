<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}


namespace Icinga\Web\Widget;


use Icinga\Filter\Query\Tree;
use Icinga\Filter\Query\Node;
use Zend_View_Abstract;

class FilterBadgeRenderer implements Widget
{
    private $tree;
    private $conjunctionCellar = '';


    public function __construct(Tree $tree)
    {
        $this->tree = $tree;
    }

    private function nodeToBadge(Node $node)
    {
        if ($node->type === Node::TYPE_OPERATOR) {
            return  ' <a class="btn btn-default btn-xs">'
                    . $this->conjunctionCellar .  ' '
                    . ucfirst($node->left) . ' '
                    . $node->operator . ' '
                    . $node->right . '</a>';
        }
        $result = '';
        $result .= $this->nodeToBadge($node->left);
        $this->conjunctionCellar = $node->type;
        $result .= $this->nodeToBadge($node->right);
        return $result;

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
        if ($this->tree->root == null) {
            return '';
        }
        return $this->nodeToBadge($this->tree->root);
    }
}