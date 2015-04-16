<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Exception\ProgrammingError;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\Tabextension;
use Icinga\Application\Icinga;
use Countable;

/**
 * Navigation tab widget
 */
class Tabs extends AbstractWidget implements Countable
{
    /**
     * Template used for the base tabs
     *
     * @var string
     */
    private $baseTpl = <<< 'EOT'
<ul class="tabs">
  {TABS}
  {DROPDOWN}
  {REFRESH}
  {CLOSE}
</ul>
EOT;

    /**
     * Template used for the tabs dropdown
     *
     * @var string
     */
    private $dropdownTpl = <<< 'EOT'
<li class="dropdown">
  <a href="#" class="dropdown-toggle"><i aria-hidden="true" class="icon-down-open"></i></a>
  <ul class="dropdown-menu">
    {TABS}
  </ul>
</li>
EOT;

    /**
     * Template used for the close-button
     *
     * @var string
     */
    private $closeTpl = <<< 'EOT'
<li class="dropdown" style="float: right;">
  <a href="#" class="dropdown-toggle close-toggle"> <i aria-hidden="true" class="icon-cancel"></i> </a>
</li>
EOT;

    /**
     * Template used for the refresh icon
     *
     * @var string
     */
    private $refreshTpl = <<< 'EOT'
<li>
  <a class="spinner" href="{URL}" title="{TITLE}" aria-label="{LABEL}">
    <i aria-hidden="true" class="icon-cw"></i>
  </a>
</li>
EOT;

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
     * Array of tab names which should be displayed in a dropdown
     *
     * @var array
     */
    private $dropdownTabs = array();

    /**
     * Whether only the close-button should by rendered for this tab
     *
     * @var bool
     */
    private $closeButtonOnly = false;

    /**
     * Whether the tabs should contain a close-button
     *
     * @var bool
     */
    private $closeTab = true;

    /**
     * Set whether the current tab is closable
     */
    public function hideCloseButton()
    {
        $this->closeTab = false;
    }

    /**
     * Activate the tab with the given name
     *
     * If another tab is currently active it will be deactivated
     *
     * @param   string $name Name of the tab going to be activated
     *
     * @return  $this
     *
     * @throws  ProgrammingError When the given tab name doesn't exist
     *
     */
    public function activate($name)
    {
        if ($this->has($name)) {
            if ($this->active !== null) {
                $this->tabs[$this->active]->setActive(false);
            }
            $this->get($name)->setActive();
            $this->active = $name;
        }
        return $this;
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
     * @param   string $name CSS class name(s)
     *
     * @return  $this
     */
    public function setClass($name)
    {
        $this->tab_class = $name;
        return $this;
    }

    /**
     * Whether the given tab name exists
     *
     * @param   string $name Tab name
     *
     * @return  bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->tabs);
    }

    /**
     * Whether the given tab name exists
     *
     * @param   string $name The tab you're interested in
     *
     * @return  Tab
     *
     * @throws  ProgrammingError When the given tab name doesn't exist
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            return null;
        }
        return $this->tabs[$name];
    }

    /**
     * Add a new tab
     *
     * A unique tab name is required, the Tab itself can either be an array
     * with tab properties or an instance of an existing Tab
     *
     * @param   string      $name   The new tab name
     * @param   array|Tab   $tab    The tab itself of its properties
     *
     * @return  $this
     *
     * @throws  ProgrammingError When the tab name already exists
     */
    public function add($name, $tab)
    {
        if ($this->has($name)) {
            throw new ProgrammingError(
                'Cannot add a tab named "%s" twice"',
                $name
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
     * @param   string      $name   The new tab name
     * @param   array|Tab   $tab    The tab itself of its properties
     *
     * @return  $this
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
     * Remove a tab
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function remove($name)
    {
        if ($this->has($name)) {
            unset($this->tabs[$name]);
            if (($dropdownIndex = array_search($name, $this->dropdownTabs)) !== false) {
                array_splice($this->dropdownTabs, $dropdownIndex, 2);
            }
        }

        return $this;
    }

    /**
     * Add a tab to the dropdown on the right side of the tab-bar.
     *
     * @param $name
     * @param $tab
     */
    public function addAsDropdown($name, $tab)
    {
        $this->set($name, $tab);
        $this->dropdownTabs[] = $name;
        $this->dropdownTabs = array_unique($this->dropdownTabs);
    }

    /**
     * Render the dropdown area with its tabs and return the resulting HTML
     *
     * @return  mixed|string
     */
    private function renderDropdownTabs()
    {
        if (empty($this->dropdownTabs)) {
            return '';
        }
        $tabs = '';
        foreach ($this->dropdownTabs as $tabname) {
            $tab = $this->get($tabname);
            if ($tab === null) {
                continue;
            }
            $tabs .= $tab;
        }
        return str_replace('{TABS}', $tabs, $this->dropdownTpl);
    }

    /**
     * Render all tabs, except the ones in dropdown area and return the resulting HTML
     *
     * @return  string
     */
    private function renderTabs()
    {
        $tabs = '';
        foreach ($this->tabs as $name => $tab) {
            // ignore tabs added to dropdown
            if (in_array($name, $this->dropdownTabs)) {
                continue;
            }
            $tabs .= $tab;
        }
        return $tabs;
    }

    private function renderCloseTab()
    {
        return $this->closeTpl;
    }

    private function renderRefreshTab()
    {
        $url = Url::fromRequest()->without('renderLayout');
        $tab = $this->get($this->getActiveName());

        if ($tab !== null) {
            $label = $this->view()->escape(
                $tab->getLabel()
            );
        }

        if (! empty($label)) {
            $caption = $label;
        } else {
            $caption = t('Content');
        }

        $label = t(sprintf('Refresh the %s', $caption));
        $title = $label;

        $tpl = str_replace(
            array(
                '{URL}',
                '{TITLE}',
                '{LABEL}'
            ),
            array(
                $url,
                $title,
                $label
            ),
            $this->refreshTpl
        );

        return $tpl;
    }

    /**
     * Render to HTML
     *
     * @see Widget::render
     */
    public function render()
    {
        if (empty($this->tabs) || true === $this->closeButtonOnly) {
            $tabs = '';
            $drop = '';
        } else {
            $tabs = $this->renderTabs();
            $drop = $this->renderDropdownTabs();
        }
        $close = $this->closeTab ? $this->renderCloseTab() : '';
        $refresh = $this->renderRefreshTab();

        return str_replace(
            array(
                '{TABS}',
                '{DROPDOWN}',
                '{REFRESH}',
                '{CLOSE}'
            ),
            array(
                $tabs,
                $drop,
                $close,
                $refresh
            ),
            $this->baseTpl
        );
    }

    public function __toString()
    {
        try {
            $html = $this->render(Icinga::app()->getViewRenderer()->view);
        } catch (Exception $e) {
            return htmlspecialchars($e->getMessage());
        }
        return $html;
    }

    /**
     * Return the number of tabs
     *
     * @return  int
     *
     * @see     Countable
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

    /**
     * Whether to hide all elements except of the close button
     *
     * @param   bool    $value
     * @return  Tabs            fluent interface
     */
    public function showOnlyCloseButton($value = true)
    {
        $this->closeButtonOnly = $value;
        return $this;
    }

    /**
     * Apply a Tabextension on this tabs object
     *
     * @param   Tabextension $tabextension
     *
     * @return  $this
     */
    public function extend(Tabextension $tabextension)
    {
        $tabextension->apply($this);
        return $this;
    }
}
