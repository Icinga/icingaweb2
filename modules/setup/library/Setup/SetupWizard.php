<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Setup;

/**
 * Interface for wizards providing a setup and requirements
 */
interface SetupWizard
{
    /**
     * Return the setup for this wizard
     *
     * @return  Setup
     */
    public function getSetup();

    /**
     * Return the requirements of this wizard
     *
     * @return  Requirements
     */
    public function getRequirements();
}
