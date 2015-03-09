<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Icinga\Web\Form;
use Icinga\Module\Setup\RequirementSet;

/**
 * Wizard page to list setup requirements
 */
class RequirementsPage extends Form
{
    /**
     * The requirements to list
     *
     * @var RequirementSet
     */
    protected $set;

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
     * @param   RequirementSet    $set
     *
     * @return  self
     */
    public function setRequirements(RequirementSet $set)
    {
        $this->set = $set;
        return $this;
    }

    /**
     * Return the requirements to list
     *
     * @return  RequirementSet
     */
    public function getRequirements()
    {
        return $this->set;
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

        return $this->set->fulfilled();
    }
}
