<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Setup;

/**
 * Interface for setup wizards providing an installer and requirements
 */
interface SetupWizard
{
    /**
     * Return the installer for this wizard
     *
     * @return  Installer
     */
    public function getInstaller();

    /**
     * Return the requirements of this wizard
     *
     * @return  Requirements
     */
    public function getRequirements();
}
