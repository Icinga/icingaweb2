<?php
// {{{ICINGA_LICENSE_HEADER}}}
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
        foreach (Hook::all(self::HOOK_NAMESPACE) as $configTab) {
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
