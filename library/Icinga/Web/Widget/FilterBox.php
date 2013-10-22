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

use Zend_View_Abstract;

use Icinga\Web\Form;
use Icinga\Web\Url;
use Icinga\Filter\Query\Tree;

/**
 * Widget that renders a filter input box together with an FilterBadgeRenderer widget
 */
class FilterBox implements Widget
{
    /**
     * An optional initial filter to use
     *
     * @var \Icinga\Filter\Query\Tree
     */
    private $initialFilter;

    /**
     * The domain of the filter, set in the data-icinga-filter-domain attribute
     * @var string
     */
    private $domain;

    /**
     * The module of the filter, set in the data-icinga-filter-module attribute
     * @var string
     */
    private $module;

    /**
     * The template used for rendering the form and badges
     * @var string
     */
    private static $TPL = <<<'EOT'
<div class="row">
    {{FORM}}
    {{BADGES}}
</div>
EOT;

    /**
     * Create a new FilterBox widget
     *
     * @param Tree $initialFilter       The tree to use for initial population
     * @param String $domain            The filter domain
     * @param String $module            The filter module
     */
    public function __construct(Tree $initialFilter, $domain, $module)
    {
        $this->initialFilter = $initialFilter;
        $this->domain = $domain;
        $this->module = $module;
    }

    /**
     * Render this widget
     *
     * @param Zend_View_Abstract $view  The view to use for rendering the widget
     * @return string                   The HTML of the widget as a string
     */
    public function render(Zend_View_Abstract $view)
    {

        $form = new Form();
        $form->setAttrib('class', 'form-inline');
        $form->setMethod('GET');
        $form->setAction(Url::fromPath('/filter'));
        $form->setTokenDisabled();
        $form->addElement(
            'text',
            'query',
            array(
                'label'  => 'Filter Results',
                'name'   => 'query',
                'data-icinga-component' => 'app/semanticsearch',
                'data-icinga-filter-domain'    => $this->domain,
                'data-icinga-filter-module'    => $this->module
            )
        );
        $form->removeAttrib('data-icinga-component');

        $form->setIgnoreChangeDiscarding(true);
        $badges = new FilterBadgeRenderer($this->initialFilter);
        $html = str_replace('{{FORM}}', $form->render($view), self::$TPL);
        $html = '<div class="input-append">' . $html . '</div>';
        return str_replace('{{BADGES}}', $badges->render($view), $html);
    }
}
