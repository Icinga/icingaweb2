<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
     * @return  Requirements
     */
    public function getRequirements();
}
