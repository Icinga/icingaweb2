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

use Icinga\Exception\ProgrammingError;
use Icinga\Web\Url;

use Countable;

/**
 * Navigation tab widget
 *
 */
class Tabs implements Countable, Widget
{
    /**
     * This is where single tabs added to this container will be stored
     *
     * @var array
     */
    private $tabs = array();

    /**
     * The name of the currently activated tab
     *
     * @var string
     */
    private $active;

    /**
     * Class name(s) going to be assigned to the &lt;ul&gt; element
     *
     * @var string
     */
    private $tab_class = 'nav-tabs';

    /**
     * Array when special actions (dropdown) are enabled
     * @TODO: Remove special part from tabs (Bug #4512)
     *
     * @var bool|array
     */
    private $specialActions = false;

    /**
     * Activate the tab with the given name
     *
     * If another tab is currently active it will be deactivated
     *
     * @param  string $name Name of the tab going to be activated
     *
     * @throws ProgrammingError if given tab name doesn't exist
     *
     * @return self
     */
    public function activate($name)
    {
        if ($this->has($name)) {
            if ($this->active !== null) {
                $this->tabs[$this->active]->setActive(false);
            }
            $this->get($name)->setActive();
            $this->active = $name;
            return $this;
        }

        throw new ProgrammingError(
            sprintf(
                "Cannot activate a tab that doesn't exist: %s. Available: %s",
                $name,
                empty($this->tabs)
                ? 'none'
                : implode(', ', array_keys($this->tabs))
            )
        );
    }

    /**
     * Return the name of the active tab
     *
     * @return string
     */
    public function getActiveName()
    {
        return $this->active;
    }

    /**
     * Set the CSS class name(s) for the &lt;ul&gt; element
     *
     * @param  string $name CSS class name(s)
     *
     * @return self
     */
    public function setClass($name)
    {
        $this->tab_class = $name;
        return $this;
    }

    /**
     * Whether the given tab name exists
     *
     * @param  string $name Tab name
     *
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->tabs);
    }

    /**
     * Whether the given tab name exists
     *
     * @param  string $name The tab you're interested in
     *
     * @throws ProgrammingError if given tab name doesn't exist
     *
     * @return Tab
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new ProgrammingError(
                sprintf(
                    'There is no such tab: %s',
                    $name
                )
            );
        }
        return $this->tabs[$name];
    }

    /**
     * Add a new tab
     *
     * A unique tab name is required, the Tab itself can either be an array
     * with tab properties or an instance of an existing Tab
     *
     * @param  string $name                The new tab name
     * @param  array|Tab The tab itself of it's properties
     *
     * @throws ProgrammingError if tab name already exists
     *
     * @return self
     */
    public function add($name, $tab)
    {
        if ($this->has($name)) {
            throw new ProgrammingError(
                sprintf(
                    'Cannot add a tab named "%s" twice"',
                    $name
                )
            );
        }
        return $this->set($name, $tab);
    }

    /**
     * Set a tab
     *
     * A unique tab name is required, will be replaced in case it already
     * exists. The tab can either be an array with tab properties or an instance
     * of an existing Tab
     *
     * @param  string $name                The new tab name
     * @param  array|Tab The tab itself of it's properties
     *
     * @return self
     */
    public function set($name, $tab)
    {
        if ($tab instanceof Tab) {
            $this->tabs[$name] = $tab;
        } else {
            $this->tabs[$name] = new Tab($tab + array('name' => $name));
        }
        return $this;
    }

    /**
     * Enable special actions (dropdown with format, basket and dashboard)
     *
     * @TODO: Remove special part from tabs (Bug #4512)
     *
     * @return $this
     */
    public function enableSpecialActions()
    {
        $this->specialActions = true;
        return $this;
    }

    /**
     * @see Widget::render
     */
    public function render(\Zend_View_Abstract $view)
    {
        if (empty($this->tabs)) {
            return '';
        }
        $html = '<ul class="nav ' . $this->tab_class . '">' . PHP_EOL;

        foreach ($this->tabs as $tab) {
            $html .= $tab->render($view);
        }

        // @TODO: Remove special part from tabs (Bug #4512)
        $special = array();
        $special[] = $view->qlink(
            $view->img('img/classic/application-pdf.png') . ' PDF',
            Url::fromRequest(),
            array('filetype' => 'pdf'),
            array('target' => '_blank', 'quote' => false)
        );
        $special[] = $view->qlink(
            $view->img('img/classic/application-csv.png') . ' CSV',
            Url::fromRequest(),
            array('format' => 'csv'),
            array('target' => '_blank', 'quote' => false)
        );
        $special[] = $view->qlink(
            $view->img('img/classic/application-json.png') . ' JSON',
            Url::fromRequest(),
            array('format' => 'json', 'quote' => false),
            array('target' => '_blank', 'quote' => false)
        );

        $special[] = $view->qlink(
            $view->img('img/classic/basket.png') . ' URL Basket',
            Url::fromPath('basket/add'),
            array('url' => Url::fromRequest()->getRelativeUrl()),
            array('quote' => false)
        );

        $special[] = $view->qlink(
            $view->img('img/classic/dashboard.png') . ' Dashboard',
            Url::fromPath('dashboard/addurl'),
            array('url' => Url::fromRequest()->getRelativeUrl()),
            array('quote' => false)
        );
        // $auth = Auth::getInstance();
        // if ($this->specialActions && ! empty($special) && $auth->isAuthenticated() && $auth->getUsername() === 'admin') {
        if ($this->specialActions) {
            $html .= '
               <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><b class="caret"></b></a>
                <ul class="dropdown-menu">
            ';

            foreach ($special as $shtml) {
                $html .= '<li>' . $shtml . "</li>\n";
            }
            $html .= '    </ul>
               </li>
            ';

        }
        $html .= "</ul>\n";
        return $html;
    }

    /**
     * Return the number of tabs
     *
     * @see Countable
     *
     * @return int
     */
    public function count()
    {
        return count($this->tabs);
    }

    /**
     * Return all tabs contained in this tab panel
     *
     * @return array
     */
    public function getTabs()
    {
        return $this->tabs;
    }
}
