<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Icinga\Web\Form;
use Icinga\Module\Setup\SetupWizard;

/**
 * Wizard page to list setup requirements
 */
class RequirementsPage extends Form
{
    /**
     * The wizard
     *
     * @var SetupWizard
     */
    protected $wizard;

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_requirements');
        $this->setViewScript('form/setup-requirements.phtml');
    }

    /**
     * Set the wizard
     *
     * @param   SetupWizard    $wizard
     *
     * @return  $this
     */
    public function setWizard(SetupWizard $wizard)
    {
        $this->wizard = $wizard;
        return $this;
    }

    /**
     * Return the wizard
     *
     * @return  SetupWizard
     */
    public function getWizard()
    {
        return $this->wizard;
    }

    /**
     * Validate the given form data and check whether the wizard's requirements are fulfilled
     *
     * @param   array   $data   The data to validate
     *
     * @return  bool
     */
    public function isValid($data)
    {
        if (false === parent::isValid($data)) {
            return false;
        }

        return $this->wizard->getRequirements()->fulfilled();
    }
}
