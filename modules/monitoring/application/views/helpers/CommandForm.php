<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Monitoring\Form\Command\CommandForm;
use Icinga\Application\Icinga;

/**
 * Helper to build inline html command forms
 */
class Zend_View_Helper_CommandForm extends Zend_View_Helper_Abstract
{
    /**
     * Creates a simple form without additional input fields
     *
     * @param   string  $commandName    Name of command
     * @param   string  $submitLabel    Label of submit button
     * @param   array   $arguments      Add parameter as hidden fields
     *
     * @return  string                  Html form content
     */
    public function simpleForm($commandName, $submitLabel, array $arguments = array())
    {
        $form = new CommandForm();
        $form->setRequest(Icinga::app()->getFrontController()->getRequest());
        $form->setView($this->view);
        $form->setAction($this->view->href('monitoring/command/' . $commandName));
        $form->setSubmitLabel($submitLabel);
        return $form->render();
    }
}

// @codingStandardsIgnoreStop