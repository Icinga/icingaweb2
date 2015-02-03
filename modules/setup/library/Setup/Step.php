<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Setup;

/**
 * Class to implement functionality for a single setup step
 */
abstract class Step
{
    /**
     * Apply this step's configuration changes
     *
     * @return  bool
     */
    abstract public function apply();

    /**
     * Return a HTML representation of this step's configuration changes supposed to be made
     *
     * @return  string
     */
    abstract public function getSummary();

    /**
     * Return a HTML representation of this step's configuration changes that were made
     *
     * @return  string
     */
    abstract public function getReport();
}
