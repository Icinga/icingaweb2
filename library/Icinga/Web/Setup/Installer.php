<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Setup;

/**
 * Interface for installers providing a summary and action report
 */
interface Installer
{
    /**
     * Run the installation and return whether it succeeded
     *
     * @return  bool
     */
    public function run();

    /**
     * Return a summary of all actions designated to run
     *
     * @return  array
     */
    public function getSummary();

    /**
     * Return a report of all actions that were run
     *
     * @return  array
     */
    public function getReport();
}
