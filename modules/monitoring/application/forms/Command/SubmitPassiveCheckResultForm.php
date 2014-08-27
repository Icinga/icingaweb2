<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command;

use \Icinga\Exception\ProgrammingError;

/**
 * Form for submitting passive check results
 */
class SubmitPassiveCheckResultForm extends CommandForm
{
    /**
     * Type constant for host form
     */
    const TYPE_HOST = 'host';

    /**
     * Type constant for service form
     */
    const TYPE_SERVICE = 'service';

    /**
     * List of choices for plugin states
     * @var array
     */
    private static $options = array();

    /**
     * Type of form
     * @var string
     */
    private $type;

    /**
     * Setup plugin states
     *
     * @see Zend_Form::init
     */
    public function init()
    {
        if (!count(self::$options)) {
            self::$options = array(
                self::TYPE_HOST => array(
                    0 => t('UP'),
                    1 => t('DOWN'),
                    2 => t('UNREACHABLE')
                ),
                self::TYPE_SERVICE => array(
                    0 => t('OK'),
                    1 => t('WARNING'),
                    2 => t('CRITICAL'),
                    3 => t('UNKNOWN')
                )
            );
        }

        parent::init();
    }

    /**
     * Setter for type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Getter for type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Return array of options
     *
     * @return array
     * @throws \Icinga\Exception\ProgrammingError
     */
    public function getOptions()
    {
        if (in_array($this->getType(), array(self::TYPE_HOST, self::TYPE_SERVICE)) === false) {
            throw new ProgrammingError('Type is not valid');
        }

        return self::$options[$this->getType()];
    }

    /**
     * Create the form's elements
     */
    protected function create()
    {
        $this->setName('form_submit_passive_checkresult');

        $this->addNote(
            t(
                'This command is used to submit a passive check result for particular hosts/services. It is '
                . 'particularly useful for resetting security-related objects to OK states once they have been '
                . 'dealt with.'
            )
        );

        $this->addElement(
            'select',
            'pluginstate',
            array(
                'label'        => t('Check Result'),
                'multiOptions' => $this->getOptions(),
                'required'     => true,
                'validators'   => array(
                    array(
                        'Digits',
                        true
                    ),
                    array(
                        'InArray',
                        true,
                        array(
                            array_keys($this->getOptions())
                        )
                    )
                ),
                'helptext'     => t('Set the state which should be send to Icinga for this objects.')
            )
        );

        $this->addElement(
            'textarea',
            'checkoutput',
            array(
                'label'    => t('Check Output'),
                'rows'     => 2,
                'cols'     => 72,
                'required' => true,
                'helptext' => t('Fill in the check output string which should be send to Icinga.')
            )
        );

        $this->addElement(
            'textarea',
            'performancedata',
            array(
                'label'    => t('Performance Data'),
                'rows'     => 2,
                'cols'     => 72,
                'helptext' => t('Fill in the performance data string which should be send to Icinga.')
            )
        );

        $this->setSubmitLabel(t('Submit Passive Check Result'));

        parent::create();
    }

    /**
     * Create the submit passive checkresult command object
     *
     * @return SubmitPassiveCheckresultCommand
     */
    public function createCommand()
    {
        return new SubmitPassiveCheckresultCommand(
            $this->getValue('pluginstate'),
            $this->getValue('checkoutput'),
            $this->getValue('performancedata')
        );
    }
}
