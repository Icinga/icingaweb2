<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Setup;

use Icinga\Web\Form;
use Icinga\Web\Setup\Requirements;

/**
 * Wizard page to list installation requirements
 */
class RequirementsPage extends Form
{
    /**
     * The requirements to list
     *
     * @var Requirements
     */
    protected $requirements;

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_requirements');
        $this->setViewScript('form/setup-requirements.phtml');
    }

    /**
     * Set the requirements to list
     *
     * @param   Requirements    $requirements
     *
     * @return  self
     */
    public function setRequirements(Requirements $requirements)
    {
        $this->requirements = $requirements;
        return $this;
    }

    /**
     * Return the requirements to list
     *
     * @return  Requirements
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * Validate the given form data and check whether the requirements are fulfilled
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

        return $this->requirements->fulfilled();
    }
}
