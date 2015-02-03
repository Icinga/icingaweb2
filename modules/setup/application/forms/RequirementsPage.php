<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Setup\Forms;

use Icinga\Web\Form;
use Icinga\Module\Setup\Requirements;

/**
 * Wizard page to list setup requirements
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
