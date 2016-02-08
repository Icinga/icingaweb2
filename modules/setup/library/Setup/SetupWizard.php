<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup;

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
     * @return  RequirementSet
     */
    public function getRequirements();
}
