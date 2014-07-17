<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Test;

use Icinga\Web\Form;

/**
 * Interface to test form objects
 */
interface FormTest
{
    /**
     * Instantiate a new form object
     *
     * @param   string    $formClass      Form class to instantiate
     * @param   array     $requestData    Request data for the form
     *
     * @return Form
     */
    public function createForm($formClass, array $requestData = array());
}
