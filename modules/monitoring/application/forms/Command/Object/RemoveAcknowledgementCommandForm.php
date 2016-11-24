<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\RemoveAcknowledgementCommand;
use Icinga\Web\Notification;

/**
 * Form for removing host or service problem acknowledgements
 */
class RemoveAcknowledgementCommandForm extends ObjectsCommandForm
{
    /**
     * Whether to show the submit label next to the remove icon
     *
     * The submit label is disabled in detail views but should be enabled in multi-select views.
     *
     * @var bool
     */
    protected $labelEnabled = false;

    /**
     * Whether to show the submit label next to the remove icon
     *
     * @return bool
     */
    public function isLabelEnabled()
    {
        return $this->labelEnabled;
    }

    /**
     * Set whether to show the submit label next to the remove icon
     *
     * @param   bool    $labelEnabled
     *
     * @return  $this
     */
    public function setLabelEnabled($labelEnabled)
    {
        $this->labelEnabled = (bool) $labelEnabled;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setAttrib('class', 'inline');
    }

    /**
     * {@inheritdoc}
     */
    public function addSubmitButton()
    {
        $this->addElement(
            'button',
            'btn_submit',
            array(
                'class'         => 'link-button spinner',
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'control-group form-controls'))
                ),
                'escape'        => false,
                'ignore'        => true,
                'label'         => $this->getSubmitLabel(),
                'title'         => $this->translatePlural(
                    'Remove acknowledgement',
                    'Remove acknowledgements',
                    count($this->objects)
                ),
                'type'          => 'submit'
            )
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubmitLabel()
    {
        $label = $this->getView()->icon('cancel');
        if ($this->isLabelEnabled()) {
            $label .= $this->translatePlural(
                'Remove acknowledgement',
                'Remove acknowledgements',
                count($this->objects)
            );
        }

        return $label;
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess()
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\MonitoredObject $object */
            $removeAck = new RemoveAcknowledgementCommand();
            $removeAck->setObject($object);
            $this->getTransport($this->request)->send($removeAck);
        }
        Notification::success(mtp(
            'monitoring',
            'Removing acknowledgement..',
            'Removing acknowledgements..',
            count($this->objects)
        ));

        return true;
    }
}
