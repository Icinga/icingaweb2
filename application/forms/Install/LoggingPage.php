<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Install;

use Zend_Config;
use Icinga\Web\Wizard\Page;
use Icinga\Form\Config\LoggingForm;

class LoggingPage extends Page
{
    /**
     * The logging form
     *
     * @var LoggingForm
     */
    protected $loggingForm;

    /**
     * Initialize this LoggingPage
     */
    public function init()
    {
        $this->setName('logging');
    }

    /**
     * Create and return the logging form
     *
     * @return  LoggingForm
     */
    protected function createForm()
    {
        if ($this->loggingForm === null) {
            $this->loggingForm = new LoggingForm();
            $this->loggingForm->hideButtons();
            $this->loggingForm->setTokenDisabled();
            $this->loggingForm->setRequest($this->getRequest());
            $this->loggingForm->setConfiguration($this->getConfiguration());
        }

        return $this->loggingForm;
    }

    /**
     * Create this wizard page
     */
    protected function create()
    {
        $loggingForm = $this->createForm();
        $loggingForm->buildForm(); // Needs to get called manually as it's nothing that Zend knows about
        $this->addSubForm($loggingForm, $loggingForm->getName());
    }

    /**
     * Return a config containing all values provided by the user
     *
     * @return  Zend_Config
     */
    public function getConfig()
    {
        return $this->createForm()->getConfig();
    }
}
