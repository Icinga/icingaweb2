<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Icinga\Web\Setup\Installer;

/**
 * Icinga Web 2 Installer
 */
class WebInstaller implements Installer
{
    /**
     * The setup wizard's page data
     *
     * @var array
     */
    protected $pageData;

    /**
     * Create a new web installer
     *
     * @param   array   $pageData   The setup wizard's page data
     */
    public function __construct(array $pageData)
    {
        $this->pageData = $pageData;
    }

    /**
     * @see Installer::run()
     */
    public function run()
    {
        return true;
    }

    /**
     * @see Installer::getSummary()
     */
    public function getSummary()
    {
        return array();
    }

    /**
     * @see Installer::getReport()
     */
    public function getReport()
    {
        return array();
    }
}
