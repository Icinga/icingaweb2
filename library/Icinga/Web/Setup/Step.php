<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Setup;

/**
 * Class to implement functionality for a single installation step
 */
abstract class Step
{
    /**
     * Apply this step's installation changes
     *
     * @return  bool
     */
    abstract public function apply();

    /**
     * Return a HTML representation of this step's installation changes supposed to be made
     *
     * @return  string
     */
    abstract public function getSummary();

    /**
     * Return a HTML representation of this step's installation changes that were made
     *
     * @return  string
     */
    abstract public function getReport();
}
