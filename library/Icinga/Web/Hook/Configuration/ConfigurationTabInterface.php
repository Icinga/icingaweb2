<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Hook\Configuration;

/**
 * Interface ConfigurationTabInterface
 *
 * Used to register configuration tab settings
 *
 * @package Icinga\Web\Hook\Configuration
 */
interface ConfigurationTabInterface
{
    /**
     * Returns a tab configuration to build configuration links
     * @return array
     */
    public function getTab();

    /**
     * Return the tab key
     * @return string
     */
    public function getModuleName();
}
