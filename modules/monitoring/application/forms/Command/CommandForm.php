<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command;

use Zend_Config;
use Zend_Controller_Request_Abstract;
use Zend_Form_Element_Hidden;
use Icinga\Module\Monitoring\Command\AcknowledgeCommand;
use Icinga\Web\Form;

/**
 * Simple confirmation command
 */
abstract class CommandForm extends Form
{
    /**
     * If the form is for a global command
     *
     * @var bool
     */
    protected $globalCommand = false;

    /**
     * Set command program wide
     *
     * @param bool $flag
     */
    public function setProvideGlobalCommand($flag = true)
    {
        $this->globalCommand = (boolean) $flag;
    }

    /**
     * Getter for globalCommand
     *
     * @return bool
     */
    public function provideGlobalCommand()
    {
        return (boolean) $this->globalCommand;
    }

    /**
     * Create an instance name containing hidden field
     *
     * @return Zend_Form_Element_Hidden
     */
    private function createInstanceHiddenField()
    {
        $field = new Zend_Form_Element_Hidden('instance');
        $value = $this->getRequest()->getParam($field->getName());
        $field->setValue($value);
        return $field;
    }

    /**
     * Add elements to this form (used by extending classes)
     *
     * @see Form::create
     */
    protected function create()
    {
        $this->addElement($this->createInstanceHiddenField());
    }

    /**
     * Get the author name
     *
     * @return string
     */
    protected function getAuthorName()
    {
        if (is_a($this->getRequest(), "Zend_Controller_Request_HttpTestCase")) {
            return "Test user";
        }
        return $this->getRequest()->getUser()->getUsername();
    }

    /**
     * Creator for author field
     *
     * @return Zend_Form_Element_Hidden
     */
    protected function createAuthorField()
    {
        $authorName = $this->getAuthorName();

        $authorField = new Zend_Form_Element_Hidden(
            array(
                'name'       => 'author',
                'label'      => t('Author (Your Name)'),
                'value'      => $authorName,
                'required'   => true
            )
        );

        $authorField->addDecorator(
            'Callback',
            array(
                'callback' => function () use ($authorName) {
                    return sprintf('<strong>%s</strong>', $authorName);
                }
            )
        );

        return $authorField;
    }

    /**
     * Get a list of valid datetime formats
     *
     * @return array
     */
    public function getValidDateTimeFormats()
    {
        // TODO(mh): Missing localized format (#6077)
        return 'd/m/Y g:i A';
    }

    /**
     * Sets the form to global if we have data in the request
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function setRequest(Zend_Controller_Request_Abstract $request)
    {
        parent::setRequest($request);

        if ($request->getParam('global')) {
            $this->setProvideGlobalCommand(true);
        }
    }


    /**
     * Create command object for CommandPipe protocol
     *
     * @return AcknowledgeCommand
     */
    abstract public function createCommand();
}
