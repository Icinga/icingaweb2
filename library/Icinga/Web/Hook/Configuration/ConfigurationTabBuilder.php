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

namespace Icinga\Web\Hook\Configuration;

use Icinga\Exception\ProgrammingError;
use Icinga\Web\Hook;
use Icinga\Web\Widget\Tabs;

/**
 * Class ConfigurationTabBuilder
 *
 * Glue config tabs together
 *
 * @package Icinga\Web\Hook\Configuration
 */
class ConfigurationTabBuilder
{
    /**
     * Namespace for configuration tabs
     */
    const HOOK_NAMESPACE = 'Configuration/Tabs';

    /**
     * Tabs widget
     * @var Tabs
     */
    private $tabs;

    /**
     * Create a new instance
     * @param Tabs $tabs
     */
    public function __construct(Tabs $tabs)
    {
        $this->setTabs($tabs);

        $this->initializeSystemConfigurationTabs();
    }

    /**
     * Setter for tabs
     * @param \Icinga\Web\Widget\Tabs $tabs
     */
    public function setTabs($tabs)
    {
        $this->tabs = $tabs;
    }

    /**
     * Getter for tabs
     * @return \Icinga\Web\Widget\Tabs
     */
    public function getTabs()
    {
        return $this->tabs;
    }

    /**
     * Build the tabs
     *
     */
    public function build()
    {
        /** @var ConfigurationTab $configTab */
        $configTab = null;
        foreach (Hook::get(self::HOOK_NAMESPACE) as $configTab) {
            if (!$configTab instanceof ConfigurationTabInterface) {
                throw new ProgrammingError('tab not instance of ConfigTabInterface');
            }

            $this->getTabs()->add($configTab->getModuleName(), $configTab->getTab());
        }
    }

    /**
     * Initialize system configuration tabs
     */
    public function initializeSystemConfigurationTabs()
    {
        $configurationTab = new ConfigurationTab(
            'configuration',
            'configuration/index',
            'Configuration'
        );

        // Display something about us
        Hook::registerObject(
            ConfigurationTabBuilder::HOOK_NAMESPACE,
            $configurationTab->getModuleName(),
            $configurationTab
        );

        $modulesOverviewTab = new ConfigurationTab(
            'modules',
            'modules/overview',
            'Modules'
        );

        Hook::registerObject(
            ConfigurationTabBuilder::HOOK_NAMESPACE,
            $modulesOverviewTab->getModuleName(),
            $modulesOverviewTab
        );
    }
}
